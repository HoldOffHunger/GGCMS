<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/MySQLDatabases.php');
	clireq('traits/MySQLInternalDatabases.php');
	clireq('traits/MySQLGGCMSInternalDatabases.php');
	clireq('traits/MySQLClustersInternalDatabases.php');

		/*
			One-off conversion of the ISE and ISI tables from journal-shaped to
			counted ones.  Before this, every occurrence of a fault was its own
			row carrying a print_r() of the whole handler; one rotated log held
			2,300,533 copies of a single mysqli_close() fatal.

			What it does, per database, per table:

			  1.  add Signature, Script and IncidentCount, and the Instance table
			  2.  give every existing row the signature the running code would
			      give it -- old rows have no Script, so they sign as the empty
			      one, which is what the code produces when the handler died
			      before it named a script
			  3.  roll each signature up onto its oldest row: the sum of the
			      occurrences, the earliest first-seen, the latest last-seen
			  4.  delete the rest, and only then add the unique key -- it cannot
			      go on first, because until step 3 every row shares its
			      signature with its duplicates

			The surviving rows keep one sample of the context, from the oldest
			occurrence.  Per-occurrence context for history is not preserved;
			that is the point, and it is where the space comes back.
			Occurrences from here on each get an Instance row.

			Safe to run twice: every step tests for its own effect first.

			Kept after it has been run.  A one-off script is the only durable record
			of a shape the database no longer has, and it is a better record than a
			document because it cannot drift from what actually happened -- the
			steps below are the steps that ran.
		*/

	class ConvertISEandISITables {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use MySQLDatabases;
		use MySQLInternalDatabases;
		use MySQLGGCMSInternalDatabases;
		use MySQLClustersInternalDatabases;

		public function convertISEandISITables() {
			$this->setHandle();
			$this->bannerMessage();

			$this->setGlobals();
			$this->migrateDatabases();

			return TRUE;
		}

			/*
				This deletes rows, so it defaults to the narrowest scope it has: one
				domain, named on the command line or asked for.  `all` is the explicit
				opt-in for every database at once, and is worth earning by migrating a
				small site first and reading the before-and-after numbers.
			*/

		public function migrateDatabases() {
			$databases = $this->migrateDatabases_Chosen();

			foreach($databases as $database) {
				print(PHP_EOL . $database . PHP_EOL);

				$this->migrateTable([
					'database'=>$database,
					'table'=>'InternalServerError',
					'instancetable'=>'InternalServerErrorInstance',
					'instancefield'=>'Errorid',
					'signaturefields'=>['Script', 'ErrorMessage'],
				]);

				$this->migrateTable([
					'database'=>$database,
					'table'=>'InternalServerIssue',
					'instancetable'=>'InternalServerIssueInstance',
					'instancefield'=>'Issueid',
					'signaturefields'=>['Script', 'IssueType', 'Description'],
				]);
			}

			print(PHP_EOL . 'Error and issue queues migrated.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function migrateDatabases_Chosen() {
			if($this->DomainArgumentIsAll()) {
				print('Migrating every database.' . PHP_EOL);

				return $this->getUserMySQLDatabases();
			}

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`, or `all`.']);
			}

			return [$this->host];
		}

		public function DomainArgumentIsAll() {
			if(!array_key_exists(1, $this->argv)) {
				return FALSE;
			}

			return strtolower(trim($this->argv[1])) === 'all';
		}

		public function migrateTable($args) {
			$qualified = $args['database'] . '.' . $args['table'];

			$rows_before = $this->rowCount(['table'=>$qualified]);

			$this->addCountingColumns($args);
			$this->addInstanceTable($args);
			$this->setSignatures($args);
			$this->rollUpDuplicates($args);
			$this->addSignatureKey($args);

			$rows_after = $this->rowCount(['table'=>$qualified]);

			print('  ' . $args['table'] . ': ' . $rows_before . ' rows -> ' . $rows_after . ' tickets' . PHP_EOL);

			return TRUE;
		}

			/*
				Every statement goes through `mysql -e`, the way the rest of
				these tools reach the database, because CLI PHP on the host has
				no mysqli.  See Docs/Triage.md.
			*/

		public function runSQL($args) {
			$command = 'mysql --batch --skip-column-names -e ' . escapeshellarg($args['sql']) . ' 2>&1';

			return trim((string)shell_exec($command));
		}

		public function rowCount($args) {
			return $this->runSQL([
				'sql'=>'SELECT COUNT(id) FROM ' . $args['table'] . ';',
			]);
		}

		public function quote($args) {
			return "'" . str_replace("'", "''", $args['value']) . "'";
		}

		public function columnExists($args) {
			$sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS';
			$sql .= ' WHERE TABLE_SCHEMA = ' . $this->quote(['value'=>$args['database']]);
			$sql .= ' AND TABLE_NAME = ' . $this->quote(['value'=>$args['table']]);
			$sql .= ' AND COLUMN_NAME = ' . $this->quote(['value'=>$args['column']]) . ';';

			return (int)$this->runSQL(['sql'=>$sql]) > 0;
		}

		public function indexExists($args) {
			$sql = 'SELECT COUNT(*) FROM information_schema.STATISTICS';
			$sql .= ' WHERE TABLE_SCHEMA = ' . $this->quote(['value'=>$args['database']]);
			$sql .= ' AND TABLE_NAME = ' . $this->quote(['value'=>$args['table']]);
			$sql .= ' AND INDEX_NAME = ' . $this->quote(['value'=>$args['index']]) . ';';

			return (int)$this->runSQL(['sql'=>$sql]) > 0;
		}

		public function addCountingColumns($args) {
			$column_exists_args = [
				'database'=>$args['database'],
				'table'=>$args['table'],
				'column'=>'Signature',
			];

			if($this->columnExists($column_exists_args)) {
				return FALSE;
			}

			$sql = 'ALTER TABLE ' . $args['database'] . '.' . $args['table'];
			$sql .= " ADD COLUMN Signature char(64) NOT NULL DEFAULT '' AFTER id,";
			$sql .= " ADD COLUMN Script varchar(255) NOT NULL DEFAULT '' AFTER Signature,";
			$sql .= " ADD COLUMN IncidentCount int NOT NULL DEFAULT '1' AFTER Script;";

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

		public function addInstanceTable($args) {
			$sql = 'CREATE TABLE IF NOT EXISTS ' . $args['database'] . '.' . $args['instancetable'] . ' (';
			$sql .= ' id int NOT NULL AUTO_INCREMENT,';
			$sql .= ' ' . $args['instancefield'] . " int NOT NULL DEFAULT '0',";
			$sql .= " URL varchar(1024) NOT NULL DEFAULT '',";
			$sql .= " OriginalCreationDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql .= " LastModificationDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql .= ' PRIMARY KEY (id),';
			$sql .= ' KEY ' . $args['instancefield'] . ' (' . $args['instancefield'] . '),';
			$sql .= ' KEY OriginalCreationDate (OriginalCreationDate),';
			$sql .= ' KEY LastModificationDate (LastModificationDate)';
			$sql .= ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

			/*
				SHA2(..., 256) over the fields joined by a newline is the same
				string the running code hashes with hash('sha256', ...).  Keep
				the two in step: ErrorLogging::ErrorSignature() and
				IssueLogging::IssueSignature().
			*/

		public function setSignatures($args) {
			$sql = 'UPDATE ' . $args['database'] . '.' . $args['table'];
			$sql .= ' SET Signature = SHA2(CONCAT_WS(CHAR(10), ' . implode(', ', $args['signaturefields']) . '), 256)';
			$sql .= " WHERE Signature = '';";

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

		public function rollUpDuplicates($args) {
			$qualified = $args['database'] . '.' . $args['table'];
			$rollup = $qualified . 'Rollup';

			$this->runSQL(['sql'=>'DROP TABLE IF EXISTS ' . $rollup . ';']);

			$sql = 'CREATE TABLE ' . $rollup . ' AS SELECT';
			$sql .= ' MIN(id) AS KeepId,';
			$sql .= ' Signature AS Signature,';
			$sql .= ' SUM(IncidentCount) AS Incidents,';
			$sql .= ' MIN(OriginalCreationDate) AS FirstSeen,';
			$sql .= ' MAX(LastModificationDate) AS LastSeen';
			$sql .= ' FROM ' . $qualified . ' GROUP BY Signature;';

			$this->runSQL(['sql'=>$sql]);
			$this->runSQL(['sql'=>'ALTER TABLE ' . $rollup . ' ADD PRIMARY KEY (KeepId);']);

			$sql = 'UPDATE ' . $qualified . ' AS t';
			$sql .= ' JOIN ' . $rollup . ' AS r ON r.KeepId = t.id';
			$sql .= ' SET t.IncidentCount = r.Incidents,';
			$sql .= ' t.OriginalCreationDate = r.FirstSeen,';
			$sql .= ' t.LastModificationDate = r.LastSeen;';

			$this->runSQL(['sql'=>$sql]);

			$sql = 'DELETE t FROM ' . $qualified . ' AS t';
			$sql .= ' LEFT JOIN ' . $rollup . ' AS r ON r.KeepId = t.id';
			$sql .= ' WHERE r.KeepId IS NULL;';

			$this->runSQL(['sql'=>$sql]);
			$this->runSQL(['sql'=>'DROP TABLE ' . $rollup . ';']);

			return TRUE;
		}

		public function addSignatureKey($args) {
			$index_exists_args = [
				'database'=>$args['database'],
				'table'=>$args['table'],
				'index'=>'Signature',
			];

			if($this->indexExists($index_exists_args)) {
				return FALSE;
			}

			$sql = 'ALTER TABLE ' . $args['database'] . '.' . $args['table'];
			$sql .= ' ADD UNIQUE KEY Signature (Signature);';

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Roll the error and issue queues up into counted tickets.  Duplicate rows are deleted.',
			]);
		}

		public function bannerMessageText() {
			return 'Convert The ISE And ISI Tables';
		}

		public function confirmDomainText() {
			return 'Converting Tables For: ';
		}
	}

?>

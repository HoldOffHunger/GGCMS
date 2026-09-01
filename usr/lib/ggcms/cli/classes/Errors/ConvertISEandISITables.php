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
			counted.  Before this, every occurrence of a fault was its own row
			carrying a print_r() of the whole handler; one rotated log held
			2,300,533 copies of a single mysqli_close() fatal.

			It builds the new table beside the old one rather than editing the
			old one in place:

			  1.  rename InternalServerError to InternalServerErrorOld
			  2.  create the new InternalServerError, and its Instance table,
			      with the unique key present from the start
			  3.  select one row per signature out of the old table -- the
			      oldest, with its context -- and insert it with the summed
			      count and the outer dates
			  4.  leave the old table on disk, and print the DROP for later

			Editing in place was the first draft and it was worse.  ADD COLUMN
			rebuilds a 2.3-million-row table; the UPDATE that signs every row
			writes all of it again; the DELETE that removes the duplicates
			writes it a third time and returns not one byte to the filesystem,
			because InnoDB does not shrink a tablespace -- reclaiming it would
			need OPTIMIZE TABLE, a fourth rebuild.  On a host whose disk has
			filled once already, that is the whole problem.  Building a small
			table and dropping a large one gives the space back at the drop.

			The old table is deliberately left behind.  While it is there the
			conversion is reversible by a rename, so the numbers can be read
			before anything is destroyed.  Dropping it is a separate, deliberate
			act; the command is printed at the end.

			Safe to run twice: a table that already has a Signature column is
			left alone, and so is one whose Old table is already present.

			Kept after it has been run.  A one-off script is the only durable
			record of a shape the database no longer has, and it is a better
			record than a document, because it cannot drift from what actually
			happened -- the steps below are the steps that ran.
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
			$this->convertDatabases();

			return TRUE;
		}

			/*
				This renames a live table, so it defaults to the narrowest scope
				it has: one domain, named on the command line or asked for.
				`all` is the explicit opt-in, and is worth earning by converting
				a small site first and reading the before-and-after numbers.
			*/

		public function convertDatabases() {
			$databases = $this->convertDatabases_Chosen();

			foreach($databases as $database) {
				print(PHP_EOL . $database . PHP_EOL);

				$this->convertTable($this->ErrorTableDefinition(['database'=>$database]));
				$this->convertTable($this->IssueTableDefinition(['database'=>$database]));
			}

			print(PHP_EOL . 'ISE and ISI tables converted.' . PHP_EOL);
			print('The old tables are still on disk.  Read the numbers, then drop them.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function convertDatabases_Chosen() {
			if($this->DomainArgumentIsAll()) {
				print('Converting every database.' . PHP_EOL);

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

			// Table Definitions
			// -------------------------------------------------

			/*
				`signature` is the SQL spelling of what the running code hashes.
				Keep the two in step: ErrorLogging::ErrorSignature() joins the
				script and the message with a newline, and
				IssueLogging::IssueSignature() joins the script, the type and
				the description.

				The old rows have no script, so they sign with an empty one --
				which is exactly what the code produces when the handler died
				before it named a script.
			*/

		public function ErrorTableDefinition($args) {
			return [
				'database'=>$args['database'],
				'table'=>'InternalServerError',
				'instancetable'=>'InternalServerErrorInstance',
				'instancefield'=>'Errorid',
				'signature'=>"SHA2(CONCAT_WS(CHAR(10), '', ErrorMessage), 256)",
				'context'=>[
					'ErrorMessage',
					'URL',
					'ServerVariable',
					'PostVariable',
					'GetVariable',
					'EnvironmentVariables',
				],
				'columns'=>[
					'ErrorMessage text NOT NULL',
					"URL varchar(1024) NOT NULL DEFAULT ''",
					'ServerVariable text NOT NULL',
					'PostVariable text NOT NULL',
					'GetVariable text NOT NULL',
					'EnvironmentVariables text NOT NULL',
				],
			];
		}

		public function IssueTableDefinition($args) {
			return [
				'database'=>$args['database'],
				'table'=>'InternalServerIssue',
				'instancetable'=>'InternalServerIssueInstance',
				'instancefield'=>'Issueid',
				'signature'=>"SHA2(CONCAT_WS(CHAR(10), '', IssueType, Description), 256)",
				'context'=>[
					'IssueType',
					'URL',
					'Description',
					'ServerVariable',
					'PostVariable',
					'GetVariable',
				],
				'columns'=>[
					"IssueType varchar(512) NOT NULL DEFAULT ''",
					"URL varchar(1024) NOT NULL DEFAULT ''",
					"Description varchar(2048) NOT NULL DEFAULT ''",
					'ServerVariable text NOT NULL',
					'PostVariable text NOT NULL',
					'GetVariable text NOT NULL',
				],
			];
		}

			// Conversion
			// -------------------------------------------------

		public function convertTable($args) {
			$database = $args['database'];
			$table = $args['table'];
			$old_table = $table . 'Old';

			$signature_exists_args = [
				'database'=>$database,
				'table'=>$table,
				'column'=>'Signature',
			];

			if($this->columnExists($signature_exists_args)) {
				print('  ' . $table . ': already converted, left alone' . PHP_EOL);

				return FALSE;
			}

			if($this->tableExists(['database'=>$database, 'table'=>$old_table])) {
				print('  ' . $table . ': ' . $old_table . ' is already present, left alone' . PHP_EOL);

				return FALSE;
			}

			$rows_before = $this->rowCount(['table'=>$database . '.' . $table]);

			$this->renameToOld($args);
			$this->createTicketTable($args);
			$this->createInstanceTable($args);
			$this->importFromOld($args);

			$tickets = $this->rowCount(['table'=>$database . '.' . $table]);

			print('  ' . $table . ': ' . $rows_before . ' rows -> ' . $tickets . ' tickets' . PHP_EOL);
			print('    drop when satisfied:  DROP TABLE ' . $database . '.' . $old_table . ';' . PHP_EOL);

			return TRUE;
		}

		public function renameToOld($args) {
			$sql = 'RENAME TABLE ' . $args['database'] . '.' . $args['table'];
			$sql .= ' TO ' . $args['database'] . '.' . $args['table'] . 'Old;';

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

		public function createTicketTable($args) {
			$sql = 'CREATE TABLE ' . $args['database'] . '.' . $args['table'] . ' (';
			$sql .= ' id int NOT NULL AUTO_INCREMENT,';
			$sql .= " Signature char(64) NOT NULL DEFAULT '',";
			$sql .= " Script varchar(255) NOT NULL DEFAULT '',";
			$sql .= " IncidentCount int NOT NULL DEFAULT '1',";
			$sql .= " Resolved tinyint(1) NOT NULL DEFAULT '0',";
			$sql .= ' ' . implode(', ', $args['columns']) . ',';
			$sql .= " OriginalCreationDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql .= " LastModificationDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql .= ' PRIMARY KEY (id),';
			$sql .= ' UNIQUE KEY Signature (Signature),';
			$sql .= ' KEY Resolved (Resolved),';
			$sql .= ' KEY OriginalCreationDate (OriginalCreationDate),';
			$sql .= ' KEY LastModificationDate (LastModificationDate)';
			$sql .= ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;';

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

		public function createInstanceTable($args) {
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
				One row per signature, joined back to its own oldest row for the
				context.  Every selected column is either an aggregate or comes
				from that one joined row, so this does not depend on
				ONLY_FULL_GROUP_BY being off -- and it must not, because this
				runs through `mysql -e` and gets the server's sql_mode rather
				than the session mode DBAccess::DBStart() sets.
			*/

		public function importFromOld($args) {
			$database = $args['database'];
			$table = $args['table'];
			$old_table = $database . '.' . $table . 'Old';
			$context = $args['context'];

			$selected_context = [];

			foreach($context as $column) {
				$selected_context[] = 'old.' . $column;
			}

			$sql = 'INSERT INTO ' . $database . '.' . $table;
			$sql .= ' (Signature, Script, IncidentCount, Resolved, ' . implode(', ', $context) . ', OriginalCreationDate, LastModificationDate)';
			$sql .= " SELECT rollup.Signature, '', rollup.Incidents, 0, " . implode(', ', $selected_context) . ', rollup.FirstSeen, rollup.LastSeen';
			$sql .= ' FROM (';
			$sql .= ' SELECT ' . $args['signature'] . ' AS Signature,';
			$sql .= ' MIN(id) AS KeepId,';
			$sql .= ' COUNT(*) AS Incidents,';
			$sql .= ' MIN(OriginalCreationDate) AS FirstSeen,';
			$sql .= ' MAX(LastModificationDate) AS LastSeen';
			$sql .= ' FROM ' . $old_table;
			$sql .= ' GROUP BY 1';
			$sql .= ' ) AS rollup';
			$sql .= ' JOIN ' . $old_table . ' AS old ON old.id = rollup.KeepId;';

			$this->runSQL(['sql'=>$sql]);

			return TRUE;
		}

			// Database Access
			// -------------------------------------------------

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

		public function tableExists($args) {
			$sql = 'SELECT COUNT(*) FROM information_schema.TABLES';
			$sql .= ' WHERE TABLE_SCHEMA = ' . $this->quote(['value'=>$args['database']]);
			$sql .= ' AND TABLE_NAME = ' . $this->quote(['value'=>$args['table']]) . ';';

			return (int)$this->runSQL(['sql'=>$sql]) > 0;
		}

		public function columnExists($args) {
			$sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS';
			$sql .= ' WHERE TABLE_SCHEMA = ' . $this->quote(['value'=>$args['database']]);
			$sql .= ' AND TABLE_NAME = ' . $this->quote(['value'=>$args['table']]);
			$sql .= ' AND COLUMN_NAME = ' . $this->quote(['value'=>$args['column']]) . ';';

			return (int)$this->runSQL(['sql'=>$sql]) > 0;
		}

		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Rebuild the ISE and ISI tables as counted tickets.  The old tables are renamed, not dropped.',
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

<?php

	depreq('arr2textTable/arr2textTable.php');

	ggreq('traits/ReverseDNSNotation.php');

	clireq('traits/DBAccess.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');

	class SchemaChecker {
		use DBAccess;
		use CLIAccess;
		use GlobalsTrait;
		use ReverseDNSNotation;

			// Entry Point
			// -----------------------------------------------

		public function checkSchema() {
			$this->setGlobals();
			$this->setMySQLArgs();
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$databases = $this->getDatabases();

			if(!$databases) {
				print('No databases matched.  Try --database=NAME, or omit it for all.' . "\n");

				return FALSE;
			}

			$findings = [];

			foreach($databases as $database) {
				if($this->wantsCheck(['check'=>'tables'])) {
					$findings = array_merge($findings, $this->checkTables(['database'=>$database]));
				}

				if($this->wantsCheck(['check'=>'spine'])) {
					$findings = array_merge($findings, $this->checkSpine(['database'=>$database]));
				}

				if($this->wantsCheck(['check'=>'enabled'])) {
					$findings = array_merge($findings, $this->checkEnabled(['database'=>$database]));
				}
			}

			return $this->report(['findings'=>$findings, 'databases'=>$databases]);
		}

			// Arguments
			// -----------------------------------------------

			/*
				Narrow by default.  A full sweep of seventeen databases against
				thirty-two tables is a thousand rows nobody reads, so nothing is
				printed unless it is wrong, and --all is the way to ask for the
				rest.  See the note on filtering in Docs/CommandLineTools.md.
			*/

		public function setArguments() {
			$arguments = [
				'database'=>'',
				'check'=>'all',
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--all') {
					$arguments['all'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 11) === '--database=') {
					$arguments['database'] = substr($argument, 11);
				} else if(substr($argument, 0, 8) === '--check=') {
					$arguments['check'] = substr($argument, 8);
				}
			}

			return $this->arguments = $arguments;
		}

		public function wantsCheck($args) {
			$check = $args['check'];

			if($this->arguments['check'] === 'all') {
				return TRUE;
			}

			return $this->arguments['check'] === $check;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Schema Checker' . "\n\n");
			print('  check_schema.php [--database=NAME] [--check=tables|spine|enabled] [--all]' . "\n\n");
			print('  tables   every table in the reference database exists here, and no others' . "\n");
			print('  spine    id primary key, and the two date columns last, per Docs/Database.md' . "\n");
			print('  enabled  child tables holding rows that child_types/enabled.php switches off' . "\n\n");
			print('  Prints only problems.  --all prints every table it looked at.' . "\n\n");

			return TRUE;
		}

			// Databases
			// -----------------------------------------------

		public function getDatabases() {
			if($this->arguments['database']) {
				return [$this->arguments['database']];
			}

			$rows = $this->runQuery(['query'=>'SHOW DATABASES']);

			$skip = $this->skipDatabases();
			$databases = [];

			foreach($rows as $row) {
				$database = array_values($row)[0];

				if(!$skip[$database]) {
					$databases[] = $database;
				}
			}

			return $databases;
		}

		public function skipDatabases() {
			return [
				'information_schema'=>TRUE,
				'performance_schema'=>TRUE,
				'mysql'=>TRUE,
				'sys'=>TRUE,
				'defaultdb'=>TRUE,
			];
		}

		public function referenceDatabase() {
			return 'clonefrom';
		}

			// Tables
			// -----------------------------------------------

			/*
				clonefrom is the reference rather than a list held in this file,
				for the same reason DomainChecker compares against it: a list in
				code is a second source of truth that goes stale the first time
				a table is added and nobody remembers this tool exists.
			*/

		public function checkTables($args) {
			$database = $args['database'];

			if($database === $this->referenceDatabase()) {
				return [];
			}

			$reference = $this->getTableNames(['database'=>$this->referenceDatabase()]);
			$present = $this->getTableNames(['database'=>$database]);

			$findings = [];

			foreach($reference as $table) {
				if(!$present[$table]) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'tables',
						'Subject'=>$table,
						'Finding'=>'missing -- present in ' . $this->referenceDatabase(),
					];
				}
			}

			foreach($present as $table => $ignored) {
				if(!$reference[$table]) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'tables',
						'Subject'=>$table,
						'Finding'=>'unknown -- absent from ' . $this->referenceDatabase(),
					];
				}
			}

			if(!$findings && $this->arguments['all']) {
				$findings[] = [
					'Database'=>$database,
					'Check'=>'tables',
					'Subject'=>count($present) . ' tables',
					'Finding'=>'match',
				];
			}

			return $findings;
		}

		public function getTableNames($args) {
			$database = $args['database'];

			$rows = $this->runQuery([
				'query'=>'SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$database]) . '" AND TABLE_TYPE = "BASE TABLE"',
			]);

			$tables = [];

			foreach($rows as $row) {
				$tables[$row['TABLE_NAME']] = TRUE;
			}

			return $tables;
		}

			// Spine
			// -----------------------------------------------

			/*
				Docs/Database.md, "Every table has the same spine": an
				auto-incrementing id as the primary key, and OriginalCreationDate
				and LastModificationDate as the last two columns, both datetime.
				A table that breaks either one breaks an ORM assumption rather
				than merely looking untidy.
			*/

		public function checkSpine($args) {
			$database = $args['database'];

			$rows = $this->runQuery([
				'query'=>'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_KEY, EXTRA, ORDINAL_POSITION FROM information_schema.columns WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$database]) . '" ORDER BY TABLE_NAME, ORDINAL_POSITION',
			]);

			$tables = [];

			foreach($rows as $row) {
				$tables[$row['TABLE_NAME']][] = $row;
			}

			$findings = [];

			foreach($tables as $table => $columns) {
				$problems = $this->spineProblems(['columns'=>$columns]);

				foreach($problems as $problem) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'spine',
						'Subject'=>$table,
						'Finding'=>$problem,
					];
				}

				if(!$problems && $this->arguments['all']) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'spine',
						'Subject'=>$table,
						'Finding'=>'ok',
					];
				}
			}

			return $findings;
		}

		public function spineProblems($args) {
			$columns = $args['columns'];

			$problems = [];

			$first = $columns[0];

			if($first['COLUMN_NAME'] !== 'id') {
				$problems[] = 'first column is ' . $first['COLUMN_NAME'] . ', not id';
			} else {
				if($first['COLUMN_KEY'] !== 'PRI') {
					$problems[] = 'id is not the primary key';
				}

				if(strpos($first['EXTRA'], 'auto_increment') === FALSE) {
					$problems[] = 'id is not auto_increment';
				}
			}

			$column_count = count($columns);

			$last = $columns[$column_count - 1];
			$second_last = $columns[$column_count - 2];

			if($second_last['COLUMN_NAME'] !== 'OriginalCreationDate') {
				$problems[] = 'second-to-last column is ' . $second_last['COLUMN_NAME'] . ', not OriginalCreationDate';
			}

			if($last['COLUMN_NAME'] !== 'LastModificationDate') {
				$problems[] = 'last column is ' . $last['COLUMN_NAME'] . ', not LastModificationDate';
			}

			foreach([$second_last, $last] as $date_column) {
				if($date_column['DATA_TYPE'] !== 'datetime' && ($date_column['COLUMN_NAME'] === 'OriginalCreationDate' || $date_column['COLUMN_NAME'] === 'LastModificationDate')) {
					$problems[] = $date_column['COLUMN_NAME'] . ' is ' . $date_column['DATA_TYPE'] . ', not datetime';
				}
			}

			return $problems;
		}

			// Enabled
			// -----------------------------------------------

			/*
				The check this tool was written for.  A child table can hold
				thousands of rows the ORM is never asked to fetch, because
				child_types/enabled.php says that type is off -- and nothing
				anywhere reports the disagreement.  On 31 August 2026 that was
				true of revoltlib's 2,496 images, and of twelve other sites that
				had no override file at all and so ran on clonefrom's defaults.
			*/

		public function checkEnabled($args) {
			$database = $args['database'];

			$child_types = $this->childRecordTypes();
			$enabled = $this->getEnabledFlags(['database'=>$database]);

			if($enabled === FALSE) {
				return [[
					'Database'=>$database,
					'Check'=>'enabled',
					'Subject'=>'child_types/enabled.php',
					'Finding'=>'no config found -- cannot compare',
				]];
			}

			$present = $this->getTableNames(['database'=>$database]);

			$findings = [];

			foreach($child_types as $table) {
				if(!$present[$table]) {
					continue;
				}

				$count = $this->countRows(['database'=>$database, 'table'=>$table]);
				$is_enabled = $enabled[$table];

				if($count && !$is_enabled) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'enabled',
						'Subject'=>$table,
						'Finding'=>$count . ' rows, never fetched -- ' . $table . '_enabled() is FALSE',
					];
				} else if(!$count && $is_enabled) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'enabled',
						'Subject'=>$table,
						'Finding'=>'0 rows, queried on every fetch -- ' . $table . '_enabled() is TRUE',
					];
				} else if($this->arguments['all']) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'enabled',
						'Subject'=>$table,
						'Finding'=>$count . ' rows, enabled ' . ($is_enabled ? 'TRUE' : 'FALSE') . ' -- agrees',
					];
				}
			}

			return $findings;
		}

			/*
				The same two-step AbstractGlobals performs: the domain's own
				override when it has one, clonefrom's defaults when it does not.
				The domain is assumed to be <database>.com, which is true of
				every site on this host and is stated here rather than hidden,
				because the day it stops being true this check will quietly
				compare a site against the wrong file.
			*/

		public function getEnabledFlags($args) {
			$database = $args['database'];

			$domain_location = $this->ReverseDomainName(['domain'=>$database . '.com']) . '/child_types/enabled.php';

			$classname = 'AbstractGlobals_ChildTypes_enabled';

			if(conf_isfile($domain_location)) {
				$classname .= '_override';
				confreq($domain_location);
			} else {
				confreq('clonefrom/child_types/enabled.php');
			}

			if(!class_exists($classname)) {
				return FALSE;
			}

			$globals = new $classname;

			$flags = [];

			foreach($this->childRecordTypes() as $table) {
				$method = $table . '_enabled';

				if(method_exists($globals, $method)) {
					$flags[$table] = $globals->$method();
				} else {
					$flags[$table] = FALSE;
				}
			}

			return $flags;
		}

		public function childRecordTypes() {
			return [
				'EntryTranslation',
				'Description',
				'Quote',
				'TextBody',
				'Image',
				'ImageTranslation',
				'Tag',
				'Link',
				'EventDate',
				'AvailabilityDateRange',
				'Association',
				'Definition',
				'EntryPermission',
			];
		}

		public function countRows($args) {
			$database = $args['database'];
			$table = $args['table'];

			$rows = $this->runQuery([
				'query'=>'SELECT COUNT(*) AS RowCountTotal FROM `' . $this->escape(['value'=>$database]) . '`.`' . $this->escape(['value'=>$table]) . '`',
			]);

			return (int) $rows[0]['RowCountTotal'];
		}

			// Output
			// -----------------------------------------------

		public function report($args) {
			$findings = $args['findings'];
			$databases = $args['databases'];

			if(!$findings) {
				print(count($databases) . ' databases checked, nothing to report.' . "\n");

				return TRUE;
			}

			print(arr2textTable($findings));
			print("\n" . count($findings) . ' findings across ' . count($databases) . ' databases.' . "\n");

			return TRUE;
		}

			/*
				Identifiers cannot be bound as parameters, so they are escaped
				and wrapped rather than prepared.  Everything reaching here is a
				table or database name this tool read out of INFORMATION_SCHEMA
				or took from an argument, but the argument is the reason this
				exists.
			*/

		public function escape($args) {
			$value = $args['value'];

			return preg_replace('/[^A-Za-z0-9_]/', '', $value);
		}
	}

?>

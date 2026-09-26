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

				if($this->wantsCheck(['check'=>'columns'])) {
					$findings = array_merge($findings, $this->checkColumns(['database'=>$database]));
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
			print('  check_schema.php [--database=NAME] [--check=tables|columns|spine|enabled] [--all]' . "\n\n");
			print('  tables   every table in the reference database exists here, and no others' . "\n");
			print('  columns  every column the reference has, in every table both share' . "
");
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

				if(!isset($skip[$database])) {
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

			foreach($reference as $table => $ignored_reference) {
				if(!isset($present[$table])) {
					$findings[] = [
						'Database'=>$database,
						'Check'=>'tables',
						'Subject'=>$table,
						'Finding'=>'missing -- present in ' . $this->referenceDatabase(),
					];
				}
			}

			foreach($present as $table => $ignored_present) {
				if(!isset($reference[$table])) {
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

			// Columns
			// -----------------------------------------------

			/*
				tables says whether a table exists and spine checks its first
				and last columns, and nothing checked the ones between.  On
				26 September 2026 masereelgroup and revoltsource had gone years
				without EventDate.Approximate, and every page that fetched an
				event date was a 500 -- with this tool reporting 248 findings,
				none of them that one.  A column the reference has and a site
				lacks is what breaks a page, so it is reported first; one the
				site has and the reference lacks is reported so it can be
				explained.
			*/

		public function checkColumns($args) {
			$database = $args['database'];

			if($database === $this->referenceDatabase()) {
				return [];
			}

			$reference = $this->getColumns(['database'=>$this->referenceDatabase()]);
			$present = $this->getColumns(['database'=>$database]);

			$findings = [];

			foreach($reference as $table => $reference_columns) {
				if(!isset($present[$table])) {
					continue;
				}

				foreach($reference_columns as $column => $column_type) {
					if(!isset($present[$table][$column])) {
						$findings[] = [
							'Database'=>$database,
							'Check'=>'columns',
							'Subject'=>$table . '.' . $column,
							'Finding'=>'missing -- ' . $column_type . ' in ' . $this->referenceDatabase(),
						];
					}
				}

				foreach($present[$table] as $column => $column_type) {
					if(!isset($reference_columns[$column])) {
						$findings[] = [
							'Database'=>$database,
							'Check'=>'columns',
							'Subject'=>$table . '.' . $column,
							'Finding'=>'unknown -- absent from ' . $this->referenceDatabase(),
						];
					}
				}
			}

			if(!$findings && $this->arguments['all']) {
				$findings[] = [
					'Database'=>$database,
					'Check'=>'columns',
					'Subject'=>count($present) . ' tables',
					'Finding'=>'match',
				];
			}

			return $findings;
		}

		public function getColumns($args) {
			$database = $args['database'];

			$rows = $this->runQuery([
				'query'=>'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM information_schema.columns WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$database]) . '" ORDER BY TABLE_NAME, ORDINAL_POSITION',
			]);

			$columns = [];

			foreach($rows as $row) {
				$columns[$row['TABLE_NAME']][$row['COLUMN_NAME']] = $row['COLUMN_TYPE'];
			}

			return $columns;
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

				/*
					Worth its own line even though it is not itself a fault.  A
					site with no override runs on defaults that switch almost
					every child type off, so this one fact explains every
					disagreement printed below it.
				*/

			if(!$this->hasOverride(['database'=>$database])) {
				$findings[] = [
					'Database'=>$database,
					'Check'=>'enabled',
					'Subject'=>'child_types',
					'Finding'=>'no override -- running on clonefrom defaults',
				];
			}

			foreach($child_types as $table) {
				if(!isset($present[$table])) {
					continue;
				}

				$count = $this->countRows(['database'=>$database, 'table'=>$table]);
				$is_enabled = isset($enabled[$table]) ? $enabled[$table] : FALSE;

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

				The file is read rather than required.  Every domain override
				declares the same class name, which is correct for the engine --
				one request serves one domain -- and fatal here, where one
				process walks seventeen of them and the second require dies on
				"cannot declare class, the name is already in use".  A checker
				that has to load a site to inspect it can only inspect one site.
			*/

		public function getEnabledFlags($args) {
			$database = $args['database'];

			$domain_location = GGCMS_CONFIG_DIR . $this->ReverseDomainName(['domain'=>$database . '.com']) . '/child_types/enabled.php';
			$default_location = GGCMS_CONFIG_DIR . 'clonefrom/child_types/enabled.php';

			if(!is_file($default_location)) {
				return FALSE;
			}

			$default_configuration = file_get_contents($default_location);

			if($default_configuration === FALSE) {
				return FALSE;
			}

				/*
					Both files, in the order the engine reads them.  This used
					to take the override when one existed and the defaults
					otherwise, which is not what an override is: the class in a
					domain file extends the clonefrom class and redeclares only
					what it changes.  Reading it alone reported every flag it
					did not mention as off.

					revoltlib was the worked example -- reported as never
					fetching TextBody, Tag, Association or Description, while
					its pages were visibly full of all four.  A check that
					cries wolf is worse than no check, and this is the one the
					documentation says is worth scheduling.
				*/

			$domain_configuration = '';

			if(is_file($domain_location)) {
				$domain_configuration = file_get_contents($domain_location);

				if($domain_configuration === FALSE) {
					$domain_configuration = '';
				}
			}

			$flags = [];

			foreach($this->childRecordTypes() as $table) {
				$flag = NULL;

				if(strlen($domain_configuration)) {
					$flag = $this->readFlag([
						'configuration'=>$domain_configuration,
						'table'=>$table,
					]);
				}

				if($flag === NULL) {
					$flag = $this->readFlag([
						'configuration'=>$default_configuration,
						'table'=>$table,
					]);
				}

				$flags[$table] = ($flag === NULL) ? FALSE : $flag;
			}

			return $flags;
		}

		public function hasOverride($args) {
			$database = $args['database'];

			return is_file(GGCMS_CONFIG_DIR . $this->ReverseDomainName(['domain'=>$database . '.com']) . '/child_types/enabled.php');
		}

		public function readFlag($args) {
			$configuration = $args['configuration'];
			$table = $args['table'];

			$pattern = '/function\s+' . preg_quote($table, '/') . '_enabled\s*\(\s*\)\s*\{\s*return\s+(TRUE|FALSE|true|false)\s*;/';

			$matches = [];

				/*
					NULL, not FALSE.  A domain override declares only the flags
					it changes and inherits the rest, so "this file does not
					mention the table" and "this file switches the table off"
					are different answers, and the caller has to be able to tell
					them apart to layer the two files correctly.
				*/

			if(!preg_match($pattern, $configuration, $matches)) {
				return NULL;
			}

			return strtoupper($matches[1]) === 'TRUE';
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

<?php

	if(!function_exists('arr2textTable')) {
		depreq('arr2textTable/arr2textTable.php');		# plain require, so once
	}

	if(!trait_exists('CLIAccess')) {
		clireq('traits/CLIAccess.php');
	}

	/*
		Pushes clonefrom's tables out to every site that lacks them.

		clonefrom is what every new site is cloned from, so a new table is
		made there first, once, and the sites follow.  That was always the
		pipeline; it was done by hand, one CREATE TABLE ... LIKE per site:

			CREATE TABLE earthfluent.EntryCodeReservation LIKE clonefrom.EntryCodeReservation;

		LIKE copies the column definitions and every index and creates the
		table empty, so a site cannot end up with the table typed out
		slightly differently from the reference.  See Docs/Database.md.

		Only missing tables, and only ever created.  A table a site already
		has is never altered or dropped, whatever it holds: a column that
		disagrees with clonefrom is check_schema.php's to report and a
		person's to decide, and a table clonefrom lacks may be a site's own.

		Nothing is written without --apply.  Run bare, it says what it would
		do; that is the same list check_schema.php --check=tables reports as
		"missing -- present in clonefrom".

		A new column goes the same way, one named column at a time:

			ALTER TABLE clonefrom.TextBody ADD COLUMN SpellCheckImmune tinyint(1) NOT NULL DEFAULT '0' AFTER CharacterCount;
			clonefrom_push.php --column=TextBody.SpellCheckImmune --apply

		The definition is read from clonefrom's information_schema rather than
		typed again, and the column goes after the one it follows there.  Only
		added, to a site that has the table and lacks the column; never every
		missing column at once, because check_schema.php's drift is a
		person's to read before anything acts on it.
	*/

	class ClonefromPush {
		use CLIAccess;

		public $arguments;
		public $db_link;
		public $passed_over = [];

			// Entry Point
			// -----------------------------------------------

		public function push() {
			$this->db_link = ggcms_mysqli();
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			if($this->arguments['column']) {
				return $this->pushColumn();
			}

			$reference = $this->getTableNames(['database'=>$this->referenceDatabase()]);

			if(!$reference) {
				print('No ' . $this->referenceDatabase() . ' database here, or it holds no tables.  Nothing to push from.' . "\n");

				return FALSE;
			}

			if($this->arguments['table'] && !isset($reference[$this->arguments['table']])) {
				print($this->arguments['table'] . ' is not a table in ' . $this->referenceDatabase() . '.  Make it there first.' . "\n");

				return FALSE;
			}

			$databases = $this->getSiteDatabases();

			if(!$databases) {
				print('No site databases matched' . ($this->passed_over ? ' (passed over: ' . implode(', ', $this->passed_over) . ')' : '') . '.  Try --database=NAME, or omit it for every site.' . "\n");

				return FALSE;
			}

			$rows = [];

			foreach($databases as $database) {
				foreach($this->missingTables(['database'=>$database, 'reference'=>$reference]) as $table) {
					$rows[] = [
						'Database'=>$database,
						'Table'=>$table,
						'Action'=>$this->arguments['apply'] ? $this->createTable(['database'=>$database, 'table'=>$table]) : 'would create',
					];
				}
			}

			return $this->report(['rows'=>$rows, 'databases'=>$databases]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'database'=>'',
				'table'=>'',
				'column'=>'',
				'apply'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 11) === '--database=') {
					$arguments['database'] = $this->escape(['value'=>substr($argument, 11)]);
				} else if(substr($argument, 0, 8) === '--table=') {
					$arguments['table'] = $this->escape(['value'=>substr($argument, 8)]);
				} else if(substr($argument, 0, 9) === '--column=') {
						// Table.Column: the table is --table, so one escape each
					$pieces = explode('.', substr($argument, 9), 2);

					if(count($pieces) === 2) {
						$arguments['table'] = $this->escape(['value'=>$pieces[0]]);
						$arguments['column'] = $this->escape(['value'=>$pieces[1]]);
					}
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Clonefrom Push' . "\n\n");
			print('  clonefrom_push.php [--table=NAME] [--database=NAME] [--apply]' . "\n");
			print('  clonefrom_push.php --column=TABLE.COLUMN [--database=NAME] [--apply]' . "\n\n");
			print('  Creates, in each site database, every table ' . $this->referenceDatabase() . ' has and the site lacks,' . "\n");
			print('  with CREATE TABLE site.Table LIKE ' . $this->referenceDatabase() . '.Table: same columns, same indexes, empty.' . "\n");
			print('  Never alters or drops a table a site already has.' . "\n\n");
			print('  With --column, adds that one column of ' . $this->referenceDatabase() . '\'s, as defined there, to each site' . "\n");
			print('  whose table lacks it, after the column it follows in ' . $this->referenceDatabase() . '.' . "\n\n");
			print('  --column    only add this column' . "\n");
			print('  --table     only this table' . "\n");
			print('  --database  only this site' . "\n");
			print('  --apply     do it; without this, only say what would be done' . "\n\n");

			return TRUE;
		}

			// Databases
			// -----------------------------------------------

		public function referenceDatabase() {
			return 'clonefrom';
		}

			/*
				A site database is one holding an Entry table whose site has a
				configuration file.  The Entry table keeps the shared
				dictionaries database and MySQL's own out of reach of a tool
				that creates tables; the configuration file keeps out the
				copies kept beside a site -- revoltlib_live, revoltlib_jan2022,
				masereelgroup_old on Fumiko -- which hold an Entry table too
				and are archives, not sites.

				The domain is assumed to be <database>.com, as SchemaChecker
				assumes and as is true of every site on this host.  Databases
				passed over are named in the report, so one that should have
				been included is seen rather than silently missed.
			*/

		public function getSiteDatabases() {
			$rows = $this->runQuery([
				'query'=>'SELECT TABLE_SCHEMA FROM information_schema.tables WHERE TABLE_NAME = "Entry" AND TABLE_TYPE = "BASE TABLE" ORDER BY TABLE_SCHEMA',
			]);

			$databases = [];
			$this->passed_over = [];

			foreach($rows as $row) {
				$database = $row['TABLE_SCHEMA'];

				if($database === $this->referenceDatabase()) {
					continue;
				}

				if($this->arguments['database'] && $database !== $this->arguments['database']) {
					continue;
				}

				if(!$this->isSiteDatabase(['database'=>$database])) {
					$this->passed_over[] = $database;
					continue;
				}

				$databases[] = $database;
			}

			return $databases;
		}

			// isSiteDatabase()
			// Tests: ClonefromPushTest::testIsSiteDatabase()
			// Test file: tests/cli/classes/Database/ClonefromPushTest.php
		public function isSiteDatabase($args) {
			$database = $this->escape(['value'=>$args['database']]);

			return $database !== '' && is_file(GGCMS_CONFIG_DIR . 'com.' . $database . '.php');
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

			// Tables
			// -----------------------------------------------

			// missingTables()
			// Tests: ClonefromPushTest::testMissingTables()
			// Test file: tests/cli/classes/Database/ClonefromPushTest.php
		public function missingTables($args) {
			$database = $args['database'];
			$reference = $args['reference'];

			$present = $this->getTableNames(['database'=>$database]);

			$missing = [];

			foreach($reference as $table => $ignored_reference) {
				if(isset($present[$table])) {
					continue;
				}

				if($this->arguments['table'] && $table !== $this->arguments['table']) {
					continue;
				}

				$missing[] = $table;
			}

			sort($missing);

			return $missing;
		}

			/*
				Not runQuery(): it prepares and fetches a result set, and a
				CREATE has none to fetch.  What it answers is printed as the
				row's Action, so a failure on one site is read beside the
				successes on the others rather than ending the run.
			*/

		public function createTable($args) {
			$database = $this->escape(['value'=>$args['database']]);
			$table = $this->escape(['value'=>$args['table']]);

			$query = 'CREATE TABLE `' . $database . '`.`' . $table . '` LIKE `' . $this->referenceDatabase() . '`.`' . $table . '`';

			try {
				if($this->db_link->query($query) === TRUE) {
					return 'created';
				}

				return 'failed -- ' . $this->db_link->error;
			} catch (Throwable $exception) {
				return 'failed -- ' . $exception->getMessage();
			}
		}

			// Columns
			// -----------------------------------------------

		public function pushColumn() {
			$table = $this->arguments['table'];
			$column = $this->arguments['column'];

			$reference = $this->getColumns(['database'=>$this->referenceDatabase(), 'table'=>$table]);

			if(!isset($reference[$column])) {
				print($table . '.' . $column . ' is not a column in ' . $this->referenceDatabase() . '.  Make it there first.' . "\n");

				return FALSE;
			}

			$definition = $this->columnDefinition(['column'=>$reference[$column]]);

			if($definition === NULL) {
				print($table . '.' . $column . ' is generated or auto-incrementing in ' . $this->referenceDatabase() . ', which an ADD COLUMN cannot reproduce.  Add it by hand.' . "\n");

				return FALSE;
			}

			$databases = $this->getSiteDatabases();

			if(!$databases) {
				print('No site databases matched' . ($this->passed_over ? ' (passed over: ' . implode(', ', $this->passed_over) . ')' : '') . '.  Try --database=NAME, or omit it for every site.' . "\n");

				return FALSE;
			}

			$rows = [];

			foreach($databases as $database) {
				$present = $this->getColumns(['database'=>$database, 'table'=>$table]);

				if(!$present) {
					$rows[] = ['Database'=>$database, 'Column'=>$table . '.' . $column, 'Action'=>'no ' . $table . ' table -- push the table first'];
					continue;
				}

				if(isset($present[$column])) {
					continue;
				}

				$after = $this->columnPosition(['reference'=>$reference, 'present'=>$present, 'column'=>$column]);

				$rows[] = [
					'Database'=>$database,
					'Column'=>$table . '.' . $column,
					'Action'=>$this->arguments['apply'] ? $this->addColumn(['database'=>$database, 'definition'=>$definition, 'after'=>$after]) : 'would add ' . ($after ?: 'at the end'),
				];
			}

			return $this->report(['rows'=>$rows, 'databases'=>$databases, 'noun'=>'column', 'done'=>'added', 'thing'=>$table . '.' . $column]);
		}

			// In ORDINAL_POSITION order, keyed by name, so a column's
			// neighbour in clonefrom can be found.

		public function getColumns($args) {
			$rows = $this->runQuery([
				'query'=>'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$args['database']]) . '" AND TABLE_NAME = "' . $this->escape(['value'=>$args['table']]) . '" ORDER BY ORDINAL_POSITION',
			]);

			$columns = [];

			foreach($rows as $row) {
				$columns[$row['COLUMN_NAME']] = $row;
			}

			return $columns;
		}

			/*
				One information_schema.COLUMNS row as the ADD COLUMN that would
				recreate it.  NULL for what an ADD COLUMN cannot honestly copy:
				an auto_increment column, or a generated one.  A default MySQL
				itself computes (CURRENT_TIMESTAMP) is written bare; any other
				is quoted, as SHOW CREATE TABLE writes it.
			*/

			// columnDefinition()
			// Tests: ClonefromPushTest::testColumnDefinition()
			// Test file: tests/cli/classes/Database/ClonefromPushTest.php
		public function columnDefinition($args) {
			$column = $args['column'];
			$extra = strtolower((string)$column['EXTRA']);

			if(strpos($extra, 'auto_increment') !== FALSE || (strpos($extra, 'generated') !== FALSE && strpos($extra, 'default_generated') === FALSE)) {
				return NULL;
			}

			$definition = '`' . $this->escape(['value'=>$column['COLUMN_NAME']]) . '` ' . $column['COLUMN_TYPE'];

			if($column['CHARACTER_SET_NAME']) {
				$definition .= ' CHARACTER SET ' . $this->escape(['value'=>$column['CHARACTER_SET_NAME']]) . ' COLLATE ' . $this->escape(['value'=>$column['COLLATION_NAME']]);
			}

			$definition .= $column['IS_NULLABLE'] === 'NO' ? ' NOT NULL' : ' NULL';

			if($column['COLUMN_DEFAULT'] !== NULL) {
				if(strpos($extra, 'default_generated') !== FALSE) {
					$definition .= ' DEFAULT ' . $column['COLUMN_DEFAULT'];
				} else {
					$definition .= ' DEFAULT \'' . str_replace(['\\', '\''], ['\\\\', '\\\''], $column['COLUMN_DEFAULT']) . '\'';
				}
			}

			return $definition;
		}

			/*
				After the column it follows in clonefrom, when the site has
				that one too; FIRST when it leads; otherwise at the end, which
				check_schema.php will then report as out of order rather than
				the push guessing.
			*/

			// columnPosition()
			// Tests: ClonefromPushTest::testColumnPosition()
			// Test file: tests/cli/classes/Database/ClonefromPushTest.php
		public function columnPosition($args) {
			$names = array_keys($args['reference']);
			$index = array_search($args['column'], $names, TRUE);

			if($index === 0) {
				return 'FIRST';
			}

			$previous = $names[$index - 1];

			if(isset($args['present'][$previous])) {
				return 'AFTER `' . $this->escape(['value'=>$previous]) . '`';
			}

			return '';
		}

		public function addColumn($args) {
			$database = $this->escape(['value'=>$args['database']]);
			$table = $this->arguments['table'];

			$query = 'ALTER TABLE `' . $database . '`.`' . $table . '` ADD COLUMN ' . $args['definition'] . ($args['after'] ? ' ' . $args['after'] : '');

			try {
				if($this->db_link->query($query) === TRUE) {
					return 'added';
				}

				return 'failed -- ' . $this->db_link->error;
			} catch (Throwable $exception) {
				return 'failed -- ' . $exception->getMessage();
			}
		}

			/*
				Its own two lines rather than the DBAccess trait the other
				database tools use.  That trait shares its name with the
				engine's DBAccess class, so nothing using it can be loaded
				beside the engine -- which is how the tests load everything.
				Every query here is a read of information_schema with escaped
				identifiers, so nothing is lost by not preparing it.
			*/

		public function runQuery($args) {
			$result = $this->db_link->query($args['query']);

			return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
		}

			// Output
			// -----------------------------------------------

		public function report($args) {
			$rows = $args['rows'];
			$databases = $args['databases'];
			$noun = $args['noun'] ?? 'table';
			$done = $args['done'] ?? 'created';
			$thing = $args['thing'] ?? ($this->arguments['table'] ?: 'every ' . $this->referenceDatabase() . ' table');

			if($this->passed_over) {
				print('Passed over, holding Entry but with no site configuration: ' . implode(', ', $this->passed_over) . "\n\n");
			}

			if(!$rows) {
				print(count($databases) . ' site databases checked; every one already has ' . $thing . '.' . "\n");

				return TRUE;
			}

			print(arr2textTable($rows));

			if($this->arguments['apply']) {
				$failed = count(array_filter($rows, function($row) use ($done) { return $row['Action'] !== $done; }));

				print("\n" . $this->counted(['count'=>count($rows) - $failed, 'noun'=>$noun]) . ' ' . $done . ' across ' . $this->counted(['count'=>count($databases), 'noun'=>'site database']) . ($failed ? ', ' . $failed . ' failed' : '') . '.' . "\n");

				return $failed === 0;
			}

			print("\n" . $this->counted(['count'=>count($rows), 'noun'=>$noun]) . ' missing across ' . $this->counted(['count'=>count($databases), 'noun'=>'site database']) . '.  Run again with --apply to ' . ($noun === 'table' ? 'create' : 'add') . ' them.' . "\n");

			return TRUE;
		}

		public function counted($args) {
			return $args['count'] . ' ' . $args['noun'] . ($args['count'] === 1 ? '' : 's');
		}

			/*
				Identifiers cannot be bound as parameters, so they are escaped
				and wrapped rather than prepared, as in SchemaChecker.
			*/

		public function escape($args) {
			$value = $args['value'];

			return preg_replace('/[^A-Za-z0-9_]/', '', (string)$value);
		}
	}

?>

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
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Clonefrom Push' . "\n\n");
			print('  clonefrom_push.php [--table=NAME] [--database=NAME] [--apply]' . "\n\n");
			print('  Creates, in each site database, every table ' . $this->referenceDatabase() . ' has and the site lacks,' . "\n");
			print('  with CREATE TABLE site.Table LIKE ' . $this->referenceDatabase() . '.Table: same columns, same indexes, empty.' . "\n");
			print('  Never alters or drops a table a site already has.' . "\n\n");
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

			if($this->passed_over) {
				print('Passed over, holding Entry but with no site configuration: ' . implode(', ', $this->passed_over) . "\n\n");
			}

			if(!$rows) {
				print(count($databases) . ' site databases checked; every one already has ' . ($this->arguments['table'] ?: 'every ' . $this->referenceDatabase() . ' table') . '.' . "\n");

				return TRUE;
			}

			print(arr2textTable($rows));

			if($this->arguments['apply']) {
				$failed = count(array_filter($rows, function($row) { return $row['Action'] !== 'created'; }));

				print("\n" . $this->counted(['count'=>count($rows) - $failed, 'noun'=>'table']) . ' created across ' . $this->counted(['count'=>count($databases), 'noun'=>'site database']) . ($failed ? ', ' . $failed . ' failed' : '') . '.' . "\n");

				return $failed === 0;
			}

			print("\n" . $this->counted(['count'=>count($rows), 'noun'=>'table']) . ' missing across ' . $this->counted(['count'=>count($databases), 'noun'=>'site database']) . '.  Run again with --apply to create them.' . "\n");

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

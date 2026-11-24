<?php

	// Standalone fixture: run explicitly against a disposable local MySQL instance.
	if(PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-fixture'
		|| !in_array(ini_get('mysqli.default_host'), ['', 'localhost', '127.0.0.1', '::1'], TRUE)) {
		fwrite(STDERR, "Usage: php orphan_entry_checker.php --local-fixture\n");
		exit(1);
	}

	require(__DIR__ . '/../../../usr/lib/ggcms/cli/system/RepoDirectories.php');
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');
	clireq('classes/Entries/OrphanEntryChecker.php');

	class FixtureOrphanEntryChecker extends OrphanEntryChecker {
		public function setMySQLArgs() {
			return $this->db_link;
		}
	}

	$database = 'fixtureorphan' . bin2hex(random_bytes(6));
	$db = ggcms_mysqli();
	$db->query('CREATE DATABASE ' . $database);
	try {
		$db->select_db($database);
		$db->query('CREATE TABLE `Entry` (Id INT PRIMARY KEY, Title VARCHAR(60), Code VARCHAR(60))');
		$db->query('CREATE TABLE `Assignment` (Childid INT, Parentid INT)');
		$db->query('CREATE TABLE `Tag` (Entryid INT)');
		$db->query('CREATE TABLE `TextBody` (Entryid INT)');
		$db->query("INSERT INTO `Entry` VALUES (1, 'Root', 'root'), (2, 'Attached', 'attached'),
			(3, 'No assignment', 'loose'), (4, 'Parent missing', 'broken')");
		$db->query('INSERT INTO `Assignment` VALUES (1, 0), (2, 1), (4, 90), (4, 91)');
		$db->query('INSERT INTO `Tag` VALUES (1), (90), (91)');
		$db->query('INSERT INTO `TextBody` VALUES (2), (93)');

		foreach([[], ['--summary']] as $flags) {
			$checker = new FixtureOrphanEntryChecker(['argv'=>['fixture', $database . '.com', ...$flags]]);
			$checker->db_link = $db;
			ob_start();
			try {
				$checker->checkOrphanEntries();
			} finally {
				$output = ob_get_clean();
			}
			foreach(['Entries without an assignment: 1', 'Entries with a missing parent: 1',
				'Tag: 2', 'TextBody: 1', 'Total child rows: 3'] as $expected) {
				if(!str_contains($output, $expected . "\n")) {
					throw new RuntimeException('Expected ' . $expected . "\n" . $output);
				}
			}
			if(str_contains($output, "3\tNo assignment\tloose") !== !$flags
				|| str_contains($output, "4\tParent missing\tbroken") !== !$flags
				|| str_contains($output, "1\tRoot\troot")) {
				throw new RuntimeException('Unexpected entry detail output' . "\n" . $output);
			}
		}
		print("Orphan checker MySQL fixture passed in full and summary mode.\n");
	} finally {
		$db->query('DROP DATABASE ' . $database);
	}

#!/usr/bin/php
<?php

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Entries/OrphanEntryChecker.php');

	$orphan_entry_checker = new OrphanEntryChecker([
		'argv'=>$argv,
	]);

	exit($orphan_entry_checker->checkOrphanEntries() ? 0 : 1);

?>

#!/usr/bin/php
<?php

	/*
		importer.php from a shell: a file of entries, previewed or imported,
		with a report of what every row did.  See Docs/CommandLineTools.md.

		Buffered first, as modify_entry.php is, because the handler sets a
		session ini value that PHP refuses once anything has been printed.
		Exits 1 when the report has an error in it, so a caller can tell.
	*/

	ob_start();

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Entries/EntryImporter.php');

	$entry_importer = new EntryImporter([
		'argv'=>$argv,
	]);

	$imported = $entry_importer->importEntries();

	ob_end_flush();

	exit($imported ? 0 : 1);

?>

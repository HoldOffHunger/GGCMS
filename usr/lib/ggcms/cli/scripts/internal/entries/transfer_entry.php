#!/usr/bin/php
<?php

	/*
		transfer.php from a shell: one entry moved beneath another, with
		everything beneath it.  See Docs/CommandLineTools.md.

		Buffered first, as modify_entry.php is, because the handler sets a
		session ini value that PHP refuses once anything has been printed.
		Exits 1 when the move was refused, so a caller can tell.
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

	require(GGCMS_CLI_DIR . 'classes/Entries/EntryTransferrer.php');

	$entry_transferrer = new EntryTransferrer([
		'argv'=>$argv,
	]);

	$transferred = $entry_transferrer->transferEntry();

	ob_end_flush();

	exit($transferred ? 0 : 1);

?>

#!/usr/bin/php
<?php

	/*
		Buffered before anything is printed, for the same reason index.php does
		it: Handler::ValidateSecurity() calls ini_set on a session setting, and
		PHP refuses that once output has been sent.  A banner printed first
		counts as sent, so the buffer holds everything until the end.
	*/

	ob_start();

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Entries/EntryModifier.php');

	$entry_modifier = new EntryModifier([
		'argv'=>$argv,
	]);

	$entry_modifier->modifyEntry();

	ob_end_flush();

?>

#!/usr/bin/php
<?php

	/*
		Lists the unresolved issue queue for one domain, filtered.

			server_issues.php revoltlib.com
			server_issues.php revoltlib.com --types
			server_issues.php revoltlib.com --type="Missing Braille Glyph" --limit=20
			server_issues.php revoltlib.com --type=404 --limit=50

		The domain is the first argument, as every other tool here takes it,
		and is prompted for when absent.  --types lists what the site has
		actually recorded, which is the fastest way to find out what is wrong
		with a site you have not looked at today.
	*/

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Issues/ListIssues.php');

	$listissues = new ListIssues([
		'argv'=>$argv,
	]);

	$listissues->listIssues();

?>

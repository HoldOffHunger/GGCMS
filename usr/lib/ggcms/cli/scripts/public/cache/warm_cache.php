#!/usr/bin/php
<?php

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

		//  The warmer generates pages rather than fetching them, so it needs the
		//  engine itself, and it must run where index.php runs.

	require(GGCMS_DIR . 'classes/StandardLibraries.php');

	chdir('/var/www/html');

	require(GGCMS_CLI_DIR . 'classes/Cache/PageCacheWarmer.php');

	$page_cache_warmer = new PageCacheWarmer([
		'argv'=>$argv,
	]);

	$page_cache_warmer->warmCache();

?>

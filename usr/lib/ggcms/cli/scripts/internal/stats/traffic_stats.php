#!/usr/bin/php
<?php

	/*
		An installed copy, or a checkout -- see cli/system/RepoDirectories.php.
		On a workstation, point --log at a copy of the host's nginx log.
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Storage/TrafficAnalytics.php');

	$traffic_analytics = new TrafficAnalytics([
		'argv'=>$argv,
	]);

	$traffic_analytics->reportTraffic();

?>

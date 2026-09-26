#!/usr/bin/php
<?php

	/*
		An installed copy, or a checkout -- see cli/system/RepoDirectories.php.
		On a workstation the beacon logs are read from GGCMS_WORK_DIR/log/.
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Storage/HumanAnalytics.php');

	$human_analytics = new HumanAnalytics([
		'argv'=>$argv,
	]);

	$human_analytics->reportHumans();

?>

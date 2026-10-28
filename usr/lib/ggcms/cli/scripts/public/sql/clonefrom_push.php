#!/usr/bin/php
<?php

	/*
		Creates clonefrom's tables in every site database that lacks them.
		See ClonefromPush.  Says what it would do unless given --apply.

			php clonefrom_push.php --table=UserHiddenHouseAd
			php clonefrom_push.php --table=UserHiddenHouseAd --apply

		Or from a checkout, against whatever MySQL that PHP is set up for:

			GGCMS_CONFIG_REPO=/path/to/GreenGluonCMS_Unhireable \
			GGCMS_WORK_DIR=/path/to/scratch \
			php clonefrom_push.php --table=UserHiddenHouseAd
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Database/ClonefromPush.php');

	$clonefrom_push = new ClonefromPush([
		'argv'=>$argv,
	]);

	exit($clonefrom_push->push() === FALSE ? 1 : 0);

?>

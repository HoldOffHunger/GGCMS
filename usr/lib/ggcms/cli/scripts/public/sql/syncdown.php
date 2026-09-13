#!/usr/bin/php
<?php

	/*
		Copies production databases down to a workstation's MySQL, so pages
		rendered, caches warmed and changes tested locally run against the data
		and schema the live host actually has.

		Runs on the workstation, from a checkout, and needs no GGCMS
		installation and no configuration repository -- only ssh access to the
		host and a local MySQL to import into.  See cli/classes/Database/SyncDown.php.

		Dry by default:

			php syncdown.php --host=***YOUR_SSH_USER***@***YOUR_PRODUCTION_HOST_HERE*** \
				--dumps=/path/to/dumps --mysql=/path/to/mysql --local-port=3306

		Then add --apply.  --database=NAME narrows to databases whose name
		contains NAME.  --host and --dumps may come from GGCMS_SYNC_HOST and
		GGCMS_SYNC_DUMPS instead; a local MySQL password comes from MYSQL_PWD.

		Exit 0 when every database synced, 2 when any failed.
	*/

	$cli_directory = str_replace('\\', '/', dirname(__DIR__, 3)) . '/';
	$ggcms_directory = str_replace('\\', '/', dirname(__DIR__, 4)) . '/';

	define('GGCMS_CLI_DIR', $cli_directory);
	define('GGCMS_DIR', $ggcms_directory . 'src/');
	define('GGCMS_DEP_DIR', $ggcms_directory . 'dep/');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Database/SyncDown.php');

	$sync_down = new SyncDown([
		'argv'=>$argv,
	]);

	exit($sync_down->syncDown());

?>

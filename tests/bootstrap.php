<?php

	/*
		Loads the engine for PHPUnit exactly as a CLI tool loads it from a
		checkout -- see usr/lib/ggcms/cli/system/RepoDirectories.php -- so
		GGCMS_CONFIG_REPO and GGCMS_WORK_DIR must be set:

			GGCMS_CONFIG_REPO=/path/to/GGCMS_Unhireable \
			GGCMS_WORK_DIR=/path/to/scratch \
			vendor/bin/phpunit

		Nothing touches a database or the network.  Scratch files go under
		GGCMS_WORK_DIR/data/tests/ and nowhere else.
	*/

	require(dirname(__DIR__) . '/usr/lib/ggcms/cli/system/RepoDirectories.php');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	ggreq('classes/StandardLibraries.php');

	require(__DIR__ . '/GGCMSTestCase.php');

?>

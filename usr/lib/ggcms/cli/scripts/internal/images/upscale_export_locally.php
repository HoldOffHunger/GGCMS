#!/usr/bin/php
<?php

	/*
		Like compress_export_locally.php, an entry point that does not read
		/var/www/ggcms_cli_directories.php.

		It is meant to run on a machine with a graphics card and no GGCMS
		installation at all -- a desktop holding nothing but a checkout, a
		batch of images and Real-ESRGAN -- so it works its own paths out from
		where the script itself sits.

		It also needs no database, no domain and no configuration.
	*/

	$cli_directory = str_replace('\\', '/', dirname(__DIR__, 3)) . '/';
	$ggcms_directory = str_replace('\\', '/', dirname(__DIR__, 4)) . '/';

	define('GGCMS_CLI_DIR', $cli_directory);
	define('GGCMS_DIR', $ggcms_directory . 'src/');
	define('GGCMS_DEP_DIR', $ggcms_directory . 'dep/');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Images/LocalExportUpscaler.php');

	$local_export_upscaler = new LocalExportUpscaler([
		'argv'=>$argv,
	]);

	$local_export_upscaler->upscaleExportLocally();

?>

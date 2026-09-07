#!/usr/bin/php
<?php

	/*
		The one entry point here that does not read
		/var/www/ggcms_cli_directories.php.

		Every other tool runs on the server, where GGCMS is installed and those
		files say where.  This one is meant to run on a machine that has no
		GGCMS installation at all -- a desktop with cores, holding nothing but
		a checkout and a batch of images -- so it works its own paths out from
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

	require(GGCMS_CLI_DIR . 'classes/Images/LocalExportCompressor.php');

	$local_export_compressor = new LocalExportCompressor([
		'argv'=>$argv,
	]);

	$local_export_compressor->setArguments();
	$local_export_compressor->setScriptPath([
		'path'=>str_replace('\\', '/', __FILE__),
	]);

	$local_export_compressor->compressExportLocally();

?>

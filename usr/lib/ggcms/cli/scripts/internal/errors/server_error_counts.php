#!/usr/bin/php
<?php

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');
	
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');
	
	require(GGCMS_CLI_DIR . 'classes/Errors/Error500Counts.php');
	
	$error500counts = new Error500Counts([
		'argv'=>$argv,
	]);
	
	$error500counts->count500errors();
	
?>
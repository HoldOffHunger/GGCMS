#!/usr/bin/php
<?php

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');
	
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');
	
	require(GGCMS_CLI_DIR . 'classes/Domain/DomainInstaller.php');
	
	$installer = new DomainInstaller([
		'argv'=>$argv,
	]);
	
	$installer->installDomain();
	
?>
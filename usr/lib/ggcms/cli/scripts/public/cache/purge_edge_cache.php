#!/usr/bin/php
<?php

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Cache/EdgeCachePurger.php');

	$edge_cache_purger = new EdgeCachePurger([
		'argv'=>$argv,
	]);

	exit($edge_cache_purger->purgeEdgeCache());

?>
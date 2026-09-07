#!/usr/bin/php
<?php

	require('/var/www/ggcms_cli_directories.php');
	require('/var/www/ggcms_install_directories.php');

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Images/ImageCandidateExporter.php');

	$image_candidate_exporter = new ImageCandidateExporter([
		'argv'=>$argv,
	]);

	$image_candidate_exporter->exportImageCandidates();

?>

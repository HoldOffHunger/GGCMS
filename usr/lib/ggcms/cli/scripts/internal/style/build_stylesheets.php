#!/usr/bin/php
<?php

	/*
		Builds every site's stylesheet into the document root's css/build/.
		See StylesheetBuilder and Docs/Styling.md.

		On the host, deploy.sh runs this after syncing:

			php build_stylesheets.php

		From a checkout, which needs to know where the site themes are:

			GGCMS_CONFIG_REPO=/path/to/GreenGluonCMS_Unhireable \
			GGCMS_WORK_DIR=/path/to/scratch \
			php build_stylesheets.php

		--quiet prints nothing unless something is wrong.
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_CLI_DIR . 'classes/Style/StylesheetBuilder.php');

		# the engine's own template sets, then, on a workstation, the site ones
	$template_dirs = [GGCMS_DIR . 'templates/'];

	if(getenv('GGCMS_CONFIG_REPO')) {
		$template_dirs[] = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', getenv('GGCMS_CONFIG_REPO')), '/') . '/usr/lib/ggcms/src/templates/';
	}

	$builder = new StylesheetBuilder([
		'enginecssdir'=>GGCMS_DIR . 'css/',
		'templatedirs'=>$template_dirs,
		'outputdir'=>GGCMS_DOC_ROOT . 'css/build/',
		'quiet'=>in_array('--quiet', $argv, TRUE),
	]);

	exit($builder->Build() === FALSE ? 1 : 0);

?>

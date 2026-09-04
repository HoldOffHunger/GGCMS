#!/usr/bin/php
<?php

	/*
		Renders a site's pages into the page cache.

		Runs from an installed copy or from a checkout.  A checkout is the
		interesting case -- see cli/system/RepoDirectories.php -- because the
		machine with spare cores is not the machine serving the site.

		From a checkout:

			GGCMS_CONFIG_REPO=/path/to/GreenGluonCMS_Unhireable \
			GGCMS_WORK_DIR=/path/to/scratch \
			GGCMS_PAGE_CACHE_ROOT=/path/to/scratch/pages \
			php warm_page_cache.php --domain=revoltlib.com --jobs=8 --skip-cached

		Then ship the tree, which is the whole point:

			rsync -az --info=progress2 /path/to/scratch/pages/revoltlib.com/ \
				root@host:/mnt/nyc01/ggcms_cache/pages/revoltlib.com/

		Shipping is deliberately not done here.  Rendering is safe to repeat
		and safe to interrupt; overwriting a live site's cache is neither, and
		wants a person's hand on it.
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Cache/PageCacheWarmer.php');

	$warmer = new PageCacheWarmer([
		'argv'=>$argv,
	]);

	$warmer->Warm();

?>

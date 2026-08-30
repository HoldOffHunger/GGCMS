<?php

	require('/var/www/ggcms_install_directories.php');
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');

	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);

	/*
		Output buffering exists solely so the finished page can be handed to
		the page cache.  The render pipeline prints as it goes and returns
		nothing, so this buffer is the only place a complete page exists.

		A cache hit never reaches this file at all -- .htaccess serves the
		file directly and PHP is never started.  See Docs/PageCache.md.
	*/

	ob_start();

	$handler = NULL;

	try {
		error_reporting(0);
		require(GGCMS_DIR . 'classes/StandardLibraries.php');
		$handler = new Handler();
		$handler->HandleRequest();
	} catch (Exception $exception) {
		print("My caught exception is...|" . $exception->getMessage() . "|");
	}

	$page_output = ob_get_contents();

	/*
		Send the page before touching the disk.  The visitor is not made to
		wait on a cache write they gain nothing from.
	*/

	ob_end_flush();
	flush();

	/*
		Cache writing is best-effort.  A full volume, a lost mkdir race or a
		read-only mount must slow the site down, never break it, so every
		failure path in PageCache returns FALSE rather than throwing.
	*/

	if($handler) {
		try {
			ggreq('classes/Cache/PageCache.php');

			$page_cache = new PageCache(['handler'=>$handler]);
			$page_cache->WriteCache(['output'=>$page_output]);
		} catch (Exception $exception) {
			# a cache failure is never worth a broken response
		}
	}

	exit(1);

?>

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
	$request_failed = FALSE;

	try {
		error_reporting(0);
		require(GGCMS_DIR . 'classes/StandardLibraries.php');
		$handler = new Handler();
		$handler->HandleRequest();
	} catch (Throwable $exception) {
		$request_failed = TRUE;
		ob_clean();
		http_response_code(500);
		try {
			if($handler && isset($handler->error_logging)) {
				$handler->error_logging->mylog(
					get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine(),
					'fatal',
					$exception->getTraceAsString()
				);
			} else {
				print('Internal Server Error');
			}
		} catch (Throwable $logging_exception) {
			ob_clean();
			print('Internal Server Error');
		}
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

	if($handler && !$request_failed) {
		try {
			# Handler::SendBrowserCacheHeader may already have loaded it
			if(!class_exists('PageCache')) {
				ggreq('classes/Cache/PageCache.php');
			}

			$page_cache = new PageCache(['handler'=>$handler]);
			$page_cache->WriteCache(['output'=>$page_output]);
		} catch (Exception $exception) {
			# a cache failure is never worth a broken response
		}
	}

	exit(1);

?>

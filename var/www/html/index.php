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
		fil
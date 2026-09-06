#!/usr/bin/php
<?php

	/*
		How big is GGCMS, and where does the bulk live?

		Runs from an installed copy or from a checkout -- see
		cli/system/RepoDirectories.php.  A checkout is the ordinary case here,
		because measuring source is a thing you do on the workstation holding
		the repository rather than on the server serving it.

		From a checkout:

			GGCMS_CONFIG_REPO=/path/to/GreenGluonCMS_Unhireable \
			GGCMS_WORK_DIR=/path/to/scratch \
			php code_size.php

		Nothing is written and nothing is read from the database, so both of
		those variables are pure bootstrap ceremony in this one tool.  They are
		required anyway rather than worked around, because a second way of
		starting a CLI script is worth more confusion than it saves typing.

		ARGUMENTS -- all optional, all narrowing:

			--root=src|cli|dep|all   which tree.  Default src.  dep/ is
			                         vendored third-party code and is left out
			                         unless you ask, because it answers a
			                         different question than the one you asked.
			--folder=NAME            substring match on the group name, e.g.
			                         --folder=classes/Database
			--ext=php,html           only these extensions
			--depth=N                folder breakdown depth.  1 gives
			                         classes, traits, templates; 2 gives
			                         classes/Database, classes/Cache, ...
			--sort=bytes|lines|code|files    default bytes
			--top=N                  keep only the N biggest rows
			--min-bytes=N            drop files smaller than this
			--files                  add a largest-files table
			--csv                    machine-readable instead of drawn tables

		Examples:

			php code_size.php --depth=2 --sort=code --top=15
			php code_size.php --folder=templates --files
			php code_size.php --root=all --ext=php --csv
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Source/SourceStatistics.php');

	$source_statistics = new SourceStatistics([
		'argv'=>$argv,
	]);

	$source_statistics->report();

?>

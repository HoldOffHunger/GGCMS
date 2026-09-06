#!/usr/bin/php
<?php

	/*
		If I had to restore right now, could I?

		backup_database.php writes dumps and is interactive, one site at a
		time. This reads them and is not interactive at all, so it can run
		from cron, where the exit code is the whole message:

			0   every site passes
			1   warnings only -- a dump exists and is intact, but restoring it
			    needs care (see LATIN1) or its size moved sharply
			2   at least one site has no usable backup

		Runs from an installed copy or from a checkout -- see
		cli/system/RepoDirectories.php.

		From a checkout:

			GGCMS_CONFIG_REPO=/path/to/GreenGluonCMS_Unhireable \
			GGCMS_WORK_DIR=/path/to/scratch \
			php confirm_database_backup.php

		From cron on the server, daily, mailing only on trouble:

			30 6 * * *  /usr/lib/ggcms/cli/scripts/public/sql/confirm_database_backup.php --quiet

		ARGUMENTS -- all optional:

			--domain=NAME          only sites matching this substring
			--max-age-hours=N      staleness threshold, default 168 (a week)
			--min-bytes=N          below this a dump is TINY, default 1024
			--shrink-percent=N     warn when a dump is this much smaller than
			                       the previous archived one, default 25
			--deep                 also compare CREATE TABLE count in the file
			                       against the live database's table count.
			                       Reads every line of every dump, so it is
			                       slow on large sites and off by default.
			--quiet                print only sites that are not OK
			--csv                  machine-readable instead of a drawn table

		The site list comes from the configuration repository rather than from
		the backup directory, so a site that has never been dumped is reported
		as MISSING instead of silently not appearing. That distinction is the
		entire reason this exists.
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Database/BackupConfirmation.php');

	$backup_confirmation = new BackupConfirmation([
		'argv'=>$argv,
	]);

	exit($backup_confirmation->confirm());

?>

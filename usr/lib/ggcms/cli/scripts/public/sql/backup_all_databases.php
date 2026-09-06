#!/usr/bin/php
<?php

	/*
		Dumps every configured site. Asks nothing, so cron can run it.

		backup_database.php remains the tool for dumping one site with a person
		watching. This is the one for dumping all of them with nobody watching,
		and confirm_database_backup.php is the one that reads the results back.

			0   every site dumped and passed its own footer check
			2   at least one site failed; the sites that failed kept the
			    backup they already had

		Runs from an installed copy or from a checkout -- see
		cli/system/RepoDirectories.php.

		From cron on the server, nightly, with the verifier behind it:

			15 3 * * *  /usr/lib/ggcms/cli/scripts/public/sql/backup_all_databases.php --quiet
			30 6 * * *  /usr/lib/ggcms/cli/scripts/public/sql/confirm_database_backup.php --quiet

		Both are silent when everything is fine and both exit non-zero when it
		is not, which is the only way cron ever tells anybody anything.

		ARGUMENTS -- all optional:

			--domain=NAME    only sites matching this substring
			--keep=N         archived dumps to retain per site, default 3
			--no-gzip        write plain .sql instead of .sql.gz. The whole
			                 estate compresses from about 7 GB to 433 MB, so
			                 gzip is the default and this is the escape hatch.
			--dry-run        print the mysqldump command for each site and
			                 write nothing
			--quiet          summary only

		A dump is written to a .partial name and renamed into place only after
		its "Dump completed" footer is confirmed, and the previous dump is not
		moved to archive until that has happened. A site whose dump fails keeps
		the backup it already had, which is the property that matters most in a
		tool that runs unattended.
	*/

	if(getenv('GGCMS_CONFIG_REPO')) {
		require(__DIR__ . '/../../../system/RepoDirectories.php');
	} else {
		require('/var/www/ggcms_cli_directories.php');
		require('/var/www/ggcms_install_directories.php');
	}

	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');

	require(GGCMS_CLI_DIR . 'classes/Database/BackupAll.php');

	$backup_all = new BackupAllDatabases([
		'argv'=>$argv,
	]);

	exit($backup_all->backupAll());

?>

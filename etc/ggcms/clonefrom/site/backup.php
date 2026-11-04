<?php

	class AbstractGlobals_Site_backup {

			/*
				Whether backup_all_databases.php dumps this site at 03:15.

				Off by default since 4 November 2026.  The content is not
				changing -- the only writes are visitors commenting and
				joining -- and the dump of every site was still running half an
				hour later on a one-core host already at a load of 36, while
				visitors gave up on a quarter of their requests.  The newest
				dump of every site is kept where it is, and
				confirm_database_backup.php stops calling it stale.

				Turn it back on for every site by returning TRUE here, or for
				one site at etc/ggcms/<reverse-dns>/site/backup.php in a class
				named AbstractGlobals_Site_backup_override that extends this
				one.  A database with no site configuration -- the ones
				--all-databases adds -- follows this default, because it has
				no domain to override it.

				backup_all_databases.php --force dumps regardless, for the day
				before a change worth having a copy from.
			*/

		public function NightlyDatabaseBackup() {
			return FALSE;
		}
	}

?>

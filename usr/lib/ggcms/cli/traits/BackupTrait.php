<?php

	trait BackupTrait {
			/*
				Where database dumps live.

				They used to live under GGCMS_LOG_DIR, which is the root disk --
				25 GB, and the disk filling is what took this host down in 2024.
				The nightly dump of every site is 433 MB and it is rewritten
				each night, so /var/log/ggcms held 907 MB of dumps against 8 GB
				free, while the 52 GB volume that already holds the caches sat
				at 24 percent.

				The path was built in five places across four classes, which is
				why this method exists rather than a sixth literal. Backup,
				BackupAll, BackupConfirmation and DatabasePurge each composed it
				from GGCMS_LOG_DIR independently, so moving it meant finding all
				of them -- and missing one would leave the confirmation tool
				looking in the old place and reporting every site as MISSING,
				which is the alarm that exists to catch a real absence.
			*/

		public function databaseDumpRoot() {
			return '/mnt/nyc01/ggcms_sql_backups/';
		}

			/*
				`type` is 'backup' or 'archive'. One method taking both rather
				than two, because every caller that wants one wants the other
				on the next line.
			*/

		public function databaseDumpDirectory($args) {
			return $this->databaseDumpRoot() . $args['domain'] . '/' . $args['type'] . '/';
		}

			/*
				Whether this site's NightlyDatabaseBackup() global says yes.
				See etc/ggcms/clonefrom/site/backup.php.

				Asked of a separate php process per site, because every domain
				override declares the same class name -- right for the engine,
				where one request serves one domain, and fatal here, where one
				process walks all of them.  SchemaChecker gets round the same
				thing by reading the files as text, which is fine for a report
				and not for a switch.

				Fails open.  With no switch installed, or one that cannot be
				read, the answer is "back it up": a broken setting must not be
				the reason a backup stopped.
			*/

		public function nightlyBackupWanted($args) {
			$domain = $args['domain'];

			$shared_location = GGCMS_CONFIG_DIR . 'clonefrom/site/backup.php';

			if(!is_file($shared_location)) {
				return TRUE;
			}

			$reversed_domain = implode('.', array_reverse(explode('.', $domain)));
			$domain_location = GGCMS_CONFIG_DIR . $reversed_domain . '/site/backup.php';

			$code = 'require ' . var_export($shared_location, TRUE) . '; $classname = "AbstractGlobals_Site_backup";';

			if(is_file($domain_location)) {
				$code .= ' require ' . var_export($domain_location, TRUE) . '; $classname .= "_override";';
			}

			$code .= ' $globals = new $classname; print($globals->NightlyDatabaseBackup() ? "yes" : "no");';

			$output = [];
			$exit_code = 0;

			exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>/dev/null', $output, $exit_code);

			return !($exit_code === 0 && trim(implode('', $output)) === 'no');
		}

		public function generateCurrentBackupFilename($args) {
			$base_filename = $args['base_filename'];
			$extension = $args['extension'];
			
			$backup_filename = $base_filename;
			
			$microtime = microtime();
			
			#print("BT: MICRO TIIIIIIIIIIIME!" . $microtime . '|' . PHP_EOL . PHP_EOL);
			
			$microtime_pieces = explode(' ', $microtime);
			
			$fraction_value = $microtime_pieces[0];
			
			/*
			print_r(
	preg_replace('/^0./', '', $string)
);
			*/
			$fraction_value = preg_replace('/^0./', '', $fraction_value);
			
			$integer_value = $microtime_pieces[1];
			
			$backup_filename .= '_' . $integer_value . '_' . $fraction_value;
			
			if(strlen($extension) !== 0) {
				$backup_filename .= '.' . $extension;
			}
			
			#print_r($microtime_pieces);
			
			return $backup_filename;
		}
	}

?>
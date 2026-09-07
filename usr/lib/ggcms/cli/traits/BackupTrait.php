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
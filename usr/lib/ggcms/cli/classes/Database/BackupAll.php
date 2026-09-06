<?php

	/*
		Dumps every configured site, without asking anybody anything.

		backup_database.php is interactive and does one site at a time, which
		makes it a fine thing for a person and an impossible thing for cron.
		That is why only two of eighteen sites had ever been dumped when the
		managed cluster went unreachable on 6 September 2026: the tool worked,
		and nobody was going to sit and run it nineteen times.

		So this asks nothing, dumps everything, and reports what happened.
		Paired with confirm_database_backup.php it closes the loop -- this one
		writes, that one reads back, and neither trusts the other.

		IT VERIFIES ITS OWN OUTPUT BEFORE CALLING IT A BACKUP.

		mysqldump can fail after writing a great deal of perfectly good SQL --
		a dropped connection, a full disk, a killed process -- and what it
		leaves behind looks exactly like a backup. So every dump is checked for
		the "Dump completed" footer before the previous one is allowed to age
		out of the archive, and a site whose dump fails keeps the older file it
		already had. Never destroy the backup you have to make room for one you
		have not finished writing.

		ROTATION

		  backup/    the current dump, one file
		  archive/   previous dumps, newest first, --keep of them

		The current file moves to archive before a new one is written, which
		matches what backup_database.php has always done, so the two tools can
		be used on the same tree without surprising each other.

		CHARSET

		The dump declares its own character set. This is not decoration: dumps
		written before 1 September 2026 went through a latin1 connection and
		are lossy above cp1252, and dumps written between then and 6 September
		2026 hold correct utf8mb4 bytes but never said so, because the command
		carried -N -- which mysqldump documents as --no-set-names. Both classes
		of file are indistinguishable from a good one by looking at the name.
		Everything written here declares itself, so that stops being true going
		forward.
	*/

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');

	class BackupAllDatabases {
		use CLIAccess;

		public function bannerMessageText() {
			return 'Backup All Databases';
		}

			// Entry point

		public function backupAll() {
			$this->readArguments();
			$this->bannerMessage();

			$sites = $this->siteList();

			if(!count($sites)) {
				print("No sites found. Checked " . GGCMS_CONFIG_DIR . " for com.*.php configuration files.\n");

				return 2;
			}

			$results = [];

			foreach($sites as $domain => $database) {
				$result = $this->backupSite([
					'domain'   => $domain,
					'database' => $database,
				]);

				$results[] = $result;

				if(!$this->quiet) {
					printf("  %-26s %-8s %10s  %s\n",
						$result['domain'],
						$result['status'],
						$result['size'],
						$result['note']
					);
				}
			}

			print("\n");

			$this->printSummary($results);

			return $this->exitCode($results);
		}

			// Arguments

		public function readArguments() {
			$this->only_domain = $this->argumentValue('domain', '');
			$this->keep        = max(1, (int) $this->argumentValue('keep', 3));
			$this->gzip        = !$this->argumentPresent('no-gzip');
			$this->dry_run     = $this->argumentPresent('dry-run');
			$this->quiet       = $this->argumentPresent('quiet');

			return TRUE;
		}

		public function argumentValue($name, $default) {
			foreach($this->argv as $argument) {
				if(strpos($argument, '--' . $name . '=') === 0) {
					return substr($argument, strlen($name) + 3);
				}
			}

			return $default;
		}

		public function argumentPresent($name) {
			return in_array('--' . $name, $this->argv, TRUE);
		}

		public function siteList() {
			$sites = [];

			foreach((array) @scandir(GGCMS_CONFIG_DIR) as $entry) {
				if(!preg_match('/^([a-z]+)\.([a-z0-9\-]+)\.php$/i', (string) $entry, $matches)) {
					continue;
				}

				$domain   = $matches[2] . '.' . $matches[1];
				$database = $matches[2];

				if(strlen($this->only_domain) && stripos($domain, $this->only_domain) === FALSE) {
					continue;
				}

				$sites[$domain] = $database;
			}

			ksort($sites);

			return $sites;
		}

			// One site

		public function backupSite($args) {
			$domain   = $args['domain'];
			$database = $args['database'];

			$result = [
				'domain' => $domain,
				'status' => 'FAILED',
				'size'   => '-',
				'note'   => '',
			];

			$backup_dir  = GGCMS_LOG_DIR . $domain . '/sql/backup/';
			$archive_dir = GGCMS_LOG_DIR . $domain . '/sql/archive/';

			foreach([$backup_dir, $archive_dir] as $directory) {
				if(!is_dir($directory) && !@mkdir($directory, 0755, TRUE)) {
					$result['note'] = 'cannot create ' . $directory;

					return $result;
				}
			}

			$filename = 'mysqldump_' . $domain . '_' . time() . '.sql' . ($this->gzip ? '.gz' : '');
			$target   = $backup_dir . $filename;

				/*
					Written to a .partial name and renamed only after the
					footer check passes. A reader arriving mid-dump then sees
					no current backup rather than half of one, and half of one
					is the shape of file that gets restored by mistake.
				*/

			$partial = $target . '.partial';

			$command = $this->dumpCommand([
				'database' => $database,
				'target'   => $partial,
			]);

			if($this->dry_run) {
				$result['status'] = 'DRYRUN';
				$result['note']   = $command;

				@unlink($this->last_stderr_file);

				return $result;
			}

			$output    = [];
			$exit_code = 0;

			exec($command, $output, $exit_code);

			$stderr = $this->readStderr();

			if($exit_code !== 0) {
				@unlink($partial);

				$result['note'] = 'mysqldump exited ' . $exit_code . '; previous backup left in place'
					. (strlen($stderr) ? ' -- ' . $stderr : '');

				return $result;
			}

			if(!$this->endsCleanly($partial)) {
				@unlink($partial);

				$result['note'] = 'no "Dump completed" footer; previous backup left in place';

				return $result;
			}

				/*
					Only now is the old dump moved aside. Everything above can
					fail, and if it does the site keeps the backup it had.
				*/

			$this->rotate([
				'backup_dir'  => $backup_dir,
				'archive_dir' => $archive_dir,
			]);

			if(!@rename($partial, $target)) {
				@unlink($partial);

				$result['note'] = 'could not rename into place';

				return $result;
			}

			$this->pruneArchive($archive_dir);

			$result['status'] = 'OK';
			$result['size']   = $this->humanBytes((int) @filesize($target));
			$result['note']   = $filename;

			return $result;
		}

			/*
				No -N. mysqldump documents it as --no-set-names, "Same as
				--skip-set-charset", and it was quietly stripping the SET NAMES
				line from every dump this project has ever taken.
			*/

		public function dumpCommand($args) {
			$database = $args['database'];
			$target   = $args['target'];

			$dump = 'nice mysqldump'
				. ' --max_allowed_packet=64M'
				. ' --default-character-set=utf8mb4'
				. ' --set-charset'
				. ' --no-tablespaces'
				. ' --routines'
				. ' --events'
				. ' --triggers'
				. ' --single-transaction'
				. ' --quick'
				. ' --set-gtid-purged=OFF'
				. ' ' . escapeshellarg($database);

			if($this->gzip) {
				$dump .= ' | gzip -6';
			}

				/*
					TWO THINGS THIS LINE HAS TO GET RIGHT, AND BOTH ARE EASY TO
					GET WRONG.

					pipefail. `mysqldump | gzip` reports gzip's exit status, and
					gzip is perfectly happy to compress a truncated stream and
					succeed. Without pipefail a database that died halfway
					through produces a valid .gz, an exit code of zero, and a
					backup that is not one. bash is named explicitly because
					/bin/sh here is dash, which has no pipefail.

					Where stderr goes. `> file 2>&1` sends mysqldump's warnings
					into the dump itself, which corrupts the very file being
					written. It goes to its own file instead, and is read back
					only when something failed.
				*/

			$stderr = $target . '.err';

			$this->last_stderr_file = $stderr;

			return 'bash -c ' . escapeshellarg(
				'set -o pipefail; ' . $dump . ' > ' . escapeshellarg($target)
			) . ' 2> ' . escapeshellarg($stderr);
		}

		public function readStderr() {
			if(!property_exists($this, 'last_stderr_file') || !is_file($this->last_stderr_file)) {
				return '';
			}

			$text = trim((string) @file_get_contents($this->last_stderr_file));

			@unlink($this->last_stderr_file);

			return substr(str_replace(["", "
"], ' ', $text), 0, 160);
		}

			/*
				--single-transaction, so the dump is consistent without locking
				a live site's tables for the length of a seven-gigabyte read.
				It is InnoDB throughout; on MyISAM this would be worth nothing
				and a lock would be worth having.
			*/

		public function endsCleanly($path) {
			$tail = '';

				/*
					The name tested is the name without .partial. Dumps are
					written as <name>.sql.gz.partial and checked before being
					renamed, so testing the raw path sent every gzip dump down
					the plain-file branch, which seeked into compressed bytes,
					found no footer, and threw away a perfectly good backup.
				*/

			$name = (substr($path, -8) === '.partial') ? substr($path, 0, -8) : $path;

			if(substr($name, -3) === '.gz') {
				$handle = @gzopen($path, 'rb');

				if($handle === FALSE) {
					return FALSE;
				}

				while(!gzeof($handle)) {
					$chunk = gzread($handle, 65536);

					if($chunk === FALSE || $chunk === '') {
						break;
					}

					$tail = substr($tail . $chunk, -4096);
				}

				gzclose($handle);
			} else {
				$handle = @fopen($path, 'rb');

				if($handle === FALSE) {
					return FALSE;
				}

				$size = (int) @filesize($path);
				$read = min(4096, $size);

				if($read > 0) {
					fseek($handle, -$read, SEEK_END);
				}

				$tail = (string) fread($handle, max(1, $read));

				fclose($handle);
			}

			return strpos($tail, 'Dump completed') !== FALSE;
		}

		public function rotate($args) {
			$backup_dir  = $args['backup_dir'];
			$archive_dir = $args['archive_dir'];

			foreach((array) @scandir($backup_dir) as $entry) {
				if($entry === '.' || $entry === '..') {
					continue;
				}

				$path = $backup_dir . $entry;

				if(!is_file($path) || substr($entry, -8) === '.partial') {
					continue;
				}

				@rename($path, $archive_dir . $entry);
			}

			return TRUE;
		}

		public function pruneArchive($archive_dir) {
			$files = [];

			foreach((array) @scandir($archive_dir) as $entry) {
				if($entry === '.' || $entry === '..') {
					continue;
				}

				$path = $archive_dir . $entry;

				if(is_file($path)) {
					$files[$path] = (int) @filemtime($path);
				}
			}

			arsort($files);

			$index = 0;

			foreach($files as $path => $modified) {
				$index++;

				if($index > $this->keep) {
					@unlink($path);
				}
			}

			return TRUE;
		}

			// Reporting

		public function printSummary($results) {
			$counts = [];

			foreach($results as $result) {
				$status = $result['status'];

				$counts[$status] = array_key_exists($status, $counts) ? $counts[$status] + 1 : 1;
			}

			$pieces = [];

			foreach($counts as $status => $count) {
				$pieces[] = $count . ' ' . $status;
			}

			print('SUMMARY  ' . count($results) . ' site(s):  ' . implode(',  ', $pieces) . "\n\n");

			foreach($results as $result) {
				if($result['status'] === 'FAILED') {
					print('  FAILED: ' . $result['domain'] . ' -- ' . $result['note'] . "\n");
				}
			}

			return TRUE;
		}

		public function exitCode($results) {
			foreach($results as $result) {
				if($result['status'] === 'FAILED') {
					return 2;
				}
			}

			return 0;
		}

		public function humanBytes($bytes) {
			$units = ['B', 'KB', 'MB', 'GB'];
			$index = 0;

			while($bytes >= 1024 && $index < count($units) - 1) {
				$bytes /= 1024;
				$index++;
			}

			return number_format($bytes, $index === 0 ? 0 : 1) . ' ' . $units[$index];
		}
	}

?>

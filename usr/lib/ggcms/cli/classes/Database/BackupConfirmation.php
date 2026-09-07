<?php

	/*
		Answers one question: if I had to restore right now, could I?

		backup_database.php makes dumps. Nothing checked them, and an unchecked
		backup is a belief rather than a backup. On 6 September 2026 the managed
		database cluster became unreachable for thirteen hours, and the honest
		position at that moment was that the newest local dump was from October
		2023 and only two of nineteen sites had ever been dumped at all. Nobody
		had lied; nobody had looked.

		So this looks, non-interactively, and exits non-zero when something is
		wrong -- which is what makes it useful from cron, where the exit code is
		the whole message.

		WHAT IT CHECKS, WORST FIRST

		  MISSING    the site has no dump at all. This is the finding that
		             matters and it is reported per site rather than as a
		             count, because "seventeen missing" is a number and
		             "revoltlib has never been dumped" is a decision.
		  STALE      older than --max-age-hours.
		  TRUNCATED  mysqldump writes "Dump completed on ..." as its last line.
		             A dump without it stopped early -- disk full, killed
		             process, connection dropped -- and will restore a partial
		             database without complaining.
		  TINY       smaller than --min-bytes. Catches the zero-byte file that
		             a failed dump leaves behind looking exactly like success.
		  SHRUNK     more than --shrink-percent smaller than the previous
		             archived dump of the same site. Content does get deleted,
		             so this is a warning and not a failure, but a database
		             that halved overnight is worth a human glance.
		  NO_CHARSET the file carries no SET NAMES and so does not say how it
		             should be read. Two faults produce that. A dump written
		             before 1 September 2026 went through a latin1 connection
		             and is genuinely lossy above cp1252; a dump written
		             between then and 6 September 2026 holds correct utf8mb4
		             bytes but carried -N, which mysqldump documents as
		             --no-set-names, so it never declared them. The file cannot
		             tell you which it is -- its date can. Either way, nothing
		             in the filename says so, and a dump restored wrongly is
		             worse than one you know you do not have.

		With --deep it also counts CREATE TABLE statements in the file and
		compares them against the live database's table count, which is the
		only check here that can catch a dump that is complete, recent, well
		formed and still missing half the schema.

		The domain list comes from the configuration repository, not from
		whatever happens to be sitting in the log directory, so a site that has
		never been dumped still appears -- as MISSING, loudly. A tool that can
		only see the backups that exist cannot tell you about the ones that do
		not.
	*/

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/BackupTrait.php');

	class BackupConfirmation {
		use CLIAccess;
		use DomainValidation;
		use BackupTrait;

		public function bannerMessageText() {
			return 'Confirm Database Backups';
		}

			// Entry point

		public function confirm() {
			$this->readArguments();
			$this->bannerMessage();

			$sites = $this->siteList();

			if(!count($sites)) {
				print("No sites found. Checked " . GGCMS_CONFIG_DIR . " for com.*.php configuration files.\n");

				return 2;
			}

			$results = [];

			foreach($sites as $domain => $database) {
				$results[] = $this->inspectSite([
					'domain'   => $domain,
					'database' => $database,
				]);
			}

			$this->printResults($results);

			return $this->exitCode($results);
		}

			// Arguments

		public function readArguments() {
			$this->only_domain    = $this->argumentValue('domain', '');
			$this->max_age_hours  = (int) $this->argumentValue('max-age-hours', 168);
			$this->min_bytes      = (int) $this->argumentValue('min-bytes', 1024);
			$this->shrink_percent = (int) $this->argumentValue('shrink-percent', 25);
			$this->deep           = $this->argumentPresent('deep');
			$this->as_csv         = $this->argumentPresent('csv');
			$this->quiet          = $this->argumentPresent('quiet');

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

			/*
				Configuration files are named in reversed-DNS form --
				com.revoltlib.php -- and the database is named for the site
				without its suffix, so com.revoltlib.php means revoltlib.com
				backed by the database `revoltlib`.
			*/

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

			// Inspection

		public function inspectSite($args) {
			$domain   = $args['domain'];
			$database = $args['database'];

			$result = [
				'domain'   => $domain,
				'database' => $database,
				'file'     => '',
				'bytes'    => 0,
				'age'      => '',
				'status'   => 'MISSING',
				'notes'    => [],
			];

			$backup_dir = $this->databaseDumpDirectory(['domain'=>$domain, 'type'=>'backup']);

			$newest = $this->newestFile($backup_dir);

			if($newest === '') {

					/*
						Only asked when a dump is absent, so an ordinary run
						never touches the database. A configuration file can
						outlive its site -- abstractcon.com is retired and its
						config is not -- and reporting that as a backup failure
						would exit 2 every night for something no backup can
						ever fix.
					*/

				$state = $this->databaseState($database);

				if($state === 'absent') {
					$result['status'] = 'NO_DB';
					$result['notes'][] = 'no database named `' . $database . '`; retired site? delete the stale config';

					return $result;
				}

				$result['notes'][] = 'no dump has ever been taken';

				if($state === 'unknown') {
					$result['notes'][] = 'and the database could not be reached, so this may be an unbacked-up site or an unreachable one -- they are not the same problem';
				}

				return $result;
			}

			$path = $backup_dir . $newest;

			$result['file']  = $newest;
			$result['bytes'] = (int) @filesize($path);

			$modified   = (int) @filemtime($path);
			$age_hours  = ($modified > 0) ? (time() - $modified) / 3600 : 0;
			$result['age'] = $this->humanAge($age_hours);

			$failures = [];
			$warnings = [];

			if($result['bytes'] < $this->min_bytes) {
				$failures[] = 'TINY';
				$result['notes'][] = 'only ' . number_format($result['bytes']) . ' bytes';
			}

			if(!$this->endsCleanly($path)) {
				$failures[] = 'TRUNCATED';
				$result['notes'][] = 'no "Dump completed" marker; the dump stopped early';
			}

			if($age_hours > $this->max_age_hours) {
				$failures[] = 'STALE';
				$result['notes'][] = 'older than ' . $this->max_age_hours . 'h';
			}

			if(!$this->declaresCharset($path)) {
				$warnings[] = 'NO_CHARSET';

					/*
						Renamed from LATIN1 on 6 September 2026, because that
						name asserted more than the check knows. Two different
						faults produce a dump with no SET NAMES: one written
						through a latin1 connection before 1 September, which
						is genuinely lossy above cp1252, and one written after
						it but carrying -N, which holds correct utf8mb4 bytes
						and merely fails to say so. The file cannot tell you
						which it is. Say what is true and let the dump's date
						decide the rest.
					*/

				$result['notes'][] = 'file declares no character set; if dumped before 1 Sept 2026 restore with --default-character-set=latin1, otherwise it is utf8mb4 undeclared';
			}

			$shrink = $this->shrinkAgainstArchive([
				'domain' => $domain,
				'bytes'  => $result['bytes'],
				'path'   => $path,
			]);

			if($shrink['percent'] > $this->shrink_percent) {
				$warnings[] = 'SHRUNK';
				$result['notes'][] = $shrink['percent'] . '% smaller than the previous dump (' . $shrink['date'] . ')';
			}

			if($this->deep) {
				$in_file = $this->listDumpedTables($path);
				$in_live = $this->listLiveTables($database);

				if($in_live === NULL) {
					$result['notes'][] = 'tables ' . count($in_file) . ' in file / live count unavailable';
				} else {
					$missing = array_values(array_diff($in_live, $in_file));

					$result['notes'][] = 'tables ' . count($in_file) . ' in file / ' . count($in_live) . ' live';

					if(count($missing)) {
						$failures[] = 'INCOMPLETE';

							/*
								Name them. A count cannot distinguish a dump
								that failed from a dump that is simply older
								than the schema -- on the first real run, two
								of the four "missing" tables had been created
								fourteen seconds after the dump finished, and
								two had existed since 2023. Only the names
								tell those apart, and only a person can judge
								which is which, so hand them the names.
							*/

						$result['notes'][] = 'not in dump: ' . implode(', ', array_slice($missing, 0, 8))
							. (count($missing) > 8 ? ' (+' . (count($missing) - 8) . ' more)' : '')
							. ' -- check these against the dump date before assuming loss';
					}
				}
			}

			if(count($failures)) {
				$result['status'] = implode('+', $failures);
			} elseif(count($warnings)) {
				$result['status'] = implode('+', $warnings);
			} else {
				$result['status'] = 'OK';
			}

			return $result;
		}

		public function newestFile($directory) {
			if(!is_dir($directory)) {
				return '';
			}

			$newest_name = '';
			$newest_time = 0;

			foreach((array) @scandir($directory) as $entry) {
				if($entry === '.' || $entry === '..') {
					continue;
				}

				$path = $directory . $entry;

				if(!is_file($path)) {
					continue;
				}

				$modified = (int) @filemtime($path);

				if($modified >= $newest_time) {
					$newest_time = $modified;
					$newest_name = $entry;
				}
			}

			return $newest_name;
		}

			/*
				Read the tail rather than the file. These dumps run to
				hundreds of megabytes and the only interesting part is the
				last line, so 4 KB from the end answers it without paying for
				the rest.
			*/

		public function endsCleanly($path) {
			if($this->isGzip($path)) {

					/*
						A gzip stream cannot be seeked to its end cheaply, so
						the whole file is read and only the last 4 KB kept.
						That is the price of compression and it is worth it:
						the estate is about 7 GB uncompressed and 433 MB gzipped.
					*/

				$handle = @gzopen($path, 'rb');

				if($handle === FALSE) {
					return FALSE;
				}

				$tail = '';

				while(!gzeof($handle)) {
					$chunk = gzread($handle, 65536);

					if($chunk === FALSE || $chunk === '') {
						break;
					}

					$tail = substr($tail . $chunk, -4096);
				}

				gzclose($handle);

				return strpos($tail, 'Dump completed') !== FALSE;
			}

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

			return strpos($tail, 'Dump completed') !== FALSE;
		}

			/*
				Three answers, not two. "The database is gone" and "I could not
				ask" are different facts and only one of them is the site's
				fault, but a boolean forces them into the same box -- and the
				box it picked was MISSING, which at 06:30 reads as data loss
				when it may only mean the credentials were not readable.

				Found by running the cron line under env -i, which strips HOME
				and so hides /root/.my.cnf from the mysql client. Real cron sets
				HOME and the tool behaves correctly, but a check that reports
				loss when it means uncertainty is worth not shipping.
			*/

		public function databaseState($database) {
			$command = 'mysql -N -e ' . escapeshellarg(
				'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ' .
				"'" . str_replace("'", '', $database) . "'"
			) . ' 2>/dev/null';

			$output = trim((string) shell_exec($command));

			if($output === '0') {
				return 'absent';
			}

			if($output === '1') {
				return 'present';
			}

			return 'unknown';
		}

		public function isGzip($path) {
			return strtolower(substr($path, -3)) === '.gz';
		}

		public function declaresCharset($path) {
			if($this->isGzip($path)) {
				$handle = @gzopen($path, 'rb');

				if($handle === FALSE) {
					return FALSE;
				}

				$head = (string) gzread($handle, 8192);

				gzclose($handle);
			} else {
				$handle = @fopen($path, 'rb');

				if($handle === FALSE) {
					return FALSE;
				}

				$head = (string) fread($handle, 8192);

				fclose($handle);
			}

			return stripos($head, 'SET NAMES') !== FALSE;
		}

		public function shrinkAgainstArchive($args) {
			$domain       = $args['domain'];
			$bytes        = $args['bytes'];
			$current_path = $args['path'];

			$archive_dir = $this->databaseDumpDirectory(['domain'=>$domain, 'type'=>'archive']);

			$previous = $this->newestFile($archive_dir);

			$none = ['percent' => 0, 'date' => ''];

			if($previous === '' || $bytes <= 0) {
				return $none;
			}

				/*
					Compare like with like or not at all. The first gzipped
					dump of every site sat beside an uncompressed archive and
					was reported as "95% smaller", which is true of the bytes
					and false about the data. A comparison across compression
					formats measures gzip, not loss.
				*/

			if($this->isGzip($archive_dir . $previous) !== $this->isGzip($current_path)) {
				return $none;
			}

			$previous_bytes = (int) @filesize($archive_dir . $previous);

			if($previous_bytes <= 0 || $bytes >= $previous_bytes) {
				return $none;
			}

				/*
					The comparison file's date is reported with the percentage
					because without it the warning lies by implication. The
					first real run of this tool compared a 2026 dump against a
					2022 one and said "40% smaller than the previous dump",
					which reads as overnight and was four years.
				*/

			return [
				'percent' => (int) round((($previous_bytes - $bytes) / $previous_bytes) * 100),
				'date'    => date('Y-m-d', (int) @filemtime($archive_dir . $previous)),
			];
		}

		public function listDumpedTables($path) {
			$gz = $this->isGzip($path);

			$handle = $gz ? @gzopen($path, 'rb') : @fopen($path, 'rb');

			if($handle === FALSE) {
				return [];
			}

			$tables = [];

			while(($line = $gz ? gzgets($handle) : fgets($handle)) !== FALSE) {
				if(strncasecmp(ltrim($line), 'CREATE TABLE', 12) !== 0) {
					continue;
				}

				if(preg_match('/CREATE TABLE\s+`([^`]+)`/i', $line, $matches)) {
					$tables[] = $matches[1];
				}
			}

			if($gz) {
				gzclose($handle);
			} else {
				fclose($handle);
			}

			return $tables;
		}

			/*
				Shells out to the mysql client rather than opening a second
				connection of its own. Root's ~/.my.cnf already names the
				cluster, and a verification tool that needs its own credentials
				is one more thing to update when the database moves -- which is
				exactly the trap /root/.mylogin.cnf laid on 6 September 2026.
			*/

		public function listLiveTables($database) {
			$command = 'mysql -N -e ' . escapeshellarg(
				'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ' .
				"'" . str_replace("'", '', $database) . "' ORDER BY TABLE_NAME"
			) . ' 2>/dev/null';

			$output = trim((string) shell_exec($command));

			if(!strlen($output)) {
				return NULL;
			}

			return array_values(array_filter(array_map('trim', explode("\n", $output))));
		}

			// Reporting

		public function printResults($results) {
			$rows = [];

			foreach($results as $result) {
				if($this->quiet && $result['status'] === 'OK') {
					continue;
				}

				$rows[] = [
					'Site'   => $result['domain'],
					'Status' => $result['status'],
					'Age'    => $result['age'],
					'Size'   => $result['bytes'] ? $this->humanBytes($result['bytes']) : '-',
					'Notes'  => implode('; ', $result['notes']),
				];
			}

			if(!count($rows)) {
				print("All " . count($results) . " sites pass.\n\n");

				return TRUE;
			}

			if($this->as_csv) {
				print(implode(',', array_keys($rows[0])) . "\n");

				foreach($rows as $row) {
					$cells = [];

					foreach($row as $cell) {
						$cells[] = '"' . str_replace('"', '""', (string) $cell) . '"';
					}

					print(implode(',', $cells) . "\n");
				}
			} else {
				print(arr2textTable($rows));
				print("\n");
			}

			$this->printSummary($results);

			return TRUE;
		}

		public function printSummary($results) {
			$counts = [];

			foreach($results as $result) {
				$key = ($result['status'] === 'OK') ? 'OK' : $result['status'];

				$counts[$key] = array_key_exists($key, $counts) ? $counts[$key] + 1 : 1;
			}

			print('SUMMARY  ' . count($results) . ' site(s):  ');

			$pieces = [];

			foreach($counts as $status => $count) {
				$pieces[] = $count . ' ' . $status;
			}

			print(implode(',  ', $pieces) . "\n\n");

			return TRUE;
		}

			/*
				Anything that is not plainly OK or a pure warning is a failure,
				and a failure is an exit code, because cron reads exit codes and
				nobody reads cron's output.
			*/

		public function exitCode($results) {
			$worst = 0;

			foreach($results as $result) {
				if($result['status'] === 'OK') {
					continue;
				}

				$only_warnings = TRUE;

				foreach(explode('+', $result['status']) as $flag) {
					if(!in_array($flag, ['NO_CHARSET', 'SHRUNK', 'NO_DB'], TRUE)) {
						$only_warnings = FALSE;
					}
				}

				$worst = max($worst, $only_warnings ? 1 : 2);
			}

			return $worst;
		}

			// Small helpers

		public function humanAge($hours) {
			if($hours <= 0) {
				return '-';
			}

			if($hours < 48) {
				return number_format($hours, 1) . 'h';
			}

			$days = $hours / 24;

			if($days < 90) {
				return number_format($days, 0) . 'd';
			}

			return number_format($days / 365, 1) . 'y';
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

<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');

	/*
		Four years of visitor statistics, on the disk that cannot afford them.

		/var/log/ggcms is 1.3 GB of plain text on a 25 GB root with about 8 GB
		free, and nothing has ever pruned it -- no logrotate rule mentions
		ggcms and nothing in the crontab touches it.  The oldest file is from
		August 2022.  wordweight.com alone holds 208 MB.

		The disk filling is what took this host down in 2024, and the database
		dumps already have purge_database_archives.php for exactly this reason.
		Statistics never got the equivalent.

		## What it does

		Groups every <domain>/stats/YYYY-Mon.txt and YYYY-Mon_memory.txt by
		month, tars each completed month to the mounted volume, proves the
		tarball can be read back, and only then removes the originals.

		## The three refusals

		**It never touches the current month.**  That file is open and being
		appended to by every request; archiving it would tar a snapshot of
		something still being written and then delete the live file out from
		under Apache.  The current month is computed with date('o-M'), the same
		expression UserTracking uses to name it, rather than something merely
		equivalent -- note the 'o', which is the ISO week-numbering year and
		differs from 'Y' for a few days each January.

		**It keeps recent months on disk.**  Statistics are read by looking at
		them, and a month that has to be untarred first will not be looked at.
		--keep is how many completed months stay where they are; three by
		default.

		**It verifies before it deletes.**  Every file is read back out of the
		tarball and compared by SHA-256 against the original, and a month with
		a single mismatch keeps all of its files.  A backup that has not been
		read is not a backup, and this tool's whole purpose is to be the only
		remaining copy.
	*/

	class StatsArchiver {
		use ByteDisplay;
		use CLIAccess;
		use DomainValidation;

			// Entry Point
			// -----------------------------------------------

		public function archiveStats() {
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$this->setHandle();
			$this->bannerMessage();

			if(!is_dir(GGCMS_LOG_DIR)) {
				print('No log directory at ' . GGCMS_LOG_DIR . "\n\n");

				return FALSE;
			}

			$months = $this->gatherMonths();

			if(!$months) {
				print('No statistics files found.' . "\n\n");

				return FALSE;
			}

			$eligible = $this->selectEligible(['months'=>$months]);

			$this->reportMonths(['months'=>$months, 'eligible'=>$eligible]);

			if(!$eligible) {
				print('Nothing to archive.' . "\n\n");

				return TRUE;
			}

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been written or removed.' . "\n");
				print('Add --apply to archive these months.' . "\n\n");

				return TRUE;
			}

			return $this->archiveMonths(['eligible'=>$eligible]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'domain'=>'',
				'month'=>'',
				'keep'=>3,
				'directory'=>'/mnt/nyc01/ggcms_stats_archive/',
				'apply'=>FALSE,
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--all') {
					$arguments['all'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 9) === '--domain=') {
					$arguments['domain'] = substr($argument, 9);
				} else if(substr($argument, 0, 8) === '--month=') {
					$arguments['month'] = substr($argument, 8);
				} else if(substr($argument, 0, 7) === '--keep=') {
					$arguments['keep'] = (int)substr($argument, 7);
				} else if(substr($argument, 0, 5) === '--to=') {
					$arguments['directory'] = rtrim(substr($argument, 5), '/') . '/';
				}
			}

			if($arguments['keep'] < 0) {
				$arguments['keep'] = 0;
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Statistics Archiver' . "\n\n");
			print('  archive_stats.php [--domain=NAME] [--keep=3] [--to=DIR] [--all] [--apply]' . "\n\n");
			print('  Tars completed months of <domain>/stats/ to the mounted volume,' . "\n");
			print('  verifies every file reads back, and only then removes the originals.' . "\n\n");
			print('  --keep    completed months left on disk; default 3' . "\n");
			print('  --domain  one site only; default every site' . "\n");
			print('  --month   one month only, e.g. 2023-Oct; picked from the eligible' . "\n");
			print('  --to      archive root; default /mnt/nyc01/ggcms_stats_archive/' . "\n\n");
			print('  The current month is never touched.  Dry by default.' . "\n\n");

			return TRUE;
		}

			// Finding the files
			// -----------------------------------------------

			/*
				Three filename shapes exist and no others: YYYY-Mon.txt,
				YYYY-Mon_memory.txt and YYYY-Mon_humans.txt, the last written
				by UserTracking::RecordHumanBeacon.  Anything else in a stats
				directory is left alone rather than guessed at.
			*/

		public function gatherMonths() {
			$months = [];

			foreach(scandir(GGCMS_LOG_DIR) as $domain) {
				if($domain === '.' || $domain === '..') {
					continue;
				}

				if($this->arguments['domain'] && $domain !== $this->arguments['domain']) {
					continue;
				}

				$stats_directory = GGCMS_LOG_DIR . $domain . '/stats/';

				if(!is_dir($stats_directory)) {
					continue;
				}

				foreach(scandir($stats_directory) as $file) {
					if($file === '.' || $file === '..') {
						continue;
					}

					if(!preg_match('/^([0-9]{4}-[A-Za-z]{3})(_memory|_humans)?\.txt$/', $file, $matches)) {
						continue;
					}

					$month = $matches[1];
					$path = $stats_directory . $file;

					if(!is_file($path)) {
						continue;
					}

					if(!array_key_exists($month, $months)) {
						$months[$month] = ['files'=>[], 'bytes'=>0];
					}

					$months[$month]['files'][] = [
						'path'=>$path,
						'relative'=>$domain . '/stats/' . $file,
						'size'=>filesize($path),
					];

					$months[$month]['bytes'] += filesize($path);
				}
			}

			uksort($months, function($first, $second) {
				return $this->monthKey(['month'=>$first]) <=> $this->monthKey(['month'=>$second]);
			});

			return $months;
		}

			/*
				Sortable, and comparable against the current month.  Month names
				are matched against date('M') output rather than a hand-written
				list, so this cannot disagree with the writer about
				abbreviations.
			*/

		public function monthKey($args) {
			$month = $args['month'];

			$pieces = explode('-', $month);

			if(count($pieces) !== 2) {
				return 0;
			}

			$year = (int)$pieces[0];
			$name = strtolower($pieces[1]);

			for($i = 1; $i <= 12; $i++) {
				if(strtolower(date('M', mktime(0, 0, 0, $i, 1, 2000))) === $name) {
					return ($year * 100) + $i;
				}
			}

			return 0;
		}

			/*
				The current month is named exactly as UserTracking names it.
				'o' is the ISO week-numbering year and is not 'Y': for the first
				days of some Januaries they differ, and a tool that assumed 'Y'
				would archive a file that was still being written.
			*/

			/*
				Anchored to the first of the month, because strtotime('-1 month')
				from the 31st lands in the month after the one intended -- there
				is no 31st of some months and PHP rolls forward rather than
				clamping. From the 1st it cannot.
			*/

		public function firstOfCurrentMonth() {
			return mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y'));
		}

		public function currentMonth() {
			return date('o-M');
		}

		public function selectEligible($args) {
			$months = $args['months'];

			$current = $this->currentMonth();
			$current_key = $this->monthKey(['month'=>$current]);

			$completed = [];

			foreach($months as $month=>$details) {
				$key = $this->monthKey(['month'=>$month]);

				if($key === 0 || $key >= $current_key) {
					continue;
				}

				$completed[$month] = $details;
			}

				/*
					--keep is a length of time, not a number of files.

					The first version of this kept the last N entries of the
					list, which is the same thing only when every month is
					present. This host has no statistics at all for 2024 or
					2025 -- the box was stuck -- so "the last three months on
					disk" meant 2023-Sep, 2023-Oct and 2026-Aug, and it
					carefully preserved a file from three years ago. 2023-Oct
					is 138.9 MB, the largest stats file there is.

					So the cutoff is calendar months back from the current one,
					and a gap in the record changes nothing.
				*/

			$keep = $this->arguments['keep'];

			if($keep <= 0) {
				return $completed;
			}

			$cutoff = $this->monthKey([
				'month'=>date('o-M', strtotime('-' . $keep . ' months', $this->firstOfCurrentMonth())),
			]);

			$eligible = [];

			foreach($completed as $month=>$details) {
				if($this->monthKey(['month'=>$month]) >= $cutoff) {
					continue;
				}

					/*
						Narrowed last, never first.  --month picks from what is
						already eligible, so naming the current month or one inside
						--keep selects nothing rather than overriding the rule that
						protects it.
					*/

				if($this->arguments['month'] && $month !== $this->arguments['month']) {
					continue;
				}

				$eligible[$month] = $details;
			}

			return $eligible;
		}

			// Reporting
			// -----------------------------------------------

		public function reportMonths($args) {
			$months = $args['months'];
			$eligible = $args['eligible'];

			$total = 0;
			$eligible_bytes = 0;

			foreach($months as $details) {
				$total += $details['bytes'];
			}

			foreach($eligible as $details) {
				$eligible_bytes += $details['bytes'];
			}

			print('Statistics on disk: ' . count($months) . ' months, ');
			print($this->formatBytes(['number'=>$total]) . "\n");
			print('Current month:      ' . $this->currentMonth() . ' (never touched)' . "\n");
			print('Keeping:            ' . $this->arguments['keep'] . ' completed months on disk' . "\n");
			print('To archive:         ' . count($eligible) . ' months, ');
			print($this->formatBytes(['number'=>$eligible_bytes]) . "\n\n");

			if(!$eligible) {
				return TRUE;
			}

			$limit = $this->arguments['all'] ? 0 : 20;
			$shown = ($limit > 0 && count($eligible) > $limit) ? array_slice($eligible, 0, $limit, TRUE) : $eligible;

			$table = [];

			foreach($shown as $month=>$details) {
				$table[] = [
					'Month'=>$month,
					'Files'=>count($details['files']),
					'Size'=>$this->formatBytes(['number'=>$details['bytes']]),
				];
			}

			print(arr2textTable($table));

			if(count($shown) < count($eligible)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($eligible) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

			// Archiving
			// -----------------------------------------------

		public function archiveMonths($args) {
			$directory = $this->arguments['directory'];

			if(!is_dir($directory) && !mkdir($directory, 0755, TRUE)) {
				print('Could not create ' . $directory . "\n\n");

				return FALSE;
			}

			$archived = 0;
			$reclaimed = 0;
			$failures = [];

			foreach($args['eligible'] as $month=>$details) {
				$outcome = $this->archiveOneMonth([
					'month'=>$month,
					'details'=>$details,
				]);

				if($outcome !== TRUE) {
					$failures[] = ['Month'=>$month, 'Why'=>$outcome];

					continue;
				}

				$archived++;
				$reclaimed += $details['bytes'];
			}

			print('Archived ' . $archived . ' months, reclaiming ');
			print($this->formatBytes(['number'=>$reclaimed]) . ' from the root disk.' . "\n\n");

			if($failures) {
				print(count($failures) . ' months were left alone:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");

				return FALSE;
			}

			return TRUE;
		}

			/*
				Returns TRUE, or a string saying why the month was left alone.

				Nothing is removed until every file in the month has been read
				back out of the tarball and matched by hash.  A month with one
				mismatch keeps all of its files -- a partial archive of a
				month's statistics is worse than none, because the gap would be
				invisible afterwards.
			*/

		public function archiveOneMonth($args) {
			$month = $args['month'];
			$details = $args['details'];

			$tarball = $this->arguments['directory'] . $month . '.tar.gz';

			if(is_file($tarball)) {
				return 'an archive for that month already exists';
			}

			$list_file = tempnam(sys_get_temp_dir(), 'ggcms_stats_');

			$relatives = [];

			foreach($details['files'] as $file) {
				$relatives[] = $file['relative'];
			}

			file_put_contents($list_file, implode("\n", $relatives) . "\n");

			$command = 'nice -n 19 tar czf ' . escapeshellarg($tarball);
			$command .= ' -C ' . escapeshellarg(GGCMS_LOG_DIR);
			$command .= ' -T ' . escapeshellarg($list_file) . ' 2>&1';

			$error = trim((string)shell_exec($command));

			unlink($list_file);

			if(!is_file($tarball) || filesize($tarball) === 0) {
				return 'tar produced nothing' . (strlen($error) ? (': ' . $error) : '');
			}

			$verified = $this->verifyTarball([
				'tarball'=>$tarball,
				'files'=>$details['files'],
			]);

			if($verified !== TRUE) {
				unlink($tarball);

				return $verified;
			}

			foreach($details['files'] as $file) {
				@unlink($file['path']);
			}

			return TRUE;
		}

			/*
				Read each member straight out of the archive and hash it.  tar
				tzf would only prove the names are listed, which is the half of
				the question that was never in doubt.
			*/

		public function verifyTarball($args) {
			$tarball = $args['tarball'];

			foreach($args['files'] as $file) {
				$command = 'nice -n 19 tar xzOf ' . escapeshellarg($tarball);
				$command .= ' ' . escapeshellarg($file['relative']) . ' 2>/dev/null';

				$contents = shell_exec($command);

				if($contents === NULL) {
					return 'could not read ' . $file['relative'] . ' back out';
				}

				if(hash('sha256', $contents) !== hash_file('sha256', $file['path'])) {
					return $file['relative'] . ' does not match its original';
				}
			}

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Statistics Archiver';
		}
	}

?>

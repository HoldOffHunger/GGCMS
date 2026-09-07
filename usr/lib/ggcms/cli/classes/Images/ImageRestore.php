<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	/*
		Putting the images back.

		The counterpart to backup_images.php, and the reason the compressor is
		allowed to write in place at all.

		It restores by comparison, not wholesale.  Every file in the manifest
		is hashed where it now sits and checked against the hash recorded when
		the backup was taken, and only the ones that differ are copied back.
		Restoring nine thousand identical files to fix the four that came out
		wrong would be slower, would touch every mtime on the tree, and would
		make the log useless for telling what had actually changed.

		Dry by default, like everything else here.  The dry run is the useful
		half most of the time: it answers "what has changed since the backup",
		which is the question actually being asked after a compression run.
	*/

	class ImageRestore {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;

			// Entry Point
			// -----------------------------------------------

		public function restoreImages() {
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$this->setHandle();
			$this->bannerMessage();

			if(!$this->setDomain()) {
				return $this->cancelAction([
					'message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.',
				]);
			}

			if($this->arguments['list']) {
				return $this->listBackups();
			}

			$label = $this->resolveLabel();

			if(!$label) {
				return FALSE;
			}

			$directory = $this->imageBackupDirectory(['label'=>$label]);

			$manifest = $this->readManifest(['directory'=>$directory]);

			if($manifest === FALSE) {
				return FALSE;
			}

			print('Backup:  ' . $directory . "\n");
			print('Entries: ' . count($manifest) . "\n\n");

			$comparison = $this->compareAgainstManifest([
				'manifest'=>$manifest,
				'directory'=>$directory,
			]);

			return $this->reportAndRestore(['comparison'=>$comparison]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'label'=>'',
				'file'=>'',
				'list'=>FALSE,
				'apply'=>FALSE,
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--list') {
					$arguments['list'] = TRUE;
				} else if($argument === '--all') {
					$arguments['all'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 8) === '--label=') {
					$arguments['label'] = substr($argument, 8);
				} else if(substr($argument, 0, 7) === '--file=') {
					$arguments['file'] = substr($argument, 7);
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Image Restore' . "\n\n");
			print('  restore_images.php DOMAIN [--label=NAME] [--file=PATH] [--list] [--apply]' . "\n\n");
			print('  --list   show the backups available for this domain' . "\n");
			print('  --label  which backup; defaults to the most recent' . "\n");
			print('  --file   restore one file, by its path relative to the image root' . "\n\n");
			print('  Compares the tree against the backup manifest by SHA-256 and copies' . "\n");
			print('  back only what differs.  Dry by default; --apply writes.' . "\n\n");

			return TRUE;
		}

			// Backups on disk
			// -----------------------------------------------

		public function availableBackups() {
			$root = $this->imageBackupDomainRoot();

			if(!is_dir($root)) {
				return [];
			}

			$labels = [];

			foreach(scandir($root) as $entry) {
				if($entry === '.' || $entry === '..') {
					continue;
				}

				if(!is_dir($root . $entry)) {
					continue;
				}

				$labels[] = $entry;
			}

			sort($labels);

			return $labels;
		}

		public function listBackups() {
			$labels = $this->availableBackups();

			if(!$labels) {
				print('No backups for ' . $this->domain . ' under ' . $this->imageBackupDomainRoot() . "\n\n");

				return FALSE;
			}

			$table = [];

			foreach($labels as $label) {
				$directory = $this->imageBackupDirectory(['label'=>$label]);
				$manifest = $directory . 'manifest.tsv';

				$entries = '-';
				$written = '-';

				if(is_file($manifest)) {
					$lines = $this->readManifest(['directory'=>$directory, 'quiet'=>TRUE]);
					$entries = ($lines === FALSE) ? 'unreadable' : count($lines);
					$written = date('Y-m-d H:i', filemtime($manifest));
				}

				$table[] = [
					'Label'=>$label,
					'Files'=>$entries,
					'Written'=>$written,
				];
			}

			print('Backups for ' . $this->domain . ':' . "\n\n");
			print(arr2textTable($table));
			print("\n");

			return TRUE;
		}

		public function resolveLabel() {
			if($this->arguments['label']) {
				$label = $this->arguments['label'];

				if(!is_dir($this->imageBackupDirectory(['label'=>$label]))) {
					print('No backup labelled ' . $label . ' for ' . $this->domain . '.' . "\n");
					print('Try --list.' . "\n\n");

					return FALSE;
				}

				return $label;
			}

			$labels = $this->availableBackups();

			if(!$labels) {
				print('No backups for ' . $this->domain . ' under ' . $this->imageBackupDomainRoot() . "\n\n");

				return FALSE;
			}

				/*
					Labels default to a sortable timestamp, so the last one
					alphabetically is the most recent.  A hand-given --label
					breaks that, which is why choosing one is reported rather
					than assumed silently.
				*/

			$label = end($labels);

			print('No --label given; using the most recent: ' . $label . "\n\n");

			return $label;
		}

			// The manifest
			// -----------------------------------------------

		public function readManifest($args) {
			$directory = $args['directory'];
			$quiet = array_key_exists('quiet', $args) ? $args['quiet'] : FALSE;

			$path = $directory . 'manifest.tsv';

			if(!is_file($path)) {
				if(!$quiet) {
					print('No manifest at ' . $path . "\n\n");
				}

				return FALSE;
			}

			$contents = file($path, FILE_IGNORE_NEW_LINES);

			if($contents === FALSE) {
				if(!$quiet) {
					print('Could not read ' . $path . "\n\n");
				}

				return FALSE;
			}

			$manifest = [];

			foreach($contents as $line) {
				if(strlen(trim($line)) === 0 || substr($line, 0, 1) === '#') {
					continue;
				}

				$pieces = explode("\t", $line);

				if(count($pieces) < 4) {
					continue;
				}

				$manifest[] = [
					'relative'=>$pieces[0],
					'size'=>(int)$pieces[1],
					'mtime'=>(int)$pieces[2],
					'sha256'=>$pieces[3],
				];
			}

			return $manifest;
		}

			// Comparison
			// -----------------------------------------------

		public function compareAgainstManifest($args) {
			$manifest = $args['manifest'];
			$directory = $args['directory'];

			$wanted_file = $this->arguments['file'];

			$differing = [];
			$absent = [];
			$unbacked = [];
			$matching = 0;

			foreach($manifest as $entry) {
				if($wanted_file && $entry['relative'] !== $wanted_file) {
					continue;
				}

				$live = $this->imageDirectory() . $entry['relative'];
				$backup = $directory . $entry['relative'];

				if(!is_file($backup)) {
					$unbacked[] = $entry;

					continue;
				}

				if(!is_file($live)) {
					$absent[] = $entry;

					continue;
				}

					/*
						Size first, hash only when the sizes agree.  A
						re-encoded file is a different size in almost every
						case, so this skips reading the bytes of thousands of
						files to learn what one stat call already said.
					*/

				if(filesize($live) !== $entry['size']) {
					$differing[] = $entry;

					continue;
				}

				if(hash_file('sha256', $live) !== $entry['sha256']) {
					$differing[] = $entry;

					continue;
				}

				$matching++;
			}

			return [
				'differing'=>$differing,
				'absent'=>$absent,
				'unbacked'=>$unbacked,
				'matching'=>$matching,
				'directory'=>$directory,
			];
		}

			// Reporting and restoring
			// -----------------------------------------------

		public function reportAndRestore($args) {
			$comparison = $args['comparison'];

			$differing = $comparison['differing'];
			$absent = $comparison['absent'];
			$unbacked = $comparison['unbacked'];

			if($unbacked) {
				print('Warning: ' . count($unbacked) . ' manifest entries have no file in the backup.' . "\n");
				print('That backup is incomplete and cannot fully restore.' . "\n\n");
			}

			$to_restore = array_merge($differing, $absent);

			if(!$to_restore) {
				print('Nothing to restore.  ' . $comparison['matching'] . ' files match the backup.' . "\n\n");

				return TRUE;
			}

			$restore_bytes = 0;

			foreach($to_restore as $entry) {
				$restore_bytes += $entry['size'];
			}

			print(count($differing) . ' files differ from the backup, ');
			print(count($absent) . ' are missing from disk, ');
			print($comparison['matching'] . ' match.' . "\n");
			print('Restoring would write ' . $this->formatBytes(['number'=>$restore_bytes]) . '.' . "\n\n");

			$this->printRestoreTable(['entries'=>$to_restore]);

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been written.' . "\n");
				print('Add --apply to restore these files.' . "\n\n");

				return TRUE;
			}

			return $this->restoreFiles([
				'entries'=>$to_restore,
				'directory'=>$comparison['directory'],
			]);
		}

		public function printRestoreTable($args) {
			$entries = $args['entries'];

			$limit = $this->arguments['all'] ? 0 : 25;
			$shown = ($limit > 0 && count($entries) > $limit) ? array_slice($entries, 0, $limit) : $entries;

			$table = [];

			foreach($shown as $entry) {
				$live = $this->imageDirectory() . $entry['relative'];

				$table[] = [
					'Backup size'=>$this->formatBytes(['number'=>$entry['size']]),
					'Now'=>is_file($live) ? $this->formatBytes(['number'=>filesize($live)]) : 'absent',
					'File'=>$entry['relative'],
				];
			}

			print(arr2textTable($table));

			if(count($shown) < count($entries)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($entries) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

		public function restoreFiles($args) {
			$entries = $args['entries'];
			$directory = $args['directory'];

			$restored = 0;
			$failures = [];

			foreach($entries as $entry) {
				$backup = $directory . $entry['relative'];
				$live = $this->imageDirectory() . $entry['relative'];

				$live_directory = dirname($live);

				if(!is_dir($live_directory) && !mkdir($live_directory, 0755, TRUE)) {
					$failures[] = ['File'=>$entry['relative'], 'Why'=>'could not create directory'];

					continue;
				}

				if(!copy($backup, $live)) {
					$failures[] = ['File'=>$entry['relative'], 'Why'=>'copy failed'];

					continue;
				}

					/*
						Verified after writing, not before.  The point of the
						manifest is that a restore can be proved rather than
						assumed, and a copy that silently truncated would
						otherwise be discovered by a reader of the website.
					*/

				if(hash_file('sha256', $live) !== $entry['sha256']) {
					$failures[] = ['File'=>$entry['relative'], 'Why'=>'restored file does not match its hash'];

					continue;
				}

				touch($live, $entry['mtime']);

				$restored++;
			}

			print('Restored ' . $restored . ' of ' . count($entries) . ' files.' . "\n\n");

			if($failures) {
				print(count($failures) . ' failed:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");

				return FALSE;
			}

			print('The page cache still holds pages built against the old files.' . "\n");
			print('Flush it for this domain if the images were being served.' . "\n\n");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Restore';
		}

		public function confirmDomainText() {
			return 'Restoring images for: ';
		}
	}

?>

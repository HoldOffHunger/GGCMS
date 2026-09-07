<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	/*
		Copying the image tree somewhere it can be got back from.

		This exists because the compressor rewrites files in place.  Nothing
		else on this host keeps a copy of them -- the database backups hold the
		Image rows, which are the filenames and the dimensions, and not one
		byte of any actual image.  So before anything re-encodes an archival
		scan of a woodcut, there has to be a scan of a woodcut somewhere else.

		## Shape

		A backup is a directory of files, not a tarball.  A tarball is smaller
		and tidier and it is the wrong choice here: restoring one file out of a
		5 GB tar means reading most of the tar, and the overwhelmingly likely
		restore is one file that came out wrong, not all nine thousand.  Files
		mirroring the original layout restore individually in no time and can
		be compared in place with ordinary tools.

		Every backup carries a manifest -- relative path, size, mtime and a
		SHA-256 -- written as tab-separated text.  The hash is computed from
		the bytes as they are copied, so it costs nothing beyond a read that
		was happening anyway, and it is what lets restore say "these four files
		differ from the backup" rather than "these four files exist".

		## Where

		/mnt/nyc01, always.  See the note in ImageFiles::imageBackupRoot --
		writing 5 GB to the root disk would fill the volume that serves all
		seventeen sites.
	*/

	class ImageBackup {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;

			// Entry Point
			// -----------------------------------------------

		public function backupImages() {
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

			if(!is_dir($this->imageDirectory())) {
				print('No image directory at ' . $this->imageDirectory() . "\n\n");

				return FALSE;
			}

			$files = $this->walkImageFiles([
				'minimum_size'=>$this->arguments['minimum_size'],
				'limit'=>$this->arguments['limit'],
			]);

			if(!$files) {
				print('Nothing matched.  Try a smaller --min-size.' . "\n\n");

				return FALSE;
			}

			$total_bytes = 0;

			foreach($files as $file) {
				$total_bytes += $file['size'];
			}

			$label = $this->arguments['label'];
			$destination = $this->imageBackupDirectory(['label'=>$label]);

			print('Source:      ' . $this->imageDirectory() . "\n");
			print('Destination: ' . $destination . "\n");
			print('Files:       ' . count($files) . "\n");
			print('Size:        ' . $this->formatBytes(['number'=>$total_bytes]) . "\n\n");

			if(!$this->checkFreeSpace(['needed'=>$total_bytes])) {
				return FALSE;
			}

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been copied.' . "\n");
				print('Add --apply to write this backup.' . "\n\n");

				return TRUE;
			}

			return $this->copyFiles([
				'files'=>$files,
				'destination'=>$destination,
				'total_bytes'=>$total_bytes,
			]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'label'=>date('Y-m-d_His'),
				'minimum_size'=>0,
				'limit'=>0,
				'apply'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 8) === '--label=') {
					$arguments['label'] = $this->sanitiseLabel([
						'label'=>substr($argument, 8),
					]);
				} else if(substr($argument, 0, 11) === '--min-size=') {
					$arguments['minimum_size'] = $this->parseSize([
						'value'=>substr($argument, 11),
					]);
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				}
			}

			return $this->arguments = $arguments;
		}

			/*
				The label becomes a directory name and arrives from a shell
				argument, so it is reduced to characters that cannot walk out
				of the backup root.  An empty result falls back to the
				timestamp rather than to the root itself.
			*/

		public function sanitiseLabel($args) {
			$label = preg_replace('/[^A-Za-z0-9_.-]/', '', $args['label']);

			$label = ltrim($label, '.');

			if(strlen($label) === 0) {
				return date('Y-m-d_His');
			}

			return $label;
		}

		public function parseSize($args) {
			$value = trim($args['value']);

			if(strlen($value) === 0) {
				return 0;
			}

			$suffix = strtoupper(substr($value, -1));

			$multipliers = [
				'K'=>1024,
				'M'=>1048576,
				'G'=>1073741824,
			];

			if(array_key_exists($suffix, $multipliers)) {
				return (int)((float)substr($value, 0, -1) * $multipliers[$suffix]);
			}

			return (int)$value;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Image Backup' . "\n\n");
			print('  backup_images.php DOMAIN [--label=NAME] [--min-size=1M] [--limit=N] [--apply]' . "\n\n");
			print('  Copies the image tree to ' . $this->imageBackupRoot() . '<domain>/<label>/' . "\n");
			print('  alongside a manifest of sizes and SHA-256 hashes.' . "\n\n");
			print('  --label     names the backup; defaults to a timestamp' . "\n");
			print('  --min-size  back up only files at or above this size' . "\n");
			print('  --limit     back up only the N largest matching files' . "\n\n");
			print('  Dry by default.  Prints what it would copy; --apply writes it.' . "\n\n");

			return TRUE;
		}

			// Free space
			// -----------------------------------------------

			/*
				Refuse rather than half-fill the volume.  A backup that stops
				two thirds of the way through is worse than no backup, because
				the compressor's check for "is there a backup" would find the
				directory and believe it.
			*/

		public function checkFreeSpace($args) {
			$needed = $args['needed'];

			$root = $this->imageBackupRoot();

			$existing = is_dir($root) ? $root : dirname(rtrim($root, '/'));

			if(!is_dir($existing)) {
				print('Backup volume ' . $existing . ' does not exist.' . "\n\n");

				return FALSE;
			}

			$free = disk_free_space($existing);

			if($free === FALSE) {
				print('Could not read free space on ' . $existing . '.' . "\n\n");

				return FALSE;
			}

				/*
					A tenth over, so a backup does not finish by leaving the
					volume with nothing in it for the caches that share it.
				*/

			$required = $needed * 1.1;

			print('Free on volume: ' . $this->formatBytes(['number'=>$free]));
			print(' -- need about ' . $this->formatBytes(['number'=>$required]) . "\n\n");

			if($free < $required) {
				print('Not enough room.  Refusing.' . "\n\n");

				return FALSE;
			}

			return TRUE;
		}

			// Copying
			// -----------------------------------------------

		public function copyFiles($args) {
			$files = $args['files'];
			$destination = $args['destination'];
			$total_bytes = $args['total_bytes'];

			if(is_dir($destination)) {
				print('A backup labelled ' . $this->arguments['label'] . ' already exists.' . "\n");
				print('Choose another --label.' . "\n\n");

				return FALSE;
			}

			if(!mkdir($destination, 0755, TRUE)) {
				print('Could not create ' . $destination . "\n\n");

				return FALSE;
			}

			$manifest_lines = [];
			$copied = 0;
			$copied_bytes = 0;
			$failures = [];

			foreach($files as $file) {
				$target = $destination . $file['relative'];
				$target_directory = dirname($target);

				if(!is_dir($target_directory) && !mkdir($target_directory, 0755, TRUE)) {
					$failures[] = ['File'=>$file['relative'], 'Why'=>'could not create directory'];

					continue;
				}

				if(!copy($file['path'], $target)) {
					$failures[] = ['File'=>$file['relative'], 'Why'=>'copy failed'];

					continue;
				}

				$hash = hash_file('sha256', $target);

				if($hash === FALSE) {
					$failures[] = ['File'=>$file['relative'], 'Why'=>'could not hash copy'];

					continue;
				}

					/*
						Size is read back from the copy rather than reused from
						the walk.  A short write returns true from copy() on
						some filesystems, and comparing the copy against what
						was intended is the only way that shows up here rather
						than during a restore.
					*/

				$written = filesize($target);

				if($written !== $file['size']) {
					$failures[] = [
						'File'=>$file['relative'],
						'Why'=>'size mismatch: ' . $written . ' vs ' . $file['size'],
					];

					continue;
				}

				$manifest_lines[] = implode("\t", [
					$file['relative'],
					$file['size'],
					filemtime($file['path']),
					$hash,
				]);

				$copied++;
				$copied_bytes += $file['size'];
			}

			$this->writeManifest([
				'destination'=>$destination,
				'lines'=>$manifest_lines,
			]);

			print('Copied ' . $copied . ' of ' . count($files) . ' files, ');
			print($this->formatBytes(['number'=>$copied_bytes]) . ' of ');
			print($this->formatBytes(['number'=>$total_bytes]) . '.' . "\n\n");

			if($failures) {
				print(count($failures) . ' failed:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");

				return FALSE;
			}

			print('Backup label: ' . $this->arguments['label'] . "\n");
			print('Restore with: restore_images.php ' . $this->domain . ' --label=' . $this->arguments['label'] . "\n\n");

			return TRUE;
		}

		public function writeManifest($args) {
			$destination = $args['destination'];
			$lines = $args['lines'];

			$manifest = $destination . 'manifest.tsv';

			$contents = '# relative_path' . "\t" . 'size' . "\t" . 'mtime' . "\t" . 'sha256' . "\n";
			$contents .= '# domain: ' . $this->domain . "\n";
			$contents .= '# written: ' . date('c') . "\n";
			$contents .= implode("\n", $lines) . "\n";

			return file_put_contents($manifest, $contents);
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Backup';
		}

		public function confirmDomainText() {
			return 'Backing up images for: ';
		}
	}

?>

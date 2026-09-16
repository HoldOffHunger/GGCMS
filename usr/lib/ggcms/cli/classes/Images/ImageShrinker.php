<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	ggreq('traits/ReverseDNSNotation.php');

	/*
		Scaling a site's stored images down to a ceiling, and telling the
		database what it did.

		Written for masereelgroup.com, whose fourteen Image rows carried
		originals up to 12,374 pixels wide -- 46.7 MB of a 54.1 MB tree, for a
		site that links every scan to archive.org anyway.  The website keeps a
		picture a reader can see; the archival copy lives with the archive.

		## It is the one image tool that resizes

		ImageFiles says why the others must not: PixelWidth and its siblings
		are printed into the img tag, so a file resized behind the row's back
		renders at the old size.  This tool resizes on purpose, so it owns the
		other half of the job.  Every file it replaces has its variant's two
		dimension columns rewritten in the same pass, from the file as written
		-- measured, not computed, because ImageMagick's rounding is its own.

		## It refuses to run without a backup

		The same refusal as compress_images.php, for a stronger reason: a
		re-encode loses a little, and a downscale from 12,000 pixels to 1,000
		loses almost everything.  The backup is the only full-resolution copy
		on the host.

		## It only touches files an Image row names

		Orphans are left where they are; check_orphan_images.php is the tool
		for those.  A file whose row cannot be found has no dimensions to
		correct, and shrinking it would be the silent kind of change.

		## Rows before pages

		The UPDATE goes straight to the database, past the ORM, so the row
		cache is invalidated here for the same reason ImageFilenameRepairer
		does it: flushing pages first only re-renders them from stale rows.
	*/

	class ImageShrinker {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ReverseDNSNotation;

			// Entry Point
			// -----------------------------------------------

		public function shrinkImages() {
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

			if(!$this->requireImageMagick()) {
				return FALSE;
			}

			if($this->arguments['apply'] && !$this->confirmBackupExists()) {
				return FALSE;
			}

			$this->setGlobals();
			$this->setMySQLArgs();

			$candidates = $this->gatherCandidates([
				'rows'=>$this->loadImageRows(),
			]);

			if(!$candidates) {
				print('Every named image already fits within ' . $this->arguments['max'] . 'x' . $this->arguments['max'] . '.' . "\n\n");

				return TRUE;
			}

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing will be written.' . "\n\n");
			}

			return $this->processCandidates(['candidates'=>$candidates]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'max'=>1000,
				'quality'=>85,
				'limit'=>0,
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
				} else if(substr($argument, 0, 6) === '--max=') {
					$arguments['max'] = (int)substr($argument, 6);
				} else if(substr($argument, 0, 10) === '--quality=') {
					$arguments['quality'] = (int)substr($argument, 10);
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				}
			}

			if($arguments['max'] < 100) {
				$arguments['max'] = 100;
			}

			if($arguments['quality'] < 1 || $arguments['quality'] > 100) {
				$arguments['quality'] = 85;
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Image Shrinker' . "\n\n");
			print('  shrink_images.php DOMAIN [--max=1000] [--quality=85] [--limit=N] [--all] [--apply]' . "\n\n");
			print('  Scales every image an Image row names down to fit within --max by --max,' . "\n");
			print('  and rewrites that variant\'s stored dimensions to match the new file.' . "\n\n");
			print('  Dry by default.  --apply requires a backup to exist.  Orphans are ignored.' . "\n\n");

			return TRUE;
		}

			// Refusing without a backup
			// -----------------------------------------------

		public function confirmBackupExists() {
			$root = $this->imageBackupDomainRoot();

			$labels = [];

			if(is_dir($root)) {
				foreach(scandir($root) as $entry) {
					if($entry !== '.' && $entry !== '..' && is_dir($root . $entry)) {
						$labels[] = $entry;
					}
				}
			}

			if(!$labels) {
				print('No backup exists for ' . $this->domain . '.  Take one first:' . "\n\n");
				print('  backup_images.php ' . $this->domain . ' --min-size=0 --apply' . "\n\n");

				return FALSE;
			}

			rsort($labels);

			$this->backup_labels = $labels;

			print('Backups found: ' . implode(', ', $labels) . "\n\n");

			return TRUE;
		}

			/*
				A backup directory existing is not the same as this file being
				in it.  On 16 September 2026 a fresh backup of masereelgroup
				was refused for want of space, and the run went ahead on the
				strength of an older one it never looked inside -- which did
				hold the originals, by luck rather than by check.  So each file
				is looked for, newest backup first, and a file with no copy
				holding its current dimensions is not touched.
			*/

		public function backupCopyOf($args) {
			$candidate = $args['candidate'];

			foreach($this->backup_labels as $label) {
				$copy = $this->imageBackupDirectory(['label'=>$label]) . $candidate['relative'];

				if(!is_file($copy)) {
					continue;
				}

				$image = $this->identifyImage(['path'=>$copy]);

				if($image && $image['width'] === $candidate['image']['width'] && $image['height'] === $candidate['image']['height']) {
					return $copy;
				}
			}

			return FALSE;
		}

			// Reading
			// -----------------------------------------------

		public function loadImageRows() {
			$query = 'SELECT id, Entryid, FileName, StandardFileName, IconFileName, FileDirectory, ';
			$query .= 'PixelWidth, PixelHeight, StandardPixelWidth, StandardPixelHeight, ';
			$query .= 'IconPixelWidth, IconPixelHeight ';
			$query .= 'FROM Image ORDER BY id';

			return $this->runQuery(['query'=>$query]);
		}

		public function dimensionFields() {
			return [
				'original'=>['PixelWidth', 'PixelHeight'],
				'standard'=>['StandardPixelWidth', 'StandardPixelHeight'],
				'icon'=>['IconPixelWidth', 'IconPixelHeight'],
			];
		}

			// Candidates
			// -----------------------------------------------

		public function gatherCandidates($args) {
			$max = $this->arguments['max'];
			$candidates = [];
			$missing = 0;

			foreach($args['rows'] as $row) {
				foreach($this->variantNames() as $variant) {
					$path = $this->imagePathForVariant(['row'=>$row, 'variant'=>$variant]);

					if(strlen($path) === 0) {
						continue;
					}

					if(!is_file($path)) {
						$missing++;

						continue;
					}

					$image = $this->identifyImage(['path'=>$path]);

					if(!$image || ($image['width'] <= $max && $image['height'] <= $max)) {
						continue;
					}

					$candidates[] = [
						'row'=>$row,
						'variant'=>$variant,
						'path'=>$path,
						'relative'=>$this->relativeImagePath(['path'=>$path]),
						'image'=>$image,
						'size'=>filesize($path),
					];

					if($this->arguments['limit'] && count($candidates) >= $this->arguments['limit']) {
						break 2;
					}
				}
			}

			if($missing) {
				print($missing . ' named files are not on disk; scan_images.php --check=missing lists them.' . "\n\n");
			}

			return $candidates;
		}

			// Processing
			// -----------------------------------------------

		public function processCandidates($args) {
			$rows = [];
			$refused = [];
			$entry_ids = [];
			$before_bytes = 0;
			$after_bytes = 0;

			foreach($args['candidates'] as $candidate) {
				$before_bytes += $candidate['size'];

				if(!$this->arguments['apply']) {
					$rows[] = $this->resultRow(['candidate'=>$candidate, 'after'=>NULL, 'note'=>'would shrink']);

					continue;
				}

				$installed = $this->installShrunk(['candidate'=>$candidate]);

				if(!is_array($installed)) {
					$after_bytes += $candidate['size'];

					$refused[] = [
						'File'=>$candidate['relative'],
						'Why'=>$installed,
					];

					continue;
				}

				$after_bytes += $installed['size'];
				$entry_ids[(int)$candidate['row']['Entryid']] = TRUE;

				$rows[] = $this->resultRow(['candidate'=>$candidate, 'after'=>$installed, 'note'=>'shrunk']);
			}

			return $this->report([
				'rows'=>$rows,
				'refused'=>$refused,
				'entry_ids'=>array_keys($entry_ids),
				'before_bytes'=>$before_bytes,
				'after_bytes'=>$after_bytes,
			]);
		}

		public function resultRow($args) {
			$candidate = $args['candidate'];
			$after = $args['after'];
			$image = $candidate['image'];

			return [
				'Image'=>$candidate['row']['id'],
				'Entry'=>$candidate['row']['Entryid'],
				'Variant'=>$candidate['variant'],
				'Before'=>$image['width'] . 'x' . $image['height'],
				'After'=>$after ? $after['width'] . 'x' . $after['height'] : '-',
				'Bytes'=>$this->formatBytes(['number'=>$candidate['size']]) . ($after ? ' -> ' . $this->formatBytes(['number'=>$after['size']]) : ''),
				'State'=>$args['note'],
				'File'=>$candidate['relative'],
			];
		}

			// Installing
			// -----------------------------------------------

			/*
				Written beside the original and renamed over it, as the
				compressor does, because rename is atomic only within one
				filesystem.  The row is updated only after the file is in
				place: a row describing a file that failed to install would be
				the lie this tool exists to prevent.

				Returns the new width, height and size, or a string saying why
				nothing was installed.
			*/

		public function installShrunk($args) {
			$candidate = $args['candidate'];
			$path = $candidate['path'];
			$max = $this->arguments['max'];

			if(!$this->backupCopyOf(['candidate'=>$candidate])) {
				return 'no backup holds this file at ' . $candidate['image']['width'] . 'x' . $candidate['image']['height'];
			}

			$temporary = $path . '.ggcms_shrink';

				/*
					The size hint lets libjpeg decode at a fraction of full
					resolution.  Without it a 11,092 by 12,626 scan is decoded
					whole, 140 million pixels, and on this host's ImageMagick
					policy that is "cache resources exhausted" -- three of
					masereelgroup's fourteen failed that way.  Twice the ceiling
					keeps the final resize a genuine downscale.
				*/

			$command = $this->imageMagickCommand(['tool'=>'convert']);
			$command .= ' -define ' . escapeshellarg('jpeg:size=' . (2 * $max) . 'x' . (2 * $max));
			$command .= ' ' . escapeshellarg($path . '[0]');
			$command .= ' -auto-orient -resize ' . escapeshellarg($max . 'x' . $max . '>');
			$command .= ' -strip -quality ' . (int)$this->arguments['quality'];
			$command .= ' ' . escapeshellarg($temporary) . ' 2>&1';

			$error = trim((string)shell_exec($command));

			if(!is_file($temporary) || filesize($temporary) === 0) {
				if(is_file($temporary)) {
					unlink($temporary);
				}

				return 'encoder produced nothing' . (strlen($error) ? ': ' . $error : '');
			}

			$check = $this->identifyImage(['path'=>$temporary]);

			if(!$check) {
				unlink($temporary);

				return 'result was not readable';
			}

			if($check['width'] > $max || $check['height'] > $max) {
				unlink($temporary);

				return 'result is still ' . $check['width'] . 'x' . $check['height'];
			}

				/*
					A shrink keeps the shape.  More than a percent of drift in
					the aspect ratio means the source was not what identify
					said it was -- an EXIF rotation, say -- and the stored
					dimensions would describe the wrong picture.
				*/

			$before_ratio = $candidate['image']['width'] / $candidate['image']['height'];
			$after_ratio = $check['width'] / $check['height'];

			if(abs($before_ratio - $after_ratio) / $before_ratio > 0.01) {
				unlink($temporary);

				return 'aspect ratio changed: ' . $check['width'] . 'x' . $check['height'];
			}

			$permissions = fileperms($path);

			if(!rename($temporary, $path)) {
				unlink($temporary);

				return 'could not replace the original';
			}

			if($permissions !== FALSE) {
				chmod($path, $permissions & 0777);
			}

			$fields = $this->dimensionFields()[$candidate['variant']];

			$query = 'UPDATE Image SET ' . $fields[0] . ' = ?, ' . $fields[1] . ' = ? WHERE id = ?';

			$statement = $this->db_link->prepare($query);

			$width = $check['width'];
			$height = $check['height'];
			$id = (int)$candidate['row']['id'];

			if(!$statement || !$statement->bind_param('iii', $width, $height, $id) || !$statement->execute()) {
				return 'FILE SHRUNK BUT ROW NOT UPDATED -- set ' . $fields[0] . '=' . $width . ', ' . $fields[1] . '=' . $height . ' on Image ' . $id . ' by hand';
			}

			$statement->close();

			clearstatcache(TRUE, $path);

			return [
				'width'=>$width,
				'height'=>$height,
				'size'=>filesize($path),
			];
		}

			// The row cache
			// -----------------------------------------------

		public function invalidateRowCache($args) {
			require_once(GGCMS_DIR . 'classes/Database/DBFileCache.php');

			$cache = new DBFileCache(['handler'=>NULL]);

			$directory = $cache->DBFileCacheLocation();
			$directory .= '/' . $this->ReverseDomainName(['domain'=>strtolower($this->domain)]);
			$directory .= '/ggcms_EntryChildRecords/Image/';

			if(!is_dir($directory)) {
				print('No cached Image rows to invalidate.' . "\n\n");

				return TRUE;
			}

			$deleted = 0;

			foreach($args['entry_ids'] as $entry_id) {
				$file = $directory . $entry_id;

				if(is_file($file) && @unlink($file)) {
					$deleted++;
				}
			}

			print('Invalidated ' . $deleted . ' cached Image rows across ' . count($args['entry_ids']) . ' entries.' . "\n\n");

			return TRUE;
		}

			// Reporting
			// -----------------------------------------------

		public function report($args) {
			$rows = $args['rows'];
			$refused = $args['refused'];

			if($rows) {
				$limit = $this->arguments['all'] ? 0 : 25;
				$shown = ($limit > 0 && count($rows) > $limit) ? array_slice($rows, 0, $limit) : $rows;

				print(arr2textTable($shown));

				if(count($shown) < count($rows)) {
					print("\n" . 'Showing ' . count($shown) . ' of ' . count($rows) . '; --all for the rest.' . "\n");
				}

				print("\n");
			}

			if($refused) {
				print(count($refused) . ' files were not shrunk:' . "\n\n");
				print(arr2textTable($refused));
				print("\n");
			}

			if(!$this->arguments['apply']) {
				print(count($rows) . ' files over ' . $this->arguments['max'] . 'x' . $this->arguments['max'] . ', holding ');
				print($this->formatBytes(['number'=>$args['before_bytes']]) . '.  Add --apply to shrink them.' . "\n\n");

				return TRUE;
			}

			print('Shrank ' . count($rows) . ' files: ');
			print($this->formatBytes(['number'=>$args['before_bytes']]) . ' becomes ');
			print($this->formatBytes(['number'=>$args['after_bytes']]) . '.' . "\n\n");

			if($args['entry_ids']) {
				$this->invalidateRowCache(['entry_ids'=>$args['entry_ids']]);

				print('Now flush the page cache for this domain -- in that order.' . "\n\n");
			}

			return !$refused;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Shrinker';
		}

		public function confirmDomainText() {
			return 'Shrinking images for: ';
		}
	}

?>

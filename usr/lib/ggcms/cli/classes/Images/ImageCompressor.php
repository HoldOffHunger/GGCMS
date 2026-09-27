<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');
	clireq('traits/ImageQualitySearch.php');

	/*
		Re-encoding the image tree in place.

		The only tool here that changes what visitors are served, and the only
		one that can destroy anything.  It is built around three refusals.

		## It refuses to run without a backup

		--apply does nothing unless a backup exists for the domain.  The images
		are on no other disk -- the database backups hold the Image rows, which
		are filenames and dimensions, and not one byte of any picture -- so a
		bad run with no backup is a permanent loss of somebody's archival
		scans.  backup_images.php exists to satisfy this, and the message names
		the command.

		## It refuses to change dimensions

		PixelWidth and its siblings are printed straight into the img tag, so a
		resize makes every stored dimension a lie and the page keeps rendering
		at the old size against a smaller file.  Nothing here passes -resize,
		but intent is not a guarantee: the written file is measured before it
		replaces the original, and a result whose dimensions moved is thrown
		away and reported.

		## It refuses to compress the same file twice

		This is the one that would have gone unnoticed.  A second run over an
		already-compressed tree sees a quality-80 file, searches below it,
		finds quality 72 acceptable and re-encodes -- and JPEG generation loss
		is cumulative and invisible one step at a time.  So every file this
		tool writes is recorded in a ledger with the hash of what was written,
		and a file whose hash still matches its ledger entry is skipped.  Edit
		or restore the file and the hash stops matching, and it becomes
		eligible again, which is the correct behaviour in both directions.
	*/

	class ImageCompressor {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ImageQualitySearch;
		
		public $ledger;
		public $arguments;
		public $last_encoder_error;

			// Entry Point
			// -----------------------------------------------

		public function compressImages() {
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

			if($this->arguments['apply'] && !$this->confirmBackupExists()) {
				return FALSE;
			}

			$this->ledger = $this->readLedger();

			$candidates = $this->gatherCandidates();

			if(!$candidates) {
				print('Nothing to do.' . "\n\n");

				return FALSE;
			}

			print('Target: ' . number_format($this->arguments['target'], 1) . ' dB PSNR');
			print(', quality floor ' . $this->arguments['floor'] . "\n");
			print('Files:  ' . count($candidates) . "\n\n");

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing will be written.' . "\n\n");
			}

			return $this->processCandidates(['candidates'=>$candidates]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'minimum_size'=>1048576,
				'limit'=>0,
				'target'=>$this->qualitySearchDefaultTarget(),
				'floor'=>$this->qualitySearchFloor(),
				'apply'=>FALSE,
				'force'=>FALSE,
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--force') {
					$arguments['force'] = TRUE;
				} else if($argument === '--all') {
					$arguments['all'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 11) === '--min-size=') {
					$arguments['minimum_size'] = $this->parseSize([
						'value'=>substr($argument, 11),
					]);
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				} else if(substr($argument, 0, 9) === '--target=') {
					$arguments['target'] = (float)substr($argument, 9);
				} else if(substr($argument, 0, 8) === '--floor=') {
					$arguments['floor'] = (int)substr($argument, 8);
				}
			}

			return $this->arguments = $arguments;
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
			print('GGCMS - Image Compressor' . "\n\n");
			print('  compress_images.php DOMAIN [--min-size=1M] [--limit=N] [--target=41.0]' . "\n");
			print('                             [--floor=55] [--force] [--apply] [--all]' . "\n\n");
			print('  Re-encodes JPEGs in place at the lowest quality clearing --target.' . "\n");
			print('  Dry by default.  --apply requires a backup to exist.' . "\n\n");
			print('  --force  re-encode files this tool has already written' . "\n");
			print('           (JPEG loss is cumulative; do not use this routinely)' . "\n\n");

			return TRUE;
		}

			// Refusing without a backup
			// -----------------------------------------------

		public function confirmBackupExists() {
			$root = $this->imageBackupDomainRoot();

			if(!is_dir($root)) {
				print('No backup exists for ' . $this->domain . '.' . "\n\n");
				print('Take one first:' . "\n\n");
				print('  backup_images.php ' . $this->domain . ' --min-size=');
				print($this->arguments['minimum_size'] . ' --apply' . "\n\n");

				return FALSE;
			}

			$labels = [];

			foreach(scandir($root) as $entry) {
				if($entry === '.' || $entry === '..' || !is_dir($root . $entry)) {
					continue;
				}

				$labels[] = $entry;
			}

			if(!$labels) {
				print('Backup directory ' . $root . ' exists but holds no backups.' . "\n\n");

				return FALSE;
			}

			sort($labels);

			print('Backup found: ' . end($labels) . "\n\n");

			return TRUE;
		}

			// The ledger
			// -----------------------------------------------

		public function ledgerPath() {
			return $this->imageBackupDomainRoot() . 'compressed.tsv';
		}

		public function readLedger() {
			$path = $this->ledgerPath();

			if(!is_file($path)) {
				return [];
			}

			$contents = file($path, FILE_IGNORE_NEW_LINES);

			if($contents === FALSE) {
				return [];
			}

			$ledger = [];

			foreach($contents as $line) {
				if(strlen(trim($line)) === 0 || substr($line, 0, 1) === '#') {
					continue;
				}

				$pieces = explode("\t", $line);

				if(count($pieces) < 3) {
					continue;
				}

				$ledger[$pieces[0]] = [
					'sha256'=>$pieces[1],
					'quality'=>(int)$pieces[2],
				];
			}

			return $ledger;
		}

		public function appendToLedger($args) {
			$relative = $args['relative'];
			$hash = $args['hash'];
			$quality = $args['quality'];

			$path = $this->ledgerPath();

			$directory = dirname($path);

			if(!is_dir($directory) && !mkdir($directory, 0755, TRUE)) {
				return FALSE;
			}

			if(!is_file($path)) {
				file_put_contents($path, '# relative_path' . "\t" . 'sha256' . "\t" . 'quality' . "\t" . 'written' . "\n");
			}

			$line = implode("\t", [$relative, $hash, $quality, date('c')]) . "\n";

			return file_put_contents($path, $line, FILE_APPEND);
		}

			// Candidates
			// -----------------------------------------------

		public function gatherCandidates() {
			$files = $this->walkImageFiles([
				'minimum_size'=>$this->arguments['minimum_size'],
				'limit'=>$this->arguments['limit'],
				'recompressible_only'=>TRUE,
			]);

			if($this->arguments['force']) {
				return $files;
			}

			$candidates = [];
			$skipped = 0;

			foreach($files as $file) {
				if(!array_key_exists($file['relative'], $this->ledger)) {
					$candidates[] = $file;

					continue;
				}

					/*
						In the ledger, but only skipped if it is still the file
						this tool wrote.  A restore or an edit changes the hash
						and makes it a candidate again.
					*/

				if(hash_file('sha256', $file['path']) === $this->ledger[$file['relative']]['sha256']) {
					$skipped++;

					continue;
				}

				$candidates[] = $file;
			}

			if($skipped) {
				print('Skipping ' . $skipped . ' files this tool has already written.' . "\n");
				print('--force would re-encode them, at the cost of another generation.' . "\n\n");
			}

			return $candidates;
		}

			// Processing
			// -----------------------------------------------

		public function processCandidates($args) {
			$candidates = $args['candidates'];

			$rows = [];
			$before_bytes = 0;
			$after_bytes = 0;
			$written = 0;
			$refused = [];

			foreach($candidates as $file) {
				$outcome = $this->searchQuality([
					'path'=>$file['path'],
					'target'=>$this->arguments['target'],
					'floor'=>$this->arguments['floor'],
				]);

				if($outcome['status'] !== 'found') {
					continue;
				}

				$before_bytes += $outcome['original_size'];

				if(!$this->arguments['apply']) {
					$after_bytes += $outcome['new_size'];

					$rows[] = $this->resultRow(['file'=>$file, 'outcome'=>$outcome, 'note'=>'would write']);

					continue;
				}

				$installed = $this->installEncoding([
					'file'=>$file,
					'outcome'=>$outcome,
				]);

				if($installed === TRUE) {
					$after_bytes += $outcome['new_size'];
					$written++;

					$rows[] = $this->resultRow(['file'=>$file, 'outcome'=>$outcome, 'note'=>'written']);

					continue;
				}

				$after_bytes += $outcome['original_size'];

				$refused[] = [
					'File'=>$file['relative'],
					'Why'=>$installed,
				];
			}

			return $this->report([
				'rows'=>$rows,
				'refused'=>$refused,
				'before_bytes'=>$before_bytes,
				'after_bytes'=>$after_bytes,
				'written'=>$written,
			]);
		}

		public function resultRow($args) {
			$file = $args['file'];
			$outcome = $args['outcome'];

			return [
				'Before'=>$this->formatBytes(['number'=>$outcome['original_size']]),
				'After'=>$this->formatBytes(['number'=>$outcome['new_size']]),
				'Saved'=>$this->formatSavedPercent([
					'before'=>$outcome['original_size'],
					'after'=>$outcome['new_size'],
				]),
				'q'=>$outcome['source_quality'] . '->' . $outcome['quality'],
				'PSNR'=>number_format($outcome['metric'], 1),
				'State'=>$args['note'],
				'File'=>$file['relative'],
			];
		}

			// Installing an encoding
			// -----------------------------------------------

			/*
				Encode to a temporary file beside the original, check it, then
				rename over.  Beside, rather than in /tmp, because rename is
				only atomic within a filesystem and /tmp may not be this one --
				and a copy that is interrupted halfway through the original is
				the failure this whole tool exists to avoid.

				Returns TRUE, or a string saying why the result was thrown away.
			*/

		public function installEncoding($args) {
			$file = $args['file'];
			$outcome = $args['outcome'];

			$encoded = $this->encodeBeside([
				'path'=>$file['path'],
				'quality'=>$outcome['quality'],
			]);

			if(!$encoded) {
				return 'encoder produced nothing';
			}

			$check = $this->identifyImage(['path'=>$encoded]);

			if(!$check) {
				unlink($encoded);

				return 'result was not readable';
			}

			if($check['width'] !== $outcome['width'] || $check['height'] !== $outcome['height']) {
				unlink($encoded);

				return 'dimensions changed: ' . $check['width'] . 'x' . $check['height']
					. ' against ' . $outcome['width'] . 'x' . $outcome['height'];
			}

			if(filesize($encoded) >= $outcome['original_size']) {
				unlink($encoded);

				return 'result was not smaller';
			}

			$permissions = fileperms($file['path']);

			if(!rename($encoded, $file['path'])) {
				unlink($encoded);

				return 'could not replace the original';
			}

			if($permissions !== FALSE) {
				chmod($file['path'], $permissions & 0777);
			}

			$hash = hash_file('sha256', $file['path']);

			if($hash !== FALSE) {
				$this->appendToLedger([
					'relative'=>$file['relative'],
					'hash'=>$hash,
					'quality'=>$outcome['quality'],
				]);
			}

			return TRUE;
		}

		public function encodeBeside($args) {
			$path = $args['path'];
			$quality = (int)$args['quality'];

			$temporary = $path . '.ggcms_recompress';

				/*
					The same invocation the search used, through the same
					portability layer -- this had `convert` written out, which
					broke the one guarantee the pair exists to provide: that the
					size check_image_compression.php reports is the size that
					gets installed. A host where ImageMagick answers only to
					`magick` would have searched happily and then failed every
					write.

					It writes beside the original rather than to a temporary
					directory, which is why it is not simply encodeToTemporary:
					rename is atomic only within a filesystem.
				*/

			$command = $this->imageMagickCommand(['tool'=>'convert']);
			$command .= ' ' . escapeshellarg($path . '[0]');
			$command .= ' -strip -quality ' . $quality;
			$command .= ' ' . escapeshellarg($temporary) . ' 2>&1';

			$this->last_encoder_error = trim((string)shell_exec($command));

			if(!is_file($temporary) || filesize($temporary) === 0) {
				if(is_file($temporary)) {
					unlink($temporary);
				}

				return FALSE;
			}

			return $temporary;
		}

			// Reporting
			// -----------------------------------------------

		public function report($args) {
			$rows = $args['rows'];
			$refused = $args['refused'];
			$before_bytes = $args['before_bytes'];
			$after_bytes = $args['after_bytes'];

			if(!$rows && !$refused) {
				print('No file could be improved at this target.' . "\n\n");

				return TRUE;
			}

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
				print(count($refused) . ' results were thrown away rather than installed:' . "\n\n");
				print(arr2textTable($refused));
				print("\n");
			}

			$verb = $this->arguments['apply'] ? 'Wrote' : 'Would write';

			print($verb . ' ' . count($rows) . ' files: ');
			print($this->formatBytes(['number'=>$before_bytes]) . ' becomes ');
			print($this->formatBytes(['number'=>$after_bytes]) . ', saving ');
			print($this->formatBytes(['number'=>$before_bytes - $after_bytes]) . ' (');
			print($this->formatSavedPercent(['before'=>$before_bytes, 'after'=>$after_bytes]));
			print(').' . "\n\n");

			if($this->arguments['apply'] && $args['written']) {
				print('The page cache still holds pages built against the old files.' . "\n");
				print('Flush it for this domain so the new sizes are served.' . "\n\n");
			}

			if(!$this->arguments['apply']) {
				print('Add --apply to write these.' . "\n\n");
			}

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Compressor';
		}

		public function confirmDomainText() {
			return 'Compressing images for: ';
		}
	}

?>

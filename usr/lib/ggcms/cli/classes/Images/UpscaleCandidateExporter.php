<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	/*
		Packaging the originals too small to be worth looking at, so a machine
		with a graphics card can make them bigger.

		The outbound half of the upscale pipeline, and the mirror image of
		export_image_candidates.php: that one sends the files too large, this
		one the files too small.  The upscaling itself wants a GPU and a
		neural network the droplet has neither of, so it happens elsewhere --
		see upscale_export_locally.php.

		## It works from Image rows, not from the tree

		The compression export walks the directory, because re-encoding a
		file changes nothing the database knows.  Upscaling changes the
		dimensions, and the dimensions are in the row, so a file no row names
		has nothing to correct and is not sent.  Only the original variant
		goes out; the standard and the icon are regenerated from it on the way
		back in, by the engine's own resize.

		## What counts as too small

		The standard image is the original fitted inside 1000 by 1000, and
		the engine never enlarges.  So an original whose longer side is under
		1000 is shown to the reader at its own size, however small that is --
		that is what --below defaults to.  --min-side keeps out the images
		that are small because they are icons or ornaments; a 60-pixel
		bullet upscaled four times is a 240-pixel guess.

		## The manifest is the contract

		Relative path, size, width, height, SHA-256 and the Image row's id.
		The import checks every one of them again before it writes anything.
	*/

	class UpscaleCandidateExporter {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;

		public $arguments;

			// Entry Point
			// -----------------------------------------------

		public function exportUpscaleCandidates() {
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

			if(!$this->requireImageMagick()) {
				return FALSE;
			}

			if(!is_dir($this->imageDirectory())) {
				print('No image directory at ' . $this->imageDirectory() . "\n\n");

				return FALSE;
			}

			if(strlen($this->arguments['directory']) === 0 && $this->arguments['apply']) {
				print('Where should the batch be written?  Pass --to=/path/to/batch' . "\n\n");

				return FALSE;
			}

			$this->setGlobals();
			$this->setMySQLArgs();

			$candidates = $this->gatherCandidates([
				'rows'=>$this->loadImageRows(),
			]);

			if(!$candidates) {
				print('No named original is under ' . $this->arguments['below'] . ' pixels on its longer side');
				print(' and at least ' . $this->arguments['min_side'] . ' on its shorter.' . "\n\n");

				return TRUE;
			}

			$candidates = $this->withoutAlreadyUpscaled(['candidates'=>$candidates]);

			if(!$candidates) {
				print('Every matching original has already been upscaled by this pipeline.' . "\n\n");

				return TRUE;
			}

			$this->printCandidates(['candidates'=>$candidates]);

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been written.' . "\n");
				print('Add --to=/path/to/batch --apply to write the batch.' . "\n\n");

				return TRUE;
			}

			return $this->writeBatch(['candidates'=>$candidates]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'directory'=>'',
				'below'=>1000,
				'min_side'=>150,
				'limit'=>0,
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
				} else if(substr($argument, 0, 5) === '--to=') {
					$arguments['directory'] = rtrim(substr($argument, 5), '/') . '/';
				} else if(substr($argument, 0, 8) === '--below=') {
					$arguments['below'] = (int)substr($argument, 8);
				} else if(substr($argument, 0, 11) === '--min-side=') {
					$arguments['min_side'] = (int)substr($argument, 11);
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				}
			}

			if($arguments['below'] < 1) {
				$arguments['below'] = 1000;
			}

			if($arguments['min_side'] < 1) {
				$arguments['min_side'] = 1;
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Upscale Candidate Export' . "\n\n");
			print('  export_upscale_candidates.php DOMAIN [--below=1000] [--min-side=150]' . "\n");
			print('                                       [--limit=N] [--all] [--force]' . "\n");
			print('                                       [--to=/path/to/batch --apply]' . "\n\n");
			print('  Lists, and with --apply writes out, the originals an Image row names' . "\n");
			print('  whose longer side is under --below and shorter side at least --min-side.' . "\n\n");
			print('  --force  include originals this pipeline has already upscaled' . "\n\n");
			print('  Then, on a machine with a graphics card:' . "\n\n");
			print('    upscale_export_locally.php /path/to/batch --upscaler=/path/to/realesrgan-ncnn-vulkan' . "\n\n");
			print('  and back here:' . "\n\n");
			print('    import_upscaled_images.php DOMAIN --from=/path/to/batch' . "\n\n");

			return TRUE;
		}

			// Reading
			// -----------------------------------------------

		public function loadImageRows() {
			$query = 'SELECT id, Entryid, FileName, StandardFileName, IconFileName, FileDirectory ';
			$query .= 'FROM Image ORDER BY id';

			return $this->runQuery(['query'=>$query]);
		}

			/*
				The JPEG family and PNG.  GIFs are left alone: most of the
				ones worth upscaling are animations, and the upscaler reads
				the first frame and throws the rest away.  The extension has
				to survive the trip, because FileName is not changing.
			*/

		public function upscalableExtensions() {
			return ['jpg', 'jpeg', 'jfif', 'png'];
		}

		public function gatherCandidates($args) {
			$below = $this->arguments['below'];
			$min_side = $this->arguments['min_side'];

			$candidates = [];
			$missing = 0;

			foreach($args['rows'] as $row) {
				$path = $this->imagePathForVariant(['row'=>$row, 'variant'=>'original']);

				if(strlen($path) === 0) {
					continue;
				}

				if(!in_array($this->extensionOf(['path'=>$path]), $this->upscalableExtensions(), TRUE)) {
					continue;
				}

				if(!is_file($path)) {
					$missing++;

					continue;
				}

				$image = $this->identifyImage(['path'=>$path]);

				if(!$image) {
					continue;
				}

				$longer = max($image['width'], $image['height']);
				$shorter = min($image['width'], $image['height']);

				if($longer >= $below || $shorter < $min_side) {
					continue;
				}

				$candidates[] = [
					'row'=>$row,
					'path'=>$path,
					'relative'=>$this->relativeImagePath(['path'=>$path]),
					'image'=>$image,
					'size'=>filesize($path),
				];

				if($this->arguments['limit'] && count($candidates) >= $this->arguments['limit']) {
					break;
				}
			}

			if($missing) {
				print($missing . ' named originals are not on disk; scan_images.php --check=missing lists them.' . "\n\n");
			}

			return $candidates;
		}

			// The ledger
			// -----------------------------------------------

			/*
				A file this pipeline has already upscaled is not sent round
				again.  Its detail is already invented; upscaling the guess
				invents detail on top of detail, and the second pass is where
				faces start to become somebody else's.  A file whose hash no
				longer matches its entry has been restored or replaced since,
				and is a candidate again.
			*/

		public function withoutAlreadyUpscaled($args) {
			$candidates = $args['candidates'];

			if($this->arguments['force']) {
				return $candidates;
			}

			$ledger = $this->readLedger();

			if(!$ledger) {
				return $candidates;
			}

			$kept = [];
			$skipped = 0;

			foreach($candidates as $candidate) {
				if(array_key_exists($candidate['relative'], $ledger)
					&& hash_file('sha256', $candidate['path']) === $ledger[$candidate['relative']]) {
					$skipped++;

					continue;
				}

				$kept[] = $candidate;
			}

			if($skipped) {
				print('Skipping ' . $skipped . ' originals this pipeline has already upscaled.' . "\n\n");
			}

			return $kept;
		}

		public function ledgerPath() {
			return $this->imageBackupDomainRoot() . 'upscaled.tsv';
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

				if(count($pieces) < 2) {
					continue;
				}

				$ledger[$pieces[0]] = $pieces[1];
			}

			return $ledger;
		}

			// Reporting
			// -----------------------------------------------

		public function printCandidates($args) {
			$candidates = $args['candidates'];

			$limit = $this->arguments['all'] ? 0 : 25;
			$shown = ($limit > 0 && count($candidates) > $limit) ? array_slice($candidates, 0, $limit) : $candidates;

			$table = [];
			$bytes = 0;

			foreach($candidates as $candidate) {
				$bytes += $candidate['size'];
			}

			foreach($shown as $candidate) {
				$table[] = [
					'Image'=>$candidate['row']['id'],
					'Entry'=>$candidate['row']['Entryid'],
					'Size'=>$candidate['image']['width'] . 'x' . $candidate['image']['height'],
					'Bytes'=>$this->formatBytes(['number'=>$candidate['size']]),
					'File'=>$candidate['relative'],
				];
			}

			print(arr2textTable($table));

			if(count($shown) < count($candidates)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($candidates) . '; --all for the rest.' . "\n");
			}

			print("\n" . count($candidates) . ' originals, ' . $this->formatBytes(['number'=>$bytes]) . '.' . "\n\n");

			return TRUE;
		}

			// Writing
			// -----------------------------------------------

		public function writeBatch($args) {
			$candidates = $args['candidates'];

			$directory = $this->arguments['directory'];
			$files_directory = $directory . 'files/';

			if(is_dir($directory)) {
				print('That batch directory already exists.  Choose another --to.' . "\n\n");

				return FALSE;
			}

			if(!mkdir($files_directory, 0755, TRUE)) {
				print('Could not create ' . $files_directory . "\n\n");

				return FALSE;
			}

			$manifest_lines = [];
			$copied = 0;
			$failures = [];

			foreach($candidates as $candidate) {
				$target = $files_directory . $candidate['relative'];
				$target_directory = dirname($target);

				if(!is_dir($target_directory) && !mkdir($target_directory, 0755, TRUE)) {
					$failures[] = ['File'=>$candidate['relative'], 'Why'=>'could not create directory'];

					continue;
				}

				if(!copy($candidate['path'], $target)) {
					$failures[] = ['File'=>$candidate['relative'], 'Why'=>'copy failed'];

					continue;
				}

					/*
						Hashed from the copy, as the compression export does: a
						copy that went wrong has to fail here, not become the
						thing the import compares the live file against.
					*/

				$hash = hash_file('sha256', $target);

				if($hash === FALSE || filesize($target) !== $candidate['size']) {
					$failures[] = ['File'=>$candidate['relative'], 'Why'=>'copy did not match the original'];

					continue;
				}

				$manifest_lines[] = implode("\t", [
					$candidate['relative'],
					$candidate['size'],
					$candidate['image']['width'],
					$candidate['image']['height'],
					$hash,
					$candidate['row']['id'],
				]);

				$copied++;
			}

			$this->writeManifest([
				'directory'=>$directory,
				'lines'=>$manifest_lines,
			]);

			print('Wrote ' . $copied . ' of ' . count($candidates) . ' files.' . "\n\n");

			if($failures) {
				print(count($failures) . ' were not written:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");
			}

			print('Fetch it with something like:' . "\n\n");
			print('  scp -r root@HOST:' . $directory . ' .' . "\n\n");
			print('Then on that machine:' . "\n\n");
			print('  upscale_export_locally.php ' . basename(rtrim($directory, '/')) . ' --upscaler=/path/to/realesrgan-ncnn-vulkan' . "\n\n");

			return TRUE;
		}

		public function writeManifest($args) {
			$directory = $args['directory'];

			$contents = '# relative_path' . "\t" . 'size' . "\t" . 'width' . "\t" . 'height';
			$contents .= "\t" . 'sha256' . "\t" . 'image_id' . "\n";
			$contents .= '# domain: ' . $this->domain . "\n";
			$contents .= '# exported: ' . date('c') . "\n";
			$contents .= '# below: ' . $this->arguments['below'] . '  min-side: ' . $this->arguments['min_side'] . "\n";
			$contents .= implode("\n", $args['lines']) . "\n";

			return file_put_contents($directory . 'manifest.tsv', $contents);
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Upscale Candidate Export';
		}

		public function confirmDomainText() {
			return 'Exporting upscale candidates for: ';
		}
	}

?>

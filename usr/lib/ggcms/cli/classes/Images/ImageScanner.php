<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');
	clireq('traits/ImageRows.php');

	/*
		What is in the image tree, and what is wrong with it.

		This is the inventory tool.  It reads the filesystem and the Image
		table and reconciles them, and it does not encode anything, move
		anything or write anything.

		Four checks, and only the first three run by default:

		  weight      where the bytes are -- by variant, by extension, and the
		              heaviest individual files
		  orphans     files on disk that no Image row names
		  missing     Image rows whose file is not on disk
		  dimensions  stored PixelWidth and PixelHeight against the actual
		              image

		dimensions is excluded from the default run because it shells out to
		identify once per file, and the tree is nine thousand files.  Ask for
		it by name and it honours --limit, which defaults to the fifty largest.

		The reconciliation is exact rather than heuristic.  Every filename the
		database expects is derived from FileDirectory and the three filename
		columns, so a file is an orphan because no row names it, not because
		its name did not match a pattern.
	*/

	class ImageScanner {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ImageRows;

			// Entry Point
			// -----------------------------------------------

		public function scanImages() {
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

			$this->setGlobals();
			$this->setMySQLArgs();

			$files = $this->walkImageFiles([]);
			$rows = $this->loadImageRows();
			$expected = $this->buildExpectedFileMap(['rows'=>$rows]);

			print('On disk: ' . count($files) . ' image files.  ');
			print('In the database: ' . count($rows) . ' rows naming ' . count($expected) . ' files.' . "\n\n");

			if($this->wantsCheck(['check'=>'weight'])) {
				$this->reportWeight(['files'=>$files, 'expected'=>$expected]);
			}

			if($this->wantsCheck(['check'=>'orphans'])) {
				$this->reportOrphans(['files'=>$files, 'expected'=>$expected]);
			}

			if($this->wantsCheck(['check'=>'missing'])) {
				$this->reportMissing(['expected'=>$expected]);
			}

			if($this->wantsCheck(['check'=>'dimensions'])) {
				$this->reportDimensions(['files'=>$files, 'expected'=>$expected]);
			}

			return TRUE;
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'check'=>'default',
				'minimum_size'=>0,
				'limit'=>50,
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--all') {
					$arguments['all'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 8) === '--check=') {
					$arguments['check'] = substr($argument, 8);
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
				--min-size=2M rather than --min-size=2097152.  Every operator
				who reaches for this tool is thinking in megabytes and the
				alternative is a mistyped zero.
			*/

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

		public function wantsCheck($args) {
			$check = $args['check'];
			$requested = $this->arguments['check'];

			if($requested === 'all') {
				return TRUE;
			}

			if($requested === 'default') {
				return $check !== 'dimensions';
			}

			return $requested === $check;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Image Scanner' . "\n\n");
			print('  scan_images.php DOMAIN [--check=weight|orphans|missing|dimensions|all]' . "\n");
			print('                         [--min-size=2M] [--limit=50] [--all]' . "\n\n");
			print('  weight      where the bytes are, by variant and by file' . "\n");
			print('  orphans     files on disk that no Image row names' . "\n");
			print('  missing     Image rows whose file is not on disk' . "\n");
			print('  dimensions  stored dimensions against the actual image; not in the' . "\n");
			print('              default run, because it reads every file' . "\n\n");
			print('  Prints problems and a summary.  --all widens the lists.' . "\n\n");

			return TRUE;
		}

			// Weight
			// -----------------------------------------------

		public function reportWeight($args) {
			$files = $args['files'];
			$expected = $args['expected'];

			if(!$files) {
				print('No image files.' . "\n\n");

				return TRUE;
			}

			$by_variant = [];
			$total_bytes = 0;

			foreach($files as $file) {
				$variant = array_key_exists($file['relative'], $expected)
					? $expected[$file['relative']]['variant']
					: 'orphan';

				if(!array_key_exists($variant, $by_variant)) {
					$by_variant[$variant] = ['files'=>0, 'bytes'=>0];
				}

				$by_variant[$variant]['files']++;
				$by_variant[$variant]['bytes'] += $file['size'];

				$total_bytes += $file['size'];
			}

			$variant_table = [];

			foreach($by_variant as $variant=>$totals) {
				$variant_table[] = [
					'Variant'=>$variant,
					'Files'=>$totals['files'],
					'Bytes'=>$this->formatBytes(['number'=>$totals['bytes']]),
					'Share'=>$this->formatSavedPercent([
						'before'=>$total_bytes,
						'after'=>$total_bytes - $totals['bytes'],
					]),
				];
			}

			print('Weight by variant -- ' . $this->formatBytes(['number'=>$total_bytes]) . ' total' . "\n\n");
			print(arr2textTable($variant_table));
			print("\n");

			$this->reportHeaviestFiles([
				'files'=>$files,
				'expected'=>$expected,
			]);

			return TRUE;
		}

		public function reportHeaviestFiles($args) {
			$files = $args['files'];
			$expected = $args['expected'];

			$minimum_size = $this->arguments['minimum_size'];
			$limit = $this->arguments['all'] ? 0 : $this->arguments['limit'];

			$heavy = [];

			foreach($files as $file) {
				if($minimum_size && $file['size'] < $minimum_size) {
					continue;
				}

				$heavy[] = $file;
			}

			if(!$heavy) {
				print('No files at or above ' . $this->formatBytes(['number'=>$minimum_size]) . '.' . "\n\n");

				return TRUE;
			}

			$shown = ($limit > 0 && count($heavy) > $limit) ? array_slice($heavy, 0, $limit) : $heavy;

			$table = [];

			foreach($shown as $file) {
				$variant = array_key_exists($file['relative'], $expected)
					? $expected[$file['relative']]['variant']
					: 'orphan';

				$table[] = [
					'Size'=>$this->formatBytes(['number'=>$file['size']]),
					'Variant'=>$variant,
					'File'=>$file['relative'],
				];
			}

			$heavy_bytes = 0;

			foreach($heavy as $file) {
				$heavy_bytes += $file['size'];
			}

			print('Heaviest files -- ' . count($heavy) . ' files holding ');
			print($this->formatBytes(['number'=>$heavy_bytes]));

			if(count($shown) < count($heavy)) {
				print(', showing ' . count($shown) . '; --all for the rest');
			}

			print("\n\n");
			print(arr2textTable($table));
			print("\n");

			return TRUE;
		}

			// Orphans
			// -----------------------------------------------

			/*
				An orphan is not necessarily rubbish.  Older sites here have
				stage variants and hand-placed files that predate the Image
				table, and deleting on this tool's say-so would be how a scan
				of somebody's artwork stops existing.  So it counts them, shows
				the heaviest, and says nothing about what to do with them.
			*/

		public function reportOrphans($args) {
			$files = $args['files'];
			$expected = $args['expected'];

			$orphans = [];
			$orphan_bytes = 0;

			foreach($files as $file) {
				if(array_key_exists($file['relative'], $expected)) {
					continue;
				}

				$orphans[] = $file;
				$orphan_bytes += $file['size'];
			}

			if(!$orphans) {
				print('Orphans: none.  Every file on disk is named by a row.' . "\n\n");

				return TRUE;
			}

			$limit = $this->arguments['all'] ? 0 : $this->arguments['limit'];
			$shown = ($limit > 0 && count($orphans) > $limit) ? array_slice($orphans, 0, $limit) : $orphans;

			$table = [];

			foreach($shown as $orphan) {
				$table[] = [
					'Size'=>$this->formatBytes(['number'=>$orphan['size']]),
					'File'=>$orphan['relative'],
				];
			}

			print('Orphans: ' . count($orphans) . ' files, ' . $this->formatBytes(['number'=>$orphan_bytes]));
			print(' on disk that no Image row names');

			if(count($shown) < count($orphans)) {
				print(', showing the ' . count($shown) . ' heaviest; --all for the rest');
			}

			print("\n\n");
			print(arr2textTable($table));
			print("\n");

			return TRUE;
		}

			// Missing
			// -----------------------------------------------

		public function reportMissing($args) {
			$expected = $args['expected'];

			$missing = [];

			foreach($expected as $relative=>$details) {
				if(is_file($this->imageDirectory() . $relative)) {
					continue;
				}

				$missing[] = [
					'Image id'=>$details['id'],
					'Variant'=>$details['variant'],
					'File'=>$relative,
				];
			}

			if(!$missing) {
				print('Missing: none.  Every file the database names is on disk.' . "\n\n");

				return TRUE;
			}

			$limit = $this->arguments['all'] ? 0 : $this->arguments['limit'];
			$shown = ($limit > 0 && count($missing) > $limit) ? array_slice($missing, 0, $limit) : $missing;

			print('Missing: ' . count($missing) . ' files named by a row and absent from disk');

			if(count($shown) < count($missing)) {
				print(', showing ' . count($shown) . '; --all for the rest');
			}

			print("\n\n");
			print(arr2textTable($shown));
			print("\n");

			return TRUE;
		}

			// Dimensions
			// -----------------------------------------------

			/*
				A mismatch here is a visible bug, not an untidiness.  The
				stored numbers go straight into the img tag, so a row that says
				800x600 against a file that is 400x300 renders the image at
				double size and soft.  It is also the failure a careless
				compressor would cause, which is why the tool that does the
				compressing checks the same invariant on every write.
			*/

		public function reportDimensions($args) {
			$files = $args['files'];
			$expected = $args['expected'];

			$limit = $this->arguments['all'] ? 0 : $this->arguments['limit'];
			$checked = 0;
			$findings = [];

			$dimension_fields = [
				'original'=>['PixelWidth', 'PixelHeight'],
				'standard'=>['StandardPixelWidth', 'StandardPixelHeight'],
				'icon'=>['IconPixelWidth', 'IconPixelHeight'],
			];

			foreach($files as $file) {
				if($limit > 0 && $checked >= $limit) {
					break;
				}

				if(!array_key_exists($file['relative'], $expected)) {
					continue;
				}

				$details = $expected[$file['relative']];
				$variant = $details['variant'];

				if(!array_key_exists($variant, $dimension_fields)) {
					continue;
				}

				$identified = $this->identifyImage(['path'=>$file['path']]);

				$checked++;

				if(!$identified) {
					$findings[] = [
						'Image id'=>$details['id'],
						'Variant'=>$variant,
						'Stored'=>'-',
						'Actual'=>'unreadable',
						'File'=>$file['relative'],
					];

					continue;
				}

				$width_field = $dimension_fields[$variant][0];
				$height_field = $dimension_fields[$variant][1];

				$stored_width = (int)$details['row'][$width_field];
				$stored_height = (int)$details['row'][$height_field];

					/*
						A zero stored dimension is absence, not disagreement.
						The template prints it as width="0" either way, but it
						is a different defect with a different fix and lumping
						the two together buries whichever is rarer.
					*/

				if($stored_width === 0 && $stored_height === 0) {
					$findings[] = [
						'Image id'=>$details['id'],
						'Variant'=>$variant,
						'Stored'=>'unset',
						'Actual'=>$identified['width'] . 'x' . $identified['height'],
						'File'=>$file['relative'],
					];

					continue;
				}

				if($stored_width === $identified['width'] && $stored_height === $identified['height']) {
					continue;
				}

				$findings[] = [
					'Image id'=>$details['id'],
					'Variant'=>$variant,
					'Stored'=>$stored_width . 'x' . $stored_height,
					'Actual'=>$identified['width'] . 'x' . $identified['height'],
					'File'=>$file['relative'],
				];
			}

			if(!$findings) {
				print('Dimensions: ' . $checked . ' files read, all matching their rows.' . "\n\n");

				return TRUE;
			}

			print('Dimensions: ' . count($findings) . ' disagreements in ' . $checked . ' files read.' . "\n");
			print('These render at the stored size against a file of the actual size.' . "\n\n");
			print(arr2textTable($findings));
			print("\n");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Scanner';
		}

		public function confirmDomainText() {
			return 'Scanning images for: ';
		}
	}

?>

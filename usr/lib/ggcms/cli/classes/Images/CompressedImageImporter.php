<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	/*
		Taking the results back, and believing none of them on sight.

		This is the half that matters.  The desktop that did the compressing is
		a processor, not an authority: it can be a different machine, a
		different ImageMagick, a different metric, and the files arrive over a
		network.  So every result is re-checked here against what the server
		itself knows, and anything that fails a check is refused and reported
		rather than installed.

		## The five checks

		**A backup exists.**  As with the in-place compressor, --apply does
		nothing otherwise.  The images are on no other disk.

		**The live file is still the file that went out.**  Its SHA-256 must
		match the manifest.  If it changed while the batch was away, the result
		is an answer about a file that no longer exists, and writing it would
		silently discard whatever the change was.

		**The returned file is intact.**  Its SHA-256 must match what the
		compressor recorded, which catches a truncated or corrupted transfer.
		A truncated JPEG is frequently still a valid JPEG, of the top of the
		picture.

		**The dimensions have not moved.**  PixelWidth and its siblings are
		printed into the img tag, so a resize makes every stored dimension a
		lie.  Checked against the manifest, not against the incoming file's own
		claims.

		**It is actually smaller.**  A result that is not smaller is not a
		saving, and installing it would spend a generation of quality for
		nothing.

		Only then is it renamed into place, and only then does it enter the
		ledger that stops it being compressed again.
	*/

	class CompressedImageImporter {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		
		public $arguments;

			// Entry Point
			// -----------------------------------------------

		public function importCompressedImages() {
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

			if(strlen($this->arguments['directory']) === 0) {
				print('Which batch?  Pass --from=/path/to/batch' . "\n\n");

				return FALSE;
			}

			if(!$this->requireImageMagick()) {
				return FALSE;
			}

			if($this->arguments['apply'] && !$this->confirmBackupExists()) {
				return FALSE;
			}

			$manifest = $this->readManifest();
			$results = $this->readResults();

			if($manifest === FALSE || $results === FALSE) {
				return FALSE;
			}

			print('Batch:    ' . $this->arguments['directory'] . "\n");
			print('Manifest: ' . count($manifest) . ' files' . "\n");
			print('Results:  ' . count($results) . ' rows' . "\n\n");

			$this->reportMetric(['results'=>$results]);

			$examined = $this->examine([
				'manifest'=>$manifest,
				'results'=>$results,
			]);

			return $this->reportAndInstall(['examined'=>$examined]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'directory'=>'',
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
				} else if(substr($argument, 0, 7) === '--from=') {
					$arguments['directory'] = rtrim(substr($argument, 7), '/') . '/';
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Compressed Image Import' . "\n\n");
			print('  import_compressed_images.php DOMAIN --from=/path/to/batch [--all] [--apply]' . "\n\n");
			print('  Re-checks every returned file against the manifest and installs the' . "\n");
			print('  ones that pass.  Dry by default; --apply requires a backup.' . "\n\n");

			return TRUE;
		}

			// Refusing without a backup
			// -----------------------------------------------

		public function confirmBackupExists() {
			$root = $this->imageBackupDomainRoot();

			$labels = [];

			if(is_dir($root)) {
				foreach(scandir($root) as $entry) {
					if($entry === '.' || $entry === '..' || !is_dir($root . $entry)) {
						continue;
					}

					$labels[] = $entry;
				}
			}

			if(!$labels) {
				print('No backup exists for ' . $this->domain . '.' . "\n\n");
				print('Take one first:' . "\n\n");
				print('  backup_images.php ' . $this->domain . ' --min-size=1M --apply' . "\n\n");

				return FALSE;
			}

			sort($labels);

			print('Backup found: ' . end($labels) . "\n\n");

			return TRUE;
		}

			// Reading the batch
			// -----------------------------------------------

		public function readManifest() {
			$path = $this->arguments['directory'] . 'manifest.tsv';

			$rows = $this->readTable(['path'=>$path, 'columns'=>6]);

			if($rows === FALSE) {
				return FALSE;
			}

			$manifest = [];

			foreach($rows as $pieces) {
				$manifest[$pieces[0]] = [
					'relative'=>$pieces[0],
					'size'=>(int)$pieces[1],
					'width'=>(int)$pieces[2],
					'height'=>(int)$pieces[3],
					'source_quality'=>(int)$pieces[4],
					'sha256'=>$pieces[5],
				];
			}

			return $manifest;
		}

		public function readResults() {
			$path = $this->arguments['directory'] . 'results.tsv';

			$rows = $this->readTable(['path'=>$path, 'columns'=>10]);

			if($rows === FALSE) {
				return FALSE;
			}

			$results = [];

			foreach($rows as $pieces) {
				$results[] = [
					'relative'=>$pieces[0],
					'status'=>$pieces[1],
					'original_size'=>(int)$pieces[2],
					'new_size'=>(int)$pieces[3],
					'original_sha256'=>$pieces[4],
					'new_sha256'=>$pieces[5],
					'quality'=>(int)$pieces[6],
					'metric_value'=>$pieces[7],
					'metric'=>$pieces[8],
					'target'=>$pieces[9],
				];
			}

			return $results;
		}

		public function readTable($args) {
			$path = $args['path'];

			if(!is_file($path)) {
				print('No file at ' . $path . "\n\n");

				return FALSE;
			}

			$contents = file($path, FILE_IGNORE_NEW_LINES);

			if($contents === FALSE) {
				print('Could not read ' . $path . "\n\n");

				return FALSE;
			}

			$rows = [];

			foreach($contents as $line) {
				if(strlen(trim($line)) === 0 || substr($line, 0, 1) === '#') {
					continue;
				}

				$pieces = explode("\t", $line);

				if(count($pieces) < $args['columns']) {
					continue;
				}

				$rows[] = $pieces;
			}

			return $rows;
		}

			/*
				Say which metric produced these, because it is very likely not
				the one this machine would have used.  A row reading 0.996
				against a server whose default is 41.0 is not a mistake, and
				somebody reading the table a month later should not have to
				work that out.
			*/

		public function reportMetric($args) {
			$metrics = [];

			foreach($args['results'] as $result) {
				if($result['status'] !== 'compressed') {
					continue;
				}

				$metrics[$result['metric'] . ' at ' . $result['target']] = TRUE;
			}

			if(!$metrics) {
				return TRUE;
			}

			print('Compressed elsewhere using: ' . implode(', ', array_keys($metrics)) . "\n\n");

			return TRUE;
		}

			// Checking
			// -----------------------------------------------

		public function examine($args) {
			$manifest = $args['manifest'];

			$installable = [];
			$refused = [];
			$skipped = 0;

			foreach($args['results'] as $result) {
				if($result['status'] !== 'compressed') {
					$skipped++;

					continue;
				}

				$relative = $result['relative'];

				if(!array_key_exists($relative, $manifest)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'not in the manifest']);

					continue;
				}

				$entry = $manifest[$relative];

				$live = $this->imageDirectory() . $relative;

				if(!is_file($live)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'live file has gone']);

					continue;
				}

				if(hash_file('sha256', $live) !== $entry['sha256']) {
					$refused[] = $this->refusal([
						'relative'=>$relative,
						'why'=>'live file changed since export',
					]);

					continue;
				}

				$incoming = $this->arguments['directory'] . 'compressed/' . $relative;

				if(!is_file($incoming)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'result file not in batch']);

					continue;
				}

				if(hash_file('sha256', $incoming) !== $result['new_sha256']) {
					$refused[] = $this->refusal([
						'relative'=>$relative,
						'why'=>'result does not match its recorded hash',
					]);

					continue;
				}

				$identified = $this->identifyImage(['path'=>$incoming]);

				if(!$identified) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'result is not readable']);

					continue;
				}

				if($identified['width'] !== $entry['width'] || $identified['height'] !== $entry['height']) {
					$refused[] = $this->refusal([
						'relative'=>$relative,
						'why'=>'dimensions changed: ' . $identified['width'] . 'x' . $identified['height']
							. ' against ' . $entry['width'] . 'x' . $entry['height'],
					]);

					continue;
				}

				$incoming_size = filesize($incoming);

				if($incoming_size >= $entry['size']) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'result is not smaller']);

					continue;
				}

				$installable[] = [
					'relative'=>$relative,
					'live'=>$live,
					'incoming'=>$incoming,
					'before'=>$entry['size'],
					'after'=>$incoming_size,
					'quality'=>$result['quality'],
					'metric_value'=>$result['metric_value'],
					'new_sha256'=>$result['new_sha256'],
				];
			}

			return [
				'installable'=>$installable,
				'refused'=>$refused,
				'skipped'=>$skipped,
			];
		}

		public function refusal($args) {
			return [
				'File'=>$args['relative'],
				'Why'=>$args['why'],
			];
		}

			// Reporting and installing
			// -----------------------------------------------

		public function reportAndInstall($args) {
			$examined = $args['examined'];

			$installable = $examined['installable'];
			$refused = $examined['refused'];

			if($examined['skipped']) {
				print($examined['skipped'] . ' rows were not compressed on the other machine.' . "\n\n");
			}

			if($refused) {
				print(count($refused) . ' results refused:' . "\n\n");
				print(arr2textTable($refused));
				print("\n");
			}

			if(!$installable) {
				print('Nothing to install.' . "\n\n");

				return TRUE;
			}

			$before = 0;
			$after = 0;

			foreach($installable as $entry) {
				$before += $entry['before'];
				$after += $entry['after'];
			}

			$this->printInstallTable(['installable'=>$installable]);

			$verb = $this->arguments['apply'] ? 'Installing' : 'Would install';

			print($verb . ' ' . count($installable) . ' files: ');
			print($this->formatBytes(['number'=>$before]) . ' becomes ');
			print($this->formatBytes(['number'=>$after]) . ', saving ');
			print($this->formatBytes(['number'=>$before - $after]) . ' (');
			print($this->formatSavedPercent(['before'=>$before, 'after'=>$after]));
			print(').' . "\n\n");

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been written.' . "\n");
				print('Add --apply to install these.' . "\n\n");

				return TRUE;
			}

			return $this->install(['installable'=>$installable]);
		}

		public function printInstallTable($args) {
			$installable = $args['installable'];

			$limit = $this->arguments['all'] ? 0 : 20;
			$shown = ($limit > 0 && count($installable) > $limit) ? array_slice($installable, 0, $limit) : $installable;

			$table = [];

			foreach($shown as $entry) {
				$table[] = [
					'Before'=>$this->formatBytes(['number'=>$entry['before']]),
					'After'=>$this->formatBytes(['number'=>$entry['after']]),
					'Saved'=>$this->formatSavedPercent(['before'=>$entry['before'], 'after'=>$entry['after']]),
					'q'=>$entry['quality'],
					'Metric'=>$entry['metric_value'],
					'File'=>$entry['relative'],
				];
			}

			print(arr2textTable($table));

			if(count($shown) < count($installable)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($installable) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

		public function install($args) {
			$installed = 0;
			$failures = [];

			foreach($args['installable'] as $entry) {
					/*
						Copied beside the original and renamed over it, never
						written through.  rename within a filesystem is atomic;
						a copy interrupted halfway through the live file is the
						failure this whole pipeline exists to avoid.
					*/

				$staged = $entry['live'] . '.ggcms_import';

				if(!copy($entry['incoming'], $staged)) {
					$failures[] = $this->refusal(['relative'=>$entry['relative'], 'why'=>'could not stage']);

					continue;
				}

				if(hash_file('sha256', $staged) !== $entry['new_sha256']) {
					unlink($staged);

					$failures[] = $this->refusal(['relative'=>$entry['relative'], 'why'=>'staged copy does not match']);

					continue;
				}

				$permissions = fileperms($entry['live']);

				if(!rename($staged, $entry['live'])) {
					unlink($staged);

					$failures[] = $this->refusal(['relative'=>$entry['relative'], 'why'=>'could not replace the original']);

					continue;
				}

				if($permissions !== FALSE) {
					chmod($entry['live'], $permissions & 0777);
				}

				$this->appendToLedger([
					'relative'=>$entry['relative'],
					'hash'=>$entry['new_sha256'],
					'quality'=>$entry['quality'],
				]);

				$installed++;
			}

			print('Installed ' . $installed . ' of ' . count($args['installable']) . ' files.' . "\n\n");

			if($failures) {
				print(count($failures) . ' failed:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");
			}

			print('The page cache still holds pages built against the old files.' . "\n");
			print('Flush it for this domain so the new sizes are served.' . "\n\n");

			return TRUE;
		}

			// The ledger
			// -----------------------------------------------

		public function ledgerPath() {
			return $this->imageBackupDomainRoot() . 'compressed.tsv';
		}

		public function appendToLedger($args) {
			$path = $this->ledgerPath();

			$directory = dirname($path);

			if(!is_dir($directory) && !mkdir($directory, 0755, TRUE)) {
				return FALSE;
			}

			if(!is_file($path)) {
				file_put_contents($path, '# relative_path' . "\t" . 'sha256' . "\t" . 'quality' . "\t" . 'written' . "\n");
			}

			$line = implode("\t", [$args['relative'], $args['hash'], $args['quality'], date('c')]) . "\n";

			return file_put_contents($path, $line, FILE_APPEND);
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Compressed Image Import';
		}

		public function confirmDomainText() {
			return 'Importing compressed images for: ';
		}
	}

?>

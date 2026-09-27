<?php

	clireq('traits/ByteDisplay.php');
	clireq('traits/ImageFiles.php');
	clireq('traits/ImageQualitySearch.php');

	/*
		The compute half, run anywhere with cores.

		Reads a batch written by export_image_candidates.php, runs the quality
		search over it, and writes the results back into the batch directory
		for import_compressed_images.php to check and install.

		It has no database, no domain and no notion of /srv.  Everything it
		needs is in the batch.

		## It shares the search rather than reimplementing it

		The whole value of this arrangement is that the number
		check_image_compression.php reports on the server is the number you
		actually get.  A second implementation of the quality search written
		for this machine would drift from the first, quietly, and the two would
		disagree about files nobody re-tested.  So this loads the same
		ImageQualitySearch trait and passes different settings to it.

		## What the extra compute buys

		Not merely speed.  Given cores, the search is worth making stricter in
		two ways the droplet cannot afford:

		  --regions=5   measure five windows and keep the worst, instead of
		                trusting the centre crop.  Damage is not evenly spread;
		                a portrait with a calm centre and detailed edges passes
		                on one window and loses the edges.

		  SSIM          ImageMagick 7 offers it and ImageMagick 6 does not.
		                It weighs structural damage, where PSNR only sums
		                squared error and cannot tell a smeared face from an
		                evenly noisy sky.  Chosen automatically where present.

		Because the metric may differ from the server's, every result records
		which metric and threshold produced it.  41 dB and 0.995 SSIM both mean
		"almost indistinguishable" and neither can be read as the other.

		## Parallelism

		PHP has no fork on Windows, so `--jobs` re-runs this same script as N
		child processes, each taking every Nth file, and merges their result
		files at the end.  That works the same way on both platforms.
	*/

	class LocalExportCompressor {
		use ByteDisplay;
		use ImageFiles;
		use ImageQualitySearch;

			/*
				ImageFiles expects a domain for the paths it builds.  Nothing
				used here builds one -- the batch directory is the root -- but
				the property is set so a stray call fails visibly rather than
				composing a path out of an undefined value.
			*/

		public $domain = 'local-batch';

			/*
				CLIAccess supplies the constructor for every other tool here,
				and this one does not use CLIAccess -- it has no domain to
				prompt for, no banner and no database.  So it declares its own
				rather than inheriting a prompt it would never use.
			*/

		public $script_path = '';

		public function __construct($args) {
			$this->argv = $args['argv'];
		}

			// Entry Point
			// -----------------------------------------------

		public function compressExportLocally() {
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			if(strlen($this->arguments['directory']) === 0) {
				print('Which batch?  compress_export_locally.php /path/to/batch' . "\n\n");

				return FALSE;
			}

			if(!$this->requireImageMagick()) {
				return FALSE;
			}

			$manifest = $this->readManifest();

			if($manifest === FALSE) {
				return FALSE;
			}

			$this->applySearchSettings();

			if($this->arguments['shards'] > 1 && $this->arguments['shard'] < 0) {
				return $this->runShards(['manifest'=>$manifest]);
			}

			return $this->runOneShard(['manifest'=>$manifest]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'directory'=>'',
				'target'=>0.0,
				'metric'=>'',
				'regions'=>5,
				'floor'=>0,
				'shards'=>1,
				'shard'=>-1,
				'help'=>FALSE,
			];

			$skip_first = TRUE;

			foreach($this->argv as $argument) {
				if($skip_first) {
					$skip_first = FALSE;

					continue;
				}

				if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 9) === '--target=') {
					$arguments['target'] = (float)substr($argument, 9);
				} else if(substr($argument, 0, 9) === '--metric=') {
					$arguments['metric'] = substr($argument, 9);
				} else if(substr($argument, 0, 10) === '--regions=') {
					$arguments['regions'] = (int)substr($argument, 10);
				} else if(substr($argument, 0, 8) === '--floor=') {
					$arguments['floor'] = (int)substr($argument, 8);
				} else if(substr($argument, 0, 7) === '--jobs=') {
					$arguments['shards'] = (int)substr($argument, 7);
				} else if(substr($argument, 0, 8) === '--shard=') {
					$arguments['shard'] = (int)substr($argument, 8);
				} else if(substr($argument, 0, 2) !== '--') {
					$arguments['directory'] = rtrim(str_replace('\\', '/', $argument), '/') . '/';
				}
			}

			if($arguments['shards'] < 1) {
				$arguments['shards'] = 1;
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Local Export Compressor' . "\n\n");
			print('  compress_export_locally.php /path/to/batch [--jobs=8] [--regions=5]' . "\n");
			print('                              [--metric=SSIM] [--target=X] [--floor=55]' . "\n\n");
			print('  Compresses a batch written by export_image_candidates.php.' . "\n");
			print('  Writes compressed/ and results.tsv back into the batch directory.' . "\n\n");
			print('  --jobs     how many processes; one per core is reasonable' . "\n");
			print('  --regions  windows measured per file, worst kept (1-5)' . "\n");
			print('  --metric   SSIM where ImageMagick 7 offers it, else PSNR' . "\n");
			print('  --target   threshold for that metric; defaults per metric' . "\n\n");

			return TRUE;
		}

			// Settings
			// -----------------------------------------------

		public function applySearchSettings() {
			if(strlen($this->arguments['metric'])) {
				if(!$this->setMetric(['metric'=>$this->arguments['metric']])) {
					print('This ImageMagick does not offer ' . $this->arguments['metric'] . '.' . "\n");
					print('It offers: ' . implode(', ', array_keys($this->availableMetrics())) . "\n\n");

					exit(1);
				}
			}

			$this->setRegions(['regions'=>$this->arguments['regions']]);

			if($this->arguments['target'] <= 0.0) {
				$this->arguments['target'] = $this->qualitySearchDefaultTarget();
			}

			if($this->arguments['floor'] <= 0) {
				$this->arguments['floor'] = $this->qualitySearchFloor();
			}

			return TRUE;
		}

			// The manifest
			// -----------------------------------------------

		public function readManifest() {
			$path = $this->arguments['directory'] . 'manifest.tsv';

			if(!is_file($path)) {
				print('No manifest at ' . $path . "\n\n");

				return FALSE;
			}

			$contents = file($path, FILE_IGNORE_NEW_LINES);

			if($contents === FALSE) {
				print('Could not read ' . $path . "\n\n");

				return FALSE;
			}

			$manifest = [];

			foreach($contents as $line) {
				if(strlen(trim($line)) === 0 || substr($line, 0, 1) === '#') {
					continue;
				}

				$pieces = explode("\t", $line);

				if(count($pieces) < 6) {
					continue;
				}

				$manifest[] = [
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

			// Sharding
			// -----------------------------------------------

		public function runShards($args) {
			$manifest = $args['manifest'];

			$shards = $this->arguments['shards'];

			print('Batch:   ' . $this->arguments['directory'] . "\n");
			print('Files:   ' . count($manifest) . "\n");
			print('Metric:  ' . $this->chosenMetric() . ' at ' . $this->arguments['target'] . "\n");
			print('Regions: ' . $this->arguments['regions'] . "\n");
			print('Jobs:    ' . $shards . "\n\n");
			print('Working.' . "\n\n");

			$processes = [];

			for($shard = 0; $shard < $shards; $shard++) {
				$command = escapeshellarg(PHP_BINARY);
				$command .= ' ' . escapeshellarg($this->scriptPath());
				$command .= ' ' . escapeshellarg(rtrim($this->arguments['directory'], '/'));
				$command .= ' --shard=' . $shard;
				$command .= ' --jobs=' . $shards;
				$command .= ' --regions=' . $this->arguments['regions'];
				$command .= ' --target=' . $this->arguments['target'];
				$command .= ' --floor=' . $this->arguments['floor'];
				$command .= ' --metric=' . $this->chosenMetric();

				$descriptors = [
					1=>['pipe', 'w'],
					2=>['pipe', 'w'],
				];

				$pipes = [];

				$process = proc_open($command, $descriptors, $pipes);

				if(!is_resource($process)) {
					print('Could not start shard ' . $shard . '.' . "\n\n");

					continue;
				}

				$processes[] = ['process'=>$process, 'pipes'=>$pipes, 'shard'=>$shard];
			}

				/*
					Read each child to completion before waiting on it.  A
					child that fills its stdout pipe blocks forever, and
					proc_close on a blocked child never returns -- which looks
					exactly like a slow encode and is not.
				*/

			foreach($processes as $entry) {
				$output = stream_get_contents($entry['pipes'][1]);
				$errors = stream_get_contents($entry['pipes'][2]);

				fclose($entry['pipes'][1]);
				fclose($entry['pipes'][2]);

				proc_close($entry['process']);

				if(strlen(trim($errors))) {
					print('shard ' . $entry['shard'] . ': ' . trim($errors) . "\n");
				}
			}

			return $this->mergeShardResults(['shards'=>$shards]);
		}

			/*
				Held as its own property rather than in the arguments array,
				because compressExportLocally() calls setArguments() and that
				rebuilds the array from scratch.  A path stored there would be
				set by the entry point and gone by the time the shards needed
				it, which fails as an empty command line rather than as an
				error.
			*/

		public function scriptPath() {
			if(strlen($this->script_path)) {
				return $this->script_path;
			}

			return $this->argv[0];
		}

		public function setScriptPath($args) {
			return $this->script_path = $args['path'];
		}

		public function mergeShardResults($args) {
			$directory = $this->arguments['directory'];

			$lines = [];
			$found = 0;

			for($shard = 0; $shard < $args['shards']; $shard++) {
				$path = $directory . 'results.' . $shard . '.tsv';

				if(!is_file($path)) {
					continue;
				}

				foreach(file($path, FILE_IGNORE_NEW_LINES) as $line) {
					if(strlen(trim($line)) === 0 || substr($line, 0, 1) === '#') {
						continue;
					}

					$lines[] = $line;
				}

				unlink($path);

				$found++;
			}

			$this->writeResults(['lines'=>$lines, 'path'=>$directory . 'results.tsv']);

			print('Merged ' . $found . ' shard files into results.tsv -- ' . count($lines) . ' rows.' . "\n\n");

			return $this->summarise(['lines'=>$lines]);
		}

			// The work
			// -----------------------------------------------

		public function runOneShard($args) {
			$manifest = $args['manifest'];

			$shard = $this->arguments['shard'];
			$shards = $this->arguments['shards'];

			$single = ($shard < 0);

			if($single) {
				$shard = 0;
				$shards = 1;

				print('Batch:   ' . $this->arguments['directory'] . "\n");
				print('Files:   ' . count($manifest) . "\n");
				print('Metric:  ' . $this->chosenMetric() . ' at ' . $this->arguments['target'] . "\n");
				print('Regions: ' . $this->arguments['regions'] . "\n\n");
				print('Working.' . "\n\n");
			}

			$directory = $this->arguments['directory'];
			$files_directory = $directory . 'files/';
			$out_directory = $directory . 'compressed/';

			$lines = [];

			foreach($manifest as $index=>$entry) {
				if(($index % $shards) !== $shard) {
					continue;
				}

				$lines[] = $this->processOne([
					'entry'=>$entry,
					'files_directory'=>$files_directory,
					'out_directory'=>$out_directory,
				]);
			}

			$path = $single
				? ($directory . 'results.tsv')
				: ($directory . 'results.' . $shard . '.tsv');

			$this->writeResults(['lines'=>$lines, 'path'=>$path]);

			if(!$single) {
				return TRUE;
			}

			return $this->summarise(['lines'=>$lines]);
		}

		public function processOne($args) {
			$entry = $args['entry'];

			$source = $args['files_directory'] . $entry['relative'];

			if(!is_file($source)) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'missing-from-batch']);
			}

				/*
					The batch file is checked against the manifest before any
					work is done on it.  A transfer that truncated a file would
					otherwise be compressed happily, and the result would be a
					perfectly valid JPEG of the first two thirds of a painting.
				*/

			if(hash_file('sha256', $source) !== $entry['sha256']) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'batch-file-does-not-match-manifest']);
			}

			$outcome = $this->searchQuality([
				'path'=>$source,
				'target'=>$this->arguments['target'],
				'floor'=>$this->arguments['floor'],
			]);

			if($outcome['status'] !== 'found') {
				return $this->resultLine(['entry'=>$entry, 'status'=>$outcome['status']]);
			}

			$target = $args['out_directory'] . $entry['relative'];
			$target_directory = dirname($target);

			if(!is_dir($target_directory) && !mkdir($target_directory, 0755, TRUE)) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'could-not-create-output-directory']);
			}

			$encoded = $this->encodeToTemporary([
				'path'=>$source,
				'quality'=>$outcome['quality'],
			]);

			if(!$encoded) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'final-encode-failed']);
			}

			$check = $this->identifyImage(['path'=>$encoded]);

			if(!$check || $check['width'] !== $entry['width'] || $check['height'] !== $entry['height']) {
				unlink($encoded);

				return $this->resultLine(['entry'=>$entry, 'status'=>'dimensions-changed']);
			}

			if(filesize($encoded) >= $entry['size']) {
				unlink($encoded);

				return $this->resultLine(['entry'=>$entry, 'status'=>'not-smaller']);
			}

			if(!rename($encoded, $target)) {
				if(!copy($encoded, $target)) {
					unlink($encoded);

					return $this->resultLine(['entry'=>$entry, 'status'=>'could-not-write-result']);
				}

				unlink($encoded);
			}

			return $this->resultLine([
				'entry'=>$entry,
				'status'=>'compressed',
				'new_size'=>filesize($target),
				'new_sha256'=>hash_file('sha256', $target),
				'quality'=>$outcome['quality'],
				'metric_value'=>$outcome['metric'],
			]);
		}

		public function resultLine($args) {
			$entry = $args['entry'];

			return implode("\t", [
				$entry['relative'],
				$args['status'],
				$entry['size'],
				array_key_exists('new_size', $args) ? $args['new_size'] : 0,
				$entry['sha256'],
				array_key_exists('new_sha256', $args) ? $args['new_sha256'] : '',
				array_key_exists('quality', $args) ? $args['quality'] : 0,
				array_key_exists('metric_value', $args) ? $args['metric_value'] : '',
				$this->chosenMetric(),
				$this->arguments['target'],
			]);
		}

		public function writeResults($args) {
			$header = '# relative_path' . "\t" . 'status' . "\t" . 'original_size' . "\t" . 'new_size';
			$header .= "\t" . 'original_sha256' . "\t" . 'new_sha256' . "\t" . 'quality';
			$header .= "\t" . 'metric_value' . "\t" . 'metric' . "\t" . 'target' . "\n";

			return file_put_contents($args['path'], $header . implode("\n", $args['lines']) . "\n");
		}

			// Summary
			// -----------------------------------------------

		public function summarise($args) {
			$before = 0;
			$after = 0;
			$compressed = 0;
			$statuses = [];

			foreach($args['lines'] as $line) {
				$pieces = explode("\t", $line);

				if(count($pieces) < 4) {
					continue;
				}

				$status = $pieces[1];

				if(!array_key_exists($status, $statuses)) {
					$statuses[$status] = 0;
				}

				$statuses[$status]++;

				$before += (int)$pieces[2];

				if($status === 'compressed') {
					$after += (int)$pieces[3];
					$compressed++;

					continue;
				}

				$after += (int)$pieces[2];
			}

			print('Compressed ' . $compressed . ' files: ');
			print($this->formatBytes(['number'=>$before]) . ' becomes ');
			print($this->formatBytes(['number'=>$after]) . ', saving ');
			print($this->formatBytes(['number'=>$before - $after]) . ' (');
			print($this->formatSavedPercent(['before'=>$before, 'after'=>$after]));
			print(').' . "\n\n");

			foreach($statuses as $status=>$count) {
				if($status === 'compressed') {
					continue;
				}

				print('  ' . $count . ' ' . $status . "\n");
			}

			print("\n" . 'Send it back, then on the server:' . "\n\n");
			print('  import_compressed_images.php DOMAIN --from=/path/to/batch' . "\n\n");

			return TRUE;
		}
	}

?>

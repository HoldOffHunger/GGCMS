<?php

	clireq('traits/ByteDisplay.php');
	clireq('traits/ImageFiles.php');

	/*
		The compute half of the upscale pipeline, run anywhere with a graphics
		card.

		Reads a batch written by export_upscale_candidates.php, runs each
		original through Real-ESRGAN, fits the result inside --max, and writes
		it back into the batch directory for import_upscaled_images.php to
		check and install.

		Like LocalExportCompressor it has no database, no domain and no notion
		of /srv.  Everything it needs is in the batch.

		## What the upscaler is

		Real-ESRGAN, in its ncnn-vulkan build: one portable executable and a
		folder of models, running on any GPU with Vulkan.  It is a generative
		network -- the detail it adds is not recovered, it is invented, by a
		model trained to guess what a sharper version would have looked like.
		On a woodcut or a poster that is very nearly free.  On a photograph of
		a real person it can return someone subtly not quite them, which is
		why the import writes every file it installs into a ledger of its own.

		The model always works at four times.  The result is then scaled down
		to fit --max, by ImageMagick, so the network's guess is averaged
		rather than shown pixel for pixel.

		## It refuses rotated JPEGs

		A JPEG carrying an EXIF orientation is displayed rotated and stored
		sideways.  The upscaler does not read the tag and does not keep it,
		so its output would be the stored, sideways picture with nothing to
		say so -- and the row's dimensions would be swapped against what the
		reader sees.  Those are reported as has-exif-rotation and left alone.

		## No --jobs

		The work is on the GPU, which is one device and already parallel.
		Several processes would only queue on it.
	*/

	class LocalExportUpscaler {
		use ByteDisplay;
		use ImageFiles;

		public $argv;
		public $arguments;

			/*
				Set for the same reason as LocalExportCompressor's: nothing
				here builds a domain path, so a stray call should fail
				visibly.
			*/

		public $domain = 'local-batch';

		public function __construct($args) {
			$this->argv = $args['argv'];
		}

			// Entry Point
			// -----------------------------------------------

		public function upscaleExportLocally() {
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			if(strlen($this->arguments['directory']) === 0) {
				print('Which batch?  upscale_export_locally.php /path/to/batch --upscaler=/path/to/realesrgan-ncnn-vulkan' . "\n\n");

				return FALSE;
			}

			if(!$this->requireImageMagick()) {
				return FALSE;
			}

			if(!$this->requireUpscaler()) {
				return FALSE;
			}

			$manifest = $this->readManifest();

			if($manifest === FALSE) {
				return FALSE;
			}

			return $this->upscaleBatch(['manifest'=>$manifest]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'directory'=>'',
				'upscaler'=>'',
				'model'=>'realesrgan-x4plus',
				'max'=>2000,
				'quality'=>90,
				'limit'=>0,
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
				} else if(substr($argument, 0, 11) === '--upscaler=') {
					$arguments['upscaler'] = str_replace('\\', '/', substr($argument, 11));
				} else if(substr($argument, 0, 8) === '--model=') {
					$arguments['model'] = substr($argument, 8);
				} else if(substr($argument, 0, 6) === '--max=') {
					$arguments['max'] = (int)substr($argument, 6);
				} else if(substr($argument, 0, 10) === '--quality=') {
					$arguments['quality'] = (int)substr($argument, 10);
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				} else if(substr($argument, 0, 2) !== '--') {
					$arguments['directory'] = rtrim(str_replace('\\', '/', $argument), '/') . '/';
				}
			}

				/*
					The environment is the fallback, so a desktop that always
					keeps the upscaler in one place can say so once.
				*/

			if(strlen($arguments['upscaler']) === 0) {
				$arguments['upscaler'] = str_replace('\\', '/', (string)getenv('GGCMS_UPSCALER'));
			}

			if($arguments['max'] < 100) {
				$arguments['max'] = 2000;
			}

			if($arguments['quality'] < 1 || $arguments['quality'] > 100) {
				$arguments['quality'] = 90;
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Local Export Upscaler' . "\n\n");
			print('  upscale_export_locally.php /path/to/batch --upscaler=/path/to/realesrgan-ncnn-vulkan' . "\n");
			print('                             [--model=realesrgan-x4plus] [--max=2000]' . "\n");
			print('                             [--quality=90] [--limit=N]' . "\n\n");
			print('  Upscales a batch written by export_upscale_candidates.php.' . "\n");
			print('  Writes upscaled/ and results.tsv back into the batch directory.' . "\n\n");
			print('  --upscaler  the Real-ESRGAN ncnn-vulkan executable; or set GGCMS_UPSCALER' . "\n");
			print('  --model     realesrgan-x4plus for photographs and scans,' . "\n");
			print('              realesrgan-x4plus-anime for line art and illustration' . "\n");
			print('  --max       the result is fitted inside this many pixels square' . "\n");
			print('  --quality   JPEG quality of the result; PNGs stay lossless' . "\n\n");

			return TRUE;
		}

			// The upscaler
			// -----------------------------------------------

		public function upscalerModelDirectory() {
			return dirname($this->arguments['upscaler']) . '/models/';
		}

			/*
				Checked before the first file rather than discovered on it.
				An executable that is missing, or a model name with no files,
				otherwise fails every row identically and the batch reads as a
				tree full of upscale-failed.
			*/

		public function requireUpscaler() {
			$upscaler = $this->arguments['upscaler'];

			if(strlen($upscaler) === 0) {
				print('Where is Real-ESRGAN?  Pass --upscaler=/path/to/realesrgan-ncnn-vulkan, or set GGCMS_UPSCALER.' . "\n\n");
				print('  https://github.com/xinntao/Real-ESRGAN/releases -- the ncnn-vulkan zip for this platform.' . "\n\n");

				return FALSE;
			}

			if(!is_file($upscaler)) {
				print('No upscaler at ' . $upscaler . "\n\n");

				return FALSE;
			}

			$param = $this->upscalerModelDirectory() . $this->arguments['model'] . '.param';

			if(!is_file($param)) {
				print('No model named ' . $this->arguments['model'] . ' -- expected ' . $param . "\n\n");

				return FALSE;
			}

			return TRUE;
		}

		public function runUpscaler($args) {
			$command = escapeshellarg($this->arguments['upscaler']);
			$command .= ' -i ' . escapeshellarg($args['source']);
			$command .= ' -o ' . escapeshellarg($args['target']);
			$command .= ' -s 4';
			$command .= ' -m ' . escapeshellarg(rtrim($this->upscalerModelDirectory(), '/'));
			$command .= ' -n ' . escapeshellarg($this->arguments['model']);
			$command .= ' -f png';
			$command .= ' 2>' . $this->nullDevice();

			shell_exec($command);

			return is_file($args['target']) && filesize($args['target']) > 0;
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
					'sha256'=>$pieces[4],
					'image_id'=>(int)$pieces[5],
				];
			}

			return $manifest;
		}

			// The work
			// -----------------------------------------------

		public function upscaleBatch($args) {
			$manifest = $args['manifest'];

			if($this->arguments['limit'] && count($manifest) > $this->arguments['limit']) {
				$manifest = array_slice($manifest, 0, $this->arguments['limit']);
			}

			$directory = $this->arguments['directory'];

			print('Batch:    ' . $directory . "\n");
			print('Files:    ' . count($manifest) . "\n");
			print('Model:    ' . $this->arguments['model'] . "\n");
			print('Fit in:   ' . $this->arguments['max'] . 'x' . $this->arguments['max'] . "\n\n");

			$lines = [];
			$done = 0;

			foreach($manifest as $entry) {
				$lines[] = $this->processOne([
					'entry'=>$entry,
					'files_directory'=>$directory . 'files/',
					'out_directory'=>$directory . 'upscaled/',
				]);

				$done++;

				if($done % 10 === 0) {
					print('  ' . $done . ' of ' . count($manifest) . "\n");
				}
			}

			$this->writeResults(['lines'=>$lines, 'path'=>$directory . 'results.tsv']);

			return $this->summarise(['lines'=>$lines]);
		}

			/*
				PNG: for PNGs, so alpha and line art stay lossless.  JPEG: for
				the rest, with the prefix rather than the extension deciding,
				because ImageMagick does not know .jfif as an output format and
				would guess.
			*/

		public function outputFormatPrefix($args) {
			if($this->extensionOf(['path'=>$args['path']]) === 'png') {
				return 'PNG:';
			}

			return 'JPEG:';
		}

		public function exifOrientation($args) {
			$command = $this->imageMagickCommand(['tool'=>'identify']);
			$command .= ' -ping -format "%[orientation]" ' . escapeshellarg($args['path'] . '[0]') . ' 2>' . $this->nullDevice();

			return trim((string)shell_exec($command));
		}

		public function processOne($args) {
			$entry = $args['entry'];

			$source = $args['files_directory'] . $entry['relative'];

			if(!is_file($source)) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'missing-from-batch']);
			}

			if(hash_file('sha256', $source) !== $entry['sha256']) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'batch-file-does-not-match-manifest']);
			}

			$orientation = $this->exifOrientation(['path'=>$source]);

			if(strlen($orientation) && $orientation !== 'Undefined' && $orientation !== 'TopLeft') {
				return $this->resultLine(['entry'=>$entry, 'status'=>'has-exif-rotation']);
			}

			$target = $args['out_directory'] . $entry['relative'];
			$target_directory = dirname($target);

			if(!is_dir($target_directory) && !mkdir($target_directory, 0755, TRUE)) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'could-not-create-output-directory']);
			}

			$raw = $target . '.ggcms_raw.png';

			if(!$this->runUpscaler(['source'=>$source, 'target'=>$raw])) {
				if(is_file($raw)) {
					unlink($raw);
				}

				return $this->resultLine(['entry'=>$entry, 'status'=>'upscale-failed']);
			}

			$max = $this->arguments['max'];

			$command = $this->imageMagickCommand(['tool'=>'convert']);
			$command .= ' ' . escapeshellarg($raw);
			$command .= ' -resize ' . escapeshellarg($max . 'x' . $max . '>');

			if($this->outputFormatPrefix(['path'=>$target]) === 'JPEG:') {
				$command .= ' -quality ' . (int)$this->arguments['quality'];
			}

			$command .= ' ' . escapeshellarg($this->outputFormatPrefix(['path'=>$target]) . $target);
			$command .= ' 2>' . $this->nullDevice();

			shell_exec($command);

			unlink($raw);

			if(!is_file($target) || filesize($target) === 0) {
				return $this->resultLine(['entry'=>$entry, 'status'=>'final-encode-failed']);
			}

			$check = $this->identifyImage(['path'=>$target]);

			if(!$check) {
				unlink($target);

				return $this->resultLine(['entry'=>$entry, 'status'=>'result-not-readable']);
			}

				/*
					Checked here as well as on import, so a bad result is
					reported where it was made rather than a network trip
					later.  The import does not take this check's word for it.
				*/

			if($check['width'] <= $entry['width'] || $check['height'] <= $entry['height']) {
				unlink($target);

				return $this->resultLine(['entry'=>$entry, 'status'=>'not-larger']);
			}

			$before_ratio = $entry['width'] / $entry['height'];
			$after_ratio = $check['width'] / $check['height'];

			if(abs($before_ratio - $after_ratio) / $before_ratio > 0.01) {
				unlink($target);

				return $this->resultLine(['entry'=>$entry, 'status'=>'aspect-ratio-changed']);
			}

			return $this->resultLine([
				'entry'=>$entry,
				'status'=>'upscaled',
				'new_size'=>filesize($target),
				'new_sha256'=>hash_file('sha256', $target),
				'new_width'=>$check['width'],
				'new_height'=>$check['height'],
			]);
		}

		public function resultLine($args) {
			$entry = $args['entry'];

			return implode("\t", [
				$entry['relative'],
				$args['status'],
				$entry['image_id'],
				$entry['size'],
				array_key_exists('new_size', $args) ? $args['new_size'] : 0,
				$entry['sha256'],
				array_key_exists('new_sha256', $args) ? $args['new_sha256'] : '',
				$entry['width'] . 'x' . $entry['height'],
				array_key_exists('new_width', $args) ? $args['new_width'] . 'x' . $args['new_height'] : '',
				$this->arguments['model'],
			]);
		}

		public function writeResults($args) {
			$header = '# relative_path' . "\t" . 'status' . "\t" . 'image_id' . "\t" . 'original_size';
			$header .= "\t" . 'new_size' . "\t" . 'original_sha256' . "\t" . 'new_sha256';
			$header .= "\t" . 'original_dimensions' . "\t" . 'new_dimensions' . "\t" . 'model' . "\n";

			return file_put_contents($args['path'], $header . implode("\n", $args['lines']) . "\n");
		}

			// Summary
			// -----------------------------------------------

		public function summarise($args) {
			$upscaled = 0;
			$statuses = [];

			foreach($args['lines'] as $line) {
				$pieces = explode("\t", $line);

				if(count($pieces) < 2) {
					continue;
				}

				if($pieces[1] === 'upscaled') {
					$upscaled++;

					continue;
				}

				if(!array_key_exists($pieces[1], $statuses)) {
					$statuses[$pieces[1]] = 0;
				}

				$statuses[$pieces[1]]++;
			}

			print("\n" . 'Upscaled ' . $upscaled . ' of ' . count($args['lines']) . ' files.' . "\n\n");

			foreach($statuses as $status=>$count) {
				print('  ' . $count . ' ' . $status . "\n");
			}

			if($statuses) {
				print("\n");
			}

			print('Look at upscaled/ before sending it anywhere.  The detail is invented.' . "\n\n");
			print('Then send it back, and on the server:' . "\n\n");
			print('  import_upscaled_images.php DOMAIN --from=/path/to/batch' . "\n\n");

			return TRUE;
		}
	}

?>

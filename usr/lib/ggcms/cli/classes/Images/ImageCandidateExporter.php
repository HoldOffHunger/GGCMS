<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	/*
		Packaging the files worth compressing, so the compressing can happen
		somewhere with a processor.

		The droplet is one vCPU and 2 GB shared by seventeen sites.  Working
		through revoltlib's 491 large JPEGs there is roughly sixteen hours of
		niced CPU contending with Apache the whole way.  The same files move to
		a desktop in about five and a half minutes at the measured 10 MB/s, are
		compressed in a fraction of the time on twenty-four cores, and come
		back in about four.

		This tool writes the outbound half.  It copies nothing but candidates,
		and it records enough about each one that the import can refuse
		anything that does not match.

		## The manifest is the contract

		Relative path, size, width, height, source quality and SHA-256, one row
		per file.  Every one of those is checked again on the way back in:

		  the hash    the live file must still be the file that went out, or
		              something changed while the batch was away and the result
		              is answering a question about a file that no longer exists
		  dimensions  a returned file whose dimensions moved would make every
		              stored PixelWidth a lie
		  size        a result that is not smaller is not a saving

		The importing side trusts none of it on faith; see
		import_compressed_images.php.  The desktop is a processor, not an
		authority.
	*/

	class ImageCandidateExporter {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;

			// Entry Point
			// -----------------------------------------------

		public function exportImageCandidates() {
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

			if(strlen($this->arguments['directory']) === 0) {
				print('Where should the batch be written?  Pass --to=/path/to/batch' . "\n\n");

				return FALSE;
			}

			$candidates = $this->walkImageFiles([
				'minimum_size'=>$this->arguments['minimum_size'],
				'limit'=>$this->arguments['limit'],
				'recompressible_only'=>TRUE,
			]);

			if(!$candidates) {
				print('Nothing matched.  Try a smaller --min-size.' . "\n\n");

				return FALSE;
			}

			$candidates = $this->withoutAlreadyCompressed(['candidates'=>$candidates]);

			if(!$candidates) {
				print('Every matching file has already been compressed by this pipeline.' . "\n\n");

				return FALSE;
			}

			$bytes = 0;

			foreach($candidates as $candidate) {
				$bytes += $candidate['size'];
			}

			print('Source:      ' . $this->imageDirectory() . "\n");
			print('Batch:       ' . $this->arguments['directory'] . "\n");
			print('Files:       ' . count($candidates) . "\n");
			print('Size:        ' . $this->formatBytes(['number'=>$bytes]) . "\n\n");

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been written.' . "\n");
				print('Add --apply to write the batch.' . "\n\n");

				return TRUE;
			}

			return $this->writeBatch([
				'candidates'=>$candidates,
				'bytes'=>$bytes,
			]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'directory'=>'',
				'minimum_size'=>1048576,
				'limit'=>0,
				'apply'=>FALSE,
				'force'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--force') {
					$arguments['force'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 5) === '--to=') {
					$arguments['directory'] = rtrim(substr($argument, 5), '/') . '/';
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
			print('GGCMS - Image Candidate Export' . "\n\n");
			print('  export_image_candidates.php DOMAIN --to=/path/to/batch' . "\n");
			print('                                     [--min-size=1M] [--limit=N]' . "\n");
			print('                                     [--force] [--apply]' . "\n\n");
			print('  Writes the JPEGs worth compressing, plus a manifest of sizes,' . "\n");
			print('  dimensions and SHA-256s, for compressing on another machine.' . "\n\n");
			print('  --force  include files this pipeline has already compressed' . "\n\n");
			print('  Then, on the other machine:' . "\n\n");
			print('    compress_export_locally.php /path/to/batch --jobs=8' . "\n\n");
			print('  and back here:' . "\n\n");
			print('    import_compressed_images.php DOMAIN --from=/path/to/batch' . "\n\n");

			return TRUE;
		}

			// The ledger
			// -----------------------------------------------

			/*
				The same ledger the in-place compressor keeps, and for the same
				reason: a file this pipeline has already written should not go
				round again, because JPEG generation loss is cumulative and
				invisible one step at a time.  A file whose hash no longer
				matches its entry has been restored or edited since, and is a
				candidate again.
			*/

		public function withoutAlreadyCompressed($args) {
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
				print('Skipping ' . $skipped . ' files this pipeline has already compressed.' . "\n");
				print('--force would send them round again, at the cost of another generation.' . "\n\n");
			}

			return $kept;
		}

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

				if(count($pieces) < 2) {
					continue;
				}

				$ledger[$pieces[0]] = $pieces[1];
			}

			return $ledger;
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

			$free = disk_free_space($directory);

			if($free !== FALSE && $free < ($args['bytes'] * 1.1)) {
				print('Not enough room at ' . $directory . ' -- ');
				print($this->formatBytes(['number'=>$free]) . ' free, need about ');
				print($this->formatBytes(['number'=>$args['bytes'] * 1.1]) . '.' . "\n\n");

				return FALSE;
			}

			$manifest_lines = [];
			$copied = 0;
			$failures = [];

			foreach($candidates as $candidate) {
				$identified = $this->identifyImage(['path'=>$candidate['path']]);

				if(!$identified) {
					$failures[] = ['File'=>$candidate['relative'], 'Why'=>'could not be identified'];

					continue;
				}

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
						Hashed from the copy rather than the original.  The
						import compares this against the live file, so a copy
						that went wrong here has to fail here, not silently
						become the thing everything downstream is compared to.
					*/

				$hash = hash_file('sha256', $target);

				if($hash === FALSE || filesize($target) !== $candidate['size']) {
					$failures[] = ['File'=>$candidate['relative'], 'Why'=>'copy did not match the original'];

					continue;
				}

				$manifest_lines[] = implode("\t", [
					$candidate['relative'],
					$candidate['size'],
					$identified['width'],
					$identified['height'],
					$identified['quality'],
					$hash,
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
			print('  compress_export_locally.php ' . basename(rtrim($directory, '/')) . ' --jobs=8' . "\n\n");

			return TRUE;
		}

		public function writeManifest($args) {
			$directory = $args['directory'];

			$contents = '# relative_path' . "\t" . 'size' . "\t" . 'width' . "\t" . 'height';
			$contents .= "\t" . 'source_quality' . "\t" . 'sha256' . "\n";
			$contents .= '# domain: ' . $this->domain . "\n";
			$contents .= '# exported: ' . date('c') . "\n";
			$contents .= implode("\n", $args['lines']) . "\n";

			return file_put_contents($directory . 'manifest.tsv', $contents);
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Candidate Export';
		}

		public function confirmDomainText() {
			return 'Exporting compression candidates for: ';
		}
	}

?>

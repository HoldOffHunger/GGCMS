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
		What re-encoding would do, before anything is re-encoded.

		This tool writes nothing into the image tree.  It runs the same quality
		search the compressor runs, on the same files, with the same encoder
		settings, and prints the answer -- per file, and as a total.  Because
		the search and the writer share encodeToTemporary(), the sizes reported
		here are the sizes that would be installed, not an estimate of them.

		It is the tool for arguing with the threshold.  --target moves the
		fidelity bar and the table moves with it, so the number can be chosen
		against real files instead of taken on faith from a manual.

		Cost is the reason for the small default.  Each file costs about six
		encodes plus a crop and a compare for each, and on one vCPU a large
		scan is several seconds.  Twenty files is a couple of minutes; the
		whole tree is not a thing to ask for at a prompt.
	*/

	class ImageCompressionChecker {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ImageQualitySearch;

			// Entry Point
			// -----------------------------------------------

		public function checkImageCompression() {
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

			$candidates = $this->walkImageFiles([
				'minimum_size'=>$this->arguments['minimum_size'],
				'recompressible_only'=>TRUE,
			]);

			if(!$candidates) {
				print('No JPEGs at or above ' . $this->formatBytes(['number'=>$this->arguments['minimum_size']]) . '.' . "\n\n");

				return FALSE;
			}

			$candidate_bytes = 0;

			foreach($candidates as $candidate) {
				$candidate_bytes += $candidate['size'];
			}

			$limit = $this->arguments['limit'];
			$sample = ($limit > 0 && count($candidates) > $limit) ? array_slice($candidates, 0, $limit) : $candidates;

			print('Matching JPEGs: ' . count($candidates) . ', ' . $this->formatBytes(['number'=>$candidate_bytes]) . "\n");
			print('Testing:        ' . count($sample) . ' of them (largest first)' . "\n");
			print('Target:         ' . number_format($this->arguments['target'], 1) . ' dB PSNR');
			print(', quality floor ' . $this->arguments['floor'] . "\n\n");
			print('Working.  Roughly six encodes per file.' . "\n\n");

			$results = $this->testSample(['sample'=>$sample]);

			$this->reportResults(['results'=>$results]);

			return $this->reportProjection([
				'results'=>$results,
				'candidate_count'=>count($candidates),
				'candidate_bytes'=>$candidate_bytes,
			]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'minimum_size'=>1048576,
				'limit'=>20,
				'target'=>$this->qualitySearchDefaultTarget(),
				'floor'=>$this->qualitySearchFloor(),
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--all') {
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
			print('GGCMS - Image Compression Check' . "\n\n");
			print('  check_image_compression.php DOMAIN [--min-size=1M] [--limit=20]' . "\n");
			print('                                     [--target=41.0] [--floor=55] [--all]' . "\n\n");
			print('  Runs the quality search and reports what would be saved.  Writes nothing.' . "\n\n");
			print('  --min-size  ignore files below this size; default 1M' . "\n");
			print('  --limit     how many of the largest to test; default 20' . "\n");
			print('  --target    PSNR in dB the re-encode must clear; higher is stricter' . "\n");
			print('  --floor     never search below this JPEG quality' . "\n\n");

			return TRUE;
		}

			// Testing
			// -----------------------------------------------

		public function testSample($args) {
			$sample = $args['sample'];

			$results = [];

			foreach($sample as $file) {
				$outcome = $this->searchQuality([
					'path'=>$file['path'],
					'target'=>$this->arguments['target'],
					'floor'=>$this->arguments['floor'],
				]);

				$outcome['file'] = $file;

				$results[] = $outcome;
			}

			return $results;
		}

			// Reporting
			// -----------------------------------------------

		public function reportResults($args) {
			$results = $args['results'];

			$limit = $this->arguments['all'] ? 0 : 25;
			$shown = ($limit > 0 && count($results) > $limit) ? array_slice($results, 0, $limit) : $results;

			$table = [];

			foreach($shown as $result) {
				$row = [
					'Now'=>$this->formatBytes(['number'=>$result['file']['size']]),
					'Would be'=>'-',
					'Saved'=>'-',
					'q'=>'-',
					'PSNR'=>'-',
					'File'=>$result['file']['relative'],
				];

				if($result['status'] === 'found') {
					$row['Would be'] = $this->formatBytes(['number'=>$result['new_size']]);
					$row['Saved'] = $this->formatSavedPercent([
						'before'=>$result['original_size'],
						'after'=>$result['new_size'],
					]);
					$row['q'] = $result['source_quality'] . '->' . $result['quality'];
					$row['PSNR'] = number_format($result['metric'], 1);
				} else {
					$row['Would be'] = $result['status'];
				}

				$table[] = $row;
			}

			print(arr2textTable($table));

			if(count($shown) < count($results)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($results) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

			/*
				The projection is an extrapolation and is labelled as one.

				Files are tested largest first, and large files compress
				proportionally better than small ones -- they are the untouched
				camera originals, while the small ones have usually been
				through an encoder already.  So the measured rate is an
				optimistic estimate of the rate across everything below it, and
				the honest thing is to say so next to the number rather than
				print a total that looks surveyed.
			*/

		public function reportProjection($args) {
			$results = $args['results'];
			$candidate_count = $args['candidate_count'];
			$candidate_bytes = $args['candidate_bytes'];

			$tested_before = 0;
			$tested_after = 0;
			$found = 0;
			$skipped = [];

				/*
					sample_bytes counts every file tested, refusals included.
					The projection divides by it rather than by the bytes of
					the successes alone, because a refusal is a real outcome
					that saves nothing, and the files below the sample will
					refuse at their own rate too.

					The first version divided by the successes and reported
					1.4 GB for revoltlib off a sample where seven of twelve
					files had declined -- a number reached by pretending the
					sample was the five that worked.
				*/

			$sample_bytes = 0;

			foreach($results as $result) {
				$sample_bytes += $result['file']['size'];

				if($result['status'] !== 'found') {
					if(!array_key_exists($result['status'], $skipped)) {
						$skipped[$result['status']] = 0;
					}

					$skipped[$result['status']]++;

					continue;
				}

				$tested_before += $result['original_size'];
				$tested_after += $result['new_size'];
				$found++;
			}

			if(!$found) {
				print('Nothing in the sample could be improved at this target.' . "\n\n");

				return TRUE;
			}

			$saved = $tested_before - $tested_after;

			print('Measured on ' . $found . ' files: ');
			print($this->formatBytes(['number'=>$tested_before]) . ' becomes ');
			print($this->formatBytes(['number'=>$tested_after]) . ', saving ');
			print($this->formatBytes(['number'=>$saved]) . ' (');
			print($this->formatSavedPercent(['before'=>$tested_before, 'after'=>$tested_after]));
			print(').' . "\n");

			if($skipped) {
				$notes = [];

				foreach($skipped as $status=>$count) {
					$notes[] = $count . ' ' . $status;
				}

				print('Not improved: ' . implode(', ', $notes) . '.' . "\n");
			}

			if(count($results) < $candidate_count) {
				$rate = $sample_bytes > 0 ? ($saved / $sample_bytes) : 0;
				$projected = $candidate_bytes * $rate;

				print("\n");
				print('Across all ' . $candidate_count . ' matching files (');
				print($this->formatBytes(['number'=>$candidate_bytes]) . ') the same rate would save about ');
				print($this->formatBytes(['number'=>$projected]) . '.' . "\n");
				print('That rate is ' . $found . ' of ' . count($results) . ' sampled files improving, ');
				print('measured across all ' . $this->formatBytes(['number'=>$sample_bytes]) . ' tested.' . "\n");
				print('Still the optimistic end -- the largest files were sampled and they' . "\n");
				print('compress best.  Raise --limit to narrow it.' . "\n");
			}

			print("\n");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Compression Check';
		}

		public function confirmDomainText() {
			return 'Checking compression for: ';
		}
	}

?>

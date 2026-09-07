<?php

	/*
		Choosing a JPEG quality per image, rather than picking one number and
		hoping.

		The idea is the one jpeg-recompress uses: re-encode at several
		qualities, measure each result against the original with a fidelity
		metric, and keep the smallest file that still clears a threshold.  A
		flat woodcut scan settles far lower than a noisy photograph, and
		neither has to be guessed at.

		jpeg-recompress itself is not packaged for Ubuntu 20.04 and building it
		pulls in mozjpeg, so the search lives here and runs on ImageMagick,
		which is already installed.

		## Why PSNR and not SSIM or PHASH

		SSIM is the right metric and this ImageMagick does not have it.  6.9.10
		lists AE, Fuzz, MAE, MEPP, MSE, NCC, PAE, PHASH, PSNR and RMSE, and
		nothing else.

		PHASH was measured before it was trusted, and it is unusable for this.
		Against a 38 MB scan its distance from the original ran 0.03 at quality
		95, 1.43 at 90, 0.17 at 80, 4.36 at 75 and 0.33 at 60 -- not merely
		noisy but non-monotonic, so a search would have called quality 60
		acceptable and quality 90 not.  A perceptual hash answers "is this the
		same picture", which is a different question from "how much has this
		degraded", and it answers the first one well.

		PSNR measures pixel error and falls as quality falls.  It is blind to
		where the error sits, which is what SSIM would have added, so the
		default threshold is deliberately conservative to buy that back.

		## Why the comparison is a crop and not a resize

		compare holds both images decoded and this host has 2 GB.  A 7360x4912
		scan is 36 megapixels, and the first calibration run died with "cache
		resources exhausted" on every single quality step.

		The obvious fix was to compare downscaled copies, and it was measured
		and rejected.  Downscaling averages away exactly the artifacts being
		looked for: across quality 85 down to 65 the whole PSNR range was
		1.8 dB, which is not enough separation to choose between them.

		Comparing a full-resolution centre crop instead keeps every artifact at
		the size it will actually be stored, and separates the same range by
		3.4 dB.  Measured on one 3024x4032 scan:

		    quality   bytes      PSNR (1200px centre crop)
		    95        2,925,161  52.88
		    90        2,313,263  43.34
		    85        1,838,903  41.88
		    80        1,521,203  41.20
		    75        1,315,074  40.30
		    70        1,207,991  39.95
		    65        1,108,977  39.42
		    60        1,017,026  38.75
		    50          901,415  37.94

		The crop is taken at read time -- convert 'file.jpg[1200x1200+X+Y]' --
		which never holds the whole image.  On the 36-megapixel scan that is
		48 MB of peak RSS and 2.2 seconds, against a full decode that could not
		complete at all.

		## The default threshold

		41.0 dB, which on the table above selects quality 80 and saves about
		half the file.  Above 40 dB a re-encode is generally indistinguishable
		from its source at viewing size; the tools expose --target so a run can
		be made stricter, and check_image_compression.php exists precisely so
		the number can be argued with against real files before anything is
		rewritten.
	*/

	trait ImageQualitySearch {
			// Tunables
			// -----------------------------------------------

		public function qualitySearchCropSize() {
			return 1200;
		}

		public function qualitySearchDefaultTarget() {
			return 41.0;
		}

			/*
				The search will not go below this whatever the metric says.
				Very flat images clear a high PSNR at absurd qualities, and a
				woodcut at quality 30 is a woodcut with mud in the hatching
				that no pixel metric objects to.
			*/

		public function qualitySearchFloor() {
			return 55;
		}

		public function qualitySearchCeiling() {
			return 95;
		}

			// The search
			// -----------------------------------------------

			/*
				Binary search over the quality range, keeping the lowest
				quality that clears the target.  Roughly six encodes per image
				instead of the forty a linear sweep would take, which on one
				vCPU is the difference between a nightly job and a weekend.
			*/

		public function searchQuality($args) {
			$path = $args['path'];

			$target = array_key_exists('target', $args) ? (float)$args['target'] : $this->qualitySearchDefaultTarget();
			$floor = array_key_exists('floor', $args) ? (int)$args['floor'] : $this->qualitySearchFloor();
			$ceiling = array_key_exists('ceiling', $args) ? (int)$args['ceiling'] : $this->qualitySearchCeiling();

			$identified = $this->identifyImage(['path'=>$path]);

			if(!$identified) {
				return ['status'=>'unreadable'];
			}

				/*
					ImageMagick refuses an image above its area policy, and on
					this host that policy is 128 megapixels.  Two of
					masereelgroup's woodcut scans are 142 and 145, so they come
					back instantly with "cache resources exhausted" and 11 MB of
					RSS -- a refusal, not an exhaustion.

					The policy is not the enemy here.  It is what stops one
					convert from claiming more than a gigabyte on a box with two,
					while Apache is serving seventeen sites.  So this reports the
					file and leaves it alone rather than raising the limit.
				*/

			$area_limit = $this->imageMagickAreaLimit();

			if($area_limit > 0 && ($identified['width'] * $identified['height']) > $area_limit) {
				return [
					'status'=>'over-imagemagick-area-policy',
					'source_quality'=>$identified['quality'],
					'width'=>$identified['width'],
					'height'=>$identified['height'],
				];
			}

				/*
					Never encode above what the source already is.  Raising a
					quality-60 file to 82 produces a larger file that has not
					recovered any of the detail thrown away when it was first
					saved, and the tool would report it as a negative saving --
					but only after spending the encode.
				*/

			if($identified['quality'] > 0 && $identified['quality'] < $ceiling) {
				$ceiling = $identified['quality'];
			}

			if($ceiling <= $floor) {
				return [
					'status'=>'already-low',
					'source_quality'=>$identified['quality'],
					'width'=>$identified['width'],
					'height'=>$identified['height'],
				];
			}

			$original_size = filesize($path);

			$geometry = $this->cropGeometry([
				'width'=>$identified['width'],
				'height'=>$identified['height'],
			]);

			$reference = $this->buildCrop(['path'=>$path, 'geometry'=>$geometry]);

			if(!$reference) {
				return [
					'status'=>$this->classifyEncoderFailure(),
					'source_quality'=>$identified['quality'],
					'width'=>$identified['width'],
					'height'=>$identified['height'],
				];
			}

			$best = FALSE;
			$low = $floor;
			$high = $ceiling;
			$encodes = 0;
			$encoder_failed = FALSE;

			while($low <= $high) {
				$quality = (int)floor(($low + $high) / 2);

				$candidate = $this->encodeAndMeasure([
					'path'=>$path,
					'quality'=>$quality,
					'reference'=>$reference,
					'geometry'=>$geometry,
				]);

				$encodes++;

				if(!$candidate) {
					$encoder_failed = TRUE;

					break;
				}

				if($candidate['metric'] >= $target) {
					$best = $candidate;
					$high = $quality - 1;
				} else {
					$low = $quality + 1;
				}
			}

			unlink($reference);

				/*
					An encoder that fell over is not a verdict on fidelity.
					Reporting it as "no quality clears the target" would read as
					a careful refusal when it was a crash, and the file would be
					quietly left alone forever.
				*/

			if(!$best) {
				return [
					'status'=>$encoder_failed ? $this->classifyEncoderFailure() : 'no-quality-clears-target',
					'source_quality'=>$identified['quality'],
					'width'=>$identified['width'],
					'height'=>$identified['height'],
					'encodes'=>$encodes,
				];
			}

				/*
					A saving that is not a saving.  Small files that are
					already well encoded routinely come back larger, and
					rewriting them costs a generation of quality to gain
					nothing.
				*/

			if($best['size'] >= $original_size) {
				return [
					'status'=>'no-gain',
					'source_quality'=>$identified['quality'],
					'quality'=>$best['quality'],
					'original_size'=>$original_size,
					'new_size'=>$best['size'],
					'width'=>$identified['width'],
					'height'=>$identified['height'],
					'encodes'=>$encodes,
				];
			}

			return [
				'status'=>'found',
				'source_quality'=>$identified['quality'],
				'quality'=>$best['quality'],
				'metric'=>$best['metric'],
				'original_size'=>$original_size,
				'new_size'=>$best['size'],
				'saved'=>$original_size - $best['size'],
				'width'=>$identified['width'],
				'height'=>$identified['height'],
				'encodes'=>$encodes,
			];
		}

			// Cropping and measuring
			// -----------------------------------------------

			/*
				A centred window, clamped to the image.  Smaller images are
				compared whole, which is what the clamp produces and is the
				right answer -- there is nothing to sample from a 400x300 icon.
			*/

		public function cropGeometry($args) {
			$width = (int)$args['width'];
			$height = (int)$args['height'];

			$size = $this->qualitySearchCropSize();

			$crop_width = $width < $size ? $width : $size;
			$crop_height = $height < $size ? $height : $size;

			$left = (int)floor(($width - $crop_width) / 2);
			$top = (int)floor(($height - $crop_height) / 2);

			return $crop_width . 'x' . $crop_height . '+' . $left . '+' . $top;
		}

			/*
				The crop is applied in the read specification rather than as an
				operator, so ImageMagick never materialises the whole image.
				That is the difference between 48 MB and a failure to decode.
			*/

		public function buildCrop($args) {
			$path = $args['path'];
			$geometry = $args['geometry'];

			$crop = tempnam(sys_get_temp_dir(), 'ggcms_crop_') . '.png';

			$command = 'nice -n 19 convert ' . escapeshellarg($path . '[' . $geometry . ']');
			$command .= ' +repage ' . escapeshellarg($crop) . ' 2>&1';

			$this->last_encoder_error = trim((string)shell_exec($command));

			if(!is_file($crop) || filesize($crop) === 0) {
				if(is_file($crop)) {
					unlink($crop);
				}

				return FALSE;
			}

			return $crop;
		}

		public function encodeAndMeasure($args) {
			$path = $args['path'];
			$quality = (int)$args['quality'];
			$reference = $args['reference'];
			$geometry = $args['geometry'];

			$encoded = $this->encodeToTemporary([
				'path'=>$path,
				'quality'=>$quality,
			]);

			if(!$encoded) {
				return FALSE;
			}

			$size = filesize($encoded);

			$metric = $this->measureAgainstCrop([
				'path'=>$encoded,
				'reference'=>$reference,
				'geometry'=>$geometry,
			]);

			unlink($encoded);

			if($metric === FALSE) {
				return FALSE;
			}

			return [
				'quality'=>$quality,
				'size'=>$size,
				'metric'=>$metric,
			];
		}

			/*
				-strip removes EXIF, colour profiles and embedded thumbnails.
				On these scans that is routinely tens of kilobytes of camera
				metadata per file, none of which is served to anybody.

				The compressor writes with this same function, so what is
				measured is byte-for-byte what would be installed.  A search
				that encoded differently from the writer would report savings
				nobody ever receives.
			*/

		public function encodeToTemporary($args) {
			$path = $args['path'];
			$quality = (int)$args['quality'];

			$encoded = tempnam(sys_get_temp_dir(), 'ggcms_enc_') . '.jpg';

			$command = 'nice -n 19 convert ' . escapeshellarg($path . '[0]');
			$command .= ' -strip -quality ' . $quality;
			$command .= ' ' . escapeshellarg($encoded) . ' 2>&1';

			$this->last_encoder_error = trim((string)shell_exec($command));

			if(!is_file($encoded) || filesize($encoded) === 0) {
				if(is_file($encoded)) {
					unlink($encoded);
				}

				return FALSE;
			}

			return $encoded;
		}

		public function measureAgainstCrop($args) {
			$path = $args['path'];
			$reference = $args['reference'];
			$geometry = $args['geometry'];

			$candidate_crop = $this->buildCrop([
				'path'=>$path,
				'geometry'=>$geometry,
			]);

			if(!$candidate_crop) {
				return FALSE;
			}

			$command = 'nice -n 19 compare -metric PSNR ';
			$command .= escapeshellarg($reference) . ' ' . escapeshellarg($candidate_crop);
			$command .= ' null: 2>&1';

			$output = trim((string)shell_exec($command));

			unlink($candidate_crop);

				/*
					compare prints the metric on stderr and exits non-zero
					whenever the images differ, which here they always do.  An
					identical pair prints "inf", which is a clear pass rather
					than a parse failure.
				*/

			if(strpos($output, 'inf') === 0) {
				return 99.0;
			}

			if(!preg_match('/^-?[0-9]+(\.[0-9]+)?/', $output, $matches)) {
				return FALSE;
			}

			return (float)$matches[0];
		}

			// Classifying a failure
			// -----------------------------------------------

			/*
				ImageMagick's own words, so that a resource refusal does not
				arrive looking like a mystery.

				The area policy catches the worst offenders before any work is
				done, but memory, map and disk are separate limits and a file
				comfortably under 128 megapixels can still exhaust them -- a
				109-megapixel scan wants roughly 870 MB decoded, against a
				memory limit of 256 MiB and a disk cache of 1 GiB.  Reporting
				that as "encoder-failed" would send somebody hunting a bug in
				this file, when the answer is that the box declined the job.
			*/

		public function classifyEncoderFailure() {
			if(!property_exists($this, 'last_encoder_error') || !$this->last_encoder_error) {
				return 'encoder-failed';
			}

			$error = strtolower($this->last_encoder_error);

			if(strpos($error, 'cache resources exhausted') !== FALSE) {
				return 'over-imagemagick-limits';
			}

			if(strpos($error, 'memory allocation failed') !== FALSE) {
				return 'over-imagemagick-limits';
			}

			if(strpos($error, 'no space left') !== FALSE) {
				return 'out-of-disk';
			}

			return 'encoder-failed';
		}

			// The area policy
			// -----------------------------------------------

			/*
				Read from ImageMagick rather than hardcoded, so that raising
				or lowering the policy changes what these tools attempt
				without anyone remembering this file exists.

				identify -list resource prints it as a line reading
				"Area: 128MP".  Returns 0 when it cannot be read, which the
				caller treats as no limit -- a failure to parse should not
				quietly stop the tool from doing any work.
			*/

		public function imageMagickAreaLimit() {
			if(property_exists($this, 'imagemagick_area_limit') && $this->imagemagick_area_limit !== NULL) {
				return $this->imagemagick_area_limit;
			}

			$output = (string)shell_exec('identify -list resource 2>/dev/null');

			if(!preg_match('/^[ 	]*Area:[ 	]*([0-9.]+)[ 	]*([KMGT]?)P/mi', $output, $matches)) {
				return $this->imagemagick_area_limit = 0;
			}

			$multipliers = [
				''=>1,
				'K'=>1000,
				'M'=>1000000,
				'G'=>1000000000,
				'T'=>1000000000000,
			];

			$unit = strtoupper($matches[2]);

			if(!array_key_exists($unit, $multipliers)) {
				return $this->imagemagick_area_limit = 0;
			}

			return $this->imagemagick_area_limit = (float)$matches[1] * $multipliers[$unit];
		}
	}

?>

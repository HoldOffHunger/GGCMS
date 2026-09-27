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
		Rows that name a file which is not there, beside a file nothing names.

		Found by scan_images.php on revoltlib: 51 rows whose three filenames
		lack the "<Entryid>-" prefix the actual files carry.  The row says
		m/f/i/5/6336636155_e89bdd7638_o.jpg and the disk holds
		m/f/i/5/437-6336636155_e89bdd7638_o.jpg, Entryid 437.  Nothing is lost
		-- the pictures are all present -- but the page builds the name from
		the row, so the img tag points at nothing and the visitor gets a broken
		image.  They appear twice in a scan, once as missing rows and again
		among the orphans.

		## What it will and will not do

		It only ever prepends the row's own Entryid to the row's own filename.
		It never composes a name from a directory listing, never guesses
		between two candidates, and never touches a row whose file is simply
		gone.  The repair is arithmetic on data already in the row, and the
		file it will point at has to exist before anything is written.

		**A row is repaired only if every one of its variants agrees.**  The
		bare name must be absent and the prefixed name present for the
		original, the standard and the icon alike.  A row where only some
		variants fit the pattern is reported and left alone: that is a
		different fault, and a half-repaired row renders two images and a hole
		rather than three holes, which is harder to notice and no better.

		**Dimensions are checked before the write.**  The prefixed file has to
		match the dimensions already stored on the row.  That is what
		distinguishes "the same picture under its proper name" from "some other
		picture that happens to sort nearby", and without it this tool would be
		matching on filenames alone and hoping.  Rows whose stored dimensions
		are zero skip that comparison, because absence is not disagreement.
	*/

	class ImageFilenameRepairer {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ReverseDNSNotation;
		
		public $arguments;

			// Entry Point
			// -----------------------------------------------

		public function repairImageFilenames() {
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

			$rows = $this->loadImageRowsWithEntry();

			$examined = $this->examineRows(['rows'=>$rows]);

			return $this->reportAndRepair(['examined'=>$examined, 'total'=>count($rows)]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
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
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Image Filename Repair' . "\n\n");
			print('  repair_image_filenames.php DOMAIN [--limit=N] [--all] [--apply]' . "\n\n");
			print('  Finds Image rows whose files are absent under the stored name and' . "\n");
			print('  present under "<Entryid>-<name>", and corrects the row to match.' . "\n\n");
			print('  Repairs a row only when all three variants agree and the file on' . "\n");
			print('  disk matches the dimensions already stored.  Dry by default.' . "\n\n");

			return TRUE;
		}

			// Reading
			// -----------------------------------------------

			/*
				Entryid is the prefix, so it is selected here rather than
				relying on ImageFiles::loadImageRows, which has no reason to
				fetch it.
			*/

		public function loadImageRowsWithEntry() {
			$query = 'SELECT id, Entryid, FileName, StandardFileName, IconFileName, FileDirectory, ';
			$query .= 'PixelWidth, PixelHeight, StandardPixelWidth, StandardPixelHeight, ';
			$query .= 'IconPixelWidth, IconPixelHeight ';
			$query .= 'FROM Image ORDER BY id';

			return $this->runQuery(['query'=>$query]);
		}

			// Examining
			// -----------------------------------------------

		public function examineRows($args) {
			$rows = $args['rows'];

			$repairable = [];
			$partial = [];
			$absent = [];

			$dimension_fields = [
				'original'=>['PixelWidth', 'PixelHeight'],
				'standard'=>['StandardPixelWidth', 'StandardPixelHeight'],
				'icon'=>['IconPixelWidth', 'IconPixelHeight'],
			];

			foreach($rows as $row) {
				$directory = $this->fileDirectoryToPath([
					'file_directory'=>$row['FileDirectory'],
				]);

					/*
						FileDirectory should be four characters.  A few rows
						hold a whole filename there instead, which produces a
						nonsense path; those are malformed in a way this tool
						has no business guessing at.
					*/

				if(strlen($directory) === 0 || strlen($row['FileDirectory']) !== 4) {
					continue;
				}

				$prefix = $row['Entryid'] . '-';
				$base = $this->imageDirectory() . $directory . '/';

				$variants_present = 0;
				$variants_fitting = 0;
				$variants_mismatched = 0;
				$new_names = [];

				foreach($this->variantNames() as $variant) {
					$filename = $this->variantFilename(['row'=>$row, 'variant'=>$variant]);

					if(strlen($filename) === 0) {
						continue;
					}

					$variants_present++;

						/*
							Already correct, or correct under some other name.
							Either way this row is not the fault being hunted.
						*/

					if(is_file($base . $filename)) {
						continue;
					}

					if(!is_file($base . $prefix . $filename)) {
						continue;
					}

					if(!$this->dimensionsAgree([
						'path'=>$base . $prefix . $filename,
						'row'=>$row,
						'fields'=>$dimension_fields[$variant],
					])) {
						$variants_mismatched++;

						continue;
					}

					$variants_fitting++;
					$new_names[$variant] = $prefix . $filename;
				}

				if($variants_present === 0) {
					continue;
				}

				if($variants_fitting === 0 && $variants_mismatched === 0) {
					continue;
				}

				$entry = [
					'row'=>$row,
					'new_names'=>$new_names,
					'fitting'=>$variants_fitting,
					'present'=>$variants_present,
					'mismatched'=>$variants_mismatched,
					'directory'=>$directory,
				];

				if($variants_fitting === $variants_present) {
					$repairable[] = $entry;

					continue;
				}

				$partial[] = $entry;
			}

			return [
				'repairable'=>$repairable,
				'partial'=>$partial,
				'absent'=>$absent,
			];
		}

			/*
				Corroboration, not identification.  Matching a name is a guess;
				matching a name and the dimensions the row already recorded is
				evidence that this is the same picture.
			*/

		public function dimensionsAgree($args) {
			$path = $args['path'];
			$row = $args['row'];
			$fields = $args['fields'];

			$stored_width = (int)$row[$fields[0]];
			$stored_height = (int)$row[$fields[1]];

			if($stored_width === 0 || $stored_height === 0) {
				return TRUE;
			}

			$identified = $this->identifyImage(['path'=>$path]);

			if(!$identified) {
				return FALSE;
			}

			return $identified['width'] === $stored_width
				&& $identified['height'] === $stored_height;
		}

			// Reporting and repairing
			// -----------------------------------------------

		public function reportAndRepair($args) {
			$examined = $args['examined'];

			$repairable = $examined['repairable'];
			$partial = $examined['partial'];

			print('Examined ' . $args['total'] . ' Image rows.' . "\n\n");

			if($partial) {
				print(count($partial) . ' rows fit only partly and are being left alone:' . "\n\n");
				print(arr2textTable($this->partialTable(['partial'=>$partial])));
				print("\n");
			}

			if(!$repairable) {
				print('No rows to repair.' . "\n\n");

				return TRUE;
			}

			$limit = $this->arguments['limit'];

			if($limit > 0 && count($repairable) > $limit) {
				$repairable = array_slice($repairable, 0, $limit);
			}

			print(count($repairable) . ' rows can be repaired, ');
			print((count($repairable) * 3) . ' filenames:' . "\n\n");

			$this->printRepairTable(['repairable'=>$repairable]);

			if(!$this->arguments['apply']) {
				print('Dry run.  Nothing has been written.' . "\n");
				print('Add --apply to correct these rows.' . "\n\n");

				return TRUE;
			}

			return $this->applyRepairs(['repairable'=>$repairable]);
		}

		public function partialTable($args) {
			$table = [];

			foreach($args['partial'] as $entry) {
				$table[] = [
					'Image id'=>$entry['row']['id'],
					'Entryid'=>$entry['row']['Entryid'],
					'Fit'=>$entry['fitting'] . ' of ' . $entry['present'],
					'Dimension mismatch'=>$entry['mismatched'],
					'File'=>$entry['directory'] . '/' . $entry['row']['FileName'],
				];
			}

			return $table;
		}

		public function printRepairTable($args) {
			$repairable = $args['repairable'];

			$limit = $this->arguments['all'] ? 0 : 20;
			$shown = ($limit > 0 && count($repairable) > $limit) ? array_slice($repairable, 0, $limit) : $repairable;

			$table = [];

			foreach($shown as $entry) {
				$table[] = [
					'Image id'=>$entry['row']['id'],
					'Entryid'=>$entry['row']['Entryid'],
					'Row says'=>$entry['row']['FileName'],
					'Becomes'=>$entry['new_names']['original'],
				];
			}

			print(arr2textTable($table));

			if(count($shown) < count($repairable)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($repairable) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

			/*
				One statement per row, with the three names bound.  Not one
				statement for the lot: these rows are being corrected on the
				evidence of files checked one at a time, and a single UPDATE
				spanning fifty-one of them would either all land or all fail on
				the strength of a WHERE clause that repeats none of that
				checking.
			*/

		public function applyRepairs($args) {
			$repairable = $args['repairable'];

			$query = 'UPDATE Image SET FileName = ?, StandardFileName = ?, IconFileName = ? WHERE id = ?';

			$statement = $this->db_link->prepare($query);

			if(!$statement) {
				print('Could not prepare the update: ' . $this->db_link->error . "\n\n");

				return FALSE;
			}

			$repaired = 0;
			$failures = [];

			foreach($repairable as $entry) {
				$row = $entry['row'];

				$original = $entry['new_names']['original'];
				$standard = $entry['new_names']['standard'];
				$icon = $entry['new_names']['icon'];
				$id = (int)$row['id'];

				$statement->bind_param('sssi', $original, $standard, $icon, $id);

				if(!$statement->execute()) {
					$failures[] = [
						'Image id'=>$row['id'],
						'Why'=>$statement->error,
					];

					continue;
				}

				$repaired++;
			}

			$statement->close();

			print('Repaired ' . $repaired . ' of ' . count($repairable) . ' rows.' . "\n\n");

			$this->invalidateRowCache(['repairable'=>$repairable]);

			if($failures) {
				print(count($failures) . ' failed:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");

				return FALSE;
			}

			print('Now flush the page cache for this domain -- in that order.' . "\n");
			print('Clearing pages first only rewrites them from the stale rows.' . "\n\n");

			return TRUE;
		}

			// The row cache
			// -----------------------------------------------

			/*
				The repair writes rows with a prepared UPDATE, which reaches the
				database without passing through the ORM -- so none of the
				engine's own invalidation fires, and the row-level file cache
				goes on serving the filenames that were just corrected.

				DBFileCache.php says what happens next better than this comment
				could: the page cache is flushed, the page re-renders, reads the
				stale row, and writes a fresh page cache holding the old value.
				Caching faithfully preserves the mistake.  That is not
				hypothetical -- it happened on revoltlib on 7 September 2026,
				because the pages were cleared first and the rows were not
				cleared at all.

				So the rows go first and the operator is told to flush pages
				second.  Deleting is the whole of the fix; the next render
				refills from the database.

				Only ggcms_EntryChildRecords/Image holds these filenames -- the
				other six cache types were searched for one of the repaired
				names and none of them carried it.
			*/

		public function invalidateRowCache($args) {
			require_once(GGCMS_DIR . 'classes/Database/DBFileCache.php');

			$cache = new DBFileCache(['handler'=>NULL]);

			$directory = $cache->DBFileCacheLocation();
			$directory .= '/' . $this->ReverseDomainName(['domain'=>strtolower($this->domain)]);
			$directory .= '/ggcms_EntryChildRecords/Image/';

			if(!is_dir($directory)) {
				print('No cached Image rows to invalidate.' . "

");

				return TRUE;
			}

			$entry_ids = [];

			foreach($args['repairable'] as $entry) {
				$entry_ids[(int)$entry['row']['Entryid']] = TRUE;
			}

			$deleted = 0;

			foreach(array_keys($entry_ids) as $entry_id) {
				$file = $directory . $entry_id;

				if(is_file($file) && @unlink($file)) {
					$deleted++;
				}
			}

			print('Invalidated ' . $deleted . ' cached Image rows across ');
			print(count($entry_ids) . ' entries.' . "

");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Filename Repair';
		}

		public function confirmDomainText() {
			return 'Repairing image filenames for: ';
		}
	}

?>

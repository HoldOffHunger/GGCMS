<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	ggreq('traits/ReverseDNSNotation.php');
	ggreq('traits/scripts/SimpleImages.php');

	/*
		Taking the upscaled originals back, re-deriving what hangs off them,
		and telling the database.

		The inbound half of the upscale pipeline.  As with the compression
		import, the machine that did the work is a processor and not an
		authority, and nothing it says is taken on sight.

		## Why it touches three files, not one

		The engine makes the standard image by fitting the original inside
		1000 by 1000, and the icon by fitting it inside 200 by 200, and it
		never enlarges.  So an original of 600 pixels has a standard of 600
		pixels, and replacing only the original would leave the picture the
		reader actually sees exactly as small as it was.  The standard and the
		icon are therefore made again from the new original -- by the engine's
		own makeStandardImage() and makeIcon(), through the SimpleImages trait,
		so this tool cannot drift from what an upload would have produced.

		A variant whose dimensions come out the same as it already has is left
		alone.  Most icons are already a full 200 pixels, and re-encoding one
		to the same size would spend a generation of quality for nothing.

		## The checks

		**A backup holds the original.**  Not merely a backup directory: a
		copy of this file at its current dimensions, looked for newest first,
		as shrink_images.php does.  It is the only copy of what the picture
		really looked like.

		**The row still names the file.**  The Image row from the manifest
		must exist and its original must still be this path.

		**The live file is still the file that went out.**  SHA-256 against
		the manifest.

		**The returned file is intact.**  SHA-256 against the result row.

		**It is larger, the same shape, and the same format.**  Larger on both
		sides than the manifest says the original was; the aspect ratio within
		a percent; and a JPEG still a JPEG and a PNG still a PNG, because
		FileName is not changing and the extension has to keep telling the
		truth.

		## It keeps a ledger, and the ledger is the point

		Every file installed is written to upscaled.tsv beside the compression
		ledger, with the model that made it and the size it came from.  The
		detail in those files was invented by a network, not recovered, and
		somebody looking at a face on one of these sites in a year's time is
		owed a way to find that out.  The export also reads it, so nothing is
		upscaled twice.

		## Rows before pages

		The UPDATE goes past the ORM, so the row cache is invalidated here, as
		shrink_images.php does, before anybody flushes the page cache.
	*/

	class UpscaledImageImporter {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ReverseDNSNotation;
		use SimpleImages;

		public $arguments;
		public $backup_labels;

			// Entry Point
			// -----------------------------------------------

		public function importUpscaledImages() {
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

				/*
					The standard and the icon are made by the engine's own
					resize, which is the imagick extension rather than the
					command-line tools.  Without it every install would fail at
					the second file of three, after the first was written.
				*/

			if($this->arguments['apply'] && !extension_loaded('imagick')) {
				print('The imagick extension is not loaded in this PHP, and the standard and icon' . "\n");
				print('images are made with it.  php -m should list imagick.' . "\n\n");

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

			$this->setGlobals();
			$this->setMySQLArgs();

			$rows = [];

			foreach($this->loadImageRows() as $row) {
				$rows[(int)$row['id']] = $row;
			}

			print('Batch:    ' . $this->arguments['directory'] . "\n");
			print('Manifest: ' . count($manifest) . ' files' . "\n");
			print('Results:  ' . count($results) . ' rows' . "\n\n");

			$this->reportModel(['results'=>$results]);

			$examined = $this->examine([
				'manifest'=>$manifest,
				'results'=>$results,
				'rows'=>$rows,
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
			print('GGCMS - Upscaled Image Import' . "\n\n");
			print('  import_upscaled_images.php DOMAIN --from=/path/to/batch [--all] [--apply]' . "\n\n");
			print('  Re-checks every returned original against the manifest and the Image' . "\n");
			print('  table, installs the ones that pass, remakes their standard and icon' . "\n");
			print('  images, and rewrites the stored dimensions.' . "\n\n");
			print('  Dry by default; --apply requires a backup holding each original.' . "\n\n");

			return TRUE;
		}

			// Refusing without a backup
			// -----------------------------------------------

		public function confirmBackupExists() {
			$root = $this->imageBackupDomainRoot();

			$labels = [];

			if(is_dir($root)) {
				foreach(scandir($root) as $entry) {
					if($entry !== '.' && $entry !== '..' && is_dir($root . $entry)) {
						$labels[] = $entry;
					}
				}
			}

			if(!$labels) {
				print('No backup exists for ' . $this->domain . '.  Take one first:' . "\n\n");
				print('  backup_images.php ' . $this->domain . ' --min-size=0 --apply' . "\n\n");

				return FALSE;
			}

			rsort($labels);

			$this->backup_labels = $labels;

			print('Backups found: ' . implode(', ', $labels) . "\n\n");

			return TRUE;
		}

			/*
				The same per-file search as shrink_images.php, for the reason
				recorded there: a backup directory existing is not the same as
				this file being in it.
			*/

		public function backupCopyOf($args) {
			$entry = $args['entry'];

			foreach($this->backup_labels as $label) {
				$copy = $this->imageBackupDirectory(['label'=>$label]) . $entry['relative'];

				if(!is_file($copy)) {
					continue;
				}

				$image = $this->identifyImage(['path'=>$copy]);

				if($image && $image['width'] === $entry['width'] && $image['height'] === $entry['height']) {
					return $copy;
				}
			}

			return FALSE;
		}

			// Reading
			// -----------------------------------------------

		public function loadImageRows() {
			$query = 'SELECT id, Entryid, FileName, StandardFileName, IconFileName, FileDirectory, ';
			$query .= 'PixelWidth, PixelHeight, StandardPixelWidth, StandardPixelHeight, ';
			$query .= 'IconPixelWidth, IconPixelHeight ';
			$query .= 'FROM Image ORDER BY id';

			return $this->runQuery(['query'=>$query]);
		}

		public function dimensionFields() {
			return [
				'original'=>['PixelWidth', 'PixelHeight'],
				'standard'=>['StandardPixelWidth', 'StandardPixelHeight'],
				'icon'=>['IconPixelWidth', 'IconPixelHeight'],
			];
		}

		public function readManifest() {
			$rows = $this->readTable([
				'path'=>$this->arguments['directory'] . 'manifest.tsv',
				'columns'=>6,
			]);

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
					'sha256'=>$pieces[4],
					'image_id'=>(int)$pieces[5],
				];
			}

			return $manifest;
		}

		public function readResults() {
			$rows = $this->readTable([
				'path'=>$this->arguments['directory'] . 'results.tsv',
				'columns'=>10,
			]);

			if($rows === FALSE) {
				return FALSE;
			}

			$results = [];

			foreach($rows as $pieces) {
				$results[] = [
					'relative'=>$pieces[0],
					'status'=>$pieces[1],
					'image_id'=>(int)$pieces[2],
					'original_size'=>(int)$pieces[3],
					'new_size'=>(int)$pieces[4],
					'original_sha256'=>$pieces[5],
					'new_sha256'=>$pieces[6],
					'original_dimensions'=>$pieces[7],
					'new_dimensions'=>$pieces[8],
					'model'=>$pieces[9],
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

		public function reportModel($args) {
			$models = [];

			foreach($args['results'] as $result) {
				if($result['status'] === 'upscaled') {
					$models[$result['model']] = TRUE;
				}
			}

			if($models) {
				print('Upscaled elsewhere using: ' . implode(', ', array_keys($models)) . "\n\n");
			}

			return TRUE;
		}

			// Checking
			// -----------------------------------------------

		public function imageFormat($args) {
			$command = $this->imageMagickCommand(['tool'=>'identify']);
			$command .= ' -ping -format "%m" ' . escapeshellarg($args['path'] . '[0]') . ' 2>' . $this->nullDevice();

			return strtoupper(trim((string)shell_exec($command)));
		}

		public function expectedFormat($args) {
			if($this->extensionOf(['path'=>$args['path']]) === 'png') {
				return 'PNG';
			}

			return 'JPEG';
		}

		public function examine($args) {
			$manifest = $args['manifest'];
			$rows = $args['rows'];

			$installable = [];
			$refused = [];
			$skipped = 0;

			foreach($args['results'] as $result) {
				if($result['status'] !== 'upscaled') {
					$skipped++;

					continue;
				}

				$relative = $result['relative'];

				if(!array_key_exists($relative, $manifest)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'not in the manifest']);

					continue;
				}

				$entry = $manifest[$relative];

				if(!array_key_exists($entry['image_id'], $rows)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'Image ' . $entry['image_id'] . ' no longer exists']);

					continue;
				}

				$row = $rows[$entry['image_id']];

				$live = $this->imagePathForVariant(['row'=>$row, 'variant'=>'original']);

				if($this->relativeImagePath(['path'=>$live]) !== $relative) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'Image ' . $entry['image_id'] . ' now names another file']);

					continue;
				}

				if(!is_file($live)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'live file has gone']);

					continue;
				}

				if(hash_file('sha256', $live) !== $entry['sha256']) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'live file changed since export']);

					continue;
				}

				$incoming = $this->arguments['directory'] . 'upscaled/' . $relative;

				if(!is_file($incoming)) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'result file not in batch']);

					continue;
				}

				if(hash_file('sha256', $incoming) !== $result['new_sha256']) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'result does not match its recorded hash']);

					continue;
				}

				$identified = $this->identifyImage(['path'=>$incoming]);

				if(!$identified) {
					$refused[] = $this->refusal(['relative'=>$relative, 'why'=>'result is not readable']);

					continue;
				}

				if($identified['width'] <= $entry['width'] || $identified['height'] <= $entry['height']) {
					$refused[] = $this->refusal([
						'relative'=>$relative,
						'why'=>'not larger: ' . $identified['width'] . 'x' . $identified['height']
							. ' against ' . $entry['width'] . 'x' . $entry['height'],
					]);

					continue;
				}

				$before_ratio = $entry['width'] / $entry['height'];
				$after_ratio = $identified['width'] / $identified['height'];

				if(abs($before_ratio - $after_ratio) / $before_ratio > 0.01) {
					$refused[] = $this->refusal([
						'relative'=>$relative,
						'why'=>'aspect ratio changed: ' . $identified['width'] . 'x' . $identified['height'],
					]);

					continue;
				}

				$format = $this->imageFormat(['path'=>$incoming]);
				$expected = $this->expectedFormat(['path'=>$live]);

				if($format !== $expected) {
					$refused[] = $this->refusal([
						'relative'=>$relative,
						'why'=>'result is ' . $format . ' but the file is named as ' . $expected,
					]);

					continue;
				}

				$installable[] = [
					'relative'=>$relative,
					'entry'=>$entry,
					'row'=>$row,
					'live'=>$live,
					'incoming'=>$incoming,
					'before_size'=>$entry['size'],
					'after_size'=>filesize($incoming),
					'width'=>$identified['width'],
					'height'=>$identified['height'],
					'new_sha256'=>$result['new_sha256'],
					'model'=>$result['model'],
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

			// Reporting
			// -----------------------------------------------

		public function reportAndInstall($args) {
			$examined = $args['examined'];

			$installable = $examined['installable'];
			$refused = $examined['refused'];

			if($examined['skipped']) {
				print($examined['skipped'] . ' rows were not upscaled on the other machine.' . "\n\n");
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

			$this->printInstallTable(['installable'=>$installable]);

			if(!$this->arguments['apply']) {
				print('Would install ' . count($installable) . ' originals and remake their standard and icon images.' . "\n\n");
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

			foreach($shown as $item) {
				$table[] = [
					'Image'=>$item['row']['id'],
					'Before'=>$item['entry']['width'] . 'x' . $item['entry']['height'],
					'After'=>$item['width'] . 'x' . $item['height'],
					'Bytes'=>$this->formatBytes(['number'=>$item['before_size']]) . ' -> ' . $this->formatBytes(['number'=>$item['after_size']]),
					'File'=>$item['relative'],
				];
			}

			print(arr2textTable($table));

			if(count($shown) < count($installable)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($installable) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

			// Installing
			// -----------------------------------------------

		public function install($args) {
			$installed = 0;
			$failures = [];
			$entry_ids = [];

			foreach($args['installable'] as $item) {
				$outcome = $this->installOne(['item'=>$item]);

				if($outcome !== TRUE) {
					$failures[] = $this->refusal(['relative'=>$item['relative'], 'why'=>$outcome]);

					continue;
				}

				$entry_ids[(int)$item['row']['Entryid']] = TRUE;

				$installed++;
			}

			print('Installed ' . $installed . ' of ' . count($args['installable']) . ' originals.' . "\n\n");

			if($failures) {
				print(count($failures) . ' failed:' . "\n\n");
				print(arr2textTable($failures));
				print("\n");
			}

			if($entry_ids) {
				$this->invalidateRowCache(['entry_ids'=>array_keys($entry_ids)]);

				print('Now flush the page cache for this domain -- in that order.' . "\n\n");
			}

			return !$failures;
		}

			/*
				Every file is made beside its live counterpart first, and only
				when all of them exist are any renamed into place.  A failure
				while making the standard must not leave a new original
				sitting over an old standard; that is a page showing a picture
				that disagrees with its own link.

				Returns TRUE, or a string saying why nothing was installed.
			*/

		public function installOne($args) {
			$item = $args['item'];
			$row = $item['row'];

			if(!$this->backupCopyOf(['entry'=>$item['entry']])) {
				return 'no backup holds this original at ' . $item['entry']['width'] . 'x' . $item['entry']['height'];
			}

			$staged_original = $item['live'] . '.ggcms_upscale';

			if(!copy($item['incoming'], $staged_original)) {
				return 'could not stage';
			}

			if(hash_file('sha256', $staged_original) !== $item['new_sha256']) {
				unlink($staged_original);

				return 'staged copy does not match';
			}

			$staged = [
				'original'=>[
					'staged'=>$staged_original,
					'live'=>$item['live'],
					'width'=>$item['width'],
					'height'=>$item['height'],
				],
			];

			foreach(['standard', 'icon'] as $variant) {
				$made = $this->remakeVariant([
					'row'=>$row,
					'variant'=>$variant,
					'from'=>$staged_original,
					'original_live'=>$item['live'],
				]);

				if($made === FALSE) {
					continue;
				}

				if(!is_array($made)) {
					$this->discardStaged(['staged'=>$staged]);

					return $made;
				}

				$staged[$variant] = $made;
			}

			$permissions = fileperms($item['live']);

			$placed = [];

			foreach($staged as $variant=>$file) {
				if(!rename($file['staged'], $file['live'])) {
					$this->discardStaged(['staged'=>$staged]);

					if(!$placed) {
						return 'could not replace the ' . $variant;
					}

					return 'FILES PARTLY REPLACED (' . implode(', ', $placed) . ') -- restore Image ' . $row['id'] . ' from backup';
				}

				if($permissions !== FALSE) {
					chmod($file['live'], $permissions & 0777);
				}

				$placed[] = $variant;
			}

			$updated = $this->updateDimensions(['row'=>$row, 'staged'=>$staged]);

			if($updated !== TRUE) {
				return $updated;
			}

			$this->appendToLedger([
				'relative'=>$item['relative'],
				'hash'=>$item['new_sha256'],
				'image_id'=>$row['id'],
				'model'=>$item['model'],
				'from'=>$item['entry']['width'] . 'x' . $item['entry']['height'],
				'to'=>$item['width'] . 'x' . $item['height'],
			]);

			return TRUE;
		}

			/*
				Returns FALSE when there is nothing to do -- no such variant,
				or one that would come out at the size it already is -- an
				array describing the staged file, or a string saying what went
				wrong.
			*/

		public function remakeVariant($args) {
			$row = $args['row'];
			$variant = $args['variant'];

			$live = $this->imagePathForVariant(['row'=>$row, 'variant'=>$variant]);

				/*
					A row whose standard is its original would have the new
					original overwritten by its own downscale.
				*/

			if(strlen($live) === 0 || $live === $args['original_live'] || !is_file($live)) {
				return FALSE;
			}

			$current = $this->identifyImage(['path'=>$live]);

			$staged = $live . '.ggcms_upscale';

			$resize_args = [
				'filelocation'=>$args['from'],
				'resizedlocation'=>$staged,
			];

			$made = ($variant === 'icon')
				? $this->makeIcon($resize_args)
				: $this->makeStandardImage($resize_args);

			if(!$made || !is_file($staged) || filesize($staged) === 0) {
				if(is_file($staged)) {
					unlink($staged);
				}

				return 'could not make the ' . $variant . ' image';
			}

			$check = $this->identifyImage(['path'=>$staged]);

			if(!$check) {
				unlink($staged);

				return 'the new ' . $variant . ' image is not readable';
			}

			if($current && $current['width'] === $check['width'] && $current['height'] === $check['height']) {
				unlink($staged);

				return FALSE;
			}

			return [
				'staged'=>$staged,
				'live'=>$live,
				'width'=>$check['width'],
				'height'=>$check['height'],
			];
		}

		public function discardStaged($args) {
			foreach($args['staged'] as $file) {
				if(is_file($file['staged'])) {
					unlink($file['staged']);
				}
			}

			return TRUE;
		}

			/*
				One UPDATE for every column that moved, measured from the files
				as written, as shrink_images.php does: ImageMagick's rounding
				is its own.
			*/

		public function updateDimensions($args) {
			$row = $args['row'];
			$fields = $this->dimensionFields();

			$assignments = [];
			$values = [];

			foreach($args['staged'] as $variant=>$file) {
				$assignments[] = $fields[$variant][0] . ' = ?';
				$assignments[] = $fields[$variant][1] . ' = ?';

				$values[] = (int)$file['width'];
				$values[] = (int)$file['height'];
			}

			$id = (int)$row['id'];
			$values[] = $id;

			$query = 'UPDATE Image SET ' . implode(', ', $assignments) . ' WHERE id = ?';

			$statement = $this->db_link->prepare($query);

			if(!$statement || !$statement->bind_param(str_repeat('i', count($values)), ...$values) || !$statement->execute()) {
				$by_hand = [];

				foreach($args['staged'] as $variant=>$file) {
					$by_hand[] = $fields[$variant][0] . '=' . $file['width'] . ', ' . $fields[$variant][1] . '=' . $file['height'];
				}

				return 'FILES REPLACED BUT ROW NOT UPDATED -- set ' . implode(', ', $by_hand) . ' on Image ' . $id . ' by hand';
			}

			$statement->close();

			return TRUE;
		}

			// The ledger
			// -----------------------------------------------

		public function ledgerPath() {
			return $this->imageBackupDomainRoot() . 'upscaled.tsv';
		}

		public function appendToLedger($args) {
			$path = $this->ledgerPath();

			$directory = dirname($path);

			if(!is_dir($directory) && !mkdir($directory, 0755, TRUE)) {
				return FALSE;
			}

			if(!is_file($path)) {
				$header = '# relative_path' . "\t" . 'sha256' . "\t" . 'image_id' . "\t" . 'model';
				$header .= "\t" . 'from' . "\t" . 'to' . "\t" . 'written' . "\n";

				file_put_contents($path, $header);
			}

			$line = implode("\t", [
				$args['relative'],
				$args['hash'],
				$args['image_id'],
				$args['model'],
				$args['from'],
				$args['to'],
				date('c'),
			]) . "\n";

			return file_put_contents($path, $line, FILE_APPEND);
		}

			// The row cache
			// -----------------------------------------------

		public function invalidateRowCache($args) {
			require_once(GGCMS_DIR . 'classes/Database/DBFileCache.php');

			$cache = new DBFileCache(['handler'=>NULL]);

			$directory = $cache->DBFileCacheLocation();
			$directory .= '/' . $this->ReverseDomainName(['domain'=>strtolower($this->domain)]);
			$directory .= '/ggcms_EntryChildRecords/Image/';

			if(!is_dir($directory)) {
				print('No cached Image rows to invalidate.' . "\n\n");

				return TRUE;
			}

			$deleted = 0;

			foreach($args['entry_ids'] as $entry_id) {
				$file = $directory . $entry_id;

				if(is_file($file) && @unlink($file)) {
					$deleted++;
				}
			}

			print('Invalidated ' . $deleted . ' cached Image rows across ' . count($args['entry_ids']) . ' entries.' . "\n\n");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Upscaled Image Import';
		}

		public function confirmDomainText() {
			return 'Importing upscaled images for: ';
		}
	}

?>

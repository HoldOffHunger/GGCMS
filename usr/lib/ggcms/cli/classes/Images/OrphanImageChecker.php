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
		Which files nothing names, and which of those are safe to believe are
		rubbish.

		scan_images.php counts orphans.  This one asks the question that comes
		after the count, because the count on its own invites exactly the wrong
		conclusion: revoltlib has 1,680 files no Image row names, holding
		396.7 MB, and a good number of them are neither rubbish nor deletable.

		**This tool reads and reports.  It deletes nothing and moves nothing.**
		The files are scans of artwork and photographs of people who are dead,
		there is no second copy of them anywhere, and no classification here is
		confident enough to justify a delete flag.  What it is for is telling
		you which orphans have an explanation and which genuinely have none,
		so that a decision about the rest is made against evidence.

		## The classes

		`prefix-twin` -- the file is <Entryid>-<name> and a row in the same
		directory names <name>.  These are not orphans at all: they are the
		real files, and their rows are naming them wrongly.  See
		repair_image_filenames.php.  Deleting one would destroy the picture and
		leave the broken row behind.

		`variant-of-orphan` -- an -icon or -standard belonging to an orphan
		base.  It shares its base's fate and should never be judged separately.

		`text-referenced` -- the filename appears in an entry's body text, a
		comment, a description or a definition.  No Image row names it, but a
		page does, and deleting it breaks that page.

		`unreferenced` -- nothing found anywhere.  Still not a delete
		instruction; it is the set worth looking at by hand.

		## On the text search

		The naive form of that check is a LIKE for every orphan against every
		body of text, which on revoltlib is 1,680 queries against a mediumtext
		column.  Instead the text is narrowed once with a single LIKE for the
		file extensions, which on revoltlib returns 110 rows out of thousands,
		and the matching happens in PHP against that.  Same answer, one query.
	*/

	class OrphanImageChecker {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;
		use ImageRows;

			// Entry Point
			// -----------------------------------------------

		public function checkOrphanImages() {
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

			$files = $this->walkImageFiles([
				'minimum_size'=>$this->arguments['minimum_size'],
			]);

			$rows = $this->loadImageRows();
			$expected = $this->buildExpectedFileMap(['rows'=>$rows]);

			$orphans = [];

			foreach($files as $file) {
				if(array_key_exists($file['relative'], $expected)) {
					continue;
				}

				$orphans[] = $file;
			}

			if(!$orphans) {
				print('No orphans.  Every file on disk is named by a row.' . "\n\n");

				return TRUE;
			}

			$classified = $this->classify([
				'orphans'=>$orphans,
				'expected'=>$expected,
			]);

			return $this->report([
				'classified'=>$classified,
				'orphan_count'=>count($orphans),
				'file_count'=>count($files),
			]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'minimum_size'=>0,
				'limit'=>15,
				'class'=>'all',
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
				} else if(substr($argument, 0, 8) === '--class=') {
					$arguments['class'] = substr($argument, 8);
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
			print('GGCMS - Orphan Image Checker' . "\n\n");
			print('  check_orphan_images.php DOMAIN [--min-size=1M] [--limit=15]' . "\n");
			print('                                 [--class=NAME] [--all]' . "\n\n");
			print('  Classifies files that no Image row names.  Reads only; deletes nothing.' . "\n\n");
			print('  prefix-twin        the real file, whose row names it wrongly' . "\n");
			print('  variant-of-orphan  an -icon or -standard of another orphan' . "\n");
			print('  text-referenced    named in body text, a comment or a description' . "\n");
			print('  unreferenced       nothing anywhere names it' . "\n\n");

			return TRUE;
		}

			// Classifying
			// -----------------------------------------------

		public function classify($args) {
			$orphans = $args['orphans'];
			$expected = $args['expected'];

			$text = $this->loadReferencingText();

			$orphan_bases = $this->buildOrphanBaseMap(['orphans'=>$orphans]);

			$classified = [
				'prefix-twin'=>[],
				'variant-of-orphan'=>[],
				'text-referenced'=>[],
				'unreferenced'=>[],
			];

			foreach($orphans as $orphan) {
				$class = $this->classifyOne([
					'orphan'=>$orphan,
					'expected'=>$expected,
					'orphan_bases'=>$orphan_bases,
					'text'=>$text,
				]);

				$classified[$class][] = $orphan;
			}

			return $classified;
		}

		public function classifyOne($args) {
			$orphan = $args['orphan'];
			$expected = $args['expected'];
			$orphan_bases = $args['orphan_bases'];
			$text = $args['text'];

			$relative = $orphan['relative'];
			$directory = dirname($relative);
			$filename = basename($relative);

				/*
					A leading run of digits and a hyphen is the Entryid prefix.
					If stripping it leaves a name some row in the same directory
					does claim, this file is that row's picture and the row is
					simply naming it wrongly.
				*/

			if(preg_match('/^[0-9]+-(.+)$/', $filename, $matches)) {
				if(array_key_exists($directory . '/' . $matches[1], $expected)) {
					return 'prefix-twin';
				}
			}

				/*
					Judged after prefix-twin on purpose.  An -icon whose base is
					a prefix-twin is itself a prefix-twin, and the check above
					has already said so.
				*/

			if(preg_match('/^(.+)-(icon|standard)(\.[A-Za-z0-9]+)$/', $filename, $matches)) {
				$base = $directory . '/' . $matches[1] . $matches[3];

				if(array_key_exists($base, $orphan_bases)) {
					return 'variant-of-orphan';
				}
			}

			if($this->isNamedInText(['filename'=>$filename, 'text'=>$text])) {
				return 'text-referenced';
			}

			return 'unreferenced';
		}

		public function buildOrphanBaseMap($args) {
			$bases = [];

			foreach($args['orphans'] as $orphan) {
				$bases[$orphan['relative']] = TRUE;
			}

			return $bases;
		}

			// The text side
			// -----------------------------------------------

			/*
				Narrowed by extension in one query rather than asked once per
				orphan.  On revoltlib this returns 110 rows out of thousands,
				and every orphan is then matched against those in memory.
			*/

		public function loadReferencingText() {
			$sources = [
				['table'=>'TextBody', 'column'=>'Text'],
				['table'=>'Comment', 'column'=>'Comment'],
				['table'=>'Description', 'column'=>'Description'],
				['table'=>'Definition', 'column'=>'Definition'],
			];

			$text = [];

			foreach($sources as $source) {
				$conditions = [];

				foreach($this->imageExtensions() as $extension) {
					$conditions[] = $source['column'] . ' LIKE "%.' . $extension . '%"';
				}

				$query = 'SELECT ' . $source['column'] . ' AS Body FROM ' . $source['table'];
				$query .= ' WHERE ' . implode(' OR ', $conditions);

				$rows = $this->runQuery(['query'=>$query]);

				foreach($rows as $row) {
					$text[] = $row['Body'];
				}
			}

			return $text;
		}

		public function isNamedInText($args) {
			$filename = $args['filename'];

			foreach($args['text'] as $body) {
				if(strpos($body, $filename) !== FALSE) {
					return TRUE;
				}
			}

			return FALSE;
		}

			// Reporting
			// -----------------------------------------------

		public function report($args) {
			$classified = $args['classified'];

			print($args['orphan_count'] . ' of ' . $args['file_count'] . ' files on disk are named by no Image row.' . "\n\n");

			$summary = [];

			foreach($classified as $class=>$files) {
				$bytes = 0;

				foreach($files as $file) {
					$bytes += $file['size'];
				}

				$summary[] = [
					'Class'=>$class,
					'Files'=>count($files),
					'Bytes'=>$this->formatBytes(['number'=>$bytes]),
					'Meaning'=>$this->classMeaning(['class'=>$class]),
				];
			}

			print(arr2textTable($summary));
			print("\n");

			foreach($classified as $class=>$files) {
				if(!$files) {
					continue;
				}

				if($this->arguments['class'] !== 'all' && $this->arguments['class'] !== $class) {
					continue;
				}

				$this->printClass(['class'=>$class, 'files'=>$files]);
			}

			if($classified['prefix-twin']) {
				print('The prefix-twins are real pictures whose rows name them wrongly.' . "\n");
				print('  repair_image_filenames.php ' . $this->domain . "\n\n");
			}

			print('Nothing here has been deleted or moved.  This tool only reads.' . "\n\n");

			return TRUE;
		}

		public function classMeaning($args) {
			$meanings = [
				'prefix-twin'=>'the real file; its row names it wrongly',
				'variant-of-orphan'=>'icon or standard of another orphan',
				'text-referenced'=>'named in body text; a page links it',
				'unreferenced'=>'nothing anywhere names it',
			];

			return $meanings[$args['class']];
		}

		public function printClass($args) {
			$class = $args['class'];
			$files = $args['files'];

			$limit = $this->arguments['all'] ? 0 : $this->arguments['limit'];
			$shown = ($limit > 0 && count($files) > $limit) ? array_slice($files, 0, $limit) : $files;

			$table = [];

			foreach($shown as $file) {
				$table[] = [
					'Size'=>$this->formatBytes(['number'=>$file['size']]),
					'File'=>$file['relative'],
				];
			}

			print($class . ' -- heaviest first');

			if(count($shown) < count($files)) {
				print(', showing ' . count($shown) . ' of ' . count($files));
			}

			print("\n\n");
			print(arr2textTable($table));
			print("\n");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Orphan Image Checker';
		}

		public function confirmDomainText() {
			return 'Checking orphan images for: ';
		}
	}

?>

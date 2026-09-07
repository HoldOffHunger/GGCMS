<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/ImageFiles.php');

	/*
		`Image::3` in an entry's text, against the images that entry actually
		has.

		## What the markup means

		Not an Image id.  `view.php:735` resolves it as a one-based index into
		the entry's own image list:

			$number = (int)$dom_piece;
			$image = $images[$number - 1];

		so `Image::3` is the third image attached to this entry, and the same
		number means a different picture on every page.  `FullImage::` works
		the same way through `formatImageText_fulls`.

		## Why a dangling reference is invisible

		The line after it is this:

			if($mobile_friendly || !$image) {
				$dom[$i] = '';
			}

		A reference past the end of the list is replaced with an empty string.
		No warning, no ticket, no gap in the markup -- the paragraph simply
		closes over where the picture was meant to be, and reads as though it
		was never meant to have one.  `index.php` sets `error_reporting(0)`, so
		a page missing an illustration and a page never given one are
		identical from outside.  Nothing but this tool will tell you.

		## The two questions, and only one of them is a defect

		**Dangling** references are a defect: markup pointing at an image that
		is not there, rendering nothing.

		**Unreferenced** images are usually not.  Templates display an entry's
		images on their own through the icon and standard blocks; the `Image::`
		markup is for placing one inline in the prose, and most images are
		never placed inline by design.  It is reported because it was asked
		for and because it is occasionally interesting -- an entry that
		references images 1 and 2 and has five may have lost three references
		in an edit -- but it is listed as information and not as a fault.
	*/

	class ImageReferenceChecker {
		use ByteDisplay;
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use ImageFiles;

			// Entry Point
			// -----------------------------------------------

		public function checkImageReferences() {
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

			$this->setGlobals();
			$this->setMySQLArgs();

			$counts = $this->loadImageCountsByEntry();
			$bodies = $this->loadBodiesWithMarkup();

			print('Entries carrying Image:: markup: ' . count($bodies) . "\n");
			print('Entries carrying images:         ' . count($counts) . "\n\n");

			$findings = $this->examine([
				'bodies'=>$bodies,
				'counts'=>$counts,
			]);

			return $this->report(['findings'=>$findings]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'check'=>'default',
				'limit'=>25,
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
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int)substr($argument, 8);
				}
			}

			return $this->arguments = $arguments;
		}

		public function wantsCheck($args) {
			$check = $args['check'];
			$requested = $this->arguments['check'];

			if($requested === 'all') {
				return TRUE;
			}

			if($requested === 'default') {
				return $check !== 'unreferenced';
			}

			return $requested === $check;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Image Reference Checker' . "\n\n");
			print('  check_image_references.php DOMAIN [--check=dangling|noimages|unreferenced|all]' . "\n");
			print('                                    [--limit=25] [--all]' . "\n\n");
			print('  Image::N is a one-based index into the entry\'s own images, not an id.' . "\n");
			print('  A reference past the end renders as an empty string, silently.' . "\n\n");
			print('  dangling      Image::N where the entry has fewer than N images' . "\n");
			print('  noimages      markup on an entry that has no images at all' . "\n");
			print('  unreferenced  images no marker points at; information, not a fault,' . "\n");
			print('                so it is not in the default run' . "\n\n");

			return TRUE;
		}

			// Reading
			// -----------------------------------------------

		public function loadImageCountsByEntry() {
			$rows = $this->runQuery([
				'query'=>'SELECT Entryid, COUNT(*) AS ImageCount FROM Image GROUP BY Entryid',
			]);

			$counts = [];

			foreach($rows as $row) {
				$counts[(int)$row['Entryid']] = (int)$row['ImageCount'];
			}

			return $counts;
		}

			/*
				Entry is joined for the title so a finding names a page rather
				than a row id.  The full URL would mean walking the assignment
				chain for every hit, which is a query per entry to add nothing
				the title does not already give.
			*/

		public function loadBodiesWithMarkup() {
			$query = 'SELECT TextBody.Entryid, TextBody.Text, Entry.Title, Entry.Code ';
			$query .= 'FROM TextBody LEFT JOIN Entry ON Entry.id = TextBody.Entryid ';
			$query .= 'WHERE TextBody.Text LIKE "%Image::%" ';
			$query .= 'ORDER BY TextBody.Entryid';

			return $this->runQuery(['query'=>$query]);
		}

			// Examining
			// -----------------------------------------------

			/*
				One alternation rather than two passes.  "FullImage::1"
				contains "Image::1" as a substring, so a regex for Image::
				alone would count every full-size reference twice and report
				the second copy as a phantom.  With (?:Full)? leading, the
				engine consumes the longer form where it exists -- which is
				also the order view.php runs its two replacements in, and for
				the same reason.
			*/

		public function markupPattern() {
			return '/(?:Full)?Image::([0-9]+)/';
		}

		public function examine($args) {
			$bodies = $args['bodies'];
			$counts = $args['counts'];

			$dangling = [];
			$noimages = [];
			$unreferenced = [];

			foreach($bodies as $body) {
				$entry_id = (int)$body['Entryid'];

				if(!preg_match_all($this->markupPattern(), $body['Text'], $matches)) {
					continue;
				}

				$referenced = [];

				foreach($matches[1] as $number) {
					$referenced[(int)$number] = TRUE;
				}

				$available = array_key_exists($entry_id, $counts) ? $counts[$entry_id] : 0;

				if($available === 0) {
					$noimages[] = [
						'Entryid'=>$entry_id,
						'References'=>implode(', ', array_keys($referenced)),
						'Title'=>$this->shorten(['text'=>$body['Title']]),
					];

					continue;
				}

				$bad = [];

				foreach(array_keys($referenced) as $number) {
					if($number >= 1 && $number <= $available) {
						continue;
					}

					$bad[] = $number;
				}

				if($bad) {
					sort($bad);

					$dangling[] = [
						'Entryid'=>$entry_id,
						'Points at'=>implode(', ', $bad),
						'Has'=>$available,
						'Title'=>$this->shorten(['text'=>$body['Title']]),
					];
				}

				$missing = [];

				for($i = 1; $i <= $available; $i++) {
					if(!array_key_exists($i, $referenced)) {
						$missing[] = $i;
					}
				}

				if($missing) {
					$unreferenced[] = [
						'Entryid'=>$entry_id,
						'Not placed'=>implode(', ', $missing),
						'Has'=>$available,
						'Title'=>$this->shorten(['text'=>$body['Title']]),
					];
				}
			}

			return [
				'dangling'=>$dangling,
				'noimages'=>$noimages,
				'unreferenced'=>$unreferenced,
			];
		}

		public function shorten($args) {
			$text = (string)$args['text'];

			if(strlen($text) <= 52) {
				return $text;
			}

			return substr($text, 0, 49) . '...';
		}

			// Reporting
			// -----------------------------------------------

		public function report($args) {
			$findings = $args['findings'];

			$printed = FALSE;

			if($this->wantsCheck(['check'=>'dangling'])) {
				$printed = $this->printFinding([
					'rows'=>$findings['dangling'],
					'heading'=>'Dangling references -- the markup renders as nothing, silently',
					'clear'=>'No dangling references.  Every Image:: points at an image that exists.',
				]) || $printed;
			}

			if($this->wantsCheck(['check'=>'noimages'])) {
				$printed = $this->printFinding([
					'rows'=>$findings['noimages'],
					'heading'=>'Markup on entries that have no images at all',
					'clear'=>'No entry carries Image:: markup without images.',
				]) || $printed;
			}

			if($this->wantsCheck(['check'=>'unreferenced'])) {
				print('Images no marker places inline.  This is information, not a fault --' . "\n");
				print('templates show an entry\'s images without any markup, and most images' . "\n");
				print('are never placed in the prose by design.' . "\n\n");

				$this->printFinding([
					'rows'=>$findings['unreferenced'],
					'heading'=>'Entries with images their text does not place',
					'clear'=>'Every image on every entry is placed inline somewhere.',
				]);
			}

			return TRUE;
		}

		public function printFinding($args) {
			$rows = $args['rows'];

			if(!$rows) {
				print($args['clear'] . "\n\n");

				return FALSE;
			}

			$limit = $this->arguments['all'] ? 0 : $this->arguments['limit'];
			$shown = ($limit > 0 && count($rows) > $limit) ? array_slice($rows, 0, $limit) : $rows;

			print($args['heading'] . ' -- ' . count($rows) . "\n\n");
			print(arr2textTable($shown));

			if(count($shown) < count($rows)) {
				print("\n" . 'Showing ' . count($shown) . ' of ' . count($rows) . '; --all for the rest.' . "\n");
			}

			print("\n");

			return TRUE;
		}

			// Script-Level Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Image Reference Checker';
		}

		public function confirmDomainText() {
			return 'Checking Image:: references for: ';
		}
	}

?>

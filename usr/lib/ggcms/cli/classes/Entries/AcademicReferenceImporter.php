<?php

	clireq('traits/CLIAccess.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/ErrorCLI.php');

	/*
		Imports academic references -- which published book backs which
		entry, and where in it -- from a citations file into AcademicReference.

		The file is JSON lines, one citation each, in AcademicReference's own
		field names, as the reference worker writes them beside its results:

		  {"Entryid": 68, "ReferenceCode": "the-oxford-spanish-dictionary",
		   "Headword": "turkey", "Page": "1828", "Verdict": "supports", ...}

		The book is named by its entry's code under the site's Reference
		branch -- the child coded "reference" of the site's root -- and the
		book entry must exist already: this tool makes citations, never books.

		Dry by default.  Each citation is one row per entry and book: a new one
		is inserted, a changed one updated, an identical one left alone, so
		running it twice changes nothing the second time.  A citation whose
		entry or book is not on the site is skipped and said.

		Only the verdicts asked for are taken, supports alone by default,
		since a book that disagrees is review material rather than something
		to import unread.
	*/

	class AcademicReferenceImporter {
		use CLIAccess;
		use DBAccess;
		use DomainValidation;
		use ErrorCLI;

		public $citations_location;
		public $verdicts;
		public $limit;
		public $apply;
		public $books;
		public $counts;
		public $skipped;

		public function bannerMessageText() {
			return 'Import Academic References';
		}

		public function confirmDomainText() {
			return 'Importing academic references to: ';
		}

			// Entry point
			// -----------------------------------------------

		public function importAcademicReferences() {
			$this->bannerMessage();
			$this->setHandle();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setOptions();

			if(!is_file($this->citations_location)) {
				return $this->cancelAction(['message'=>'No citations file at ' . $this->citations_location . '.  Pass --citations= to point at one.']);
			}

			$this->setMySQLArgs();

			if(!$this->db_link || $this->db_link->connect_errno) {
				return $this->cancelAction(['message'=>'Could not connect to the ' . $this->host . ' database.']);
			}

			$this->books = $this->referenceBooks();

			if(!count($this->books)) {
				return $this->cancelAction(['message'=>'This site has no Reference branch with books under it: an entry coded `reference` under the root, its books beneath.']);
			}

			$this->importCitations();
			$this->reportResults();

			if($this->apply && ($this->counts['inserted'] || $this->counts['updated'])) {
				$this->flushPageCache();
			}

			return TRUE;
		}

			// Options
			// -----------------------------------------------

			/*
				Narrowest useful scope by default: twenty citations, supports
				only, and no writing.  Everything wider is asked for.
			*/

		public function setOptions() {
			$this->citations_location = $this->option(['name'=>'citations', 'default'=>'']);
			$this->verdicts = array_filter(array_map('trim', explode(',', $this->option(['name'=>'verdicts', 'default'=>'supports']))));
			$this->limit = (int)$this->option(['name'=>'limit', 'default'=>'20']);
			$this->apply = $this->flag(['name'=>'apply']);

			if($this->limit < 1) {
				$this->limit = 1;
			}

			print('Citations : ' . $this->citations_location . PHP_EOL);
			print('Verdicts  : ' . implode(', ', $this->verdicts) . PHP_EOL);
			print('Limit     : ' . $this->limit . PHP_EOL);
			print('Mode      : ' . ($this->apply ? 'APPLY -- writes to the database' : 'dry run -- nothing is written') . PHP_EOL);
			print(PHP_EOL);

			return TRUE;
		}

		public function option($args) {
			$name = $args['name'];
			$default = $args['default'];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--' . $name . '=') === 0) {
					return substr($argument, strlen($name) + 3);
				}
			}

			return $default;
		}

		public function flag($args) {
			return in_array('--' . $args['name'], $this->argv);
		}

			// The books
			// -----------------------------------------------

			/*
				Code => entry id for every book under the Reference branch, found
				as modify.php's ReferenceChoices() finds them: the child coded
				"reference" of an entry that is no one's child, so a word deeper
				in the tree coded "reference" -- EarthFluent has thirteen, one per
				language -- is never taken for the library.
			*/

		public function referenceBooks() {
			$sql =
				'SELECT Book.id, Book.Code FROM Entry Book ' .
				'JOIN Assignment ToBook ON ToBook.Childid = Book.id ' .
				'JOIN Entry Library ON Library.id = ToBook.Parentid AND Library.Code = ? ' .
				'JOIN Assignment ToLibrary ON ToLibrary.Childid = Library.id ' .
				'WHERE NOT EXISTS (SELECT 1 FROM Assignment Above WHERE Above.Childid = ToLibrary.Parentid)';

			$statement = $this->db_link->prepare($sql);
			$library = 'reference';
			$statement->bind_param('s', $library);
			$statement->execute();

			$books = [];

			foreach($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
				$books[$row['Code']] = (int)$row['id'];
			}

			return $books;
		}

			// Importing
			// -----------------------------------------------

		public function importCitations() {
			$this->counts = ['inserted'=>0, 'updated'=>0, 'unchanged'=>0, 'skipped'=>0, 'not asked for'=>0];
			$this->skipped = [];
			$taken = 0;

			foreach(file($this->citations_location, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				$citation = json_decode($line, TRUE);

				if(!is_array($citation)) {
					continue;
				}

				if(!in_array($citation['Verdict'] ?? '', $this->verdicts, TRUE)) {
					$this->counts['not asked for']++;
					continue;
				}

				if($taken >= $this->limit) {
					break;
				}

				$taken++;

				$this->importCitation(['citation'=>$citation]);
			}

			return TRUE;
		}

		public function importCitation($args) {
			$citation = $args['citation'];
			$entry_id = (int)($citation['Entryid'] ?? 0);
			$code = (string)($citation['ReferenceCode'] ?? '');

			if(!isset($this->books[$code])) {
				return $this->skip(['citation'=>$citation, 'reason'=>'no book coded "' . $code . '" under the Reference branch']);
			}

			if(!$entry_id || !$this->entryExists(['id'=>$entry_id])) {
				return $this->skip(['citation'=>$citation, 'reason'=>'no entry ' . $entry_id]);
			}

			$row = $this->citationRow(['citation'=>$citation, 'book'=>$this->books[$code]]);
			$live = $this->liveCitation(['entryid'=>$entry_id, 'book'=>$this->books[$code]]);

			if($live) {
				$changed = FALSE;

				foreach($row as $field => $value) {
					if((string)$live[$field] !== (string)$value) {
						$changed = TRUE;
					}
				}

				if(!$changed) {
					$this->counts['unchanged']++;
					return TRUE;
				}

				if($this->apply) {
					$this->writeCitation(['row'=>$row, 'id'=>$live['id']]);
				}

				$this->counts['updated']++;
				return TRUE;
			}

			if($this->apply) {
				$this->writeCitation(['row'=>$row, 'id'=>0]);
			}

			$this->counts['inserted']++;

			return TRUE;
		}

			/*
				The row as AcademicReference holds it.  Every text is cut to
				its column, as MySQL would refuse the insert otherwise; the
				lengths are the table description's.
			*/

		public function citationRow($args) {
			$citation = $args['citation'];

			$lengths = [
				'Headword'=>255,
				'VolumeNumber'=>32,
				'VolumeTitle'=>255,
				'ChapterNumber'=>32,
				'ChapterTitle'=>255,
				'Page'=>32,
				'Note'=>512,
			];

			$row = [
				'Entryid'=>(int)$citation['Entryid'],
				'ReferenceEntryid'=>$args['book'],
				'Verdict'=>$citation['Verdict'],
			];

			foreach($lengths as $field => $length) {
				$row[$field] = mb_substr(trim((string)($citation[$field] ?? '')), 0, $length);
			}

			return $row;
		}

		public function entryExists($args) {
			$statement = $this->db_link->prepare('SELECT id FROM Entry WHERE id = ?');
			$statement->bind_param('i', $args['id']);
			$statement->execute();

			return (bool)$statement->get_result()->fetch_assoc();
		}

		public function liveCitation($args) {
			$statement = $this->db_link->prepare('SELECT * FROM AcademicReference WHERE Entryid = ? AND ReferenceEntryid = ? ORDER BY id LIMIT 1');
			$statement->bind_param('ii', $args['entryid'], $args['book']);
			$statement->execute();

			return $statement->get_result()->fetch_assoc();
		}

		public function writeCitation($args) {
			$row = $args['row'];
			$fields = array_keys($row);
			$values = array_values($row);

			if($args['id']) {
				$sql = 'UPDATE AcademicReference SET ' . implode(' = ?, ', $fields) . ' = ?, LastModificationDate = NOW() WHERE id = ?';
				$values[] = $args['id'];
			} else {
				$sql = 'INSERT INTO AcademicReference (' . implode(', ', $fields) . ', OriginalCreationDate, LastModificationDate) VALUES (' .
					implode(', ', array_fill(0, count($fields), '?')) . ', NOW(), NOW())';
			}

			$statement = $this->db_link->prepare($sql);
			$statement->bind_param(str_repeat('s', count($values)), ...$values);

			return $statement->execute();
		}

		public function skip($args) {
			$this->counts['skipped']++;

			if(count($this->skipped) < 20) {
				$this->skipped[] = 'entry ' . ($args['citation']['Entryid'] ?? '?') . ': ' . $args['reason'];
			}

			return FALSE;
		}

			// Reporting
			// -----------------------------------------------

		public function reportResults() {
			if(count($this->skipped)) {
				print('Skipped (the first ' . count($this->skipped) . '):' . PHP_EOL);

				foreach($this->skipped as $line) {
					print('  ' . $line . PHP_EOL);
				}

				print(PHP_EOL);
			}

			$verb = $this->apply ? '' : 'would be ';

			print($this->counts['inserted'] . ' ' . $verb . 'inserted, ' . $this->counts['updated'] . ' ' . $verb . 'updated, ');
			print($this->counts['unchanged'] . ' unchanged, ' . $this->counts['skipped'] . ' skipped; ');
			print($this->counts['not asked for'] . ' of other verdicts left out.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

			// The page cache
			// -----------------------------------------------

			/*
				As TranslationWriter::flushPageCache(): a shell has no
				HTTP_HOST for the engine's own flush, so both hostnames are
				flushed by name, and Cloudflare purged where there is a token.
			*/

		public function flushPageCache() {
			if(!class_exists('PageCache')) {
				ggreq('classes/Cache/PageCache.php');
			}

			$page_cache = new PageCache(['handler'=>NULL]);

			foreach([$this->domain, 'www.' . $this->domain] as $host) {
				if($page_cache->FlushDomain(['domain'=>$host])) {
					print('Flushed the page cache for ' . $host . '.' . PHP_EOL);
				}
			}

			if(!class_exists('EdgeCache')) {
				ggreq('classes/Cache/EdgeCache.php');
			}

			$edge_cache = new EdgeCache([]);

			if($edge_cache->Token() !== FALSE && $edge_cache->PurgeDomain(['domain'=>$this->domain])) {
				print('Purged Cloudflare\'s cache for ' . $this->domain . '.' . PHP_EOL);
			}

			return TRUE;
		}
	}

?>

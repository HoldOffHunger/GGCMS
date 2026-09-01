<?php

	clireq('traits/CLIAccess.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/ErrorCLI.php');

	/*
		Applies the corrections in Development/TranslationReview/<lang>.txt to
		EntryTranslation, and marks them shipped in the store as it goes.

		Dry by default.  It prints what it would do and changes nothing unless
		--apply is passed, because this writes to a live site and the operator
		should see the list before it happens rather than afterwards.

		Two things it refuses to do:

		  A record whose `current` no longer matches the live row is skipped,
		  never overwritten.  That row has been edited since it was reviewed,
		  and the edit is more recent evidence than the review is.

		  A record already marked shipped is skipped, so running the tool twice
		  is not an error and does not re-apply anything.
	*/

	class TranslationWriter {
		use CLIAccess;
		use DBAccess;
		use DomainValidation;
		use ErrorCLI;

		public function bannerMessageText() {
			return 'Apply Translation Review';
		}

		public function confirmDomainText() {
			return 'Applying translation corrections to: ';
		}

			// Entry point
			// -----------------------------------------------

		public function applyTranslationReview() {
			$this->bannerMessage();
			$this->setHandle();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setOptions();

			if(!$this->setStoreLocation()) {
				return $this->cancelAction(['message'=>'No review file at ' . $this->store_location . '.  Pass --store= to point at one.']);
			}

			$this->records = $this->readStore();

			if(!count($this->records)) {
				return $this->cancelAction(['message'=>'No records with status: proposed in ' . $this->store_location . '.']);
			}

			$this->setMySQLArgs();

			if(!$this->db_link || $this->db_link->connect_errno) {
				return $this->cancelAction(['message'=>'Could not connect to the ' . $this->host . ' database.']);
			}

			$this->applyRecords();
			$this->reportResults();

			if($this->apply) {
				$this->markShipped();
				$this->flushPageCache();
			}

			return TRUE;
		}

			// Options
			// -----------------------------------------------

			/*
				Narrowest useful scope by default: one language, twenty records,
				and no writing.  Everything wider is asked for explicitly.
			*/

		public function setOptions() {
			$this->language = $this->option(['name'=>'lang', 'default'=>'es']);
			$this->limit = (int)$this->option(['name'=>'limit', 'default'=>'20']);
			$this->apply = $this->flag(['name'=>'apply']);

			if($this->limit < 1) {
				$this->limit = 1;
			}

			print('Language : ' . $this->language . PHP_EOL);
			print('Limit    : ' . $this->limit . PHP_EOL);
			print('Mode     : ' . ($this->apply ? 'APPLY -- writes to the database' : 'dry run -- nothing is written') . PHP_EOL);
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

			// The store
			// -----------------------------------------------

			/*
				Taken as an argument rather than derived.  The review store
				lives in Development/, which is in the repository and not in
				the deployed tree -- deployment copies usr/, etc/ and var/ to
				/, and Development/ is documentation that never travels.

				So the operator says where it is.  Default assumes a checkout
				in the working directory.
			*/

		public function setStoreLocation() {
			$this->store_location = $this->option([
				'name'=>'store',
				'default'=>'Development/TranslationReview/' . $this->language . '.txt',
			]);

			print('Store    : ' . $this->store_location . PHP_EOL . PHP_EOL);

			return is_file($this->store_location);
		}

			/*
				Records are blank-line separated, one `key: value` per line, and
				a value may continue onto following lines indented by spaces.
				Comment lines start with #.
			*/

		public function readStore() {
			$records = [];
			$record = [];
			$key = '';

			$lines = file($this->store_location);

			foreach($lines as $line) {
				$line = rtrim($line, "\r\n");

				if(substr($line, 0, 1) === '#') {
					continue;
				}

				if(trim($line) === '') {
					if(count($record)) {
						$records[] = $record;
						$record = [];
					}

					continue;
				}

				if(substr($line, 0, 2) === '  ' && $key) {
					$record[$key] .= ' ' . trim($line);
					continue;
				}

				$pieces = explode(':', $line, 2);

				if(count($pieces) !== 2) {
					continue;
				}

				$key = trim($pieces[0]);
				$record[$key] = trim($pieces[1]);
			}

			if(count($record)) {
				$records[] = $record;
			}

			return $this->filterRecords(['records'=>$records]);
		}

		public function filterRecords($args) {
			$filtered = [];

			foreach($args['records'] as $record) {
				if(!isset($record['id']) || !isset($record['proposed'])) {
					continue;
				}

				if($record['status'] !== 'proposed') {
					continue;
				}

				if($record['lang'] !== $this->language) {
					continue;
				}

				$filtered[] = $record;

				if(count($filtered) >= $this->limit) {
					break;
				}
			}

			return $filtered;
		}

			// Applying
			// -----------------------------------------------

		public function applyRecords() {
			$this->applied = [];
			$this->skipped = [];

			foreach($this->records as $record) {
				$live = $this->liveTitle(['id'=>$record['id']]);

				if($live === NULL) {
					$record['skip_reason'] = 'no row with that id';
					$this->skipped[] = $record;
					continue;
				}

					/*
						The review is old evidence and the row is new evidence.
						Where they disagree the row wins, and the record goes
						back for another look rather than over the top of
						whatever somebody did in the meantime.
					*/

				if($live !== $record['current']) {
					$record['skip_reason'] = 'row now reads "' . $live . '"';
					$this->skipped[] = $record;
					continue;
				}

				if($this->apply) {
					$this->writeTitle([
						'id'=>$record['id'],
						'title'=>$record['proposed'],
					]);
				}

				$this->applied[] = $record;
			}

			return TRUE;
		}

		public function liveTitle($args) {
			$statement = $this->db_link->prepare('SELECT Title FROM EntryTranslation WHERE id = ?');
			$statement->bind_param('i', $args['id']);
			$statement->execute();

			$result = $statement->get_result();

			if(!$result) {
				return NULL;
			}

			$row = $result->fetch_assoc();

			if(!$row) {
				return NULL;
			}

			return $row['Title'];
		}

		public function writeTitle($args) {
			$statement = $this->db_link->prepare('UPDATE EntryTranslation SET Title = ?, LastModificationDate = NOW() WHERE id = ?');
			$statement->bind_param('si', $args['title'], $args['id']);

			return $statement->execute();
		}

			// Reporting
			// -----------------------------------------------

		public function reportResults() {
			foreach($this->applied as $record) {
				printf("%-8s %-22s %-22s -> %s" . PHP_EOL,
					$record['permalink'],
					$record['english'],
					$record['current'],
					$record['proposed']
				);
			}

			if(count($this->skipped)) {
				print(PHP_EOL . 'Skipped:' . PHP_EOL);

				foreach($this->skipped as $record) {
					printf("%-8s %-22s %s" . PHP_EOL,
						$record['permalink'],
						$record['english'],
						$record['skip_reason']
					);
				}
			}

			print(PHP_EOL);
			print(count($this->applied) . ($this->apply ? ' applied' : ' would be applied') . ', ');
			print(count($this->skipped) . ' skipped.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

			// Marking the store
			// -----------------------------------------------

			/*
				Rewritten in place, one line changed per applied record: the
				`status: proposed` that follows its `id:`.  The file keeps its
				comments, its spacing and its order, so the diff is exactly the
				records that shipped.
			*/

		public function markShipped() {
			$shipped_ids = [];

			foreach($this->applied as $record) {
				$shipped_ids[$record['id']] = TRUE;
			}

			$lines = file($this->store_location);
			$output = [];
			$current_id = '';

			foreach($lines as $line) {
				$trimmed = rtrim($line, "\r\n");

				if(strpos($trimmed, 'id: ') === 0) {
					$current_id = trim(substr($trimmed, 4));
				}

				if($trimmed === 'status: proposed' && isset($shipped_ids[$current_id])) {
					$output[] = 'status: shipped ' . date('Y-m-d') . "\n";
					continue;
				}

				$output[] = $line;
			}

			file_put_contents($this->store_location, implode('', $output));

			print('Marked ' . count($shipped_ids) . ' records shipped in ' . $this->store_location . '.' . PHP_EOL);

			return TRUE;
		}

			// The page cache
			// -----------------------------------------------

			/*
				The web engine flushes this for itself: every write in DBAccess
				calls MarkPageCacheDirty(), which registers a shutdown function
				that calls FlushDomain([]) -- and FlushDomain with no domain
				falls through to SafeHost(), which reads $_SERVER['HTTP_HOST'].

				There is no HTTP_HOST in a shell.  SafeHost() would return FALSE,
				FlushDomain would return FALSE, and every page corrected here
				would go on serving the old word until something else happened
				to flush it.  Silently, because FlushPageCacheNow swallows its
				own exceptions.

				So the domain is passed explicitly.  FlushDomain already accepts
				one; nothing needed changing but the calling.
			*/

		public function flushPageCache() {
			ggreq('classes/Cache/PageCache.php');

			$page_cache = new PageCache(['handler'=>NULL]);

			$flushed = $page_cache->FlushDomain(['domain'=>'www.' . $this->domain]);

			if($flushed) {
				print('Flushed the page cache for www.' . $this->domain . '.' . PHP_EOL . PHP_EOL);
			} else {
				print('NOTE: nothing was flushed for www.' . $this->domain . '.  If pages still show the old word, clear the cache by hand -- see Docs/PageCache.md.' . PHP_EOL . PHP_EOL);
			}

			return $flushed;
		}
	}

?>

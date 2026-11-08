<?php

	clireq('traits/CLIAccess.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/ErrorCLI.php');
	clireq('classes/Entries/SpellcheckFix.php');

	/*
		Applies reviewed spellcheck fixes to TextBody, and marks them in the
		store as it goes.  The store is the fixes.tsv that the AI Workers'
		ggcms_spellcheck/export_fixes.py writes from a reviewed task.

		Dry by default, like apply_translation_review.php: it prints what it
		would change and writes nothing unless --apply is passed.

		What it refuses to do:

		  A fix whose sentence no longer reads as it did when it was reviewed
		  is skipped, never forced.  SpellcheckFix says why.

		  A text marked Spell Check Immune is never touched, whatever the
		  store says.  Somebody decided its spelling is the point.

		  A line already marked shipped is skipped, so running it twice is
		  not an error and does not re-apply anything.

		Each text is written once, with every fix for it applied in store
		order, and its WordCount and CharacterCount worked out the way
		modify.php works them out.
	*/

	class SpellcheckFixWriter {
		use CLIAccess;
		use DBAccess;
		use DomainValidation;
		use ErrorCLI;

		public $records;
		public $limit;
		public $apply;
		public $store_location;
		public $applied;
		public $skipped;
		public $texts_written;

		public function bannerMessageText() {
			return 'Apply Spellcheck Fixes';
		}

		public function confirmDomainText() {
			return 'Applying spellcheck fixes to: ';
		}

			// Entry point
			// -----------------------------------------------

		public function applySpellcheckFixes() {
			$this->bannerMessage();
			$this->setHandle();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setOptions();

			if(!$this->setStoreLocation()) {
				return $this->cancelAction(['message'=>'No store at ' . $this->store_location . '.  Pass --store= to point at a fixes.tsv.']);
			}

			$this->records = $this->readStore();

			if(!count($this->records)) {
				return $this->cancelAction(['message'=>'No proposed fixes in ' . $this->store_location . '.']);
			}

			$this->setMySQLArgs();

			if(!$this->db_link || $this->db_link->connect_errno) {
				return $this->cancelAction(['message'=>'Could not connect to the ' . $this->host . ' database.']);
			}

			$this->applyRecords();
			$this->reportResults();

			if($this->apply) {
				$this->markStore();

				if($this->texts_written) {
					$this->flushPageCache();
				}
			}

			return TRUE;
		}

			// Options
			// -----------------------------------------------

		public function setOptions() {
			$this->limit = (int)$this->option(['name'=>'limit', 'default'=>'20']);
			$this->apply = $this->flag(['name'=>'apply']);

			if($this->limit < 1) {
				$this->limit = 1;
			}

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
				Always given: the store lives with the AI Workers task that
				produced it, not anywhere in the deployed tree.
			*/

		public function setStoreLocation() {
			$this->store_location = $this->option(['name'=>'store', 'default'=>'fixes.tsv']);

			print('Store    : ' . $this->store_location . PHP_EOL . PHP_EOL);

			return is_file($this->store_location);
		}

		public function readStore() {
			$lines = file($this->store_location, FILE_IGNORE_NEW_LINES);
			$header = explode("\t", array_shift($lines));
			$records = [];

			foreach($lines as $line) {
				if($line === '') {
					continue;
				}

				$record = array_combine($header, array_pad(explode("\t", $line), count($header), ''));

				if($record['status'] !== 'proposed') {
					continue;
				}

				$records[] = $record;

				if(count($records) >= $this->limit) {
					break;
				}
			}

			return $records;
		}

			// Applying
			// -----------------------------------------------

		public function applyRecords() {
			$this->applied = [];
			$this->skipped = [];
			$this->texts_written = 0;

			$fixer = new SpellcheckFix([]);
			$by_text = [];

			foreach($this->records as $record) {
				$by_text[$record['textbody']][] = $record;
			}

			foreach($by_text as $textbody_id => $records) {
				$live = $this->liveText(['id'=>$textbody_id]);

				if($live === NULL) {
					$this->skipAll(['records'=>$records, 'reason'=>'no text body with that id']);
					continue;
				}

				if((int)$live['SpellCheckImmune'] === 1) {
					$this->skipAll(['records'=>$records, 'reason'=>'text is Spell Check Immune']);
					continue;
				}

				$text = $live['Text'];
				$changed = FALSE;

				foreach($records as $record) {
					$result = $fixer->Apply([
						'text'=>$text,
						'word'=>$record['word'],
						'fix'=>$record['fix'],
						'before'=>$record['before'],
						'after'=>$record['after'],
					]);

					if($result['text'] === NULL) {
						$record['skip_reason'] = $result['reason'];
						$this->skipped[] = $record;
						continue;
					}

					$text = $result['text'];
					$record['replacement'] = $result['replacement'];
					$this->applied[] = $record;
					$changed = TRUE;
				}

				if($changed) {
					if($this->apply) {
						$this->writeText(['id'=>$textbody_id, 'text'=>$text]);
					}

					$this->texts_written++;
				}
			}

			return TRUE;
		}

		public function skipAll($args) {
			foreach($args['records'] as $record) {
				$record['skip_reason'] = $args['reason'];
				$this->skipped[] = $record;
			}

			return TRUE;
		}

		public function liveText($args) {
			$statement = $this->db_link->prepare('SELECT Text, SpellCheckImmune FROM TextBody WHERE id = ?');
			$statement->bind_param('i', $args['id']);
			$statement->execute();

			$result = $statement->get_result();

			if(!$result) {
				return NULL;
			}

			return $result->fetch_assoc() ?: NULL;
		}

			// the two counts exactly as modify.php sets them on a save

		public function writeText($args) {
			$text = $args['text'];
			$word_count = str_word_count(strip_tags($text));
			$character_count = strlen($text);

			$statement = $this->db_link->prepare('UPDATE TextBody SET Text = ?, WordCount = ?, CharacterCount = ?, LastModificationDate = NOW() WHERE id = ?');
			$statement->bind_param('siii', $text, $word_count, $character_count, $args['id']);

			return $statement->execute();
		}

			// Reporting
			// -----------------------------------------------

		public function reportResults() {
			foreach($this->applied as $record) {
				printf("%-7s entry %-6s %-20s -> %s" . PHP_EOL,
					$record['id'],
					$record['entry'],
					$record['word'],
					$record['replacement']
				);
			}

			if(count($this->skipped)) {
				print(PHP_EOL . 'Skipped:' . PHP_EOL);

				foreach($this->skipped as $record) {
					printf("%-7s entry %-6s %-20s %s" . PHP_EOL,
						$record['id'],
						$record['entry'],
						$record['word'],
						$record['skip_reason']
					);
				}
			}

			print(PHP_EOL);
			print(count($this->applied) . ($this->apply ? ' applied' : ' would be applied') . ' in ' . $this->texts_written . ' texts, ');
			print(count($this->skipped) . ' skipped.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

			// Marking the store
			// -----------------------------------------------

			/*
				Rewritten in place, one field changed per line attempted: the
				status column becomes `shipped <date>` or `skipped: <reason>`.
				Everything else keeps its order, so the diff is exactly what this
				run did.  A skipped line is not tried again; set it back to
				`proposed` by hand to retry it.
			*/

		public function markStore() {
			$statuses = [];

			foreach($this->applied as $record) {
				$statuses[$record['id']] = 'shipped ' . date('Y-m-d');
			}

			foreach($this->skipped as $record) {
				$statuses[$record['id']] = 'skipped: ' . $record['skip_reason'];
			}

			$lines = file($this->store_location, FILE_IGNORE_NEW_LINES);
			$header = explode("\t", $lines[0]);
			$id_column = array_search('id', $header);
			$status_column = array_search('status', $header);

			foreach($lines as $number => $line) {
				if($number === 0 || $line === '') {
					continue;
				}

				$fields = explode("\t", $line);

				if(isset($statuses[$fields[$id_column]])) {
					$fields[$status_column] = $statuses[$fields[$id_column]];
					$lines[$number] = implode("\t", $fields);
				}
			}

			file_put_contents($this->store_location, implode("\n", $lines) . "\n");

			print('Marked ' . count($statuses) . ' lines in ' . $this->store_location . '.' . PHP_EOL);

			return TRUE;
		}

			// The page cache
			// -----------------------------------------------

			/*
				As TranslationWriter::flushPageCache, and for its reasons: a shell
				has no HTTP_HOST, so the domain is passed explicitly, and both
				hostnames are flushed because each holds its own copy of every
				page.
			*/

		public function flushPageCache() {
			if(!class_exists('PageCache')) {
				ggreq('classes/Cache/PageCache.php');
			}

			$page_cache = new PageCache(['handler'=>NULL]);

			$hosts = [
				$this->domain,
				'www.' . $this->domain,
			];

			$flushed_any = FALSE;

			foreach($hosts as $host) {
				if($page_cache->FlushDomain(['domain'=>$host])) {
					print('Flushed the page cache for ' . $host . '.' . PHP_EOL);
					$flushed_any = TRUE;
				}
			}

			if(!$flushed_any) {
				print('NOTE: nothing was flushed for either hostname.  If pages still show the old word, clear the cache by hand -- see Docs/PageCache.md.' . PHP_EOL);
			}

			if(!class_exists('EdgeCache')) {
				ggreq('classes/Cache/EdgeCache.php');
			}

			$edge_cache = new EdgeCache([]);

			if($edge_cache->Token() !== FALSE) {
				if($edge_cache->PurgeDomain(['domain'=>$this->domain])) {
					print('Purged Cloudflare\'s cache for ' . $this->domain . '.' . PHP_EOL);
				} else {
					print('NOTE: Cloudflare\'s cache for ' . $this->domain . ' was not purged; readers on the edge may see the old word for up to four hours.' . PHP_EOL);
				}
			}

			print(PHP_EOL);

			return $flushed_any;
		}
	}

?>

<?php

	/*
		The translation review store -- Development/TranslationReview/<lang>.txt
		-- read into records and narrowed to the ones a run may apply.

		Kept apart from TranslationWriter, which needs a database, so that
		reading and choosing can be tested without one.

			$store = new TranslationReviewStore(['location'=>$file, 'language'=>'es', 'limit'=>20]);
			$records = $store->Read();
	*/

	class TranslationReviewStore {
		public $location;
		public $language;
		public $limit;

		public function __construct($args) {
			$this->location = $args['location'];
			$this->language = $args['language'];
			$this->limit = $args['limit'];
		}

			/*
				Records are blank-line separated, one `key: value` per line, and
				a value may continue onto following lines indented by spaces.
				Comment lines start with #.
			*/

		public function Read() {
			$records = [];
			$record = [];
			$key = '';

			$lines = file($this->location);

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

			return $this->Filter(['records'=>$records]);
		}

		public function Filter($args) {
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

					/*
						Not every record is a correction.  A structural one finds a
						row attached to the wrong entry, and its `proposed` is a
						note -- "(needs investigating, not translating)" -- which
						written to the row would appear on the page as the word.
						Those want the database looking at by a person.
					*/

				if(($record['kind'] ?? '') === 'structural' || substr($record['proposed'], 0, 1) === '(') {
					continue;
				}

				$filtered[] = $record;

				if(count($filtered) >= $this->limit) {
					break;
				}
			}

			return $filtered;
		}
	}

?>

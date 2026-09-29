<?php

	/*
		TranslationReviewStore reads the translation review store and chooses
		the records a run of apply_translation_review.php may apply.  Each test
		writes a store to the scratch directory and reads it back.
	*/

	class TranslationReviewStoreTest extends GGCMSTestCase {
		public function newStore($args) {
			$this->requireCLI(['file'=>'classes/Entries/TranslationReviewStore.php']);

			$location = $this->emptyScratchDirectory() . '/es.txt';
			file_put_contents($location, $args['store']);

			return new TranslationReviewStore([
				'location'=>$location,
				'language'=>'es',
				'limit'=>$args['limit'] ?? 200,
			]);
		}

		public function record($args) {
			return
				'id: ' . $args['id'] . "\n" .
				'lang: ' . ($args['lang'] ?? 'es') . "\n" .
				'english: ' . $args['english'] . "\n" .
				'current: ' . $args['current'] . "\n" .
				'proposed: ' . $args['proposed'] . "\n" .
				'kind: ' . $args['kind'] . "\n" .
				"reason: A reason that runs\n  onto a second line.\n" .
				'status: ' . ($args['status'] ?? 'proposed') . "\n\n";
		}

		public function testReadsCorrections() {
			$store = $this->newStore(['store'=>
				"# Spanish\n\n" .
				$this->record(['id'=>44, 'english'=>'Turkey', 'current'=>'Turquía', 'proposed'=>'Pavo', 'kind'=>'wrong-word'])
			]);

			$records = $store->Read();

			$this->assertCount(1, $records);
			$this->assertSame('Pavo', $records[0]['proposed']);
			$this->assertSame('A reason that runs onto a second line.', $records[0]['reason']);
		}

		public function testSkipsShippedAndOtherLanguages() {
			$store = $this->newStore(['store'=>
				$this->record(['id'=>1, 'english'=>'a', 'current'=>'x', 'proposed'=>'y', 'kind'=>'wrong-word', 'status'=>'shipped 2026-09-29']) .
				$this->record(['id'=>2, 'english'=>'b', 'current'=>'x', 'proposed'=>'y', 'kind'=>'wrong-word', 'lang'=>'it']) .
				$this->record(['id'=>3, 'english'=>'c', 'current'=>'x', 'proposed'=>'y', 'kind'=>'wrong-word'])
			]);

			$this->assertSame(['3'], array_column($store->Read(), 'id'));
		}

			/*
				A structural record's `proposed` is a note, not a word.  Applied,
				it would have put "(needs investigating, not translating)" on
				the page as the Spanish for three entries.
			*/

		public function testSkipsStructuralRecordsAndNotes() {
			$store = $this->newStore(['store'=>
				$this->record(['id'=>9172, 'english'=>'Nouns - Society, Part 79', 'current'=>'admisión', 'proposed'=>'(needs investigating, not translating)', 'kind'=>'structural']) .
				$this->record(['id'=>9173, 'english'=>'x', 'current'=>'y', 'proposed'=>'(a note)', 'kind'=>'wrong-word']) .
				$this->record(['id'=>1392, 'english'=>'copy', 'current'=>'dupdo', 'proposed'=>'copiar', 'kind'=>'corrupt'])
			]);

			$this->assertSame(['1392'], array_column($store->Read(), 'id'));
		}

		public function testStopsAtTheLimit() {
			$records = '';

			for($i = 1; $i <= 5; $i++) {
				$records .= $this->record(['id'=>$i, 'english'=>'w' . $i, 'current'=>'x', 'proposed'=>'y', 'kind'=>'orthography']);
			}

			$store = $this->newStore(['store'=>$records, 'limit'=>3]);

			$this->assertCount(3, $store->Read());
		}
	}

?>

<?php
	class UltraRareEnglishMisspellingDetector {
		public $misspellings;

		public function __construct($args) {
			$this->misspellings = [];
			$source = new SplFileObject($args['path'], 'r');
			$source->setCsvControl("\t", '"', '');
			if ($source->fgetcsv() !== ['wrong', 'correct']) {
				throw new UnexpectedValueException('Expected wrong and correct columns.');
			}

			while (($row = $source->fgetcsv()) !== FALSE) {
				if ($row === [NULL]) {
					continue;
				}
				if (count($row) !== 2 || $row[0] === '' || $row[1] === '') {
					throw new UnexpectedValueException('Invalid UltraRare spelling row.');
				}
				$this->misspellings[$row[1]][] = $row[0];
			}
		}

		public function GetWords_Misspelled() {
			$misspellings = [];
			foreach ($this->misspellings as $words) {
				foreach ($words as $word) {
					$misspellings[] = $word;
				}
			}
			return $misspellings;
		}
	}
?>

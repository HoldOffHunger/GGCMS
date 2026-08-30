<?php
		
		/* UTF-8 TEST
		
			中文汉字漢字
		
		*/
				
				/* EnglishMisspellingsWords_X
					
					Class for English-Misspellings for all words beginning with : X.
					
				*/

	class EnglishMisspellingsWords_X {
			/* __construct($args)
			
				Constructor.
				
				Nothing to do here.
			
			*/
			
		public function __construct($args) {
			return TRUE;
		}
		
			/* EnglishMisspellingsWords()
			
				List of English misspellings for words starting with : X.
			
			*/

		public function EnglishMisspellingsWords() {
			return [
				'xanthine'=>[
					'xanthini',
					'xenthine',
				],
				'xanthophyll'=>[
					'xenthophyll',
				],
				'xbox'=>[
					'x-box',
				],
				'xenogeneic'=>[
					'xenogeniec',
				],
				'xylophone'=>[
					'xilohpone',
					'xilophone',
					'xylophon',
				],
			];
		}
	}

?>
<?php

	/*
		/randomwords.json -- the dictionary's random word list, for a page to
		fetch in the browser rather than carry in its own HTML.

		wordweight printed a thousand of these links on every word page.  They
		were 191 KB of each 222 KB page and filled the page cache volume, and
		the list only changes hourly anyway (Dictionary::LookUpRandomWords keeps
		it in the file cache for an hour), so one list per hour serves every
		page.  The browser caches it for the same hour.

		A site without a dictionary answers 404 -- it is switched on per domain
		in AbstractGlobals, the same way as for view.php.
	*/

	class randomwords extends basicscript {

						// Security Data
						// ---------------------------------------------

		public function IsSecure() {
			return FALSE;
		}

		public function RequiresLogin() {
			return FALSE;
		}

						// Functionality
						// ---------------------------------------------

		public function display() {
			if(!$this->handler->dictionary) {
				return FALSE;	# 404
			}

			$words = [];

			foreach(array_keys($this->handler->dictionary->LookUpRandomWords([])) as $random_word) {
				$words[] = [
					'word'=>ucwords($random_word),
					'url'=>'/' . rawurlencode(ucwords($random_word)) . '/',	# %20, not +: /Sea+Cucumber/ is a 404
				];
			}

			$this->rpc_results = [	# what SetRecordToUseForMetadata() hands the JSON format
				'words'=>$words,
			];

			header('Cache-Control: public, max-age=3600');

			return TRUE;
		}
	}

?>
<?php

	class AbstractGlobals_randomwords {

			/*
				randomwords.json reads the dictionary, which is a second database
				and a second connection, so no domain has it unless it says so.
				A dictionary site turns it on -- see com.wordweight/scripts/randomwords.php.
			*/

		public function Dictionary_enabled($args) {
			return FALSE;
		}
	}

?>
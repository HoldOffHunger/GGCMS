<?php

	class AbstractGlobals_view {

			/*
				The dictionary is a second database and a second connection, so it
				is loaded for the pages that read it rather than for every request.

				browseByTag shows a keyword and the entries carrying it, and a
				keyword may have a definition in alldictionaries.  No other action
				here does.

				A domain that is a dictionary in its own right overrides this --
				see com.wordweight/scripts/view.php.
			*/

		public function Dictionary_enabled($args) {
			return in_array($args['action'], $this->Dictionary_actions());
		}

		public function Dictionary_actions() {
			return [
				'browseByTag',
			];
		}
	}

?>

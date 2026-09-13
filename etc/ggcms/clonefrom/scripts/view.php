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

			/*
				A view.php path is a walk through the entry graph: every segment
				names an entry, and Handler::EntryPathResolves answers 404 before
				loading anything when one does not.

				A domain whose paths name something else overrides this -- see
				com.wordweight/scripts/view.php.
			*/

		public function EntryPath_required($args) {
			return TRUE;
		}
	}

?>

<?php

	class localAbstractGlobals_view extends AbstractGlobals_view {

			//  wordweight is the dictionary site.  Every page of it may look a
			//  word up, not only the keyword page.

		public function Dictionary_enabled($args) {
			return TRUE;
		}
	}

?>

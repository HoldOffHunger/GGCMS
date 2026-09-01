<?php

	class localAbstractGlobals_view extends AbstractGlobals_view {

			//  A word page here prints the word's definition from alldictionaries,
			//  the same entry wordweight would show, so the ordinary view action
			//  needs the dictionary as well as the inherited keyword page.

		public function Dictionary_actions() {
			return array_merge(parent::Dictionary_actions(), [
				'display',
			]);
		}
	}

?>

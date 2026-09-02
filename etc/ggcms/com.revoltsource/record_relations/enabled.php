<?php

	class AbstractGlobals_RecordRelations_enabled_override extends AbstractGlobals_RecordRelations_enabled {

			/*
				Includes entry-navigation, so it needs siblings for its next and
				previous links.  It does not include
				entry-children-grandchildren, so the grandchild tier stays off.
			*/

		public function Siblings_enabled() {
			return TRUE;
		}
	}

?>
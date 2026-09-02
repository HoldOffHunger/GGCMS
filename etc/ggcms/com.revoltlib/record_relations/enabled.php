<?php

	class AbstractGlobals_RecordRelations_enabled_override extends AbstractGlobals_RecordRelations_enabled {

			/*
				Includes entry-children-grandchildren on its index and
				entry-navigation on a view, so it renders all of these:
				authors, their books, and the chapters under them, with next
				and previous chapter links.
			*/

		public function GrandChildRecords_enabled() {
			return TRUE;
		}

		public function GrandChildAssociations_enabled() {
			return TRUE;
		}

		public function NewestChildren_enabled() {
			return TRUE;
		}

		public function Siblings_enabled() {
			return TRUE;
		}
	}

?>
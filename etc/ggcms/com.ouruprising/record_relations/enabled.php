<?php

	class AbstractGlobals_RecordRelations_enabled_override extends AbstractGlobals_RecordRelations_enabled {

			/*
				Includes entry-children-grandchildren and entry-navigation, the
				same pair revoltlib does.
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
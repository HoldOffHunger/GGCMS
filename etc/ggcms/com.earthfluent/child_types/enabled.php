<?php

	/*
		EarthFluent had no override at all, so it ran on clonefrom's defaults,
		and clonefrom defaults almost everything to FALSE.  That left the site's
		60,061 translations switched off -- the entire reason the site exists,
		never fetched, on every request since it was built.

		Found by cli/scripts/public/sql/check_schema.php, which reports rows
		that exist and are never asked for.  Same shape as ffd040d, where
		revoltlib held 2,496 images the ORM was never asked to fetch.

		AvailabilityDateRange is deliberately left off.  It holds 75,257 rows
		here, and switching it on adds that fetch to every render -- a
		performance decision rather than a repair, and one that wants measuring
		before it is made.
	*/

	class AbstractGlobals_ChildTypes_enabled_override extends AbstractGlobals_ChildTypes_enabled {
		public function EntryTranslation_enabled() {
			return TRUE;
		}
		
		public function Image_enabled() {
			return TRUE;
		}
		
		public function Quote_enabled() {
			return TRUE;
		}
	}

?>

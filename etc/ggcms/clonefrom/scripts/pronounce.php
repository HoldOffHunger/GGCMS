<?php

	class AbstractGlobals_pronounce {

			/*
				pronounce.json sends text to Google, which bills by the
				character, so no domain has it unless it says so -- see
				com.pronouncethat/scripts/pronounce.php.

				A domain that turns it on keeps these limits unless it has a
				reason not to.  Detection reads the text as well as synthesis,
				so a detected request counts twice against the day.
			*/

		public function Speech_enabled() {
			return FALSE;
		}

		public function Speech_characters_per_request() {
			return 500;
		}

		public function Speech_characters_per_day() {
			return 15000;
		}
	}

?>

<?php

	class basicscript extends baseformat {
			// Display Components
			// -------------------------------------------------------
		
		public function NonBreakingSpace() {
			return ' ';
		}
		
		public function Bullet() {
			return '*';
		}
		
		public function Quote() {
			return '"';
		}
		
		public function PreFormattedStart() {
			return '';
		}
		
		public function PreFormattedEnd() {
			return '';
		}
	}

?>
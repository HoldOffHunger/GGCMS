<?php

	class basicscript extends baseformat {
			// Page Configuration
			// ------------------------------------------------
		
		public function setPageConfiguration() {
			$this->navigation = FALSE;
			
			return TRUE;
		}
		
			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplaySingleTab() {
			print("\t");
		}
		
		public function DisplaySingleReturn() {
			print("\n");
		}
		
		public function DisplayDoubleReturns() {
			print("\n");
			print("\n");
		}
	}

?>
<?php

	ggreqonce('scripts/view.php');

	class social extends view {
		
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
						// Code
						// ---------------------------------------------
						
					// Display Options
					// ---------------------------------------------
		
		public function login_instagram() {
			return TRUE;
		}
		
		public function post_instagram() {
			return TRUE;
		}
	}
	
?>
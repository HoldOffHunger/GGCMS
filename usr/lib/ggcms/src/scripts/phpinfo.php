<?php

	ggreqonce('scripts/view.php');

	class phpinfo extends view {
		use DBFunctions;
		use SimpleForms;
		use SimpleORM;
		
			// Security Data
		
		public function IsSecure() {
			return FALSE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
		public function DisplayText() {
			return 'Hi!  Would you like to know about our server?  I can tell you: YES, we ARE using PHP!  Thank you!';
		}
		
		public function Display() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			return TRUE;
		}
	}

?>
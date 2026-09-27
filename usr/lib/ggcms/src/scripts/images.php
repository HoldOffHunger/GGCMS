<?php

	ggreq('scripts/view.php');

	class images extends view {
		use DBFunctions;
		use SimpleForms;
		use SimpleORM;
		
		public $redirect_script;
		
			// Security Data
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
			// Login
			//
			// Just show the login screen, i.e., username/password.
			//
			// ---------------------------------------------
		
		public function Display() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->redirect_script = 'image';
			
			return FALSE;
		}
	}

?>
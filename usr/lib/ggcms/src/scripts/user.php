<?php

	ggreq('scripts/view.php');

	class user extends view {
		use DBFunctions;
		use SimpleForms;
		use SimpleORM;
		
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
			
			$this->redirect_script = 'users';
			
			return FALSE;
		}
		
		public function login() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->redirect_script = 'login';
			
			return FALSE;
		}
	}

?>
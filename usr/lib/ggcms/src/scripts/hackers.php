<?php

	classreq('scripts/view.php');

	class hackers extends view {
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
			return 'If you would like to report a security or other vulnerability, please contact us: UprisingEngineer@gmail.com.';
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
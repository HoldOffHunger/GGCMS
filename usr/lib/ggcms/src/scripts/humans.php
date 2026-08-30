<?php

	ggreq('scripts/view.php');

	class humans extends view {
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
			return 'Software Engineering, System Maintenance, Site Administration: UprisingEngineer@gmail.com.';
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
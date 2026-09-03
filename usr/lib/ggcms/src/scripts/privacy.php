<?php

	ggreqonce('traits/scripts/DBFunctions.php');
	ggreqonce('traits/scripts/PrivacyPolicy.php');
	ggreqonce('traits/scripts/SimpleErrors.php');
	ggreqonce('traits/scripts/SimpleForms.php');
	ggreqonce('traits/scripts/SimpleLookupLists.php');
	ggreqonce('traits/scripts/SimpleORM.php');

	class privacy extends basicscript {
						// Traits
						// ---------------------------------------------
		
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;
		use PrivacyPolicy;
		
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return FALSE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
						// Functionality
						// ---------------------------------------------
		
		public function display() {
			$this->SetORM();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->SetMasterRecord();
			
			$this->FormatErrors();
			
			return TRUE;
		}
	}
?>
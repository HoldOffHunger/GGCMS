<?php

	classreq('traits/scripts/DBFunctions.php');
	classreq('traits/scripts/PrivacyPolicy.php');
	classreq('traits/scripts/SimpleErrors.php');
	classreq('traits/scripts/SimpleForms.php');
	classreq('traits/scripts/SimpleLookupLists.php');
	classreq('traits/scripts/SimpleORM.php');

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
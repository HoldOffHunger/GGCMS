<?php

	ggreq('scripts/view.php');
	ggreq('traits/scripts/SimpleGeography.php');
	
	class spellchecker extends view {
		use SimpleGeography;	
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
			$this->SetGeographyBasics();
			
			$this->FormatErrors();
			ggreq('classes/Language/EnglishMisspellings.php');
			$this->misspellings = new EnglishMisspellings([]);
			$this->misspelled_words = $this->misspellings->GetWords_Misspelled();
			
			return TRUE;
		}
	}

?>
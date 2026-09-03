<?php

	ggreqonce('scripts/view.php');

	class chapterify extends view {
		use DBFunctions;
		use SimpleForms;
		use SimpleORM;
		
			// Security Data
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return TRUE;
		}
			
			// Display()
			// ---------------------------------------------
		
		public function Display() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
			$this->FormatText();
			$this->SetChildRecordCount();
			$this->SetChildRecords([]);
			
			return TRUE;
		}
	}

?>
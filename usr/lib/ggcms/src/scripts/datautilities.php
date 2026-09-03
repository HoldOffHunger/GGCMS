<?php
	classreq('traits/scripts/DBFunctions.php');
	classreq('traits/scripts/SimpleErrors.php');
	classreq('traits/scripts/SimpleForms.php');
	classreq('traits/scripts/SimpleLookupLists.php');
	classreq('traits/scripts/SimpleORM.php');
	classreq('traits/scripts/SimpleSocialMedia.php');

	class datautilities extends basicscript {
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;
		use SimpleSocialMedia;
		
			// Security Data
		
		public function IsSecure() {
			return FALSE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
		public function AdminOnly() {
			return FALSE;
		}
		
				// Primary Logic
				// ------------------------------------------------------------
		
		public function findDuplicateArrayKeys() {
			$this->SetOrmBasics();
			
			$text = $this->Param('text');
			
			if($text && strlen($text) < 100000) {
				classreq('classes/Data/DataStructures.php');
				$datastructures = new DataStructures([]);
				
				$duplicates = $datastructures->findDuplicateArrayKeys(['text'=>$text]);
				
				$this->text = $this->CleanseForDisplay($text);
				$this->duplicates = $duplicates;
			}
			
			return TRUE;
		}
		
				// ** HTML FORMAT DATA ** //
		
			// Title
		
		public function GetHTMLFormatData_Title() {
			return 'PHP Data Structure Utilities';
		}
	}

?>
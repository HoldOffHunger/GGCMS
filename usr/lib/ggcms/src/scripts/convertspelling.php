<?php
	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/SimpleSocialMedia.php');

	class convertspelling extends basicscript {
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
		
		public function Display() {
			$text = $this->Param('text');
			$direction = $this->Param('direction');
			$this->SetOrmBasics();
			
			if($text && strlen($text) < 100000) {
				$text = trim($text);
								
				ggreq('classes/Language/AmericanBritishSpellings.php');
				$american_british_spellings = new AmericanBritishSpellings([]);
				
				if($direction == 'british-to-american') {
					$converted_text = $american_british_spellings->SwapBritishSpellingsForAmericanSpellings(['text'=>$text]);
				} else {
					$converted_text = $american_british_spellings->SwapAmericanSpellingsForBritishSpellings(['text'=>$text]);
				}
				
				$this->text = $this->CleanseForDisplay($text);
				$this->converted_text = $this->CleanseForDisplay($converted_text);
			}
			
			return TRUE;
		}
		
				// ** HTML FORMAT DATA ** //
		
			// Title
		
		public function GetHTMLFormatData_Title() {
			return 'British/American Spelling Converter';
		}
	}

?>
<?php

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');

	class contact extends basicscript {
						// Traits
						// ---------------------------------------------
		
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;
		
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
		
						// HTML Data
						// ---------------------------------------------
		
		public function GetHTMLFormatData_Title() {
			if(!$this->parent['id'] && $this->master_record && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$header_title_text = 'Contact ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
				} else {
					$contact_header_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesContactHeader']);
					
					if($contact_header_language_translations[$this->handler->language->getLanguageCode()]) {
						$header_title_text = $contact_header_language_translations[$this->handler->language->getLanguageCode()];
					} else {
						$header_title_text = 'Contact ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
					}
				}
				
				return $this->header_title_text = $header_title_text;
			}
			
			return FALSE;
		}
	}

?>
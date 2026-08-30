<?php

	ggreq('scripts/view.php');

	class about extends view {
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
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatText();
			$this->FormatEventDates();
			
		#	$this->SaveComments();
		#	$this->SetComments();
			
		#	$this->SetChildRecordCount();
			
			if($this->children_count > 400) {
				$this->desired_action = 'index';	// you don't want this page
				return $this->index();
			}
			
			#$this->SetChildRecords([]);
			#$this->SetEntryChildRecordStats([]);
			#$this->SetEntryAssociatedRecordStats([]);
			#$this->SetSimpleChildAssociationRecords();
			
			#$this->SetLikeDislikeRecords();
			#$this->SetAssociationRecords();
			#$this->SetChildRecordsOfChildren();
			
			$this->HandleMainPage();
			
			#$this->CompactDefinitions();
			#$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			#$this->SetSiblings([]);
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
						// HTML Data
						// ---------------------------------------------
		
		public function GetHTMLFormatData_Title() {
			if(!$this->parent['id'] && $this->master_record && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$header_title_text = 'About ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
				} else {
					$main_header_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesAboutHeader']);
					
					if($main_header_language_translations[$this->handler->language->getLanguageCode()]) {
						$header_title_text = $main_header_language_translations[$this->handler->language->getLanguageCode()];
					} else {
						$header_title_text = 'About ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
					}
				}
				
				return $this->header_title_text = $header_title_text;
			}
			
			return FALSE;
		}
	}

?>
<?php

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/SimpleSocialMedia.php');

	class formmaker extends basicscript {
						// Traits
						// ---------------------------------------------
		
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;
		use SimpleSocialMedia;
		
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return TRUE;
		}
		
						// Functionality
						// ---------------------------------------------
		
		public function display() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
	#		$this->FormatText();
	#		$this->FormatEventDates();
			
			$this->SaveComments();
			$this->SetComments();
			
			$this->SetChildRecordCount();
			
			if($this->children_count > 400) {
				$this->desired_action = 'index';	// you don't want this page
				return $this->index();
			}
			
			$this->SetChildRecords([]);
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
		#	if($this->entry && $this->entry['associated'] && count($this->entry['associated']) < $this->maxAssociated() + 1) {
				$this->SetSimpleChildAssociationRecords();
				$this->SetAssociationRecords();
		#	}
			$this->SetChildRecordsOfChildren();
			
		#	$this->SetLikeDislikeRecords();
		#	$this->HandleMainPage();
		#	
			$this->CompactDefinitions();
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			$this->SetSiblings([]);
			
			$this->CountRecords();
			$this->FixDates();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
		public function newform() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
	#		$this->FormatText();
	#		$this->FormatEventDates();
			
			$this->SaveComments();
			$this->SetComments();
			
			$this->SetChildRecordCount();
			
			if($this->children_count > 400) {
				$this->desired_action = 'index';	// you don't want this page
				return $this->index();
			}
			
			$this->SetChildRecords([]);
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
		#	if($this->entry && $this->entry['associated'] && count($this->entry['associated']) < $this->maxAssociated() + 1) {
				$this->SetSimpleChildAssociationRecords();
				$this->SetAssociationRecords();
		#	}
			$this->SetChildRecordsOfChildren();
			
		#	$this->SetLikeDislikeRecords();
		#	$this->HandleMainPage();
		#	
			$this->CompactDefinitions();
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			$this->SetSiblings([]);
			
			$this->CountRecords();
			$this->FixDates();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
		public function list() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
	#		$this->FormatText();
	#		$this->FormatEventDates();
			
			$this->SaveComments();
			$this->SetComments();
			
			$this->SetChildRecordCount();
			
			if($this->children_count > 400) {
				$this->desired_action = 'index';	// you don't want this page
				return $this->index();
			}
			
			$this->SetChildRecords([]);
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
		#	if($this->entry && $this->entry['associated'] && count($this->entry['associated']) < $this->maxAssociated() + 1) {
				$this->SetSimpleChildAssociationRecords();
				$this->SetAssociationRecords();
		#	}
			$this->SetChildRecordsOfChildren();
			
		#	$this->SetLikeDislikeRecords();
		#	$this->HandleMainPage();
		#	
			$this->CompactDefinitions();
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			$this->SetSiblings([]);
			
			$this->CountRecords();
			$this->FixDates();
			
			$this->FormatErrors();
			
			$sql = 'SELECT * FROM Form WHERE Entryid = ?';
			$forms = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$this->entry['id'],
			]);
			
			$this->forms = $forms;
			
			return TRUE;
		}
		
						// HTML Data
						// ---------------------------------------------
		
		public function GetHTMLFormatData_Title() {
			if(!$this->parent['id'] && $this->master_record && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$header_title_text = 'Configure Submission Details -- ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
				} else {
				}
				
				return $this->header_title_text = $header_title_text;
			}
			
			return FALSE;
		}
		
		public function FormatText() {
			if($this->entry['textbody'] && $this->entry['textbody'][0] && $this->entry['textbody'][0]['id'] && $this->entry['textbody'][0]['Text']) {
				$text = $this->formatImageText([
					'text'=>$this->entry['textbody'][0]['Text'],
					'images'=>$this->entry['image'],
				]);
				$this->entry['textbody'][0]['Text'] = $text;
			}
			return TRUE;
		}
		
		public function FormatEventDates() {
			if($this->parent['eventdate'] && $this->parent['eventdate'][0]) {
				for($i = 0; $i < count($this->parent['eventdate']); $i++) {
					$event_date = $this->parent['eventdate'][$i];
					
					$date_time_pieces = explode(' ', $event_date['EventDateTime']);
					$date = $date_time_pieces[0];
					$time = $date_time_pieces[1];
					
					$event_date['date'] = $date;
					$event_date['time'] = $time;
					
					$this->parent['eventdate'][$i] = $event_date;
				}
			}
			
			if($this->entry['eventdate'] && $this->entry['eventdate'][0]) {
				for($i = 0; $i < count($this->entry['eventdate']); $i++) {
					$event_date = $this->entry['eventdate'][$i];
					
					$date_time_pieces = explode(' ', $event_date['EventDateTime']);
					$date = $date_time_pieces[0];
					$time = $date_time_pieces[1];
					
					$event_date['date'] = $date;
					$event_date['time'] = $time;
					
					$this->entry['eventdate'][$i] = $event_date;
				}
			}
			
			if($this->children && count($this->children)) {
				for($i = 0; $i < count($this->children); $i++) {
					$child = $this->children[$i];
					
					if($child['eventdate']) {
						for($j = 0; $j < count($child['eventdate']); $j++) {
							$event_date = $child['eventdate'][$j];
							
							$date_time_pieces = explode(' ', $event_date['EventDateTime']);
							$date = $date_time_pieces[0];
							$time = $date_time_pieces[1];
							
							$event_date['date'] = $date;
							$event_date['time'] = $time;
							
							$this->children[$i]['eventdate'][$j] = $event_date;
						}
					}
				}
			}
#			$this->entry['eventdate'][0]['test'] = 'soybean';
			return TRUE;
		}
		
		public function CountRecords() {
				# BT: REDO!  VARIABLE-IZE!
			if($this->children) {
				$children_count = count($this->children);
			} else {
				$children_count = 0;
			}
			
			if($this->younger_siblings) {
				$younger_sibling_count = count($this->younger_siblings);
			} else {
				$younger_sibling_count = 0;
			}
			
			if($this->older_siblings) {
				$older_sibling_count = count($this->older_siblings);
			} else {
				$older_sibling_count = 0;
			}
			
			if($this->entry['image']) {
				$image_count = count($this->entry['image']);
			} else {
				$image_count = 0;
			}
			
			if($this->entry['description']) {
				$description_count = count($this->entry['description']);
			} else {
				$description_count = 0;
			}
			
			if($this->entry['quote']) {
				$quote_count = count($this->entry['quote']);
			} else {
				$quote_count = 0;
			}
			
			if($this->entry['tag']) {
				$tag_count = count($this->entry['tag']);
			} else {
				$tag_count = 0;
			}
			
			if($this->entry['textbody']) {
				$textbody_count = count($this->entry['textbody']);
			} else {
				$textbody_count = 0;
			}
			
			if($this->entry['associated']) {
				$associated_count = count($this->entry['associated']);
			} else {
				$associated_count = 0;
			}
			
			if($this->entry['association']) {
				$association_count = count($this->entry['association']);
			} else {
				$association_count = 0;
			}
			
			if($this->entry['eventdate']) {
				$eventdate_count = count($this->entry['eventdate']);
			} else {
				$eventdate_count = 0;
			}
			
			if($this->entry['definition']) {
				$definition_count = count($this->entry['definition']);
			} else {
				$definition_count = 0;
			}
			
			if($this->entry['link']) {
				$link_count = count($this->entry['link']);
			} else {
				$link_count = 0;
			}
			
			if($this->comments) {
				$comments_count = count($this->comments);
			} else {
				$comments_count = 0;
			}
			
			$this->counts = [
				'image'=>$image_count,
				'tag'=>$tag_count,
				'description'=>$description_count,
				'quote'=>$quote_count,
				'textbody'=>$textbody_count,
				'associated'=>$associated_count,
				'association'=>$association_count,
				'eventdate'=>$eventdate_count,
				'definition'=>$definition_count,
				'link'=>$link_count,
				
				'children'=>$children_count,
				
				'comment'=>$comments_count,
				
				'younger_sibling'=>$younger_sibling_count,
				'older_sibling'=>$older_sibling_count,
			];
			
			return TRUE;
		}
		
		public function FixDates() {
			if($this->entry['eventdate']) {
				$event_dates = $this->entry['eventdate'];
				
				if($this->counts['eventdate']) {
					for($i = 0; $i < $this->counts['eventdate']; $i++) {
						$event_date = $event_dates[$i];
						
						$event_date_time_pieces = explode('-', $event_date['EventDateTime']);
						
						$year = (int)$event_date_time_pieces[0];
						
						if($year >= 3000) {
							$diff = $year - 3000;
							$real_year = 1000 - $diff;
						} else {
							$real_year = $year;
						}
						
						$event_date_time_pieces[0] = $real_year;
						$new_time = implode('-', $event_date_time_pieces);
						$event_date['EventDateTime'] = $new_time;
						
						$event_dates[$i] = $event_date;
						
		#				print("<!-- BT: DATE! ");
		#				print_r($event_date);
		#				print("-->");
					}
				#	print("<!-- BT: event dates exist! go! -->");
				}
			}
		}
	}

?>
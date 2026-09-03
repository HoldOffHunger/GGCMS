<?php

	ggreqonce('scripts/view.php');

	class image extends view {
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
			
			// Display()
			// ---------------------------------------------
		/*
		
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			$this->FormatText();
			$this->SetImageCount();
			$this->SetBrowseParameters();
			$this->SetChildRecords(['publish'=>1]);
			
			$this->SetSimpleChildAssociationRecords();
			$this->SetAssociationRecords();
			return TRUE;
			*/
		
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
			
			$this->SetImageCount();
			$this->SetBrowseParameters();
			
			$this->FormatText();
			$this->FormatEventDates();
			
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
			if($this->entry['associated'] && count($this->entry['associated']) < $this->maxAssociated() + 1) {
				$this->SetSimpleChildAssociationRecords();
				$this->SetAssociationRecords();
			}
			$this->SetChildRecordsOfChildren();
			
			$this->SetLikeDislikeRecords();
			$this->HandleMainPage();
			
			$this->CompactDefinitions();
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			$this->SetSiblings([]);
			
			$this->CountRecords();
			$this->FixDates();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
		public function SetImageCount() {
			$sql = 'SELECT COUNT(id) AS Count FROM Image ';
			$sql_args = [];
			
			if($this->master_record['id'] !== $this->entry['id']) {
				$sql .= 'WHERE Entryid = ?';
				$sql_args[] = $this->entry['id'];
			}
			
			$this->children_count = $this->image_count = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$sql_args,
			])[0]['Count'];
			
			return TRUE;
		}
		
		public function SetBrowseParameters_PageAndPerPage() {
			$this->page = (int)$this->Param('page');
			$possible_per_page = $this->Param('perpage');
			if($possible_per_page == 'custom') {
				$this->custom_per_page_selected = true;
				$possible_per_page = $this->Param('CustomPerPage');
			}
			$this->perpage = (int)$possible_per_page;
			
			if($this->page < 1) {
				$this->page = 1;
			}
			
			if($this->perpage < 0) {
				$this->perpage = $this->browse_DefaultPerPage();
			}
			
			if($this->perpage < $this->browse_MinPerPage()) {
				$this->perpage = $this->browse_DefaultPerPage();
			} elseif($this->perpage > $this->browse_MaxPerPage()) {
				$this->perpage = $this->browse_MaxPerPage();
			}
			
		#	print("BT: PAGE AND PER PAGE???" . $this->page . "|" . $this->perpage . "|");
			
		#	print("BT: IMAGE COUNT!" . $this->image_count . "|");
			
			$child_record_start_index = ($this->page - 1) * $this->perpage + 1;
			if($child_record_start_index > $this->image_count) {
				$this->page = 1;
				$this->perpage = $this->browse_DefaultPerPage();
				$child_record_start_index = 1;
			}
			$child_record_end_index = $child_record_start_index + $this->perpage - 1;
			
		#	print("BT: END!" . $child_record_end_index . "|");
			
			if($child_record_end_index > $this->image_count) {
				$child_record_end_index = $this->image_count;
			}
			
		#	print("<BR>BT: END2!" . $child_record_end_index . "|");
			
			$this->child_record_start_index = $child_record_start_index;
			$this->child_record_end_index = $child_record_end_index;
			
		#	print("BT: THEN!!!!" . $this->child_record_end_index . "|");
			
			return TRUE;
		}
		
		public function SetBrowseParameters_TotalPages() {
			$this->total_pages = (int) ceil($this->image_count / $this->perpage);
			
			return TRUE;
		}
		
		public function SetBrowseParameters_RemainingPages() {
			$this->total_images_viewed = $this->perpage * ($this->page - 1);
			$this->total_children_left = $this->image_count - $this->total_images_viewed - ($this->child_record_end_index - $this->child_record_start_index + 1);
			
			return TRUE;
		}
		
		public function browse_DefaultPerPage() {
			return 100;
		}
	}

?>
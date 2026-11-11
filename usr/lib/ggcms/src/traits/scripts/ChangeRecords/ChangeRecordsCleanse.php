<?php

	/*
		Belt stage two: Cleanse.  Each record is cut down to exactly the
		fields its table stores, so nothing the request carried beyond them
		goes any further.  What Read produced is kept as <type>_original.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsCleanse {
		public $entry;
		public $entry_original;
		public $images;
		public $entrypermission;
		public $entrypermission_unset;
		public $entrypermission_unformatted;
		
		public function CleanseRecordFromQueryForAll() {
			$cleanse_results = TRUE;
			
			if(!$this->CleanseRecordFromQuery_Entry()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_EntryTranslation()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Description()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Quote()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_TextBody()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Image()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_ImageTranslation()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Tag()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Link()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Price()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_AcademicReference()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Publication()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_EventDate()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Association()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_AvailabilityDates()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_EntryPermission()) {
				$cleanse_results = FALSE;
			}
			
			if(!$this->CleanseRecordFromQuery_Definition()) {
				$cleanse_results = FALSE;
			}
			
			return $cleanse_results;
		}
		
						// Record Cleansing
						// ---------------------------------------------
		
		public function CleanseRecordFromQuery_Entry() {
			$this->entry_original = $this->entry;
			$this->entry = [
				'Title'=>$this->entry_original['Title'],
				'Subtitle'=>$this->entry_original['Subtitle'],
				'ListTitle'=>$this->entry_original['ListTitle'],
				'ListTitleSortKey'=>$this->entry_original['ListTitleSortKey'],
				'Code'=>$this->entry_original['Code'],
				'ChildAdjective'=>$this->entry_original['ChildAdjective'],
				'ChildNoun'=>$this->entry_original['ChildNoun'],
				'ChildNounPlural'=>$this->entry_original['ChildNounPlural'],
				'GrandChildAdjective'=>$this->entry_original['GrandChildAdjective'],
				'GrandChildNoun'=>$this->entry_original['GrandChildNoun'],
				'GrandChildNounPlural'=>$this->entry_original['GrandChildNounPlural'],
				'Publish'=>$this->entry_original['Publish'],
				'OriginalEntryid'=>$this->entry_original['OriginalEntryid'],
			];
			
			if($this->entry_original['id']) {
				$this->entry['id'] = $this->entry_original['id'];
			}
			
			return TRUE;
		}
		
		public function CleanseRecordFromQuery_EntryTranslation() {
			$cleanse_record_from_query_args = [
				'variablename'=>'entrytranslation',
				'datastructure'=>[
					'Title',
					'Subtitle',
					'ListTitle',
					'ListTitleSortKey',
					'Language',
				],
			];
			
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Description() {
			$cleanse_record_from_query_args = [
				'variablename'=>'description',
				'datastructure'=>[
					'Description',
					'Source',
					'Language',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Quote() {
			$cleanse_record_from_query_args = [
				'variablename'=>'quote',
				'datastructure'=>[
					'Quote',
					'Source',
					'Language',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_TextBody() {
			$cleanse_record_from_query_args = [
				'variablename'=>'textbody',
				'datastructure'=>[
					'Text',
					'Source',
					'Language',
				],
			];
			$this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Image() {
			$cleanse_record_from_query_args = [
				'variablename'=>'image',
				'datastructure'=>[
					'Title',
					'FileName',
					'Description',
				],
			];
			return $this->images = $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_ImageTranslation() {
			$cleanse_record_from_query_args = [
				'variablename'=>'imagetranslation',
				'datastructure'=>[
					'Title',
					'FileName',
					'Description',
					'Language',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Tag() {
			$cleanse_record_from_query_args = [
				'variablename'=>'tag',
				'datastructure'=>[
					'Tag',
					'Language',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Link() {
			$cleanse_record_from_query_args = [
				'variablename'=>'link',
				'datastructure'=>[
					'Title',
					'URL',
					'Language',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Price() {
			$cleanse_record_from_query_args = [
				'variablename'=>'price',
				'datastructure'=>[
					'PriceValue',
					'Currency',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_AcademicReference() {
			$cleanse_record_from_query_args = [
				'variablename'=>'academicreference',
				'datastructure'=>[
					'ReferenceEntryid',
					'Headword',
					'VolumeNumber',
					'VolumeTitle',
					'ChapterNumber',
					'ChapterTitle',
					'Page',
					'Verdict',
					'Note',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Publication() {
			$cleanse_record_from_query_args = [
				'variablename'=>'publication',
				'datastructure'=>[
					'Authors',
					'Editors',
					'Translators',
					'Publisher',
					'Edition',
					'Year',
					'ISBN',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_EventDate() {
			$cleanse_record_from_query_args = [
				'variablename'=>'eventdate',
				'datastructure'=>[
					'EventDate',
					'EventTime',
					'Title',
					'Description',
					'Language',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Association() {
			$cleanse_record_from_query_args = [
				'variablename'=>'association',
				'datastructure'=>[
					'ChosenEntryid',
					'Type',
					'SubType',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_AvailabilityDates() {
			$cleanse_record_from_query_args = [
				'variablename'=>'availabilitydaterange',
				'datastructure'=>[
					'AvailabilityStartDate',
					'AvailabilityStartTime',
					'AvailabilityEndDate',
					'AvailabilityEndTime',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_Definition() {
			$cleanse_record_from_query_args = [
				'variablename'=>'definition',
				'datastructure'=>[
					'Term',
					'Definition',
				],
			];
			return $this->CleanseRecordFromQuery($cleanse_record_from_query_args);
		}
		
		public function CleanseRecordFromQuery_EntryPermission() {
			$entry_permission = [
				'Userid'=>$this->authentication_object->user_session['User.id'],
			];
			$entry_permissions = [
				$entry_permission,
			];
			
			return	$this->entrypermission =
				$this->entrypermission_unset =
				$this->entrypermission_unformatted =
				$entry_permissions;
		}
	}

?>

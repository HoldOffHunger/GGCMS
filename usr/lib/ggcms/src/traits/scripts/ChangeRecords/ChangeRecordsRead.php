<?php

	/*
		Belt stage one: Read.  Each table's fields are taken off the
		request with Param(), and nothing else -- whatever arrived, as it
		arrived.  The value already on the belt is kept as <type>_unset
		first, so a later stage can always ask what was there before.

		Line breaks in one-line fields are flattened here, smart title case
		applied, and a blank code generated, because those are how the
		request is understood rather than how it is cleaned.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsRead {
		public $entry;
		public $entry_unset;
		public $entrytranslation_unset;
		public $description_unset;
		public $quote_unset;
		public $textbody_unset;
		public $image_unset;
		public $imagetranslation_unset;
		public $tag_unset;
		public $link_unset;
		public $price_unset;
		public $academicreference_unset;
		public $publication_unset;
		public $eventdate_unset;
		public $association_unset;
		public $availabilitydaterange_unset;
		public $definition_unset;


						// Read ~ Every Table
						// ---------------------------------------------
		
		public function SetRecordFromQueryForAll() {
			$set_results = TRUE;
			
			if(!$this->SetRecordFromQuery_Description()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Quote()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_TextBody()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Image()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_ImageTranslation()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Tag()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Link()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Price()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_AcademicReference()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Publication()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_EventDate()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Association()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_AvailabilityDates()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Definition()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_Entry()) {
				$set_results = FALSE;
			}
			
			if(!$this->SetRecordFromQuery_EntryTranslation()) {
				$set_results = FALSE;
			}
			
			return $set_results;
		}
		
		public function RealignEntryIDsForRecordUpdate() {
			$realign_results = TRUE;
			
			$child_record_types = $this->GetChildRecordTypes();
			
			foreach($child_record_types as $child_record_type) {
				$child_record_type_unset = $child_record_type . '_unset';
				$new_child_record_types = [];
				
				$child_record_index = 0;
				$unset_child_records = $this->$child_record_type_unset;
				$child_records = $this->$child_record_type;
				
				foreach($child_records as $child_record) {
					$unset_child_record_version = $unset_child_records[$child_record_index];
					$child_record['id'] = $unset_child_record_version['id'];
					$child_record_index++;
					$new_child_record_types[] = $child_record;
				}
				
				$this->$child_record_type = $new_child_record_types;
			}
			
			return $realign_results;
		}
		
						// Record Setting
						// ---------------------------------------------
		
		public function FlattenLineBreaks($args) {
			$text = $args['text'];
			
			if(!is_string($text)) {
				return $text;
			}
			
			return preg_replace('/[ \t]*[\r\n]+[ \t]*/', ' ', $text);
		}
		
		public function SetRecordFromQuery_Entry() {
			if($this->entry && !$this->entry_unset) {
				$this->entry_unset = $this->entry;
			}
			$this->entry = [
				'Title'=>$this->Param('Title'),
				'Subtitle'=>$this->Param('Subtitle'),
				'ListTitle'=>$this->Param('ListTitle'),
				'ListTitleSortKey'=>$this->Param('ListTitleSortKey'),
				'ChildAdjective'=>$this->Param('ChildAdjective'),
				'ChildNoun'=>$this->Param('ChildNoun'),
				'ChildNounPlural'=>$this->Param('ChildNounPlural'),
				'GrandChildAdjective'=>$this->Param('GrandChildAdjective'),
				'GrandChildNoun'=>$this->Param('GrandChildNoun'),
				'GrandChildNounPlural'=>$this->Param('GrandChildNounPlural'),
			];
			
			if($this->isUserAdmin()) {
				$this->entry['Publish'] = $this->Param('Publish');
				$this->entry['Code'] = $this->Param('Code');
				$this->entry['OriginalEntryid'] = 0;
			} else {
				$this->entry['Publish'] = '0';
				$this->entry['Code'] = '';
				if($this->entry_unset['id'] && ($this->handler->desired_action === 'Edit' || $this->handler->desired_action === 'Update')) {
					$this->entry['OriginalEntryid'] = $this->entry_unset['id'];
				} else {
					$this->entry['OriginalEntryid'] = 0;
				}
			}
			
			foreach($this->entry as $field => $value) {		# one-line fields: a pasted line break becomes a space
				$this->entry[$field] = $this->FlattenLineBreaks(['text'=>$value]);
			}
			
			if($this->handler->desired_action === 'Save') {
				$this->entry['id'] = '0';
			} else {
				if($this->entry_unset['id'] && $this->isUserAdmin()) {
					$this->entry['id'] = $this->entry_unset['id'];
				} else {
					$this->entry['id'] = '0';
				}
			}
			
			$this->entry_unset['association'] = $this->association;
			$this->entry_unset = $this->SetAssociationRecordsForEntries(['entries'=>[$this->entry_unset]])[0];
			
			if($this->Param('title-smart-title-case')) {
				$this->entry['Title'] = $this->GenerateSmartTitle(['title'=>$this->entry['Title']]);
			}
			
			if($this->Param('title-de-romanize-numbers')) {
				$this->entry['Title'] = $this->ConvertSentenceRomanNumerals(['sentence'=>$this->entry['Title']]);
			}
			
			if($this->Param('subtitle-smart-title-case')) {
				$this->entry['Subtitle'] = $this->GenerateSmartTitle(['title'=>$this->entry['Subtitle']]);
			}
			
			if($this->Param('listtitle-smart-title-case')) {
				$this->entry['ListTitle'] = $this->GenerateSmartTitle(['title'=>$this->entry['ListTitle']]);
			}
			
			if(strlen($this->entry['Code']) === 0) {		# do this last, after all the other auto-generates
				$this->entry['Code'] = $this->GenerateUnblockedEntryCode();
			}
			
			return TRUE;
		}
		
		public function SetRecordFromQuery_EntryTranslation() {
			$this->entrytranslation_unset = $this->entrytranslation;
			
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'entrytranslation']);
			
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Description() {
			$this->description_unset = $this->description;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'description']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Quote() {
			$this->quote_unset = $this->quote;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'quote']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_TextBody() {
			$this->textbody_unset = $this->textbody;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'textbody']);
			$this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Image() {
			$this->image_unset = $this->image;
			
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'image']);
			
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_ImageTranslation() {
			$this->imagetranslation_unset = $this->imagetranslation;
			
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'imagetranslation']);
			
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Tag() {
			$this->tag_unset = $this->tag;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'tag']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Link() {
			$this->link_unset = $this->link;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'link']);
			$this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_EventDate() {
			$this->eventdate_unset = $this->eventdate;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'eventdate']);
			$this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Association() {
			$this->association_unset = $this->association;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'association']);
			$this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_AvailabilityDates() {
			$this->availabilitydaterange_unset = $this->availabilitydaterange;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'availabilitydaterange']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Definition() {
			$this->definition_unset = $this->definition;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'definition']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Price() {
			$this->price_unset = $this->price;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'price']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_AcademicReference() {
			$this->academicreference_unset = $this->academicreference;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'academicreference']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
		
		public function SetRecordFromQuery_Publication() {
			$this->publication_unset = $this->publication;
			$set_record_from_query_args = $this->RecordQueryDefinition(['variablename'=>'publication']);
			return $this->SetRecordFromQuery($set_record_from_query_args);
		}
	}

?>

<?php

	/*
		Belt stage seven: Format.  The saved records are made fit to show
		back -- the dates decoded, the definitions generated where asked.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsFormat {
		public $availabilitydaterange;
		public $availabilitydateranges;
		public $grammar;
		public $textcleanup;
		public $definition_object;
		public $definition;

		public function FormatSavedRecordFromQueryForAll() {
			$format_results = TRUE;
			
			if(!$this->FormatSavedRecordFromQuery_Entry()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_EntryTranslation()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Description()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Quote()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_TextBody()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Image()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_ImageTranslation()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Tag()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Link()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Price()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_AcademicReference()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Publication()) {
				$format_results = FALSE;
			}

			if(!$this->FormatSavedRecordFromQuery_WordPiece()) {
				$format_results = FALSE;
			}

			if(!$this->FormatSavedRecordFromQuery_WordForm()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_EventDate()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_AvailabilityDates()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_EntryPermission()) {
				$format_results = FALSE;
			}
			
			if(!$this->FormatSavedRecordFromQuery_Definition()) {
				$format_results = FALSE;
			}
			
			return $format_results;
		}
		
						// Saved Record Display Formatting
						// ---------------------------------------------
		
		public function FormatSavedRecordFromQuery_Entry() {
			$format_args = [
				'recordtype'=>'entry',
				'recordfields'=>[
					'Title',
					'Subtitle',
					'ListTitle',
					'ListTitleSortKey',
					'Code',
					'ChildAdjective',
					'ChildNoun',
					'ChildNounPlural',
					'GrandChildAdjective',
					'GrandChildNoun',
					'GrandChildNounPlural',
					'Publish',
					'OriginalEntryid',
				],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_EntryTranslation() {
			$format_args = [
				'recordtype'=>'entry',
				'recordfields'=>[
					'Title',
					'Subtitle',
					'ListTitle',
					'ListTitleSortKey',
					'Language',
				],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Description() {
			$format_args = [
				'recordtype'=>'description',
				'recordfields'=>['Description', 'Source', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Quote() {
			$format_args = [
				'recordtype'=>'quote',
				'recordfields'=>['Quote', 'Source', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_TextBody() {
			$format_args = [
				'recordtype'=>'textbody',
				'recordfields'=>['Text', 'Source', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Image() {
			$format_args = [
				'recordtype'=>'image',
				'recordfields'=>['Title', 'Description', 'FileName'],
			];
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_ImageTranslation() {
			$format_args = [
				'recordtype'=>'image',
				'recordfields'=>['Title', 'Description', 'FileName', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Tag() {
			$format_args = [
				'recordtype'=>'tag',
				'recordfields'=>['Tag', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Link() {
			$format_args = [
				'recordtype'=>'link',
				'recordfields'=>['Title', 'Description', 'URL', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Price() {
			$format_args = [
				'recordtype'=>'price',
				'recordfields'=>['PriceValue', 'Currency'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_AcademicReference() {
			$format_args = [
				'recordtype'=>'academicreference',
				'recordfields'=>['ReferenceEntryid', 'Headword', 'VolumeNumber', 'VolumeTitle', 'ChapterNumber', 'ChapterTitle', 'Page', 'Verdict', 'Note'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Publication() {
			$format_args = [
				'recordtype'=>'publication',
				'recordfields'=>['Authors', 'Editors', 'Translators', 'Publisher', 'Edition', 'Year', 'ISBN'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_WordPiece() {
			$format_args = [
				'recordtype'=>'wordpiece',
				'recordfields'=>['Language', 'Position', 'Piece', 'Role', 'Answers'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_WordForm() {
			$format_args = [
				'recordtype'=>'wordform',
				'recordfields'=>['BuiltFromEntryid', 'Language', 'BaseWord', 'Features', 'Source'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_EventDate() {
			$format_args = [
				'recordtype'=>'eventdate',
				'recordfields'=>['EventDateTime', 'Title', 'Description', 'Language'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_AvailabilityDates() {
			if(is_array($this->availabilitydaterange_unprepared) && $availabilitydaterange_unprepared->tag[0]['id']) {
				$availabilitydateranges = $this->availabilitydaterange_unprepared;
				$newavailabilitydateranges = [];
				
				foreach($availabilitydateranges as $availabilitydaterange) {
					if(strlen($availabilitydaterange['AvailabilityStartTime'])) {
						$availabilitydaterange['AvailabilityStart'] .= ' (' . $availabilitydaterange['AvailabilityStartTime'] . ')';
					}
					
					if(strlen($availabilitydaterange['AvailabilityEndTime'])) {
						$availabilitydaterange['AvailabilityEnd'] .= ' (' . $availabilitydaterange['AvailabilityEndTime'] . ')';
					}
					
					$newavailabilitydateranges[] = $availabilitydaterange;
				}
				
				$this->availabilitydateranges = $newavailabilitydateranges;
			} else {
				if(strlen($this->availabilitydaterange_unprepared['AvailabilityStartTime'])) {
					$this->availabilitydaterange['AvailabilityStart'] .= ' (' . $this->availabilitydaterange_unprepared['AvailabilityStartTime'] . ')';
				}
				
				if(strlen($this->availabilitydaterange_unprepared['AvailabilityEndTime'])) {
					$this->availabilitydaterange['AvailabilityEnd'] .= ' (' . $this->availabilitydaterange_unprepared['AvailabilityEndTime'] . ')';
				}
			}
			
			return TRUE;
		}
		
		public function FormatSavedRecordFromQuery_EntryPermission() {
			$format_args = [
				'recordtype'=>'entrypermission',
				'recordfields'=>['Userid'],
			];
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
		
		public function FormatSavedRecordFromQuery_Definition() {
			$format_args = [
				'recordtype'=>'definition',
				'recordfields'=>['Term', 'Definition'],
			];

			$autogenerate_definitions = $this->Param('autogenerate-definitions');
			if($autogenerate_definitions) {
				$autogenerated_definition_sources = [];
				
				foreach($this->textbody as $textbody) {
					$autogenerated_definition_sources[] = $textbody['Text'];
			//		print_r($textbody);
				}
				
				$autogenerated_definition_sources_count = count($autogenerated_definition_sources);
				
				if($autogenerated_definition_sources_count) {
					ggreq('classes/Language/Grammar.php');
					$this->grammar = new Grammar();
					
					ggreq('classes/Language/TextCleanup.php');
					$this->textcleanup = new TextCleanup(['grammar'=>$this->grammar]);
					
					ggreq('classes/Language/Definition.php');
					$this->definition_object = new Definition(['grammar'=>$this->grammar, 'textcleanup'=>$this->textcleanup]);
					
				//	print_r($this->definition);
					$definition_hash = [];
					
					foreach($this->definition as $defined_piece) {
						$definition_hash[$defined_piece['Term']] = TRUE;
					}
					
					$full_definition_list = [];
					
					for($i = 0; $i < $autogenerated_definition_sources_count; $i++) {
						$autogenerated_definition_source = $autogenerated_definition_sources[$i];
						$definitions_found = $this->definition_object->GetDefinitions(['text'=>strip_tags($autogenerated_definition_source)]);
						
				//		print_r($definitions_found);
						
						foreach($definitions_found as $term => $definition_list) {
							if(!$definition_hash[$term]) {
								if(!$full_definition_list[$term]) {
									$full_definition_list[$term] = [];
								}
								
								$full_definition_list[$term] = array_merge($full_definition_list[$term], $definition_list);
							}
						}
					}
			#		print_r($full_definition_list);
					
					foreach($full_definition_list as $term => $multiple_definitions) {
						foreach($multiple_definitions as $single_definition) {
							$definition_record = [
								'Term'=>$term,
								'Definition'=>$single_definition,
								'Entryid'=>$this->entry['id'],
							];
							
							$definition_insert_args = [
								'type'=>'Definition',
								'definition'=>$definition_record,
							];
							
							$new_definition_record = $this->db_access_object->CreateRecord($definition_insert_args);
							$this->definition[] = $new_definition_record;
						}
					}
				}
			}
			
			return $this->FormatSavedRecordFromQuery_Base($format_args);
		}
	}

?>

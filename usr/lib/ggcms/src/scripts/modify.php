<?php

	/*
		Please, if you need to modify entries via cli, use this cli file:

			usr/lib/ggcms/cli/scripts/internal/entries/modify_entry.php
	*/

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleImages.php');
	ggreq('traits/scripts/SimpleLanguages.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/UserAccounts.php');
	ggreq('traits/scripts/ChangeRecords.php');
	
	class modify extends basicscript {
						// Traits
						// ---------------------------------------------
		
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleImages;
		use SimpleLanguages;
		use SimpleLookupLists;
		use UserAccounts;
		
			//  The belt every saved, updated and deleted entry rides: see ChangeRecords.php.
		
		use SimpleORM, ChangeRecords {
			ChangeRecords::updateFileName insteadof SimpleORM;
			ChangeRecords::startsWith insteadof SimpleORM;
		}
		
		
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return TRUE;
		}
		
		#public function AdminOnly() {
		#	return TRUE;
		#}
		
		public function RequiresLogin() {
			return TRUE;
		}
		
						// Add Entry Form
						// ---------------------------------------------
		
		public function Add() {
			$this->SetOrmBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# Causes 404
			}
			
			$this->SetAssociationRecords();
			$this->SetSelectableLanguages();
			$this->SetLastAdd([]);
			$this->SetLastUnPublished();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
		public function SetLastUnpublished() {
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			$this->last_unpublished_add = $this->orm->GetLastChildAdded([
				'entry'=>$this->entry,
				'where'=>'Publish = 0 AND OriginalEntryid = 0',
			]);
			
			$this->last_unpublished_edit = $this->orm->GetLastChildAdded([
				'entry'=>$this->entry,
				'where'=>'Publish = 0 AND OriginalEntryid != 0',
			]);
		#	print("BT:!");
		#	print_r($this->last_unpublished_edit);
			
			return TRUE;
		}
		
						// Edit Selected Entry
						// ---------------------------------------------
		
//		public function display() {
//			$this->desired_action = 'Edit';
//			return $this->Edit();
//		}
		
		public function Edit() {
			$this->SetOrmBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# Trigger 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
			$this->SetEntryFromCode();
			$this->OrderAndFillChildRecords();
			$this->SetAssociationRecords();
			
			$this->FillBlankChildRecords();
			$this->SetSelectableLanguages();
			$this->SetLastEdit([]);
			
			$this->FormatDates();
			$this->SetLastUnPublished();
			
			$this->FormatErrors();
			
			return TRUE;
		}

		
		public function PrepareChildRecords() {
			$image_count = count($this->image);
			if($this->image && $image_count > 0) {
				$prefix = $this->entry['id'] . '-';
				for($i = 0; $i < $image_count; $i++) {
					$image = $this->image[$i];
					if($this->startsWith($image['FileName'], $prefix)) {
						$pos = strpos($this->startsWith($image['FileName'], $prefix));
						if ($pos !== FALSE) {
							$image['FileName'] = substr_replace($image['FileName'], '', $pos, strlen($prefix));
							$this->image[$i] = $image;
						}
					}
				}
			}
		}
		
						// Update Selected Entry
						// ---------------------------------------------

			/*
				The belt is ChangeRecordsVerbs::UpdateEntry(); this is what an
				editor is told about it.  An entry with no parent goes as a
				save, and is reported as one.
			*/

		public function Update() {
			if($this->Param('Delete')) {		# Delete decides for itself who may
				return $this->Delete();
			}

			if(!$this->UpdateEntry()) {
				return FALSE;	# Causes 404
			}

			if($this->saveattempted) {
				return $this->SaveReport();
			}

			if($this->saveattemptresults) {
				$temporary_hyperlinked_record_list = $this->record_list;
				$temporary_hyperlinked_record_list[(count($temporary_hyperlinked_record_list) - 1)] = $this->entry;

				$get_hyperlinked_entry_view_args = [
					'entry'=>$this->entry,
					'entrylist'=>$temporary_hyperlinked_record_list,
					'scriptname'=>'view.php',
				];

				$matching_record_view_link = $this->GetHyperlinkedEntryView($get_hyperlinked_entry_view_args);

				$get_hyperlinked_entry_edit_args = [
					'entry'=>$this->entry,
					'entrylist'=>$temporary_hyperlinked_record_list,
					'scriptname'=>'modify.php?action=Edit',
				];

				$matching_record_edit_link = $this->GetHyperlinkedEntryView($get_hyperlinked_entry_edit_args);

				$save_status = '';
				$save_status .= 'Update successful!<br>';
				$save_status .= '<br>&bull; View Results: ' . $matching_record_view_link;
				$save_status .= '<br>&bull; Edit Record: ' . $matching_record_edit_link;
				$save_status .= '<br>&bull; Add Another: <a href="modify.php?action=Add">Add Entry</a>';

				if(!$this->isUserAdmin()) {
					$save_status .= '<br>&bull; User Panel (all pending submissions): <a href="/user-panel.php">User Panel</a>';
				}

				$save_status .= $this->FlushPageCacheAndReport();

				$this->save_status = $save_status;
			}

			$this->entry['association'] = $this->entry_unset['association'];	# so that the proper image shows in the header
			$this->FormatErrors();
			
			return TRUE;
		}
		
						// Delete Entry
						// ---------------------------------------------
		
			/*
				The belt is ChangeRecordsVerbs::DeleteEntryWithChildRecords();
				nothing about a delete is reported beyond its status.
			*/

		public function Delete() {
			return $this->DeleteEntryWithChildRecords();
		}

						// Save Entry
						// ---------------------------------------------

			//  The belt is ChangeRecordsVerbs::SaveEntry(); SaveReport() tells the editor.

		public function Save() {
			if(!$this->SaveEntry()) {
				return FALSE;	# Trigger 404
			}

			return $this->SaveReport();
		}

		public function SaveReport() {
			if($this->saveattemptresults) {
				$record_list_to_link = $this->record_list;
				$record_list_to_link[] = $this->entry;

				$get_hyperlinked_entry_view_args = [
					'entry'=>$this->entry,
					'entrylist'=>$record_list_to_link,
					'scriptname'=>'view.php',
				];

				$matching_record_view_link = $this->GetHyperlinkedEntryView($get_hyperlinked_entry_view_args);

				$get_hyperlinked_entry_edit_args = [
					'entry'=>$this->entry,
					'entrylist'=>$record_list_to_link,
					'scriptname'=>'modify.php?action=Edit',
				];

				$matching_record_edit_link = $this->GetHyperlinkedEntryView($get_hyperlinked_entry_edit_args);

			#	print("<PRE>");
			#	print_r($record_list_to_link);
			#	print("</PRE>");

				$save_message = '';

				$save_message .= 'Save successful!<br>';
				$save_message .= '<br>&bull; View Results: ' . $matching_record_view_link;
				$save_message .= '<br>&bull; Edit Results: ' . $matching_record_edit_link;
				$save_message .= '<br>&bull; Add Another New Entry: <a href="modify.php?action=Add">Add Entry</a>';

				if(!$this->isUserAdmin()) {
					$save_message .= '<br>&bull; User Panel (all pending submissions): <a href="/user-panel.php">User Panel</a>';
				}

				$save_message .= $this->FlushPageCacheAndReport();

				$this->save_status = $save_message;
			}

			$this->entry['association'] = $this->entry_unset['association'];	# so that the proper image shows in the header
			$this->FormatErrors();

			return TRUE;
		}
		
		public function modifiableRecordTypes() {
			$modifiable_fields = $this->handler->globals->ShowModifiableFields();
			
			$record_count = count($this->record_list);
			
			if($this->handler->desired_action === 'Add') {
				$record_count++;
			}
			
			$elements = [];
			
			if($modifiable_fields['Title']) {
				$elements[] = 'Title';
			}
			
			if($modifiable_fields['Subtitle']) {
				$elements[] = 'Subtitle';
			}
			
			if($modifiable_fields['ListTitle']) {
				$elements[] = 'List Title';
			}
			
			if($modifiable_fields['ListTitleSortKey']) {
				$elements[] = 'List Title Sort Key';
			}
			
			if($modifiable_fields['Code'] && $this->isUserAdmin()) {
				$elements[] = 'Code';
			}
			
			if($record_count <= $this->handler->globals->MaxParentGrammarControlDepth()) {
				array_push($elements,
					'Child Adjective',
					'Child Noun',
					'Child Noun Plural',
					'Grand Child Adjective',
					'Grand Child Noun',
					'Grand Child Noun Plural',
				);
			}
			
			if($modifiable_fields['EntryTranslation']) {
				$elements[] = 'Entry Translation';
			}
			
			if($modifiable_fields['AvailabilityStart']) {
				$elements[] = 'Availability Start';
			}
			
			if($modifiable_fields['AvailabilityEnd']) {
				$elements[] = 'Availability End';
			}
			
			if($modifiable_fields['Description']) {
				$elements[] = 'Description';
			}
			
			if($modifiable_fields['Quote']) {
				$elements[] = 'Quote';
			}
			
			if($modifiable_fields['TextBody']) {
				$elements[] = 'Text Body';
			}
			
			if($modifiable_fields['Image']) {
				$elements[] = 'Image';
			}
			
			if($modifiable_fields['ImageTranslation']) {
				$elements[] = 'Image Translation';
			}
			
			if($modifiable_fields['Tag']) {
				$elements[] = 'Tag';
			}
			
			if($modifiable_fields['Link']) {
				$elements[] = 'Link';
			}
			
			if($modifiable_fields['Price']) {
				$elements[] = 'Price';
			}
			
			if($modifiable_fields['AcademicReference']) {
				$elements[] = 'Academic Reference';
			}
			
			if($modifiable_fields['Publication']) {
				$elements[] = 'Publication';
			}
			
			if($modifiable_fields['EventDate']) {
				$elements[] = 'Event Date';
			}
			
			if($this->showAssociations() || $record_count <= $this->handler->globals->MaxParentAssociationControlDepth()) {
				$elements[] = 'Association';
			};
			
			if($modifiable_fields['Definition']) {
				$elements[] = 'Definition';
			}
			
			if($modifiable_fields['Publish'] && $this->isUserAdmin()) {
				$elements[] = 'Publish';
			}
			
			if($modifiable_fields['Save']) {
				$elements[] = 'Save';
			}
			
			return $elements;
		}
		
		static function removeAccents($string) {
			return preg_replace('/[\x{0300}-\x{036f}]/u', "", normalizer_normalize($string, Normalizer::FORM_D));
		}
		
		public function cmp($a, $b) {
			return strcmp($this->removeAccents($a['ListTitle']), $this->removeAccents($b['ListTitle']));
		}
		
		public function displayAssociations($args) {
			$selected = $args['selected'];
			$defaultblankoption = $args['defaultblankoption'];
			
			if($defaultblankoption) {
				print('<option title="No Selection" value="">');
				print('(none)');
				print('</option>');
			}
			
			$associations = $this->getAssociations($args);
			$associations_count = count($associations);
			
			usort($associations, [$this, 'cmp']);
			
			for($i = 0; $i < $associations_count; $i++) {
				$association = $associations[$i];
				print('<option title="[' . $association['id'] . ']" value="' . $association['id'] . '"');
				if($selected === $association['id']) {
					print(' SELECTED="SELECTED"');
				}
				print('>');
				if(strlen($association['ListTitle']) > 40) {
					print(substr($association['ListTitle'], 0, 40));
					print('...');
				} else {
					print($association['ListTitle']);
				}
				print('</option>');
			}
			
			return TRUE;
		}
		
		public function displayAssociationTypes() {
			return $this->displayAssociationAbstractTypes(['field'=>'Type']);
		}
		
		public function displayAssociationSubTypes() {
			return $this->displayAssociationAbstractTypes(['field'=>'SubType']);
		}
		
		public function displayAssociationAbstractTypes($args) {
			$field = $args['field'];
			$association_types = $this->getAssociationTypes()[$field];
			
			$datalist = '<datalist id="Association_' . $field . '">';
			
			$association_types_count = count($association_types);
			
			for($i = 0; $i < $association_types_count; $i++) {
				$association_type = $association_types[$i];
				
				$datalist .= '<option value="' . $association_type . '"></option>';
			}
			
			$datalist .= '</datalist>';
			
			print($datalist);
			
			return TRUE;
		}
		
		public function getAssociationTypes() {
			if($this->association_types) {
				return $this->association_types;
			}
			
			$sql_start = 'SELECT DISTINCT ';
			$sql_end = ' FROM Association ';
			$sql_end .= 'JOIN Entry Entry1 ON Entry1.id = Association.Entryid ';
			$sql_end .= 'JOIN Entry Entry2 ON Entry2.id = Association.ChosenEntryid ';
			$sql_end .= 'WHERE Entry1.Publish = 1 AND Entry2.Publish = 1 ';
			
			$association_types = [];
			$association_types['Type'] = array_column($this->db_access_object->RunQuery(['sql'=>$sql_start . 'Type' . $sql_end]), 'Type');
			$association_types['SubType'] = array_column($this->db_access_object->RunQuery(['sql'=>$sql_start . 'SubType' . $sql_end]), 'SubType');
			
			return $this->association_types = $association_types;
		}
		
		public function getAssociations($args) {
			if($this->potential_associations) {
			#	return $this->potential_associations;
			}
			
			if($args['config']) {
				$config = [$args['config']];
			} else {
				$config = $this->setAssociationConfigToUse();
				
				if(!$config || !$config[0] || $config[0] === '') {
					return [];
			#		return $this->potential_associations = [];
				}
			}
			
			$cache_key = 'potential_associations' . $config[0];
			
			if($this->$cache_key) {
				return $this->$cache_key;
			}
			
			$sql = 
				'SELECT Entry.* FROM Entry ' .
				'JOIN Assignment ON Assignment.Childid = Entry.id ' .
				'JOIN Entry Parent ON Parent.id = Assignment.Parentid ' .
				'WHERE Parent.Code = ? AND Entry.Publish = 1 ; ';
			
			return $this->$cache_key = $this->db_access_object->RunQuery([
				'sql'=>$sql,
				'args'=>[$config[0]],
			]);
		}
		
		public function showAssociations() {
			$modifiable_fields = $this->handler->globals->ShowModifiableFields();
			
			if(!$modifiable_fields['Association']) {
				return FALSE;
			}
			
			if(count($this->setAssociationConfigToUse()) === 0) {
		#		return FALSE;
			}
			
			return TRUE;
		}
		
		public function showAssociationRawIds() {
			return $this->setAssociationConfigToUse()[0] === '';
		}
		
		public function ElementDisplay($args) {
			$element = $args['element'];
			
			$element_display = $this->handler->globals->ModifiableFieldDisplay();
			
			if(array_key_exists($element, $element_display)) {
				return '<span style="font-family:tahoma, arial;"><b><u>' . $element_display[$element] . '</u></b></span>';
			}
			
			return '<span style="font-family:tahoma, arial;"><b><u>' . $element . '</u></b></span>';
		}
		
		public function SourceDisplay() {
			print('<span style="font-family:tahoma, arial;"><b><u>');
			print('Source:</u> ');
			print('</b></span>');
		}
		
		public function LanguageDisplay() {
			print('<span style="font-family:tahoma, arial;"><b><u>');
			print('Language:</u> ');
			print('</b></span>');
		}
		
		public function TitleDisplay() {
			print('<span style="font-family:tahoma, arial;"><b><u>');
			print('Title:</u> ');
			print('</b></span>');
		}
		
		public function setAssociationConfigToUse() {
			if($this->association_config_to_use) {
				return $this->association_config_to_use;
			}
			
			$entrycodes = $this->handler->globals->AssociationEntryCodes();
			
			$max_record_depth = $this->handler->globals->AssociationEntryCodes_MaxDepth();
			$record_list_count = count($this->record_list);
			
			$config_to_use = $entrycodes['*'];
			
			for($i = 0; $i < min($max_record_depth, $record_list_count); $i++) {
				$record = $this->record_list[$i];
				if(array_key_exists($record['Code'], $entrycodes)) {
					$config_to_use = $entrycodes[$record['Code']];
				}
			}
			
			return $this->association_config_to_use = $config_to_use;
		}
		
		public function SubtleFieldHints() {
			$code_hint = 'It is recommended to leave this blank, it will be autogenerated.';
			
			if(!$this->isUserAdmin()) {
				$title_hint = 'This should be the regular, full title as it appears on its own page, i.e., "The Coolest Blog Post Ever", without quotes.';
				$subtitle_hint = 'This should be the subtitle as it appears on its own page.  It should be something that adds description to the Title, if available.';
				$list_title_hint = 'This is autogenerated and can generally be left alone, unless you are adding a person, then this should be "LastName, FirstName."  If not generating for a person, this should be the regular, full title as it appears within a list, i.e., "Coolest Blog Post Ever, The", without quotes.';
				$list_title_sort_key_hint = 'This is the value that this submission will be sorted by within lists.  You should usually leave this blank, as it is auto-generated based on the Title.  For instance, you might want this value to be "1" or "alpha" for the preface, but "Chapter 1", "Chapter 2", etc., for the other submissions, so that they sort in the order of: Preface, Chapter 1, Chapter 2, etc..';
				$description_hint = 'Please describe what you are uploading in plain text.  The source should be the author/origin of the text.';
				$quote_hint = 'If there is some good, juicy quote for this text, put it here in plain text.  The source should be where the quote appears within the text, i.e., "Section 2," or "Paragraph 18", etc.';
				$text_body_hint = 'The textbody should be in pure HTML.  Please fill in a source if you have a textbody, like the website it came from.  Don\'t submit a text body if you intend to upload individual chapters.  In that case, just create the record, and then create new individual, sub-records for the chapters.  Do not list a source if there is no text being uploaded in this submission.';
				$image_hint = 'If submitting for a new person record, submit that person\'s portraits and indicate in the Title or Description the site where the image came from.  If submitting text somewhere else, make sure to identify the proper copyright-holder of the image in the Title or the Description.  Creative-Commons-licensed material is encouraged.';
				$tag_hint = 'You may submit optional tags associated with this submission here.';
				$link_hint = 'You may submit a link associated with this submission here.  If submitting a person record, it is recommended that you include a Wikipedia link, if available.';
				$price_hint = 'The price this is offered at, as a plain amount like 12.50, and its currency.';
				$academic_reference_hint = 'A published book that backs this entry: choose it from the Reference branch, say where in it (headword, volume, chapter, page), and whether it supports what the entry says.  The book is cited on the page; its text never is.';
				$publication_hint = 'For a book under the Reference branch: who wrote, edited and translated it, its publisher, edition, year and ISBN, as a citation prints them.';
				$event_date_hint = 'You may set a negative value, for instance, "-571-00-00", to indicate the year 571 BCE.';
				$association_hint = 'Select the author here, and if necessary, add additional people records for co-authors, translators, editors, and the like.';
				$publish_hint = 'If checked, this will be publicly available, otherwise, it will not be publicly available, and only visible to admin and the user who created it.';
				
				$event_date_hint .= ' Please set the title for the event date.';
			}
			
			return [
				'Title'=>$title_hint,
				'Subtitle'=>$subtitle_hint,
				'List Title'=>$list_title_hint,
				'List Title Sort Key'=>$list_title_sort_key_hint,
				'Code'=>$code_hint,
				'Description'=>$description_hint,
				'Quote'=>$quote_hint,
				'Text Body'=>$text_body_hint,
				'Image'=>$image_hint,
				'Tag'=>$tag_hint,
				'Link'=>$link_hint,
				'Price'=>$price_hint,
				'Academic Reference'=>$academic_reference_hint,
				'Publication'=>$publication_hint,
				'Event Date'=>$event_date_hint,
				'Association'=>$association_hint,
				'Publish'=>$publish_hint,
			];
		}
		
		public function ActiveFieldHints() {
			$config = $this->setAssociationConfigToUse();
			
			$association_description = '';
			
			if(!$this->isUserAdmin()) {
				$association_description = 'Are you not seeing an author here you expected?  Then please add them at <a href="/' . $config[0] . '/modify.php?action=Add">People: Add Entry</a>.  Thank you!';
			}
			
			return [
				'Association'=>$association_description,
			];
		}
	}

?>

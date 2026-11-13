<?php

	/*
		Belt stage three: Validate.  Every record is checked -- lengths
		against the table's own description, a code nobody else holds,
		a price that is a number in a currency the schema allows, a date
		that is a date, an image that is an image.  A failure adds to
		$this->errors and stops the entry; nothing is written for it.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsValidate {
		public $errors;
		public $reference_choices;
		public $check_record_results;
		
		public function ValidateForSavingForAll() {
			$save_results = TRUE;
			
			if(!$this->ValidateRecordForSaving_Entry()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_EntryTranslation()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Description()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Quote()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_TextBody()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Image()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_ImageTranslation()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Tag()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Link()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Price()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_AcademicReference()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Publication()) {
				$save_results = FALSE;
			}

			if(!$this->ValidateRecordForSaving_WordPiece()) {
				$save_results = FALSE;
			}

			if(!$this->ValidateRecordForSaving_WordForm()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_EventDate()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Association()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_AvailabilityDates()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Assignment()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_EntryPermission()) {
				$save_results = FALSE;
			}
			
			if(!$this->ValidateRecordForSaving_Definition()) {
				$save_results = FALSE;
			}
			
			return $save_results;
		}
		
						// Record Validating
						// ---------------------------------------------
		
		public function ValidateRecordForSaving_Entry() {
			$validation_results = TRUE;
			
			if(!strlen($this->entry['Title']) && !$this->Param('autoincrement-title')) {
				$this->errors[] = ['You must enter a Title.'];
				$validation_results = FALSE;
			}
			
		#	if(!strlen($this->entry['Code']) && !$this->Param('autoincrement-title')) {
		#		$this->errors[] = ['You must enter a Code.'];
		#		$validation_results = FALSE;
		#	}
			
			if(strlen($this->entry['Title']) > 255) {
				$this->errors[] = ['The Title may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['Subtitle']) > 255) {
				$this->errors[] = ['The Subtitle may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['ListTitle']) > 255) {
				$this->errors[] = ['The ListTitle may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['ListTitleSortKey']) > 255) {
				$this->errors[] = ['The ListTitleSortKey may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['Code']) > 255) {
				$this->errors[] = ['The Code may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['ChildAdjective']) > 255) {
				$this->errors[] = ['The ChildAdjective may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['ChildNoun']) > 255) {
				$this->errors[] = ['The ChildNoun may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['ChildNounPlural']) > 255) {
				$this->errors[] = ['The ChildNounPlural may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['GrandChildAdjective']) > 255) {
				$this->errors[] = ['The GrandChildAdjective may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['GrandChildNoun']) > 255) {
				$this->errors[] = ['The GrandChildNoun may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['GrandChildNounPlural']) > 255) {
				$this->errors[] = ['The GrandChildNounPlural may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($this->entry['Publish']) > 1) {
				$this->errors[] = ['The Publish field may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(
				$this->isUserAdmin() && $this->parent['id'] &&					// Is it a non-master record entry?	# BT: FIX, wtf userdamin????
				!$this->ValidateRecordForSaving_Entry_Code()) {		// Is the code claimed by another entry?
				$check_record_results_count = count($this->check_record_results);
				$check_record_results_index = $check_record_results_count - 1;
				
				$get_hyperlinked_entry_view_args = [
					'entry'=>$this->check_record_results[$check_record_results_index],
					'entrylist'=>$this->check_record_results,
				];
				
				$matching_record_link = $this->GetHyperlinkedEntryView($get_hyperlinked_entry_view_args);
				
				$error_message = 'The Code you have entered is already in use by ' . $matching_record_link . '.';
				
				$this->errors[] = [$error_message];
				$validation_results = FALSE;
			}
			
			return $validation_results;
		}
		
			/*
				The sibling already holding a code, or FALSE -- the question
				ValidateRecordForSaving_Entry_Code() asks, for a candidate code,
				so GenerateUnblockedEntryCode() can ask it before validation does.
			*/
		public function EntryCodeHolder($args) {
			$record_list = $this->object_list;

			if($this->desired_action === 'Save') {
				$record_list[] = $args['code'];
			} else {
				$record_list[count($record_list) - 1] = $args['code'];
			}

			$holders = $this->CheckRecordTree([
				'recordlist'=>$record_list,
				'availabilitylimit'=>1,
			]);

			if(count($holders) && $holders[count($holders) - 1]['id'] != $this->entry['id']) {
				return $holders[count($holders) - 1];
			}

			return FALSE;
		}

		public function ValidateRecordForSaving_Entry_Code() {
			$record_list_to_check_code = $this->object_list;
			
			if($this->desired_action === 'Save') {
				$record_list_to_check_code[] = $this->entry['Code'];
			}
			
			$check_record_args = [
				'recordlist'=>$record_list_to_check_code,
				'availabilitylimit'=>1,
			];
			
			$duplicate_results = $this->CheckRecordTree($check_record_args);
			$results_count = count($duplicate_results);
			$results_index = $results_count - 1;
			
			$this->check_record_results = $duplicate_results;
			
			if(count($duplicate_results) && $duplicate_results[$results_index]['id'] != $this->entry['id']) {
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function ValidateRecordForSaving_EntryTranslation() {
			$validation_results = TRUE;
			
			foreach($this->entrytranslation as $entry_translation) {
				if(strlen($this->entry_translation['Title']) > 255) {
					$this->errors[] = ['The Entry Translation\'s Title may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(strlen($this->entry_translation['Subtitle']) > 255) {
					$this->errors[] = ['The Entry Translation\'s Subtitle may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(strlen($this->entry_translation['ListTitle']) > 255) {
					$this->errors[] = ['The Entry Translation\'s ListTitle may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(strlen($this->entry_translation['ListTitleSortKey']) > 255) {
					$this->errors[] = ['The Entry Translation\'s ListTitleSortKey may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Description() {
			$validation_results = TRUE;
			
			foreach($this->description as $description) {
				if(strlen($description['Description']) > 512) {
					$this->errors[] = ['The Description may not be longer than 512 characters.'];
					$validation_results = FALSE;
				}
				
				if(strlen($description['Source']) > 512) {
					$this->errors[] = ['The Description\'s Source may not be longer than 512 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Quote() {
			$validation_results = TRUE;
			
			foreach($this->quote as $quote) {
				if(strlen($quote['Quote']) > 2048) {
					$this->errors[] = ['The Quote may not be longer than 2,048 characters.'];
					$validation_results = FALSE;
				}
				if(strlen($quote['Source']) > 1024) {
					$this->errors[] = ['The Quote\'s Source may not be longer than 1,024 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_TextBody() {
			$validation_results = TRUE;
			
			foreach($this->textbody as $textbody) {
				if(strlen($textbody['Source']) > 512) {
					$this->errors[] = ['The Text\'s Source may not be longer than 512 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
		public function updateFileName($args) {
			$filename = $args['filename'];
			
			if(!$filename) {
				return $filename;
			}
			
			$prefix = $this->entry['id'] . '-';
			
			if(!$this->startsWith($filename, $prefix)) {
				$filename = $prefix . $filename;
			}
			
			return $filename;
		}
		
		public function ValidateRecordForSaving_Image() {
			$validation_results = TRUE;
			
			$i = 0;
			
			$prefix = $this->entry['id'] . '-';
			$image_count = count($this->image);
			
			for ($i = 0; $i < $image_count; $i++) {
				$file = $this->image_files[$i];
				$original_image = $this->image_unset[$i];
				$i++;
				
				$validation_results = $this->ValidateSingleImage([
					'image'=>$image,
					'entrytype'=>'Image',
					'validationresults'=>$validation_results,
				]);
				
				if($file['name']) {	// New File Upload
					$image_folder_location = $this->GetImageFolderDirectory();
					
					$new_file_location = $image_folder_location . $image['FileName'];
					$new_icon_file_location = $image_folder_location . $file['icon_name'];
					$new_standard_file_location = $image_folder_location . $file['standard_name'];
					
					if($image['FileName'] != $original_image['FileName']) {
						$taken_file_names = [];
						
						if(is_file($new_file_location)) {
							$taken_file_names[] = $new_file_location;
						}
						
						if(is_file($new_icon_file_location)) {
							$taken_file_names[] = $new_icon_file_location;
						}
						
						if(is_file($new_standard_file_location)) {
							$taken_file_names[] = $new_icon_file_location;
						}
						
						if(count($taken_file_names)) {
							$this->errors[] = ['The filename (' . implode(', ', $taken_file_names) . ') is taken.  Please choose another filename.'];
							$validation_results = FALSE;
						}
					}
				} else {
					if($image['FileName'] !== $original_image['FileName']) {		// Rename/Move Old File Upload
						$uploaded_filename_pieces = explode(".", $image['FileName']);
						
						if($selected_filename_pieces) {
							$selected_filename_pieces_count = count($selected_filename_pieces);
						} else {
							$selected_filename_pieces_count = 0;
						}
						
						$last_uploaded_filename_piece = $uploaded_filename_pieces[$selected_filename_pieces_count - 1];
						
						if(count($uploaded_filename_pieces) > 1) {
							unset($uploaded_filename_pieces[count($selected_filename_pieces) - 1]);
						}
						
						$uploaded_icon_filename = implode(".", $uploaded_filename_pieces) . '-icon.' . $last_uploaded_filename_piece;
						$uploaded_standard_filename = implode(".", $uploaded_filename_pieces) . '-standard.' . $last_uploaded_filename_piece;
						
						$image_directory_location = $this->GetImageFolderDirectory();
						
						$new_file_location = $image_directory_location . $image['FileName'];
						$new_icon_file_location = $image_directory_location . $uploaded_icon_filename;
						$new_standard_file_location = $image_directory_location . $uploaded_standard_filename;
						
						$taken_file_names = [];
						
						if(is_file($new_file_location)) {
							$taken_file_names[] = $new_file_location;
						}
						
						if(is_file($new_icon_file_location)) {
							$taken_file_names[] = $new_icon_file_location;
						}
						
						if(is_file($new_standard_file_location)) {
							$taken_file_names[] = $new_standard_file_location;
						}
						
						if(count($taken_file_names)) {
							$this->errors[] = ['The filename (' . implode(', ', $taken_file_names) . ') is taken.  Please choose another filename.'];
							$validation_results = FALSE;
						}
					}
				}
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_ImageTranslation() {
			$validation_results = TRUE;
			
			foreach ($this->imagetranslation as $image_translation) {
				$validation_results = $this->ValidateSingleImage([
					'image'=>$image_translation,
					'entrytype'=>'Image Translation',
					'validationresults'=>$validation_results,
				]);
			}
			
			return $validation_results;
		}
		
		public function ValidateSingleImage($args) {
			$image = $args['image'];
			$entry_type = $args['entrytype'];
			$validation_results = $args['validationresults'];
			
			if(strlen($image['Title']) > 255) {
				$this->errors[] = [$entry_type . ' Titles may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($image['Description']) > 1023) {
				$this->errors[] = [$entry_type . ' Descriptions may not be longer than 1,023 characters.'];
				$validation_results = FALSE;
			}
			
			if(strlen($image['FileName']) > 255) {
				$this->errors[] = [$entry_type . ' FileNames may not be longer than 255 characters.'];
				$validation_results = FALSE;
			}
			
			if(preg_match('/advert/i', $image['FileName'])) {
				$this->errors[] = [$entry_type . ' FileNames may not contain the text "advert", because they may cause them to be blocked by ad-blocking software.'];
				$validation_results = FALSE;
			}
			
			if(preg_match('/%/i', $image['FileName'])) {
				$this->errors[] = [$entry_type . ' FileNames may not contain the "%" character, as this character is reserved as an escape character for http URLs (i.e., "%20" is a space, " ", etc.).'];
				$validation_results = FALSE;
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Tag() {
			$validation_results = TRUE;
			
			foreach ($this->tag as $tag) {
				if(strlen($tag['Tag']) > 255) {
					$this->errors[] = ['Tags may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Link() {
			$validation_results = TRUE;
			$charset = $this->handler->cleanser->utf8_characters->SystemCharSet();
			
			foreach ($this->link as $link) {
				if(mb_strlen($link['Title'], $charset) > 255) {
					$this->errors[] = ['Link Titles may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(mb_strlen($link['URL'], $charset) > 255) {
					$this->errors[] = ['URLs may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
			/*
				A plain amount, as PriceValue's decimal(10,2) holds it: up to
				eight whole digits and two after the point, with no currency
				sign or thousands separator, which MySQL would quietly turn
				into a different number.  A blank amount is a blank row and is
				left to PrepareRecordForSaving_Price().
			*/
		
		public function ValidateRecordForSaving_Price() {
			$validation_results = TRUE;
			$currencies = $this->PriceCurrencies();
			
			foreach ((array)$this->price as $price) {
				$amount = trim((string)$price['PriceValue']);
				
				if(!strlen($amount)) {
					continue;
				}
				
				if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) {
					$this->errors[] = ['Prices must be a plain amount, like 12.50, with no currency sign or commas.'];
					$validation_results = FALSE;
				}
				
				if(!in_array($price['Currency'], $currencies, TRUE)) {
					$this->errors[] = ['Prices must be in one of these currencies: ' . implode(', ', $currencies) . '.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
			/*
				The currencies a Price may be in, read from the Currency enum
				in Price's table description, so the list is kept in one
				place beside the schema rather than again here.
			*/
		
		public function PriceCurrencies() {
			$description = $this->handler->db_access->GetRecordDescription(['type'=>'Price']);
			
			return explode(',', str_replace("'", '', $description['Currency']['TypeAttribute']));
		}
		
			/*
				An academic reference names a book by its entry id, so the id
				must be a plain number, and its verdict one of the Verdict enum's.
				A row without a book is a blank row, left to
				PrepareRecordForSaving_AcademicReference().  Every text must fit
				its column, read from the table description, because MySQL would
				refuse the whole save over one long page title.
			*/
		
		public function ValidateRecordForSaving_AcademicReference() {
			$validation_results = TRUE;
			$verdicts = $this->AcademicReferenceVerdicts();
			
			foreach ((array)$this->academicreference as $reference) {
				$book = trim((string)$reference['ReferenceEntryid']);
				
				if(!strlen($book)) {
					continue;
				}
				
				if(!preg_match('/^\d{1,10}$/', $book)) {
					$this->errors[] = ['An academic reference\'s book must be chosen from the list, or given as an entry id.'];
					$validation_results = FALSE;
				}
				
				if(!in_array($reference['Verdict'], $verdicts, TRUE)) {
					$this->errors[] = ['An academic reference\'s verdict must be one of: ' . implode(', ', $verdicts) . '.'];
					$validation_results = FALSE;
				}
			}
			
			if(!$this->ValidateRecordLengths(['type'=>'AcademicReference', 'records'=>$this->academicreference, 'label'=>'An academic reference\'s'])) {
				$validation_results = FALSE;
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Publication() {
			return $this->ValidateRecordLengths(['type'=>'Publication', 'records'=>$this->publication, 'label'=>'A publication\'s']);
		}
		
		public function ValidateRecordForSaving_WordPiece() {
			return $this->ValidateRecordLengths(['type'=>'WordPiece', 'records'=>$this->wordpiece, 'label'=>'A word piece\'s']);
		}
		
		public function ValidateRecordForSaving_WordForm() {
			return $this->ValidateRecordLengths(['type'=>'WordForm', 'records'=>$this->wordform, 'label'=>'A word form\'s']);
		}
		
			/*
				Each varchar a record type has, held to the length its table
				description gives, with an error naming the field.
			*/
		
		public function ValidateRecordLengths($args) {
			$validation_results = TRUE;
			$description = $this->handler->db_access->GetRecordDescription(['type'=>$args['type']]);
			
			foreach ((array)$args['records'] as $record) {
				foreach ((array)$record as $field => $value) {
					if(!isset($description[$field]) || $description[$field]['TypeBase'] !== 'varchar') {
						continue;
					}
					
					if(mb_strlen((string)$value) > (int)$description[$field]['TypeAttribute']) {
						$this->errors[] = [$args['label'] . ' ' . $field . ' can be at most ' . $description[$field]['TypeAttribute'] . ' characters.'];
						$validation_results = FALSE;
					}
				}
			}
			
			return $validation_results;
		}
		
			/*
				The verdicts an academic reference may give, read from the Verdict
				enum in its table description, as PriceCurrencies() reads Price's.
			*/
		
		public function AcademicReferenceVerdicts() {
			$description = $this->handler->db_access->GetRecordDescription(['type'=>'AcademicReference']);
			
			return explode(',', str_replace("'", '', $description['Verdict']['TypeAttribute']));
		}
		
			/*
				The books an academic reference may cite: the published entries
				under the site's Reference branch, the child coded "reference" of
				an entry that is itself no one's child -- the site's root -- so a
				word deeper in the tree coded "reference" is never taken for the
				library.  Sorted as the index sorts them.
			*/
		
		public function ReferenceChoices() {
			if(is_array($this->reference_choices)) {
				return $this->reference_choices;
			}
			
			$sql =
				'SELECT Book.id, Book.Title, Book.Subtitle FROM Entry Book ' .
				'JOIN Assignment ToBook ON ToBook.Childid = Book.id ' .
				'JOIN Entry Library ON Library.id = ToBook.Parentid AND Library.Code = ? ' .
				'JOIN Assignment ToLibrary ON ToLibrary.Childid = Library.id ' .
				'WHERE Book.Publish = 1 AND NOT EXISTS (SELECT 1 FROM Assignment Above WHERE Above.Childid = ToLibrary.Parentid) ' .
				'ORDER BY Book.ListTitleSortKey, Book.Title';
			
			$books = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>['reference'],
			]);
			
			return $this->reference_choices = is_array($books) ? $books : [];
		}
		
		public function ValidateRecordForSaving_EventDate() {
			$validation_results = TRUE;
			$charset = $this->handler->cleanser->utf8_characters->SystemCharSet();
			
					# VALID OPTIONS:
				# Birth Day
				# Death Day
				# Publication / Written / Etc.
			
			$invalid_birthday_options_hash = [
				'Birth'=>TRUE,
				'BirthDay'=>TRUE,
				'BirthDate'=>TRUE,
				'Birth Date'=>TRUE,
			];
			
			$invalid_deathday_options_hash = [
				'Death'=>TRUE,
				'DeathDay'=>TRUE,
				'DeathDate'=>TRUE,
				'Death Date'=>TRUE,
			];
			
			foreach ($this->eventdate as $eventdate) {
				if(mb_strlen($eventdate['Title'], $charset) > 255) {
					$this->errors[] = ['Event Date Titles may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(mb_strlen($eventdate['Description'], $charset) > 255) {
					$this->errors[] = ['Event Date Descriptions may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(!empty($invalid_birthday_options_hash[$eventdate['Title']])) {
					$this->errors[] = ['Event Date Descriptions may not be: "' . $eventdate['Title'] . '".  Did you mean "Birth Day"?'];
					$validation_results = FALSE;
				}
				
				if(!empty($invalid_deathday_options_hash[$eventdate['Title']])) {
					$this->errors[] = ['Event Date Descriptions may not be: "' . $eventdate['Title'] . '".  Did you mean "Death Day"?'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Association() {
			$validation_results = TRUE;
			
			foreach ($this->association as $association) {
				if(strlen($association['Type']) > 255) {
					$this->errors[] = ['Association Types may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
				
				if(strlen($association['SubType']) > 255) {
					$this->errors[] = ['Association SubTypes may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
			}
			
			if(!$this->ValidateRecordForSaving_Association_ConfigValidation()) {
				return FALSE;
			}
			
			return $validation_results;
		}
		
		public function ValidateRecordForSaving_Association_ConfigValidation() {
			$association_entry_codes = $this->handler->globals->AssociationEntryCodes();
			
			$record_parent = $this->record_list[0];
			
			if(array_key_exists($record_parent['Code'], $association_entry_codes)) {
				if(count($association_entry_codes[$record_parent['Code']]) === 0) {
					return TRUE;	# we are not even showing associations on this entry
				}
			}
			
			$thing = $association_entry_codes['*'][0];
			
			$depths = $this->handler->globals->RequiredFieldDepths();
			$depths_count = count($depths);
			if($depths_count !== 0) {
				$association_depths = $depths['Association'];
				if($association_depths) {
					$current_depth = count($this->record_list);
					
					if($this->handler->desired_action === 'Save') {
						$current_depth++;
					}
					$limit = $association_depths[$current_depth];
					
					if($limit !== 0) {
						$chosen_entry_ids = array_column($this->association, 'ChosenEntryid');
						$valid_entry_ids = array_filter($chosen_entry_ids, function($k) {
							if($k !== '') {
								return $k;
							}
							return FALSE;
						});
						
						$valid_entry_ids_count = count($valid_entry_ids);
						
						if($valid_entry_ids_count < $limit) {
							$this->errors[] = ['You must select at least ' . $limit . ' ' . $thing . '.'];
							return FALSE;
						}
					#	print_r($valid_entry_ids);
					}
				}
			}
			
			return TRUE;
		}
		
		public function ValidateRecordForSaving_AvailabilityDates() {
			return TRUE;
		}
		
		public function ValidateRecordForSaving_Assignment() {
			return TRUE;
		}
		
		public function ValidateRecordForSaving_EntryPermission() {
			$userid = $this->authentication_object->user_session['id'];
			
			$useridintval = intval($userid);
			if(!(strlen($userid)) || ($useridintval !== $userid)) {
				$this->errors[] = ['You may only save information if you are logged in, which you may do here: ' . $this->GetLoginURL() . '.'];
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function ValidateRecordForSaving_Definition() {
			$validation_results = TRUE;
			
			foreach ($this->definition as $definition) {
				if(strlen($definition['Term']) > 255) {
					$this->errors[] = ['Definition Terms may not be longer than 255 characters.'];
					$validation_results = FALSE;
				}
			}
			
			return $validation_results;
		}
	}

?>

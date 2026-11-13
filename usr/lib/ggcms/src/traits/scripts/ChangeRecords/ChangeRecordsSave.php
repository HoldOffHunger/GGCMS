<?php

	/*
		Belt stage six: Save.  Each table's records are written, the entry
		first so the rest have its id, and a new entry is given the
		Assignment that is its place in the tree.  Uploaded images are moved
		into the site's image directory here.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsSave {
		public $image;
		public $entry;
		public $errors;
		public $assignment;
		public $entry_unsaved;
		public $admin_errors;
		
		public function SaveRecordFromQueryForAll() {
			$save_results = TRUE;
			
			if(!$this->SaveRecordFromQuery_Entry()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Entry.'];
				return FALSE;
			}
			
			if(!$this->SaveRecordFromQuery_EntryTranslation()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Entry Translation.'];
			}
			
			if(!$this->SaveRecordFromQuery_Description()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Description.'];
			}
			
			if(!$this->SaveRecordFromQuery_Quote()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Quote.'];
			}
			
			if(!$this->SaveRecordFromQuery_TextBody()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Text Body.'];
			}
			
			if(!$this->SaveRecordFromQuery_Image()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Image.'];
			}
			
			if(!$this->SaveRecordFromQuery_ImageTranslation()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Image Translation.'];
			}
			
			if(!$this->SaveRecordFromQuery_Tag()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Tag.'];
			}
			
			if(!$this->SaveRecordFromQuery_Link()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Link.'];
			}
			
			if(!$this->SaveRecordFromQuery_Price()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Price.'];
			}
			
			if(!$this->SaveRecordFromQuery_AcademicReference()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Academic Reference.'];
			}
			
			if(!$this->SaveRecordFromQuery_Publication()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Publication.'];
			}

			if(!$this->SaveRecordFromQuery_WordPiece()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Word Piece.'];
			}

			if(!$this->SaveRecordFromQuery_WordForm()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Word Form.'];
			}
			
			if(!$this->SaveRecordFromQuery_EventDate()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Event Date.'];
			}
			
			if(!$this->SaveRecordFromQuery_AvailabilityDates()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Availability Date Range.'];
			}
			
			if(!$this->SaveRecordFromQuery_Association()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Association.'];
			}
			
			if(!$this->SaveRecordFromQuery_Assignment()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Assignment.'];
			}
			
			if(!$this->SaveRecordFromQuery_EntryPermission()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the User Permission.'];
			}
			
			if(!$this->SaveRecordFromQuery_Definition()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Definition.'];
			}
			
			return $save_results;
		}
		
		public function SaveRecordFromQueryForUpdate() {
			$save_results = TRUE;
			
			if(!$this->SaveRecordFromQuery_Entry()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Entry.'];
				return FALSE;
			}
			
			if(!$this->SaveRecordFromQuery_EntryTranslation()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Entry Translation.'];
			}
			
			if(!$this->SaveRecordFromQuery_Description()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Description.'];
			}
			
			if(!$this->SaveRecordFromQuery_Quote()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Quote.'];
			}
			
			if(!$this->SaveRecordFromQuery_TextBody()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Text Body.'];
			}
			
			if(!$this->SaveRecordFromQuery_Image()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Image.'];
			}
			
			if(!$this->SaveRecordFromQuery_ImageTranslation()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Image Translation.'];
			}
			
			if(!$this->SaveRecordFromQuery_Tag()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Tag.'];
			}
			
			if(!$this->SaveRecordFromQuery_Link()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Link.'];
			}
			
			if(!$this->SaveRecordFromQuery_Price()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Price.'];
			}
			
			if(!$this->SaveRecordFromQuery_AcademicReference()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Academic Reference.'];
			}
			
			if(!$this->SaveRecordFromQuery_Publication()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Publication.'];
			}

			if(!$this->SaveRecordFromQuery_WordPiece()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Word Piece.'];
			}

			if(!$this->SaveRecordFromQuery_WordForm()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Word Form.'];
			}
			
			if(!$this->SaveRecordFromQuery_EventDate()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Event Date.'];
			}
			
			if(!$this->SaveRecordFromQuery_AvailabilityDates()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Availability Date Range.'];
			}
			
			if(!$this->SaveRecordFromQuery_Association()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Association.'];
			}
			
			if(!$this->SaveRecordFromQuery_Definition()) {
				$save_results = FALSE;
				$this->errors[] = ['There was a problem with saving the Association.'];
			}
			
			if($this->entry_unset['id'] !== $this->entry['id']) {
				if(!$this->SaveRecordFromQuery_Assignment()) {
					$save_results = FALSE;
					$this->errors[] = ['There was a problem with saving the Assignment.'];
				}
				
				if(!$this->SaveRecordFromQuery_EntryPermission()) {
					$save_results = FALSE;
					$this->errors[] = ['There was a problem with saving the User Permission.'];
				}
			}
			
			return $save_results;
		}
		
						// Record Saving
						// ---------------------------------------------
		
		public function SaveRecordFromQuery_Entry() {
			$this->entry_unsaved = $this->entry;
			
			$entry = $this->entry;
			
		#	print("BT: SaveRecordFromQuery_Entry().");
		#	print("<PRE>");
		#	print_r($entry);
		#	print("</PRE>");
			
			if($entry['id']) {			// Update Old Entry Record
				$entry_update_args = [
					'type'=>'Entry',
					'update'=>$entry,
					'where'=>[
						'id'=>$entry['id'],
					],
				];
				
				$entry_result = $this->db_access_object->UpdateRecord($entry_update_args);
				if(!empty($entry_result['line'])) {
					$this->admin_errors[] = $entry_result;
					return FALSE;
				}
				$this->entry = $entry_result[0];
			} else {					// Create New Entry Record
				$entry_insert_args = [
					'type'=>'Entry',
					'definition'=>$this->entry,
				];
				
				$entry_result = $this->db_access_object->CreateRecord($entry_insert_args);
				if(!empty($entry_result['line'])) {
					$this->admin_errors[] = $entry_result;
					return FALSE;
				}
				$this->entry = $entry_result;
			}
			
			return $this->entry;
		}
		
		public function SaveRecordFromQuery_EntryTranslation() {
			$save_record_from_query_args = [
				'objectname'=>'entrytranslation',
				'objecttype'=>'EntryTranslation',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Description() {
			$save_record_from_query_args = [
				'objectname'=>'description',
				'objecttype'=>'Description',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Quote() {
			$save_record_from_query_args = [
				'objectname'=>'quote',
				'objecttype'=>'Quote',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_TextBody() {
			$save_record_from_query_args = [
				'objectname'=>'textbody',
				'objecttype'=>'TextBody',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
			
		#	die("BT:!!!!!");
		}
		
		public function startsWith( $haystack, $needle ) {
			$length = strlen($needle);
			return substr( $haystack, 0, $length ) === $needle;
		}
		

		
		#public function PrepareChildRecords() {
			#$image_count = count($this->image);
			#if($this->image && $image_count > 0) {
			#	$prefix = $this->entry['id'] . '-';
			#	for($i = 0; $i < $image_count; $i++) {
			#		$image = $this->image[$i];
			#		}
			#	}
			#}			
		#}
		
		public function SaveRecordFromQuery_Image() {
			if(empty($this->image[0]) || !is_array($this->image[0])) {	# BT: FIXME, wtf is this needed only for imageS?
				return TRUE;
			}
		
		#	print("BT:!<BR><BR>");
		#	print("<PRE>");
		##	print_r($this->image);
		#	print("</PRE>");
		#	die();
			
			$save_record_from_query_args = [
				'objectname'=>'image',
				'objecttype'=>'Image',
			];
			
			$swap_hash = [];
		
		#	print_r($this->image);
			for($i = 0; $i < count($this->image); $i++) {
				if($this->image[$i] && $this->image[$i]['id']) {
					$image = $this->image[$i];
					$swap_hash[$image['id']] = $image['swapped'];
				}
			}
			
			$prefix = $this->entry['id'] . '-';
			
			$save_image_results = $this->SaveRecordFromQuery_Base($save_record_from_query_args);
			
			if(!$save_image_results) {
				return FALSE;
			}
			
			if($save_image_results) {
				$i = 0;
				for($i = 0; $i < count($this->image); $i++) {
					$image = $this->image[$i];
					if(!$image['OriginalCreationDate'] || $this->isUserAdmin()) {
					$file = $this->image_files[$i];
					$original_image = $this->image_unset[$i];
					
					$original_image_location_pieces = str_split($original_image['FileDirectory']);
					$dir_pieces = implode('/', $original_image_location_pieces);
					$original_image_location = $dir_pieces . '/';
					
					$image_folder_location = $this->GetImageFolderDirectory();
					
					if($file['name']) {	// New File Upload
						if(empty($file['tmp_name']) || !empty($file['error'])) {
							$this->errors[] = ['The replacement image upload failed. Please select the file and try again.'];
							return FALSE;
						}
						
						$old_file_location = $image_folder_location . $original_image_location . $original_image['FileName'];
						$old_icon_file_location = $image_folder_location . $original_image_location . $original_image['IconFileName'];
						$old_standard_file_location = $image_folder_location . $original_image_location . $original_image['StandardFileName'];
						
						
						$image_subdirectory_depth = 4;
					
					#	print_r($file);
					#	print(sys_get_temp_dir() . $file['tmp_name']);
						$image_hash = hash_file('sha512', $file['tmp_name']);
						if($image_hash === FALSE) {
							$this->errors[] = ['The replacement image upload could not be read.'];
							return FALSE;
						}
						$base = new Base();
						$base_conversion_args = [
							'startingbase'=>'Hexadecimal',
							'endingbase'=>'Base32',
							'value'=>$image_hash,
						];
						$full_image_hash = $base->ConvertBase($base_conversion_args);
						$file_directory = substr($full_image_hash, 0, $image_subdirectory_depth);
						$new_image_directory = $this->UpdateImagesDirectory(['subdir'=>$file_directory]);
						if(!$new_image_directory) {
							$this->errors[] = ['The image storage directory could not be created.'];
							return FALSE;
						}
						
						$image = $this->prepareImageForSaving(['image'=>$image]);
						$this->image[$i] = $image;
						$file['icon_name'] = $this->updateFileName(['filename'=>$file['icon_name']]);
						$file['standard_name'] = $this->updateFileName(['filename'=>$file['standard_name']]);
						
						$new_file_location = $new_image_directory . $image['FileName'];
						$new_icon_file_location = $new_image_directory . $file['icon_name'];
						$new_standard_file_location = $new_image_directory . $file['standard_name'];
						
						if(!$this->MoveUploadedFile(['from'=>$file['tmp_name'], 'to'=>$new_file_location])) {
							$this->errors[] = ['The replacement image could not be stored. Please try again.'];
							return FALSE;
						}
						
						$resize_args = [
							'filelocation'=>$new_file_location,
							'resizedlocation'=>$new_icon_file_location,
						];
						$icon_results = $this->makeIcon($resize_args);
						if(!$icon_results) {
							$this->errors[] = ['The replacement image could not be resized.'];
							return FALSE;
						}
						
						$resize_args['resizedlocation'] = $new_standard_file_location;
						$standard_results = $this->makeStandardImage($resize_args);
						if(!$standard_results) {
							$this->errors[] = ['The replacement image could not be resized.'];
							return FALSE;
						}
						
						$this->image[$i]['IconFileName'] = $file['icon_name'];
						$this->image[$i]['StandardFileName'] = $file['standard_name'];
						$this->image[$i]['PixelHeight'] = $icon_results['originalheight'];
						$this->image[$i]['PixelWidth'] = $icon_results['originalwidth'];
						$this->image[$i]['IconPixelHeight'] = $icon_results['resizedheight'];
						$this->image[$i]['IconPixelWidth'] = $icon_results['resizedwidth'];
						$this->image[$i]['StandardPixelHeight'] = $standard_results['resizedheight'];
						$this->image[$i]['StandardPixelWidth'] = $standard_results['resizedwidth'];
						$this->image[$i]['FileDirectory'] = $file_directory;
						
						$save_image_results = $this->SaveRecordFromQuery_Base($save_record_from_query_args);
						
						if(!$save_image_results) {
							return FALSE;
						}
						$replacement_paths = [$new_file_location, $new_icon_file_location, $new_standard_file_location];
						$old_paths = array_unique([$old_file_location, $old_icon_file_location, $old_standard_file_location]);
						foreach($old_paths as $old_path) {
							if(!in_array($old_path, $replacement_paths, TRUE) && is_file($old_path)) {
								if(!unlink($old_path)) {
									$this->errors[] = ['The replacement image was saved, but an old image file could not be removed.'];
									return FALSE;
								}
							}
						}
					} else {
						if(strlen($image['FileName']) > 0) {
							$image = $this->prepareImageForSaving(['image'=>$image]);
							$this->image[$i] = $image;
						#	print("CHECK!" . $image['FileName'] . "|" . $original_image['FileName'] . "|<BR><BR>");
							if(!$swap_hash[$image['id']] && $image['FileName'] !== $original_image['FileName'] && ($prefix . $image['FileName']) !== $original_image['FileName']) {		// Rename/Move Old File Upload
						#		print("GO!");
								$image_directory_location = $this->GetImageFolderDirectory();
								
								$original_file_location = $image_directory_location . $original_image_location . $original_image['FileName'];
								$new_file_location = $image_directory_location . $original_image_location . $image['FileName'];
						
						#		print("BT: PREP!!!<BR><BR>");
						#		print("<PRE>");
						#		print_r($image);
						#		print("</PRE>");
								
								if(!rename($original_file_location, $new_file_location)) {
									$this->errors[] = ['There was a problem renaming an image file. Some image files may already have been renamed.'];
									return FALSE;
								}
								
								$alternate_old_filenames = $this->makeAlternateFileNames(['filename'=>$original_image['FileName']]);
								$alternate_new_filenames = $this->makeAlternateFileNames(['filename'=>$image['FileName']]);
								
								$original_file_icon_location = $image_directory_location . $original_image_location . $alternate_old_filenames['icon_name'];
								$new_file_icon_location = $image_directory_location . $original_image_location . $alternate_new_filenames['icon_name'];
								
								if(!rename($original_file_icon_location, $new_file_icon_location)) {
									$this->errors[] = ['There was a problem renaming an image file. Some image files may already have been renamed.'];
									return FALSE;
								}
								
								$original_file_standard_location = $image_directory_location . $original_image_location . $alternate_old_filenames['standard_name'];
								$new_file_standard_location = $image_directory_location . $original_image_location . $alternate_new_filenames['standard_name'];
								
								if(!rename($original_file_standard_location, $new_file_standard_location)) {
									$this->errors[] = ['There was a problem renaming an image file. Some image files may already have been renamed.'];
									return FALSE;
								}
								
								$this->image[$i]['FileName'] = $image['FileName'];
								$this->image[$i]['IconFileName'] = $alternate_new_filenames['icon_name'];
								$this->image[$i]['StandardFileName'] = $alternate_new_filenames['standard_name'];
								
								$save_image_results = $this->SaveRecordFromQuery_Base($save_record_from_query_args);
								
								if(!$save_image_results) {
									return FALSE;
								}
							}
						} else {
							unset($this->image[$i]);
							
							$image_directory_location = $this->GetImageFolderDirectory();
							
							$files = [
								$image_directory_location . $original_image_location . $original_image['FileName'],
								$image_directory_location . $original_image_location . $original_image['StandardFileName'],
								$image_directory_location . $original_image_location . $original_image['IconFileName'],
							];
							
							$this->DeleteFiles(['files'=>$files]);
						}
					}
					}
				}
			}
			
			
			return $save_image_results;
		}

			/*
				move_uploaded_file() refuses any file PHP did not receive in this
				request's POST, which is the point of it -- and also why
				modify_entry.php could not attach an image at all.  The shell
				stages its files itself, and CLIHandler vouches for exactly those
				paths and no others.  Every web request still goes through
				move_uploaded_file(), but for the images importer.php took out of
				an uploaded zip, which it vouched for by path with
				Handler::VouchForUpload().

				Kept just above DeleteFiles(), inside the stretch of this file the
				image-upload-move regression lifts out and runs.
			*/

		public function MoveUploadedFile($args) {
			$from = $args['from'];
			$to = $args['to'];

			if(isset($this->handler) && $this->handler->IsCLIUpload(['file'=>$from])) {
					/*
						tempnam() made the staged copy 0600, and rename() keeps that,
						so the original was readable by nobody but the shell's user
						and the web server answered it with the image 404.  The mode
						move_uploaded_file() gives is set here too.
					*/

				if(!rename($from, $to)) {
					return FALSE;
				}

				return chmod($to, 0666 & ~umask());
			}

			return move_uploaded_file($from, $to);
		}

		public function DeleteFiles($args) {
			$files = $args['files'];
			
			foreach($files as $file) {
				unlink($file);
			}
			
			return TRUE;
		}
		
		public function SaveRecordFromQuery_ImageTranslation() {
			$save_record_from_query_args = [
				'objectname'=>'imagetranslation',
				'objecttype'=>'ImageTranslation',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Tag() {
			$save_record_from_query_args = [
				'objectname'=>'tag',
				'objecttype'=>'Tag',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Link() {
			$save_record_from_query_args = [
				'objectname'=>'link',
				'objecttype'=>'Link',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Price() {
			$save_record_from_query_args = [
				'objectname'=>'price',
				'objecttype'=>'Price',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_AcademicReference() {
			$save_record_from_query_args = [
				'objectname'=>'academicreference',
				'objecttype'=>'AcademicReference',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Publication() {
			$save_record_from_query_args = [
				'objectname'=>'publication',
				'objecttype'=>'Publication',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_WordPiece() {
			$save_record_from_query_args = [
				'objectname'=>'wordpiece',
				'objecttype'=>'WordPiece',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_WordForm() {
			$save_record_from_query_args = [
				'objectname'=>'wordform',
				'objecttype'=>'WordForm',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_EventDate() {
			$save_record_from_query_args = [
				'objectname'=>'eventdate',
				'objecttype'=>'EventDate',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_AvailabilityDates() {
			$save_record_from_query_args = [
				'objectname'=>'availabilitydaterange',
				'objecttype'=>'AvailabilityDateRange',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Association() {
			$save_record_from_query_args = [
				'objectname'=>'association',
				'objecttype'=>'Association',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Assignment() {
			if($this->parent['id']) {		// Standard Entry Record
				$this->assignment['Childid'] = $this->entry['id'];
			} else {				// Master Entry Record
				$this->assignment['Parentid'] = $this->entry['id'];
			}
			
			$save_record_from_query_args = [
				'objectname'=>'assignment',
				'objecttype'=>'Assignment',
				'noentryid'=>1,
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_EntryPermission() {
			$save_record_from_query_args = [
				'objectname'=>'entrypermission',
				'objecttype'=>'EntryPermission',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
		
		public function SaveRecordFromQuery_Definition() {
			$save_record_from_query_args = [
				'objectname'=>'definition',
				'objecttype'=>'Definition',
			];
			
			return $this->SaveRecordFromQuery_Base($save_record_from_query_args);
		}
	}

?>

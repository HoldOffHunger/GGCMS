<?php

	/*
		Belt stage eight, for an update or a delete: Remove.  Records the
		request no longer carries are deleted, with their files.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsRemove {
		
		public function DeleteChildRecordsForUpdate() {
			$delete_child_record_results = TRUE;
			
			$child_record_types = $this->GetStandardChildRecordTypes();
			if($this->Param('Delete')) {
				$child_record_types['EntryCodeReservation'] = 'entrycodereservation';
			}
			
			foreach($child_record_types as $child_record_index => $child_record_type) {
					/*
						A type the site has switched off is never read into the
						form (ORM::GetChildRecords skips it), so it comes back
						with nothing to keep -- and the branch below would then
						delete every row of it the entry has.  It is skipped here
						as it is there.  Found on 8 November 2026, when Price,
						switched on for AutonomyBooks alone, made every Update on
						RevoltLib a 500: RevoltLib has no Price table to delete
						from.  A site that has the table would instead have lost
						the rows, silently.
					*/

				$enabled_method = $child_record_index . '_enabled';

				if(method_exists($this->handler->abstractglobals->child_types, $enabled_method) && !$this->handler->abstractglobals->child_types->$enabled_method()) {
					continue;
				}

					/*
						The same hole from the other side: a type switched on
						but left off the site's form.  It is read, so it has
						rows, but no input posts them back, so it too comes back
						with nothing to keep, and every row went.  Found on 10
						November 2026, when citing a word on EarthFluent's int
						copy deleted the word's translation: EarthFluent turns
						EntryTranslation on and its form never shows it.  An
						Update leaves alone what its form did not show; deleting
						the entry still takes everything.
					*/

				if(!$this->Param('Delete') && !$this->DeleteChildRecordsForUpdate_OnForm(['type'=>$child_record_index])) {
					continue;
				}

				$unformatted_saved_records = $this->$child_record_type;

				$record_ids_to_keep = [];
				
				if(is_array($unformatted_saved_records) && count($unformatted_saved_records) && !$this->delete_in_progress) {
				#	if($child_record_type === 'image') {
				#	print("DEL-A");
				#	}
					foreach($unformatted_saved_records as $unformatted_saved_record) {
						if($unformatted_saved_record && $unformatted_saved_record['id']) {
							$record_ids_to_keep[] = $unformatted_saved_record['id'];
						}
					}
					
					$delete_record_tree_args = [
						'parent'=>$this->entry,
						'recordtype'=>$child_record_index,
						'recordidstokeep'=>$record_ids_to_keep,
					];
					
				#	if($child_record_type === 'image') {
				#	print("<PRE>");
					#print_r($record_ids_to_keep);
				#	print("\n\n_______\n\n");
					#print_r($records_to_delete);
				#	#print_r($this->entry);
				#	print("\n\n_______\n\n");
				#	print_r($this->entry_unset['image']);
				#	print("</PRE>");
				#	}
					
					if(!$this->orm->DeleteChildRecords($delete_record_tree_args)) {
						return FALSE;
					}
				} else {
					$delete_record_tree_args = [
						'parent'=>$this->entry,
						'recordtype'=>$child_record_index,
						'recordidstokeep'=>$record_ids_to_keep,
					];
					
					if(!$this->orm->DeleteChildRecords($delete_record_tree_args)) {
						return FALSE;
					}
					
					$records_to_delete = $unformatted_saved_records;
				}
				
				if($child_record_type === 'image' && $this->UpdatesInPlace()) {		# a reader's copy shares the original's files
					#print("BT: DELETE CHILD IMAGES!");
					$image_directory_location = $this->GetImageFolderDirectory();
					
					$preserved_record_id_hash = [];
					$records_to_delete = [];
					
					foreach($record_ids_to_keep as $record_id_to_keep) {
						$preserved_record_id_hash[$record_id_to_keep] = TRUE;
					}
					
					foreach($this->entry_unset['image'] ?? [] as $image) {
						if(empty($preserved_record_id_hash[$image['id']])) {
							$records_to_delete[] = $image;
						}
					}
					
				#	print("<PRE>");
				#	print_r($records_to_remove);
				#	print("</PRE>");
					
				#	print_r($records_to_delete);
					#print("<PRE>");
					for($i = 0; $i < count($records_to_delete); $i++) {
						$record_to_delete = $records_to_delete[$i];
						
					#	print("BT: DELETE!");
					#	print_r($record_to_delete);
						
						if($record_to_delete && $record_to_delete['FileName']) {
							$original_image_location_pieces = str_split($record_to_delete['FileDirectory']);
							$dir_pieces = implode('/', $original_image_location_pieces);
							
								/*
									No '../'.  GetImageFolderDirectory() returns an absolute
									path built from GGCMS_DATA_DIR, so prefixing it produced
									..//srv/ggcms/... -- which resolves to nothing from
									/var/www/html, and /var/www/html is where Apache runs.
									unlink() on a path that does not exist just fails, so
									every image deleted through the web since the move to
									DigitalOcean lost its database row and kept its file.
									masereelgroup.com was holding 282 MB across 200 files
									that the records had long stopped pointing at.

									The prefix is a DreamHost inheritance.  That host had no
									/var or /usr to speak of and everything hung off a single
									directory, where hopping up one level was correct.

									The same three paths are built correctly further down
									this file, where an image is replaced rather than removed
									-- absolute, and guarded with is_file().  Matched here.
								*/

							$image_file_location = $image_directory_location . $dir_pieces . '/' . $record_to_delete['FileName'];
							$icon_file_location = $image_directory_location . $dir_pieces . '/' . $record_to_delete['IconFileName'];
							$standard_file_location = $image_directory_location . $dir_pieces . '/' . $record_to_delete['StandardFileName'];
							
							if(is_file($icon_file_location)) {
								unlink($icon_file_location);
							}

							if(is_file($image_file_location)) {
								unlink($image_file_location);
							}

							if(is_file($standard_file_location)) {
								unlink($standard_file_location);
							}
						}
					}
					#print("</PRE>");
				}
			}
			
			return $delete_child_record_results;
		}

			/*
				Whether the site's form shows a record type, by its name in
				ShowModifiableFields() -- the table's own name, but for the
				availability range, which the form shows as its start.  A
				type the list does not name at all is taken as shown, as it
				always was, so nothing new is spared that was not before.
			*/

		public function DeleteChildRecordsForUpdate_OnForm($args) {
			$type = $args['type'];
			$modifiable_fields = $this->handler->globals->ShowModifiableFields();
			$field = $type === 'AvailabilityDateRange' ? 'AvailabilityStart' : $type;

			if(!array_key_exists($field, (array)$modifiable_fields)) {
				return TRUE;
			}

			return (bool)$modifiable_fields[$field];
		}
	}

?>

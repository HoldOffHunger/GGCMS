<?php

	/*
		Belt stage five, for an update only: Backup.  Before anything is
		written, every changed record's old value is filed in RecordChange,
		with who changed it, which entry, which table and which field.  This
		is the history a site has; anything that writes around this stage
		leaves none.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsBackup {
		
			/*
				Whether this update changes the entry itself.  An administrator's
				does.  A reader's does not: SetRecordFromQuery_Entry() gives it
				no id, and it is filed as a new unpublished copy pointing back
				at the original through OriginalEntryid, for an administrator to
				accept.  The original is not touched -- so there is nothing of
				it to back up, no path of it to reserve, and none of its files
				to remove.
			*/
		
		public function UpdatesInPlace() {
			return !empty($this->entry['id']) && !empty($this->entry_unset['id']) && (string)$this->entry['id'] === (string)$this->entry_unset['id'];
		}
		
		public function BackupOldRecord() {
			if(!$this->UpdatesInPlace()) {
				return TRUE;		# a reader's copy leaves the original as it was
			}
			
			$entry = $this->entry;
			$backup_record = $this->entry_unset;
			
			if($entry['Code'] !== $backup_record['Code']) {
				$this->BackupEntryCodeReservation(['entry'=>$entry, 'old_entry'=>$backup_record, 'record_list'=>$this->record_list]);
			}
			
			$child_record_types = $this->GetRecordTypes();
			$primary_record_fields = $this->GetRecordsPrimaryFields();
			
			foreach($child_record_types as $formal_type => $child_record_type) {
				$records = $this->entry_unset[$child_record_type];
				if($child_record_type === 'entry') {
					$records = [$this->entry_unset];
				}
				$fields = $primary_record_fields[$formal_type];
				
				$changed_records = $this->DetectChangedRecords([
					'recordtype'=>$child_record_type,
					'records'=>$records,
					'fields'=>$fields,
				]);
				
				$changed_records_count = count($changed_records);
				if($changed_records_count > 0) {
					for($i = 0; $i < $changed_records_count; $i++) {
						$changed_record = $changed_records[$i];
						if($changed_record && count($changed_record)) {
							$conflicting_field = $changed_record['conflictingfield'];
							
							if(!$conflicting_field) {
								$conflicting_field = '';
							}
							$backup_insert_args = [
								'type'=>'RecordChange',
								'definition'=>[
									'Entryid'=>$entry['id'],
									'Userid'=>$this->authentication_object->user_session['id'],
									'RecordField'=>$conflicting_field,
									'Recordid'=>$changed_record['id'],
									'RecordType'=>$formal_type,
									'OldValue'=>$changed_record[$conflicting_field],
								],
							];
							
							$backup = $this->db_access_object->CreateRecord($backup_insert_args);
						}
					}
				}
			}
			
			return TRUE;
		}
		
		public function DetectChangedRecords($args) {
			$record_type = $args['recordtype'];
			$old_records = $args['records'];
			$fields = $args['fields'];
			
			if($old_records) {
				$record_count = count($old_records);
			} else {
				$record_count = 0;
			}
			
			$record_type_lc = strtolower($record_type);
			$new_records = $this->$record_type_lc;
			
			if($new_records['id']) {
				$new_records = [$new_records];
			}
			
			$new_record_count = count($new_records);
			
			if($record_count === 0 && $new_record_count === 0) {
				return [];
			}
			
			if($fields) {
				$fields_count = count($fields);
			} else {
				$fields_count = 0;
			}
			
			$old_record_data = [];
			$new_record_data = [];
			
			foreach($old_records as $old_record) {
				$old_record_data[$old_record['id']] = $old_record;
			}
			
			foreach($new_records as $new_record) {
				if($new_record && $new_record['id']) {
					$new_record_data[$new_record['id']] = $new_record;
				}
			}
			
			$conflicts = [];
			
			foreach($old_record_data as $old_record_id => $old_record) {
				$comparable_record = $new_record_data[$old_record_id];
				
				if($old_record['id'] >= 1) {
					if(!$comparable_record) {
						$conflicts[$old_record['id']] = $old_record;
					} else {
						for($i = 0; $i < $fields_count; $i++) {
							$field = $fields[$i];
							
							if($old_record[$field] != $comparable_record[$field]) {
								$old_record['conflictingfield'] = $field;
								$conflicts[] = $old_record;
							}
						}
					}
				}
			}
			
			return $conflicts;
		}
		
		public function GetRecordsPrimaryFields() {
			return [
				'Entry'=>['Title'],
				'EntryTranslation'=>['Title'],
				'Description'=>['Description'],
				'Quote'=>['Quote'],
				'TextBody'=>['Text'],
				'Image'=>['Title'],
				'ImageTranslation'=>['Title'],
				'Tag'=>['Tag'],
				'Link'=>['URL'],
				'Price'=>['PriceValue'],
				'AcademicReference'=>['ReferenceEntryid'],
				'Publication'=>['Publisher'],
				'EventDate'=>['Title'],
				'Association'=>['ChosenEntryid'],	# was Childid, an Assignment column; a re-pointed association went unrecorded
		#		'AvailabilityDateRange'=>'',
				'Definition'=>['Term'],
			];
		}
	}

?>

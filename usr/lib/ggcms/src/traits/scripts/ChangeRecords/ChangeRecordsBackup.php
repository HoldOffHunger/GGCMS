<?php

	/*
		Belt stage five, for an update or a delete: Backup.  Before anything
		is written, every changed record's old value is filed in RecordChange,
		with who changed it, which entry, which table and which field.  This
		is the history a site has; anything that writes around this stage
		leaves none.

		A record that is deleted is filed whole: one row whose RecordField is
		*, holding the record as JSON, every column it had -- enough to put it
		back.  Deleting an entry files the entry, every child record, its
		assignment and the associations other entries made to it, before any
		of them goes.  If a backup cannot be written, nothing is: the verb
		stops, and a record is never removed without its copy.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsBackup {
		public $errors;
		
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
				
					/*
						A changed record names the field that changed; a deleted
						one names none, and is filed whole.  Until 11 November
						2026 a deleted record was found and never filed: this
						loop counted 0 to n-1, and DetectChangedRecords() kept a
						deletion under its id, so neither it nor any change
						after it was ever reached -- and had it been, its
						OldValue would have been NULL in a NOT NULL column.
					*/

				foreach($changed_records as $changed_record) {
					if(!$changed_record || !count($changed_record)) {
						continue;
					}

					if(empty($changed_record['conflictingfield'])) {
						unset($changed_record['conflictingfield']);

						$backed_up = $this->BackupWholeRecord([
							'entryid'=>$entry['id'],
							'type'=>$formal_type,
							'record'=>$changed_record,
						]);
					} else {
						$conflicting_field = $changed_record['conflictingfield'];

						$backed_up = $this->BackupRecordChange([
							'entryid'=>$entry['id'],
							'field'=>$conflicting_field,
							'recordid'=>$changed_record['id'],
							'type'=>$formal_type,
							'oldvalue'=>(string)$changed_record[$conflicting_field],
						]);
					}

					if(!$backed_up) {
						return FALSE;
					}
				}
			}

			return TRUE;
		}

			//  One RecordChange row.  FALSE if it could not be written.

		public function BackupRecordChange($args) {
			$backup_insert_args = [
				'type'=>'RecordChange',
				'definition'=>[
					'Entryid'=>$args['entryid'],
					'Userid'=>$this->authentication_object->user_session['id'],
					'RecordField'=>$args['field'],
					'Recordid'=>$args['recordid'],
					'RecordType'=>$args['type'],
					'OldValue'=>$args['oldvalue'],
				],
			];

			$backup = $this->db_access_object->CreateRecord($backup_insert_args);

			if(!is_array($backup) || !empty($backup['line'])) {
				$this->errors[] = ['The old ' . $args['type'] . ' could not be backed up, so nothing was changed.'];

				return FALSE;
			}

			return TRUE;
		}

			/*
				A deleted record, whole: RecordField *, and every column it had
				as JSON.  Only the record's own values go -- a child list the
				ORM hung on it is filed as records of its own, or not at all.
			*/

		public function BackupWholeRecord($args) {
			$record = [];

			foreach((array)$args['record'] as $field => $value) {
				if($value !== NULL && !is_array($value) && !is_object($value)) {		# not the child lists the ORM hangs on an entry, empty or not
					$record[$field] = $value;
				}
			}

			return $this->BackupRecordChange([
				'entryid'=>$args['entryid'],
				'field'=>'*',
				'recordid'=>isset($record['id']) ? (int)$record['id'] : 0,
				'type'=>$args['type'],
				'oldvalue'=>json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
			]);
		}

			/*
				Everything a delete is about to remove, filed whole first: the
				entry, each of its child records, the Assignment that placed it
				(as Parentid and Childid -- its own id is not read here), and
				the associations other entries made to it, which
				DeleteChosenAssociations() removes with it.
			*/

		public function BackupDeletedEntry() {
			$entryid = $this->entry['id'];

			if(!$this->BackupWholeRecord(['entryid'=>$entryid, 'type'=>'Entry', 'record'=>$this->entry])) {
				return FALSE;
			}

			foreach($this->GetRecordTypes() as $formal_type => $variable_name) {
				if($variable_name === 'entry' || !is_array($this->$variable_name)) {
					continue;
				}

				foreach($this->$variable_name as $record) {
					if(empty($record['id'])) {
						continue;
					}

					if(!$this->BackupWholeRecord(['entryid'=>$entryid, 'type'=>$formal_type, 'record'=>$record])) {
						return FALSE;
					}
				}
			}

			$parentid = ($this->parent && isset($this->parent['id'])) ? $this->parent['id'] : 0;

			if(!$this->BackupWholeRecord(['entryid'=>$entryid, 'type'=>'Assignment', 'record'=>['Parentid'=>$parentid, 'Childid'=>$entryid]])) {
				return FALSE;
			}

			$chosen_associations = $this->orm->GetChosenAssociations(['entry'=>$this->entry]);

			if(!is_array($chosen_associations) || !empty($chosen_associations['line'])) {
				$this->errors[] = ['The associations other entries made to this one could not be read, so nothing was deleted.'];

				return FALSE;
			}

			foreach($chosen_associations as $association) {
				if(!$this->BackupWholeRecord(['entryid'=>$entryid, 'type'=>'Association', 'record'=>$association])) {
					return FALSE;
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
						/*
							Deleted: no conflictingfield, so it is filed whole.  A
							child record removed from the form usually comes back
							not missing but blank -- RealignEntryIDsForRecordUpdate()
							hands ids out by position, so the empty row left in its
							place wears its id -- and Remove then deletes it.  That is
							a deletion too, not a field emptied.
						*/

					if(!$comparable_record || ($record_type_lc !== 'entry' && $fields_count && $this->RecordIsBlank(['record'=>$comparable_record, 'fields'=>$fields]))) {
						$conflicts[] = $old_record;
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

			//  Every one of a record's primary fields empty: the row a removed record leaves behind.

		public function RecordIsBlank($args) {
			foreach($args['fields'] as $field) {
				if(isset($args['record'][$field]) && strlen(trim((string)$args['record'][$field]))) {
					return FALSE;
				}
			}

			return TRUE;
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

<?php

	/*
		The three verbs -- save, update, delete -- each of which runs the
		belt from end to end for one entry.  They decide and write; they do
		not speak.  modify.php wraps each in the HTML it reports with, and
		importer.php in a line of its import report.

		Set entry_modification_stop_before_save and the first two stop when
		Prepare is done: every check made, nothing written.  That is an
		import's preview.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsVerbs {
		public $entryid;
		public $saveaccepted;
		public $savepreparedresults;
		public $saveattemptresults;
		public $save_status;
		public $delete_in_progress;
		public $saveattempted;
		public $parent;
		public $entry_unset;
		public $entry_modification_stopped;
						// The Three Verbs
						// ---------------------------------------------

			/*
				A new entry, filed beneath the entry the request names.  FALSE
				only where modify.php answers 404; every other outcome is TRUE,
				with saveaccepted, savepreparedresults and saveattemptresults
				saying how far it got and save_status saying why it stopped.
			*/

		public function SaveEntry() {
			set_time_limit(240);
			$this->save_status = 'Save attempted.';

			$this->saveattempted = TRUE;

			if(!$this->orm) {
				$this->SetOrmBasics();
			}

			if(!$this->ValidateOrm()) {
				return FALSE;	# Trigger 404
			}

			$this->SetAssociationRecords();

			if($this->entry) {
				$this->parent = $this->entry;
				$this->entry_unset = $this->entry;
				unset($this->entry);
			}

			$this->SetRecordFromQueryForAll();
			$this->CleanseRecordFromQueryForAll();
			$this->SetAssociationRecords();
			$this->SetSelectableLanguages();
			if($this->ValidateForSavingForAll()) {
				$this->saveaccepted = TRUE;
				if($this->PrepareRecordForSavingForAll()) {
					$this->savepreparedresults = TRUE;

					if($this->entry_modification_stop_before_save) {
						$this->entry_modification_stopped = TRUE;
						$this->save_status = 'Save checked, and not attempted.';

						return TRUE;
					}

					if($this->SaveRecordFromQueryForAll()) {
						$this->FormatSavedRecordFromQueryForAll();

						$this->saveattemptresults = TRUE;
						$this->save_status = 'Save successful!';
					} else {
						$this->saveattemptresults = FALSE;
						$this->save_status = 'Save attempted, but failed.';
					}
				} else {
					$this->savepreparedresults = FALSE;
					$this->save_status = 'Save was considered valid, but failed while preparing to save.';
				}
			} else {
				$this->saveaccepted = FALSE;
				$this->save_status = 'Save not attempted, because the input was invalid.';
			}

			return TRUE;
		}

			/*
				The entry the request names, changed to what the request says.
				The request is the whole truth: a child record it does not
				carry is a child record deleted, which is right for a browser,
				whose form always carries everything.  BackupOldRecord() files
				every changed value in RecordChange before anything is written.

				An entry with no parent and no master record is a save, as it
				always was; saveattempted tells the caller it went that way.
			*/

		public function UpdateEntry() {
			$this->SetOrmBasics();

			if(!$this->ValidateOrm()) {
				return FALSE;	# Causes 404
			}

			$this->FormatEntryInformation();

			if(!$this->canUserAccess()) {
				return FALSE;
			}

			if(!$this->parent['id'] && (!$this->master_record || !$this->master_record['id'])) {
				return $this->SaveEntry();
			}

			$this->entryid = $this->entry['id'];

			$this->SetEntryFromCode();
			$this->SetChildRecords([]);
			$this->OrderAndFillChildRecords();
			$this->SetAssociationRecords();

			$this->SetRecordFromQueryForAll();
			$this->CleanseRecordFromQueryForAll();
			$this->RealignEntryIDsForRecordUpdate();
			$this->SetSelectableLanguages();

			if($this->ValidateForSavingForAll()) {
				$this->saveaccepted = TRUE;
				if($this->PrepareRecordForSavingForAll()) {
					$this->savepreparedresults = TRUE;

					if($this->entry_modification_stop_before_save) {
						$this->entry_modification_stopped = TRUE;
						$this->save_status = 'Update checked, and not attempted.';

						return TRUE;
					}

					if($this->BackupOldRecord()) {
						if($this->SaveRecordFromQueryForUpdate()) {
							$this->ReserveOldEntryCode();		# the entry is under its new code now, so the old one can be reserved
							$this->FormatSavedRecordFromQueryForAll();
							if($this->DeleteChildRecordsForUpdate()) {
								$this->saveattemptresults = TRUE;
								$this->save_status = 'Update successful!';
							} else {
								$this->saveattemptresults = FALSE;
								$this->save_status = 'Update of old record(s) attempted, but failed.';
							}
						} else {
							$this->saveattemptresults = FALSE;
							$this->save_status = 'Update attempted, but failed.';
						}
					} else {
						$this->saveattemptresults = FALSE;
						$this->save_status = 'Update attempted, but failed while backing up the old record.';
					}
				} else {
					$this->savepreparedresults = FALSE;
					$this->save_status = 'Update was considered valid, but failed while preparing to save.';
				}
			} else {
				$this->saveaccepted = FALSE;
				$this->save_status = 'Update not attempted, because the input was invalid.';
			}

			return TRUE;
		}

			/*
				The entry the request names, its child records, its assignment,
				and the associations other entries made to it -- every one of
				them filed in RecordChange first, by BackupDeletedEntry().
				canUserDelete() decides who may, here as on the web.
			*/

		public function DeleteEntryWithChildRecords() {
			$this->SetOrmBasics();

			if(!$this->ValidateOrm()) {
				return FALSE;	# Causes 404
			}

			if(!$this->canUserDelete()) {
				return FALSE;	# 404, as a refusal to see it is
			}

			$this->delete_in_progress = 1;

			$this->OrderAndFillChildRecords();

			if(!$this->BackupDeletedEntry()) {
				$this->saveattemptresults = FALSE;
				$this->save_status = 'Delete not attempted: the entry could not be backed up first, so nothing was deleted.';

				return TRUE;
			}

			$this->save_status = 'Delete attempted.';

			if($this->DeleteChildRecordsForUpdate()) {
				$delete_entry_results = $this->DeleteEntry();
				if(!empty($delete_entry_results['line'])) {
					$this->saveattemptresults = FALSE;
					$this->save_status = 'Delete failed while deleting the entry. Some child records may already have been deleted.';
					return TRUE;
				}

				$delete_assignment_results = $this->DeleteAssignment();
				if(!empty($delete_assignment_results['line'])) {
					$this->saveattemptresults = FALSE;
					$this->save_status = 'Delete incomplete: the entry was deleted, but its assignment could not be deleted.';
					return TRUE;
				}

				$delete_chosen_associations_results = $this->DeleteChosenAssociations();
				if(!empty($delete_chosen_associations_results['line'])) {
					$this->saveattemptresults = FALSE;
					$this->save_status = 'Delete incomplete: the entry was deleted, but associations other entries made to it could not be deleted.';
					return TRUE;
				}

				$this->save_status = 'Delete successful.  The information deleted is available below as a confirmation of what was deleted.';
				$this->saveattemptresults = TRUE;
			} else {
				$this->saveattemptresults = FALSE;
				$this->save_status = 'Delete failed while deleting child records. Some records may already have been deleted.';
			}

			return TRUE;
		}
	}

?>

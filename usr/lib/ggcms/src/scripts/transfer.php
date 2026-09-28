<?php

	ggreq('scripts/view.php');
	
	class transfer extends view {
		public $admin_errors;
		public $selections;
		public $search_term;
		public $new_parent_results;
		public $entry_update_args;
		public $entry_update;
		public $target_parent;
		public $conflicting_entries;
		public $conflicting_entry_count;
		
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return TRUE;
		}
			
			/*
				Moving an entry rewrites the assignment that places it, so
				everything beneath it moves too.  This was only RequiresLogin(),
				and AdminOnly() is FALSE by default: any Google sign-in could
				move any entry anywhere.  It never quite could, because the
				move died on the reservation backup first; that is fixed below,
				so this had to come first.
			*/
		
		public function AdminOnly() {
			return TRUE;
		}
		
		public function search() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->SetEntryChildRecordStats([]);
			
			$this->SetSearchTerm();
			
			if($this->search_term) {
				$orm_match_args = [
					'fieldname'=>'Code',
					'fieldvalue'=>$this->search_term,
					'matchlike'=>FALSE,
				];
				
				$record_results = $this->SearchForEntries($orm_match_args);
				
				if($record_results['error']) {
					$this->admin_errors[] = $record_results;
				} else {
					$this->selections = $record_results;
				}
			}
			
			return TRUE;
		}
		
		public function SetSearchTerm() {
			$this->search_term = $this->Param('search');
		}
		
		public function transferentry() {
			$this->SetOrmBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->SetTargetParent();
			
			if($this->target_parent) {
				$this->SetConflictingCodeEntries();
				
				if(!$this->conflicting_entry_count) {
					$orm_match_args = [
						'fieldname'=>'id',
						'fieldvalue'=>$this->target_parent,
						'matchlike'=>FALSE,
					];
						
						/*
							The result went to new_parent_results and the test read
							$record_results, which was never set: a parent that did
							not exist, or a failed search, went unnoticed.
						*/
					
					$record_results = $this->SearchForEntries($orm_match_args);
					
					if(!empty($record_results['error']) || empty($record_results[0]['id'])) {
						$this->admin_errors[] = ['There is no entry ' . $this->target_parent . ' to move this under.'];
						
						return TRUE;
					}
					
					$this->new_parent_results = $record_results[0];
					$this->selections = $record_results;
					
					if($this->TargetIsWithinEntry()) {
						$this->admin_errors[] = ['An entry cannot be moved under itself or anything beneath it.'];
						
						return TRUE;
					}
					
					$assignment = $this->entry['assignment'][0];
					$assignment['Parentid'] = $this->target_parent;
					
					$entry_update_args = [
						'type'=>'Assignment',
						'update'=>$assignment,
						'where'=>[
							'id'=>$assignment['id'],
						],
					];
					
					$this->entry_update_args = $entry_update_args;
					
						/*
							The old path is reserved so its links still find the
							entry.  $entry and $backup_record were never set here,
							so the reservation went in with no Entryid, MySQL
							refused it, and every transfer died before moving
							anything.  A move keeps the entry's code; only its path
							changes, so it is the old entry and the new.
						*/
					
					$this->BackupEntryCodeReservation(['entry'=>$this->entry, 'old_entry'=>$this->entry, 'record_list'=>$this->record_list]);
					
					$update_results = $this->handler->db_access->UpdateRecord($entry_update_args);
					
					if(!empty($update_results['line'])) {
						$this->admin_errors[] = ['The entry could not be moved.'];
						
						return TRUE;
					}
					
					return $this->entry_update = $update_results[0];
				}
			}
			
			return TRUE;
		}
			
			/*
				Is the target the entry itself, or somewhere beneath it?  Then
				the move would hang the branch from its own descendant, a loop
				with no way back to the root, and the whole branch would vanish
				from the site.  Walks up from the target; fifty steps is far
				deeper than any site.
			*/
		
		public function TargetIsWithinEntry() {
			$current = $this->target_parent;
			
			for($steps = 0; $current && $steps < 50; $steps++) {
				if($current === (int)$this->entry['id']) {
					return TRUE;
				}
				
				$assignment = $this->handler->db_access->GetRecords([
					'type'=>'Assignment',
					'definition'=>[
						'Childid'=>$current,
					],
					'limit'=>1,
				]);
				
				$current = (int)($assignment[0]['Parentid'] ?? 0);
			}
			
			return FALSE;
		}
		
		public function SetTargetParent() {
			$this->target_parent = (int) $this->Param('target-parent');
			
			return TRUE;
		}
		
		public function SetConflictingCodeEntries() {
			$target_parent_get_args = [
				'type'=>'Entry',
				'definition'=>[
					'Code'=>$this->entry['Code'],
				],
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Childid = Entry.id AND Assignment.Parentid = ' . $this->target_parent,
					],
				],
			];
			
			$this->conflicting_entries = $this->handler->db_access->GetRecords($target_parent_get_args);
			$this->conflicting_entry_count = count($this->conflicting_entries);
			
			return TRUE;
		}
	}

?>
<?php

	ggreqonce('scripts/view.php');
	
	class transfer extends view {
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
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
					
					$this->new_parent_results = $this->SearchForEntries($orm_match_args)[0];
					
					if($record_results['error']) {
						$this->admin_errors[] = $record_results;
					} else {
						$this->selections = $record_results;
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
					
					$this->BackupEntryCodeReservation(['entry'=>$entry, 'old_entry'=>$backup_record, 'record_list'=>$this->record_list]);
					
					return $this->entry_update = $this->handler->db_access->UpdateRecord($entry_update_args)[0];
				}
			}
			
			return TRUE;
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
<?php

	ggreqonce('scripts/view.php');

	class userpanel extends view {
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return TRUE;
		}
		
						// View Functionality
						// ---------------------------------------------
		
		public function display() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->setEntrySubmissionCounts();
			$this->setUnpublishedEntrySubmissions();
			$this->setPrimaryChildren();
			
			return TRUE;	# not done; FIXME
		}
		
		public function setUnpublishedEntrySubmissions() {
			if($this->submission_counts['Unpublished_Count'] === 0) {
				return $this->unpublished_submissions = [];
			}
			
			$sql = '';
			
			$sql .= 'SELECT Entry.*, Assignment.id AS PermaLinkid ';
			$sql .= 'FROM Entry ';
			$sql .= 'JOIN EntryPermission ON EntryPermission.Entryid = Entry.id AND EntryPermission.Userid = ? ';
			$sql .= 'JOIN Assignment ON Assignment.Childid = Entry.id ';
			$sql .= 'WHERE Entry.Publish = 0;';
			
			$user_id = $this->handler->authentication->user_account['id'];
			
			$sql_args = [
				$user_id,
			];
			
			$unpublished_submissions = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$sql_args,
			]);
			
			
		#	print("<PRE>");
		#	print_r($unpublished_submissions);
		#	print("</PRE>");
			
			return $this->unpublished_submissions = $unpublished_submissions;
		}
		
		public function setEntrySubmissionCounts() {
			$sql = '';
			$sql .= '(SELECT ';
			$sql .= 'COUNT(DISTINCT EntryPublished.id) as Published_Count,  ';
			$sql .= '"-" AS Unpublished_Count ';
			$sql .= 'FROM  ';
			$sql .= 'User  ';
			$sql .= 'LEFT JOIN EntryPermission AS EntryPermissionPublished ON EntryPermissionPublished.Userid = User.id '; 
			$sql .= 'LEFT JOIN Entry AS EntryPublished ON EntryPublished.id = EntryPermissionPublished.Entryid AND EntryPublished.Publish = 1  ';
			$sql .= 'WHERE User.id = ? ';
			$sql .= 'GROUP BY User.id ';
			$sql .= ')UNION( ';
			$sql .= 'SELECT  ';
			$sql .= '"-" AS Published_Count, ';
			$sql .= 'COUNT(DISTINCT EntryUnpublished.id) as Unpublished_Count  ';
			$sql .= 'FROM  ';
			$sql .= 'User  ';
			$sql .= 'LEFT JOIN EntryPermission AS EntryPermissionUnpublished ON EntryPermissionUnpublished.Userid = User.id  ';
			$sql .= 'LEFT JOIN Entry AS EntryUnpublished ON EntryUnpublished.id = EntryPermissionUnpublished.Entryid AND EntryUnpublished.Publish = 0  ';
			$sql .= 'WHERE User.id = ? ';
			$sql .= 'GROUP BY User.id) ';
			
			$user_id = $this->handler->authentication->user_account['id'];
			
			$sql_args = [
				$user_id,
				$user_id,
			];
			
			$submission_count_records = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$sql_args,
			]);
			
			$submission_counts = [];
			
			for($i = 0; $i < count($submission_count_records); $i++) {
				$submission_count_record = $submission_count_records[$i];
				
				if($submission_count_record['Published_Count'] !== '-') {
					$submission_counts['Published_Count'] = $submission_count_record['Published_Count'];
				}
				
				if($submission_count_record['Unpublished_Count'] !== '-') {
					$submission_counts['Unpublished_Count'] = $submission_count_record['Unpublished_Count'];
				}
			}
			
			$this->submission_counts = $submission_counts;
			
		#	print("<PRE>");
		#	print_r($this->submission_counts);
		#	print("</PRE>");
			
			return TRUE;
		}
	}

?>
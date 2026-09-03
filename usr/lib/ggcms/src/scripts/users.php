<?php

	ggreqonce('scripts/view.php');

	class users extends view {
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return FALSE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
						// View Functionality
						// ---------------------------------------------
		
		public function display() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->redirect_script = 'login';
			
			return FALSE;	# not done; FIXME
		}
		
						// View Functionality
						// ---------------------------------------------
		
		public function viewuser() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm() || !$this->SetUser()) {
				return FALSE;	# 404
			}
			
			$this->SetUserComments([
				'limit'=>3,
			]);
			$this->SetUserCommentsCount();
			
			$this->SetUserLikesDislikes([
				'limit'=>3,
			]);
			$this->SetUserLikesDislikesCount();
			
			$this->SetTagCounts();
			
			return TRUE;
		}
		
						// Export User Functionality
						// ---------------------------------------------
		
		public function exportuser() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm() || !$this->SetUser()) {
				return FALSE;	# 404
			}
			
			$this->SetUserComments([]);
			$this->SetUserCommentsCount();
			
			$this->SetUserLikesDislikes([]);
			$this->SetUserLikesDislikesCount();
			
			return TRUE;
		}
		
		public function SetUser() {
			$username = trim(urldecode($this->Param('user')));
			if($username) {
				$user_get_args = [
					'type'=>'User',
					'definition'=>[
						'Username'=>$username,
					],
				];
				
				$user = $this->handler->db_access->GetRecords($user_get_args)[0];
				
				if($user && $user['id']) {
					return $this->user = $user;
				}
			}
			
			$userid = ((int)$this->Param('userid'));
			if($userid) {
				$user_get_args = [
					'type'=>'User',
					'definition'=>[
						'id'=>$userid,
					],
				];
				
				$user = $this->handler->db_access->GetRecords($user_get_args)[0];
				
				if($user && $user['id']) {
					$user['Username'] = 'User #' . $user['id'];
					return $this->user = $user;
				}
			}
			
			return FALSE;
		}
		
		public function SetUserComments($args) {
			$sql = 'SELECT Comment.* FROM Comment JOIN Entry ON Entry.id = Comment.Entryid AND Entry.Publish = 1 WHERE Userid = ? AND Approved = 1 AND Rejected = 0 ';
			
			if(isset($args['limit'])) {
				$sql .= 'LIMIT ' . $args['limit'];
			}
			
			$comments = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>[$this->user['id']],
			]);
			
			if(isset($args['limit'])) {
				$this->comments = $this->SetRecordEntries(['records'=>$comments]);
			} else {
				$this->comments = $this->SetLimitedRecordEntries(['records'=>$comments]);
			}
			
			return TRUE;
		}
		
		public function SetUserLikesDislikes($args) {
			$sql = 'SELECT LikeDislike.* FROM LikeDislike JOIN Entry ON Entry.id = LikeDislike.Entryid AND Entry.Publish = 1 WHERE Userid = ? ';
			
			if(isset($args['limit'])) {
				$sql .= 'LIMIT ' . $args['limit'];
			}
			
			$likedislikes = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>[$this->user['id']],
			]);
			
			if(isset($args['limit'])) {
				$this->likedislikes = $this->SetRecordEntries(['records'=>$likedislikes]);
			} else {
				$this->likedislikes = $this->SetLimitedRecordEntries(['records'=>$likedislikes]);
			}
			
			return TRUE;
		}
		
		public function SetUserCommentsCount() {
			return $this->GetCommentsCount(['user'=>$this->user]);
		}
		
		public function SetUserLikesDislikesCount() {
			return $this->GetLikesDislikesCount(['user'=>$this->user]);
		}
		
						// Browse Comments Functionality
						// ---------------------------------------------
		
		public function browseComments() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm() || !$this->SetUser()) {
				return FALSE;	# 404
			}
			
			$this->SetCommentParameters();
			$this->SetUserCommentsCount();
			$this->SetBrowseParameters();
			$this->SetChildRecords(['noassignment'=>1]);
			$this->SetChildParents();
			
			$this->SetTagCounts();
			
			return TRUE;
		}
		
						// Browse Likes Functionality
						// ---------------------------------------------
		
		public function browseLikes() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm() || !$this->SetUser()) {
				return FALSE;	# 404
			}
			
			$this->SetLikeParameters();
			$this->SetUserLikesDislikesCount();
			$this->SetBrowseParameters();
			$this->SetChildRecords(['noassignment'=>1]);
			$this->SetChildParents();
			$this->SetChildRecordsOfChildren();
			
			$this->SetTagCounts();
			
			return TRUE;
		}
		
						// Browse Helper Functionality
						// ---------------------------------------------
		
		public function SetChildParents() {
			foreach($this->children as $child_key => $child) {
				$this->children[$child_key]['parents'] = $this->GetEntryParents(['entry'=>$child])['parents'];
			}
			
			return $this->children;
		}
		
		public function SetCommentParameters() {
			$this->where = [
				'sql'=>'JOIN Comment ON Comment.Entryid = Entry1.id AND Comment.Approved = 1 AND Comment.Rejected = 0 AND Comment.Userid = ? ',
				'bind'=>'i',
				'value'=>[$this->user['id']],
				'extraselect'=>'Comment.Comment AS Comment, Comment.LastModificationDate AS CommentLastModificationDate, Comment.OriginalCreationDate AS CommentOriginalCreationDate',
			];
			
			return TRUE;
		}
		
		public function SetLikeParameters() {
			$this->where = [
				'sql'=>'JOIN LikeDislike ON LikeDislike.Entryid = Entry1.id AND LikeDislike.LikeOrDislike = 1 AND LikeDislike.Userid = ? ',
				'bind'=>'i',
				'value'=>[$this->user['id']],
				'extraselect'=>'LikeDislike.LastModificationDate AS LikeDislikeModificationDate, LikeDislike.OriginalCreationDate AS LikeDislikeOriginalCreationDate',
			];
			
			return TRUE;
		}
		
		public function SetBrowseParameters() {
			$this->SetBrowseParameters_PageAndPerPage();
			$this->SetBrowseParameters_TotalPages();
			$this->SetBrowseParameters_RemainingPages();
			
			return TRUE;
		}
		
		public function SetBrowseParameters_PageAndPerPage() {
			$this->page = (int)$this->Param('page');
			$possible_per_page = $this->Param('perpage');
			if($possible_per_page === 'custom') {
				$this->custom_per_page_selected = TRUE;
				$possible_per_page = $this->Param('CustomPerPage');
			}
			$this->perpage = (int)$possible_per_page;
			
			$count = $this->comments_count;
			
			if($this->likes_count && !$count) {
				$count = $this->likes_count;
			}
			
			if($this->page < 1) {
				$this->page = 1;
			}
			
			if($this->perpage < 0) {
				$this->perpage = $this->browse_DefaultPerPage();
			}
			
			if($this->perpage < $this->browse_MinPerPage()) {
				$this->perpage = $this->browse_DefaultPerPage();
			} elseif($this->perpage > $this->browse_MaxPerPage()) {
				$this->perpage = $this->browse_MaxPerPage();
			}
			
			$child_record_start_index = ($this->page - 1) * $this->perpage + 1;
			if($child_record_start_index > $count) {
				$this->page = 1;
				$this->perpage = $this->browse_DefaultPerPage();
				$child_record_start_index = 1;
			}
			$child_record_end_index = $child_record_start_index + $this->perpage - 1;
			
			if($child_record_end_index > $count) {
				$child_record_end_index = $count;
			}
			
			$this->child_record_start_index = $child_record_start_index;
			$this->child_record_end_index = $child_record_end_index;
			
			return TRUE;
		}
		
		public function SetBrowseParameters_TotalPages() {
			$count = $this->comments_count;
			
			if($this->likes_count && !$count) {
				$count = $this->likes_count;
			}
			
			$this->total_pages = ceil($count / $this->perpage);
			
			return TRUE;
		}
		
		public function SetBrowseParameters_RemainingPages() {
			$this->total_children_viewed = $this->perpage * ($this->page - 1);
			$this->total_children_left = $this->comments_count - $this->total_children_viewed - ($this->child_record_end_index - $this->child_record_start_index + 1);
			
			return TRUE;
		}
		
		public function browse_DefaultPerPage() {
			return 10;
		}
		
		public function browse_MinPerPage() {
			return 1;
		}
		
		public function browse_MaxPerPage() {
			return 200;
		}
	}

?>
<?php
	classreq('traits/scripts/DBAdminFunctions.php');
	classreq('traits/scripts/DBFunctions.php');
	classreq('traits/scripts/SimpleForms.php');

	class warroom extends basicscript {
		use DBAdminFunctions;
		use DBFunctions;
		use SimpleForms;
		
			// Security Data
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return TRUE;
		}
		
		public function AdminOnly() {
			return TRUE;
		}
		
				// Primary Logic
				// ------------------------------------------------------------
		
		public function Display() {
			ini_set('memory_limit', '400M');
			$this->SetDBAdmin();
			
			$select_sql = 'SELECT * FROM ';
			$order_sql = $this->getOrderBySQL();
			
			$comment_sql = $select_sql . 'Comment WHERE Approved = 0 AND Rejected = 0 ' . $order_sql;
			$suggestion_sql = $select_sql . 'Suggestion WHERE Approved = 0 AND Rejected = 0 ' . $order_sql;
			$error_sql = $select_sql . 'InternalServerError WHERE Resolved = 0 ' . $order_sql;
			$issue_sql = $select_sql . 'InternalServerIssue WHERE Resolved = 0 ' . $order_sql;

			$primary_hosts = $this->getPrimaryHosts();
			$primary_hosts_count = count($primary_hosts);
			
			$comments = [];
			$suggestions = [];
			$errors = [];
			$issues = [];
			
			for($i = 0; $i < $primary_hosts_count; $i++) {
				$primary_host = $primary_hosts[$i];
				
				$client_db = $this->getPrimaryHostDB(['primaryhost'=>$primary_host]);
				
				$comments[$primary_host] = $client_db->RunQuery([
					'sql'=>$comment_sql,
				]);
				
				$suggestions[$primary_host] = $client_db->RunQuery([
					'sql'=>$suggestion_sql,
				]);
				
				$errors[$primary_host] = $client_db->RunQuery([
					'sql'=>$error_sql,
				]);
				
				$issues[$primary_host] = $client_db->RunQuery([
					'sql'=>$issue_sql,
				]);
			}
			
			$this->primary_hosts = $primary_hosts;
			$this->primary_hosts_count = $primary_hosts_count;
			
			$this->comments = $comments;
			$this->suggestions = $suggestions;
			$this->errors = $errors;
			$this->issues = $issues;
			
			return TRUE;
		}
		
		public function getPrimaryHostDB($args) {
			$primary_host = $args['primaryhost'];
			
			$primary_host_pieces = explode('.', $primary_host);
			$primary_host_usable = $primary_host_pieces[0];
			
			$client_db_args = [
				'handler'=>$this->handler,
				'database'=>$primary_host_usable,
			];
			
			$client_db = new DBAccess($client_db_args);
			$client_db->DBStart();
			
			return $client_db;
		}
		
		public function getPrimaryHosts() {
			$selected_primary_host = $this->Param('host');
			
			$primary_hosts = $this->db_admin->ViewAllPrimaryHosts();
			$primary_hosts_count = count($primary_hosts);
			
			for($i = 0; $i < $primary_hosts_count; $i++) {
				$primary_host = $primary_hosts[$i];
				$primary_host_pieces = explode('.', $primary_host);
				$primary_host_comparable = $primary_host_pieces[0];
				
				if($primary_host_comparable == $selected_primary_host) {
					$primary_hosts = [
						$primary_host
					];
					$i = $primary_hosts_count;
				}
			}
			
			return $primary_hosts;
		}
		
		public function getOrderBySQL() {
			$order_sql = 'ORDER BY OriginalCreationDate DESC, id DESC ';
			
			$limit = (int) $this->Param('limit');
			
			if($limit > 0 && $limit < 1001) {
				$order_sql .= 'LIMIT ' . $limit . ' ';
			} else {
				$order_sql .= 'LIMIT 10 ';
			}
			
			return $order_sql;
		}
		
				// Type Handlers
				// ------------------------------------------------------------
			
					// Error Handlers
					// ------------------------------------------------------------
		
		public function viewError() {
			$this->SetDBAdmin();
			
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$acceptable_tables = [
				'InternalServerError'=>1,
				'InternalServerIssue'=>1,
			];
			
			$table = $this->param('table');
			
			if(!isset($acceptable_tables[$table])) {
				return FALSE;
			}
			
			$acceptable_table = $table;
			
			$client_db = $this->getClientDB(['client'=>$client]);
			
			$sql = 'SELECT * FROM ' . $acceptable_table . ' WHERE id = ?';
			$error = $client_db->RunQuery(['sql'=>$sql, 'args'=>[$id]])[0];
			
			if(!$error || !$error['id']) {
				return FALSE;
			}
			
			$this->error = $error;
			
			$this->error_instances = $this->ErrorInstances([
				'db'=>$client_db,
				'table'=>$acceptable_table,
				'id'=>$id,
			]);
			
			return TRUE;
		}
		
			/*
				One ticket, many occurrences.  The ticket carries the context of the
				first one; these are the dates and URLs of the rest.  Newest first,
				and capped, because the ticket an admin opens is the one that has
				fired a hundred thousand times.
			*/
		
		public function ErrorInstances($args) {
			$instance_tables = [
				'InternalServerError'=>['InternalServerErrorInstance', 'Errorid'],
				'InternalServerIssue'=>['InternalServerIssueInstance', 'Issueid'],
			];
			
			$instance_table = $instance_tables[$args['table']];
			
			if(!$instance_table) {
				return [];
			}
			
			$sql = 'SELECT * FROM ' . $instance_table[0];
			$sql .= ' WHERE ' . $instance_table[1] . ' = ?';
			$sql .= ' ORDER BY OriginalCreationDate DESC LIMIT 50';

				//  The instances live in the client's database, beside the ticket
				//  they belong to -- not in the warroom's own.

			$client_db = $args['db'] ? $args['db'] : $this->handler->db_access;

			return $client_db->RunQuery([
				'sql'=>$sql,
				'args'=>[$args['id']],
			]);
		}
		
		public function resolveError() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];

			$acceptable_tables = [
				'InternalServerError'=>1,
				'InternalServerIssue'=>1,
			];

			$table = $this->param('table');

			if(!isset($acceptable_tables[$table])) {
				return FALSE;
			}

			$client_db = $this->getClientDB(['client'=>$client]);

			$error_sql = 'UPDATE ' . $table . ' SET Resolved = TRUE WHERE id = ?';

			$client_db->RunQuery([
				'sql'=>$error_sql,
				'args'=>[$id],
			]);

			$error_sql = 'SELECT * FROM ' . $table . ' WHERE id = ?';

			$error = $client_db->RunQuery([
				'sql'=>$error_sql,
				'args'=>[$id],
			])[0];

			if(!$error || !$error['id'] || !$error['Resolved']) {
				return FALSE;
			}
			
			$this->error = $error;
			
			return TRUE;
		}
			
					// Comment Handlers
					// ------------------------------------------------------------
		
		public function viewComment() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$client_db = $this->getClientDB(['client'=>$client]);
			
			$comment_sql = 'SELECT * FROM Comment WHERE id = ?';

			$comment = $client_db->RunQuery([
				'sql'=>$comment_sql,
				'args'=>[$id],
			])[0];

			if(!$comment || !$comment['id']) {
				return FALSE;
			}
			
			$this->comment = $comment;
			
			return TRUE;
		}
		
		public function acceptComment() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$client_db = $this->getClientDB(['client'=>$client]);
			
			$comment_sql = 'UPDATE Comment SET Approved = TRUE WHERE id = ?';

			$client_db->RunQuery([
				'sql'=>$comment_sql,
				'args'=>[$id],
			]);

			$comment_sql = 'SELECT * FROM Comment WHERE id = ?';

			$comment = $client_db->RunQuery([
				'sql'=>$comment_sql,
				'args'=>[$id],
			])[0];

			if(!$comment || !$comment['id'] || !$comment['Approved']) {
				return FALSE;
			}
			
			$this->comment = $comment;
			
			return TRUE;
		}
		
		public function rejectComment() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$client_db = $this->getClientDB(['client'=>$client]);

			$comment_sql = 'UPDATE Comment SET Rejected = TRUE WHERE id = ?';

			$client_db->RunQuery([
				'sql'=>$comment_sql,
				'args'=>[$id],
			]);

			$comment_sql = 'SELECT * FROM Comment WHERE id = ?';

			$comment = $client_db->RunQuery([
				'sql'=>$comment_sql,
				'args'=>[$id],
			])[0];

			if(!$comment || !$comment['id'] || !$comment['Rejected']) {
				return FALSE;
			}
			
			$this->comment = $comment;
			
			return TRUE;
		}
			
					// Suggestion Handlers
					// ------------------------------------------------------------
		
		public function viewSuggestion() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$client_db = $this->getClientDB(['client'=>$client]);
			
			$suggestion_sql = 'SELECT * FROM Suggestion WHERE id = ?';
			
			$suggestion = $client_db->RunQuery([
				'sql'=>$suggestion_sql,
				'args'=>[$id],
			])[0];
			
			if(!$suggestion || !$suggestion['id']) {
				return FALSE;
			}
			
			$this->suggestion = $suggestion;
			
			return TRUE;
		}
		
		public function acceptSuggestion() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$client_db = $this->getClientDB(['client'=>$client]);
			
			$suggestion_sql = 'UPDATE Suggestion SET Approved = TRUE WHERE id = ?';

			$client_db->RunQuery([
				'sql'=>$suggestion_sql,
				'args'=>[$id],
			]);

			$suggestion_sql = 'SELECT * FROM Suggestion WHERE id = ?';

			$suggestion = $client_db->RunQuery([
				'sql'=>$suggestion_sql,
				'args'=>[$id],
			])[0];

			if(!$suggestion || !$suggestion['id'] || !$suggestion['Approved']) {
				return FALSE;
			}
			
			$this->suggestion = $suggestion;
			
			return TRUE;
		}
		
		public function rejectSuggestion() {
			$client_and_id = $this->getClientAndId();
			
			if(!$client_and_id) {
				return FALSE;
			}
			
			$client = $client_and_id['client'];
			$id = $client_and_id['id'];
			
			$client_db = $this->getClientDB(['client'=>$client]);
			
			$suggestion_sql = 'UPDATE Suggestion SET Rejected = TRUE WHERE id = ?';

			$client_db->RunQuery([
				'sql'=>$suggestion_sql,
				'args'=>[$id],
			]);

			$suggestion_sql = 'SELECT * FROM Suggestion WHERE id = ?';

			$suggestion = $client_db->RunQuery([
				'sql'=>$suggestion_sql,
				'args'=>[$id],
			])[0];

			if(!$suggestion || !$suggestion['id'] || !$suggestion['Rejected']) {
				return FALSE;
			}
			
			$this->suggestion = $suggestion;
			
			return TRUE;
		}
		
				// Utilities
				// ------------------------------------------------------------
		
		public function getClientAndId() {
			$client = $this->getPrimaryHost();
			$id = (int)$this->Param('id');
			
			if(!$client || !$id) {
				return FALSE;
			}
			
			$this->client = $client;
			$this->id = $id;
			
			return [
				'client'=>$client,
				'id'=>$id,
			];
		}
		
		public function getPrimaryHost() {
			$client = $this->Param('client');
			$client_hash = $this->getPrimaryHostsHash();
			
			if($client_hash[$client]) {
				return $client;
			}
			
			return FALSE;
		}
		
		public function getPrimaryHostsHash() {
			$hash = [];
			
			$primary_hosts = $this->db_admin->ViewAllPrimaryHosts();
			$primary_hosts_count = count($primary_hosts);
			
			for($i = 0; $i < $primary_hosts_count; $i++) {
				$primary_host = $primary_hosts[$i];
				
				$hash[$primary_host] = TRUE;
			}
			
			return $hash;
		}
		
		public function getClientDB($args) {
			$client = $args['client'];
			$client_pieces = explode('.', $client);
			$client_usable = $client_pieces[0];

			$client_db_args = [
				'handler'=>$this->handler,
				'database'=>$client_usable,
			];
			
			$client_db = new DBAccess($client_db_args);
			
			return $client_db;
		}
		
				// ** HTML FORMAT DATA ** //
		
			// Title
		
		public function GetHTMLFormatData_Title() {
			return 'No Fighting in the War Room';
		}
	}

?>

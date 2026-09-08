<?php

	class DBAccessUpgraded extends DBAccess {
		public function Upgraded() {
			return TRUE;
		}
		
			// Construction
			// -------------------------------------------------
		
		public function __construct($args) {
			#print('hey!!!!');
			$this->handler = $args['handler'];
			$db_access = $args['db_access'];
			
			$this->SetDatabase($args);
			#print('hey!!!!2');
			
	#		$mysql_time = new TimeMySQL($args);
	#		$this->mysql_time_string = $mysql_time->ConvertTimeFromEpochToMySQLFormat($this->handler->time->time);
			$this->mysql_time_string = $db_access->mysql_time_string;
			
			#$ip_address = new IPAddress($args);
			#$this->ip_address = $ip_address->GetIPAddressForDatabase();
			$this->ip_address = $db_access->ip_address;
			
			#$escapemysql = new EscapeMySQL($args);
			#$this->escapemysql = $escapemysql;
			$this->escapemysql = $db_access->escapemysql;
			
			#$hardcoded_table_entries = new HardcodedTableDescriptions($args);
			#$this->hardcoded_table_entries = $hardcoded_table_entries;
			$this->hardcoded_table_entries = $db_access->hardcoded_table_entries;
			
			if($this->handler->globals->useDBFileCache()) {
				$this->db_file_cache = $db_access->db_file_cache;
			}
			
			$this->db_link = $db_access->db_link;
			
			#print("BT: UPGRADED!");
			#print_r($this->db_link);
			
			if(!$this->ip_address) {
				if($_SERVER['HTTP_HOST'] !== 'localhost' || $_SERVER['SERVER_NAME'] !== 'localhost') {
					die('Unable to proceed if user has no recognizable IP address.');
				}
			}
			
			$this->queries = [];
			
			return TRUE;
		}
		
		public function Enabled() {
			return TRUE;
		}
		
		public function ShowQueries() {
			if($this->Enabled()) {
				print('<pre>');
				print_r($this->queries);
				print('</pre>');
			}
			
			return TRUE;
		}
		
		/*
			Admin-only: name the place that closed the connection, at the moment
			it closes.  DBAccessUpgraded is constructed only for admins, so this
			prints for nobody else.
		*/
		
		public function CloseLink($args) {
			print('<pre>DB CONNECTION CLOSED BY:' . PHP_EOL);
			print($this->BackTrace());
			print('</pre>');
			
			return parent::CloseLink($args);
		}
		
			// Get Schema Information
			// -------------------------------------------------
		
		public function FetchAllRows($args) {
			$query = $args['query'];
			
			$this->queries[] = [
				'query'=>$query,
				'back_trace'=>$this->BackTrace(),
			];
			
			return parent::FetchAllRows($args);
		}
		
		public function FillArraysFromDB($args) {
			$query = $args['query'];
			$sqlbindstring = $args['sqlbindstring'];
			$recordvalues = $args['recordvalues'];
			$record_type = $args['record_type'];
			
			$this->queries[] = [
				'query'=>$query,
				'bind_string'=>$sqlbindstring,
				'record_values'=>$recordvalues,
				'record_type'=>$record_type,
				'back_trace'=>$this->BackTrace(),
			];
			
			return parent::FillArraysFromDB($args);
		}
		
		public function GetRecordDescription_QueryDB($args) {
			$record_type = $args['type'];
			
			$record_schema_query = 'DESCRIBE ' . $record_type;
			
			$this->queries[] = [
				'query'=>$record_schema_query,
				'back_trace'=>$this->BackTrace(),
			];
			
			return parent::GetRecordDescription_QueryDB($args);
		}
		
		public function GetRecords($args) {
		/*
			$record_select = $args['select'];
			$record_type = $args['type'];
			$record_definition = $args['definition'];
			$record_limit = $args['limit'];
			$order_by = $args['orderby'];
			$group_by = $args['groupby'];
			$debug = $args['debug'];
			
			$joins = $args['joins'];
		*/
			$this->queries[] = [
				'query'=>$args,
				'back_trace'=>$this->BackTrace(),
			];
			
			return parent::GetRecords($args);
		}
		
		public function BackTrace() {
			$e = new Exception();
			return $e->getTraceAsString();
		}
	}

?>
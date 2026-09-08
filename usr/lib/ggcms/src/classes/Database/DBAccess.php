<?php

	class DBAccess {
		public $ip_address;
		
		public $escapemysql;
		
		public function Upgraded() {
			return FALSE;
		}
		
			// Construction
			// -------------------------------------------------
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$this->SetDatabase($args);
			
			$mysql_time = new TimeMySQL($args);
			$this->mysql_time_string = $mysql_time->ConvertTimeFromEpochToMySQLFormat($this->handler->time->time);
			
			$ip_address = new IPAddress($args);
			$this->ip_address = $ip_address->GetIPAddressForDatabase();
			
			$escapemysql = new EscapeMySQL($args);
			$this->escapemysql = $escapemysql;
			
			$hardcoded_table_entries = new HardcodedTableDescriptions($args);
			$this->hardcoded_table_entries = $hardcoded_table_entries;
			
			if($this->handler->globals->useDBFileCache()) {
				ggreq('classes/Database/DBFileCache.php');
				
				$this->db_file_cache = new DBFileCache($args);
			}
			
			if(!$this->ip_address) {
				if($_SERVER['HTTP_HOST'] !== 'localhost' || $_SERVER['SERVER_NAME'] !== 'localhost') {
					die('Unable to proceed if user has no recognizable IP address.');
				}
			}
			
			return TRUE;
		}
		
		public function SetDatabase($args) {
			if($args['database']) {
				$this->database = $args['database'];
			} else {
				$server_base_name = $this->handler->domain->host;
				$valid_host_label = $server_base_name;
				
				$valid_database_name = $this->handler->globals->OverrideDatabaseName();
				
				if(!$valid_database_name) {
					$valid_database_name = $server_base_name;
				}
				
				$this->database = $valid_database_name;
				$this->hostlabel = $valid_host_label;
			}
			
			return TRUE;
		}
		
			// Start/Stop the DB
			// -------------------------------------------------
		
		/*
			property_exists() asked whether the PROPERTY exists, and in PHP a
			property that has ever been assigned exists forever -- including
			when it holds a connection that has since been closed.  So after any
			close this never reconnected, and the next prepare() ran against a
			dead link.  That was the "mysqli object is already closed" family,
			398 of them.

			Ask about the link itself instead.  DBEnd clears it, so a closed
			connection reads as absent and is reopened on demand.
		*/

		public function DBStartConditional() {
			if(!$this->IsLinkOpen()) {
				$this->DBStart();
			}

			return TRUE;
		}

		/*
			A closed mysqli is still an instanceof mysqli.  Testing the class
			alone therefore accepted a link that had been closed by someone
			holding another reference to the same object, and the next prepare()
			threw "mysqli object is already closed" -- from inside the shutdown
			error logger, which is the one place that must never throw.

			Reading a property is the cheapest question that a closed link
			refuses to answer.  An unconnected link refuses it too, which is the
			same answer for the same reason: do not use this, open a new one.
		*/

		public function IsLinkOpen() {
			if(!($this->db_link instanceof mysqli)) {
				return FALSE;
			}

			try {
				return (bool) $this->db_link->thread_id;
			} catch (Error $error) {
				return FALSE;
			}
		}
		
		public function DBStart() {
		#	error_reporting(E_ERROR);

				/*
					Cleared first, and cleared again on failure.

					The assignment lives inside the try, so when `new mysqli` threw,
					$this->db_link kept whatever it already held -- which is a link
					that has just been closed.  DBStartConditional would correctly
					see a dead link, call this, get no connection out of it, and the
					next prepare() would throw `mysqli object is already closed` from
					a guard that had done its job.  1,410 of those in eight hours.

					A failed connection now leaves NULL, which every reader below
					tests for, so the failure is reported as a failure instead of
					surfacing later as a fatal somewhere else.
				*/

			$this->db_link = NULL;

			try {
				$this->db_link = new mysqli(
					ini_get("mysqli.default_host"),
					ini_get("mysqli.default_user"),
					ini_get("mysqli.default_pw"),
					$this->database,
					ini_get("mysqli.default_port")
				);
			} catch (Exception $e) {
				$this->db_link = NULL;

				#if($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost') {
				#	print($this->db_link->connect_errno . ' : ' . $this->db_link->connect_error);
				#	print_r($e);
				#}
			}
			
			if(!$this->db_link || $this->db_link->connect_errno) {
				$this->hostname = 'mysql.' . $this->hostlabel . '.com';

				$this->db_link = NULL;

				try {
					$this->db_link = new mysqli(
						ini_get("mysqli.default_host"),
						ini_get("mysqli.default_user"),
						ini_get("mysqli.default_pw"),
						$this->database
					);
				} catch (Exception $e) {
					$this->db_link = NULL;
				}
			}
			
		//	error_reporting(E_ERROR | E_WARNING | E_PARSE);
			
			$results = 1;
			$errors = [];
			
			if(!$this->db_link || $this->db_link->connect_errno) {
				$results = 0;
				$errors[] = [
					'errornumber'=>$this->db_link ? $this->db_link->connect_errno : 0,
					'errormessage'=>$this->db_link ? $this->db_link->connect_error : 'No connection could be opened.',
				];
			} else {
				if($this->handler->globals->SetSQLModePerSession()) {
				#if($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost') {
				#	print($this->db_link->connect_errno . ' : ' . $this->db_link->connect_error);
			#	}
						# never enable `ONLY_FULL_GROUP_BY`
						# nevere nable `STRICT_TRANS_TABLES` (it causes Text types to fail always because they need to have a default type, which they cannot have)
				#	$this->db_link->query("SET GLOBAL sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
					$this->db_link->query("SET SESSION sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
				#	SET @@SESSION.sql_mode	='ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
	
				}
			}
			
			if($this->db_link && $this->db_link->connect_error) {
			#	print_r($this->db_link->connect_error);
				http_response_code(500);
				if($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost') {
					print($this->db_link->connect_errno . ' : ' . $this->db_link->connect_error);
				}
			#	print('<p>');
			#	print($this->db_link->connect_errno . ' : ' . $this->db_link->connect_error);
			#	print('</p>');
			//	print("<PRE>");
			//	print_r($this->db_link);
			//	$e = new \Exception;
			//	var_dump($e->getTraceAsString());
			//	print("</PRE>");
			//	die ("Database unavailable. =(");
			}
			
		#	$this->db_link->set_charset("utf8");
			
			return [
				'results'=>$results,
				'errors'=>$errors,
			];
		}
		
		/*
			The ONLY place in the codebase that closes a MySQL connection.
			Everything else asks this, so the null-checking and the
			already-closed handling live in one place rather than being
			re-derived, differently, at each call site.

			The previous guard tested $this->db_link->connect_error, which
			dereferences the link before establishing there is one.  When
			DBStart threw and never assigned, that was mysqli_close(null) --
			2,300,533 of them in one rotated log, thrown from __destruct, which
			also masked whatever the original failure had been.
		*/

		public function CloseLink($args) {
			$link = $args['link'];
			$this->close_debug = (new Exception)->getTraceAsString();
			
			if(!($link instanceof mysqli)) {
				return FALSE;
			}
			
			try {
				mysqli_close($link);
			} catch (Error $error) {
				return FALSE;		# already closed by someone else
			}
			
			return TRUE;
		}

		public function DBEnd() {
			$this->CloseLink(['link'=>$this->db_link]);
			
			$this->db_link = NULL;		# so DBStartConditional reopens on demand
			
			return TRUE;
		}
		
			// Get Schema Information
			// -------------------------------------------------
		
		public function FetchAllRows($args) {
			$this->DBStartConditional();
			$query = $args['query'];
			$objects = [];
			
			$query_result = $this->db_link->query($query);
			if($query_result) {
				while ($row = $query_result->fetch_assoc()) {
					$objects[] = $row;
				}
			}
			
			return $objects;
		}
		
			// Get Information
			// -------------------------------------------------
		
		public function GetRecords($args) {
			$this->DBStartConditional();
			
			$record_select = $args['select'];
			$record_type = $args['type'];
			$record_definition = $args['definition'];
			$record_limit = $args['limit'];
			$order_by = $args['orderby'];
			$group_by = $args['groupby'];
			$debug = $args['debug'];
			
			$joins = $args['joins'];
			
		#	print("DBAccess.php.  Getrecord... type?...|" . $record_type . "|...definition?...|" . $record_definition . "|");
			
			$get_description_args = [
				'type'=>$record_type,
			];
			$record_description = $this->GetRecordDescription($get_description_args);
			
			if($record_select) {
				$get_query_select = $record_select;
			} else {
				$get_record_select_args = [
					'recordtype'=>$record_type,
					'recorddescription'=>$record_description,
					'joins'=>$joins,
				];
				
				$get_query_select = $this->GetRecords_Select($get_record_select_args);
			}
			
			$get_query_statement = 'SELECT ' . $get_query_select . ' FROM ' . $record_type;
			
			$join_record_args = [
				'joins'=>$joins,
			];
			
			$get_query_statement .= $this->GetRecords_ApplyJoin($join_record_args);
#			print_r($get_query_statement);
			
			$record_where_args = [
				'recorddescription'=>$record_description,
				'recordwhere'=>$record_definition,
				'delimiter'=>' AND ',
			];
			
			$record_where_results = $this->GetRecordWhere($record_where_args);
			$sql_bind_string = $record_where_results['sqlbindstring'];
			$record_where = $record_where_results['sqlwhereclause'];
			$record_values = $record_where_results['sqlwherevalues'];
			
			if($record_where) {
				$get_query_statement .= ' WHERE ' . $record_where;
			}
			
			if($group_by) {
				$get_query_statement .= ' GROUP BY ' . $group_by;
			}
			
			if($order_by) {
				$get_query_statement .= ' ORDER BY ' . $order_by;
			}
			
			$record_limit_args = [
				'recordlimit'=>$record_limit,
			];
			$get_query_statement .= $this->GetRecords_ApplyLimit($record_limit_args);
			
		#	$get_query_statement .= ';';
			
		#	print("STATEMENT: " . $get_query_statement . "<BR><BR>");
			
			$fill_arrays_from_db_args = [
				'query'=>$get_query_statement,
				'sqlbindstring'=>$sql_bind_string,
				'recordvalues'=>$record_values,
			];
			
			if($debug) {
				print("QUERY: ");
				print_r($get_query_statement);
				print("<BR><BR> VALUES: ");
				print_r($record_values);
				print("<BR><BR>");
			}
			
			return $this->FillArraysFromDB($fill_arrays_from_db_args);
		}
		
		public function GetRecords_Select($args) {
			$record_type = $args['recordtype'];
			$record_description = $args['recorddescription'];
			$joins = $args['joins'];
			
			$get_record_select_main_table_args = [
				'recordtype'=>$record_type,
				'recorddescription'=>$record_description,
			];
			
			$all_selected_fields = [];
			$all_selected_fields[] = $this->escapemysql->GetRecordFullSelectStatement($get_record_select_main_table_args);
			
			if($joins) {
				foreach ($joins as $join_type => $join_array) {
					if($join_array) {
						foreach($join_array as $table => $field_where) {
							$join_table_description_args = [
								'type'=>$table,
							];
							
							$join_table_description = $this->GetRecordDescription($join_table_description_args);
							
							$get_record_select_join_table_args = [
								'recordtype'=>$table,
								'recorddescription'=>$join_table_description,
							];
							
							$all_selected_fields[] = $this->escapemysql->GetRecordFullTableSelectStatement($get_record_select_join_table_args);
						}
					}
				}
			}
			
			$all_selected_fields_string = implode(', ', $all_selected_fields);
#			print("BT: " . $all_selected_fields_string);
			
			return $all_selected_fields_string;
		}
		
		public function GetRecords_ApplyJoin($args) {
			$joins = $args['joins'];
			
			if($joins) {
				$full_join_query = [];
				
				foreach ($joins as $join_type => $join_array) {
					if($join_array) {
						foreach($join_array as $table => $field_where) {
							$full_join_query[] = ' ' . $join_type . ' ' . $table . ' ON ' . $field_where;
						}
					}
				}
				
				if($full_join_query) {
					return implode(' ', $full_join_query);
				}
			}
			
			return '';
		}
		
		public function GetRecords_ApplyLimit($args) {
			$record_limit = $args['recordlimit'];
			
			if($record_limit) {
				$cleanse_limit_args = [
					'input'=>$record_limit,
				];
				$cleansed_limit_results = $this->handler->cleanser->CleanseInput_Integer($cleanse_limit_args);
				$cleansed_limit = $cleansed_limit_results['cleansedinput'];
				return ' LIMIT ' . $cleansed_limit;
			}
			
			return '';
		}
		
		public function refValues($arr){
			if (strnatcmp(phpversion(),'5.3') >= 0) { //Reference is required for PHP 5.3+
				$refs = [];
				foreach($arr as $key => $value)
					$refs[$key] = &$arr[$key];
				return $refs;
			}
			return $arr;
		}
		
		public function FillArraysFromDB($args) {
			$this->DBStartConditional();
			
			$query = $args['query'];
			$sqlbindstring = $args['sqlbindstring'];
			$recordvalues = $args['recordvalues'];
			$record_type = $args['record_type'];
			
			$prepare_line = __LINE__;	# Current line number
			$statement = $this->db_link->prepare($query);
			
#			print("BT: " . $query);
#			print_r($recordvalues);
			
			if($statement) {
				if($sqlbindstring) {
					$bind_arguments = [];
					$bind_arguments[] = $sqlbindstring;
					foreach ($recordvalues as $recordkey => $recordvalue) {
						$bind_arguments[] = & $recordvalues[$recordkey];
					}
					
					$bind_line = __LINE__;		# Current line number
					$bind_results = call_user_func_array([$statement, 'bind_param'], $this->refValues($bind_arguments));
					
					if(!$bind_results) {
						$get_error_args = [
							'specifictype'=>'Bind',
							'query'=>$query,
							'values'=>$recordvalues,
							'line'=>$bind_line,
							'function'=>__FUNCTION__,
							'method'=>__METHOD__,
						];
						
						return $this->GetError($get_error_args);
					}
				}
				
				$objects = [];
	#			print_r($query);
				$statement->execute();
				$result = $statement->get_result();
				
				if($result) {
					while ($row = $result->fetch_assoc()) {
						$format_row_args = [
							'row'=>$row,
						];
						$objects[] = $this->FillArraysFromDB_FormatRow($format_row_args);
					}
				} else {
					# print errors here (FIXME)
				}
				return $objects;
			}
			
			$get_error_args = [
				'specifictype'=>'Query',
				'query'=>$query,
				'values'=>$recordvalues,
				'line'=>$prepare_line,
				'function'=>__FUNCTION__,
				'method'=>__METHOD__,
			];
			
			return $this->GetError($get_error_args);
			
			# http://php.net/manual/en/language.constants.predefined.php
		}
		
		public function GetError($args) {
			$error_pieces = $args;
			
			$error = [];
			
			$error['type'] = 'MySQL';
			$error['error'] = $this->db_link->error;
			$error['errornumber'] = $this->db_link->errno;
			
			foreach ($error_pieces as $error_piece_key => $error_piece_value) {
				$error[$error_piece_key] = $error_piece_value;
			}
			
				// Constant Pieces of Data Later On
			$error['trait'] = __TRAIT__;
			$error['class'] = __CLASS__;
			$error['file'] = __FILE__;
			$error['namespace'] = __NAMESPACE__;
			
				// Longest Pieces of Data Last
			$e = new Exception();
			$error['stacktracestring'] = $e->getTraceAsString();
			$error['stacktrace'] = $e->getTrace();
			
			return $error;
		}
		
		public function FillArraysFromDB_FormatRow($args) {
			$row = $args['row'];
			
			$sub_tables = [];
			
			foreach ($row as $field_name => $field_value) {
				$first_row_field_name_char = substr($field_name, 0, 1);
				
				if($first_row_field_name_char === '.') {
					$row_explosion = explode('.', $field_name);
					
					$joined_table_name = $row_explosion[1];
					$joined_table_field = $row_explosion[2];
					
					if(!$sub_tables[$joined_table_name]) {
						$sub_tables[$joined_table_name] = [];
					}
					
					$sub_tables[$joined_table_name][$joined_table_field] = $field_value;
					unset($row[$field_name]);
				}
			}
			
			foreach ($sub_tables as $sub_table => $sub_table_fields) {
				$row[strtolower($sub_table)] = $sub_table_fields;
			}
			
			return $row;
		}
		
		public function GetRecordDescription($args) {
			$hardcoded_function = 'HardcodedTable_' . $args['type'];
			
			if(method_exists($this->hardcoded_table_entries, $hardcoded_function)) {
				return $this->hardcoded_table_entries->$hardcoded_function();
			}
			
		#	if($args['type'] === 'Entry') {
		#		return $this->hardcoded_table_entries->HardcodedTable_Entry();
		#	}
			$result = $this->GetRecordDescription_QueryDB($args);
			
			$record_description = [];
			
			if($result) {
				while ($row = $result->fetch_assoc()) {
					$base_and_attribute_args = [
						'Type'=>$row['Type'],
					];
					$type_base_and_attribute = $this->GetRecordDescription_GetTypeBaseAndAttribute($base_and_attribute_args);
					
					$type_base = $type_base_and_attribute['Base'];
					$type_attribute = $type_base_and_attribute['Attribute'];
					
					$record_description[$row['Field']] = [
						'Type'=>$row['Type'],
						'TypeBase'=>$type_base,
						'TypeAttribute'=>$type_attribute,
						'Null'=>$row['Null'],
						'Key'=>$row['Key'],
						'Default'=>$row['Default'],
						'Extra'=>$row['Extra'],
					];
				}
			} else {
				die('Failed : ' . $this->database . "|" . $record_schema_query);
			}
			
			#http://php.net/manual/en/pdo.errorinfo.php
			#http://php.net/manual/en/mysqli.info.php
			
			return $record_description;
		}
		
		public function GetRecordDescription_QueryDB($args) {
			$this->DBStartConditional();
			
			$record_type = $args['type'];
			
			$record_schema_query = 'DESCRIBE ' . $record_type;
			
			$result = $this->db_link->query($record_schema_query);
			
			return $result;
		}
		
		public function GetRecordDescription_GetTypeBaseAndAttribute($args) {
			$type = $args['Type'];
			$type_base_explosion = explode('(', $type, 2);
			
			$type_base = $type_base_explosion[0];
			$type_attribute_unclean = $type_base_explosion[1];
			
			if($type_attribute_unclean) {
				$type_attribute = substr($type_attribute_unclean, 0, strlen($type_attribute_unclean) - 1);
			} else {
				$type_attribute = '';
			}
			
			return [
				'Base'=>$type_base,
				'Attribute'=>$type_attribute,
			];
		}
		
		public function GetRecordWhere($args) {
			$record_description = $args['recorddescription'];
			$record_where = $args['recordwhere'];
			$delimiter = $args['delimiter'];
			
			$sql_bind_string = '';
			$sql_where_clauses = [];
			$sql_where_values = [];
			$all_bindings = [];
			
			foreach ($record_where as $key => $value) {
				$field_description = $record_description[$key];
				
				if($key === 'RAW') {
					if(!is_array($value)) {
						return [
							'sqlbindstring'=>'',
							'sqlwhereclause'=>$value,
							'sqlwherevalues'=>'',
							'allbindings'=>[],
						];
					}
					
					foreach($value as $valuekey => $valuevalue) {
						$fieldkey = $valuekey;
						$operator = ' ' .  $valuevalue[0] . ' ';
						$binding = $valuevalue[1];
					}
				} elseif(!(is_array($value))) {
					$fieldkey = $key;
					$operator = ' = ';
					$escaped_value = $value;
					$binding = '?';
				} else {
					$fieldkey = $key;
					$operator = ' ' . $value[0] . ' ';
					$escaped_value = $value[1];
					$binding = '?';
				}
				
				$sql_bind_string_args = [
					'fielddescription'=>$field_description,
				];
				
				if($binding === '?') {
					$sql_bind_string .= $this->GetMySQLFieldPHPBinding($sql_bind_string_args);
					$sql_where_values[] = $escaped_value;
				}
				
				$sql_where_clauses[] = $fieldkey . $operator . $binding;
				$all_bindings[] = $binding;
			}
			
			$sql_where_clause = implode($delimiter, $sql_where_clauses);
			
			return [
				'sqlbindstring'=>$sql_bind_string,
				'sqlwhereclause'=>$sql_where_clause,
				'sqlwherevalues'=>$sql_where_values,
				'allbindings'=>$all_bindings,
			];
		}
		
		public function GetMySQLFieldPHPBinding($args) {
			$fielddescription = $args['fielddescription'];
			
			switch($fielddescription['TypeBase']) {
				case 'bigint':
				case 'int':
				case 'mediumint':
				case 'smallint':
				case 'tinyint':
				case 'year':
					return 'i';	# Integer
					
				case 'decimal':
				case 'numeric':
				case 'double':
				case 'float':
					return 'd';	# Double
			}
			
			return 's';	# String
		}
		
			// Update Information
			// -------------------------------------------------
		
		public function UpdateRecord($args) {
			$this->MarkPageCacheDirty($args);
			$this->MarkRowCacheDirty($args);

			$record_type = $args['type'];
			$record_update = $args['update'];
			$record_where = $args['where'];
			
			if($record_type) {
				$get_description_args = [
					'type'=>$record_type,
				];
				$record_description = $this->GetRecordDescription($get_description_args);
				
				$record_update_for_where = $record_update;
				unset($record_update_for_where['id']);
				
				$record_set_args = [
					'recorddescription'=>$record_description,
					'recordwhere'=>$record_update_for_where,
					'delimiter'=>', ',
				];
				
				$record_set_results = $this->GetRecordWhere($record_set_args);
				$set_sql_bind_string = $record_set_results['sqlbindstring'];
				$set_record_where = $record_set_results['sqlwhereclause'];
				$set_record_values = $record_set_results['sqlwherevalues'];
				
				$record_where_args = [
					'recorddescription'=>$record_description,
					'recordwhere'=>$record_where,
					'delimiter'=>' AND ',
				];
				
				$record_where_results = $this->GetRecordWhere($record_where_args);
				$sql_bind_string = $record_where_results['sqlbindstring'];
				$record_where = $record_where_results['sqlwhereclause'];
				$record_values = $record_where_results['sqlwherevalues'];
				
				$update_statement = 'UPDATE ' . $record_type . ' SET ' . $set_record_where . ', LastModificationDate = NOW()';
				
				if($record_where) {
					$update_statement .= ' WHERE ' . $record_where;
				}
				
				$this->FillArraysFromDB([
					'query'=>'SET @update_id := 0;',
				]);
				$uid_handling_clause = ' AND (SELECT @update_id := id)';
				$update_statement .= $uid_handling_clause;
				
				$query_result = $this->FillArraysFromDB([
					'query'=>$update_statement,
					'sqlbindstring'=>$set_sql_bind_string . $sql_bind_string,
					'recordvalues'=>array_merge($set_record_values, $record_values),
				]);
				
	#		print_r($this->db_link->error);
				
				$record_update_ids = $this->FillArraysFromDB([
					'query'=>'SELECT @update_id;',
				]);
				
				$new_record_where = [
					'type'=>$record_type,
					'definition'=>[
						'id'=>$record_update_ids[0]['@update_id'],
					],
				];
				
				$new_record = $this->GetRecords($new_record_where);
				
				return $new_record;
			} else {
				return FALSE;
			}
		}
		
			// Insert Information
			// -------------------------------------------------
		
		public function CreateRecord($args) {
			$this->MarkPageCacheDirty($args);
			$this->MarkRowCacheDirty($args);

			$record_type = $args['type'];
			$record_definition = $args['definition'];
			
			$get_description_args = [
				'type'=>$record_type,
			];
			$record_description = $this->GetRecordDescription($get_description_args);
			
			$record_values_args = [
				'recorddescription'=>$record_description,
				'recordwhere'=>$record_definition,
				'delimiter'=>', ',
			];
			
			$record_where_results = $this->GetRecordWhere($record_values_args);
			
			$sql_bind_string = $record_where_results['sqlbindstring'];
			$record_where = $record_where_results['sqlwhereclause'];
			$record_values = $record_where_results['sqlwherevalues'];
			$all_bindings = $record_where_results['allbindings'];
			
			$record_columns_args = [
				'recorddefinition'=>$record_definition,
			];
			$record_columns = $this->GetRecordColumns($record_columns_args);
			$record_columns .= ', OriginalCreationDate, LastModificationDate';
			
			$query_statement = 'INSERT INTO ' . $record_type . ' (' . $record_columns . ') VALUES (' . implode(', ', $all_bindings) . ', NOW(), NOW())';
		
			$query_result = $this->FillArraysFromDB([
				'query'=>$query_statement,
				'sqlbindstring'=>$sql_bind_string,
				'recordvalues'=>$record_values,
				'record_type'=>$record_type,
			]);
			
			if($record_type == 'InternalServerIssue') {

	#			print("<PRE>");
	#			print_r($query_statement);
	#			print_r($sql_bind_string);
				
	#			print_r($record_values);
				
			}
			
			$new_record_id = mysqli_insert_id($this->db_link);
			
			if($new_record_id) {
				$new_record_where = [
					'type'=>$record_type,
					'definition'=>[
						'id'=>$new_record_id,
					],
				];
				
				$new_record = $this->GetRecords($new_record_where)[0];
				return $new_record;
			}
			
			return $query_result;
		}
		

			// Insert Information, Counted
			// -------------------------------------------------

		/*
			The error and issue queues are ticket tables rather than journals.  A
			fatal that fires 2,300,533 times is one defect, and storing it 2,300,533
			times -- every row carrying a print_r() of the whole handler -- is what
			made those two tables the largest thing in the database.

			So the ticket is keyed by a signature over the script and the message,
			and a repeat bumps its count and its LastModificationDate instead of
			inserting again.  The occurrences themselves live in the companion
			Instance table, which holds a date and a URL and nothing else.

			A recurrence clears Resolved.  If it is still happening it is not fixed,
			and a ticket closed in the warroom that quietly keeps firing is exactly
			the thing this table exists to show.

			The upsert is one statement because SELECT-then-INSERT-or-UPDATE loses
			records to a race, and these tables fill fastest precisely when the site
			is failing hardest.  LAST_INSERT_ID(id) is what makes the existing row's
			id readable after a duplicate; without it mysqli_insert_id() returns 0
			and the instance would have nothing to point at.
		*/

		public function CreateCountedRecord($args) {
			$this->MarkPageCacheDirty($args);
			$this->MarkRowCacheDirty($args);

			$record_type = $args['type'];
			$record_definition = $args['definition'];
			$instance_type = $args['instancetype'];
			$instance_field = $args['instancefield'];

			$record_description = $this->GetRecordDescription([
				'type'=>$record_type,
			]);

			$record_where_results = $this->GetRecordWhere([
				'recorddescription'=>$record_description,
				'recordwhere'=>$record_definition,
				'delimiter'=>', ',
			]);

			$record_columns = $this->GetRecordColumns([
				'recorddefinition'=>$record_definition,
			]);
			$record_columns .= ', OriginalCreationDate, LastModificationDate';

			$query_statement = 'INSERT INTO ' . $record_type . ' (' . $record_columns . ')';
			$query_statement .= ' VALUES (' . implode(', ', $record_where_results['allbindings']) . ', NOW(), NOW())';
			$query_statement .= ' ON DUPLICATE KEY UPDATE';
			$query_statement .= ' id = LAST_INSERT_ID(id),';
			$query_statement .= ' IncidentCount = IncidentCount + 1,';
			$query_statement .= ' Resolved = 0,';
			$query_statement .= ' LastModificationDate = NOW()';

			$this->FillArraysFromDB([
				'query'=>$query_statement,
				'sqlbindstring'=>$record_where_results['sqlbindstring'],
				'recordvalues'=>$record_where_results['sqlwherevalues'],
				'record_type'=>$record_type,
			]);

			$record_id = mysqli_insert_id($this->db_link);

			if(!$record_id) {
				return FALSE;
			}

			$this->CreateRecord([
				'type'=>$instance_type,
				'definition'=>[
					$instance_field=>$record_id,
					'URL'=>$args['url'],
				],
			]);

			return $record_id;
		}
		
			// Delete Information
			// -------------------------------------------------
		
		public function DeleteRecords($args) {
			$this->MarkPageCacheDirty($args);
			$this->MarkRowCacheDirty($args);

			$type = $args['type'];
			$where = $args['where'];
			$sql_bind_string = $args['sqlbindstring'];
			$where_values = $args['wherevalues'];
			
			$sql = 'DELETE FROM ' . $type . ' WHERE ' . $where;
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>$sql_bind_string,
				'recordvalues'=>$where_values,
			];
			return $this->FillArraysFromDB($fill_arrays_from_db_args);
		}
		
		public function GetRecordColumns($args) {
			$record_definition = $args['recorddefinition'];
			
			$record_keys = [];
			
			foreach ($record_definition as $key => $value) {
				if($key === 'RAW') {
					foreach($value as $valuekey => $valuevalue) {
						$record_keys[] = $valuekey;
					}
				} else {
					$record_keys[] = $key;
				}
			}
			
			$columns = implode(', ', $record_keys);
			
			return $columns;
		}
		
			// Delete Other Information
			// -------------------------------------------------
		
		public function DeleteOtherRecords($args) {
			$this->MarkPageCacheDirty($args);
			$this->MarkRowCacheDirty($args);

			$type = $args['type'];
			$field = $args['field'];
			$fieldtype = $args['fieldtype'];
			$notin = $args['notin'];
			$extrawhere = $args['extrawhere'];
			
			$sql = 'DELETE FROM ' . $type;
			$sql_bind_string = '';
			
			if(count($notin)) {
				$sql .= ' WHERE ' . $field . ' NOT IN(';
				
				if(($fieldtype === 'int') || ($fieldtype === 'i')) {
					$repeat_component = 'i';
				} else {
					$repeat_component = 's';
				}
				
				$sql_bind_string = str_repeat($repeat_component, count($notin));
				$sql .= implode(', ', array_fill(0, count($notin), '?'));
				$sql .= ')';
				
				if($extrawhere) {
					$sql .= ' AND ' . $extrawhere;
				}
			} else if($extrawhere) {
				$sql .= ' WHERE ' . $extrawhere;
			}
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>$sql_bind_string,
				'recordvalues'=>$notin,
			];
			return $this->FillArraysFromDB($fill_arrays_from_db_args);
		}
		
		public function RunQuery($args) {
			$sql = $args['sql'];
			$db_args = $args['args'];
			
			if(!$db_args) {
				$db_args = [];
			}
			
			$sql_bind_string = str_repeat('s', count($db_args));
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>$sql_bind_string,
				'recordvalues'=>$db_args,
			];
			
			return $this->FillArraysFromDB($fill_arrays_from_db_args);
		}

			// Page cache invalidation
			// -------------------------------------------------

		/*
			Any database write makes every cached page for this domain suspect.

			Marking is separated from flushing because one save in modify.php
			performs dozens of writes, and flushing a whole domain tree dozens of
			times would be absurd.  The mark is cheap and idempotent; the flush
			runs once, at shutdown, after every write in the request has landed.

			Flushing at the start of the write instead would leave a window in
			which a concurrent read could re-cache the pre-write page and make the
			stale copy permanent.
		*/

		/*
			Operational writes must NOT invalidate anything.  Every 404 logs an
			InternalServerIssue and every error logs an InternalServerError, so
			hooking those to a flush means a crawler probing nonsense URLs wipes
			the cache continuously and it never survives long enough to be used.
			That is exactly what happened on first deployment.
		*/

		public function NonContentRecordTypes() {
			return [
					# error and issue logging, written on every 404 and fault
				'InternalServerError',
				'InternalServerIssue',
				'InternalServerErrorInstance',
				'InternalServerIssueInstance',
				'UserSession',

					/*
						Derived statistics, recomputed and rewritten during an
						ordinary page render.  Excluding them is not an
						optimisation, it is required for the page cache to
						function at all: rendering a page updates these, which
						marked the domain dirty, which flushed the very entry
						the render had just written.  Every page destroyed its
						own cache on the way out and nothing was ever served
						from disk twice.
					*/
				'ChildRecordStats',
				'AssociatedRecordStats',
				'ChildRecordCount',
				'RecordChange',
			];
		}

			/*
				Types written by a visitor acting on one page, rather than by
				someone editing content.

				A like or a comment changes what that one page shows and
				nothing else, so flushing the whole domain for it is enormously
				out of proportion: every such write deleted every cached page
				on the site, and the sites with the most engagement therefore
				kept the least cache.

				They are deliberately not in NonContentRecordTypes.  Excluding
				them entirely would leave a stale like count on a cached page
				forever, which is the opposite mistake.
			*/

		public function EngagementRecordTypes() {
			return [
				'LikeDislike',
				'Comment',
				'Suggestion',
			];
		}

		/*
			The row cache lives one layer below the page cache and, until now,
			was never invalidated by anything.  Flushing a page and leaving its
			rows cached is worse than not caching at all: the page re-renders,
			reads the stale row, and writes a fresh page cache that looks new
			and holds the old value.

			The keys come from the four ORM read paths, which are the only
			writers:

				ggcms_MasterRecord      key '1', one per domain
				ggcms_ChildRecordCount  key: entry id
				ggcms_EntryChildRecords subtype: child table, key: entry id
				ggcms_RecordTree        key: '%2F'-joined URL path

			Child records carry Entryid; Association carries ChosenEntryid;
			Assignment carries Parentid and Childid.  Whichever of those appear
			in the write name the entries whose cached rows are now wrong.
		*/

		public function MarkRowCacheDirty($args) {
			if(!$this->db_file_cache) {
				return FALSE;
			}

			if(in_array($args['type'], $this->NonContentRecordTypes())) {
				return FALSE;
			}

			if(!is_array($this->row_cache_dirty_entry_ids)) {
				$this->row_cache_dirty_entry_ids = [];
				$this->row_cache_dirty_codes = [];
				$this->row_cache_dirty_user_ids = [];
			}

				/*
					UpdateRecord passes 'update' and 'where', CreateRecord passes
					'definition', DeleteRecords passes 'where'.  Read all three
					rather than branching on the caller: over-collecting an id
					costs one re-render, missing one serves a stale page.
				*/

			$comment_named_its_entry = FALSE;

			foreach(['update', 'definition', 'where'] as $args_key) {
				$field_set = $args[$args_key];

				if(!is_array($field_set)) {
					continue;
				}

				foreach(['Entryid', 'ChosenEntryid', 'Parentid', 'Childid'] as $foreign_key) {
					if($field_set[$foreign_key]) {
						$this->row_cache_dirty_entry_ids[$field_set[$foreign_key]] = TRUE;
					}
				}

				if($args['type'] === 'Entry') {
					if($field_set['id']) {
						$this->row_cache_dirty_entry_ids[$field_set['id']] = TRUE;
					}

						/*
							A Code identifies the entry's segment in every
							RecordTree key that carries it.  When the write does
							not name one -- which is most Entry updates, since
							they pass only the columns that changed -- there is
							no way to know which paths are affected, so the whole
							type goes.  That is 3,543 files of 1.3 KB on
							revoltlib, refilled lazily one page at a time, and it
							only happens on an author's edit.

							Deliberately blunt.  Over-deleting costs a render;
							under-deleting serves a wrong title indefinitely.
						*/

					if(strlen($field_set['Code'])) {
						$this->row_cache_dirty_codes[$field_set['Code']] = TRUE;
					} else {
						$this->row_cache_dirty_all_trees = TRUE;
					}
				}

				if($args['type'] === 'Comment') {
					if($field_set['Entryid']) {
						$comment_named_its_entry = TRUE;
					}
				}

					/*
						ggcms_UserIds caches Username and EmailAddress, and a
						commenter naming themselves for the first time updates
						User by its own id -- SimpleORM's comment path does
						exactly that.  The foreign keys above never catch it,
						because here the User IS the record rather than a
						reference to one.
					*/

				if($args['type'] === 'User') {
					if($field_set['id']) {
						$this->row_cache_dirty_user_ids[$field_set['id']] = TRUE;
					}
				}
			}


				/*
					An approval names only the comment's own id -- see the
					approval path in userstatus.php -- so nothing in the write
					says which entry just gained a visible comment.

					Same answer as an Entry write that carries no Code: delete
					the type rather than guess at it.  Cheaper here than there,
					too -- one file per entry that has comments at all, and
					rebuilt by a moderator's click rather than by a render.
				*/

			if($args['type'] === 'Comment' && !$comment_named_its_entry) {
				$this->row_cache_dirty_all_comments = TRUE;
			}

			if($this->row_cache_dirty) {
				return TRUE;
			}

			$this->row_cache_dirty = TRUE;

			register_shutdown_function([$this, 'FlushRowCacheNow']);

			return TRUE;
		}

		/*
			Runs at shutdown, after the response has gone out, for the same
			reason FlushPageCacheNow does: the visitor should never wait on
			cache maintenance, and cache maintenance must never break a write.
		*/

		public function FlushRowCacheNow() {
			try {
				$entry_ids = array_keys((array) $this->row_cache_dirty_entry_ids);

				if(count($entry_ids)) {
					$this->db_file_cache->DeleteCache([
						'type'=>'ggcms_ChildRecordCount',
						'arguments'=>$entry_ids,
					]);

						/*
							ggcms_EntryRecords is ORM::GetRecordsAndChildren's
							base fetch, keyed by entry id like the two above.
							It caches Title and Code among other columns, so an
							edit that misses it shows the old title on every
							index page that lists the entry.
						*/

					$this->db_file_cache->DeleteCache([
						'type'=>'ggcms_EntryRecords',
						'arguments'=>$entry_ids,
					]);

						/*
							ggcms_Comments_approved is SetComments()'s list of
							approved comments, keyed by entry id.  A new comment
							names its Entryid, so it lands here.
						*/

					$this->db_file_cache->DeleteCache([
						'type'=>'ggcms_Comments_approved',
						'arguments'=>$entry_ids,
					]);

					$subtypes = $this->db_file_cache->DeleteCache_Subtypes([
						'type'=>'ggcms_EntryChildRecords',
					]);

					foreach($subtypes as $subtype) {
						$this->db_file_cache->DeleteCache([
							'type'=>'ggcms_EntryChildRecords',
							'subtype'=>$subtype,
							'arguments'=>$entry_ids,
						]);
					}
				}

					/*
						One file per domain, holding the site's own record.  Any
						content write can have changed it and it costs a single
						query to rebuild.
					*/

				$this->db_file_cache->DeleteCache([
					'type'=>'ggcms_MasterRecord',
					'arguments'=>['1'],
				]);

				if($this->row_cache_dirty_all_trees) {
					$this->db_file_cache->DeleteCache_All([
						'type'=>'ggcms_RecordTree',
					]);
				} else {
					foreach(array_keys((array) $this->row_cache_dirty_codes) as $code) {
						$this->db_file_cache->DeleteCache_PathSegment([
							'type'=>'ggcms_RecordTree',
							'segment'=>$code,
						]);
					}
				}

				if($this->row_cache_dirty_all_comments) {
					$this->db_file_cache->DeleteCache_All([
						'type'=>'ggcms_Comments_approved',
					]);
				}

				$user_ids = array_keys((array) $this->row_cache_dirty_user_ids);

				if(count($user_ids)) {
					$this->db_file_cache->DeleteCache([
						'type'=>'ggcms_UserIds',
						'arguments'=>$user_ids,
					]);
				}
			} catch (Throwable $exception) {
				# cache maintenance must never break a write
			}

			return TRUE;
		}

		public function MarkPageCacheDirty($args) {
			if(in_array($args['type'], $this->NonContentRecordTypes())) {
				return FALSE;
			}

				/*
					Domain wins.  A request that edits content and records a
					like needs the wider flush, so once the scope is 'domain'
					nothing narrows it again.
				*/

			if(!in_array($args['type'], $this->EngagementRecordTypes())) {
				$this->page_cache_dirty_scope = 'domain';
			} elseif($this->page_cache_dirty_scope !== 'domain') {
				$this->page_cache_dirty_scope = 'page';
			}

			if($this->page_cache_dirty) {
				return TRUE;
			}

			$this->page_cache_dirty = TRUE;

			register_shutdown_function([$this, 'FlushPageCacheNow']);

			return TRUE;
		}

			/*
				Empty when there is no handler or no domain on it -- a caller
				that has neither gets the old SafeHost() behaviour rather than
				a fatal, which is the right trade for cache maintenance.
			*/

		public function PageCacheHostsToFlush() {
			if(!is_object($this->handler) || !property_exists($this->handler, 'domain')) {
				return [];
			}

			if(!is_object($this->handler->domain)) {
				return [];
			}

			$domain = $this->handler->domain->primary_domain_lowercased;

			if(!is_string($domain) || strlen($domain) === 0) {
				return [];
			}

			if(substr($domain, 0, 4) === 'www.') {
				return [$domain, substr($domain, 4)];
			}

			return [$domain, 'www.' . $domain];
		}

		public function FlushPageCacheNow() {
			try {
				if(!class_exists('PageCache')) {
					ggreq('classes/Cache/PageCache.php');
				}

				$page_cache = new PageCache(['handler'=>$this->handler]);

				if($this->page_cache_dirty_scope === 'page') {
					$page_cache->FlushPage([]);
				} else {

						/*
							The domain is passed, not looked up.

							FlushDomain falls back to SafeHost(), which reads
							$_SERVER['HTTP_HOST'] -- present on the web and
							absent from a shell, where it returned FALSE and
							the flush quietly did nothing. Every tool that
							writes an entry from the command line then had to
							know to clear the cache itself, and one that
							forgot left pages serving the old version with
							nothing to say so.

							The handler already knows which domain it is
							serving, on both SAPIs, so it is asked instead.

							Both hostnames, because the cache is keyed on
							HTTP_HOST verbatim: every page is stored twice,
							under example.com and www.example.com, and
							clearing one leaves the other serving what it held
							before the write. The engine's own flush had that
							fault on the web too -- it cleared whichever
							hostname the editor happened to arrive on.
						*/

					$hosts = $this->PageCacheHostsToFlush();

					if($hosts) {
						foreach($hosts as $host) {
							$page_cache->FlushDomain(['domain'=>$host]);
						}
					} else {
						$page_cache->FlushDomain([]);
					}
				}
			} catch (Throwable $throwable) {
				# cache maintenance must never break a write -- and a
				# redeclare is an Error, which Exception does not catch,
				# which is how this one got out.  The sibling handler
				# above already catches Throwable.
			}

			return TRUE;
		}

	}

?>

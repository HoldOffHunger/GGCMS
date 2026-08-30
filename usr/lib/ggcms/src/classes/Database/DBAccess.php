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
		
		public function DBStartConditional() {
			if(!property_exists($this, 'db_link')) {
				$this->DBStart();
			}
			
			return TRUE;
		}
		
		public function DBStart() {
		#	error_reporting(E_ERROR);
			
			try {
				$this->db_link = new mysqli(
					ini_get("mysqli.default_host"),
					ini_get("mysqli.default_user"),
					ini_get("mysqli.default_pw"),
					$this->database,
					ini_get("mysqli.default_port")
				);
			} catch (Exception $e) {
				#if($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost') {
				#	print($this->db_link->connect_errno . ' : ' . $this->db_link->connect_error);
				#	print_r($e);
				#}
			}
			
			if($this->db_link->connect_errno) {
				$this->hostname = 'mysql.' . $this->hostlabel . '.com';
				$this->db_link = new mysqli(
					ini_get("mysqli.default_host"),
					ini_get("mysqli.default_user"),
					ini_get("mysqli.default_pw"),
					$this->database
				);
			}
			
		//	error_reporting(E_ERROR | E_WARNING | E_PARSE);
			
			$results = 1;
			$errors = [];
			
			if($this->db_link->connect_errno) {
				$results = 0;
				$errors[] = [
					'errornumber'=>$this->db_link->connect_errno,
					'errormessage'=>$this->db_link->connect_error,
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
			
			if($this->db_link->connect_error) {
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
		
		public function DBEnd() {
			if(!$this->db_link->connect_error) {
				mysqli_close($this->db_link);
			}
			
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
		
			// Delete Information
			// -------------------------------------------------
		
		public function DeleteRecords($args) {
			$this->MarkPageCacheDirty($args);

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

			$type = $args['type'];
			$field = $args['field'];
			$fieldtype = $args['fieldtype'];
			$notin = $args['notin'];
			$extrawhere = $args['extrawhere'];
			
			$sql = 'DELETE FROM ' . $type . ' WHERE ' . $field . ' NOT IN(';
			
			if($fieldtype = 'int') {
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

		public function MarkPageCacheDirty($args) {
			if(in_array($args['type'], $this->NonContentRecordTypes())) {
				return FALSE;
			}

			if($this->page_cache_dirty) {
				return TRUE;
			}

			$this->page_cache_dirty = TRUE;

			register_shutdown_function([$this, 'FlushPageCacheNow']);

			return TRUE;
		}

		public function FlushPageCacheNow() {
			try {
				ggreq('classes/Cache/PageCache.php');

				$page_cache = new PageCache(['handler'=>$this->handler]);
				$page_cache->FlushDomain([]);
			} catch (Exception $exception) {
				# cache maintenance must never break a write
			}

			return TRUE;
		}

	}

?>
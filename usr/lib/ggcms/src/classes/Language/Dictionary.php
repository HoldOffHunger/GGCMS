<?php

	class Dictionary {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		function __destruct() {
			return $this->DBEnd();
		}
		
		public function LookUpWords($args) {
			$words_to_look_up = $args['words'];
			
			if($this->handler->db_access->db_file_cache) {
				$lowercased_words = [];
				
				foreach($words_to_look_up as $word_to_look_up) {
					$lowercased_words[] = strtoupper($word_to_look_up);
				}
				
				$db_file_cache_results = $this->handler->db_access->db_file_cache->ReadCache([
					'directory'=>'dictionaries',
					'arguments'=>$lowercased_words,
					'field'=>'Term',
				]);
			}
			
			if(!$words_to_look_up) {
				return [];
			}
			
			if(is_array($db_file_cache_results)) {
				$db_results = $db_file_cache_results;
				
			#	if($_GET['soybeans']) {
			#		print_r("BT: DB CACHE HIT ON DICTIONARY!");
			#		print_r($db_results);
			#	}
			} else {
				if(!$this->db_link) {
					$this->DBStart();
				}
				
				$sql = 'SELECT ';
				
				$sql .= ' Definition.id AS id, ';
				$sql .= ' Definition.Term AS Term, ';
				$sql .= ' Definition.Pronunciation AS Pronunciation, ';
				$sql .= ' Definition.PartOfSpeech AS PartOfSpeech, ';
				$sql .= ' Definition.Etymology AS Etymology, ';
				$sql .= ' Definition.Definition AS Definition, ';
				
				$sql .= ' Dictionary.id AS Dictionaryid, ';
				$sql .= ' Dictionary.Title AS DictionaryTitle, ';
				$sql .= ' Dictionary.Subtitle AS DictionarySubtitle, ';
				$sql .= ' Dictionary.ListTitle AS DictionaryListTitle, ';
				$sql .= ' Dictionary.Code AS DictionaryCode ';
				
				$sql .= ' FROM ';
				$sql .= ' Definition ';
				$sql .= ' JOIN Dictionary ON Definition.Dictionaryid = Dictionary.id ';
				$sql .= ' WHERE ';
				$sql .= ' Definition.Term IN(';
				
				$sql .= implode(', ', array_fill(0, count($words_to_look_up), '?'));
				
				$sql .= ')';
				$sql_bind_string = str_repeat('s', count($words_to_look_up));
				
				$fill_arrays_from_db_args = [
					'query'=>$sql,
					'sqlbindstring'=>$sql_bind_string,
					'recordvalues'=>$words_to_look_up,
				];
				
				$db_results = $this->FillArraysFromDB($fill_arrays_from_db_args);
				
				if($this->handler->db_access->db_file_cache) {
					if(count($db_results) !== 0) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'directory'=>'dictionaries',
							'arguments'=>$lowercased_words,
							'data'=>$db_results,
							'field'=>'Term',
						]);
					}
				}
			}
			$definitions = [];
			
			foreach ($db_results as $definition) {
				if(!$definitions[strtolower($definition['Term'])]) {
					$definitions[strtolower($definition['Term'])] = [];
				}
				
				$definitions[strtolower($definition['Term'])][] = $definition;
			}
			
			return $definitions;
		}
		
		public function LookUpWord($args) {
			$word_to_look_up = $args['word'];
			
			if($this->handler->db_access->db_file_cache) {
				$db_file_cache_results = $this->handler->db_access->db_file_cache->ReadCache([
					'directory'=>'dictionaries',
					'arguments'=>[strtolower($word_to_look_up),],
				]);
				
				if($db_file_cache_results) {
					$word_data = $db_file_cache_results;
				}
			}
			
			if(!is_array($word_data)) {
				if(!$this->db_link) {
					$this->DBStart();
				}
				
				$sql = 'SELECT ';
				
				$sql .= ' Definition.id AS Definitionid, ';
				$sql .= ' Definition.Term AS Term, ';
				$sql .= ' Definition.Pronunciation AS Pronunciation, ';
				$sql .= ' Definition.PartOfSpeech AS PartOfSpeech, ';
				$sql .= ' Definition.Etymology AS Etymology, ';
				$sql .= ' Definition.Definition AS Definition, ';
				
				$sql .= ' Dictionary.id AS Dictionaryid, ';
				$sql .= ' Dictionary.Title AS DictionaryTitle, ';
				$sql .= ' Dictionary.Subtitle AS DictionarySubtitle, ';
				$sql .= ' Dictionary.ListTitle AS DictionaryListTitle, ';
				$sql .= ' Dictionary.Code AS DictionaryCode ';
				
				$sql .= ' FROM ';
				$sql .= ' Definition ';
				$sql .= ' JOIN Dictionary ON Definition.Dictionaryid = Dictionary.id ';
				$sql .= ' WHERE ';
				$sql .= ' Definition.Term = ? ';
				
				$fill_arrays_from_db_args = [
					'query'=>$sql,
					'sqlbindstring'=>'s',
					'recordvalues'=>[$word_to_lookup_up],
				];
				
				$word_data = $this->FillArraysFromDB($fill_arrays_from_db_args);
				
				if($this->handler->db_access->db_file_cache) {
					if(count($word) !== 0) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'directory'=>'dictionaries',
							'arguments'=>[strtolower($word_to_look_up),],
							'data'=>$word_data,
						]);
					}
				}
			}
			
			return $word_data;
		}
		
		public function LookUpRandomWords($args) {
			if($this->handler->db_access->db_file_cache) {
				$lowercased_words = [];
				
				foreach($words_to_look_up as $word_to_look_up) {
					$lowercased_words[] = strtolower($word_to_look_up);
				}
				
				$db_results = $this->handler->db_access->db_file_cache->ReadCache([
					'directory'=>'dictionaries_admin',
					'arguments'=>['random_words'],
					'max_age'=>3600,
				]);
			}
			
			if(!is_array($db_results)) {
				if(!$this->db_link) {
					$this->DBStart();
				}
				
				$random_word_count = $args['randomwordcount'];
				
				if(!$random_word_count) {
					$random_word_count = 1000;
				}
				
				$sql = 'SELECT ';
				
				$sql .= ' Definition.id AS id, ';
				$sql .= ' Definition.Term AS Term, ';
				$sql .= ' Definition.Pronunciation AS Pronunciation, ';
				$sql .= ' Definition.PartOfSpeech AS PartOfSpeech, ';
				$sql .= ' Definition.Etymology AS Etymology, ';
				$sql .= ' Definition.Definition AS Definition, ';
				
				$sql .= ' Dictionary.id AS Dictionaryid, ';
				$sql .= ' Dictionary.Title AS DictionaryTitle, ';
				$sql .= ' Dictionary.Subtitle AS DictionarySubtitle, ';
				$sql .= ' Dictionary.ListTitle AS DictionaryListTitle, ';
				$sql .= ' Dictionary.Code AS DictionaryCode ';
				
				$sql .= ' FROM ';
				$sql .= ' Definition ';
				$sql .= ' JOIN Dictionary ON Definition.Dictionaryid = Dictionary.id ';
				
				$sql .= ' ORDER BY RAND() ';
				
				$sql .= ' LIMIT ' . $random_word_count;
				
				$fill_arrays_from_db_args = [
					'query'=>$sql,
					'sqlbindstring'=>'',
					'recordvalues'=>[],
				];
				
				$db_results = $this->FillArraysFromDB($fill_arrays_from_db_args);
				
				if($this->handler->db_access->db_file_cache) {
					if(count($db_results) !== 0) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'directory'=>'dictionaries_admin',
							'arguments'=>['random_words'],
							'data'=>$db_results,
						]);
					}
				}
			}
			$definitions = [];
			
			foreach ($db_results as $definition) {
				if(!$definitions[strtolower($definition['Term'])]) {
					$definitions[strtolower($definition['Term'])] = [];
				}
				
				$definitions[strtolower($definition['Term'])][] = $definition;
			}
			
			return $definitions;
		}
		
		public function GetDefinitionsCount($args) {
			if($this->handler->db_access->db_file_cache) {
				$definitions_count = $this->handler->db_access->db_file_cache->ReadCache([
					'directory'=>'dictionaries_admin',
					'arguments'=>['definitions_count'],
					'max_age'=>3600,
				]);
			}
			
			if(!is_array($definitions_count)) {
				if(!$this->db_link) {
					$this->DBStart();
				}
				
				$sql = 'SELECT COUNT(id) AS DefinitionCount FROM Definition;';
				
				$fill_arrays_from_db_args = [
					'query'=>$sql,
					'sqlbindstring'=>'',
					'recordvalues'=>[],
				];
				
				$definitions_count = $this->FillArraysFromDB($fill_arrays_from_db_args);
				
				if($this->handler->db_access->db_file_cache) {
					if(count($definitions_count) !== 0) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'directory'=>'dictionaries_admin',
							'arguments'=>['definitions_count'],
							'data'=>$definitions_count,
						]);
					}
				}
			}
			
			return $definitions_count[0]['DefinitionCount'];
		}
		
		public function GetWordsCount($args) {
			if($this->handler->db_access->db_file_cache) {
				$words_count = $this->handler->db_access->db_file_cache->ReadCache([
					'directory'=>'dictionaries_admin',
					'arguments'=>['words_count'],
					'max_age'=>3600,
				]);
			}
			
			if(!is_array($words_count)) {
				if(!$this->db_link) {
					$this->DBStart();
				}
				
				$sql = 'SELECT COUNT(DISTINCT Term) AS WordCount FROM Definition;';
				
				$fill_arrays_from_db_args = [
					'query'=>$sql,
					'sqlbindstring'=>'',
					'recordvalues'=>[],
				];
				
				$words_count = $this->FillArraysFromDB($fill_arrays_from_db_args);
				
				if($this->handler->db_access->db_file_cache) {
					if(count($words_count) !== 0) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'directory'=>'dictionaries_admin',
							'arguments'=>['words_count'],
							'data'=>$words_count,
						]);
					}
				}
			}
			
			return $words_count[0]['WordCount'];
		}
		
		public function GetDictionariesCount($args) {
			if($this->handler->db_access->db_file_cache) {
				$dictionaries_count = $this->handler->db_access->db_file_cache->ReadCache([
					'directory'=>'dictionaries_admin',
					'arguments'=>['dictionaries_count'],
					'max_age'=>3600,
				]);
			}
			
			if(!is_array($dictionaries_count)) {
				if(!$this->db_link) {
					$this->DBStart();
				}
				
				$sql = 'SELECT COUNT(id) AS DictionaryCount FROM Dictionary;';
				
				$fill_arrays_from_db_args = [
					'query'=>$sql,
					'sqlbindstring'=>'',
					'recordvalues'=>[],
				];
				
				$dictionaries_count = $this->FillArraysFromDB($fill_arrays_from_db_args);
				
				if($this->handler->db_access->db_file_cache) {
					if(count($dictionaries_count) !== 0) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'directory'=>'dictionaries_admin',
							'arguments'=>['dictionaries_count'],
							'data'=>$dictionaries_count,
						]);
					}
				}
			}
			
			return $dictionaries_count[0]['DictionaryCount'];
		}
		
			// Start/Stop the DB
			// -------------------------------------------------
		
		public function dictionary_db() {
			return 'alldictionaries';
		}
		
		public function DBStart() {
			error_reporting(E_ERROR);
			
			$this->db_link = new mysqli(
				ini_get('mysqli.default_host'),
				ini_get('mysqli.default_user'),
				ini_get('mysqli.default_pw'),
				$this->dictionary_db(),
				ini_get('mysqli.default_port'),
			);
			
			if($this->db_link->connect_errno) {
				$this->db_link = new mysqli(
					ini_get('mysqli.default_host'),
					ini_get('mysqli.default_user'),
					ini_get('mysqli.default_pw'),
					$this->dictionary_db(),
					ini_get('mysqli.default_port'),
				);
			}
			
			error_reporting(E_ERROR | E_WARNING | E_PARSE);
			
			$results = 1;
			$errors = [];
			
			if($this->db_link->connect_errno) {
				$results = 0;
				$errors[] = [
					'errornumber'=>$this->db_link->connect_errno,
					'errormessage'=>$this->db_link->connect_error,
				];
			}
			
			if($this->db_link->connect_error) {
				http_response_code(500);
				die ('Database unavailable. =(');
			}
			
			$this->db_link->set_charset('utf8');
			
			return [
				'results'=>$results,
				'errors'=>$errors,
			];
		}
		
		public function DBEnd() {
			if(!$this->db_link->connect_error && $this->db_link) {
				return mysqli_close($this->db_link);
			}
			
			return TRUE;
		}
		
		public function FillArraysFromDB($args) {
			$query = $args['query'];
			$sqlbindstring = $args['sqlbindstring'];
			$recordvalues = $args['recordvalues'];
			
			$prepare_line = __LINE__;	# Current line number
			$statement = $this->db_link->prepare($query);
			
			if($statement) {
				if($sqlbindstring) {
					$bind_arguments = [];
					$bind_arguments[] = $sqlbindstring;
					foreach ($recordvalues as $recordkey => $recordvalue) {
						$bind_arguments[] = & $recordvalues[$recordkey];
					}
					
					$bind_line = __LINE__;		# Current line number
					$bind_results = call_user_func_array(array($statement, 'bind_param'), $bind_arguments);
					
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
				$statement->execute();
				$result = $statement->get_result();
				
				if($result) {
					while ($row = $result->fetch_assoc()) {
						$format_row_args = [
							'row'=>$row,
						];
						$objects[] = $this->FillArraysFromDB_FormatRow($format_row_args);
					}
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
	}

?>
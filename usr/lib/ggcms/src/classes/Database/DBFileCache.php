<?php

	class DBFileCache {
		
			// Construction
			// -------------------------------------------------
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		public function DBFileCacheLocation() {
			return '/mnt/nyc01/ggcms_cache/mysql_db_file_cache';
		}
		
		public function DomainFileCacheLocation() {
			if($this->db_file_cache_location) {
				return $this->db_file_cache_location;
			}
			
			$domain_folder_name = $this->handler->ReverseDomainName([
				'domain'=>$this->handler->domain->primary_domain_lowercased,
			]);
		
			return $this->db_file_cache_location = $this->DBFileCacheLocation() . '/' . $domain_folder_name;
		}
		
		public function CacheLocation($args) {
			$type = $args['type'];
			$subtype = $args['subtype'];
			
			if($args['directory']) {
				return $this->DBFileCacheLocation() . '/' . $args['directory'];
			}
			
			$domain_directory = $this->DomainFileCacheLocation();
			
			if(strlen($subtype) === 0) {
				$full_directory = $domain_directory . '/' . $type;
			} else {			
				$full_directory = $domain_directory . '/' . $type . '/' . $subtype;
			}
			
			return $full_directory;
		}
		
		public function WriteCache($args) {
			$arguments = $args['arguments'];
			
			$full_directory = $this->CacheLocation($args);
			$blanks_data = $this->ReadCache_blanks(['full_directory'=>$full_directory]);
			$blanks_file_location = $full_directory . '_blanks.txt';
			$full_directory .= '/';
			
			$args['blanks'] = $blanks_data;
			
			$new_blanks = [];
			$blanks_data_hash = [];
			
			if(!$blanks_data || !is_array($blanks_data)) {
				$blanks_data = [];
			}

			foreach($blanks_data as $blank) {
				$blanks_data_hash[$blank] = TRUE;
			}
			
				/*
					Create the directory rather than requiring it.  This test used
					to be the whole condition: no directory, no write, and nothing
					anywhere created one.
				
					So the cache could be emptied but never refilled.  Clearing a
					domain's row cache removed its subdirectories, and from then on
					every write was a silent no-op and every read a miss -- the
					cache stayed enabled, reported nothing wrong, and served no
					purpose.  revoltlib was found in exactly that state, its cache
					directory emptied on 31 August 2026 and still empty two days
					and many thousands of renders later, while every other domain
					carried subdirectories dating from 2024.
				*/

			if(!is_dir($full_directory)) {
				@mkdir($full_directory, 0755, TRUE);
			}

			if(is_dir($full_directory)) {
				$args['full_directory'] = $full_directory;
				
				if($args['directory']) {
					$args['argument'] = $args['arguments'][0];
					$args['argument_data'] = $args['data'];
					$this->WriteCache_Item($args);
				} else {
					$arguments = array_unique($arguments);
					sort($arguments);
					$field = $args['field'];
					
					$argument_cache = [];
					
					$data = $args['data'];
					$field = $args['field'];
					
					if(!$field) {
						$field = 'id';
					}
					
					foreach($data as $datum) {
						$field_value = $datum[$field];
						
						if(strlen($field_value) === 0) {
							$field_value = $arguments[0];
						}
						
						if(!$argument_cache[$field_value]) {
							$argument_cache[$field_value] = [];
						}
						
						$argument_cache[$field_value][] = $datum;
					}
					
					foreach($arguments as $argument) {
						$args['argument'] = $argument;
						if(
							(
								is_array($argument_cache[$argument]) &&
								$argument_cache[$argument][0] != 0
							)
							|| !is_file($blanks_file_location)) {
							$args['argument_data'] = $argument_cache[$argument];
							$this->WriteCache_Item($args);
						} elseif(!$blanks_data_hash[$args['argument']]) {
							$new_blanks[$args['argument']] = TRUE;
						}
					}
				}
			}
			
			$args['new_blanks'] = $new_blanks;
			$args['blanks_file'] = $blanks_file_location;
			
			$this->WriteCache_Blank($args);
			
			return TRUE;
		}
		
		public function WriteCache_Blank($args) {
			$blanks = $args['blanks'];
			$new_blanks = $args['new_blanks'];
			$blanks_file = $args['blanks_file'];
			
			if(!is_file($blanks_file)) {		// means that this type of cache doesn't have an option for a blanks-file
				return FALSE;		// see the CLI tool for enabling db file cache for more info
			}
			
			if($new_blanks) {
				$new_blanks_count = count($new_blanks);
				
				if($new_blanks_count !== 0) {
					$new_blanks_values = array_keys($new_blanks);
					
					/*
					$newest_blanks = [];
					if($blanks) {
						$newest_blanks = array_merge($blanks, $new_blanks_values);
					} else {
						$newest_blanks = $new_blanks_values;
					}
					sort($newest_blanks);
					*/
					
					if($blanks) {
						$newest_blanks = $this->insertArrayIntoSortedArray([
							'arr1'=>$blanks,
							'arr2'=>$new_blanks_values,
						]);
					} else {
						$newest_blanks = $new_blanks_values;
					}
					
					$newest_blanks_writeable = implode("\n", $newest_blanks);
					file_put_contents($blanks_file, $newest_blanks_writeable, LOCK_EX);
				}
			}
			
			return TRUE;
		}
		
		public function WriteCache_Item($args) {
			$type = $args['type'];
			$subtype = $args['subtype'];
			$field = $args['field'];
			$data = $args['data'];
			
			$full_directory = $args['full_directory'];
			$argument = $args['argument'];
			$argument_data = $args['argument_data'];
			
			$file_name = $argument;
			
			if(strlen($file_name) < $this->FileNameMax()) {
				$file_location = $full_directory . $file_name;
				
				if(!is_file($file_location)) {
					$json_data = json_encode($argument_data);
					
					$file_handle_for_writing = fopen($file_location, 'a+');
					
					if($file_handle_for_writing) {
						while (!flock($file_handle_for_writing, LOCK_EX)) {
							usleep(round(rand(0, 100)*1000)); //0-100 milliseconds
						}
						
						fwrite($file_handle_for_writing, $json_data);
						flock($file_handle_for_writing, LOCK_UN);
						fclose($file_handle_for_writing);
					}
				}
			}
			
			return TRUE;
		}
		
		public function removeBomUtf8($s){
			if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))){
				return substr($s,3);
			} else {
				return $s;
			}
		}
		
		/*
			The blanks file is asked about once per record, and a page carries
			dozens of records per type, so a type whose blanks file does not
			exist cost one stat every single time it was consulted.

			Measured on the revoltlib front page: 8,068 stat calls in one
			render, 7,370 of them failing, all asking the same eight questions
			-- Tag_blanks.txt, Quote_blanks.txt, Link_blanks.txt and the rest --
			seventy-four times each.

			A file cannot appear or vanish part-way through a request, so the
			answer is remembered for the life of the request.  Including the
			answer "there is no such file", which is the expensive one.
		*/

		public function ReadCache_blanks($args) {
			$full_directory = $args['full_directory'];

			$full_directory_blanks = $full_directory . '_blanks.txt';

			if(!is_array($this->blanks_already_read)) {
				$this->blanks_already_read = [];
			}

			if(array_key_exists($full_directory_blanks, $this->blanks_already_read)) {
				return $this->blanks_already_read[$full_directory_blanks];
			}

			$blanks_data = FALSE;

			if(is_file($full_directory_blanks)) {
				$blanks = $this->removeBomUtf8(file_get_contents($full_directory_blanks));

				if(strlen($blanks) !== 0) {
					$blanks_data = explode("\n", $blanks);
				}
			}

			$this->blanks_already_read[$full_directory_blanks] = $blanks_data;

			return $blanks_data;
		}
		
		public function ReadCache($args) {
			$type = $args['type'];
			$subtype = $args['subtype'];
			$sub2type = $args['sub2type'];
			$arguments = $args['arguments'];
			$max_age = $args['max_age'];
			
			$full_directory = $this->CacheLocation($args);
			
			if(!is_dir($full_directory)) {
				return FALSE;
			}
			
			$args['full_directory'] = $full_directory;
			
			$arguments = array_unique($arguments);
			sort($arguments);
			
			$arguments_length = count($arguments);
			
			$all_data = [];
			
			$blanks_data = $this->ReadCache_blanks($args);
			
			$args['full_directory'] .= '/';
			$args['blanks'] = $blanks_data;
			
			for($i = 0; $i < $arguments_length; $i++) {
				$argument = $arguments[$i];
				$args['argument'] = $argument;
				$datum = $this->ReadCache_Item($args);
				
				if(!is_array($datum)) {
					return FALSE;
				}
				
				foreach($datum as $datum_piece) {
					if(is_array($datum_piece)) {
						$all_data[$datum_piece['id']] = $datum_piece;
					}
				}
			}
			
			if(count($all_data) === 0) {
				return [];
			}
			
			$all_data_values = array_values($all_data);
			
			return $all_data_values;
		}
		
		public function ReadCache_Item($args) {
			$type = $args['type'];
			$subtype = $args['subtype'];
			$sub2type = $args['sub2type'];
			$arguments = $args['arguments'];
			$max_age = $args['max_age'];
			
			$full_directory = $args['full_directory'];
			$argument = $args['argument'];
			$blanks = $args['blanks'];
			
			if($blanks) {
				#if($this->findElementInSortedArray(['array'=>$blanks, 'element'=>$argument,])) {
				if(in_array($argument, $blanks)) {
					return [];
				}
			}
			
			$file_name = $argument;
			
			if(strlen($file_name) < $this->FileNameMax()) {
				$file_location = $full_directory . $file_name;
				
				if(is_file($file_location)) {
					$read_valid = TRUE;
					if($max_age) {
						$age_difference = time() - filemtime($file_location);
						if($age_difference > $max_age) {
							unlink($file_location);
							$read_valid = FALSE;
						}
					}
					
					if($read_valid) {
						$data = json_decode(file_get_contents($file_location), TRUE);
						
						return $data;
					}
				}
			}
			
			return FALSE;
		}
		
		public function FileNameMax() {
			return 128;
		}
	
		public function findElementInSortedArray($args) {
			$element = $args['element'];
			$array = $args['array'];
			
			$array_count = count($array);
			
			if($array_count === 0) {
				return FALSE;
			}
			
			if($array[0] === $element) {
				return TRUE;
			}
			
			$last_index = $array_count - 1;
			
			$half_size = ceil($array_count / 2) + 1;
			$middle_index = floor($array_count / 2);
			$found_item = FALSE;
			
			$iterations = 0;
			$limit = 100;
			
			$last_middle_index = -1;
			$last_it = FALSE;
			
		#	if($element == 39) {
		#	print("\n\n");
		#	print("BT: HALF SIZE????" . $half_size . "|\n\n");
		#	print("BT: MIDDLE INDEX???" . $middle_index . "|\n\n");
		#	}
			while(!$found_item) {
				$middle_item = $array[$middle_index];
				
				if($middle_item == $element) {
					return TRUE;
				}
				
				if($last_middle_index === $middle_index) {
					$found_item = TRUE;
				} else {
				#	if($element == 39) {
				#		print("INDEX: " . $middle_index . " | Item: " . $middle_item . " HALF: " . $half_size . "|\n\n");
				#		print_r($array);
				#	}
					if($half_size == 1 && $last_it === FALSE) {
						$half_size = 1;
						$last_it = TRUE;
					} elseif($half_size == 1 || $half_size == 0) {
						$half_size = 0;
					} else {
						$half_size = ceil($half_size / 2);
					}
					
					$last_middle_index = $middle_index;
					if($middle_item > $element) {
						$middle_index -= $half_size;
					} elseif($middle_item < $element) {
						$middle_index += $half_size;
					}
					
					if($middle_index > $last_index) {
						$middle_index = $last_index;
					} elseif($middle_index < 0) {
						$middle_index = 0;
					}
				}
				
				$iterations++;
				
				if($iterations === $limit) {
					$found_item = TRUE;
				}
			}
			
			return FALSE;
		}
	
		public function insertArrayIntoSortedArray($args) {
			$arr1 = $args['arr1'];
			$arr2 = $args['arr2'];
			
			$new_array = $arr1;
			
			foreach($arr2 as $arr2_item) {
				$new_array = $this->insertElementIntoSortedArray([
					'array'=>$new_array,
					'element'=>$arr2_item,
				]);
			}
			
			return $new_array;
		}
		
		public function insertElementIntoSortedArray($args) {
			$element = $args['element'];
			$array = $args['array'];
			
			$array_count = count($array);
			
			if($array_count === 0) {
				return [$element];
			}
			
			$final_index = $array_count - 1;
			
			$final_element = $array[$final_index];
			
			if($final_element < $element) {
				$array[] = $element;
				return $array;
			} elseif($final_element === $element) {
				return $array;
			}
			
			$first_element = $array[0];
			
			if($first_element > $element) {
				array_unshift($array, $element);
				return $array;
			} elseif($first_element === $element) {
				return $array;
			}
			
			$half_size = ceil($array_count / 2);
			$middle_index = floor($array_count / 2);
			$found_item = FALSE;
			
			$iterations = 0;
			$limit = 100;
			
			$last_middle_index = -1;
			$last_it = FALSE;
			
			while(!$found_item) {
				$middle_item = $array[$middle_index];
				
				if($middle_item === $element) {
					return $array;	// element is already in the array
				}
				
				if($last_middle_index === $middle_index) {
					$found_item = TRUE;
				} else {
					if($half_size == 1 && $last_it === FALSE) {
						$half_size = 1;
						$last_it = TRUE;
					} elseif($half_size == 1 || $half_size == 0) {
						$half_size = 0;
					} else {
						$half_size = ceil($half_size / 2);
					}
					
					$last_middle_index = $middle_index;
					if($middle_item > $element) {
						$middle_index -= $half_size;
					} elseif($middle_item < $element) {
						$middle_index += $half_size;
					}
					
					if($middle_index > $final_index) {
						$middle_index = $final_index;
					} elseif($middle_index < 0) {
						$middle_index = 0;
					}
				}
				
				$iterations++;
				
				if($iterations === $limit) {
					$found_item = TRUE;
				}
			}
			
			if($middle_index > $final_index) {
				$array[] = $element;
				return $array;
			}
			
			array_splice($array, $middle_index, 0, [$element,]); 
			
			return $array;
		}
	}

?>
<?php

	class DBFileCache {
		public $handler;
		public $db_file_cache_location;
		public $blanks_already_read;
		
		
			// Construction
			// -------------------------------------------------
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
			// DBFileCacheLocation()
			// Tests: DBFileCacheTest::testDBFileCacheLocation()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
		public function DBFileCacheLocation() {
			return '/mnt/nyc01/ggcms_cache/mysql_db_file_cache';
		}
		
			// DomainFileCacheLocation()
			// Tests: DBFileCacheTest::testDomainFileCacheLocation()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
		public function DomainFileCacheLocation() {
			if($this->db_file_cache_location) {
				return $this->db_file_cache_location;
			}
			
			$domain_folder_name = $this->handler->ReverseDomainName([
				'domain'=>$this->handler->domain->primary_domain_lowercased,
			]);
		
			return $this->db_file_cache_location = $this->DBFileCacheLocation() . '/' . $domain_folder_name;
		}
		
			// CacheLocation()
			// Tests: DBFileCacheTest::testCacheLocation()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
		
			// WriteCache()
			// Tests: DBFileCacheTest::testWriteCache()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
				
				/*
					Which directory the cache lives in does not say anything about how
					its rows should be laid out, and this used to branch on it as
					though it did.  `directory` only selects a path -- see
					CacheLocation() -- so every caller that passed one got all of its
					rows written into a single file named after the FIRST argument,
					while ReadCache() went on expecting one file per argument.
					
					A dictionary lookup of twenty words therefore wrote one file and
					then missed on the other nineteen, every time, for ever: the read
					returns FALSE the moment one argument is absent, so the whole
					lookup went to the database on every request.  wordweight is
					113,609 definitions behind 110,000 pages and had 699 cache files.
					
					The real question is whether there is a field to lay the rows out
					by.  With one, they belong in a file each, keyed by that field.
					Without one, the data is a single blob under a name of the
					caller's choosing -- a count, a random sample -- and belongs in
					one file, which is what the counts in Dictionary rely on.
				*/
				
				if($args['directory'] && !$args['field']) {
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
							is_array($argument_cache[$argument]) &&
							(
								$argument_cache[$argument][0] != 0
								|| !is_file($blanks_file_location)
							)
						) {
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
		
			// WriteCache_Blank()
			// Tests: DBFileCacheTest::testWriteCache_Blank()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
		
			// WriteCache_Item()
			// Tests: DBFileCacheTest::testWriteCache_Item()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
		public function WriteCache_Item($args) {
			$type = $args['type'];
			$subtype = $args['subtype'];
			$field = $args['field'];
			$data = $args['data'];
			
			$full_directory = $args['full_directory'];
			$argument = $args['argument'];
			$argument_data = $args['argument_data'];
			
			$file_name = $argument;
			
			if($this->IsCacheFileName(['filename'=>$file_name])) {
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
		

			// Cache invalidation
			// -------------------------------------------------

		// DeleteCache()
		// Tests: DBFileCacheTest::testDeleteCache()
		// Test file: tests/src/classes/Database/DBFileCacheTest.php
		/*
			The row cache had no way to forget anything until 3 September 2026.
			WriteCache_Item writes only when the file is absent, so a record
			cached once was served forever; the only remedy was clear_db_cache.php,
			which removes all of it.  On the production host that is 2.4 GB and
			593,978 files, every one of which then has to be re-derived.

			The bug this leaves is quiet and wrong in the worst direction: edit an
			entry's title and the page cache correctly flushes, the page then
			re-renders, reads the STALE row, and writes a fresh page cache holding
			the old title.  Caching faithfully preserves the mistake.

			Deleting is the whole of the fix.  Because writes are create-only
			there is no update path to get wrong -- unlink the file and the next
			render refills it from the database.
		*/

		public function DeleteCache($args) {
			$arguments = $args['arguments'];

			if(!is_array($arguments) || count($arguments) === 0) {
				return 0;
			}

			$full_directory = $this->CacheLocation($args);
			$blanks_file_location = $full_directory . '_blanks.txt';
			$full_directory .= '/';

			$deleted = 0;

			foreach($arguments as $argument) {
				$file_name = (string) $argument;

					/*
						Mirror WriteCache_Item's guard exactly.  A key at or over
						the length limit was never written, so looking for it is
						a stat that can only fail.
					*/

				if(!$this->IsCacheFileName(['filename'=>$file_name])) {
					continue;
				}

				$file_location = $full_directory . $file_name;

				if(is_file($file_location)) {
					@unlink($file_location);

					$deleted++;
				}
			}

			$this->DeleteCache_Blank([
				'blanks_file'=>$blanks_file_location,
				'arguments'=>$arguments,
			]);

			return $deleted;
		}

		/*
			Unlinking a file is not enough on its own, and this is the half that
			would have been missed.

			_blanks.txt lists the ids KNOWN to have no rows, so that an empty
			result is a hit rather than a permanent miss.  Consider an entry with
			no image: its id sits in Image_blanks.txt and there is no file to
			delete.  Give it its first image and unlinking achieves nothing --
			the blanks file still asserts the entry has none, and the image never
			appears at all.

			So an id leaving the blank state must leave the blanks file too.
		*/

		public function DeleteCache_Blank($args) {
			$blanks_file = $args['blanks_file'];
			$arguments = $args['arguments'];

				/*
					No file means this cache type has blanks switched off; see
					the CLI tool for enabling db file cache.  WriteCache_Blank
					makes the same test for the same reason.
				*/

			if(!is_file($blanks_file)) {
				return FALSE;
			}

			$blanks = $this->removeBomUtf8(file_get_contents($blanks_file));

			if(strlen($blanks) === 0) {
				return FALSE;
			}

			$blanks = explode("\n", $blanks);

			$removing = [];

			foreach($arguments as $argument) {
				$removing[(string) $argument] = TRUE;
			}

			$kept = [];

			foreach($blanks as $blank) {
				if(!$removing[$blank]) {
					$kept[] = $blank;
				}
			}

			if(count($kept) === count($blanks)) {
				return FALSE;
			}

			file_put_contents($blanks_file, implode("\n", $kept), LOCK_EX);

				/*
					ReadCache_blanks remembers its answer for the life of the
					request, keyed by this same path.  A write that clears a
					blank and then reads it back within one request must not be
					handed the memoised list it just invalidated.
				*/

			if(is_array($this->blanks_already_read)) {
				unset($this->blanks_already_read[$blanks_file]);
			}

			return TRUE;
		}

		// DeleteCache_PathSegment()
		// Tests: DBFileCacheTest::testDeleteCache_PathSegment()
		// Test file: tests/src/classes/Database/DBFileCacheTest.php
		/*
			ggcms_RecordTree is the one type not keyed by a record id.  Its key is
			the URL path, '%2F'-joined -- 'anarchism%2F15-post-primitivist-theses'
			-- and the file holds the whole ancestor spine: every Entry on the
			path with its columns, plus the Assignment that joined it.

			So an entry's Title is embedded in the file of every descendant path,
			not merely its own.  Editing 'Anarchism' on revoltlib makes 3,543 of
			these stale.  There is no id to look up, but there does not need to be
			one: if an entry's Code appears as a segment of a path, that entry is
			on that path's spine.  The key alone answers the question, with no
			query and no ancestor walk.
		*/

		public function DeleteCache_PathSegment($args) {
			$segment = (string) $args['segment'];

			if(strlen($segment) === 0) {
				return 0;
			}

			$full_directory = $this->CacheLocation($args) . '/';

			if(!is_dir($full_directory)) {
				return 0;
			}

			$directory_handle = @opendir($full_directory);

			if(!$directory_handle) {
				return 0;
			}

			$deleted = 0;

			while(($file_name = readdir($directory_handle)) !== FALSE) {
				if(($file_name === '.') || ($file_name === '..')) {
					continue;
				}

				if(!in_array($segment, explode('%2F', $file_name), TRUE)) {
					continue;
				}

				$file_location = $full_directory . $file_name;

				if(is_file($file_location)) {
					@unlink($file_location);

					$deleted++;
				}
			}

			closedir($directory_handle);

			return $deleted;
		}

		/*
			Every file under a type, for the case where the affected keys cannot
			be derived.  Used only for ggcms_RecordTree on an Entry write that
			does not name a Code.
		*/

		public function DeleteCache_All($args) {
			$full_directory = $this->CacheLocation($args) . '/';

			if(!is_dir($full_directory)) {
				return 0;
			}

			$directory_handle = @opendir($full_directory);

			if(!$directory_handle) {
				return 0;
			}

			$deleted = 0;

			while(($file_name = readdir($directory_handle)) !== FALSE) {
				if(($file_name === '.') || ($file_name === '..')) {
					continue;
				}

				$file_location = $full_directory . $file_name;

				if(is_file($file_location)) {
					@unlink($file_location);

					$deleted++;
				}
			}

			closedir($directory_handle);

			return $deleted;
		}

		/*
			The subtypes a type has actually been written under, read from the
			cache tree itself.

			ggcms_EntryChildRecords is written under one subtype per child table,
			plus 'TextBody_short' for the truncated form the index pages ask for
			and 'Associated' for the reverse association.  Asking the ORM for
			that list would mean reaching through $handler->orm, which is
			assigned inside a method rather than the constructor and is null on
			an ordinary request.  The directory listing cannot go stale and
			cannot be null.
		*/

		public function DeleteCache_Subtypes($args) {
			$full_directory = $this->CacheLocation($args) . '/';

			if(!is_dir($full_directory)) {
				return [];
			}

			$directory_handle = @opendir($full_directory);

			if(!$directory_handle) {
				return [];
			}

			$subtypes = [];

			while(($file_name = readdir($directory_handle)) !== FALSE) {
				if(($file_name === '.') || ($file_name === '..')) {
					continue;
				}

				if(is_dir($full_directory . $file_name)) {
					$subtypes[] = $file_name;
				}
			}

			closedir($directory_handle);

			return $subtypes;
		}

			// removeBomUtf8()
			// Tests: DBFileCacheTest::testRemoveBomUtf8()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
		public function removeBomUtf8($s){
			if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))){
				return substr($s,3);
			} else {
				return $s;
			}
		}
		
		// ReadCache_blanks()
		// Tests: DBFileCacheTest::testReadCache_blanks()
		// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
		
			// ReadCache()
			// Tests: DBFileCacheTest::testReadCache()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
		
			// ReadCache_Item()
			// Tests: DBFileCacheTest::testReadCache_Item()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
			
			if($this->IsCacheFileName(['filename'=>$file_name])) {
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
		
			// FileNameMax()
			// Tests: DBFileCacheTest::testFileNameMax()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
		public function FileNameMax() {
			return 128;
		}
		
		// IsCacheFileName()
		// Tests: DBFileCacheTest::testIsCacheFileName()
		// Test file: tests/src/classes/Database/DBFileCacheTest.php
		/*
			An argument becomes a file name, and some arguments come from a
			request: view.php looks ?search= up in the dictionary cache, so
			?search=../../X read whatever JSON sat at X two directories above
			the cache.  A name is one path segment or it is nothing -- no
			slash, no NUL, not . or .. -- and the reads, writes and deletes
			all ask here.
		*/
		
		public function IsCacheFileName($args) {
			$file_name = (string) $args['filename'];
			
			if((strlen($file_name) === 0) || (strlen($file_name) >= $this->FileNameMax())) {
				return FALSE;
			}
			
			if(strpbrk($file_name, "/\0") !== FALSE) {
				return FALSE;
			}
			
			return (($file_name !== '.') && ($file_name !== '..'));
		}
	
			// findElementInSortedArray()
			// Tests: DBFileCacheTest::testFindElementInSortedArray()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
	
			// insertArrayIntoSortedArray()
			// Tests: DBFileCacheTest::testInsertArrayIntoSortedArray()
			// Test file: tests/src/classes/Database/DBFileCacheTest.php
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
		
		// insertElementIntoSortedArray()
		// Tests: DBFileCacheTest::testInsertElementIntoSortedArray()
		// Test file: tests/src/classes/Database/DBFileCacheTest.php
		/*
			Where the element belongs, found by halving: the first position
			whose item is not less than it.  This used to track a shrinking
			step by hand and put one element in four in the wrong place --
			21 before 18 -- so the blanks file this keeps "sorted" was not.
			Nothing yet relies on the order, since reads use in_array(), but
			findElementInSortedArray() above was written to.
			
			Equality is == because the blanks file is read back as strings
			and new blanks arrive as integers; "39" and 39 are one id.
		*/
		
		public function insertElementIntoSortedArray($args) {
			$element = $args['element'];
			$array = $args['array'];
			
			$low = 0;
			$high = count($array);
			
			while($low < $high) {
				$middle = ($low + $high) >> 1;
				
				if($array[$middle] < $element) {
					$low = $middle + 1;
				} else {
					$high = $middle;
				}
			}
			
			if(($low < count($array)) && ($array[$low] == $element)) {
				return $array;	// element is already in the array
			}
			
			array_splice($array, $low, 0, [$element,]);
			
			return $array;
		}
	}

?>
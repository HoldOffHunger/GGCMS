<?php

	class module_entrylist extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->record = $this->that->entry;
			
			$this->entrydate = $args['entrydate'];
			
			return TRUE;
		}
		
		public function Display($args) {
			$children = $args['children'];
			
			if(!$children) {
				$children = $this->that->children;	# oh, you know, this 'n' that (joke will not get old)
			}
			
			$child_id_hash = [];
			
			$child_ids = array_keys($child_id_hash);
			$child_ids_count = count($child_ids);
			
			if($child_ids_count > 0) {
				$entry_id_string = implode(', ', $child_ids);
				$child_entries = $this->that->handler->db_access->RunQuery([
					'sql'=>'SELECT Entry.* FROM Entry WHERE id IN(' . $entry_id_string . ') ',
				]);
				
				$child_entries_hash = [];
				
				foreach($child_entries as $child_entry) {
					$child_entries_hash[$child_entry['id']] = $child_entry;
				}
				
				foreach($children as $child_key => $child) {
					foreach($child['association'] as $child_association_key => $child_association) {
						$children[$child_key]['association'][$child_association_key]['entry'] = $child_entries_hash[$child_association['ChosenEntryid']];
					}
				}
			}
			
			return $this->DisplayChildren($args);
		}
		
		public function DisplayChildGroups($args) {
			$children = $args['children'];
			$childgroups = $args['childgroups'];
			$childgroupscount = count($childgroups);
			
			$childgroupsdisplayed = [];
			
			
			
		#	print("BT: " . $childgroupscount);
			ksort($childgroups);
			
			foreach($childgroups as $childgroupkey => $childgroup) {
				$first_child = $childgroup['children'][0];
				
				$child_group_url_so_far = '/';
				$child_group_url = [];

				$quote_parent = false;				
				$index = 0;
				
				foreach($first_child['parents'] as $first_child_parent) {
					if($first_child_parent['Code'] == 'quotes') {
						$quote_parent = true;
					}
				}
				
				foreach($first_child['parents'] as $first_child_parent) {
					if($first_child_parent['Code'] == 'quotes') {
						$quote_parent = true;
					} else {
						if($first_child_parent['Code']) {
							$child_group_url_so_far .= $first_child_parent['Code'] . '/';
							
							if($quote_parent && $index == 0) {
								$child_group_url_so_far .= 'quotes/';
							}
							
							$child_group_url[] = '<a target="_parent" href="' . $child_group_url_so_far . 'view.php">' . $first_child_parent['Title'] . '</a>';
						}
					}
					$index++;
				}
				
				if($childgroupscount > 1 && !$childgroupsdisplayed[$childgroupkey]) {
					print('<h3 class="margin-0px horizontal-cent font-family-arial" style="background-color:white;border:2px solid black; margin:5px;">');
					$record = $this->record;
					if($quote_parent) {
						print($record['Title'] . ' Quotes on ' );
					}
	#				print_r($record);
					print(implode(' &gt;&gt; ', $child_group_url));
				//	print('<a href="/' . $childgroupkey . '/view.php">');
				//	print($childgroupkey);
				//	print('</a>');
					$childgroupsdisplayed[$childgroupkey] = true;
					print('</h3>');
				}
				$args['children'] = $childgroup['children'];
				$this->DisplayChildren($args);
			}
			
			return TRUE;
		}
		
		public function DisplayChildren($args) {
			$children = $args['children'];
			$title_prefix = $args['title_prefix'];
			$title_suffix = $args['title_suffix'];
			
		#	print("<!-- BT: CHIL! -->");
			
			foreach($children as $child) {
				if($child['entry']) {
					$child = $child['entry'];
				}
				print('<div class="horizontal-left width-100percent background-color-gray14 border-2px margin-top-5px">');
				print('<div class="margin-2px">');
				
				unset($display_image);
				
				if($child['image']) {
					$child_images = $child['image'];
					$child_image_count = count($child_images);
					if($child_image_count) {
						shuffle($child_images);
						$child_image = $child_images[0];
						$display_image = $child_image;
					}
				}
				
				if(!$display_image && $this->that->master_record['image'] && count($this->that->master_record['image']) > 0) {
					$display_image = $this->that->master_record['image'][0];
				}
				
				$codes = [];
				if($child['parents']) {
					$parent_count = count($child['parents']);
					foreach($child['parents'] as $parent) {
						if($parent['Code'] && $parent['id'] !== $child['id']) {
							$codes[] = $parent['Code'];
						}
					}
					
					$codes[] = $child['Code'];
					
					$child_title = '<a target="_parent" href="/' . implode('/', $codes) . '/view.php">';
				} else {
					$child_title = '<a target="_parent" href="' . $child['Code'] . '/view.php">';
				}
				
			#	print("<!-- BT:!!!!!!!\n\n");
				
			#	print($child_title);
				#print_r($child['parents']);
			#	print($child_title);
				
			#	print("\n\n-->");
				if($display_image) {
					print('<div class="border-2px background-color-gray15 margin-5px float-left">');
					print('<div class="border-2px background-color-gray15 margin-5px float-left">');
					print('<div class="height-100px width-100px background-color-gray0">');
					print('<div class="vertical-specialcenter">');
					if(count($codes)) {
						$link = '/' . implode('/', $codes);
					} else {
						$link = $child['Code'];
					}
					print('<a target="_parent" href="' . $link . '/view.php">');
					print('<img class="horizontal-center" width="');
					print(ceil($display_image['IconPixelWidth'] / 2));
					print('" height="');
					print(ceil($display_image['IconPixelHeight'] / 2));
					print('" src="');
					print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
					print('/image/');
					print(implode('/', str_split($display_image['FileDirectory'])));
					print('/');
					print($display_image['IconFileName']);
					print('">');
					print('</a>');
					print('</div>');
					print('</div>');
					print('</div>');
					print('</div>');
				}
				
				if($child['ListTitle']) {
					$good_child_title = $child['ListTitle'];
				} else {
					$good_child_title = $child['Title'];
				}
				
				if(strlen($good_child_title) > 40) {
					$good_child_title = mb_substr($good_child_title, 0, 40, 'utf-8') . '...';
				}
				
				if($title_prefix) {
					$good_child_title = $title_prefix . $good_child_title;
				}
				
				if($title_suffix) {
					$good_child_title .= $title_suffix;
					
					$parent_count = count($child['parents']);
					$good_grandparent = $child['parents'][$parent_count - 2];
					$good_parent = $child['parents'][$parent_count - 3];
					$good_greatgrandparent = $child['parents'][$parent_count - 4];
					
					$good_child_title .= $good_parent['Title'] . ' &amp; ' . $good_grandparent['Title'];
				}
				
				$child_title .= $good_child_title;
				
				if($child['Subtitle']) {
					$good_child_subtitle = $child['Subtitle'];
					
					if(strlen($good_child_subtitle) > 30) {
						$good_child_subtitle = mb_substr($good_child_subtitle, 0, 30, 'utf-8') . '...';
					}
					$child_title .= ' : ';
					$child_title .= $good_child_subtitle;
				}
				
			#	$child_title = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $child_title);
		#		$child_title = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $child_title);
#				$child_title = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $child_title);
				
				$child_title .= '</a>';
				
				if($child['textbody']) {
					$text_bodies = $child['textbody'];
					
					$text_body_count = count($text_bodies);
					if($text_body_count) {
						$first_textbody = $text_bodies[0];
						
						$div_mouseover .= number_format($first_textbody['WordCount']) . ' Words / ' . number_format($first_textbody['CharacterCount']) . ' Characters';
					}
				}
				
				print('<div class="border-2px background-color-gray15 margin-5px float-left"');
				if($this->that->handler->authentication->user_session['UserAdmin.id']) {
					print(' title="id: ');
					print($child['id']);
					print('"');
				}
				print('>');
				print('<h2 class="padding-0px margin-5px horizontal-left font-family-tahoma">');
				print($child_title);
				print('</h2>');
				print('</div>');
				
				if($this->that->handler->authentication->user_session['UserAdmin.id'] && !$thingtoset) {
		#			$thingtoset = true;
		#			print("<PRE>");
		#			print_r($child);
		#			print("</PRE>");
				}
				
				if($this->that->handler->authentication->user_session['UserAdmin.id']) {
					print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-left" title="id: ');
					print($child['id']);
					print('">');
					print('<div class="margin-2px">');
					if(count($codes)) {
						$link = '/' . implode('/', $codes);
					} else {
						$link = $child['Code'];
					}
					print('<b><a target="_parent" href="' . $link . '/modify.php?action=Edit">Edit</a></b> ');
					print('</div>');
					print('</div>');
				}
				
				if($args['show_associations']) {
					$associated = $child['associated'];
					if($associated && count($associated) > 0) {
						$writings = [];
						$quotes = [];
						
						$cancelled = 0;
						
						foreach($associated as $single_associated) {
							$associated_entry = $single_associated['entry'];
							$associated_entry_parents = $associated_entry['parents'];
							
							foreach($associated_entry_parents as $associated_entry_parent) {
								if($associated_entry_parent['Code'] === 'writings') {
									$writings[] = $single_associated;
								} elseif($associated_entry_parent['Code'] === 'quotes') {
									$quotes[] = $single_associated;
								}
							}
						}
						
						$writings_count = count($writings);
						$quotes_count = count($quotes);
						
						if($quotes_count > 0) {
							print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-right">');
							print('<div class="margin-2px">');
							
							print('<strong>');
							
							print(number_format($quotes_count));
							print(' Quotes');
							
							print('</strong>');
							
							print('</div>');
							print('</div>');
						}
						
						if($writings_count > 0) {
							print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-right">');
							print('<div class="margin-2px">');
							
							print('<strong>');
							
							print(number_format($writings_count));
							print(' Writings');
							
							print('</strong>');
							
							print('</div>');
							print('</div>');
						}
					}
				}
				
				if($child['association'] && count($child['association']) > 0) {
					$writings = [];
					$roles = [];
					
					$cancelled = 0;
					
					foreach($child['association'] as $child_association) {
						if($child_association['ChosenEntryid'] == $this->that->entry['id']) {
							$cancelled = 1;
						}
						if($child_association['Type'] == 'Writing') {
							$writings[] = $child_association;
						} elseif($child_association['Type'] == 'Role') {
							$roles[] = $child_association;
						}
					}
					
					// $child['association'][0]['entry']['Title']) {
					
					
						
					
					if(count($roles) > 0 && ($args['list_author'] || $cancelled == 0)) {
						print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-left">');
						print('<div class="margin-2px">');
						
						print('by ');
						print('<strong>');
						
						$printable_roles = [];
						
					#	print($this->that->entry['id']);
					#	print_r($roles[0]['ChosenEntryid']);
						
						foreach($roles as $role) {	# bonjour!  we meet again!  en guarde!
							$role_info = '<a target="_parent" href="/people/' . $role['entry']['Code'] . '/view.php">';	# href=>"/" means I'm the best
							$role_info .= $role['entry']['Title'];
							if($role['entry']['Subtitle']) {
								$child_title = $role['entry']['Subtitle'];
								
								if(strlen($child_title) > 30) {
									$child_title = substr($child_title, 0, 30) . '...';
								}
								
								$role_info .= $child_title;
							}
							$role_info .= '</a>';
							
							$printable_roles[] = $role_info;
						}
						
						print(implode(', ', $printable_roles));
						
						print('</strong>');
						
						print('</div>');
						print('</div>');
					}
					
					if($args['parents'] == 2) {
						print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-left">');
						print('<div class="margin-2px">');
						
						print('listed in');
						print('<strong>');
						print(' ');
						
						$last_parent = $child['parents'][$parent_count - 2];
						$second_to_last_parent = $child['parents'][$parent_count - 3];
						$third_to_last_parent = $child['parents'][$parent_count - 5];
						
						$parents_codes = $codes;
						array_pop($parents_codes);
						
						print('<a target="_parent" href="/' . implode('/', $parents_codes) . '/view.php">');
						print($third_to_last_parent['Title']);
						print(' :: ');
						print($second_to_last_parent['Title']);
						print(' :: ');
						print($last_parent['Title']);
						print('</a>');
						
						print('</strong>');
						
						print('</div>');
						print('</div>');
					}
					
					if(!$args['list_author'] && (count($writings) > 0)) {
						print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-left">');
						print('<div class="margin-2px">');
						
						print('from');
						print('<strong>');
						print(' ');
						
						$printable_roles = [];
						
						foreach($writings as $writing) {	# bonjour!  we meet again!  en guarde!
							$role_info = '<a href="/writings/' . $writing['entry']['Code'] . '/view.php">';
							$role_info .= $writing['entry']['Title'];
							if($role['entry']['Subtitle']) {
								$child_title = $writing['entry']['Subtitle'];
								
								if(strlen($child_title) > 30) {
									$child_title = substr($child_title, 0, 30) . '...';
								}
								
								$role_info .= $child_title;
							}
							
							#	print("<!-- BT:!!!! \n\n");
							#	print_r($writing['entry']);
							#	print("\n\n-->\n\n");
							if($writing['associated_entry'] && $this->that->entry['id'] !== $writing['associated_entry']['id']) {
								$role_info .= '</a>, by <a href="/people/' . '' . $writing['associated_entry']['Code'] . '/">' . $writing['associated_entry']['Title']  . '</a>';
							}
							
							$role_info .= '</a>';
							
							$printable_roles[] = $role_info;
						}
						
						print(implode(', ', $printable_roles));
						
						print('</strong>');
						
						print('</div>');
						print('</div>');
						
					#	print("\n\n<!-- BT:!!!!\n\n");
					#	print_r($writings);
					#	print("\n\n-->");
					}
				}
				
				$this->DisplayTimeFrame(['child'=>$child, 'short-dates'=>$args['short-dates']]);
				
				print('<span class="horizontal-left font-family-arial">');
				
				$this->DisplayDescription(['child'=>$child]);
				
				if($child['quote']) {
					$child_quotes = $child['quote'];
					$child_quotes_count = count($child_quotes);
					$max_limit = $child_quotes_count;
					if($max_limit > 3) {
						$max_limit = 3;
					}
					shuffle($child_quotes);
					for($i = 0; $i < $max_limit; $i++) {
						$quote = $child_quotes[$i];
						if($quote && $quote['Quote']) {
							print(' <br>&bull; ');
							print('"');
							print(str_replace('"', '\'', $quote['Quote']));
							print('"');
							
							if($quote['Source']) {
								$source = $quote['Source'];
								
								if(strlen($source) > 50) {
									$source = substr($source, 0, 50) . '...';
								}
								
								print(' (From : ' . $source . '.)');
							}
						}
					}
				} else {
					if($args['quotes_body']) {
						$associateds = $child['associated'];
						$associateds_count = $associateds ? count($associateds) : 0;
						
						$valid_textbody = false;
						$valid_work = false;
						
						for($i = 0; $i < $associateds_count; $i++) {
							$associated = $associateds[$i];
							
							$associated_entry = $associated['entry'];
							if($associated_entry) {
								$associated_entry_textbody = $associated_entry['textbody'];
								if($associated_entry_textbody) {
									$valid_textbody = $associated_entry_textbody[0];
								}
								
								$associated_entry_parents = $associated_entry['parents'];
									
									# BT: FIXME -- get the work that MATCHES the quote (if possible, otherwise, just grab the work)
									# BT: PFFFFFFFFFFT, display ALL TITLES!  MUWHUAHHAHAHAHAHAHAH!
								if($associated_entry_parents) {
									$associated_entry_parent = $associated_entry['parents'][0];
									if($associated_entry_parent['Code'] === 'writings') {
										$valid_work = $associated_entry;
									}
								}
								
								if($valid_textbody && $valid_work) {
									$i = $associateds_count;
								}
							}
						}
						
						if($valid_textbody) {
						#	print("<PRE>");
						#	print_r($valid_textbody);
						#	print("</PRE>");
							
							$valid_text = $valid_textbody['FirstThousandCharacters'];
							
							$valid_text = trim(strip_tags($valid_text));
							
							print('"');
							print($valid_text);
							print('"');
						}
						
							# BT: FIXME: Only a work available mode!
						
						if($valid_work) {
							print(' (Works: ' . $valid_work['Title'] . '...)');
						#	print("<PRE>");
						#	print_r($valid_work);
						#	print("</PRE>");
						}
						
			#			print("<PRE>");
			#			print_r($valid_textbody);
			#			print_r($child['associated']);
			#				print_r($child);
			#			print("</PRE>");
					} else {
						if($child['textbody'] && $child['textbody'][0]) {
							$this->DisplayTextBody(['child'=>$child]);
						} else {
							if($child['children'] && $child['children'][0] && $child['children'][0]['textbody'] && $child['children'][0]['textbody'][0]) {
								$this->DisplayTextBody(['child'=>$child['children'][0]]);
							}
						}
					}
				}
				
				print('</span>');
				
				print('<div class="clear-float"></div>');
				
						// Tags
					
					// -------------------------------------------------------------
				
				if($child['tag']) {
					$tag_count = count($child['tag']);
					
					if($tag_count) {
						$tags = $child['tag'];
						$max_limit = $tag_count;
						if($max_limit > 10) {
							$max_limit = 10;
						}
						shuffle($tags);
						
						for($i = 0; $i < $max_limit; $i++) {
							$tag = $tags[$i];
							print('<div class="border-2px background-color-gray15 margin-left-5px margin-bottom-5px float-left">');
							print('<span class="horizontal-left margin-5px font-family-arial">');
							print('<a target="_parent" href="/view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
							print($tag['Tag']);
							print('</a>');
							print('</span>');
							print('</div>');
						}
						
						print('<div class="clear-float"></div>');
					}
				}
				
				print('</div>');
				print('</div>');
			}
			
			return TRUE;
		}
		
		public function getBlockingHTML() {
			return [
				'_',
				'&nbsp;',
				'<br>',
				'<br >',
				'<br />',
				'<hr>',
				'<hr >',
				'<hr />',
			];
		}
		
		public function DisplayTextBody($args) {
			$child = $args['child'];
			
			$text_bodies = $child['textbody'];
			
			$text_display = $text_bodies[0]['FirstThousandCharacters'];
			
			$text_display = preg_replace('/Image::(\d+)(\s+)/', '', $text_display);
			$text_display = str_replace($this->getBlockingHTML(), ' ', $text_display);
	#		$text_display = str_replace('<hr>', ' ', $text_display);
			$text_display = trim(strip_tags($text_display));
			
			if(strlen($text_display) > 750) {
				$text_display = substr($text_display, 0, 750) . '...';
			}
			
			if($text_display) {
				print($text_display);
				
				if($text_bodies[0]['Source']) {
					$source = $text_bodies[0]['Source'];
					
					if(strlen($source) > 50) {
						$source = substr($source, 0, 50) . '...';
					}
					
					print(' (From: ' . $source . '.)');
				}
			}
			
			return TRUE;
		}
		
		public function DisplayDescription($args) {
			$child = $args['child'];
			
			if($child['description']) {
				$description = $child['description'][0];
				
				if($description && $description['Description']) {
					print('<em>');
					print($description['Description']);
					print(' ');
					print('</em>');
					
					if($description['Source']) {
						$source = $description['Source'];
						
						if(strlen($source) > 50) {
							$source = substr($source, 0, 50) . '...';
						}
						
						print(' (From : ' . $source . '.)');
					}
				}
			}
			
			return TRUE;
		}
		
		public function DisplayTimeFrame($args) {
			$child = $args['child'];
			$time_frame = '';
			
			
			if($this->entrydate) {
				$time_info = $this->entrydate->getEntrySimpleData(['entry'=>$child, 'short-dates'=>$args['short-dates']]);
			}
			$time_frame = $time_info['text'];
			
			print("<!-- BT: TIMEFRAME!!!\n\n");
			print("\n\n");
			print($time_frame);
			print("-->");
			
			if($time_frame) {
				print('<div class="font-family-arial border-2px background-color-gray15 margin-4px float-right">');
				print('<div class="margin-2px">');
				
				print('<nobr>');
				print('<span style="font-size:1.5em;">');
				print('<strong>');
				
				print($time_frame);
				
				print('</strong>');
				print('</span>');
				print('</nobr>');
				
				print('</div>');
				print('</div>');
			}
			
			return TRUE;
		}
	}

?>
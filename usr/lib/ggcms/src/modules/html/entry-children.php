<?php

	class module_entrychildren extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->entrysort = $args['entrysort'];
		}
		
		public function Display() {
			if($this->that->children && $this->that->counts['children'] !== 0) {
				return $this->Display_Entries([
					'entries'=>$this->that->children,
					'count'=>$this->that->counts['children'],
					'alts'=>TRUE,
					'stats'=>TRUE,
				]);
			}
			
			return FALSE;
		}
		
		public function Display_Entries($args) {
			$entries = $args['entries'];
			$count = $args['count'];
			$stats = $args['stats'];
			$alts = $args['alts'];
			$date_field = $args['datefield'];
			$url_prefix = $args['url_prefix'];
			$url_action = $args['url_action'];
			$custom_width = $args['custom_width'];
			
			if(!$custom_width) {
				$custom_width = '100%';
			}
			
					// Textbody Header
				
				// -------------------------------------------------------------
				
			print('<a name="children"></a>');
			
			if($this->header) {
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<h2 class="horizontal-left margin-5px font-family-arial">');
				print($this->header);
				print('</h2>');
				print('</div>');
				print('</div>');
			}
			
					// Child Record Counts
				
				// -------------------------------------------------------------
			
			if($stats) {
				print('<div class="border-2px background-color-gray15 margin-5px float-left" title="');
				
				print(' (Last Updated: ');
				$date_epoch_time = strtotime($this->that->child_record_stats['LastModificationDate']);
				$full_date = date("F d, Y; H:i:s", $date_epoch_time);
				print($full_date);
				print('.)');
				
				print('">');
				
				print('<strong>');
				print('<p class="horizontal-left margin-5px font-family-tahoma">');
				print('' . number_format($this->that->child_record_stats['ChildRecordCount']) . ' Chapters | ' . number_format($this->that->child_record_stats['ChildWordCount']) . ' Words | ' . number_format($this->that->child_record_stats['ChildCharacterCount']) . ' Characters');
				print('</p>');
				print('</strong>');
				
				print('</div>');
			}
			
					// Alternates
				
				// -------------------------------------------------------------
				
			if($alts && $this->that->counts['textbody'] === 0) {
						// Alternate Formats Info
					
					// -------------------------------------------------------------
					
				require_once(GGCMS_DIR . 'modules/html/alternateformats.php');
				print('<div style="float:right;">');
				$auth = new module_alternateformats(['that'=>$this->that, 'audio'=>FALSE]);
				$auth->Display();
				print('</div>');
			}
			
					// Finish Textbody Header
				
				// -------------------------------------------------------------
									
			print('<div class="clear-float"></div>');
			
					// Display Children
				
				// -------------------------------------------------------------
			
			if($this->that->children_count > $this->that->maxChildren()) {
				$record_list_count = count($this->that->record_list);
				$url = '';
				for($i = 0; $i < $record_list_count; $i++) {
					$record = $this->that->record_list[$i];
					$url .= '/' . $record['Code'];
				}
				
				$url .= '/view.php?headless=1&action=browse';
				print('<center>');
				print('<iframe style="width:95%;height:900px;" src="' . $url . '">');
				print('</iframe>');
				print('</center>');
			} else {
				$children_display = $this->entrysort->Sort(['entries'=>$entries, 'sort_field'=>$date_field]);
				
				if($date_field) {
					$children_display = array_reverse($children_display);
				}
				
				print('<div class="horizontal-center width-90percent">');
				foreach($children_display as $child) {
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
					
					$child_title = '<a target="_parent" href="/' . $url_prefix . implode('/', $codes) . '/view.php';
					
					if($this->that->entry['ChildAction']) {
						$child_title .= '?action=' . $this->that->entry['ChildAction'];
					} elseif($url_action) {
						$child_title .= '?action=' . $url_action;
					}
					
					$child_title .= '">';
				} else {
					$child_title = '<a target="_parent" href="' . $url_prefix . $child['Code'] . '/view.php';
					
					if($this->that->entry['ChildAction']) {
						$child_title .= '?action=' . $this->that->entry['ChildAction'];
					} elseif($url_action) {
						$child_title .= '?action=' . $url_action;
					}
					
					$child_title .= '">';
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
					
					if($url_prefix) {
						$link = $url_prefix . $link;
					}
					
					print('<a target="_parent" href="' . $link . '/view.php');
					
					if($this->that->entry['ChildAction']) {
						print('?action=' . $this->that->entry['ChildAction']);
					} elseif($url_action) {
						print('?action=' . $url_action);
					}
					
					print('">');
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
					
					if($url_prefix) {
						$link = $url_prefix . $link;
					}
					
					print('<b><a target="_parent" href="' . $link . '/modify.php?action=Edit">Edit</a></b> ');
					print('</div>');
					print('</div>');
				}
				
				if($args['show_associations']) {
					$associated = $child['associated'];
					if(count($associated) > 0) {
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
							if($writing['entry']['Subtitle']) {
								$child_title = $writing['entry']['Subtitle'];
								
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
					if($child['textbody'] && $child['textbody'][0]) {
						$this->DisplayTextBody(['child'=>$child]);
					} else {
						if($child['children'] && $child['children'][0] && $child['children'][0]['textbody'] && $child['children'][0]['textbody'][0]) {
							$this->DisplayTextBody(['child'=>$child['children'][0]]);
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
				print('</div>');
			}
			
			if($custom_width !== '100%') {
				print('<div class="clear-float"></div>');
			}
			
			return TRUE;
		}
		
		public function FormatDate($args) {
			$date = $args['date'];
			
			if(!$date) {
				return '?';
			}
			
			$event_date_pieces = explode('-', $date);
			
			$date_epoch_time = strtotime($date);
			
			$month_format = 'F';
			if($args['short-dates']) {
				$month_format = 'M.';
			}
			
			$year = $event_date_pieces[0];
			/*
			if(intval($event_date_pieces[0]) > 3000) {
				if($year >= 3000) {
					$diff = $year - 3000;
					$real_year = 1000 - $diff;
				} else {
					$real_year = $year;
				}
			*/
			$bce_check = mb_substr($year, 0, 3, 'utf-8');
			if($bce_check === 'bce') {
				$real_year = str_replace('bce', '', $year);
				$formatted = $real_year . ' BCE';
			} elseif($event_date_pieces[1] !== '00' && $event_date_pieces[2] !== '00') {
				$formatted = date("$month_format j, Y", $date_epoch_time);
			} elseif($event_date_pieces[1] !== '00') {
				$new_date_epoch_time = $event_date_pieces[0] . '-' . $event_date_pieces[1] . '-01';
				$formatted = date("$month_format, Y", strtotime($new_date_epoch_time));
			} else {
				$new_date_epoch_time = $event_date_pieces[0] . '-01-01';
				$formatted = date("Y", strtotime($new_date_epoch_time));
			}
			
			return $formatted;
		}
		
		public function DisplayTimeFrame($args) {
			$time_frame = $args['time_frame'];
			
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
		
		public function getTimeFrame($args) {
			$child = $args['child'];
			
			if($child['eventdate']) {
				unset($publication_event);
				unset($birth_day_event);
				unset($death_day_event);
				
				$child_event_count = count($child['eventdate']);
				for($i = 0; $i < $child_event_count; $i++) {
					$child_event = $child['eventdate'][$i];
						
					if($child_event['Title'] === 'Publication' || $child_event['Title'] === 'Written') {
						$publication_event = $child_event;
					}
					
					if($child_event['Title'] === 'Birth Day') {
						$birth_day_event = $child_event;
					}
					
					if($child_event['Title'] === 'Death Day') {
						$death_day_event = $child_event;
					}
					
					if($publication_event || ($birth_day_event && $death_day_event)) {
						$i = $child_event_count;
					}
				}
				
				if($publication_event) {
					if($publication_event['EventDateTime'] != '0000-00-00 00:00:00') {
						$event_date_pieces = explode('-', $publication_event['EventDateTime']);
						$year = $event_date_pieces[0];
						$time_frame = $year;
					} else {
						$time_frame = '?';
					}
				} else if ($birth_day_event && $death_day_event) {
					$time_frame = $this->FormatDate(['date'=>$birth_day_event['EventDateTime']]) . ' - ' . $this->FormatDate(['date'=>$death_day_event['EventDateTime']]);
				} else if ($birth_day_event) {
					$time_frame = $this->FormatDate(['date'=>$birth_day_event['EventDateTime']]) . ' - ' . '?';
				} else if ($death_day_event) {
					$time_frame = '?' . ' - ' . $this->FormatDate(['date'=>$death_day_event['EventDateTime']]);
				}
				
				return $time_frame;
			}
			
			return '';
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
	}

?>
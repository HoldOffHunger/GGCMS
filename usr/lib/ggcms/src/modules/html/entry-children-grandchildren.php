<?php

	class module_entrychildrengrandchildren extends module_spacing {
		public $that;
		public $header;
		public $entrysort;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->entrysort = $args['entrysort'];
		}
		
		public function Display() {
			$page = 'view.php';
			
			if($this->that->entry['ChildAction']) {
				$page .= '?action=' . $this->that->entry['ChildAction'];
			}
			
			for($i = 0; $i < count($this->that->children); $i++) {
				$child = $this->that->children[$i];
				
				$child_title =
					'<a href="' . $child['Code'] . '/' . $page . '">' .
					$child['Title'] . ' : ' . $child['Subtitle'] .
					'</a>';
				
				print('<center>');
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left width-100percent">');
				
				$this->Display_Image([
					'child'=>$child,
					'parent'=>$this->that->entry,
				]);
				
				$this->Display_Header([
					'entry'=>$child,
					'title'=>$child_title,
					'parent'=>$this->that->entry,
				]);
				
				$this->Display_Description([
					'entry'=>$child,
				]);
				
				$this->Display_Quote([
					'entry'=>$child,
				]);
				
				print('</div>');
				print('</div>');
				print('</center>');
										
				print('<div class="clear-float"></div>');
				
				$this->Display_GrandChildren(['child'=>$child]);
			}
		}
		
		public function Display_GrandChildren($args) {
			$child = $args['child'];
			$grandchildren = $child['children'];
			
			if(!$grandchildren) {
				return TRUE;
			}
			
			$grandchildren_count = count($grandchildren);
			
			if($grandchildren_count === 0) {
				return TRUE;
			}
			
			print('<div class="horizontal-center width-90percent">');
			
			for($i = 0; $i < $grandchildren_count; $i++) {
				$grandchild = $grandchildren[$i];
				
				$this->Display_GrandChild([
					'child'=>$child,
					'grandchild'=>$grandchild,
				]);
			}
			
			print('</div>');
		}
		
		public function Display_GrandChild($args) {
			$grandchild = $args['grandchild'];
			$child = $args['child'];
			
			print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
			
			$this->Display_Image([
				'child'=>$grandchild,
				'parent'=>$child,
			]);
			
			$title_max = 50;
			
			if($grandchild['association'] && count($grandchild['association'])) {
				$title_max = 30;
			}
			
			$grandchild_title_full = $grandchild['Title'];
			$popup_title = 0;
			if(strlen($grandchild_title_full) > $title_max) {
				$grandchild_title_full = substr($grandchild_title_full, 0, $title_max) . '...';
				$popup_title = 1;
			}
			
			$grandchild_title = '<a href="' . $child['Code'] . '/' . $grandchild['Code'] . '/view.php';
			
			if($child['ChildAction']) {
				$grandchild_title .= '?action=' . $child['ChildAction'];
			}
			
			$grandchild_title .= '"';
			
			if($popup_title) {
				$grandchild_title .= ' title="' . str_replace('"', '&quot;', $grandchild['Title']) . '"';
			}
			
			$grandchild_title .= '>';
			
			$grandchild_title .= $grandchild_title_full;
			$grandchild_title .= '</a>';
			
			if($grandchild['association'] && count($grandchild['association'])) {
				$author = $grandchild['association'][0]['entry'];
				$grandchild_title .= ', by ';
				
				$grandchild_author_full_title = $author['Title'];
				
				$popup_title = 0;
				if(strlen($grandchild_author_full_title) > 20) {
					$grandchild_author_full_title = substr($grandchild_author_full_title, 0, 20) . '...';
					$popup_title = 1;
				}
				
				$grandchild_title .= '<a href="people/' . $author['Code'] . '/view.php"';
				
				if($popup_title) {
					$grandchild_title .= ' title="' . str_replace('"', '&quot;', $author['Title']) . '"';
				}
				
				$grandchild_title .= '>';
				
				$grandchild_title .= $grandchild_author_full_title;
				$grandchild_title .= '</a>';
			}
			
			$div_mouseover = '';
			
			if($grandchild['textbody']) {
				$text_bodies = $grandchild['textbody'];
				
				$text_body_count = count($text_bodies);
				if($text_body_count) {
					$first_textbody = $text_bodies[0];
					
					$div_mouseover .= number_format($first_textbody['WordCount']) . ' Words / ' . number_format($first_textbody['CharacterCount']) . ' Characters';
				}
			}
			
			print('<div id="header_backgroundimageurl" class="border-2px background-color-gray15 margin-5px float-left" title="' . $div_mouseover . '">');

			print('<div class="span-header-3"><h3 style="margin:5px;padding:5px;display: inline-block;border:black 2px solid;background-color:#FFFFFF;" class="header-3 padding-0px margin-5px horizontal-left font-family-tahoma">' . $grandchild_title . '</h3></div>');

			print('</div>');
			
			
			
			
			
			
			print('<p class="horizontal-left margin-5px font-family-arial">');
			
			$time_frame = '';
			
			if($grandchild['eventdate']) {
				$grandchild_event_count = count($grandchild['eventdate']);
				$birth_event = NULL;
				$death_event = NULL;

				for($k = 0; $k < $grandchild_event_count; $k++) {
					$grandchild_event = $grandchild['eventdate'][$k];
					
					if($grandchild_event['Title'] == 'Birth Day') {
						$birth_event = $grandchild_event;
					} elseif($grandchild_event['Title'] == 'Death Day') {
						$death_event = $grandchild_event;
					}
					
					if($birth_event && $death_event) {
						$k = $grandchild_event_count;
					}
				}
				
				if($birth_event || $death_event) {
					$time_frame .= ' (';
					
					if($birth_event && $birth_event['id']) {
						if($birth_event['EventDateTime'] != '0000-00-00 00:00:00') {
							$birth_event_date_pieces = explode('-', $birth_event['EventDateTime']);
							$birth_year = $birth_event_date_pieces[0];
							$time_frame .= $birth_year;
						} else {
							$time_frame .= '?';
						}
					}
					
					$time_frame .= ' - ';
					
					if($death_event && $death_event['id']) {
						if($death_event['EventDateTime'] != '0000-00-00 00:00:00') {
							$death_event_date_pieces = explode('-', $death_event['EventDateTime']);
							$death_year = $death_event_date_pieces[0];
							$time_frame .= $death_year;
						} else {
							$time_frame .= '?';
						}
					}
					
					$time_frame .= ') ';
					
					unset($birth_event);
					unset($death_event);
				}
			}
			
			if($time_frame) {
				print($time_frame);
			}
			
			if($grandchild['Subtitle']) {
				if($time_frame) {
					print(' ~ ');
				}
				
				print('<strong>');
				print($grandchild['Subtitle']);
				print('</strong>');
			}
			
			if($grandchild['description']) {
				$description = $grandchild['description'][0];
				
				if($description && $description['Description']) {
					print('<em>');
					if($time_frame || $grandchild['Subtitle']) {
						print(' : ');
					}
					
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
			
			if($grandchild['quote']) {
				$grandchild_quotes = $grandchild['quote'];
				$grandchild_quotes_count = count($grandchild_quotes);
				$max_limit = $grandchild_quotes_count;
				if($max_limit > 3) {
					$max_limit = 3;
				}
				shuffle($grandchild_quotes);
				for($k = 0; $k < $max_limit; $k++) {
					$quote = $grandchild_quotes[$k];
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
				$printed = 0;
				if($grandchild['textbody']) {
					$text_bodies = $grandchild['textbody'];
					
					$text_body_count = count($text_bodies);
					if($text_body_count) {
						$text_display = $this->that->cleanser_object->FormatListOutput([
							'text'=>$text_bodies[0]['FirstThousandCharacters'],
						]);
						
						if($text_display) {
							if($time_frame || $grandchild['Subtitle'] || $grandchild['description']) {
								print('<br>');
							}
							print($text_display);
							$printed = 1;
							
							if($text_bodies[0]['Source']) {
								$source = $text_bodies[0]['Source'];
								
								if(strlen($source) > 50) {
									$source = substr($source, 0, 50) . '...';
								}
								
								print(' (From : ' . $source . '.)');
							}
						}
					}
				}
				
				if(!$printed) {
					$great_grand_children = $grandchild['children'];
					if($great_grand_children && is_array($great_grand_children)) {
						$great_grand_children_count = count($great_grand_children);
						
						if($great_grand_children_count) {
							$great_grand_children_display = $this->entrysort->Sort(['entries'=>$great_grand_children]);
							
							$great_grand_child = NULL;
							foreach($great_grand_children_display as $single_grand_child) {
								if(!$great_grand_child) {
									$great_grand_child = $single_grand_child['textbody'][0];
								}
							}
							
							$text_display = $this->that->cleanser_object->FormatListOutput([
								'text'=>$great_grand_child['FirstThousandCharacters'],
							]);
							print($text_display);
						}
					}
				}
			}
			
			print('</p>');
			
					// Finish Float
				
				// -------------------------------------------------------------
									
			print('<div class="clear-float"></div>');
			
			$this->Display_Tags([
				'entry'=>$grandchild,
			]);
			
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_Tags($args) {
			$grandchild = $args['entry'];
					// Tags
				
				// -------------------------------------------------------------
			
			if($grandchild['tag']) {
				$tag_count = count($grandchild['tag']);
				
				if($tag_count) {
					$tags = $grandchild['tag'];
					$max_limit = $tag_count;
					if($max_limit > 10) {
						$max_limit = 10;
					}
					shuffle($tags);
					
					for($l = 0; $l < $max_limit; $l++) {
						$tag = $tags[$l];
						print('<div class="border-2px background-color-gray15 margin-left-5px margin-bottom-5px float-left">');
						print('<span class="horizontal-left margin-5px font-family-arial">');
						print('<a href="/view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
						print($tag['Tag']);
						
						print(' (');
						print(number_format($this->that->tag_counts[$tag['Tag']]));
						print(')');
						
						print('</a>');
						print('</span>');
						print('</div>');
					}
					
							// Finish Float
						
						// -------------------------------------------------------------
											
					print('<div class="clear-float"></div>');
				}
			}
			
			return TRUE;
		}
		
		public function Display_Image($args) {
			$child = $args['child'];
			$parent = $args['parent'];
			
			if($child['image'][0]) {
				$image = $this->Display_Image_GetRandomImage(['entry'=>$child]);
			} elseif($parent && $parent['image'][0]) {
				$image = $this->Display_Image_GetRandomImage(['entry'=>$parent]);
			} elseif($this->that->entry['image'][0]) {
				$image = $this->Display_Image_GetRandomImage(['entry'=>$this->that->entry]);
			} else {
				return TRUE;
			}
			
			$page = 'view.php';
			
			if($parent['ChildAction']) {
				$page .= '?action=' . $parent['ChildAction'];
			}
			
		#	print("<PRE>");
		#	print_r($this->that->record_list);
		#	print($parent['Code']);
		#	print($this->that->master_record['Code']);
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<div class="background-color-gray0">');
			
			
			print('<a href="');
			
			if($this->that->record_list) {
				// handle use for outside first main page
			}
			
			if($parent['id'] !== $this->that->master_record['id']) {
				print($parent['Code'] . '/');
			}
			
			print($child['Code'] . '/' . $page . '">');
			
			print('<img src="');
			print($this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
			print('/image/');
			print(implode('/', str_split($image['FileDirectory'])));
			print('/');
			print($image['IconFileName']);
			print('">');
			print('</a>');
			print('</div>');
			print('</div>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_Image_GetRandomImage($args) {
			$entry = $args['entry'];
			$images = $entry['image'];
			
			shuffle($images);
			
			$random_image = $images[0];
			
			return $random_image;
		}
		
		public function Display_Header($args) {
			$entry = $args['entry'];
			$title = $args['title'];
			$parent = $args['parent'];
			
			$page = 'view.php';
			
			if($parent['ChildAction']) {
				$page .= '?action=' . $parent['ChildAction'];
			}
			
			print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
			print('<strong>');
			print('<a href="' . $entry['Code'] . '/' . $page . '">');
			print($title);
			print('</a>');
			print('</strong>');
			print('</h2>');
			
			return TRUE;
		}
		
		public function Display_Description($args) {
			$entry = $args['entry'];
			
			if($entry['description'][0]['Description']) {
				print('<p class="horizontal-left font-family-arial margin-5px">');
				
				print('<b>' . $entry['description'][0]['Description'] . '</b><br>');
				
				print('</p>');
			}
			
			return TRUE;
		}
		
		public function Display_Quote($args) {
			$entry = $args['entry'];
			
			if($entry['quote'][0]) {
				$random_quote = $entry['quote'][array_rand($entry['quote'], 1)];
				
				print('<center>');
				print('<div class="horizontal-center ">');
				print('<blockquote class="horizontal-left font-family-arial"><em>"');
				print(str_replace('"', '\'', $random_quote['Quote']));
				print('"');
				if($random_quote['Source']) {
					print(' -- ');
					print($random_quote['Source']);
				}
				print('</em></blockquote>');
				print('</div>');
				print('</center>');
			}
			
			return TRUE;
		}
	}
	
?>
<?php

	class module_entryassociation extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			
			if($this->that->counts['association'] === 0) {
				$parent = $this->that->record_list[count($this->that->record_list) - 2];
				if($parent['association'] && $parent['association'][0] && $parent['association'][0]['id']) {
					for($i = 0; $i < count($parent['association']); $i++) {
						$this->that->entry['association'][] = $parent['association'][$i];
						$this->that->counts['association']++;
					}
				}
			}
			
			return $this;
		}
		
		public function DisplayHeader() {
					// Association Header
				
				// -------------------------------------------------------------
				
			print('<a name="association"></a>');
			
			if($this->header) {
				print('<center>');
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<h2 class="horizontal-left margin-5px font-family-arial">');
				print($this->header);
				print('</h2>');
				print('</div>');
				print('</div>');
				print('</center>');
			}
			
			return TRUE;
		}
		
		public function Display($args) {
			if($this->that->entry['association'] && $this->that->counts['association']) {
				if(!empty($args['parent_code'])) {
					$parent_code = $args['parent_code'];
				} else {
					$parent_code = 'people';
				}
				$this->DisplayHeader();
						// Finish Textbody Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Author Info
					
					// -------------------------------------------------------------
				
				$associations = $this->that->entry['association'];
				
				$max = 0;
				
				if(!empty($args['max'])) {
					$max = $args['max'];
				} else {
					$max = $this->that->counts['association'];
				}
				
				for($i = 0; $i < $max; $i++) {
					$association = $associations[$i];
					
					if(empty($args['type']) || $args['type'] === $association['Type']) {
						$child = $association['entry'];
						
						print('<div class="horizontal-center width-90percent">');
					
						print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
						
						$display_image = NULL;
						
						if($child['image']) {
							$child_images = $child['image'];
							$child_image_count = count($child_images);
							if($child_image_count) {
								shuffle($child_images);
								$child_image = $child_images[0];
								$display_image = $child_image;
							}
						}
						
						if(!$display_image) {
							if(!empty($this->that->entry['association'][0]['entry']['image'])) {
								$display_image = $this->that->entry['association'][0]['entry']['image'][0];
							} elseif(!empty($child['association'][0]['entry']['image'])) {
								$display_image = $child['association'][0]['entry']['image'][0];
							} elseif(!empty($this->that->master_record['image'][0])) {
								$display_image = $this->that->master_record['image'][0];
							} else {
								$display_image = [
									'IconFileName'=>$this->that->SitePrimaryIcon(),
									'IconPixelWidth'=>200,
									'IconPixelHeight'=>200,
								];
							}
						}
						
						print('<div class="border-2px background-color-gray15 margin-5px float-left font-family-arial">');
						print('<div class="border-2px background-color-gray15 margin-5px float-left">');
						print('<div class="height-100px width-100px background-color-gray0">');
						print('<div class="vertical-specialcenter">');
						print('<a href="/' . $parent_code . '/' . $child['Code'] . '/view.php">');
						print('<img width="');
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
						
						$popup_title = 0;
						$mouseover_title = '';
						$title_max = 30;
						
						$first_child_title = $association['SubType'];
						
						if(!$first_child_title && !empty($args['type'])) {
							$first_child_title .= ucfirst($args['type']);
						}
						
						if(strlen($first_child_title) > $title_max) {
							$mouseover_title = $first_child_title;
							$first_child_title = substr($first_child_title, 0, $title_max) . '...';
							$popup_title = 1;
						}
						
						$second_child_title = $child['Title'];
						
						if(strlen($second_child_title) > $title_max) {
							if($mouseover_title) {
								$mouseover_title .= ' : ';
							}
							
							$mouseover_title .= $second_child_title;
							$second_child_title = substr($second_child_title, 0, $title_max) . '...';
							$popup_title = 1;
						}
						
						$child_title = '<a href="/' . $parent_code . '/' . $child['Code'] . '/view.php"';
						
						if($popup_title) {
							$child_title .= ' title="' . str_replace('"', '&quot;', $mouseover_title) . '"';
						}
						
						$child_title .= '>';
						
						$child_title .= $first_child_title;
						
						$child_title .= ' : ';
						$child_title .= $second_child_title;
						
						$child_title = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $child_title);
						
						$child_title .= '</a>';
						
						$div_mouseover = '';
						
						if($child['textbody']) {
							$text_bodies = $child['textbody'];
							
							$text_body_count = count($text_bodies);
							if($text_body_count) {
								$first_textbody = $text_bodies[0];
								
								$div_mouseover .= number_format($first_textbody['WordCount']) . ' Words / ' . number_format($first_textbody['CharacterCount']) . ' Characters';
							}
						}
						
						$header_secondary_args = [
							'title'=>$child_title,
							'divmouseover'=>$div_mouseover,
							'level'=>3,
							'divclass'=>'border-2px background-color-gray15 margin-5px float-left',
							'textclass'=>'padding-0px margin-5px horizontal-left font-family-tahoma',
							'imagedivclass'=>'border-2px margin-5px background-color-gray10',
							'imageclass'=>'border-1px',
							'domainobject'=>$this->that->handler->domain,
							'leftimageenable'=>0,
							'rightimageenable'=>0,
						];
						
						print('<div class="span-header-3" style="float:left;">');
						print('<h3 style="margin:5px;padding:5px;display: inline-block;border:black 2px solid;background-color:#FFFFFF;" class="header-3 padding-0px margin-5px horizontal-left font-family-tahoma">');
						print($child_title);
						print('</h3></div>');
						
						$time_frame = '';
						
						if($child['eventdate']) {
							$child_event_count = count($child['eventdate']);
							$birth_event = NULL;
							$death_event = NULL;

							for($j = 0; $j < $child_event_count; $j++) {
								$child_event = $child['eventdate'][$j];
								
								if($child_event['Title'] === 'Birth Day') {
									$birth_event = $child_event;
								} elseif($child_event['Title'] === 'Death Day') {
									$death_event = $child_event;
								}
								
								if($birth_event && $death_event) {
									$j = $child_event_count;
								}
							}
							
							if($birth_event || $death_event) {
								$time_frame .= ' (';
								
								if($birth_event && $birth_event['id']) {
									if($birth_event['EventDateTime'] != '0000-00-00 00:00:00') {
										$birth_event_date_pieces = explode('-', $birth_event['EventDateTime']);
										$birth_year = $birth_event_date_pieces[0];
										$time_frame .= $this->FormatDate(['date'=>$birth_year . '-00-00']);
									} else {
										$time_frame .= '?';
									}
								}
								
								$time_frame .= ' - ';
								
								if($death_event && $death_event['id']) {
									if($death_event['EventDateTime'] != '0000-00-00 00:00:00') {
										$death_event_date_pieces = explode('-', $death_event['EventDateTime']);
										$death_year = $death_event_date_pieces[0];
										$time_frame .= $this->FormatDate(['date'=>$death_year . '-00-00']);
									} else {
										$time_frame .= '?';
									}
								}
								
								$time_frame .= ') ';
								
								unset($birth_event);
								unset($death_event);
							}
						}
						
						$this->DisplayTimeFrame(['time_frame'=>$time_frame]);
						
						if($child['Subtitle']) {
							print('<p align="left" style="margin:0px;font-family:arial;padding:0px;">');
							print('<strong>');
							print($child['Subtitle']);
							print('</strong>');
							print('</p>');
						}
						
						print('<p align="left" class="horizontal-left margin-5px font-family-arial">');
						
						if($child['description']) {
							$description = $child['description'][0];
							
							if($description && $description['Description']) {
								print('<em>');
								if($time_frame || $child['Subtitle']) {
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
									
									print(' (From: ' . $source . '.)');
								}
							}
						}
						
						if($child['quote']) {
							$child_quotes = $child['quote'];
							$child_quotes_count = count($child_quotes);
							$max_limit = $child_quotes_count;
							if($max_limit > 3) {
								$max_limit = 3;
							}
							shuffle($child_quotes);
							for($j = 0; $j < $max_limit; $j++) {
								$quote = $child_quotes[$j];
								if($quote && $quote['Quote']) {
									print(' <br>&bull; ');
									print('"');
									print(str_replace('"', '\'', $quote['Quote']));
									print('"');
									
									if($quote['Source']) {
										$source = $quote['Source'];
										
										if(strlen($source) > 50)
										{
											$source = substr($source, 0, 50) . '...';
										}
										
										print(' (From: ' . $source . '.)');
									}
								}
							}
						} else {
							if($child['textbody']) {
								$text_bodies = $child['textbody'];
								
								$text_body_count = count($text_bodies);
								if($text_body_count) {
									$text_display = $this->that->handler->cleanser->FormatListOutput([
										'text'=>$text_bodies[0]['FirstThousandCharacters'],
									]);
									
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
								}
							}
						}
						
						print('</p>');
						
								// Finish Float
							
							// -------------------------------------------------------------
												
						print('<div class="clear-float"></div>');
						
								// Tags
							
							// -------------------------------------------------------------
						
						if($child['tag']) {
							$person_tag_count = count($child['tag']);
							
							if($person_tag_count) {
								$tags = $child['tag'];
								$max_limit = $person_tag_count;
								if($max_limit > 10) {
									$max_limit = 10;
								}
								shuffle($tags);
								
								for($j = 0; $j < $max_limit; $j++) {
									$tag = $tags[$j];
									print('<div class="border-2px background-color-gray15 margin-left-5px margin-bottom-5px float-left">');
									print('<span class="horizontal-left margin-5px font-family-arial">');
									print('<a href="/view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
									print($tag['Tag']);
									if($this->that->tag_counts[$tag['Tag']]) {
										print(' (');
										print($this->that->tag_counts[$tag['Tag']]);
										print(')');
									}
									print('</a>');
									print('</span>');
									print('</div>');
								}
								
										// Finish Float
									
									// -------------------------------------------------------------
														
								print('<div class="clear-float"></div>');
							}
						}
						
						print('</div>');
					
						print('</div>');
					}
				}
			}
			
			return TRUE;
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
		
			/* poached from html/entry-date.php */
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
			$bce_check = substr($year, 0, 3);
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
	}

?>
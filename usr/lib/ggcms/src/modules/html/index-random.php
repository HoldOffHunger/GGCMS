<?php

	class module_indexrandom extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->entrysort = $args['entrysort'];
			
			return $this;
		}
		
		public function Display() {
			print('<center>');
			print('<div class="horizontal-center width-95percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
			print('<strong>');
			print('Blasts from the Past');
			print('</strong>');
			print('</h2>');
			print('</div>');
			print('</div>');
			print('</center>');
				
			print('<div class="clear-float"></div>');
			
			print('<div class="horizontal-center width-90percent">');
			
			foreach($this->that->children_random as $child)
			{
				print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
				
				unset($display_image);
				
				if($child['image'])
				{
					$child_images = $child['image'];
					$child_image_count = count($child_images);
					if($child_image_count)
					{
						shuffle($child_images);
						$child_image = $child_images[0];
						$display_image = $child_image;
					}
				}
				
				if(!$display_image) {
					$display_image = $this->that->master_record['image'][0];
				}
				
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<div class="height-100px width-100px background-color-gray0">');
				print('<div class="vertical-specialcenter">');
				print('<a href="' . $child['Code'] . '/view.php');
				
				if($this->that->entry['ChildAction']) {
					print('?action=' . $this->that->entry['ChildAction']);
				}
				
				print('">');
				print('<img width="');
				print(ceil($display_image['IconPixelWidth'] / 2));
				print('" height="');
				print(ceil($display_image['IconPixelHeight'] / 2));
				print('" src="');
				print($this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
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
				
				$title_max = 50;
				
				if($child['association'] && count($child['association']))
				{
					$title_max = 30;
				}
				
				$child_title_full = $child['Title'];
				$popup_title = 0;
				if(strlen($child_title_full) > $title_max)
				{
					$child_title_full = substr($child_title_full, 0, $title_max) . '...';
					$popup_title = 1;
				}
				
				$child_title = '<a href="' . $child['Code'] . '/view.php';
				
				if($this->that->entry['ChildAction']) {
					$child_title .= '?action=' . $this->that->entry['ChildAction'];
				}
				
				$child_title .= '"';
				
				if($popup_title)
				{
					$child_title .= ' title="' . str_replace('"', '&quot;', $child['Title']) . '"';
				}
				
				$child_title .= '>';
				$child_title .= $child_title_full;
				$child_title .= '</a>';
				
				if($child['association'] && count($child['association']))
				{
					$author = $child['association'][0]['entry'];
					$child_title .= ', by ';
								
					$child_author_full_title = $author['Title'];
					
					$popup_title = 0;
					if(strlen($child_author_full_title) > 20)
					{
						$child_author_full_title = substr($child_author_full_title, 0, 20) . '...';
						$popup_title = 1;
					}
					
					$child_title .= '<a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$author['Code']]) . '"';
					
					if($popup_title)
					{
						$child_title .= ' title="' . str_replace('"', '&quot;', $author['Title']) . '"';
					}
					
					$child_title .= '>';
					$child_title .= $child_author_full_title;
					$child_title .= '</a>';
				}
				
				$div_mouseover = '';
				
				if($child['textbody'])
				{
					$text_bodies = $child['textbody'];
					
					$text_body_count = count($text_bodies);
					if($text_body_count)
					{
						$first_textbody = $text_bodies[0];
						
						$div_mouseover .= number_format($first_textbody['WordCount']) . ' Words / ' . number_format($first_textbody['CharacterCount']) . ' Characters';
					}
				}
				else
				{
					$grand_children = $child['children'];
					
					if($grand_children && is_array($grand_children))
					{
						$grand_children_count = count($grand_children);
						
						if($grand_children_count)
						{
							$grand_child_display = $this->entrysort->Sort(['entries'=>$grand_children]);
							
							unset($grand_child);
							foreach($grand_child_display as $single_grand_child)
							{
								if(!$grand_child)
								{
									$full_grand_child = $single_grand_child;
									$grand_child = $single_grand_child['textbody'][0];
								}
							}
							
							$div_mouseover .= $full_grand_child['Title'] . ' : ' . number_format($grand_child['WordCount']) . ' Words / ' . number_format($grand_child['CharacterCount']) . ' Characters';
						}
					}
				}
				
				print('<div id="header_backgroundimageurl" class="border-2px background-color-gray15 margin-5px float-left" title="' . $div_mouseover . '"><div class="span-header-3"><h3 style="margin:5px;padding:5px;display: inline-block;border:black 2px solid;background-color:#FFFFFF;" class="header-3 padding-0px margin-5px horizontal-left font-family-tahoma">' . $child_title . '</h3></div></div>');
				
				print('<p class="horizontal-left margin-5px font-family-arial">');
				
				$time_frame = '';
				
				if($child['eventdate'])
				{
					$child_event_count = count($child['eventdate']);
					for($i = 0; $i < $child_event_count; $i++)
					{
						$child_event = $child['eventdate'][$i];
							
						if($child_event['Title'] == 'Publication')
						{
							$publication_event = $child_event;
						}
						
						if($publication_event)
						{
							$i = $child_event_count;
						}
					}
					
					if($publication_event)
					{
						if($publication_event['EventDateTime'] != '0000-00-00 00:00:00')
						{
							$event_date_pieces = explode('-', $publication_event['EventDateTime']);
							$year = $event_date_pieces[0];
							$time_frame .= $year;
						}
						else
						{
							$time_frame .= '?';
						}
						
						unset($publication_event);
					}
				}
				
				if($time_frame)
				{
					print($time_frame);
				}
				
				if($child['Subtitle'])
				{
					if($time_frame)
					{
						print(' ~ ');
					}
					
					print('<strong>');
					print($child['Subtitle']);
					print('</strong>');
				}
				
				if($child['description'])
				{
					$description = $child['description'][0];
					
					if($description && $description['Description'])
					{
						print('<em>');
						if($time_frame || $child['Subtitle'])
						{
							print(' : ');
						}
						
						print($description['Description']);
						print(' ');
						print('</em>');
						
						if($description['Source'])
						{
							$source = $description['Source'];
							
							if(strlen($source) > 50)
							{
								$source = substr($source, 0, 50) . '...';
							}
							
							print(' (From : ' . $source . '.)');
						}
					}
				}
				
				if($child['quote'])
				{
					$child_quotes = $child['quote'];
					$child_quotes_count = count($child_quotes);
					$max_limit = $child_quotes_count;
					if($max_limit > 3)
					{
						$max_limit = 3;
					}
					shuffle($child_quotes);
					for($i = 0; $i < $max_limit; $i++)
					{
						$quote = $child_quotes[$i];
						if($quote && $quote['Quote'])
						{
							print(' <br>&bull; ');
							print('"');
							print(str_replace('"', '\'', $quote['Quote']));
							print('"');
							
							if($quote['Source'])
							{
								$source = $quote['Source'];
								
								if(strlen($source) > 50)
								{
									$source = substr($source, 0, 50) . '...';
								}
								
								print(' (From : ' . $source . '.)');
							}
						}
					}
				}
				else
				{
					$printed = 0;
					if($child['textbody'])
					{
						$text_bodies = $child['textbody'];
						
						$text_body_count = count($text_bodies);
						if($text_body_count)
						{
							$text_display = $this->that->cleanser_object->FormatListOutput([
								'text'=>$text_bodies[0]['FirstThousandCharacters'],
							]);
							
							$text_display = strip_tags($text_display);
							
							if(strlen($text_display) > 750)
							{
								$text_display = substr($text_display, 0, 750) . '...';
							}
							
							if($text_display)
							{
								$printed = 1;
								print('<br>');
								print($text_display);
								
								if($text_bodies[0]['Source'])
								{
									$source = $text_bodies[0]['Source'];
									
									if(strlen($source) > 50)
									{
										$source = substr($source, 0, 50) . '...';
									}
									
									print(' (From : ' . $source . '.)');
								}
							}
						}
					}
					
					if(!$printed)
					{
						$grand_children = $child['children'];
						
						if($grand_children && is_array($grand_children))
						{
							$grand_children_count = count($grand_children);
							
							if($grand_children_count)
							{
								$grand_child_display = $this->entrysort->Sort(['entries'=>$grand_children]);
								
								unset($grand_child);
								foreach($grand_child_display as $single_grand_child)
								{
									if(!$grand_child)
									{
										$grand_child = $single_grand_child['textbody'][0];
									}
								}
								
						$text_display = $this->that->cleanser_object->FormatListOutput([
							'text'=>$grand_child['FirstThousandCharacters'],
						]);
								print($text_display);
								
								if(strlen($grand_child['FirstThousandCharacters']) == 1000)
								{
									print('...');
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
				
				if($child['tag'])
				{
					$tag_count = count($child['tag']);
					
					if($tag_count)
					{
						$tags = $child['tag'];
						$max_limit = $tag_count;
						if($max_limit > 10)
						{
							$max_limit = 10;
						}
						shuffle($tags);
						
						for($i = 0; $i < $max_limit; $i++)
						{
							$tag = $tags[$i];
							print('<div class="border-2px background-color-gray15 margin-left-5px margin-bottom-5px float-left">');
							print('<span class="horizontal-left margin-5px font-family-arial">');
							print('<a href="view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
							print($tag['Tag']);
						
							print(' (');
							print(number_format($this->that->tag_counts['children'][$tag['Tag']]));
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
				
				print('</div>');
			}
			print('</div>');
		}
	}

?>
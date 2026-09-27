<?php

	class module_entryheader extends module_spacing {
		public $that;
		public $time_frame;
		public $header_text;
		public $header_subtext;
		public $record_list_count;
		public $title_tags;
		public $images;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->time_frame = $args['time_frame'];
			$this->header_text = $args['header_text'];
			$this->header_subtext = $args['header_subtext'];
			
			$this->record_list_count = count($this->that->record_list);
		}
		
		public function getBackgroundHeaderImage() {
			if(!$this->that->master_record) {
				return [];
			}
			
			$images = $this->that->master_record['image'];
			
			if(!$images) {
				return [];
			}
			
			for($i = 0; $i < count($images); $i++) {
				$image = $images[$i];
				
				if($image['Description'] === 'header') {
					return $image;
				}
			}
			
			return [];
		}
		
		public function Display() {
			$images = $this->getImages();
			$primary_image = $images['primary'];
			$header_cluster = $images['headercluster'];
			
			print('<a name="top"></a>');
			
			$this->Display_OpeningBlock();
			
			$this->Display_LeftIcons(['primary_image'=>$primary_image]);
			$this->Display_CenterText();
			$this->Display_RightIcons(['header_cluster'=>$header_cluster]);
			
			$this->Display_ClosingBlock();
			
			return TRUE;
		}
		
		public function Display_OpeningBlock() {
			$background_image = $this->getBackgroundHeaderImage();
			$background_image_location = '';
			
			if($background_image['id']) {
				$title_tags = $this->getTitleTags();
				$background_image_title = htmlentities($title_tags['header']);
				$background_image_location = '/image/' . implode('/', str_split($background_image['FileDirectory'])) . '/' . $background_image['FileName'];
			} else {
				$background_image_title = 'Image from WikiCommons, Photo by Uroš Novina from Semič, Slovenia, CC BY License';	// Thank you, Uroš, you rock! -- holdoffhunger
				$background_image_location = '/image/background/header/night-sky-of-our-hearts-night-sky-of-our-minds-2.jpg';
			}
			
			print('<div id="header_backgroundimageurl" title="');
			print($background_image_title);
			print('"');
			print(' style="');
			if($background_image_location) {
				print('background-image:url(\'');
				print($background_image_location);
				print('\');');
			}
			print('"');
			print('>');
			
			return TRUE;
		}
		
		public function Display_ClosingBlock() {
			print('</tr></tbody></table>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_LeftIcons($args) {
			$primary_image = $args['primary_image'];
			
			$directory = implode('/', str_split($primary_image['FileDirectory']));
			
			print('<table width="100%"><tbody><tr>');
			
			print('<td width="1"><center><div class="float-left padding-5-px">');
			
			print('<div class="border-2px margin-5px background-color-gray10 ">');
			
	#		print('<a href="/image/' . $directory . '/' . $primary_image['FileName'] . '" target="_blank">');
			
			print('<a href="/">');
			
			print('<img style="max-width:200px;max-height:200px;" src="/image/' . $directory . '/' . $primary_image['IconFileName'] . '" ');
			
			if($primary_image['Title'] || $primary_image['Description']) {
				print('title="');
				if($primary_image['Title']) {
					print(htmlentities($primary_image['Title']));
					
					if($primary_image['Description']) {
						print(': ');
					}
				}
				
				if($primary_image['Description']) {
					print(htmlentities($primary_image['Description']));
				}
				print('" ');
			}
			
			print('>');
			
			print('</a>');	
			
			print('</div>');
			
			print('</div>');
			
			print('</center></td>');
			
			return TRUE;
		}
		
		public function Display_CenterText_Opening() {
			print('<td>');
			
			return TRUE;
		}
		
		public function Display_CenterText_Closing() {
			print('</td>');
			
			return TRUE;
		}
		
		public function Display_CenterText_TextBlock($args) {
			if($args['text'] === '') {
				return FALSE;
			}
			print('<div style="display: inline-block;" class="span-header-1">');
			print('<h1 style="margin:5px;padding:5px;display: inline-block;border:black 2px solid;background-color:#FFFFFF;" class="header-1 margin-0px horizontal-center vertical-center">');
			print($args['text']);
			print('</h1>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_CenterText_getMainText() {
			$text = '';
			
			if($this->header_text) {
				return $this->header_text;
			}
			
			if(count($this->that->record_list) > 2) {
				$parent = $this->that->record_list[count($this->that->record_list) - 2];
				$text .= $parent['Title'];
				$text .= ' &mdash; ';
			}
			
			if($this->that->header_title_text) {
				$text .= $this->that->header_title_text;
			} else {
				$text .= $this->that->entry['Title'];		#	FORMERLY -->	print($this->that->header_title_text);
				if($this->that->entry['Subtitle']) {
					$text .= ': ';
					$text .= $this->that->entry['Subtitle'];
				}
			}
			
			return $text;
		}
		
		public function Display_CenterText_getSecondaryText() {
			if($this->header_subtext) {
				return $this->header_subtext;
			}
			
			$associations = $this->that->entry['association'];
			$author_association = $this->getAuthorAssociation(['associations'=>$associations]);
			$author_entry = $author_association['entry'];
			
			$text = '';
			
			if($author_entry && $author_entry['id']) {		
				$text .= 'By ' . $author_entry['Title'];
				if($this->time_frame) {
					$text .= ' ';
					$text .= '(';
					$text .= $this->time_frame;
					$text .= ')';
				};
			} else {
				if($this->time_frame) {
					$text .= $this->time_frame;
				};
			}
			
			return $text;
		}
		
		public function Display_CenterText() {
			$this->Display_CenterText_Opening();
			
			$this->Display_CenterText_TextBlock(['text'=>$this->Display_CenterText_getMainText()]);
						
			print('<div class="clear-float;"></div>');
			
			$this->Display_CenterText_TextBlock(['text'=>$this->Display_CenterText_getSecondaryText()]);
			
			$this->Display_CenterText_Closing();
			
			return TRUE;
		}
		
		public function Display_RightIcons($args) {
			$header_cluster = $args['header_cluster'];
			
			if(!$header_cluster[0]) {
				return FALSE;
			}
			print('<td width="1">');
			
			print('<center><div class="float-right padding-5-px">');
			
			print('<div class="border-2px margin-5px background-color-gray10 ">');
			
			print('<table><tr><td><center>');
			
			$this->Display_RightIcons_SingleIcon(['icon'=>$header_cluster[0]]);
			
			print('</center></td><td><center>');
			
			$this->Display_RightIcons_SingleIcon(['icon'=>$header_cluster[1]]);
			
			print('</center></td></tr><tr><td><center>');
			
			$this->Display_RightIcons_SingleIcon(['icon'=>$header_cluster[2]]);
			
			print('</center></td><td><center>');
			
			$this->Display_RightIcons_SingleIcon(['icon'=>$header_cluster[3]]);
			
			print('</center></td></tr></table>');
			
			print('</div></div></center>');
			
			return TRUE;
		}
		
		public function Display_RightIcons_SingleIcon($args) {
			$icon = $args['icon'];
			
			if(!$icon || !$icon['id']) {
				return FALSE;
			}
			
			$directory = implode('/', str_split($icon['FileDirectory']));
			
			print('<a href="/image/' . $directory . '/' . $icon['FileName'] . '" target="_blank">');
			print('<img style="max-height:80px;border:1px solid black;" src="/image/' . $directory . '/' . $icon['IconFileName'] . '" ');
			if($icon['Title'] || $icon['Description']) {
				print('title="');
				if($icon['Title']) {
					print(htmlentities($icon['Title']));
					
					if($icon['Description']) {
						print(': ');
					}
				}
				
				if($icon['Description']) {
					print(htmlentities($icon['Description']));
				}
				print('" ');
			}
			print('>');
			print('</a>');
			
			return TRUE;
		}
		
		public function getTitleTags() {
			if($this->title_tags) {
				return $this->title_tags;
			}
			
			$header_tag = $this->getTitleTags_headerTag();
			
			$title_tags = [
				'header'=>$header_tag,
			];
			
			return $this->title_tags = $title_tags;
		}
		
		public function getTitleTags_headerTag() {
			$header_tag = '';
			
			if($this->that->entry['quote'] && $this->that->entry['quote'][0]) {
				$quote = $this->that->entry['quote'][0];
				
				if($quote && $quote['id']) {
					$header_tag = '"' . $quote['Quote'] . '"';
					
					return $header_tag;
				}
			}
			
			if($this->that->master_record['quote'] && $this->that->master_record['quote'][0]) {
				$quote = $this->that->master_record['quote'][0];
				
				if($quote && $quote['id']) {
					$header_tag = '"' . $quote['Quote'] . '"';
					
					return $header_tag;
				}
			}
			
			return $this->getTitleTags_headerTag_default();
		}
		
		public function getTitleTags_headerTag_default() {
			return 'Revolution';
		}
		
			/* getImages()
			
				What Needs To Be Done:
					* Single Main Image (either the first Author Image, the Top-Category Image, or the Main Site Image)
						* Left of Header
					* Four Secondary Images
						* Right of Header, Grid Formation
						* Two Parents, Two Author Images
					* Ten Tertiary Images
			
				Order by Most to Least Important:
					* associated author image #1
					* random associated author image
					* top-category image
					* remaining associated people
					* chapter images
					* main site image (if there are fewer than 3 images so far)
			
			*/
		
		public function getImages() {
						# BT: NEW EDGE CASE: main image display on a document that only has child-documents and no textbody itself (fix, fix fix!!!! this is an in-use edge-case!)
		
			if($this->images) {
				return $this->images;
			}
			
			$primary_image = [];
			$header_cluster_images = [];	# TODO: Randomize these
			$body_images = [];
			if($this->that->entry) {
					# Basics
					
				$entry = $this->that->entry;
				
					# People Page Stuff
				
				if($this->that->parent['Code'] === 'people' || $this->that->counts['textbody'] === 0) {
					$images = $entry['image'];
					
					if($images) {
						if($images[0]) {
							$primary_image = $images[0];
						}
						if($images[1]) {
							$header_cluster_images[] = $images[1];
						}
					}
				}
				
					# Main Author Stuff
					
				$associations = $entry['association'];
				$author_association = $this->getAuthorAssociation(['associations'=>$associations]);
				if($author_association && $author_association['id']) {
					$author_images = $author_association['entry']['image'];
					if($author_images && $author_images[0] && $author_images[0]['id']) {
						if(!$primary_image) {
							$primary_image = $author_images[0];
						}
						
						if($author_images[1]) {
							$header_cluster_images[] = $author_images[1];
						}
						
						$author_image_count = count($author_images);
						
						for($i = 2; $i < $author_image_count; $i++) {
							$body_images[] = $author_images[$i];
						}
					}
				}
				
				if(!$primary_image['id'] && $entry && $entry['image'] && $entry['image'][0]) {
					$images = $entry['image'];
					
					$primary_image = $images[0];
					
					for($i = 1; $i < min(6, count($images)); $i++) {
						$image = $images[$i];
						if($image['Description'] !== 'header' && count($header_cluster_images) < 4) {
							$header_cluster_images[] = $image;
						}
					}
				}
				
					# Parent Stuff
				if(count($header_cluster_images) < 4) {
					if($entry['id'] !== $this->that->master_record['id']) {
						if($this->that->master_record['image'] && $this->that->master_record['image'][0]) {
							$image = $this->that->master_record['image'][0];
							if($image['Description'] !== 'header') {
								if($primary_image['id']) {
									$header_cluster_images[] = $image;
								} else {
									$primary_image = $image;
								}
							}
						}
					}
				}
				
					# People Page Stuff
				
				if($this->that->parent['Code'] === 'people') {
					$images = $entry['image'];
					
					if($images) {
						if($images[2]) {
							$header_cluster_images[] = $images[2];
						}
					}
				}
				
					# Alternate Role Stuff
				
				if($associations) {
					$association_count = count($associations);
					for($i = 0; $i < $association_count; $i++) {
						$association = $associations[$i];
						
						if($association['id'] !== $author_association['id']) {
							$association_images = $association['entry']['image'];
							if($association_images) {
								$association_images_count = count($association_images);
								if($association_images_count > 2) {
									$association_images_count = 2;
								}
								for($j = 0; $j < $association_images_count; $j++) {
									$association_image = $association_images[$j];
									if(count($header_cluster_images) === 2) {
										$body_images[] = $association_image;
									} else {
										$header_cluster_images[] = $association_image;
									}
								}
							}
						}
					}
				}
				
					# Record List Stuff
				
				if(count($header_cluster_images) < 4) {
					$record_list = $this->that->record_list;
					for($i = 0; $i < count($record_list); $i++) {
						$record = $record_list[$i];
						if($record['id'] !== $this->that->entry['id']) {
							$images = $record['image'];
							if($images && $images[0] && $images[0]['id']) {
								for($j = 0; $j < count($images); $j++) {
									$header_cluster_images[] = $images[$j];
									
									if(count($header_cluster_images) >= 4) {
										$j = count($images);
									}
								}
							}
						}
						
						if(count($header_cluster_images) >= 4) {
							$i = count($record_list);
						}
					}
				}
				
					# Child Record Stuff
				
				if(count($header_cluster_images) < 4) {
					$children_count = $this->that->children ? count($this->that->children) : 0;
					
					for($i = 0; $i < $children_count; $i++) {
						$child = $this->that->children[$i];
						if($child['image'] && $child['image'][0]) {
							$header_cluster_images[] = $child['image'][0];
							
							if(count($header_cluster_images) === 4) {
								$i = $children_count;
							}
						}
					}
				}
				
					# Parent Stuff
				if(count($header_cluster_images) < 0) {
					if($this->that->master_record['image'] && $this->that->master_record['image'][0]) {
						$image = $this->that->master_record['image'][0];
						if($image['Description'] !== 'header') {
							if($primary_image['id']) {
								$header_cluster_images[] = $image;
							} else {	
								$primary_image = $image;
							}
						}
					}
				}
				
				if(count($header_cluster_images) < 4) {
					$images = $entry['image'];
					
					if($images) {
						for($i = 3; $i < count($images); $i++) {
							$image = $images[$i];
							if($image['Description'] !== 'header') {
								if(count($header_cluster_images) > 3) {
									$i = 5;
								} else {
									$header_cluster_images[] = $image;
								}
							}
						}
					}
				}
				
					# Grandchildren Stuff
				
				if(count($header_cluster_images) < 4) {
					if($this->that->children) {
						$children_count = count($this->that->children);
						
						for($i = 0; $i < $children_count; $i++) {
							$child = $this->that->children[$i];
							
							$grandchildren = $child['children'];
							
							if($grandchildren) {
								$grandchildren_count = count($grandchildren);
								for($j = 0; $j < $grandchildren_count; $j++) {
									$grandchild = $grandchildren[$j];
									if($grandchild['image'] && $grandchild['image'][0]) {
										$header_cluster_images[] = $grandchild['image'][0];
										
										if(count($header_cluster_images) === 4) {
											$i = $children_count;
											$j = $grandchildren_count;
										}
									}
								}
							}
						}
					}
				}
			}
			
			return $this->images = [
				'primary'=>$primary_image,
				'headercluster'=>$header_cluster_images,
				'body'=>$body_images,
			];
		}
		
		public function getAuthorAssociation($args) {
			if($this->author_association){
				return $this->author_association;
			}
			
			$associations = $args['associations'];
			
			if($associations) {
				$association_count = count($associations);
				
				$author_association = [];
				for($i = 0; $i < $association_count; $i++) {
					$association = $associations[$i];
					if($association['Type'] === 'Role' && $association['SubType'] === 'Author') {
						$author_association = $association;
						$i = $association_count;
					}
				}
			}
			
			return $author_association;
		}
	}

?>
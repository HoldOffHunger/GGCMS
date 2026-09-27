<?php

	class module_entryindexheader extends module_entryheader {
		public $that;
		public $main_text;
		public $sub_text;
		public $sub2_text;
		public $sub_title;
		public $record_list_count;
		public $images;
		
		public function __construct($args) {
			$this->that = $args['that'];
			
			$this->main_text = $args['main_text'];
			$this->sub_text = $args['sub_text'];
			$this->sub2_text = $args['sub2_text'];
			$this->sub_title = $args['sub_title'];
			
			$this->record_list_count = count($this->that->record_list);
		}
		
		public function Display() {
			$images = $this->getImages();
			$primary_image = $images['primary'];
			$header_cluster = $images['headercluster'];
			
			$this->Display_OpeningBlock();
			
			$this->Display_LeftIcons(['primary_image'=>$primary_image]);
			$this->Display_CenterText();
			$this->Display_RightIcons(['header_cluster'=>$header_cluster]);
			
			$this->Display_ClosingBlock();
			
			return TRUE;
		}
		
		public function Display_ClosingBlock() {
			print('</tr></tbody></table>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_LeftIcons($args) {
			$primary_image = $args['primary_image'];
			
			if(!$primary_image) {
				return TRUE;
			}
			
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
			
			if(count($this->that->record_list) > 2) {
				$parent = $this->that->record_list[count($this->that->record_list) - 2];
				$text .= $parent['Title'];
				$text .= ' &mdash; ';
			}
			
			$text .= $this->that->entry['Title'];		#	FORMERLY -->	print($this->that->header_title_text);
			
			if($this->that->entry['Subtitle']) {
				$text .= ': ';
				$text .= $this->that->entry['Subtitle'];
			}
			
			return $text;
		}
		
		public function Display_CenterText_getSecondaryText() {
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
			
			$this->Display_CenterText_TextBlock(['text'=>$this->main_text]);
						
			print('<div class="clear-float;"></div>');
			
			$this->Display_CenterText_SubTextBlock(['text'=>$this->sub_text, 'title'=>$this->sub_title, 'subtitle'=>$this->sub2_text]);

			print('<div class="clear-float;"></div>');
			
			$this->Display_CenterText_Closing();
		}
		
		public function Display_CenterText_SubTextBlock($args) {
			if($args['text'] === '') {
				return FALSE;
			}
			
			print('<center style="font-family:arial;">');
			print('<div class="horizontal-center" style="width:90%; background-color:#fff;">');
			print('<div class="border-2px margin-5px float-left" style="background-color:#fff;"');
			
			if($args['title']) {
				print(' title="');
				print(htmlentities($args['title']));
				print('"');
			}
			
			print('>');
			
			print('<strong>');
			
			if($args['subtitle']) {
				print('<h3 style="margin:5px;padding:5px;border:black 2px solid;background-color:#CCC; float:right; font-family: arial;" class="padding-0px margin-5px font-family-arial">' . $args['subtitle'] . '</h3>');
			}
			
			print($args['text']);
			
			print('</strong>');
			
			print('</div>');
			print('</div>');
			print('</center>');
			
			print('<div class="clear-float"></div>');
			return TRUE;
		}
		
		public function getImages() {
						# BT: NEW EDGE CASE: main image display on a document that only has child-documents and no textbody itself (fix, fix fix!!!! this is an in-use edge-case!)
		
			if($this->images) {
				return $this->images;
			}
			
			$primary_image = [];
			$header_cluster_images = [];	# TODO: Randomize these
			$body_images = [];
			
			if($this->that->entry || $this->that->master_record) {
					# Basics
					
				$entry = $this->that->entry;
				
					# People Page Stuff
				
				$images = $entry['image'];
				
				if($images && $images[0]) {
					$primary_image = $images[0];
				}
				
					# Main Author Stuff
					
				$associations = $entry['association'];
				$author_association = $this->getAuthorAssociation(['associations'=>$associations]);
				if($author_association && $author_association['id']) {
					$author_images = $author_association['entry']['image'];
					if($author_images && $author_images[0] && $author_images[0]['id']) {
						$primary_image = $author_images[0];
						
						if($author_images[1]) {
							$header_cluster_images[] = $author_images[1];
						}
						
						$author_image_count = count($author_images);
						
						for($i = 2; $i < $author_image_count; $i++) {
							$body_images[] = $author_images[$i];
						}
					}
				}
				
					# Parent Stuff
				
				if(count($header_cluster_images) < 4) {
					if($this->that->master_record['id'] !== $entry['id'] && $this->that->master_record['image'] && $this->that->master_record['image'][0]) {
						$image = $this->that->master_record['image'][0];
						if($primary_image['id']) {
							$header_cluster_images[] = $image;
						} else {
							$primary_image = $image;
						}
					}
				}
				
					# Main Author Stuff
				
				if($images) {
					$image_count = count($images);
					
					for($i = 1; $i < $image_count; $i++) {
						$image = $images[$i];
						if($image['Description'] !== 'header') {
							$header_cluster_images[] = $image;
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
								#	if(count($header_cluster_images) === 2) {
										$body_images[] = $association_image;
								#	} else {
								#		$header_cluster_images[] = $association_image;
								#	}
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
					if($this->that->children) {
						$children_count = count($this->that->children);
						
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
				}
				
					# Parent Stuff
					
				if(!$primary_image['id']) {
					if($this->that->master_record['image'] && $this->that->master_record['image'][0]) {
						$image = $this->that->master_record['image'][0];
						$primary_image = $image;
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
	}

?>
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
		
			/*
				The head of an index page -- a site's front page, a section's
				list -- with a paragraph of introduction and, beside it, the
				section's figures.  sub_text is the introduction, as HTML;
				sub2_text is the aside; sub_title is the aside's hover text.
			*/

		public function Display() {
			print('<a id="top"></a>');

			$this->DisplaySiteBar();

			$images = $this->getImages();

			print('<div class="page-head page-head-index">');
			print('<div class="page-head-inner">');

			$this->DisplayPageHeadImage(['image'=>$images['primary']]);

			print('<div class="page-head-text">');
			$this->DisplayKicker();
			print('<h1 class="page-title">' . $this->TitleText() . '</h1>');
			$this->DisplayIntroduction();
			print('</div>');

			$this->DisplayPageHeadCluster(['images'=>$images['headercluster']]);

			print('</div>');
			print('</div>');

			return TRUE;
		}

		public function TitleText() {
			if(strlen((string) $this->main_text)) {
				return $this->main_text;
			}

			return $this->that->entry['Title'];
		}

		public function DisplayIntroduction() {
			if(strlen((string) $this->sub_text)) {
				print('<div class="page-intro">' . $this->sub_text . '</div>');
			}

			if(strlen((string) $this->sub2_text)) {
				print('<p class="page-aside"');

				if($this->sub_title) {
					print(' title="' . htmlentities($this->sub_title) . '"');
				}

				print('>' . $this->sub2_text . '</p>');
			}

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
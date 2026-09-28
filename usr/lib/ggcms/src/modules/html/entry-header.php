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
		
			/*
				The site bar, then the page's head: its picture, where it sits,
				its title and who wrote it.  A template that wants the title
				somewhere else -- inside the reading sheet, say -- calls
				DisplaySiteBar() and DisplayTitleBlock() itself instead.
			*/

		public function Display() {
			print('<a id="top"></a>');

			$this->DisplaySiteBar();
			$this->DisplayPageHead();

			return TRUE;
		}

		public function DisplaySiteBar() {
			require_once(GGCMS_DIR . 'modules/html/site-bar.php');

			$site_bar = new module_sitebar(['that'=>$this->that]);
			$site_bar->Display();

			return TRUE;
		}

		public function DisplayPageHead() {
			$images = $this->getImages();

			print('<div class="page-head">');
			print('<div class="page-head-inner">');

			$this->DisplayPageHeadImage(['image'=>$images['primary']]);

			print('<div class="page-head-text">');
			$this->DisplayTitleBlock();
			print('</div>');

			$this->DisplayPageHeadCluster(['images'=>$images['headercluster']]);

			print('</div>');
			print('</div>');

			return TRUE;
		}

		public function DisplayTitleBlock() {
			$this->DisplayKicker();

			print('<h1 class="page-title">' . $this->TitleText() . '</h1>');

			if(!$this->header_text && $this->that->entry['Subtitle']) {
				print('<p class="page-subtitle">' . $this->that->entry['Subtitle'] . '</p>');
			}

			$this->DisplayByline();

			return TRUE;
		}

			/*
				Where the page sits: its parent, linked.  The old header wrote
				"Parent — Title" into the title itself.
			*/

		public function DisplayKicker() {
			$parent = $this->that->parent;

				/*
					Not for a section at the top of the site: its parent is the
					site itself, which the site bar has already named.
				*/

			if(!$parent || empty($parent['Title']) || ($this->that->master_record && $parent['id'] === $this->that->master_record['id'])) {
				return FALSE;
			}

			print('<p class="page-kicker">');
			print('<a href="' . $this->ParentURL() . '">' . $parent['Title'] . '</a>');
			print('</p>');

			return TRUE;
		}

		public function ParentURL() {
			$pieces = is_array($this->that->object_list) ? $this->that->object_list : [];
			array_pop($pieces);

			if(!$pieces) {
				return '/';
			}

			return '/' . implode('/', $pieces) . '/view.php';
		}

			/*
				Author and dates, then the length of the text and how long it
				takes to read -- the two things a reader decides by.
			*/

		public function DisplayByline() {
			$pieces = [];

			$author = $this->getAuthorAssociation(['associations'=>$this->that->entry['association']]);
			$author_entry = $author ? $author['entry'] : NULL;

			if($author_entry && $author_entry['id']) {
				$author_text = 'By ';

				if(method_exists($this->that, 'EntryAssociationURL')) {
					$author_text .= '<a class="page-byline-author" href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$author_entry['Code']]) . '">' . $author_entry['Title'] . '</a>';
				} else {
					$author_text .= $author_entry['Title'];
				}

				$pieces[] = $author_text;
			}

			if($this->time_frame) {
				$pieces[] = $this->time_frame;
			}

			$words = $this->WordCount();

			if($words) {
				$pieces[] = number_format($words) . ' words';
				$pieces[] = $this->ReadingTime(['words'=>$words]);
			}

			if(!$pieces) {
				return FALSE;
			}

			print('<p class="page-byline">');
			print(implode('<span class="dot" aria-hidden="true"> &middot; </span>', $pieces));
			print('</p>');

			return TRUE;
		}

		public function WordCount() {
			if(empty($this->that->entry['textbody']) || empty($this->that->counts['textbody'])) {
				return 0;
			}

			$words = 0;

			foreach($this->that->entry['textbody'] as $textbody) {
				$words += (int) $textbody['WordCount'];
			}

			return $words;
		}

			// At 250 words a minute, the usual figure for reading prose.

		public function ReadingTime($args) {
			$minutes = (int) round($args['words'] / 250);

			if($minutes < 1) {
				return 'Under a minute';
			}

			if($minutes < 90) {
				return 'About ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's');
			}

			return 'About ' . round($minutes / 60) . ' hours';
		}

		public function DisplayPageHeadImage($args) {
			$image = $args['image'];

			if(!$image || empty($image['id'])) {
				return FALSE;
			}

			print('<figure class="page-head-image">');
			print('<img src="' . $this->ImageURL(['image'=>$image, 'icon'=>TRUE]) . '" alt=""' . $this->ImageTitle(['image'=>$image]) . '>');
			print('</figure>');

			return TRUE;
		}

		public function DisplayPageHeadCluster($args) {
			$images = array_slice(array_values(array_filter((array) $args['images'], function($image) {
				return $image && !empty($image['id']);
			})), 0, 4);

			if(!$images) {
				return FALSE;
			}

			print('<div class="page-head-cluster" aria-hidden="true">');

			foreach($images as $image) {
				print('<a href="' . $this->ImageURL(['image'=>$image]) . '" target="_blank" tabindex="-1">');
				print('<img src="' . $this->ImageURL(['image'=>$image, 'icon'=>TRUE]) . '" alt=""' . $this->ImageTitle(['image'=>$image]) . '>');
				print('</a>');
			}

			print('</div>');

			return TRUE;
		}

		public function ImageURL($args) {
			$image = $args['image'];
			$file = (!empty($args['icon']) && $image['IconFileName']) ? $image['IconFileName'] : $image['FileName'];

			return '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $file;
		}

		public function ImageTitle($args) {
			$image = $args['image'];
			$title = trim($image['Title'] . ($image['Title'] && $image['Description'] ? ': ' : '') . $image['Description']);

			if(!strlen($title)) {
				return '';
			}

			return ' title="' . htmlentities($title) . '"';
		}

			/*
				The title alone.  Its parent is the kicker above it and its
				subtitle the line below; the old header ran all three into one.
			*/
		
		public function TitleText() {
			if($this->header_text) {
				return $this->header_text;
			}
			
			return $this->that->entry['Title'];
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
<?php

	class module_entrydescription extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
		}
		
		public function Display() {		
			if($this->that->entry['description'] && $this->that->counts['description'] !== 0) {
						// Description Header
					
					// -------------------------------------------------------------
					
				print('<a name="description"></a>');
				
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
				
						// Finish Description Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Display Description
					
					// -------------------------------------------------------------
				
				$description = $this->that->entry['description'][0];
				print('<center>');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
				print('<div class="border-2px background-color-gray15 margin-5px horizontal-left">');
				print('<p class="horizontal-left margin-5px font-family-arial">');
				print($description['Description']);
				print('</p>');
				
					/*
						The test has to be on tag_counts, because that is what is
						used.  entry['tag'] and counts['tag'] describe the entry's
						tags; tag_counts is a separate property filled in by
						SetTagCounts, and an entry can have tags on a render where
						that has not run.  arsort(NULL) is then a TypeError that
						takes the whole page down over a tag list.
					*/

				if($this->that->entry['tag'] && $this->that->counts['tag'] !== 0 && is_array($this->that->tag_counts)) {
					print('<p class="horizontal-left margin-5px font-family-tahoma float-left"><b>Top Tags :</b> ');
					$entry_tags = $this->that->tag_counts;
					arsort($entry_tags);
					$entry_tag_keys = array_keys($entry_tags);
					$tags_max = min(3, count($entry_tags));
					for($i = 0; $i < $tags_max; $i++) {
						$tag = $entry_tag_keys[$i];
						
						print('<div class="border-2px background-color-gray13 margin-left-5px margin-top-5px margin-bottom-5px float-left">');
						print('<span class="horizontal-left margin-5px font-family-arial ">');
						print('<a href="/view.php?action=browseByTag&tag=' . urlencode($tag) . '">');
						print($tag);
						
						print(' (');
						print(number_format($this->that->tag_counts[$tag]));
						print(')');
						
						print('</a>');
						print('</span>');
						print('</div>');
					}
					print('</p>');
				}
				
				if($description['Source']) {
					print('<div class="float-right border-2px margin-5px background-color-gray13">');
					
					print('<p class="horizontal-left margin-5px font-family-arial">');
					print('From : ');
					print($this->that->HyperlinkizeText(['text'=>$description['Source']]));
					print('</p>');
					print('</div>');
				}
				
						// Finish Floated Box
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
				print('</div>');
				print('</div>');
				print('</center>');
			}
		}
	}

?>
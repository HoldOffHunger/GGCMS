<?php

	class module_entrynavigation extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return $this;
		}
		
		public function BackToTopLinkBox() {
			print('<div style="margin-right:50px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray13 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#top">');
			print('<nobr>');
			print('Back to Top');
			print('</nobr>');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplaySiblingNavigation($args) {
			if($this->that->counts['younger_sibling'] || $this->that->counts['older_sibling']) {
						// Navigation Header
					
					// -------------------------------------------------------------
					
				print('<a name="siblings"></a>');
				
				print('<center>');
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<h2 class="horizontal-left margin-5px font-family-arial">');
				print('Navigation');
				print('</h2>');
				print('</div>');
				print('</div>');
				print('</center>');
				
				$this->BackToTopLinkBox();
				
						// Finish Textbody Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Start Next/Last Styling
					
					// -------------------------------------------------------------
					
				print('<center>');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
				
						// Display Table Start
					
					// -------------------------------------------------------------
				
				print('<table width="100%">');
				print('<tr>');
				
						// Display Last Entry
					
					// -------------------------------------------------------------
				
				print('<td width="33%" class="width-33percent">');
				
				print('<center>');
				print('<span class="font-family-arial font-size-150percent margin-10px border-2px background-color-gray15">');
				print('<div style="display:inline;" class="margin-10px">');
				print('&lt;&lt; Last Entry in ' . $this->that->parent['Title']);
				print('</div>');
				print('</span>');
				print('</center>');
				
						// Clear Float
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Last Entry?
					
					// -------------------------------------------------------------
				
				if($this->that->counts['younger_sibling']) {
					$oldest_young_sibling = $this->that->younger_siblings[0];
					$sibling_descriptions = $oldest_young_sibling['description'];
					
					if($sibling_descriptions && count($sibling_descriptions)) {
						$first_sibling_description = $sibling_descriptions[0];
						$last_sibling_mouseover_text = str_replace('"', '&quot;', $first_sibling_description['Description']);
					}
					
					$younger_sibling_text = '<a href="' . $this->that->EntrySiblingURL(['code'=>$oldest_young_sibling['Code']]) . '">';
					$younger_sibling_text .= $oldest_young_sibling['Title'];
					$younger_sibling_text .= '</a>';
					
				#	print("<!-- BT:");
				#	print_r($oldest_young_sibling['image']);
				#	print("-->");
				} else {
					$younger_sibling_text = 'This is the first item.';
				}
				
				print('<div id="header_backgroundimageurl" class="border-2px background-color-gray15 margin-5px"');
				if($last_sibling_mouseover_text) {
					print(' title="' . $last_sibling_mouseover_text . '"');
				}
				print('>');
				print('<div class="margin-10px">');
				print('<span class="font-family-tahoma font-size-125percent">');
				
				print($younger_sibling_text);
				
				if($oldest_young_sibling['image'] && $oldest_young_sibling['image'][0]) {
					$display_image = $oldest_young_sibling['image'][0];
					$url = '/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'];
					print('<a href="' . $this->that->EntrySiblingURL(['code'=>$oldest_young_sibling['Code']]) . '">');
					print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
					print('</a>');
				}
				
				if($oldest_young_sibling['link'] && $oldest_young_sibling['link'][1] && $oldest_young_sibling['link'][1]['Title'] === 'Image') {
					$url = $oldest_young_sibling['link'][1]['URL'];
					print('<a href="' . $this->that->EntrySiblingURL(['code'=>$oldest_young_sibling['Code']]) . '">');
					print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
					print('</a>');
				}
				
				print('</span>');
				print('<div style="clear: both;"></div>');
				print('</div>');
				print('</div>');
				
						// End Displaying Last Entry
					
					// -------------------------------------------------------------
				
				print('</td>');
				
						// Display Current Entry
					
					// -------------------------------------------------------------
				
				print('<td width="33%" class="width-33percent">');
				
				print('<center>');
				print('<span class="font-family-arial font-size-150percent margin-10px border-2px background-color-gray15">');
				print('<div style="display:inline;" class="margin-10px">');
				print('Current Entry in ' . $this->that->parent['Title']);
				print('</div>');
				print('</span>');
				print('</center>');
				
						// Clear Float
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// This Entry?
					
					// -------------------------------------------------------------
				
				if($this->that->counts['description']) {
					$first_description = $this->that->entry['description'][0];
					
					$current_navigation_item_mouseover = str_replace('"', '&quot;', $first_description['Description']);
				}
				
				print('<div id="header_backgroundimageurl" class="border-2px background-color-gray15 margin-5px"');
				
				if($current_navigation_item_mouseover) {
					print(' title="' . $current_navigation_item_mouseover . '"');
				}
				
				print('>');
				print('<div class="margin-10px">');
				print('<span class="font-family-tahoma font-size-125percent">');
				
				print($this->that->entry['Title']);
				
				print('</span>');
				
				if($this->that->entry['image'] && $this->that->entry['image'][0]) {
					$display_image = $this->that->entry['image'][0];
					$url = '/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'];
					print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
				}
				
				if($this->that->entry['link'] && $this->that->entry['link'][1] && $this->that->entry['link'][1]['Title'] === 'Image') {
					$url = $this->that->entry['link'][1]['URL'];
					print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
				}
				
				print('<div style="clear: both;"></div>');
				
				print('</div>');
				print('</div>');
				
						// End Displaying Last Entry
					
					// -------------------------------------------------------------
					
				print('</td>');
				
						// Display Next Entry
					
					// -------------------------------------------------------------
				
				print('<td width="33%" class="width-33percent">');
				
				print('<center>');
				print('<span class="font-family-arial font-size-150percent margin-10px border-2px background-color-gray15">');
				print('<div style="display:inline;" class="margin-10px">');
				print('Next Entry in ' . $this->that->parent['Title'] . ' &gt;&gt;');
				print('</div>');
				print('</span>');
				print('</center>');
				
						// Clear Float
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Last Entry?
					
					// -------------------------------------------------------------
				
				if($this->that->counts['older_sibling']) {
					$youngest_old_sibling = $this->that->older_siblings[0];
					$sibling_descriptions = $youngest_old_sibling['description'];
					
					if($sibling_descriptions && count($sibling_descriptions)) {
						$first_sibling_description = $sibling_descriptions[0];
						$next_sibling_mouseover_text = str_replace('"', '&quot;', $first_sibling_description['Description']);
					}
					
					$next_sibling_text = '<a href="' . $this->that->EntrySiblingURL(['code'=>$youngest_old_sibling['Code']]) . '">';
					$next_sibling_text .= $youngest_old_sibling['Title'];
					$next_sibling_text .= '</a>';
				} else {
					$next_sibling_text = 'This is the last item.';
				}
				
				print('<div id="header_backgroundimageurl" class="border-2px background-color-gray15 margin-5px"');
				if($next_sibling_mouseover_text) {
					print(' title="' . $next_sibling_mouseover_text . '"');
				}
				print('>');
				print('<div class="margin-10px">');
				print('<span class="font-family-tahoma font-size-125percent">');
				
				print($next_sibling_text);
				
				print('</span>');
				
				if($youngest_old_sibling['image'] && $youngest_old_sibling['image'][0]) {
					$display_image = $youngest_old_sibling['image'][0];
					$url = '/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'];
					print('<a href="' . $this->that->EntrySiblingURL(['code'=>$youngest_old_sibling['Code']]) . '">');
					print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
					print('</a>');
				}
				
				if($youngest_old_sibling['link'] && $youngest_old_sibling['link'][1] && $youngest_old_sibling['link'][1]['Title'] === 'Image') {
					$url = $youngest_old_sibling['link'][1]['URL'];
					print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
				}
				
				print('<div style="clear: both;"></div>');
				
				print('</div>');
				print('</div>');
				
						// End Displaying Last Entry
					
					// -------------------------------------------------------------
					
				print('</td>');
				
						// Display Last/Next Ten Header
					
					// -------------------------------------------------------------
				
				print('</tr>');
				
				print('<tr>');
				print('<td colspan="3">');
				
				print('<center>');
				print('<span class="font-family-arial font-size-150percent margin-10px border-2px background-color-gray15">');
				print('<div style="display:inline;" class="margin-10px">');
				print('All Nearby Items in ' . $this->that->parent['Title']);
				print('</div>');
				print('</span>');
				
				print('<div class="margin-10px border-2px background-color-gray15 width-70percent horizontal-left">');
				print('<ul class="font-family-arial font-size-125percent" type="disc">');
				
				if($this->that->counts['younger_sibling']) {
					for($i = 0; $i < $this->that->counts['younger_sibling']; $i++) {
						$younger_sibling = $this->that->younger_siblings[$i];
						
						print('<li>');
						print('<a href="' . $this->that->EntrySiblingURL(['code'=>$younger_sibling['Code']]) . '">');
						print($younger_sibling['ListTitle']);
						
						$younger_sibling_descriptions = $younger_sibling['description'];
						
						if($younger_sibling_descriptions && count($younger_sibling_descriptions) && $younger_sibling_descriptions[0]['Description'])
						{
							print(' - ');
							
							print('<em>');
							print($younger_sibling_descriptions[0]['Description']);
							print('</em>');
						}
						
						print('</a>');
						
						if($younger_sibling['association'][0] && $younger_sibling['association'][0]['entry']['id'])
						{
							print(', by ');
							print('<a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$younger_sibling['association'][0]['entry']['Code']]) . '">');
							print($younger_sibling['association'][0]['entry']['Title']);
							print('</a>');
						}
						
						if($younger_sibling['image'] && $younger_sibling['image'][0]) {
							$display_image = $younger_sibling['image'][0];
							$url = '/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'];
							print('<a href="' . $this->that->EntrySiblingURL(['code'=>$younger_sibling['Code']]) . '">');
							print('<img style="max-height:25px;max-width:25px;float:right;margin-top:-10px;margin-bottom:-10px;" src="' . $url . '">');
							print('</a>');
							print('<div style="clear: both;"></div>');
						}
						
						if($younger_sibling['link'] && $younger_sibling['link'][1] && $younger_sibling['link'][1]['Title'] === 'Image') {
							$url = $younger_sibling['link'][1]['URL'];
							print('<img style="max-height:80px;max-width:80px;float:right;margin-top:-10px;margin-bottom:-10px;margin-right:-11px;" src="' . $url . '">');
							print('<div style="clear: both;"></div>');
						}
						
						print('</li>');
					}
				}
				
				print('<li>');
				print($this->that->entry['ListTitle']);
				
				if($this->that->counts['description'] && $this->that->entry['description'][0]['Description'])
				{
					print(' - ');
					
					print('<em>');
					print($this->that->entry['description'][0]['Description']);
					print('</em>');
				}
				
				if($this->that->entry['image'] && $this->that->entry['image'][0]) {
					$display_image = $this->that->entry['image'][0];
					$url = '/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'];
				
					print('<img style="max-height:25px;max-width:25px;float:right;margin-top:-10px;margin-bottom:-10px;" src="' . $url . '">');
				}
				
				if($this->that->entry['link'] && $this->that->entry['link'][1] && $this->that->entry['link'][1]['Title'] === 'Image') {
					$url = $this->that->entry['link'][1]['URL'];
					print('<img style="max-height:25px;max-width:25px;float:right;margin-top:-10px;margin-bottom:-10px;" src="' . $url . '">');
				}
				
				print('</li>');
				
				if($this->that->counts['older_sibling']) {
					function cmp($a, $b) {
					    return strcmp($a['ListTitle'], $b->name['ListTitle']);
					}
					
					usort($this->that->older_siblings, "cmp");
					for($i = 0; $i < $this->that->counts['older_sibling']; $i++)
					{
						$older_sibling = $this->that->older_siblings[$i];
						
						print('<li>');
						print('<a href="' . $this->that->EntrySiblingURL(['code'=>$older_sibling['Code']]) . '">');
						print($older_sibling['ListTitle']);
						
						$older_sibling_descriptions = $older_sibling['description'];
						
						if($older_sibling_descriptions && count($older_sibling_descriptions) && $older_sibling_descriptions[0]['Description'])
						{
							print(' - ');
							
							print('<em>');
							print($older_sibling_descriptions[0]['Description']);
							print('</em>');
						}
						
						print('</a>');
						
						if($older_sibling['association'][0] && $older_sibling['association'][0]['entry']['id'])
						{
							print(', by ');
							print('<a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$older_sibling['association'][0]['entry']['Code']]) . '">');
							print($older_sibling['association'][0]['entry']['Title']);
							print('</a>');
						}
						
						if($older_sibling['image'] && $older_sibling['image'][0]) {
							$display_image = $older_sibling['image'][0];
							$url = '/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'];
							print('<a href="' . $this->that->EntrySiblingURL(['code'=>$older_sibling['Code']]) . '">');
							print('<img style="max-height:25px;max-width:25px;float:right;" src="' . $url . '">');
							print('</a>');
							print('<div style="clear: both;"></div>');
						}
						
						if($older_sibling['link'] && $older_sibling['link'][1] && $older_sibling['link'][1]['Title'] === 'Image') {
							$url = $older_sibling['link'][1]['URL'];
							print('<a href="' . $this->that->EntrySiblingURL(['code'=>$older_sibling['Code']]) . '">');
							print('<img style="max-height:25px;max-width:25px;float:right;" src="' . $url . '">');
							print('</a>');
							print('<div style="clear: both;"></div>');
						}
						
						print('</li>');
					}
					print('</ul>');
				}
				
				print('</div>');
				
				print('</center>');
				
				print('</td>');
				print('</tr>');
				
						// Display Table End
					
					// -------------------------------------------------------------
				
				print('</tr>');
				print('</table>');
				
						// End Next/Last Styling
					
					// -------------------------------------------------------------
				
				print('</div>');
				print('</center>');
			}
			
			return TRUE;
		}
		
		public function DisplayTOC() {
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			
			print('<ul type="1" class="margin-5px font-family-arial">');
			
			if($this->that->entry['description'] && $this->that->counts['description'] !== 0) {
				print('<li><a href="#description">Description</a></li>');
			}
			
			if($this->that->entry['quote'] && $this->that->counts['quote'] !== 0) {
				print('<li><a href="#quote">Quotes</a></li>');
			}
			
			if($this->that->entry['textbody'] && $this->that->counts['textbody'] !== 0) {
				print('<li><a href="#textbody">Biography</a></li>');
			}
			
			if($this->that->entry['associated'] && $this->that->counts['associated'] !== 0) {
				print('<li><a href="#associated">Works</a></li>');
			}
			
			if($this->that->entry['image'] && $this->that->counts['image'] !== 0) {
				print('<li><a href="#image">Images</a></li>');
			}
			
			if(($this->that->entry['definition'] && $this->that->counts['definition'] !== 0) || $this->that->authentication_object->user_session['UserAdmin.id']) {
				print('<li><a href="#glossary">Glossary</a></li>');
			}
			
			if($this->that->entry['eventdate'] && $this->that->counts['eventdate'] !== 0) {
				print('<li><a href="#eventdate">Chronology</a></li>');
			}
			
		#	print('<li><a href="#share">Share</a></li>');
			
			if($this->that->entry['link'] && $this->that->counts['link'] !== 0) {
				print('<li><a href="#link">Links</a></li>');
			}
			
			print('<li><a href="#comments">Comments</a></li>');
			
			if($this->that->entry['tag'] && $this->that->counts['tag'] !== 0) {
				print('<li><a href="#tag">Tags</a></li>');
			}
			
			if($this->that->counts['younger_sibling'] !== 0 || $this->that->counts['older_sibling'] !== 0) {
				print('<li><a href="#siblings">Navigation</a></li>');
			}
			
			print('</ul>');
			
			print('</div>');
			
			return TRUE;
		}
	}

?>
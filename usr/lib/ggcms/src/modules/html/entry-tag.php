<?php

	class module_entrytag extends module_spacing {
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
		
		public function Display($args) {
			if($this->that->entry['tag'] && $this->that->counts['tag']) {
						// Description Header
					
					// -------------------------------------------------------------
					
				print('<a name="tag"></a>');
				
				print('<center>');
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<h2 class="horizontal-left margin-5px font-family-arial">');
				print('Tags');
				print('</h2>');
				print('</div>');
				print('</div>');
				print('</center>');
				
				$this->BackToTopLinkBox();
				
						// Finish Description Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Show Tags
					
					// -------------------------------------------------------------
				
				print('<center>');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
				print('<div class="border-2px background-color-gray15 margin-5px horizontal-left">');
				
				$tags = $this->that->entry['tag'];
				
				$max_limit = $this->that->counts['tag'];
				
				shuffle($tags);
				
				for($i = 0; $i < $max_limit; $i++) {
					$tag = $tags[$i];
					print('<div class="border-2px background-color-gray13 margin-left-5px margin-top-5px margin-bottom-5px float-left">');
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
				
				print('</div>');
				print('</div>');
				print('</center>');
			}
			
			return TRUE;
		}
	}

?>
<?php

	class module_entrylink extends module_spacing {
		public $that;
		
		public function __construct($args) {
			$this->that = $args['that'];
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
			if($this->that->entry['link'] && $this->that->counts['link'] !== 0) {
						// Links Header
					
					// -------------------------------------------------------------
					
				print('<a name="link"></a>');
				
				print('<center>');
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<h2 class="horizontal-left margin-5px font-family-arial">');
				print('Links');
				print('</h2>');
				print('</div>');
				print('</div>');
				print('</center>');
				
				$this->BackToTopLinkBox();
				
						// Finish Textbody Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
			
						// Display Links
					
					// -------------------------------------------------------------
				
				print('<center>');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
				
				for($i = 0; $i < $this->that->counts['link']; $i++) {
					$link = $this->that->entry['link'][$i];
					
					print('<div class="border-2px background-color-gray15 margin-5px horizontal-left font-family-arial">');
					print('<div class="margin-5px horizontal-left font-family-arial">');
					
					print('<a href="' . $link['URL'] . '">');
					if($link['Title']) {
						print(' &bull; ' . $link['Title']);
					} else {
						print($link['URL']);
					}
					print('</a>');
								
					print('</div>');
					print('</div>');
				}
				
				print('</div>');
				print('</center>');
			}
			
			return TRUE;
		}
		
	}

?>
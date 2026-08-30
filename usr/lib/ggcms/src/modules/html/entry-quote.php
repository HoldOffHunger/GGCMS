<?php

	class module_entryquotes extends module_spacing {
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
		
		public function DisplayLinkBox() {
			if($this->that->counts['quote'] === 0) {
				return FALSE;
			}
			
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#quote">');
			print('Quotes (');
			print(number_format($this->that->counts['quote']));
			print(')');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display($args) {
			if(array_key_exists('max', $args)) {
				$max = $args['max'];
				
				if($max > $this->that->counts['quote']) {
					$max = $this->that->counts['quote'];
				}
			} else {
				$max = $this->that->counts['quote'];
			}
			
			if($this->that->entry['quote'] && $this->that->counts['quote'] !== 0) {
						// Quote Header
					
					// -------------------------------------------------------------
					
				print('<a name="quote"></a>');
				
				if($max !== 1 && $max !== 0 && $args['header']) {
					print('<center>');
					print('<div class="horizontal-center width-95percent">');
					print('<div class="border-2px background-color-gray15 margin-5px float-left">');
					print('<h2 class="horizontal-left margin-5px font-family-arial">');
					print($args['header']);
					print('</h2>');
					print('</div>');
					print('</div>');
					print('</center>');
					
					$this->BackToTopLinkBox();
					
							// Finish Quote Header
						
						// -------------------------------------------------------------
											
					print('<div class="clear-float"></div>');
				}
				
						// Display Quote
					
					// -------------------------------------------------------------
				
				print('<center>');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
				
				for($i = 0; $i < $max; $i++) {
					$quote = $this->that->entry['quote'][$i];
					print('<div class="border-2px background-color-gray15 margin-5px horizontal-left">');
					print('<blockquote class="margin-top-0px"><p class="horizontal-left margin-5px font-family-arial"><em>"<quote>');
					print(str_replace('"', '\'', $quote['Quote']));
					print('</quote>"</em></p></blockquote>');
					
					if($quote['Source']) {
						print('<div class="float-right border-2px margin-5px background-color-gray13">');
						print('<p class="horizontal-left margin-5px font-family-arial">');
						print('From : ');
						print($this->that->HyperlinkizeText(['text'=>$quote['Source']]));
						print('</p>');
						print('</div>');
						
								// Finish Floated Box
							
							// -------------------------------------------------------------
												
						print('<div class="clear-float"></div>');
					}
					
					print('</div>');
				}
				
				print('</div>');
				print('</center>');
			}
			
			return TRUE;
		}
		
	}

?>
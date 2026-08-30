<?php

	class module_entrytextbody extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->subheader = $args['subheader'];
			$this->noalts = $args['noalts'];
			
			if(!$this->noalts) {
				require_once(GGCMS_DIR . 'modules/html/alternateformats.php');
				$alts = new module_alternateformats(['that'=>$this->that]);
				$this->alts = $alts;
			}
			
			return $this;
		}
		
		public function Display() {
			if($this->that->entry['textbody'] && $this->that->counts['textbody']) {
				return $this->DisplayContent();
			}
			
			return FALSE;
		}
		
		public function DisplayCustomContent($args) {
			$this->DisplayHeader();
			print('<center>');
			$this->Display_TextBlock($args);
			print('</center>');
			
			return TRUE;
		}
		
		public function DisplayContent() {
			$this->DisplayHeader();
			$this->DisplayTextBody();
			
			return TRUE;
		}
		
		public function DisplayTextBody() {
			print('<center>');
			
			for($i = 0; $i < $this->that->counts['textbody']; $i++) {
				$textbody = $this->that->entry['textbody'][$i];
				$this->Display_TextBlock(['textbody'=>$textbody]);
			}
			
			print('</center>');
			
			return TRUE;
		}
		
		public function DisplayHeader() {
			print('<a name="textbody"></a>');
			
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
			print('<div class="clear-float"></div>');
			
			return TRUE;
		}
		
		public function Display_TextBlock($args) {
			$textbody = $args['textbody'];
			
			print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent"');
			
			if(strlen($textbody['Text']) > $this->that->maxTextLength()) {
				print('  style="border:1px solid gray;width:90%;height:900px;overflow-y:auto;"');
			}
			
			print('>');
			print('<div class="border-2px background-color-gray15 margin-5px horizontal-left font-family-arial">');
			
			if(!$this->noalts) {
				print('<div style="float:right;margin:auto;margin-right:30px;">');
				$this->alts->Display();
				print('</div>');
			}
			
			if($this->subheader) {
				print('<div class="float-left border-2px margin-5px background-color-gray13 margin-left-20px">');
				print('<p class="horizontal-left margin-5px font-family-arial">');
				print($this->subheader);
				print('</p>');
				print('</div>');
			}
				
						// Finish Floated Box
					
					// -------------------------------------------------------------
			
			print('<div class="margin-5px font-family-arial text-to-play-as-audio" style="float:left;">');
			
			$text = $textbody['Text'];
			$text_size = strlen($text);
			
			$text_font_size = 150;
			
			if($text_size < 200) {
				$text_font_size = 200;
			} 
			
			if($args['quote_mode']) {
				$pos = strpos($text, '<p>');
				if ($pos !== false) {
					$text = substr_replace($text, '<p>&ldquo;', $pos, strlen('<p>'));
				}
				
				$text = str_replace('<p', '<p style="margin:10px;" ', $text);
				
				
				function str_lreplace($search, $replace, $subject)
				{
				    $pos = strrpos($subject, $search);
				
				    if($pos !== false)
				    {
				        $subject = substr_replace($subject, $replace, $pos, strlen($search));
				    }
				
				    return $subject;
				}
				
				$text = str_lreplace('</p>', '&rdquo;</p>', $text);
				
				print('<blockquote style="margin-top:0px;padding-top:0px;margin-bottom:0px;padding-bottom:0px;font-size:' . $text_font_size . '%;font-family:tahoma;border:3px solid black;background-color:#FFFFFF;">');
				#print('&ldquo;');
			}
			
			print($this->that->HyperlinkizeText(['text'=>$text]));
			
			if($args['quote_mode']) {
				#print('&rdquo;');
				
				
				print('</blockquote>');
			}
			
			print('</div>');
				
						// Finish Floated Box
					
					// -------------------------------------------------------------
										
			print('<div class="clear-float"></div>');
			
			$this->Display_Source($args);
			
			print('</div>');
			print('</div>');
			
			if(strlen($textbody['Text']) > $this->that->maxTextLength()) {
			#	print('</div>');
			#	print('</center>');
			}
			
			return TRUE;
		}
		
		public function Display_Source($args) {
			$textbody = $args['textbody'];
			
			if($textbody && $textbody['Source']) {
				$source = $textbody['Source'];
			} elseif($args['source']) {
				$source = $args['source'];
			}
			
			if($args['source_text']) {
				$source_text = $args['source_text'];
			} else {
				$source_text = 'From : ';
			}
			
			if($source) {
				print('<div class="float-right border-2px margin-5px background-color-gray13">');
				print('<p class="horizontal-left margin-5px font-family-arial">');
				print($source_text);
				print($this->that->HyperlinkizeText(['text'=>$source]));
				print('</p>');
				print('</div>');
				
						// Finish Floated Box
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
			}
			
			return TRUE;
		}
	}

?>
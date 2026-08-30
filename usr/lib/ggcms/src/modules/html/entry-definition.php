<?php

	class module_entrydefinition extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return $this;
		}
		
		public function Display_Header() {
			print('<a name="glossary"></a>');
			
			print('<center>');
			print('<div class="horizontal-center width-95percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			print('Glossary');
			print('</h2>');
			print('</div>');
			print('</div>');
			print('</center>');
			
			return TRUE;
		}
		
		public function Display() {
			if($this->that->counts['definition'] || $this->that->handler->authentication->user_session['UserAdmin.id']) {
				$this->Display_Header();
				
						// Finish Glossary Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// View Glossary
					
					// -------------------------------------------------------------
				
				if($definition_count) {
					print('<center>');
					print('<div class="width-90percent horizontal-center">');
					print('<center>');
					print('<div class="border-2px background-color-gray15 margin-5px width-90percent horizontal-center">');
					print('<div class="horizontal-left">');
					
					$definitions = $this->that->definitions;
					$definitions_hash_count = count($definitions);
					
				//	print_r($this->that->definitions);
					
					foreach($definitions as $term => $definition_list) {
							// View Word
						
						// -------------------------------------------------------------
						
						print('<center>');
						print('<div class="width-90percent">');
						
						$header_secondary_args = [
							'title'=>$term,
							'divmouseover'=>$term . ' defined (Metaphone : ' . metaphone($term) . '; Soundex : ' . soundex($term) . ')',
							'level'=>2,
							'divclass'=>'border-2px background-color-gray13 margin-5px float-left',
							'textclass'=>'padding-0px margin-5px horizontal-left font-family-tahoma',
							'imagedivclass'=>'border-2px margin-5px background-color-gray10',
							'imageclass'=>'border-1px',
							'domainobject'=>$this->that->handler->domain,
							'leftimageenable'=>0,
							'rightimageenable'=>0,
						];
						$header->display($header_secondary_args);
						
						print('<div class="clear-float"></div>');
						
						print('</div>');
						print('</center>');
						
						$definition_count = count($definition_list);
						
						print('<ol class="margin-10px font-family-tahoma horizontal-left">');
						for($i = 0; $i < $definition_count; $i++) {
							$full_definition = $definition_list[$i];
								
								print('<li>');
								print($full_definition);
								print('</li>');
							
						#	print_r($definition);
						}
						print('</ol>');
					}
					
					print('</div>');
					print('</div>');
					print('</center>');
					print('</div>');
					print('</center>');
				}
				
				if($this->that->handler->authentication->user_session['UserAdmin.id']) {
							// View ALl
						
						// -------------------------------------------------------------
					
					print('<div class="horizontal-center width-50percent">');
					print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
					print('<h3 class="margin-5px">');
					print('<a href="view.php?action=definitions">');
					print('<span class="font-family-tahoma">');
					print('View All x Definitions');
					print('</span>');
					print('</a>');
					print('</h3>');
					print('</div>');
					print('</div>');
				}
			}
			
			return TRUE;
		}
		
	}

?>
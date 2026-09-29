<?php

	class module_languages extends module_spacing {
		public $language_object;
		public $domain_object;
		
		public function __construct($args) {
			$this->language_object = $args['languageobject'];
			$this->domain_object = $args['domainobject'];
		}
		
		public function display() {
				// nothing to choose between on a site written in one language
			if(count(array_intersect_key($this->language_object->GetListOfNativeLanguageNames(), $this->language_object->GetListOfSupportedLanguageCodes())) < 2) {
				return FALSE;
			}

						// Start Div
						// -------------------------------------------------------

			print('<div class="width-90percent horizontal-center margin-top-14px border-1px">');
			
						// Start Div
						// -------------------------------------------------------
			
			print('<div class="display-inline-block">');
			
						// Display Languages
						// -------------------------------------------------------
			
			$current_language_code = $this->language_object->getLanguageCode();
			
			$language_codes = $this->language_object->GetListOfLanguageCodes_any(['languagecode'=>$current_language_code]);
			$language_flags = $this->language_object->GetListOfLanguageFlags();
			
			$supported_language_codes = $this->language_object->GetListOfSupportedLanguageCodes();
			
			foreach(array_intersect_key($this->language_object->GetListOfNativeLanguageNames(), $supported_language_codes) as $native_language_key => $native_language_name) {
							// Gather Data
							// -------------------------------------------------------
							
				$english_language_name = $language_codes[$native_language_key];
				$language_flag_filename = $language_flags[$native_language_key];
				
							// Start Div
							// -------------------------------------------------------
				
				print('<div class="border-1px float-left margin-10px height-115px">');
				
							// Display Language Option
							// -------------------------------------------------------
				
				print('<p class="font-family-tahoma margin-5px">');
				
				if($current_language_code === $native_language_key) {
					print('<strong>');
				} else {
					print('<a rel="nofollow" href="' . $_SERVER['SCRIPT_URL'] . '?language=' . $native_language_key . '">');
				}
				
				print('<img src="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/flags/' . $language_flag_filename . '" style="margin:0px;" width="64" height="64">');
				
				print('<br>');
				
				print($native_language_name);
				
				if($native_language_name !== $english_language_name) {
					print('<br>' . $english_language_name);
				}
				
				if($current_language_code === $native_language_key) {
					print('</strong>');
				} else {
					print('</a>');
				}
				
				print('</p>');
				#print($native_language_key . "|" . $native_language_name . "|" . $language_flag_filename . "<BR><BR>");
				
							// End Div
							// -------------------------------------------------------
				
				print('</div>');
			}
			
						// Start Div
						// -------------------------------------------------------
			
			print('<div class="clear-float"></div>');
			
						// End Div
						// -------------------------------------------------------
			
			print('</div>');
			
						// End Div
						// -------------------------------------------------------
			
			print('</div>');
		}
		
		public function displaytiny() {
						// Start Div
						// -------------------------------------------------------
			
			print('<div class="width-90percent horizontal-center margin-top-14px margin-bottom-14px border-1px">');
			
						// Start Div
						// -------------------------------------------------------
			
			print('<div class="display-inline-block">');
			
						// Display Languages
						// -------------------------------------------------------
			
			$current_language_code = $this->language_object->getLanguageCode();
			
			$language_codes = $this->language_object->GetListOfLanguageCodes_any(['languagecode'=>$current_language_code]);
			$language_flags = $this->language_object->GetListOfLanguageFlags();
			
			$supported_language_codes = $this->language_object->GetListOfSupportedLanguageCodes();
			
			foreach(array_intersect_key($this->language_object->GetListOfNativeLanguageNames(), $supported_language_codes) as $native_language_key => $native_language_name) {
							// Gather Data
							// -------------------------------------------------------
							
				$english_language_name = $language_codes[$native_language_key];
				$language_flag_filename = $language_flags[$native_language_key];
				
							// Start Div
							// -------------------------------------------------------
				
				print('<div class="float-left margin-5px">');
				
							// Display Language Option
							// -------------------------------------------------------
				
				print('<p class="font-family-tahoma margin-0px">');
				
				if($current_language_code !== $native_language_key) {
					print('<a rel="nofollow" href="' . $_SERVER['SCRIPT_URL'] . '?language=' . $native_language_key . '">');
				}
				
				$flag_mouseover = $native_language_name;
				
				if($native_language_name !== $english_language_name) {
					$flag_mouseover .= ' ~ ' . $english_language_name;
				}
				
				print('<img src="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/flags/' . $language_flag_filename . '" style="margin:0px;" width="32" height="32" title="' . $flag_mouseover . '">');
				
				if($current_language_code != $native_language_key) {
					print('</a>');
				}
				
				print('</p>');
				#print($native_language_key . "|" . $native_language_name . "|" . $language_flag_filename . "<BR><BR>");
				
							// End Div
							// -------------------------------------------------------
				
				print('</div>');
			}
			
						// Start Div
						// -------------------------------------------------------
			
			print('<div class="clear-float"></div>');
			
						// End Div
						// -------------------------------------------------------
			
			print('</div>');
			
						// End Div
						// -------------------------------------------------------
			
			print('</div>');
		}
	}

?>
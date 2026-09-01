<?php

	class basicscript extends baseformat {
		public function GetGoodFunctionName() {
			return $this->good_function_name;
		}
		
			// Requires
		
		public function HTMLTitle() {
			if($this->entry && $this->entry['id']) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_' . $this->entry['Code'] . '_title.php';
				
				if(is_file($template_location) === TRUE) {
					return require($template_location);
				}
			}
			
			if($this->object_parent) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->object_parent . '_title.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			} elseif ($this->parent) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->parent['Code'] . '_title.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			} elseif(count($this->object_list) === 1) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->master_record['Code'] . '_title.php';
				
#				print($template_location);
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			if(count($this->object_list) >= 2) {
				if(count($this->object_list) === 2) {
					$grandparent_code = $this->master_record['Code'];
				} else {
					$grandparent_code = $this->object_list[count($this->object_list) - 3];
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_grandchildof_' . $grandparent_code . '_title.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			if(count($this->object_list) >= 3) {
				if(count($this->object_list) === 3) {
					$grandparent_code = $this->master_record['Code'];
				} else {
					$grandparent_code = $this->object_list[count($this->object_list) - 4];
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_greatgrandchildof_' . $grandparent_code . '_title.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_title.php';
			
			if(is_file($template_location)) {
				return require($template_location);
			}
			
			$template_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->desired_action . '_title.php';
			
			if(is_file($template_location)) {
				return require($template_location);
			}
			
			print($this->html_format_data['title']);
		}
		
		public function GetNonLineBreakGoodFunctionName() {
			return '<nobr>' . $this->GetGoodFunctionName() . ':</nobr>';
		}
		
		public function NonBreakingSpace() {
			return '&nbsp;';
		}
		
		public function Quote() {
			return '&quot;';
		}
		
		public function Bullet() {
			return '&bullet;';
		}
		
		public function PreFormattedStart() {
			return '<PRE>';
		}
		
		public function PreFormattedEnd() {
			return '</PRE>';
		}
		
		public function FormatNewLines($args) {
			$text_to_format = $args['texttoformat'];
			
			$formatted_text = $text_to_format;
			
			$formatted_text = str_replace("\n", "<br>\n", $formatted_text);
			
			return $formatted_text;
		}
		
		public function FormatTabs($args) {
			$text_to_format = $args['texttoformat'];
			
			$formatted_text = $text_to_format;
			
			$non_breaking_space = $this->NonBreakingSpace();
			$formatted_text = str_replace("\t", str_repeat($non_breaking_space, 4), $formatted_text);
			
			return $formatted_text;
		}
		
		public function HyperlinkizeText($args) {
			$text_to_hyperlinkize = $args['text'];
			
			$hyperlinkized_text = $text_to_hyperlinkize;
			
				// Pre-Parse Data Preseting
				// -----------------------------------------------------
			
			$number_of_letters_in_current_url = 0;
			$current_url_start_position = 0;
			$inside_url = FALSE;
			$inside_html = 0;
			
				// Pre-Parse Data Gathering
				// -----------------------------------------------------
			
			$hyperlinkized_text_length = strlen($hyperlinkized_text);
			
				// Parse Data
				// -----------------------------------------------------
			
			for($i_parse_text = 0; $i_parse_text < $hyperlinkized_text_length; $i_parse_text++) {
					// Are We Inside a URL?
					// -----------------------------------------------------
				
				if($inside_url === TRUE) {
						// Cut Up Data To Test
						// -----------------------------------------------------
						
					$current_testing_characters_1 = substr($hyperlinkized_text, $i_parse_text, 1);
					$current_testing_characters_2 = substr($hyperlinkized_text, $i_parse_text, 2);
					
						// Did We Hit the End of the Link?
						// -----------------------------------------------------
					
					if(	($current_testing_characters_1 == " ")				||
						($current_testing_characters_1 == "\n")				||
						($current_testing_characters_1 == "\r")				||
						($current_testing_characters_1 == "\s")				||
						($current_testing_characters_1 == "\t")				||
						($current_testing_characters_1 == "<")				||
						($current_testing_characters_1 == ">")				||
						($current_testing_characters_2 == ". ")				||
						($current_testing_characters_2 == ".)")				||
						($current_testing_characters_2 == ".]")				||
						($current_testing_characters_2 == ".\n")			||
						($current_testing_characters_2 == ".\r")			||
						($current_testing_characters_2 == ".\s")			||
						($current_testing_characters_2 == ".\t")			||
						($current_testing_characters_2 == ".<")				||
						(($i_parse_text + 1) == ($hyperlinkized_text_length))		)
					{
							// Did We Hit the End of the Text?
							// -----------------------------------------------------
						
						if(($i_parse_text + 1) == ($hyperlinkized_text_length)) {
							$extra_increment = 1;
						} else {
							$extra_increment = 0;
						}
						
							// Break Out of URL Parsing
							// -----------------------------------------------------
							
						$inside_url = FALSE;
						
						if($number_of_letters_in_current_url > 8) {
								// Grab Link, Text-Before, Text-After
								// -----------------------------------------------------
							
							$url_to_hyperlinkize = substr($hyperlinkized_text, $current_url_start_position, ($i_parse_text - $current_url_start_position) + $extra_increment);
							$text_before_hyperlinkize = substr($hyperlinkized_text, 0, $current_url_start_position);
							$text_after_hyperlinkize = substr($hyperlinkized_text, $i_parse_text + $extra_increment, $hyperlinkized_text_length - $i_parse_text);
							
								// Hyperlinkize URL
								// -----------------------------------------------------
							
							$url_hyperlinkized = "<a href=\"$url_to_hyperlinkize\" target=\"_blank\">$url_to_hyperlinkize</a>";
							
								// Reconcat Data
								// -----------------------------------------------------
								
							$hyperlinkized_text = $text_before_hyperlinkize . $url_hyperlinkized . $text_after_hyperlinkize;
							$i_parse_text = $current_url_start_position + strlen($url_hyperlinkized);
							
								// Re-Determine Length of Text to Hyperlinkize
								// -----------------------------------------------------
								
							$hyperlinkized_text_length = strlen($hyperlinkized_text);
						}
					} else {
							// Increment URL Size
							// -----------------------------------------------------
						
						$number_of_letters_in_current_url++;
					}
				} else {
					if(!$inside_html) {
						$current_testing_characters_1 = strtolower(substr($hyperlinkized_text, $i_parse_text, 1));
						
						if($current_testing_characters_1 === '<') {
							$inside_html = 1;
						}
					} else {
						$current_testing_characters_1 = strtolower(substr($hyperlinkized_text, $i_parse_text, 1));
						
						if($current_testing_characters_1 === '>') {
							$inside_html = 0;
						}
					}
					
					if(!$inside_html) {
							// Cut Up Data To Test
							// -----------------------------------------------------
							
						$current_testing_characters_6 = substr($hyperlinkized_text, $i_parse_text, 6);
						$current_testing_characters_7 = strtolower(substr($hyperlinkized_text, $i_parse_text, 7));
						$current_testing_characters_8 = strtolower(substr($hyperlinkized_text, $i_parse_text, 8));
						
							// Did We Hit a Link?
							// -----------------------------------------------------
						
						if($current_testing_characters_8 === "https://") {
							$current_url_start_position = $i_parse_text;
							$inside_url = TRUE;
							$i_parse_text += 7;
							$number_of_letters_in_current_url = 8;
						} elseif($current_testing_characters_7 === "http://") {
							$current_url_start_position = $i_parse_text;
							$inside_url = TRUE;
							$i_parse_text += 6;
							$number_of_letters_in_current_url = 7;
						} elseif($current_testing_characters_6 === "ftp://") {
							$current_url_start_position = $i_parse_text;
							$inside_url = TRUE;
							$i_parse_text += 5;
							$number_of_letters_in_current_url = 6;
						}
					}
				}
			}
			
			return $hyperlinkized_text;
		}
		
			// Head Data
		
		public function GetHTMLFormatData() {
			if($this->html_format_data) {
				return $this->html_format_data;
			}
			
			$this->html_format_data = [
					// Title Tag
				'title'=>$this->GetHTMLFormatData_Title(),
				
					// Basic HTTP Equiv Tags
				'contenttype'=>$this->GetHTMLFormatData_ContentType(),
				'contentlanguage'=>$this->GetHTMLFormatData_ContentLanguage(),
				'contentscripttype'=>$this->GetHTMLFormatData_ContentScriptType(),
				'contentstyletype'=>$this->GetHTMLFormatData_ContentStyleType(),
				
					// Article Information Tags
				'description'=>$this->GetHTMLFormatData_Description(),
				'abstract'=>$this->GetHTMLFormatData_Abstract(),
				'keywords'=>$this->GetHTMLFormatData_Keywords(),
				'newskeywords'=>$this->GetHTMLFormatData_NewsKeywords(),
				'classification'=>$this->GetHTMLFormatData_Classification(),
				
					// Author Information Tags
				'author'=>$this->GetHTMLFormatData_Author(),
				'contact'=>$this->GetHTMLFormatData_Contact(),
				'replyto'=>$this->GetHTMLFormatData_ReplyTo(),
				'webauthor'=>$this->GetHTMLFormatData_WebAuthor(),
				'copyright'=>$this->GetHTMLFormatData_Copyright(),
				
					// Language Tags
				'language'=>$this->GetHTMLFormatData_Language(),
				
					// Search Engine Data Tags
				'robots'=>$this->GetHTMLFormatData_Robots(),
				'cachecontrol'=>$this->GetHTMLFormatData_CacheControl(),
				'pragma'=>$this->GetHTMLFormatData_Pragma(),
				'distribution'=>$this->GetHTMLFormatData_Distribution(),
				'revisitafter'=>$this->GetHTMLFormatData_RevisitAfter(),
				'expires'=>$this->GetHTMLFormatData_Expires(),
				'noemailcollection'=>$this->GetHTMLFormatData_NoEmailCollection(),
				'googlebot'=>$this->GetHTMLFormatData_GoogleBot(),
				'slurp'=>$this->GetHTMLFormatData_Slurp(),
				
					// Classification Tags
				'doctype'=>$this->GetHTMLFormatData_DocType(),
				'docclass'=>$this->GetHTMLFormatData_DocClass(),
				'docrights'=>$this->GetHTMLFormatData_DocRights(),
				'resourcetype'=>$this->GetHTMLFormatData_ResourceType(),
				'rating'=>$this->GetHTMLFormatData_Rating(),
				
					// Server and Technical Credits Tags
				'designer'=>$this->GetHTMLFormatData_Designer(),
				'generator'=>$this->GetHTMLFormatData_Generator(),
				'publisher'=>$this->GetHTMLFormatData_Publisher(),
				'progid'=>$this->GetHTMLFormatData_ProgID(),
				
					// Server and Technical Details Tags
				'template'=>$this->GetHTMLFormatData_Template(),
				'viewport'=>$this->GetHTMLFormatData_ViewPort(),
				
					// Dublin Core Tags
				'dctitle'=>$this->GetHTMLFormatData_DCTitle(),
				'dccreator'=>$this->GetHTMLFormatData_DCCreator(),
				'dcsubject'=>$this->GetHTMLFormatData_DCSubject(),
				'dcdescription'=>$this->GetHTMLFormatData_DCDescription(),
				'dcpublisher'=>$this->GetHTMLFormatData_DCPublisher(),
				'dccontributor'=>$this->GetHTMLFormatData_DCContributor(),
				'dcdate'=>$this->GetHTMLFormatData_DCDate(),
				'dctype'=>$this->GetHTMLFormatData_DCType(),
				'dcformat'=>$this->GetHTMLFormatData_DCFormat(),
				'dcidentifier'=>$this->GetHTMLFormatData_DCIdentifier(),
				'dcsource'=>$this->GetHTMLFormatData_DCSource(),
				'dclanguage'=>$this->GetHTMLFormatData_DCLanguage(),
				'dcrelation'=>$this->GetHTMLFormatData_DCRelation(),
				'dccoverage'=>$this->GetHTMLFormatData_DCCoverage(),
				'dcrights'=>$this->GetHTMLFormatData_DCRights(),
				
					// WebApp Information Tags
				'applicationname'=>$this->GetHTMLFormatData_ApplicationName(),
				
					// CSS Style Sheets Tags
				'cssstylesheets'=>$this->GetHTMLFormatData_CSSStyleSheets(),
				
					// Javascript Tags
				'javascript'=>$this->GetHTMLFormatData_Javascript(),
			];
			
			return $this->html_format_data;
		}
		
		public function GetHTMLFormatData_Title() {
			if($this->entry && $this->entry['id']) {
				$header_text = '';
				
				if($this->entry['Title']) {
					$header_text .= $this->entry['Title'];
				}
				
				if($this->entry['Subtitle']) {
					if(strlen($header_text) > 0) {
						$header_text .= ' : ';
					}
					
					$header_text .= $this->entry['Subtitle'];
				}
				
				return $this->header_title_text = $header_text;
			}
			if(($this->master_record) && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$header_title_text = $this->master_record['Title'];
					
					if($this->master_record['Subtitle']) {
						if(strlen($header_title_text) > 0) {
							$header_title_text .= ' : ';
						}
						
						$header_title_text .= $this->master_record['Subtitle'];
					}
				} else {
					$main_header_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainHeader']);
					
					if($main_header_language_translations[$this->handler->language->getLanguageCode()]) {
						$header_title_text = $main_header_language_translations[$this->handler->language->getLanguageCode()];
					} else {
						$header_title_text = $this->master_record['Title'];
						
						if($this->master_record['Subtitle']) {
							if(strlen($header_title_text) > 0) {
								$header_title_text .= ' : ';
							}
							
							$header_title_text .= $this->master_record['Subtitle'];
						}
					}
				}
				return $this->header_title_text = $header_title_text;
			}
			return FALSE;
		}
		
		public function GetHTMLFormatData_ContentType() {
			return 'text/css/javascript/php; charset=UTF-8';
		}
		
		public function GetHTMLFormatData_ContentLanguage() {
			return strtoupper($this->handler->language->getLanguageCode());
		}
		
		public function GetHTMLFormatData_ContentScriptType() {
			return 'text/php';
		}
		
		public function GetHTMLFormatData_ContentStyleType() {
			return 'text/css';
		}
		
			// Description
		
		public function getDescription() {
			return $this->GetHTMLFormatData_Description();
		}
		
		public function GetHTMLFormatData_Description() {		// TODO: rename to getDescription()
			if($this->entry && $this->entry['id']) {
				if($this->entry['description']) {
					$descriptions = $this->entry['description'];
					$description_count = count($descriptions);
					if($description_count) {
						$description = $descriptions[0];
						return $description['Description'];
					}
				}
			}
			
			if($this->master_record && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$description_text = $this->master_record['description'][0]['Description'];
				} else {
					$main_description_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainInstructionsContent']);
					
					if($main_description_language_translations[$this->handler->language->getLanguageCode()]) {
						$description_text = $main_description_language_translations[$this->handler->language->getLanguageCode()];
					} else {
						$description_text = $this->master_record['description'][0]['Description'];
					}
				}
				return $this->description_text = $description_text;
			}
			return FALSE;
		}
		
		public function GetHTMLFormatData_Abstract() {
			if($this->entry && $this->entry['id']) {
				if($this->entry['description']) {
					$descriptions = $this->entry['description'];
					$description_count = count($descriptions);
					if($description_count) {
						$description = $descriptions[0];
						return $description['Description'];
					}
				}
			}
			
			if($this->master_record && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$abstract_text = $this->master_record['description'][0]['Description'] . ' ' . '\'' . $this->master_record['quote'][0]['Quote'] . '\'';
				} else {
					$abstract_text = $this->description_text;
					
					$main_quote_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainImageQuote']);
					
					if($main_quote_language_translations[$this->handler->language->getLanguageCode()]) {
						$abstract_text .= ' ' . '\'' . $main_quote_language_translations[$this->handler->language->getLanguageCode()] . '\'';
					} else {
						$abstract_text = $this->master_record['description'][0]['Description'] . ' ' . '\'' . $this->master_record['quote'][0]['Quote'] . '\'';
					}
				}
				return $this->abstract_text = $abstract_text;
			}
			
			return FALSE;
		}
		
		public function GetHTMLFormatData_Keywords() {
			if($this->entry && $this->entry['id']) {
				if($this->entry['tag']) {
					$tags = $this->entry['tag'];
					$tag_count = count($tags);
					if($tag_count) {
						$display_tags = [];
						
						for($i = 0; $i < $tag_count; $i++) {
							$tag = $tags[$i];
							
							$display_tags[] = $tag['Tag'];
						}
						
						return implode(', ', $display_tags);
					}
				}
			}
			
			if($this->master_record && $this->master_record['id']) {
				if($this->handler->language->getLanguageCode() === 'en') {
					$keywords = [];
					
					$tags = $this->master_record['tag'];
					
					if($tags && count($tags)) {
						foreach($tags as $tag) {
							$keywords[] = $tag['Tag'];
						}
						
						$keywords_text = implode(', ', $keywords);
					}
				} else {
					$main_description_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainKeywords']);
					
					if($main_description_language_translations[$this->handler->language->getLanguageCode()]) {
						$keywords_text = $main_description_language_translations[$this->handler->language->getLanguageCode()];
					} else {
						$keywords = [];
						
						$tags = $this->master_record['tag'];
						
						if($tags && count($tags)) {
							foreach($tags as $tag) {
								$keywords[] = $tag['Tag'];
							}
							
							$keywords_text = implode(', ', $keywords);
						}
					}
				}
				return $keywords_text;
			}
			
			return FALSE;
		}
		
		public function GetHTMLFormatData_NewsKeywords() {
			if($this->handler->language->getLanguageCode() !== 'en') {
				$main_news_keyword_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainNewsKeywords']);

				if($main_news_keyword_language_translations[$this->handler->language->getLanguageCode()]) {
					return $main_news_keyword_language_translations[$this->handler->language->getLanguageCode()];
				}
			}

			return $this->SiteKeywords();
		}
		
		public function GetHTMLFormatData_Classification() {
			if(!$this->parent['id']) {
				$classification_text = $this->SiteKeywords();

				if($this->handler->language->getLanguageCode() !== 'en') {
					$main_classification_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainClassification']);

					if($main_classification_language_translations[$this->handler->language->getLanguageCode()]) {
						$classification_text = $main_classification_language_translations[$this->handler->language->getLanguageCode()];
					}
				}

				return $this->classification = $classification_text;
			} elseif($this->parent && $this->parent['id']) {
				$classification = $this->parent['Title'];
				
				if($this->parent['Subtitle']) {
					if($classification) {
						$classification .= ' : ';
					}
					
					$classification .= $this->parent['Subtitle'];
				}
				return $this->classification = $classification;
			}
			
			return FALSE;
		}
		
			// Creator
		
		public function GetHTMLFormatData_Author() {
			if ($this->entry && $this->entry['id']) {
				$textbodies = $this->entry['textbody'];
				if($textbodies) {
					$textbody_count = count($textbodies);
					
					if($textbody_count) {
						$textbody = $textbodies[0];
						if($textbody['Source']) {
							return 'From : ' . str_replace('"', '&quot;', $textbody['Source']);
						}
					}
				}
			}
			
			return $this->handler->abstractglobals->site->Creator();
		}
		
		public function GetHTMLFormatData_Contact() {
			return $this->handler->abstractglobals->site->Contact();
		}

		public function GetHTMLFormatData_ReplyTo() {
			return $this->handler->abstractglobals->site->Contact();
		}
		
		public function GetHTMLFormatData_WebAuthor() {
			return $this->GetHTMLFormatData_Author();
		}
		
		public function GetHTMLFormatData_Copyright() {
			return $this->handler->abstractglobals->site->Copyright();
		}
		
			// Language
		
		public function GetHTMLFormatData_Language() {
			return $this->handler->language->GetListOfNativeLanguageNames()[$this->handler->language->getLanguageCode()];
		}
		
			// Search Engine Data
		
		public function GetHTMLFormatData_Robots() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'index,follow';
			} else {
				return 'noindex,nofollow';
			}
		}
		
		public function GetHTMLFormatData_CacheControl() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'public';
			} else {
				return 'no-cache';
			}
		}
		
		public function GetHTMLFormatData_Pragma() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'option';
			} else {
				return 'no-cache';
			}
		}
		
		public function GetHTMLFormatData_Distribution() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'global';
			} else {
				return 'iu';	# "Internal Use"
			}
		}
		
		public function GetHTMLFormatData_RevisitAfter() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return '7 days';
			} else {
				return 'never';
			}
		}
		
		public function GetHTMLFormatData_Expires() {
			if($this->IsSecure() && $this->IsAccessible()) {
				return 'thu, 01 Jan 1970 00:00:00 GMT';
			} else {
				return '';
			}
		}
		
		public function GetHTMLFormatData_NoEmailCollection() {
			$primary_domain_args = [
				'secure'=>0,
				'www'=>1,
				'lowercased'=>1,
			];
			return $this->handler->domain->GetPrimaryDomain($primary_domain_args) . '/no-email-collection.php';
		}
		
		public function GetHTMLFormatData_GoogleBot() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'index,follow';
			} else {
				return 'noindex,nofollow,nosnippet,noodp,noarchive,noimageindex,unavailable_after: 01-Jan-1970 00:00:00 EST';
			}
		}
		
		public function GetHTMLFormatData_Slurp() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'index,follow';
			} else {
				return 'noindex,nofollow,noydir,noodp';
			}
		}
		
			// Classifications
		
		public function GetHTMLFormatData_DocType() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'eZine';
			} else {
				return 'Admin-Only';
			}
		}
		
		public function GetHTMLFormatData_DocClass() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'Published';
			} else {
				return 'Private';
			}
		}
		
		public function GetHTMLFormatData_DocRights() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'Public';
			} else {
				return 'Private';
			}
		}
		
		public function GetHTMLFormatData_ResourceType() {
			return 'document';
		}
		
		public function GetHTMLFormatData_Rating() {
			if(!$this->IsSecure() && $this->IsAccessible()) {
				return 'general';
			} else {
				return 'admin';
			}
		}
		
			// Server Information and Credits
		
		public function GetHTMLFormatData_Designer() {
			return $this->handler->version->GetSoftwareDesigner();
		}
		
		public function GetHTMLFormatData_Generator() {
			return $this->handler->version->GetSoftwareNameAcronymAndVersion();
		}
		
		public function GetHTMLFormatData_Publisher() {
			return $this->handler->version->GetSoftwareNameAndAcronym();
		}
		
		public function GetHTMLFormatData_ProgID() {
			return $this->handler->version->GetSoftwareAcronymAndVersion();
		}
		
			// Template
		
		public function GetHTMLFormatData_Template() {
				/*
					A BaseTemplate override lived in PrimaryHostRecord and was
					never populated, so this has always returned the default.
					Template selection is by directory now -- see
					templates/<site>/ -- and no site needs a second mechanism.
				*/

			return 'html-templates/base-template.html';
		}
		
		public function GetHTMLFormatData_ViewPort() {
			return 'width=device-width, initial-scale=1';
		}
		
			// Dublin Core Classifications
		
		public function GetHTMLFormatData_DCTitle() {
			if(!$this->parent['id'] && $this->master_record && $this->master_record['id']) {
				return $this->header_title_text;
			} elseif ($this->entry && $this->entry['id']) {
				return $this->GetHTMLFormatData_Title();
			}
			
			return FALSE;
		}
		
		public function GetHTMLFormatData_DCCreator() {
			if($this->handler->abstractglobals->site->Creator()) {
				return $this->handler->abstractglobals->site->Creator();
			}
			
			return FALSE;
		}
		
		public function GetHTMLFormatData_DCSubject() {
			return $this->classification;
		}
		
		public function GetHTMLFormatData_DCDescription() {
			return $this->GetHTMLFormatData_Abstract();
		}
		
		public function GetHTMLFormatData_DCPublisher() {
			if($this->handler->abstractglobals->site->Publisher()) {
				return $this->handler->abstractglobals->site->Publisher();
			}
		
			return FALSE;
		}
		
		public function GetHTMLFormatData_DCContributor() {
			if($this->handler->abstractglobals->site->Contributor()) {
				return $this->handler->abstractglobals->site->Contributor();
			}
			
			return FALSE;
		}
		
		public function GetHTMLFormatData_DCDate() {
			if($this->entry && $this->entry['id']) {
				return $this->entry['OriginalCreationDate'];
			}
			
				/*
					The site's own release date is the primary top-level entry's
					creation date.  The test that stood here read
					$primary_host_record before the line below assigned it, so it
					was false on its own account as well as on the property's.
				*/

			if($this->master_record && $this->master_record['id'] && $this->master_record['OriginalCreationDate']) {
				return $this->master_record['OriginalCreationDate'];
			}

			return '1970-01-01';
		}
		
		public function GetHTMLFormatData_DCType() {
			return 'Text';
		}
		
		public function GetHTMLFormatData_DCFormat() {
			return 'TXT / HTML / XHTML / CSS / PHP';
		}
		
		public function GetHTMLFormatData_DCIdentifier() {
			if(!$this->parent['id'] && $this->master_record && $this->master_record['id']) {
				return $this->master_record['Code'];
			} elseif($this->entry && $this->entry['id']) {
				return $this->entry['id'];
			}
			
			return 'Anarchist Identifier';
		}
		
		public function GetHTMLFormatData_DCSource() {
			$primary_domain_args = [
				'secure'=>0,
				'www'=>1,
				'lowercased'=>1,
			];
			return $this->handler->domain->GetPrimaryDomain($primary_domain_args);
		}
		
		public function GetHTMLFormatData_DCLanguage() {
			return $this->handler->language->getLanguageCode();
		}
		
		public function GetHTMLFormatData_DCRelation() {
			$primary_domain_args = [
				'secure'=>0,
				'www'=>1,
				'lowercased'=>1,
			];
			return $this->handler->domain->GetPrimaryDomain($primary_domain_args);
		}
		
		public function GetHTMLFormatData_DCCoverage() {
			if(!$this->IsSecure()) {
				return 'eternity';
			} else {
				return 'nocoverage';
			}
		}
		
		public function GetHTMLFormatData_DCRights() {
			if(!$this->IsSecure()) {
				if($this->handler->abstractglobals->site->Rights()) {
					return $this->handler->abstractglobals->site->Rights();
				}
				
				return 'CopyLeft';
			} else {
				return 'Admin-Only';
			}
		}
		
			// Web Application
		
		public function GetHTMLFormatData_ApplicationName() {
				//  The site's name is the primary top-level entry's Title.

			if($this->master_record && $this->master_record['id']) {
				return $this->master_record['Title'];
			}

			return FALSE;
		}
		
			// CSS Style Sheets
		
		public function GetHTMLFormatData_CSSStyleSheets() {
			return [
				'css/style/sheet/location.css',
			];
		}
		
			// JavaScript
		
		public function GetHTMLFormatData_Javascript() {
			return [
				'javascript/location.js',
			];
		}
		
			// Header Location Extras
			// -----------------------------------------------
		
		public function HTMLHeadDisplayExtra_Start() {
		}
		
		public function HTMLHeadDisplayExtra_Title() {
		}
		
		public function HTMLHeadDisplayExtra_HTTPEquivalents() {
		}
		
		public function HTMLHeadDisplayExtra_ArticleInformation() {
		}
		
		public function HTMLHeadDisplayExtra_AuthorInformation() {
		}
		
		public function HTMLHeadDisplayExtra_LanguageInformation() {
		}
		
		public function HTMLHeadDisplayExtra_SearchEngineData() {
		}
		
		public function HTMLHeadDisplayExtra_Classifications() {
		}
		
		public function HTMLHeadDisplayExtra_ServerAndTechnicalCredits() {
		}
		
		public function HTMLHeadDisplayExtra_ServerAndTechnicalDetails() {
		}
		
		public function HTMLHeadDisplayExtra_DublinCore() {
		}
		
		public function HTMLHeadDisplayExtra_AlternateVersions() {
		}
		
		public function HTMLHeadDisplayExtra_CSS() {
		}
		
		public function HTMLHeadDisplayExtra_Javascript() {
		}
		
			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplayDoubleReturns() {
			ggreq('html/basics/spacing/return.html');
			ggreq('html/basics/spacing/return.html');
		}
		
		public function arrowRight($args) {
			print(' ');
			print($this->arrowRightSVG());
			print(' ');
			
			return TRUE;
		}
		
		public function arrowRightSVG() {
			$svg = '';
			
			$svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" style="margin-right:-2px;margin-left:-2px;">';
			$svg .= '<path d="M8.122 24l-4.122-4 8-8-8-8 4.122-4 11.878 12z"/>';
			$svg .= '</svg>';
			
			return $svg;
		}
	}

?>
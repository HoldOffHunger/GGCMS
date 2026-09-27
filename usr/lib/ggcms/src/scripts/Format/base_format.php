<?php

		/*
			Every script extends this, and a script is a bag of records named
			at run time: SimpleORM keeps each record type under its own name
			($this->$record_type), and the per-site templates, included inside
			a script's methods, set whatever the page needs.  Those names cannot
			be declared ahead, so scripts opt in to dynamic properties -- the
			attribute is inherited, and it is what PHP 9 honours when it makes
			the undeclared kind an Error.  Everything that can be declared, is.
		*/

	#[AllowDynamicProperties]
	class baseformat {
		public $subject;
		public $record_to_use;
		public $handler;
		public $format;
		public $desired_script;
		public $desired_action;
		public $object_code;
		public $object_parent;
		public $object_list;
		public $script_location;
		public $script_name;
		public $script_file;
		public $script_extension;
		public $script_format;
		public $script_format_lower;
		public $script_args;
		public $google_api;
		public $authentication_object;
		public $cleanser_object;
		public $query_object;
		public $db_access_object;
		public $domain_object;
		public $globals;
		public $language_object;
		public $dictionary;
		public $time;
		public $cookie;
		public $formats_object;
		public $version_object;
		public $redirect_object;
		public $errors;
		public $admin_errors;
		public $navigation;
		public $mobile_friendly;
		public $header_title_text;
		
			// Constructor
			// ------------------------------------------------
			
		public function __construct($args) {
			$this->startUp($args);
		}
		
			// Meta-Data
			// ------------------------------------------------
		
		public function SetDocumentAttributes() {
			$this->subject = $this->SetMetadata_Subject();
			return $this->record_to_use = $this->SetRecordToUseForMetadata();
		}
		
		public function SetMetadata_Subject() {
			$subject = '';
			$record_to_use = false;
			
			$parent_code = $this->object_parent;
			
			if($this->record_list) {
				$record_count = count($this->record_list);
				
				for($i = 0; $i < $record_count; $i++) {
					$record = $this->record_list[$i];
					
					if($record['Code'] === $parent_code) {
						$record_to_use = $record;
						$i = $record_count;
					}
				}
			}
			
			if($record_to_use) {
				if($record_to_use['Title']) {
					$subject .= $record_to_use['Title'];
				}
				
				if($record_to_use['Subtitle']) {
					if($subject) {
						$subject .= ' : ';
					}
					
					$subject .= $record_to_use['Subtitle'];
				}
			} else {
				$subject .= $this->SiteKeywords();
			}

			return $subject;
		}

			/*
				What the site is about, when there is no entry to speak for
				itself: the primary top-level entry's tags.

				These were three columns in PrimaryHostRecord -- Subject,
				Classification and NewsKeywords -- and every reader of them
				concatenated all three back into one comma-separated list, which
				is a tag list written the long way round.  The entry already
				carries tags, GetMasterRecord() already fetches them, and the
				file cache already holds them, so there is nothing to load.

				Tags carry a Language.  An untagged language matches everything,
				which is how a site with one set of tags keeps them in every
				translation.
			*/

		public function SiteKeywords() {
			if(!$this->master_record || !$this->master_record['tag']) {
				return '';
			}

			$language_code = $this->handler->language->GetLanguageCode();

			$tags = [];

			foreach($this->master_record['tag'] as $tag) {
				if($tag['Language'] && $tag['Language'] !== $language_code) {
					continue;
				}

				if(!$tag['Tag']) {
					continue;
				}

				$tags[] = $tag['Tag'];
			}

			return implode(', ', $tags);
		}

			/*
				The site's own icon, which was PrimaryImageLeft: the first image
				child of the primary top-level entry.  PrimaryImageRight had two
				call sites, both commented out, and is not replaced.

				Image is disabled by default in child_types, so a site that has
				not enabled it gets nothing here rather than a warning.
			*/

			/*
				The images the header slideshow runs on.

				An entry deep in the graph has its own images -- a word may have
				one illustrating it -- but the header is not about the word.  It
				is about the language being learned, and those images hang on the
				top-level ancestor: record_list[0], which is the same record the
				word page already reads its title out of.

				So the ancestor's images are preferred, and the entry's are the
				fallback for a page with no ancestor to borrow from.  Fewer than
				two of either and there is nothing to rotate, which the caller
				checks.
			*/

		public function SlideshowImages() {
			if($this->record_list && $this->record_list[0] && $this->record_list[0]['image']) {
				if(count($this->record_list[0]['image']) > 1) {
					return $this->record_list[0]['image'];
				}
			}

			if($this->entry && $this->entry['image']) {
				return $this->entry['image'];
			}

			return [];
		}

		public function SitePrimaryIcon() {
			if(!$this->master_record || !$this->master_record['image']) {
				return '';
			}

			$image = $this->master_record['image'][0];

			return $image['IconFileName'];
		}
		
		public function SetRecordToUseForMetadata() {
			if($this->rpc_results) {
				return $this->record_to_use = $this->rpc_results;
			}
			
			$record_to_use = FALSE;
			
			if($this->entry) {
				$record_to_use = $this->entry;
			} elseif($this->master_record) {
				$record_to_use = $this->master_record;
			}
			
			return $this->record_to_use = $record_to_use;
		}
		
			// Configuration
			// ------------------------------------------------
		
		public function startUp($args) {
			$this->setArguments($args);
			$this->setArguments_OLD($args);		// TODO: DELETE!!!!!!!!!!!!!!!!!!!
			$this->initializeErrors();
			$this->setPageConfiguration();
			
			return TRUE;
		}
		
		public function setArguments($args) {
			$this->handler = $args['handler'];
			$this->format = $args['format'];
			
			return TRUE;
		}
		
		public function setArguments_OLD($args) {
			$this->desired_script = $args['desiredscript'];
			$this->desired_action = $args['desiredaction'];
			
			$this->object_code = $args['objectcode'];
			$this->object_parent = $args['objectparent'];
			$this->object_list = $args['objectlist'];
			
			$this->script_location = $args['scriptlocation'];
			$this->script_name = $args['scriptname'];
			$this->script_file = $args['scriptfile'];
			$this->script_extension = $args['scriptextension'];
			$this->script_format = $args['scriptformat'];
			$this->script_format_lower = $args['scriptformatlower'];
			$this->script_args = $args['scriptargs'];
			$this->google_api = $args['googleapi'];
			
			$this->authentication_object = $args['authenticationobject'];
			$this->cleanser_object = $args['cleanserobject'];
			$this->query_object = $args['queryobject'];
			$this->db_access_object = $args['dbaccessobject'];
			$this->domain_object = $args['domainobject'];
			$this->globals = $args['globals'];
			$this->language_object = $args['languageobject'];
			$this->dictionary = $args['dictionary'];
			$this->time = $args['time'];
			$this->cookie = $args['cookie'];
			$this->formats_object = $args['formatsobject'];
			$this->version_object = $args['versionobject'];
			$this->redirect_object = $args['redirectobject'];
			
			return TRUE;
		}
		
		public function initializeErrors() {
			$this->errors = [];
			$this->admin_errors = [];
			
			return TRUE;
		}
		
		public function setPageConfiguration() {
			$this->navigation = TRUE;
			$this->mobile_friendly = $this->Param('mobilefriendly');
			
			return TRUE;
		}
		
			// Main Function
			// ------------------------------------------------
		
		public function Display() {
			return TRUE;
		}
		
			// Scheme
			// ------------------------------------------------

			/*
				For templates: $this->HTTPProtocol() rather than working out
				http or https in each one.  Domain::HTTPProtocol decides.
			*/

		public function HTTPProtocol() {
			return $this->domain_object->HTTPProtocol();
		}

			// Security Data
			// ------------------------------------------------
		
		public function IsSecure() {
			return FALSE;
		}
		
		public function IsAccessible() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
		public function AdminOnly() {
			return FALSE;
		}
		
		public function isUserAdmin() {
			if($this->handler->authentication->user_session['UserAdmin.id']) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function isUserLoggedIn() {
			if($this->handler->authentication->user_session['User.Username']) {
				return TRUE;
			}
			
			return FALSE;
		}
		
			// Templates
			// ------------------------------------------------
			
		public function DisplayTemplates() {
			if($this->humanreadable) {
				print("\n");
			}
			
			return $this->HandleRequires();
		}
		
		public function HandleRequires() {
			if(count($this->object_list) < 1) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_index.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			} else {
				if($this->entry && $this->entry['id'] && $this->entry['Code'] !== 'index') {
					$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_' . $this->entry['Code'] . '.php';
					
					if(is_file($template_location)) {
						return require($template_location);
					}
				}
			}
			
			if($this->object_parent) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->object_parent . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			if($this->parent) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->parent['Code'] . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			if(count($this->object_list) >= 4) {
				if(count($this->object_list) === 4) {
					$grandparent_code = $this->master_record['Code'];
				} else {
					$grandparent_code = $this->object_list[count($this->object_list) - 5];
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_greatgreatgrandchildof_' . $grandparent_code . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_greatgreatgrandchildof_index.php';
				
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
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_greatgrandchildof_' . $grandparent_code . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_greatgrandchildof_index.php';
				
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
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_grandchildof_' . $grandparent_code . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_grandchildof_index.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			if(count($this->object_list) === 1) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->master_record['Code'] . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
				
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_index.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			if(count($this->object_list) === 0) {
				$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '_childof_' . $this->master_record['Code'] . '.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			$template_location = GGCMS_DIR . 'templates/' . $this->handler->domain->host . '/' . $this->script_file . '/' . $this->desired_action . '.php';
			
			if(is_file($template_location)) {
				return require($template_location);
			}
			
			if(count($this->object_list) < 1) {
				$template_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->desired_action . '_index.php';
				
				if(is_file($template_location)) {
					return require($template_location);
				}
			}
			
			$template_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->handler->script_format_lower . '/' . $this->desired_action . '.php';
			
			if(is_file($template_location)) {
				return require($template_location);
			}
			
			$template_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->desired_action . '.php';
			
			if(is_file($template_location)) {
				return require($template_location);
			}
			
			return FALSE;
		}

		public function getDescription() {
			$description = '';
			
			if($this->entry['description'] && $this->entry['description'][0] && $this->entry['description'][0]['Description']) {
				$description = $this->entry['description'][0]['Description'];
				$description = preg_replace('/Image::(\d+)/', '', $description);
			}
			
			return $description;
		}
		
		public function Param($parameter) {
			$cleansed_input = $this->query_object->Parameter(['parameter'=>$parameter]);
			
			if(is_array($cleansed_input)) {
				$cleansed_input_pieces = [];
				
				foreach ($cleansed_input as $cleansed_input_piece) {
					$cleansed_input_pieces[] = $this->CleanseWhiteSpace($cleansed_input_piece);
				}
				$cleansed_input = $cleansed_input_pieces;
			} else {
				$cleansed_input = $this->CleanseWhiteSpace($cleansed_input);
			}
			
			return $cleansed_input;
		}
		
		public function CleanseWhiteSpace($text) {
			return trim($text);
		}

			// Document Title
			// -----------------------------------------------

		/*
			Every format's basicscript extends this class, but only HTML's
			defined a title method -- so a template calling it under any other
			format hit "Call to undefined method view::GetHTMLFormatData_Title()".

			The three anarchistcode templates and the three default ones that
			call it are gated to run for pdf, tex, rtf, csv, opds and rdf --
			precisely the formats where HTML's version is not loaded.

			This is a deliberately small fallback rather than a copy of HTML's
			implementation, which carries language-translation branches and
			dependencies that do not belong in the shared base.  HTML continues
			to override it; the other sixteen formats now get a title instead of
			a fatal.
		*/

		public function GetHTMLFormatData_Title() {
			$title_text = '';

			$record = ($this->entry && $this->entry['id']) ? $this->entry : $this->master_record;

			if(!$record) {
				return $title_text;
			}

			if($record['Title']) {
				$title_text .= $record['Title'];
			}

			if($record['Subtitle']) {
				if(strlen($title_text) > 0) {
					$title_text .= ' : ';
				}

				$title_text .= $record['Subtitle'];
			}

			return $this->header_title_text = $title_text;
		}

			// Display Components
			// -----------------------------------------------

		/*
			Only HTML, TXT and BRF defined these, so sitemap.json, .rss, .atom,
			.csv and every other format fataled on "Call to undefined method
			sitemap::NonBreakingSpace()" -- on every site.  The error
			listing in SimpleErrors and the search results in view.php make
			the same calls under any format.

			The plain-text characters TXT and BRF already use are the fallback;
			those three formats continue to override them.
		*/

		public function NonBreakingSpace() {
			return ' ';
		}

		public function Bullet() {
			return '*';
		}

	}
	
?>
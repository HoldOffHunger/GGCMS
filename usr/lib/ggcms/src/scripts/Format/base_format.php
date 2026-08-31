<?php

	class baseformat {
			// Constructor
			// ------------------------------------------------
			
		public function __construct($args) {
			$this->startUp($args);
			
			return $this;
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
				$primary_host_record = $this->primary_host_record;
				
				if($primary_host_record) {
					if($primary_host_record['Subject']) {
						$subject .= $primary_host_record['Subject'];
					}
					
					if($primary_host_record['Classification']) {
						if($subject) {
							$subject .= ', ';
						}
						
						$subject .= $primary_host_record['Classification'];
					}
					
					if($primary_host_record['NewsKeywords']) {
						if($subject) {
							$subject .= ', ';
						}
						
						$subject .= $primary_host_record['NewsKeywords'];
					}
				}
			}
			
			return $subject;
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

	}
	
?>
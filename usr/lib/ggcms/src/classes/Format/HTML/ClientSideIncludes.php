<?php

	class ClientSideIncludes {
		public function __construct($args) {
			$this->desired_action = $args['desiredaction'];
			$this->script_file = $args['scriptfile'];
			$this->domain_object = $args['domainobject'];
			$this->secure_script = $_SERVER['HTTPS'];
			$this->language = $args['language'];
			$this->google_api = $args['googleapi'];
			$this->globals = $args['globals'];
			
			return $this;
		}
		
		public function Headers($args) {
			$include_type = $args['includetype'];
			$header_location = $args['headerlocation'];
			$include_location = $args['includelocation'];
			$unavailable_location = $args['unavailablelocation'];
			
			$includes_file_location = GGCMS_DIR . 'templates/' . $this->domain_object->host . '/' . $this->script_file . '/' . $this->desired_action . '_' . $include_type . '.php';
			
			if(is_file($includes_file_location) === FALSE) {
				$includes_file_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->desired_action . '_' . $include_type . '.php';
			}
			
			if(is_file($includes_file_location) === TRUE) {
				$include_files = file($includes_file_location);
				$include_file_locations = [];
				
				if(count($include_files)) {
					print("\n");
					
					print('<!-- ');
					print($include_type);
					print(' -->');
					
					print("\n");
					
					$this->displayDefaultIncludes(['includetype'=>$include_type]);
					
					foreach ($include_files as $include_file) {
						$include_file = trim($include_file);
						$include_file_location = $include_type . '/' . $include_file;
						
						$primary_domain_args = [
							'secure'=>$this->secure_script,
					#		'www'=>1,
							'lowercased'=>1,
						];
						
						$include_file_location_pieces = explode('.', $include_file_location);
						
						$total_count = count($include_file_location_pieces);
						$include_file_location_pieces[$total_count - 2] = $include_file_location_pieces[$total_count - 2] . '_' . $this->language->GetLanguageCode();
						$new_include_file_location = implode('.', $include_file_location_pieces);
						
						if(webroot_isfile($new_include_file_location)) {
							$include_file_location = $this->domain_object->GetPrimaryDomain($primary_domain_args) . '/' . $new_include_file_location;
						} else {
							if(!webroot_isfile($include_file_location)) {
								$include_file_location = $include_file;
							} else {
								$include_file_location = $this->domain_object->GetPrimaryDomain($primary_domain_args) . '/' . $include_file_location;
							}
						}
						
						print("\n\t");
						
						if($include_type === 'css') {
							print('<link type="text/css" rel="stylesheet" href="');
							print($include_file_location);
							print('">');
						} elseif($include_type === 'javascript') {
							print('<script src="');
							print($include_file_location);
							print('"></script>');
						}
					}
					
					if($_SERVER['HTTPS'] === 'on' && $this->google_api->client_id) {
						print("\n\t" . '<script src="https://apis.google.com/js/platform.js" async defer></script>');
						
						print("\n\t" . '<meta name="google-signin-client_id" content="' . $this->google_api->client_id . '">');
					}
				} else {
					$headers_unavailable_args = [
						'type'=>$include_type,
						'unavailablelocation'=>$unavailable_location,
					];
					$this->Headers_Unavailable($headers_unavailable_args);
				}
			} else {
				$headers_unavailable_args = [
					'type'=>$include_type,
					'unavailablelocation'=>$unavailable_location,
				];
				$this->Headers_Unavailable($headers_unavailable_args);
			}
			
			return TRUE;
		}
		
		public function Headers_DisplayRealFiles($args) {
			$include_type = $args['includetype'];
			
			$includes_file_location = GGCMS_DIR . 'templates/' . $this->domain_object->host . '/' . $this->script_file . '/' . $this->desired_action . '_' . $include_type . '.php';
			
			if(is_file($includes_file_location) === FALSE) {
				$includes_file_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->desired_action . '_' . $include_type . '.php';
			}
			
			if(is_file($includes_file_location) === TRUE) {
				$primary_domain_args = [
					'secure'=>$this->secure_script,
#					'www'=>1,
					'lowercased'=>1,
				];
				$include_files = file($includes_file_location);
				$include_url_locations = [];
				
				foreach($include_files as $include_file) {
					$include_file = trim($include_file);
					$include_file_location = $include_type . '/' . $include_file;
					
					if(is_file($include_file_location)) {
						$include_url_locations[] = $this->domain_object->GetPrimaryDomain($primary_domain_args) . '/' . $include_file_location;
					} else {
						$default_include_file_location = GGCMS_DIR . GGCMS_REFERENCE_DOMAIN . '/' . $include_file_location;
						
						if(is_file($default_include_file_location) === TRUE) {
							$include_url_locations[] = $this->domain_object->GetPrimaryDomain($primary_domain_args) . '/' . $include_file_location;
						} else {
							if(filter_var($include_file, FILTER_VALIDATE_URL)) {
								$include_url_locations[] = $include_file;
							}
						}
					}
				}
				
				foreach ($include_url_locations as $include_url_location) {
					$include_file_location = $include_url_location;
					print("\n\t");
					
					if($include_type === 'css') {
						print('<link type="text/css" rel="stylesheet" href="');
						print($include_file_location);
						print('">');
					} elseif($include_type === 'javascript') {
						print('<script src="');
						print($include_file_location);
						print('"></script>');
					}
				}
			}
			
			return TRUE;
		}
		
		public function Headers_Simple($args) {
			$include_type = $args['includetype'];
			$header_location = $args['headerlocation'];
			$include_location = $args['includelocation'];
			$unavailable_location = $args['unavailablelocation'];
			
			$includes_file_location = GGCMS_DIR . 'templates/' . $this->domain_object->host . '/' . $this->script_file . '/' . $this->desired_action . '_' . $include_type . '.php';
			
			if(is_file($includes_file_location) === FALSE) {
				$includes_file_location = GGCMS_DIR . 'templates/default/' . $this->script_file . '/' . $this->desired_action . '_' . $include_type . '.php';
			}
			
			if(is_file($includes_file_location) === TRUE) {
				$include_files = file($includes_file_location);
				if(strlen($include_files[0]) > 0) {
					print("\n");
					
					print('<!-- ');
					print($include_type);
					print(' -->');
					
					print("\n");
					
					$this->displayDefaultIncludes(['includetype'=>$include_type]);
					
					$primary_domain_args = [
						'secure'=>$this->secure_script,
				#		'www'=>1,
						'lowercased'=>1,
					];
					$include_file = $include_type . '/' . $this->script_file . '/' . $this->desired_action . '.' . $include_type;
					
					$include_file_location = $this->domain_object->GetPrimaryDomain($primary_domain_args) . '/' . $include_file;
					
					print("\n\t");
					
					if($include_type === 'css') {
						print('<link type="text/css" rel="stylesheet" href="');
						print($include_file_location);
						print('">');
					} elseif($include_type === 'javascript') {
						print('<script src="');
						print($include_file_location);
						print('"></script>');
					}
				}
			} else {
				$headers_unavailable_args = [
					'type'=>$include_type,
					'unavailablelocation'=>$unavailable_location,
				];
				$this->Headers_Unavailable($headers_unavailable_args);
			}
			
			return TRUE;
		}
		
		public function Headers_Unavailable($args) {
			$type = $args['type'];
			$unavailable_location = $args['unavailablelocation'];
			print("\n");
			
			print("\t\t" . '<!-- ' . $type . ' -->');
			
			$this->DisplayDoubleReturns();
			$this->displayDefaultIncludes(['includetype'=>$type]);
			print("\t\t" . '<!-- Unavailable -->');
			
			return TRUE;
		}
		
		public function DisplayDefaultIncludes($args) {
			$include_type = $args['includetype'];
			$domain = $this->domain_object->GetPrimaryDomain(['secure'=>$this->secure_script, 'www'=>0, 'lowercased'=>TRUE]);
			
			if($include_type === 'css') {
				print("\n\t" . '<link type="text/css" rel="stylesheet" href="' . $domain . '/css/jquery-ui.min.css">');
				print("\n\t" . '<link type="text/css" rel="stylesheet" href="' . $domain . '/css/jquery-ui.structure.min.css">');
				print("\n\t" . '<link type="text/css" rel="stylesheet" href="' . $domain . '/css/jquery-ui.theme.min.css">');
				print("\n\t" . '<link type="text/css" rel="stylesheet" href="' . $domain . '/css/jquery.timepicker.min.css">');
			} elseif($include_type === 'javascript') {
				print("\n\t" . '<script src="' . $domain . '/javascript/jquery.min.js"></script>');
				print("\n\t" . '<script src="' . $domain . '/javascript/jquery-ui.min.js"></script>');
				print("\n\t" . '<script src="' . $domain . '/javascript/tooltip.js"></script>');

				if($this->HumanBeaconEnabled()) {
					print("\n\t" . '<script src="' . $domain . '/javascript/humanbeacon.js" async></script>');
				}
			}
			
			return TRUE;
		}
		
			/*
				The same question UserTracking::HumanBeaconEnabled asks, so a
				page never carries a beacon the server would not log.
			*/

		public function HumanBeaconEnabled() {
			if(!$this->globals || !method_exists($this->globals, 'EnableStats_HumanBeacon')) {
				return FALSE;
			}

			return $this->globals->EnableStats() && $this->globals->EnableStats_HumanBeacon();
		}

			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplayDoubleReturns() {
			print("\n\n");
			
			return TRUE;
		}
	}
	
?>
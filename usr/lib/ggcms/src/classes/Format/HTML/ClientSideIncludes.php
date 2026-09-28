<?php

	class ClientSideIncludes {
		public $desired_action;
		public $script_file;
		public $domain_object;
		public $secure_script;
		public $language;
		public $google_api;
		public $globals;
		
		public function __construct($args) {
			$this->desired_action = $args['desiredaction'];
			$this->script_file = $args['scriptfile'];
			$this->domain_object = $args['domainobject'];
			$this->secure_script = $_SERVER['HTTPS'] ?? '';		# unset under the CLI, as when the page cache is warmed
			$this->language = $args['language'];
			$this->google_api = $args['googleapi'];
			$this->globals = $args['globals'];
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
					
					if($include_type === 'javascript' && $this->LoadsGoogleSignIn()) {
						$domain = $this->domain_object->GetPrimaryDomain(['secure'=>$this->secure_script, 'www'=>0, 'lowercased'=>TRUE]);

						print("\n\t" . '<meta name="google-signin-client_id" content="' . htmlspecialchars($this->google_api->client_id, ENT_QUOTES, 'UTF-8') . '">');
						print("\n\t" . '<script src="' . $domain . '/javascript/google-signin.js"></script>');
						print("\n\t" . '<script src="https://accounts.google.com/gsi/client" async defer></script>');
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

				if($this->CarriesLanguageInLinks()) {
					print("\n\t" . '<script src="' . $domain . '/javascript/language-links.js" defer></script>');
				}
			}
			
			return TRUE;
		}
		
			/*
				Google's sign-in script, only where someone signs in or out.
				On every page it would tell Google about every reader's visit,
				and a page-cached page is rendered once for everyone anyway --
				the warmer renders without HTTPS, so the old test here left
				the script off every cached page.

				Google Identity Services (gsi/client) replaced platform.js,
				which Google now refuses to start: "idpiframe_initialization_
				failed", its libraries "are deprecated".  Google allows
				http://localhost as an origin, so there is no HTTPS test.
			*/

			// LoadsGoogleSignIn()
			// Tests: ClientSideIncludesTest::testLoadsGoogleSignIn()
			// Test file: tests/src/classes/Format/HTML/ClientSideIncludesTest.php
		public function LoadsGoogleSignIn() {
			if(!is_object($this->google_api) || empty($this->google_api->client_id)) {
				return FALSE;
			}

			return in_array($this->script_file, $this->GoogleSignInScripts(), TRUE);
		}

		public function GoogleSignInScripts() {
			return [
				'login',
				'logout',
			];
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

			/*
				A page in the site's default language needs no help; one in any
				other language has its links carry ?language= so the next page
				is in it too.  See javascript/language-links.js.
			*/

		public function CarriesLanguageInLinks() {
			if(!is_object($this->language) || empty($this->language->default_language_code)) {
				return FALSE;
			}

			return $this->language->language_code !== $this->language->default_language_code;
		}

			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplayDoubleReturns() {
			print("\n\n");
			
			return TRUE;
		}
	}
	
?>
<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


	class AbstractGlobals {
		use ReverseDNSNotation;
		public function __construct($args) {
			$this->setHandler($args);
			$this->buildAbstractGlobals_Scripts();
			$this->buildAbstractGlobals_Language_Scripts();
			$this->buildAbstractGlobals_Formats();
			$this->buildAbstractGlobals_ChildTypes();
			
			return $this;
		}
		
		public function setHandler($args) {
			$this->handler = $args['handler'];
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Scripts() {
			$shared_scripts_dir = 'clonefrom/scripts/' . $this->handler->script_file . '.php';
			
			$domain_scripts_dir = $this->ReverseDomainName(['domain'=>$this->handler->domain->primary_domain_lowercased]) . '/scripts/' . $this->handler->script_file . '.php';
			
			if(conf_isfile($shared_scripts_dir)) {
				confreq($shared_scripts_dir);
				
				$classname = 'AbstractGlobals_' . $this->handler->script_file;
				if(conf_isfile($domain_scripts_dir)) {
					confreq($domain_scripts_dir);
					$classname = 'local' . $classname;
				}
				
				$this->script = new $classname;
			}
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Language_Scripts() {
			$specific_args = [
				'script_file'=>$this->handler->script_file,
				'language_code'=>$this->handler->language->language_code,
				'primary_domain_lowercased'=>$this->handler->domain->primary_domain_lowercased,
			];
			
			$this->buildAbstractGlobals_Language_Scripts_specific($specific_args);
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Language_Scripts_reset($args) {
			$specific_args = [
				'script_file'=>$args['script_file'],
				'language_code'=>$this->handler->language->language_code,
				'primary_domain_lowercased'=>$this->handler->domain->primary_domain_lowercased,
			];
			
			$this->buildAbstractGlobals_Language_Scripts_specific($specific_args);
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Language_Scripts_specific($args) {
			$script_file = $args['script_file'];
			$language_code = $args['language_code'];
			$primary_domain_lowercased = $args['primary_domain_lowercased'];
			
			$shared_default_language_scripts_dir = 'clonefrom/language_scripts/' . $script_file . '/en.php';
			
			$shared_language_scripts_dir = 'clonefrom/language_scripts/' . $script_file . '/' . $language_code . '.php';
			
			$domain_language_scripts_dir = $this->ReverseDomainName(['domain'=>$primary_domain_lowercased]) . '/language_scripts/' . $script_file . '/' . $language_code . '.php';
			
			if(conf_isfile($domain_language_scripts_dir)) {
				confreq($domain_language_scripts_dir);
				$classname = 'AbstractGlobals_languagescript_' . $script_file . '_' . $language_code;
			} elseif(conf_isfile($shared_language_scripts_dir)) {
				confreq($shared_language_scripts_dir);
				$classname = 'AbstractGlobals_languagescript_' . $script_file . '_' . $language_code;
			} elseif(conf_isfile($shared_default_language_scripts_dir)) {
				confreq($shared_default_language_scripts_dir);
				$classname = 'AbstractGlobals_languagescript_' . $script_file . '_en';
			}
			
			if($classname) {
				$this->language_script = new $classname;
			}
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Formats() {
			$this->buildAbstractGlobals_Formats_LinkTo();
			$this->buildAbstractGlobals_Formats_Specific();
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Formats_LinkTo() {
			$shared_default_formats_linkto_location = 'clonefrom/formats/link_to.php';
			
			if(conf_isfile($shared_default_formats_linkto_location)) {
				confreq($shared_default_formats_linkto_location);
				$domain_formats_linkto_location = $this->ReverseDomainName(['domain'=>$primary_domain_lowercased]) . '/formats/link_to.php';
				
				$classname = 'AbstractGlobals_formats_linkto';
				
				if(is_file($domain_formats_linkto_location)) {
					$classname .= '_override';
					confreq($shared_default_formats_linkto_location);
					
					$this->formats_linkto = new $classname;
				} else {
					$this->formats_linkto = new $classname;
				}
			}
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Formats_Specific() {
			$shared_default_formats_linkto_location = 'clonefrom/formats/default_format.php';
			
			if(conf_isfile($shared_default_formats_linkto_location)) {
				confreq($shared_default_formats_linkto_location);
				$domain_formats_default_location = 'clonefrom/formats/specific/' . $this->handler->script_extension . '.php';
				
				$classname = 'AbstractGlobals_Formats_GivenRequestedFormat';
				
				if(conf_isfile($domain_formats_default_location)) {
					$classname .= '_override';
					confreq($domain_formats_default_location);
				
					$domain_formats_default_location_client = $this->ReverseDomainName(['domain'=>$primary_domain_lowercased]) . '/formats/specific/' . $this->handler->script_extension . '.php';
					
					if(conf_isfile($domain_formats_default_location_client)) {
						$classname .= '_client';
						confreq($domain_formats_default_location_client);
					}
				}
				
				$this->format_requested = new $classname;
			}
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_ChildTypes() {
			$shared_default_formats_linkto_location = 'clonefrom/child_types/enabled.php';
			
			if(conf_isfile($shared_default_formats_linkto_location)) {
				confreq($shared_default_formats_linkto_location);
				/*
					Two bugs lived here and masked each other.

					The domain came from $primary_domain_lowercased, an
					undefined local -- it is assigned in a different function --
					so this path never resolved and no site ever loaded its
					override.  And the override branch required the SHARED file
					a second time rather than the domain one, so had the path
					ever resolved, the _override class would not have been
					defined and the `new` below would have fatalled.

					Every site therefore ran silently on clonefrom's defaults,
					which set almost every child type to FALSE.  revoltlib held
					2,496 Image records, 437 Quotes, 228 Links and 27 Comments
					that the ORM was never asked to fetch, and its pages
					rendered without a single entry image.
				*/

				$domain_formats_linkto_location = $this->ReverseDomainName(['domain'=>$this->handler->domain->primary_domain_lowercased]) . '/child_types/enabled.php';
				
				$classname = 'AbstractGlobals_ChildTypes_enabled';
				
				if(conf_isfile($domain_formats_linkto_location)) {
					$classname .= '_override';
					confreq($domain_formats_linkto_location);
					
					$this->child_types = new $classname;
				} else {
					$this->child_types = new $classname;
				}
			}
			
			return TRUE;
		}
	}

?>
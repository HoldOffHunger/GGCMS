<?php

	/*
		Errors are shown only off production -- Handler::Construct_ProductionSite()
		turns display on there.  Switching it on here, for every request, printed
		PHP's own diagnostics into public pages whenever one got past the error
		handler: under PHP 8.4, hundreds of E_STRICT deprecations on every page.
	*/

error_reporting(E_ALL);


	class AbstractGlobals {
		use ReverseDNSNotation;
		public function __construct($args) {
			$this->setHandler($args);
			$this->buildAbstractGlobals_Scripts();
			$this->buildAbstractGlobals_Language_Scripts();
			$this->buildAbstractGlobals_Formats();
			$this->buildAbstractGlobals_ChildTypes();
			$this->buildAbstractGlobals_Site();
			$this->buildAbstractGlobals_RecordRelations();
		}
		
		public function setHandler($args) {
			$this->handler = $args['handler'];
			
			return TRUE;
		}
		
			/*
				Every builder here assembles a class name as a string and then
				instantiates it.  When a config file does not declare exactly the
				expected name the failure is a fatal carrying nothing useful, and
				both faults fixed on 1 September 2026 lived at that joint.

				A missing class is now an absent config object rather than a dead
				site.  Callers already test for absence --
				Handler::Construct_Dictionaries_Wanted() is the pattern.

				This is deliberately a downgrade from fatal to absent rather than a
				repair.  A config file that names its class wrongly is still wrong.
			*/

		public function NewConfigClass($args) {
			$classname = $args['classname'];

			if(!class_exists($classname)) {
				return FALSE;
			}

			return new $classname;
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
				
				$this->script = $this->NewConfigClass(['classname'=>$classname]);
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
				$this->language_script = $this->NewConfigClass(['classname'=>$classname]);
			}
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Formats() {
			$this->buildAbstractGlobals_Formats_LinkTo();
			$this->buildAbstractGlobals_Formats_Specific();
			
			return TRUE;
		}
		
			/*
				Three faults lived here, and the first two masked each other exactly
				as they did in _ChildTypes().

				The domain came from $primary_domain_lowercased, an undefined local
				assigned in a different function, so the path never resolved.  The
				override branch then required the SHARED file a second time rather
				than the domain one, so _override was never defined and the `new`
				would have fatalled had the path ever resolved.  And the test used
				is_file() rather than conf_isfile(), so a relative config path could
				not resolve even with a correct domain.

				revoltlib and anarchistcode both ship a link_to.php override and
				neither has ever been loaded.
			*/

		public function buildAbstractGlobals_Formats_LinkTo() {
			$shared_default_formats_linkto_location = 'clonefrom/formats/link_to.php';
			
			if(conf_isfile($shared_default_formats_linkto_location)) {
				confreq($shared_default_formats_linkto_location);
				$domain_formats_linkto_location = $this->ReverseDomainName(['domain'=>$this->handler->domain->primary_domain_lowercased]) . '/formats/link_to.php';
				
				$classname = 'AbstractGlobals_formats_linkto';
				
				if(conf_isfile($domain_formats_linkto_location)) {
					$classname .= '_override';
					confreq($domain_formats_linkto_location);
				}
				
				$this->formats_linkto = $this->NewConfigClass(['classname'=>$classname]);
			}
			
			return TRUE;
		}
		
		public function buildAbstractGlobals_Formats_Specific() {
			$shared_default_formats_linkto_location = 'clonefrom/formats/default_format.php';
			
			if(conf_isfile($shared_default_formats_linkto_location)) {
				confreq($shared_default_formats_linkto_location);
					/*
						The same undefined local as _LinkTo(), plus a structural gap:
						the domain file was only reachable when a clonefrom file for
						that extension existed first.  Most domain extensions have no
						shared counterpart -- revoltlib carries nineteen legacy URL
						extensions clonefrom knows nothing about -- so they could
						never load.

						The config files already anticipate this.  revoltlib's pdf.php
						declares _override_client because it extends the shared one;
						its asp.php declares _override, because nothing is beneath it.
					*/

				$shared_formats_specific_location = 'clonefrom/formats/specific/' . $this->handler->script_extension . '.php';
				$domain_formats_specific_location = $this->ReverseDomainName(['domain'=>$this->handler->domain->primary_domain_lowercased]) . '/formats/specific/' . $this->handler->script_extension . '.php';
				
				$classname = 'AbstractGlobals_Formats_GivenRequestedFormat';
				
				if(conf_isfile($shared_formats_specific_location)) {
					$classname .= '_override';
					confreq($shared_formats_specific_location);
					
					if(conf_isfile($domain_formats_specific_location)) {
						$classname .= '_client';
						confreq($domain_formats_specific_location);
					}
				} elseif(conf_isfile($domain_formats_specific_location)) {
					$classname .= '_override';
					confreq($domain_formats_specific_location);
				}
				
				$this->format_requested = $this->NewConfigClass(['classname'=>$classname]);
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
				}
				
				$this->child_types = $this->NewConfigClass(['classname'=>$classname]);
			}

			return TRUE;
		}

			/*
				What the site is, rather than what any one script does: the
				Dublin Core a document format has to carry, the two flags that
				decide whether languages and search are offered at all, and the
				alternate domains robots.txt names.

				This replaces PrimaryHostRecord, a per-site table of RecordKey /
				RecordValue pairs -- a globals store that predates globals.  Its
				loader was removed without its readers, so every one of them had
				been reading an undefined property since: the two readiness flags
				read as NULL and so were permanently open, and every EPub, DAISY
				and OPDS file shipped with blank Dublin Core.

				What belongs to the primary top-level entry stays there and is
				deliberately absent here.  The site's name is that entry's Title,
				its release date that entry's OriginalCreationDate, its imagery
				and its keywords that entry's Image and Tag children.  Config
				answers only what no entry field can.

				Scoped to the site rather than the script, so it is built like
				ChildTypes and not like Scripts.
			*/

		/*
			Which relational walks this site renders.  Built like ChildTypes and
			Site -- scoped to the site rather than to any one script -- because
			whether a site prints next-and-previous links is a fact about the
			site.

			See etc/ggcms/clonefrom/record_relations/enabled.php for what the
			switches mean and why their defaults are FALSE.
		*/

		public function buildAbstractGlobals_RecordRelations() {
			$shared_relations_location = 'clonefrom/record_relations/enabled.php';

			if(conf_isfile($shared_relations_location)) {
				confreq($shared_relations_location);

				$domain_relations_location = $this->ReverseDomainName(['domain'=>$this->handler->domain->primary_domain_lowercased]) . '/record_relations/enabled.php';

				$classname = 'AbstractGlobals_RecordRelations_enabled';

				if(conf_isfile($domain_relations_location)) {
					$classname .= '_override';
					confreq($domain_relations_location);
				}

				$this->record_relations = $this->NewConfigClass(['classname'=>$classname]);
			}

			return TRUE;
		}

		public function buildAbstractGlobals_Site() {
			$shared_site_location = 'clonefrom/site/identity.php';

			if(conf_isfile($shared_site_location)) {
				confreq($shared_site_location);

				$domain_site_location = $this->ReverseDomainName(['domain'=>$this->handler->domain->primary_domain_lowercased]) . '/site/identity.php';

				$classname = 'AbstractGlobals_Site_identity';

				if(conf_isfile($domain_site_location)) {
					$classname .= '_override';
					confreq($domain_site_location);
				}

				$this->site = $this->NewConfigClass(['classname'=>$classname]);
			}

			return TRUE;
		}
	}

?>
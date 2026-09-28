<?php

	class HTML extends AbstractBaseFormat {
		public $formats_object;
		public $version_object;
		public $redirect_object;
		public $navigation_object;
		public $css_object;
		public $script;
		public $clientsideincludes_object;
		public $html_data;
		public $printerfriendly;
		public $invertedcolors;
		
		public function __construct($args) {
			$this->SetArgs($args);
			
			if($args['firstcall']) {
				$this->Construct_Requires();
			}
			
			$this->formats_object = new Formats(['handler'=>$this->handler]);
			$this->version_object = $args['versionobject'];
			
			$redirect_args = [
				'cleanser'=>$this->cleanser_object,
				'domainobject'=>$this->domain_object,
				'formats'=>$this->formats_object,
				'scriptfile'=>$this->script_file,
				'language'=>$this->language,
			];
			$this->redirect_object = new HTML_Redirect($redirect_args);
			
			$navigation_object_args = [
				'domainobject'=>$this->domain_object,
			];
			$this->navigation_object = new HTML_Navigation($navigation_object_args);
			
			$this->css_object = new CSS($args);
			
			$constructor_args = [
				'handler'=>$this->handler,
				'desiredscript'=>$this->desired_script,
				'desiredaction'=>$this->desired_action,
				'objectcode'=>$this->object_code,
				'objectparent'=>$this->object_parent,
				'objectlist'=>$this->object_list,
				'scriptlocation'=>$this->script_location,
				'scriptname'=>$this->script_name,
				'scriptfile'=>$this->script_file,
				'scriptextension'=>$this->script_extension,
				'scriptformat'=>$this->script_format,
				'scriptformatlower'=>$this->script_format_lower,
				'scriptargs'=>$this->script_args,
				'googleapi'=>$this->google_api,
				'yahooapi'=>$this->yahoo_api,
				'authenticationobject'=>$this->authentication_object,
				'cleanserobject'=>$this->cleanser_object,
				'queryobject'=>$this->query_object,
				'dbaccessobject'=>$this->db_access_object,
				'globals'=>$this->globals,
				'domainobject'=>$this->domain_object,
				'languageobject'=>$this->language,
				'dictionary'=>$this->dictionary,
				'time'=>$this->time,
				'cookie'=>$this->cookie,
				'formatsobject'=>$this->formats_object,
				'versionobject'=>$this->version_object,
				'redirectobject'=>$this->redirect_object,
			];
			
			require($this->script_location);
			$this->script = new $this->script_classname($constructor_args);
			
			$js_and_css_args = [
				'desiredaction'=>$this->desired_action,
				'scriptfile'=>$this->script_file,
				'domainobject'=>$this->domain_object,
				'securescript'=>$this->domain_object->GetHTTPSConnection(),
				'language'=>$this->language,
				'googleapi'=>$this->google_api,
				'globals'=>$this->globals,
			];
			
			$this->clientsideincludes_object = new ClientSideIncludes($js_and_css_args);
		}
		
			// Construct ~ Requires
			// -----------------------------------------------
		
		public function Construct_Requires() {
			ggreq('scripts/Format/base_format.php');
			ggreq('scripts/Format/' . $this->script_format . '/basicscript.php');
			ggreq('classes/Format/Base/Formats.php');
			ggreq('classes/Format/HTML/Redirect.php');
			ggreq('classes/Format/HTML/Navigation.php');
			ggreq('classes/Format/CSS.php');
			ggreq('classes/Format/HTML/ClientSideIncludes.php');
			
			return TRUE;
		}
		
			// Display HTML
			// -----------------------------------------------
		
		public function Display() {
			$desired_action = $this->desired_action;
			
			$client_method_found = 0;
			if($this->domain_object->host) {
				$host_desired_action = $desired_action . '_' . $this->domain_object->host;
				
					/*
						Only a method the script declares itself, never one it
						inherited.  view.php declares display_wordweight for the
						dictionary, and every script that extends view -- login,
						about, terms, register and two dozen more -- inherited it.
						On wordweight each of them ran the word lookup, which is
						only switched on for view, got FALSE back, and answered
						404: /login.php and /about.php, on every page's login link.
					*/

				if(method_exists($this->script, $host_desired_action) && (new ReflectionMethod($this->script, $host_desired_action))->getDeclaringClass()->getName() === get_class($this->script)) {
					$display_results = $this->script->$host_desired_action();
					$client_method_found = 1;
				}
			}
			
			if(!$client_method_found) {
				$display_results = $this->script->$desired_action();
			}
			
			if(!$display_results) {
				return FALSE;
			}
			
			$this->html_data = $this->script->GetHTMLFormatData();
			
			$this->StartHTML();
			$this->script->DisplayTemplates();
			$this->FinishHTML();
			
			return TRUE;
		}
		
		public function StartHTML() {
			$this->StartHTML_HTML_Start();
			$this->StartHTML_Head();
			$this->StartHTML_Body_Start();
			$this->StartHTML_Navigation();
		}
		
		public function StartHTML_Navigation() {
			if($this->script->navigation) {
				$this->navigation_object->Display();
			}
		}
		
			// HTML Start
			// -----------------------------------------------
		
			/*
				No page ever declared a doctype, so every browser drew every
				page in quirks mode: tables sized by the 1990s' rules, line
				boxes and percentage heights computed differently, and modern
				CSS quietly doing less than it says.  The redesign needs the
				standard mode -- see Docs/Styling.md.
			*/

		public function StartHTML_HTML_Start() {
			print('<!DOCTYPE html>' . "\n");
			print('<html lang="' . $this->html_data['contentlanguage'] . '">');
		}
		
			// HTML Head
			// -----------------------------------------------
			
				// Head ~ Handle Templates
				// -----------------------------------------------
		
		public function StartHTML_Head() {
			$this->StartHTML_Head_Start();
			$this->StartHTML_Head_Title();
			
			$this->printerfriendly = $this->query_object->Parameter(['parameter'=>'printerfriendly']);
			$this->invertedcolors = $this->query_object->Parameter(['parameter'=>'invertedcolors']);
			
			if(!$this->printerfriendly) {
				$this->StartHTML_Head_HTTPEquivalents();
				$this->StartHTML_Head_ArticleInformation();
				$this->StartHTML_Head_AuthorInformation();
				$this->StartHTML_Head_LanguageInformation();
				$this->StartHTML_Head_SearchEngineData();
				$this->StartHTML_Head_Classifications();
				$this->StartHTML_Head_ServerAndTechnicalCredits();
				$this->StartHTML_Head_ServerAndTechnicalDetails();
				$this->StartHTML_Head_DublinCore();
				$this->StartHTML_Head_WebAppInformation();
				$this->StartHTML_Head_FaviconInformation();
				$this->StartHTML_Head_AlternateVersions();
				$this->StartHTML_Head_CSSandJavascript();
			} else {
				$this->StartHTML_Head_HTTPEquivalents();
				$this->StartHTML_Head_SearchEngineData();
				$this->StartHTML_Head_LanguageInformation();
				$this->StartHTML_Head_CSS();
				$this->script->HTMLHeadDisplayExtra_CSS();
			}
				
			$this->StartHTML_Head_End();
		}
		
		public function StartHTML_Head_Start() {
			$this->DisplayDoubleReturns();
			
			print("\t\t\t" . '<!-- Head -->');
			
			$this->DisplayDoubleReturns();

			print('<head>');

			$this->StartHTML_Head_NightReading();

			$this->script->HTMLHeadDisplayExtra_Start();
		}

			/*
				A reader's night-reading choice, applied before anything is
				drawn, so a dark reader never sees a white flash first.  The
				page cache serves the same HTML to everyone, so the choice
				cannot come from the server; it lives in the reader's own
				browser.  javascript/night-reading.js is the toggle.
			*/

		public function StartHTML_Head_NightReading() {
			if(!is_object($this->globals) || !method_exists($this->globals, 'NightReading') || !$this->globals->NightReading()) {
				return FALSE;
			}

			print("\n\t" . '<script>try{var t=localStorage.getItem("ggcms-theme");if(t==="dark"||t==="light"){document.documentElement.setAttribute("data-theme",t);}}catch(e){}</script>');

			return TRUE;
		}
		
		public function StartHTML_Head_Title() {
			if($this->html_data['title']) {
				$this->DisplayDoubleReturns();
				print("\t\t" . '<!-- Title -->');
				
				$this->DisplayDoubleReturns();
				
				print("\t" . '<title>');
						
				print($this->script->HTMLTitle());
				print('</title>');
				
				print("\n");
				
				print("\t");
				print('<meta property="og:title" content="');
				print(str_replace('"', '&quot;', $this->html_data['title']));
				print('">');
			}
			$this->script->HTMLHeadDisplayExtra_Title();
		}
		
		public function StartHTML_Head_HTTPEquivalents() {
			$this->DisplayDoubleReturns();
		
			print("\t\t" . '<!-- Basic HTTP Equiv Tags -->');
			$this->DisplayDoubleReturns();
			
			if($this->html_data['contenttype']) {
				print("\t");
				print('<meta http-equiv="content-type" content="');
				print($this->html_data['contenttype']);
				print('">');
				print("\n");
			}
			
			if($this->html_data['contentlanguage']) {
				print("\t");
				print('<meta http-equiv="content-language" content="');
				print($this->html_data['contentlanguage']);
				print('">');
				print("\n");
			}

			if($this->html_data['contentscripttype']) {
				print("\t");
				print('<meta http-equiv="content-script-type" content="');
				print($this->html_data['contentscripttype']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['contentstyletype']) {
				print("\t");
				print('<meta http-equiv="content-style-type" content="');
				print($this->html_data['contentstyletype']);
				print('">');
				print("\n");
			}
			
			$this->script->HTMLHeadDisplayExtra_HTTPEquivalents();
		}
		
		public function StartHTML_Head_ArticleInformation() {
			$this->DisplayDoubleReturns();

			print("\t\t" . '<!-- Article Information -->');
			
			$this->DisplayDoubleReturns();

			$primary_domain = $this->handler->domain->GetPrimaryDomain(['www'=>1, 'secure'=>$this->handler->domain->secure]);
			
			if($this->html_data['description']) {
				print("\t");
				print('<meta name="description" content="');
				print(str_replace('"', '&quot;', $this->html_data['description']));
				print('">');
				print("\n");
				
				print("\t");
				print('<meta property="og:description" content="');
				print(str_replace('"', '&quot;', $this->html_data['description']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['abstract']) {
				print("\t");
				print('<meta name="abstract" content="');
				print(str_replace('"', '&quot;', $this->html_data['abstract']));
				print('">');
				print("\n");
			}
			
			if($this->html_data['keywords']) {
				print("\t");
				print('<meta name="keywords" content="');
				print(str_replace('"', '&quot;', $this->html_data['keywords']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['newskeywords']) {
				print("\t");
				print('<meta name="news_keywords" content="');
				print(str_replace('"', '&quot;', $this->html_data['newskeywords']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['classification']) {
				print("\t");
				print('<meta name="classification" content="');
				print(str_replace('"', '&quot;', $this->html_data['classification']));
				print('">');
				print("\n");
			}

			print("\t");
			print('<meta property="og:url" content="');
			print($primary_domain . $_SERVER['REQUEST_URI']);
			print('">');
			print("\n");
			
			$this->script->HTMLHeadDisplayExtra_ArticleInformation();
		}
		
		public function StartHTML_Head_AuthorInformation() {
			$this->DisplayDoubleReturns();

			print("\t\t" . '<!-- Author Information -->');
			
			$this->DisplayDoubleReturns();
			
			if($this->html_data['author']) {
				print("\t");
				print('<meta name="author" content="');
				print(str_replace('"', '&quot;', $this->html_data['author']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['contact']) {
				print("\t");
				print('<meta name="contact" content="');
				print(str_replace('"', '&quot;', $this->html_data['contact']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['replyto']) {
				print("\t");
				print('<meta name="reply-to" content="');
				print(str_replace('"', '&quot;', $this->html_data['replyto']));
				print('">');
				print("\n");
			}
			
			if($this->html_data['webauthor']) {
				print("\t");
				print('<meta name="web_author" content="');
				print(str_replace('"', '&quot;', $this->html_data['webauthor']));
				print('">');
				print("\n");
			}

			if($this->html_data['copyright']) {
				print("\t");
				print('<meta name="copyright" content="');
				print(str_replace('"', '&quot;', $this->html_data['copyright']));
				print('">');
				print("\n");
			}

			$this->script->HTMLHeadDisplayExtra_AuthorInformation();
		}
		
		public function StartHTML_Head_LanguageInformation() {
			$this->DisplayDoubleReturns();
			
			print("\t\t" . '<!-- Language -->');
			
			$this->DisplayDoubleReturns();

			if($this->html_data['language']) {
				print("\t");
				print('<meta name="language" content="');
				print($this->html_data['language']);
				print('">');
				print("\n");
			}
			
			$this->script->HTMLHeadDisplayExtra_LanguageInformation();
		}
		
		public function StartHTML_Head_SearchEngineData() {
			$this->DisplayDoubleReturns();
			
			print("\t\t" . '<!-- Search Engine Data -->');
			
			$this->DisplayDoubleReturns();

			if($this->html_data['robots']) {
				print("\t");
				print('<meta name="robots" content="');
				print($this->html_data['robots']);
				print('">');
				print("\n");
			}

			if($this->html_data['cachecontrol']) {
				print("\t");
				print('<meta name="cache-control" content="');
				print($this->html_data['cachecontrol']);
				print('">');
				print("\n");
			}
			
			if($this->html_data['pragma']) {
				print("\t");
				print('<meta name="pragma" content="');
				print($this->html_data['pragma']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['pragma']) {
				print("\t");
				print('<meta http-equiv="pragma" content="');
				print($this->html_data['pragma']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['distribution']) {
				print("\t");
				print('<meta name="distribution" content="');
				print($this->html_data['distribution']);
				print('">');
				print("\n");
			}

			if($this->html_data['revisitafter']) {
				print("\t");
				print('<meta name="revisit-after" content="');
				print($this->html_data['revisitafter']);
				print('">');
				print("\n");
			}

			if($this->html_data['expires']) {
				print("\t");
				print('<meta name="expires" content="');
				print($this->html_data['expires']);
				print('">');
				print("\n");
			
				print("\t");
				print('<meta http-equiv="expires" content="');
				print($this->html_data['expires']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['noemailcollection']) {
				print("\t");
				print('<meta name="no-email-collection" content="');
				print($this->html_data['noemailcollection']);
				print('">');
				print("\n");
			}
			
			if($this->html_data['googlebot']) {
				print("\t");
				print('<meta name="googlebot" content="');
				print($this->html_data['googlebot']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['slurp']) {
				print("\t");
				print('<meta name="slurp" content="');
				print($this->html_data['slurp']);
				print('">');
				print("\n");
			}
	
			print("\t");
			print('<link rel="canonical" href="');
	
			print($this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
			print($_SERVER['REDIRECT_URL']);
	
			$page = (int)$this->query_object->Parameter(['parameter'=>'page']);
			$per_page = (int)$this->query_object->Parameter(['parameter'=>'perpage']);
			
			if($page || $per_page) {
				print('?');
				if($page) {
					print('page=' . $page);
				}
				
				if($page && $per_page) {
					print('&');
				}
				
				if($per_page) {
					print('perpage=' . $per_page);
				}
			}
			
			print('">');
		
			if($this->script->entry['image'] && $this->script->entry['image'][0]) {
				$image = $this->script->entry['image'][0];
				
				if($image['id'] && $image['IconFileName']) {
					$image_pieces = explode('.', $image['IconFileName']);
					$image_extension = array_pop($image_pieces);
				
					ggreq('classes/Networking/MIMEType.php');
					$mimetype = new MIMEType(['handler'=>$this->handler]);
					$mimetypes = $mimetype->GetMIMETypeCodes();
					
					print("\n");
					print("\t");
					print('<link rel="image_src" ');
					print('type="');
					print($mimetypes[$image_extension]);
					print('" ');
					print('src="');
					print('/image/');
					print(implode('/', str_split($image['FileDirectory'])));
					print('/');
					print($image['IconFileName']);
					print('"');
					print(' />');
				}
			}
		
			/*
				Google Analytics is gone.  Every site was tagged with a Universal
				Analytics property, and Google stopped processing those in 2023, so
				each page loaded gtag.js to record nothing.  Readers are counted by
				humanbeacon.js, which reports to this host.
			*/

			$this->DisplayDoubleReturns();
			
			if($this->domain_object->host === 'earthfluent') {
				print('<script data-ad-client="ca-pub-5613154091905636" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>');
			}
	
			$this->script->HTMLHeadDisplayExtra_SearchEngineData();
		}
		
		public function StartHTML_Head_Classifications() {
			$this->DisplayDoubleReturns();
			
			print("\t\t" . '<!-- Classifications -->');
			
			$this->DisplayDoubleReturns();

			if($this->html_data['doctype']) {
				print("\t");
				print('<meta name="doc-type" content="');
				print($this->html_data['doctype']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['docclass']) {
				print("\t");
				print('<meta name="doc-class" content="');
				print($this->html_data['docclass']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['docrights']) {
				print("\t");
				print('<meta name="doc-rights" content="');
				print($this->html_data['docrights']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['resourcetype']) {
				print("\t");
				print('<meta name="resource-type" content="');
				print($this->html_data['resourcetype']);
				print('">');
				print("\n");
			}
			
			if($this->html_data['rating']) {
				print("\t");
				print('<meta name="rating" content="');
				print($this->html_data['rating']);
				print('">');
				print("\n");
			}
			
			$this->script->HTMLHeadDisplayExtra_Classifications();
		}
		
		public function StartHTML_Head_ServerAndTechnicalCredits() {
			$this->DisplayDoubleReturns();

			print("\t\t" . '<!-- Server and Technical Credits -->');
			
			$this->DisplayDoubleReturns();
			
			if($this->html_data['designer']) {
				print("\t");
				print('<meta name="designer" content="');
				print($this->html_data['designer']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['generator']) {
				print("\t");
				print('<meta name="generator" content="');
				print($this->html_data['generator']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['publisher']) {
				print("\t");
				print('<meta name="publisher" content="');
				print($this->html_data['publisher']);
				print('">');
				print("\n");
			}
	
			if($this->html_data['progid']) {
				print("\t");
				print('<meta name="progid" content="');
				print($this->html_data['progid']);
				print('">');
				print("\n");
			}
			
			$this->script->HTMLHeadDisplayExtra_ServerAndTechnicalCredits();
		}
		
		public function StartHTML_Head_ServerAndTechnicalDetails() {
			$this->DisplayDoubleReturns();

			print("\t\t" . '<!-- Server and Technical Details -->');
			
			$this->DisplayDoubleReturns();

			if($this->html_data['template']) {
				print("\t");
				print('<meta name="template" content="');
				print($this->html_data['template']);
				print('">');
				print("\n");
			}
			
			if($this->html_data['viewport']) {
				print("\t");
				print('<meta name="viewport" content="');
				print($this->html_data['viewport']);
				print('">');
				print("\n");
			}
			
			$this->script->HTMLHeadDisplayExtra_ServerAndTechnicalDetails();
		}
		
		public function StartHTML_Head_DublinCore() {
			$this->DisplayDoubleReturns();
			
			print("\t\t" . '<!-- Dublin Core -->');
			
			$this->DisplayDoubleReturns();
			
			if($this->html_data['dctitle']) {
				print("\t");
				print('<meta name="dc.title" content="');
				print(str_replace('"', '&quot;', $this->html_data['dctitle']));
				print('">');
				print("\n");
			}
		
			if($this->html_data['dccreator']) {
				print("\t");
				print('<meta name="dc.creator" content="');
				print(str_replace('"', '&quot;', $this->html_data['dccreator']));
				print('">');
				print("\n");
			}
			
			if($this->html_data['dcsubject']) {
				print("\t");
				print('<meta name="dc.subject" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcsubject']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['dcdescription']) {
				print("\t");
				print('<meta name="dc.description" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcdescription']));
				print('">');
				print("\n");
			}

			if($this->html_data['dcpublisher']) {
				print("\t");
				print('<meta name="dc.publisher" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcpublisher']));
				print('">');
				print("\n");
			}
		
			if($this->html_data['dccontributor']) {
				print("\t");
				print('<meta name="dc.contributor" content="');
				print(str_replace('"', '&quot;', $this->html_data['dccontributor']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['dcdate']) {
				print("\t");
				print('<meta name="dc.date" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcdate']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['dctype']) {
				print("\t");
				print('<meta name="dc.type" content="');
				print(str_replace('"', '&quot;', $this->html_data['dctype']));
				print('">');
				print("\n");
			}

			if($this->html_data['dcformat']) {
				print("\t");
				print('<meta name="dc.format" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcformat']));
				print('">');
				print("\n");
			}
			
			if($this->html_data['dcidentifier']) {
				print("\t");
				print('<meta name="dc.identifier" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcidentifier']));
				print('">');
				print("\n");
			}
			
			if($this->html_data['dcsource']) {
				print("\t");
				print('<meta name="dc.source" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcsource']));
				print('">');
				print("\n");
			}
	
			if($this->html_data['dclanguage']) {
				print("\t");
				print('<meta name="dc.language" content="');
				print($this->html_data['dclanguage']);
				print('">');
				print("\n");
			}

			if($this->html_data['dcrelation']) {
				print("\t");
				print('<meta name="dc.relation" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcrelation']));
				print('">');
				print("\n");
			}

			if($this->html_data['dccoverage']) {
				print("\t");
				print('<meta name="dc.coverage" content="');
				print(str_replace('"', '&quot;', $this->html_data['dccoverage']));
				print('">');
				print("\n");
			}

			if($this->html_data['dcrights']) {
				print("\t");
				print('<meta name="dc.rights" content="');
				print(str_replace('"', '&quot;', $this->html_data['dcrights']));
				print('">');
				print("\n");
			}
			
			$this->script->HTMLHeadDisplayExtra_DublinCore();
		}
		
		public function StartHTML_Head_AlternateVersions() {
			$this->DisplayDoubleReturns();
			
			print("\t\t" . '<!-- Alternate Versions -->');
			
			$this->DisplayDoubleReturns();
			
			$this->redirect_object->PrintAllVersionURLs(['script'=>$this->script]);
			$this->script->HTMLHeadDisplayExtra_AlternateVersions();
		}
		
		public function StartHTML_Head_WebAppInformation() {
			$this->DisplayDoubleReturns();

			print("\t\t" . '<!-- WebApp Information -->');
			
			$this->DisplayDoubleReturns();

			print("\t");
			print('<meta name="application-name" content="');
			print($this->html_data['applicationname']);
			print('">');
			print("\n");
			
			print("\t");
			print('<meta name="application-tooltip" content="');
			print($this->html_data['applicationname']);
			print('">');
			print("\n");
	
			if(is_file('browserconfig.xml')) {
				print("\t");
				print('<meta name="msapplication-config" content="' . $this->domain_object->GetPrimaryDomain(['www'=>1, 'secure'=>$this->domain_object->secure]) . '/browserconfig.xml">');
				print("\n");
			}
	
			print("\t");
			print('<meta name="msapplication-starturl" content="');
			print $this->domain_object->GetPrimaryDomain(['www'=>1, 'secure'=>$this->domain_object->secure]);
			print('/">');
			print("\n");
			
			print("\t");
			print('<meta name="msapplication-allowDomainApiCalls" content="true">');
			print("\n");
			
			print("\t");
			print('<meta name="msapplication-allowDomainMetaTags" content="true">');
			print("\n");
		}
		
		public function StartHTML_Head_FaviconInformation() {
			$this->DisplayDoubleReturns();
			$displayed = 0;
			
			$primary_domain = $this->handler->domain->GetPrimaryDomain(['www'=>1, 'secure'=>$this->domain_object->secure]);
			
			if(data_isfile('favicon.ico', $this->handler)) {
				print("\t" . '<link rel="icon" href="' . $primary_domain . '/favicon.ico" type="image/x-icon">' . "\n");
				print("\t" . '<link rel="shortcut icon" href="' . $primary_domain . '/favicon.ico" type="image/x-icon">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-57x57.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="57x57" href="' . $primary_domain . '/apple-touch-icon-57x57.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-60x60.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="60x60" href="' . $primary_domain . '/apple-touch-icon-60x60.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-72x72.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="72x72" href="' . $primary_domain . '/apple-touch-icon-72x72.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-76x76.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="76x76" href="' . $primary_domain . '/apple-touch-icon-76x76.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-114x114.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="114x114" href="' . $primary_domain . '/apple-touch-icon-114x114.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-120x120.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="120x120" href="' . $primary_domain . '/apple-touch-icon-120x120.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-144x144.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="144x144" href="' . $primary_domain . '/apple-touch-icon-144x144.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-152x152.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="152x152" href="' . $primary_domain . '/apple-touch-icon-152x152.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('apple-touch-icon-180x180.png', $this->handler)) {
				print("\t" . '<link rel="apple-touch-icon" sizes="180x180" href="' . $primary_domain . '/apple-touch-icon-180x180.png">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('favicon-32x32.png', $this->handler)) {
				print("\t" . '<link rel="icon" type="image/png" href="' . $primary_domain . '/favicon-32x32.png" sizes="32x32">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('android-chrome-192x192.png', $this->handler)) {
				print("\t" . '<link rel="icon" type="image/png" href="' . $primary_domain . '/android-chrome-192x192.png" sizes="192x192">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('favicon-96x96.png', $this->handler)) {
				print("\t" . '<link rel="icon" type="image/png" href="' . $primary_domain . '/favicon-96x96.png" sizes="96x96">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('favicon-16x16.png', $this->handler)) {
				print("\t" . '<link rel="icon" type="image/png" href="' . $primary_domain . '/favicon-16x16.png" sizes="16x16">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('manifest.json', $this->handler)) {
				print("\t" . '<link rel="manifest" href="' . $primary_domain .  '/manifest.json">' . "\n");
				$displayed = 1;
			}
			
			if(data_isfile('safari-pinned-tab.svg', $this->handler)) {
				print("\t" . '<link rel="mask-icon" href="' . $primary_domain . '/safari-pinned-tab.svg" color="#5bbad5">' . "\n");
				$displayed = 1;
			}
			
			if(($this->script->entry['image'] && $this->script->entry['image'][0])) {
				$image = $this->script->entry['image'][0];
				print("\t" . '<meta property="og:image" content="');
				print($primary_domain);
				print('/image/');
				print(implode('/', str_split($image['FileDirectory'])));
				print('/');
				print($image['IconFileName']);
				print('">' . "\n");
				$displayed = 1;
			}
			
			if($displayed) {
				print("\t" . '<meta name="msapplication-TileColor" content="#da532c">' . "\n");
				print("\t" . '<meta name="msapplication-TileImage" content="' . $primary_domain . '/mstile-144x144.png">' . "\n");
				print("\t" . '<meta name="theme-color" content="#ffffff">');
			} else {
				print("\t" . '<!-- Not Available -->');
			}
		}
		
		public function StartHTML_Head_CSSandJavascript() {
			$this->StartHTML_Head_CSS();
			$this->script->HTMLHeadDisplayExtra_CSS();
			print("\n");
			$this->StartHTML_Head_Javascript();
			$this->script->HTMLHeadDisplayExtra_Javascript();
		}
		
		public function StartHTML_Head_CSS() {
			$headers_args = [
				'includetype'=>'css',
			];
			
			if(!$this->css_object->OneCSSFilePerPage()) {
				$this->clientsideincludes_object->Headers($headers_args);
			} else {
				$this->clientsideincludes_object->Headers_Simple($headers_args);
			}
		}
		
		public function StartHTML_Head_Javascript() {
			$headers_args = [
				'includetype'=>'javascript',
			];
			$this->clientsideincludes_object->Headers($headers_args);
		}
		
		public function StartHTML_Head_End() {
			$this->DisplayDoubleReturns();
			print('</head>');
		}
		
			// HTML Body
			// -----------------------------------------------
		
		public function StartHTML_Body_Start() {
			$this->DisplayDoubleReturns();
			
			print("\t\t" . '<!-- Body -->');
			
			$this->DisplayDoubleReturns();

			print('<body');
		
			if($this->invertedcolors) {
				print(' class="');
				print('background-color-gray0 color-gray15');
				print('"');
			}
			print('>');
			
			$this->DisplayDoubleReturns();
		}
		
			// HTML Finish
			// -----------------------------------------------
		
		public function FinishHTML() {
			print('</body>');
			$this->DisplayDoubleReturns();
			print('</html>');
		}
		
			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplayDoubleReturns() {
			print("\n\n");
		}
	}

?>
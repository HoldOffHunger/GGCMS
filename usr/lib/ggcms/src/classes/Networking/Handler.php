<?php
	
	class Handler {
		use ReverseDNSNotation;
		
		public $db_access;
		public $local_host_handler;
		public $error_logging;
		public $issue_logging;
		public $version;
		public $version_object;
		public $access;
		public $redirect;
		public $authentication;
		public $cleanser;
		public $domain;
		public $cookie;
		public $abstractglobals;
		public $globals;
		public $original_get;
		public $query;
		public $language;
		public $dictionary;
		public $time;
		public $desired_action;
		public $object_list;
		public $desired_script;
		public $object_code;
		public $object_parent;
		public $script_name;
		public $orm;
		public $redirect_url;
		public $script_file;
		public $script_extension;
		public $script_classname;
		public $script_format;
		public $script_format_lower;
		public $script_location;
		public $google_api;
		public $logout_results;
		public $before_content;
		public $error404redirect;
		public $resolved_record_list;
		public $last_repair_changed;
		public $collect_redirect;
		public $script;
		public $original_request_uri;
		public $original_redirect_url;
		public $repair_count;
		public $repaired_to;
		public $error_404;
		public $user_tracking;
		public $redirects;
		public function __construct() {
			
			$this->Construct_Redirects();
			$this->LocalHostHandling();
			
			
			
			
			
			$this->ValidateSecurity();
			$this->Construct_SetErrorLogging();
			$this->Construct_SetDevelopmentVersion();
			$this->Construct_Cleanser();
			$this->Construct_Domain();
			$this->Construct_Time();
			$this->Construct_Cookie();
			$this->Construct_RepairQueryString();
			$this->Construct_Query();
			$this->Construct_Language();
			$this->Construct_Action();
			$this->Construct_ObjectsAndScripts();
			$this->Construct_ScriptName();
			$this->Construct_ScriptFileAndExtension();
			$this->Construct_ScriptClassname();
			$this->Construct_ScriptFormat();
			$this->Construct_Globals();
			$this->Construct_SiteLanguages();
			$this->Construct_ProductionSite();
			$this->Construct_DBAccess();
			$this->Construct_Dictionaries();
			$this->Construct_PresetAuthentication();
			
			$this->CheckPermalinkRedirect();
			
			if($this->script_name) {
				$this->Construct_ScriptLocation();
				$this->Construct_SocialMedia();
			}
		}
		
		public function Construct_UpgradeDBAccess() {
			if($this->authentication->CheckAuthenticationForCurrentObject_IsAdmin()) {
				ggreq('classes/Database/DBAccessUpgraded.php');
				
				$this->db_access = new DBAccessUpgraded([
					'handler'=>$this,
					'db_access'=>$this->db_access,
				]);
			}
			
			return TRUE;
		}
		
		public function LocalHostHandling() {
			$local_host_name = 'localhost';
			
			$server_variables = [
				'HTTP_HOST',
				'SERVER_NAME',
			];
			
			$valid_local_host = TRUE;
			
			$server_variables_count = count($server_variables);
			
			for($i = 0; $i < $server_variables_count; $i++) {
				$server_variable = $server_variables[$i];
				if($_SERVER[$server_variable] !== $local_host_name) {
					$valid_local_host = FALSE;
					$i = $server_variables_count;
				}
			}
			
			if($valid_local_host) {
				ggreq('classes/Networking/Handler/LocalHostHandler.php');
				$this->local_host_handler = new LocalHostHandler($this->getArgs());
				$this->local_host_handler->HandleLocalRequest();
			}
			
			return TRUE;
		}
		
			/*
				The redirect-and-repair stage lives in its own class; see
				HandlerRedirects.php.  It keeps nothing of its own, so it is
				made first and is ready whenever anything asks it.
			*/
		
		public function Construct_Redirects() {
			if(!class_exists('HandlerRedirects', FALSE)) {
				ggreq('classes/Networking/Handler/HandlerRedirects.php');
			}
			
			return $this->redirects = new HandlerRedirects($this->getArgs());
		}
		
		public function getArgs() {
			return [
				'handler'=>$this,
			];
		}
		
		public function ValidateSecurity() {
			setlocale(LC_ALL,'en_US.UTF-8');
				
				/*
					session.referer_check was set here, to 'TRUE'.  It guards only
					PHP's own session ids, and GGCMS signs in with its own
					AuthenticationToken cookie and never starts a PHP session, so it
					never applied -- and 'TRUE' is read as text the Referer must
					contain.  PHP 8.5 deprecates it.  The referrer defence that
					works is ValidateReferrals(): "You done been smote."
				*/
			
			return TRUE;
		}
		
		public function __destruct() {
			$this->db_access->DBEnd();
		}
		
		public function Construct_SetErrorLogging() {
			$this->error_logging = new ErrorLogging($this->getArgs());
			$this->issue_logging = new IssueLogging($this->getArgs());
			return TRUE;
		}
		
		public function Construct_SetDevelopmentVersion() {
			$version = new Version();
			$this->version = $version;
			$this->version_object = $version;	# BT: DELETE THIS!
			return TRUE;
		}
		
		public function Construct_PresetAuthentication() {
			$this->access = 0;
			$this->redirect = '';
			
			$authentication = new Authentication($this->getArgs());
			return $this->authentication = $authentication;
		}
		
		public function CheckSecurity() {
			$authenticate_args = [
				'script'=>$this->script,
			];
			$authentication = $this->authentication;
			$authentication->Authenticate($authenticate_args);
			
			$this->access = $authentication->access_granted;
			$this->redirect = $authentication->redirect;
			
			if($this->script->script->isSecure()) {
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
				header("Cache-Control: post-check=0, pre-check=0", false);
				header("Pragma: no-cache");
				$this->authentication->ReAuthenticate();
			}
		}
		
		public function Construct_Cleanser() {
			$cleanser = new HandleInput($this->getArgs());
			return $this->cleanser = $cleanser;
		}
		
		public function Construct_Domain() {
			$domain = new Domain($this->getArgs());
			
			return $this->domain = $domain;
		}
		
		public function Construct_Cookie() {
			$cookie = new Cookie($this->getArgs());
			
			return $this->cookie = $cookie;
		}
		
			// Construct_Globals()
			// Tests: HandlerTest::testConstruct_Globals(), HandlerTest::testConstruct_GlobalsLoadsTheSite()
			// Test file: tests/src/classes/Networking/HandlerTest.php
		public function Construct_Globals() {
			confreq('clonefrom.php');
			$base_globals_classname = $this->ReverseDomainName(['domain'=>$this->domain->primary_domain_lowercased]) . '.php';
			
			$client_globals_location = $base_globals_classname;
			
				/*
					The domain becomes a path to require().  Domain no longer
					takes it from a request header, and this makes sure nothing
					that is not a host name can ever reach the filesystem here.
				*/
			
			if($this->domain->IsHostName(['name'=>$this->domain->primary_domain_lowercased]) && conf_isfile($client_globals_location)) {
				confreq($client_globals_location);
				$globals = new globals([]);
			} else {
				$globals = new defaultglobals([]);
			}
			
			ggreq('classes/System/AbstractGlobals.php');
			$this->abstractglobals = new AbstractGlobals([
				'handler'=>$this,
			]);
			
			return $this->globals = $globals;
		}
		
			/*
				The language was chosen before the site's config existed; see
				Language::ApplySiteLanguages().  If the site does not offer it,
				the language scripts built for it are rebuilt for the default.
			*/
		
		public function Construct_SiteLanguages() {
			$site = isset($this->abstractglobals->site) ? $this->abstractglobals->site : NULL;
			
			if($this->language->ApplySiteLanguages(['site'=>$site])) {
				$this->abstractglobals->buildAbstractGlobals_Language_Scripts();
			}
			
			return TRUE;
		}
		
		public function Construct_ProductionSite() {
			if(!$this->globals->isProductionSite()) {
				ini_set('display_errors', 1);
				ini_set('display_startup_errors', 1);
			}
			return TRUE;
		}
		
			// Query String Repair
			// -----------------------------------------------

		// Construct_RepairQueryString()
		// Tests: HandlerTest::testConstruct_RepairQueryString()
		// Test file: tests/src/classes/Networking/HandlerTest.php
		/*
			A URL carries one '?'.  Every later one is a separator that should
			have been '&', because something appended a parameter to a URL that
			already had a query string:

				view.pdf?mobilefriendly=1?mobilefriendly=1?action=browse

			PHP fills $_GET from the raw query string before this class runs, so
			without repair the script receives

				$_GET['mobilefriendly'] = '1?mobilefriendly=1?action=browse'

			and answers with a 500.  We repair rather than redirect: the visitor
			gets the page they asked for and pays no round trip for a typo.

			QUERY_STRING is everything after the FIRST '?', so any '?' still
			inside it is by definition a mis-typed separator.  That makes the
			test exact rather than heuristic.

			The original parse is kept in $GLOBALS['_ORIGINALGET'] and on
			$this->original_get, in case anything ever needs to know what
			actually arrived.
		*/

		public function Construct_RepairQueryString() {
			$query_string = $_SERVER['QUERY_STRING'];

			$this->original_get = $_GET;
			$GLOBALS['_ORIGINALGET'] = $_GET;

			if(!$this->QueryStringNeedsRepair(['querystring'=>$query_string])) {
				return FALSE;
			}

			$repaired_query_string = $this->RepairQueryString(['querystring'=>$query_string]);

			$repaired_get = [];
			parse_str($repaired_query_string, $repaired_get);

			$_GET = $repaired_get;
			$_SERVER['QUERY_STRING'] = $repaired_query_string;

			return TRUE;
		}

			// QueryStringNeedsRepair()
			// Tests: HandlerTest::testQueryStringNeedsRepair()
			// Test file: tests/src/classes/Networking/HandlerTest.php
		public function QueryStringNeedsRepair($args) {
			$query_string = $args['querystring'];

			if(strlen($query_string) === 0) {
				return FALSE;
			}

			return (strpos($query_string, '?') !== FALSE);
		}

			// RepairQueryString()
			// Tests: HandlerTest::testRepairQueryString()
			// Test file: tests/src/classes/Networking/HandlerTest.php
		public function RepairQueryString($args) {
			return str_replace('?', '&', $args['querystring']);
		}

		public function Construct_Query() {
			$query = new Query($this->getArgs());
			
			return $this->query = $query;
		}
		
		public function Construct_Language() {
			$language = new Language($this->getArgs());
			
			return $this->language = $language;
		}
		
		/*
			The dictionary is a second database on a second connection, so it is
			built for the pages that read it rather than for every request.

			Which pages those are is script-level AbstractGlobals config --
			clonefrom/scripts/view.php names browseByTag, and a domain that is
			itself a dictionary overrides it in com.wordweight/scripts/view.php.
			The action is known by here: Construct_Action runs nine lines earlier.
		*/

		public function Construct_Dictionaries_Wanted() {
			if(!isset($this->abstractglobals->script)) {
				return FALSE;
			}

			if(!is_object($this->abstractglobals->script)) {
				return FALSE;
			}

			if(!method_exists($this->abstractglobals->script, 'Dictionary_enabled')) {
				return FALSE;
			}

			return $this->abstractglobals->script->Dictionary_enabled([
				'action'=>$this->desired_action,
			]);
		}

		public function Construct_Dictionaries() {
			if($this->globals->EnableDictionaries() && $this->Construct_Dictionaries_Wanted()) {
				$folder_location_prefix = GGCMS_DIR . 'classes/';
				require($folder_location_prefix . 'Language/Dictionary' . '.php');
				
				$dictionary = new Dictionary($this->getArgs());
				
				return $this->dictionary = $dictionary;
			}
			return FALSE;
		}
		
		public function Construct_Time() {
			$time = new Time($this->getArgs());
			
			return $this->time = $time;
		}
		
		public function Construct_DBAccess() {
			$this->db_access = new DBAccess($this->getArgs());
			
			return $this->db_access->DBStart();
		}
		
		public function Construct_Action() {
			$cleanser_args = [
				'input'=>$this->query->Parameter(['parameter'=>'action']),
			];
			
			$this->desired_action = $this->cleanser->CleanseInput($cleanser_args)['cleansedinput'];
			
			if(strlen($this->desired_action) === 0) {
				$cleanser_args = [
					'input'=>$this->query->Parameter(['parameter'=>'act']),
				];
				
				$this->desired_action = $this->cleanser->CleanseInput($cleanser_args)['cleansedinput'];
			}
			
			if(strlen($this->desired_action) === 0) {
				$this->desired_action = 'display';
			}
			
			return TRUE;
		}
		
		public function Construct_ObjectsAndScripts() {
			$this->object_list = explode('/', ltrim($_SERVER['REDIRECT_URL'], '/'));
			
			$this->desired_script = array_pop($this->object_list);
			
			if($this->desired_script === 'index.html') {
				$this->desired_script = '';
			}
			
			$object_list_count = count($this->object_list);
			if($object_list_count > 0) {
				$this->object_code = $this->object_list[$object_list_count - 1];
				
				if($object_list_count > 1) {
					$this->object_parent = $this->object_list[$object_list_count - 2];
				}
			}
		}
		
		public function Construct_ScriptName() {
			$cleanser_args = [
				'input'=>$this->desired_script,
			];
			
			$this->script_name = $this->cleanser->CleanseInput($cleanser_args)['cleansedinput'];
			
			if(!$this->script_name) {
				$this->Construct_ScriptName_SetScriptNameDefault();
			}
			
			return TRUE;
		}
		
		public function CheckPermalinkRedirect() {
			$permalink_id = (int)$this->query->Parameter(['parameter'=>'id']);
			
			if($this->PermalinkRedirect(['permalink_id'=>$permalink_id])) {
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function PermalinkRedirect($args) {
			$permalink_id = $args['permalink_id'];
			
			if($permalink_id) {
				$assignment_record_args = [
					'type'=>'Assignment',
					'definition'=>[
						'id'=>$permalink_id,
					],
				];
				
				$assignment = $this->db_access->GetRecords($assignment_record_args);
				
				if($assignment && $assignment[0] && $assignment[0]['id']) {
					$this->BuildRedirect(['assignment'=>$assignment[0], 'permalink_id'=>$permalink_id]);
					return TRUE;
				} else {
					$this->issue_logging->createLog([
						'issuetype'=>'BadPermalink',
						'description'=>'Invalid Permalink ID: ' . $permalink_id,
					]);
				}
			}
			
			return FALSE;
		}
		
		public function BuildRedirect($args) {
			$assignment = $args['assignment'];
			$permalink_id = $args['permalink_id'];
			
			if(!$this->orm) {
				$this->orm = new ORM($this->getArgs());
			}
			
			$entry_records = $this->orm->SearchForEntries([
				'fieldname'=>'id',
				'fieldvalue'=>$assignment['Childid'],
				'assignmentid'=>$permalink_id,
				'includeunpublished'=>TRUE,
			])[0];
			
			if(!$entry_records || count($entry_records) === 0) {
			#	print("NONE");
				return FALSE;
			}
			
			$redirect_url = '';
			
			if($_SERVER['HTTPS'] === 'on') {
				$redirect_url .= 'https://';
			} else {
				$redirect_url .= 'http://';
			}
			$redirect_url .= $this->domain->primary_domain_lowercased;
			
			$entry_record_count = count($entry_records['parents']);
			for($i = 0; $i < $entry_record_count; $i++) {
				$entry_record = $entry_records['parents'][$i];
				$redirect_url .= '/' . $entry_record['Code'];
			}
			
			$action = $this->desired_action;
			
			if($action === 'Edit') {
				$redirect_url .= '/modify.php';
			} else {
				$redirect_url .= '/';
			}
			
			if($this->desired_action && $this->desired_action !== 'display') {
				if($action !== 'Edit') {
					$redirect_url .= 'view.php';
				}
				$redirect_url .= '?action=' . $this->desired_action;
			}
			
			return $this->redirect_url = $redirect_url;
		}
		
		public function Construct_ScriptFileAndExtension() {
			$script_name_pieces = explode('.', $this->script_name);
			array_pop($script_name_pieces);
			$this->script_file = implode('.', $script_name_pieces);
			$this->script_extension = pathinfo($this->script_name, PATHINFO_EXTENSION);
			
			return TRUE;
		}
		
		public function Construct_ScriptClassname() {
			$this->script_classname = str_replace('-', '', $this->script_file);
			
			return TRUE;
		}
		
		public function Construct_ScriptFormat() {
			$this->script_format = $this->Construct_ScriptFormat_DetermineScriptFormat();
			$this->script_format_lower = $this->Construct_ScriptFormatLower_DetermineScriptFormatLower();
			
			return TRUE;
		}
		
		public function Construct_ScriptLocation() {
			switch($this->script_format) {
				case 'CSS':
					$this->script_location = GGCMS_DIR . 'scripts/style.php';
					break;
				
				default:
					$this->script_location = GGCMS_DIR . 'scripts/' . $this->script_file . '.php';
					break;
			}
		}
		
		public function Construct_SocialMedia() {
			$this->google_api = new Google($this->getArgs());
			
			$google_token_id = $this->query->Parameter(['parameter'=>'google_token_id']);
			$google_log_results = $this->google_api->AuthenticateOrDisauthenticateWithGoogle([
				'token'=>$google_token_id,
				'logout'=>$this->query->Parameter(['parameter'=>'logout']),
			]);
			
			if($google_log_results['action'] === 'logout') {
				$this->logout_results = TRUE;
			}
			
			return TRUE;
		}
		
		public function Construct_ScriptName_SetScriptNameDefault() {
			return $this->script_name = 'view.php';
		}
		
			# one day: https://gist.github.com/aymen-mouelhi/82c93fbcd25f091f2c13faa5e0d61760
		public function Construct_ScriptFormat_DetermineScriptFormat() {
			switch ($this->script_extension) {
				case '':
				case 'php':
				case 'php3':
				case 'cfm':
				case 'cgi':
				case 'asp':
				case 'aspx':
				case 'htm':
				case 'html':
				case 'xhtml':
				case 'phtml':
				case 'shtml':
				case 'rhtml':
				case 'dll':
				case 'py':
				case 'rb':
				case 'php4':
				case 'pl':
				case 'wss':
				case 'jspx':
				case 'do':
				case 'action':
				case 'axd':
				case 'asx':
				case 'asmx':
				case 'ashx':
				case 'svc':
				case 'jsp':
				case 'yaws':
				case 'kt':
				case 'adp':
				case 'hta':
				case 'rjs':
				case 'erb':
				case 'htc':
				case 'dtl':
				case 'mvc':
					return 'HTML';
					
				case 'css':
					return 'CSS';
					
				case 'xml':
					return 'XML';
					
				case 'txt':
					return 'TXT';
				
				case 'pdf':
					return 'PDF';
					
				case 'rtf':
					return 'RTF';
					
				case 'epub':
					return 'EPub';
					
				case 'daisy':
					return 'DAISY';
					
				case 'json':
					return 'JSON';
					
				case 'csv':
					return 'CSV';
					
				case 'sgml':
					return 'SGML';
					
				case 'tex':
					return 'TEX';
					
				case 'opds':
					return 'OPDS';
					
				case 'rdf':
					return 'RDF';
					
				case 'rss':
					return 'RSS';
					
				case 'atom':
					return 'ATOM';
					
				case 'brf':
					return 'BRF';
					
					/*
				case 'apng':
				case 'avif':
				case 'bmp':
				case 'cur':
				case 'gif':
				case 'ico':
				case 'jfif':
				case 'jpeg':
				case 'jpg':
				case 'pjp':
				case 'pjpeg':
				case 'png':
				case 'svg':
				case 'tif':
				case 'tiff':
				case 'webp':
					return 'Image';
					*/
					
				default:
					return '';
			}
		}
		
		public function Construct_ScriptFormatLower_DetermineScriptFormatLower() {
			switch ($this->script_extension) {
				case '':
				case 'php':
				case 'php3':
				case 'cfm':
				case 'cgi':
				case 'asp':
				case 'aspx':
				case 'htm':
				case 'html':
				case 'xhtml':
				case 'phtml':
				case 'shtml':
				case 'rhtml':
				case 'dll':
				case 'py':
				case 'rb':
				case 'php4':
				case 'pl':
				case 'wss':
				case 'jspx':
				case 'do':
				case 'action':
				case 'axd':
				case 'asx':
				case 'asmx':
				case 'ashx':
				case 'svc':
				case 'jsp':
				case 'yaws':
				case 'kt':
				case 'adp':
				case 'hta':
				case 'rjs':
				case 'erb':
				case 'htc':
				case 'dtl':
				case 'mvc':
					return 'html';
					
				case 'css':
					return 'css';
					
				case 'xml':
					return 'xml';
					
				case 'txt':
					return 'txt';
					
				case 'pdf':
					return 'pdf';
					
				case 'rtf':
					return 'rtf';
					
				case 'epub':
					return 'epub';
					
				case 'daisy':
					return 'daisy';
				
				case 'json':
					return 'json';
					
				case 'csv':
					return 'csv';
					
				case 'sgml':
					return 'sgml';
					
				case 'tex':
					return 'tex';
					
				case 'opds':
					return 'opds';
					
				case 'rdf':
					return 'rdf';
					
				case 'rss':
					return 'rss';
					
				case 'atom':
					return 'atom';
					
				case 'brf':
					return 'brf';
					
				default:
					return '';
			}
		}
		
		public function HandleRequest() {
				/*
					Everything above HandleRequest_ServeContent runs before a
					format or a script class has been loaded, which is the whole
					condition that makes repairing safe: the request can be
					rewritten and carried forward without anything having to be
					re-entered.  A trailing bracket, a doubled slash, a /~user
					path -- all correctable here for nothing, where redirecting
					costs the reader a round trip and this host a second worker.
				*/

			$this->before_content = TRUE;

			if($this->handleHumanBeacon()) {
				return FALSE;
			}

			if($this->redirects->SecureRequired()) {
				return $this->redirects->SecureRedirect();
			}
			
					//start redirects
			
			if(!$this->ValidateReferrals()) {
				return FALSE;
			}
			
			if($this->redirects->handleMailTo()) {
				return FALSE;
			}
			
			if($this->redirects->handleGitRedirect()) {
				return FALSE;
			}
			
			if($this->redirects->handleLinuxUserRedirect()) {
				return FALSE;
			}
			
			if($this->redirects->handleMultipleSlashesRedirect()) {
				return FALSE;
			}

			if($this->redirects->handleForceCanonicalLinkRedirect()) {
				return FALSE;
			}

			if($this->redirects->handleUndesirableParameters()) {
				return FALSE;
			}
			
			if($this->redirects->handleCopyPasteErrorRedirect()) {
				return FALSE;
			}
			
			if($this->redirects->handleBadLinkRedirect()) {
				return FALSE;
			}
			
			#if($this->handleImageRedirect()) {
			#	return FALSE;
			#}
			
					// end redirects
			
			$this->HandleRequest_ServeContent();
			
			return $this->HandleRequest_EndRequest();
		}
		
		public function HandleRequest_ServeContent() {
			$this->before_content = FALSE;

			if($this->handle404Image()) {
				return TRUE;
			}
			
			if($this->handleSrvLocalFiles()) {
				return TRUE;
			}
			
				/*
					Ask whether the path resolves before loading anything that
					would render it.  A path that names nothing is answered by
					the chain below, and there is no reason for it to have
					constructed a format and a script first.
				*/

			if($this->EntryPathResolves() || $this->RepairEntryPath()) {
				if($this->HandleRequest_Content()) {
					return TRUE;
				}
			}
			
			ggreq('classes/Networking/Error404.php');
			if($this->isScriptImage()) {
				$this->HandleRequest_Error_404();	# BT: FIXME, special error 404 for images?
			} else {
				ggreq('classes/Networking/Error404Redirect.php');
				$this->error404redirect = new Error404Redirect([
					'handler'=>$this,
				]);
				if(!$this->redirects->handleReservedCodeRedirect()) {
					if(!$this->redirects->handleMatchingCodeRedirect()) {
						if(!$this->redirects->handleScriptRedirect()) {
							if(!$this->redirects->handleMisplacedScriptRedirect()) {
								$this->HandleRequest_Error_404();
							}
						}
					}
				}
			}
			
			return TRUE;
		}
		
		/*
			Does this path name a real walk through the entry graph?

			HandleRequest_Content builds a format object and a script object before
			it finds out, and both pull in class files -- AbstractBaseFormat, the
			format, view, base_format and the traits.  A request for a path that
			names nothing paid for all of it and then answered 404.  On 3 September
			2026 the majority of traffic to this host was exactly that.

			The question is cheap to ask first.  ORM is already in memory --
			StandardLibraries requires it before this class is constructed -- its
			constructor wants nothing but the handler, and GetRecordTree is row
			cached.  So this costs one query, often none, and loads nothing.

			The test is ValidateOrm's, because it is the same question: every
			segment of the path must have resolved to a record.  A shorter answer
			than the path means some segment named nothing.

			Only entry walks are asked.  The front page has no segments, and
			style.php, sitemap.php, robots.php and search.php are not paths through
			the graph at all; all of them answer TRUE and carry on untouched.
		*/

		public function EntryPathResolves() {
			if(!is_array($this->object_list) || (count($this->object_list) === 0)) {
				return TRUE;		# the front page names no entry
			}

			if($this->script_name !== 'view.php') {
				return TRUE;		# not a walk through the entry graph
			}

			if(!$this->EntryPathRequired()) {
				return TRUE;		# this site's paths name something other than entries
			}

			if(!$this->db_access) {
				return TRUE;		# nothing to ask; let the old path answer
			}

			if(!$this->orm) {
				$this->orm = new ORM(['handler'=>$this]);
			}

			$this->resolved_record_list = $this->orm->GetRecordTree([
				'codelist'=>$this->object_list,
				'availabilitylimit'=>1,
			]);

			if(!is_array($this->resolved_record_list)) {
				return FALSE;
			}

			return (count($this->object_list) === count($this->resolved_record_list));
		}

		/*
			Whether this site's view.php paths are walks through the entry graph
			at all.

			Every site's are bar wordweight's.  /funerate/ there is a word from
			alldictionaries, which display_wordweight looks up for itself, and
			names no entry.  Asked of EntryPathResolves it answered 404, and did
			so for every word on the site from 3 September 2026 until this
			existed.

			Script-level AbstractGlobals config, in the shape Dictionary_enabled
			already uses.  Absent config or an absent method means required,
			which is the behaviour before this existed.
		*/

		public function EntryPathRequired() {
			if(!isset($this->abstractglobals->script)) {
				return TRUE;
			}

			if(!is_object($this->abstractglobals->script)) {
				return TRUE;
			}

			if(!method_exists($this->abstractglobals->script, 'EntryPath_required')) {
				return TRUE;
			}

			return $this->abstractglobals->script->EntryPath_required([
				'action'=>$this->desired_action,
			]);
		}

		/*
			A path that named nothing, corrected and answered rather than
			redirected.

			This can only run where EntryPathResolves has already said no,
			which is the one place in the request where the corrections are
			known and nothing has been loaded to render with.  So the request
			is rewritten and carried forward -- the chain is never re-entered,
			no file is required twice, and the invariant every ggreq in this
			codebase rests on is untouched.

			The handlers are asked what they would have redirected to rather
			than allowed to send it.  All three want only db_access and
			script_name, both of which exist long before a format or a script
			does.

			handleScriptRedirect is deliberately absent: it reads
			$this->script->script->redirect_script, and there is no script
			object here yet.  It keeps its redirect, below, where there is.
		*/

		public function RepairEntryPath() {
			while($this->repair_count < 3) {
				if($this->RepairEntryPath_Once()) {
					return TRUE;
				}

				if(strlen($this->redirect_url)) {
					return FALSE;		# a correction we may not make ourselves
				}

				if(!$this->last_repair_changed) {
					return FALSE;		# nothing left to correct
				}
			}

			return FALSE;
		}

		/*
			One correction.  '/x/view.php' becomes '/x/', which on the next
			pass becomes '/parent/x/', which resolves.  So the caller keeps
			asking while something is still changing, up to three times.
		*/

		public function RepairEntryPath_Once() {
			$this->last_repair_changed = FALSE;

			$this->collect_redirect = TRUE;
			$this->redirect_url = '';

			$this->redirects->handleReservedCodeRedirect();

			if(!$this->redirect_url) {
				$this->redirects->handleMatchingCodeRedirect();
			}

			if(!$this->redirect_url) {
				$this->redirects->handleMisplacedScriptRedirect();
			}

			$this->collect_redirect = FALSE;

			$target = $this->redirect_url;

			if(strlen($target) === 0) {
				return FALSE;
			}

				/*
					Off this host, or a scheme change, or not a GET: those are
					redirects for good reasons and are left as redirects.  The
					url is put back so the chain below sends it.
				*/

			$this->redirect_url = '';

			if(!$this->redirects->RepairInsteadOfRedirect(['url'=>$target])) {
				$this->redirect_url = $target;

				return FALSE;
			}

			$this->last_repair_changed = TRUE;

			return $this->EntryPathResolves();
		}

		public function HandleRequest_EndRequest() {
			$this->AdminTools();
			$this->MySQLDebugging();
			$this->RecordUserStatistics();
			$this->SendBrowserCacheHeader();
			
			return TRUE;
		}

		/*
			Tell browsers and Cloudflare that a page may be kept.

			Only what PageCache would write to disk is marked public: a GET with
			no query string and no session cookie, answered 200, setting no
			cookie, whole and long enough.  PageCache is asked that question in
			its own words, with the page in hand, because this runs while the
			whole page is still in index.php's output buffer and nothing has
			been sent.

			The meta tags StartHTML_Head_SearchEngineData prints -- cache-control,
			pragma, expires -- never did this.  No browser honours cache
			instructions written into markup, and neither nginx nor Cloudflare
			reads the HTML.  Cache hits never reach PHP at all, so nginx sends
			the same header for those.

			Nothing here may break a page, so every failure is a quiet FALSE.
		*/

		public function SendBrowserCacheHeader() {
			try {
				if(headers_sent() || (ob_get_level() < 1)) {
					return FALSE;
				}

				if(!class_exists('PageCache')) {
					ggreq('classes/Cache/PageCache.php');
				}

				$page_cache = new PageCache(['handler'=>$this]);

				if(!$page_cache->IsCacheable(['output'=>ob_get_contents()])) {
					return FALSE;
				}

				header($page_cache->BrowserCacheHeader());
			} catch (Throwable $exception) {
				return FALSE;
			}

			return TRUE;
		}
		
		public function AdminTools() {
			if($this->authentication->CheckAuthenticationForCurrentObject_IsAdmin()) {
				
				$action = $_GET['admin_action'];
				
				if($action) {
				ggreq('classes/Admin/AdminTools.php');
				$admintools = new AdminTools();
				print('<PRE>');
				$admintools->$action();
				print('</PRE>');
				}
			}
			
			return TRUE;
		}
		
		public function MySQLDebugging() {
			if($this->db_access->Upgraded()) {
				$this->db_access->ShowQueries();
			}
			
			return TRUE;
		}
		
		public function handleSrvLocalFiles() {
			$file = $this->SrvLocalFile(['requesturi'=>$_SERVER['REQUEST_URI']]);
			
			if(!$file) {
				return FALSE;
			}
			
			foreach($file['headers'] as $header) {
				header($header);
			}
			
			return (bool)readfile($file['location']);
		}
			
			// SrvLocalFile()
			// Tests: HandlerTest::testSrvLocalFile()
			// Test file: tests/src/classes/Networking/HandlerTest.php
			/*
				The files a site keeps beside its images -- favicons, manifests,
				search engines' verification pages, the word-game demos -- and
				the headers to send with each.  They went out through
				print(file_get_contents()) with no Content-Type, so PHP called
				everything text/html, and the name came from the whole
				REQUEST_URI, query string and all.
				
				Anything under image/ is Image::ImageRequest()'s, which serves
				only images; never .php, whose source would be printed; SVG
				sandboxed, since it is XML and can carry a script; nosniff on
				everything.
			*/
		
		public function SrvLocalFile($args) {
			$path = ltrim((string)parse_url((string)$args['requesturi'], PHP_URL_PATH), '/');
			
			if($path === '' || str_starts_with(strtolower($path), 'image/')) {
				return FALSE;
			}
			
			$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
			
			if(in_array($extension, ['php', 'phtml', 'phar', 'inc'], TRUE)) {
				return FALSE;
			}
			
			if(!data_isfile($path, $this)) {
				return FALSE;
			}
			
			if(!class_exists('MIMEType')) {
				ggreq('classes/Networking/MIMEType.php');		# plain require, so once
			}
			$mimetypes = (new MIMEType(['handler'=>$this]))->GetMIMETypeCodes();
			
			$mimetype = $mimetypes[$extension] ?? 'application/octet-stream';
			
			$headers = [
				'Content-Type: ' . $mimetype,
				'X-Content-Type-Options: nosniff',
			];
			
			if($mimetype === 'image/svg+xml') {
				$headers[] = 'Content-Security-Policy: sandbox';
			}
			
			return [
				'location'=>GGCMS_DATA_DIR . $this->domain->primary_domain_lowercased . '/www/' . $path,
				'mimetype'=>$mimetype,
				'headers'=>$headers,
			];
		}
		
		public function handle404Image() {
			#return FALSE;	 // hrm, is this a img src=??? problem?
			$url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			
			
			
			if(count($url_pieces) > 2) {
				$first_piece = $url_pieces[1];
				
				if($first_piece === 'image') {
	#				ggreq();
					$this->script_format = 'Image';
					
					$this->HandleRequest_Content_Format_GetFormatObject();
					
					$this->script = new $this->script_format(['handler'=>$this]);
						
						/*
							What Display() answers.  This returned TRUE whether or
							not an image was sent, so a missing or refused one was
							an empty 200 and never reached the image 404 below.
						*/
					
					return (bool)$this->script->Display();
				}
			}
			return FALSE;
		}
		
		public function isScriptImage() {
			$extension = strtolower(pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION));
			$image_extension_hash = $this->imageFileExtensionsHash();
			
			if($image_extension_hash[$extension]) {
				return TRUE;	# we never want to redirect image 404's the way to redirect entry 404's
			}
			
			return FALSE;
		}
		
		public function imageFileExtensionsHash() {
			$image_file_extensions = $this->imageFileExtensions();
			$image_file_extensions_hash = [];
			
			foreach($image_file_extensions as $image_file_extension) {
				$image_file_extensions_hash[$image_file_extension] = TRUE;
			}
			
			return $image_file_extensions_hash;
		}
		
		public function imageFileExtensions() {
			return [
				'apng',
				'avif',
				'bmp',
				'cur',
				'gif',
				'ico',
				'jfif',
				'jpeg',
				'jpg',
				'pjp',
				'pjpeg',
				'png',
				'svg',
				'tif',
				'tiff',
				'webp',
			];
		}
		
		public function ValidateReferrals() {
			if(!$this->domain->ValidateReferringWebsite()) {
				print('Error 403 - You done been smote.');
				return FALSE;
			}
			
			return TRUE;
		}
		
			/*
				humanbeacon.js posts here once a visitor first moves, scrolls,
				types or touches.  It is answered first, before the redirects
				and before anything that would load a script or open the
				database: it is a statistic and nothing more.

				Returning TRUE ends the request with an empty 204, so
				HandleRequest_EndRequest never files it as a page request
				as well.
			*/

		public function handleHumanBeacon() {
			if($_SERVER['REQUEST_METHOD'] !== 'POST') {
				return FALSE;
			}

			if(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/humanbeacon.php') {
				return FALSE;
			}

			ggreq('classes/Networking/UserTracking.php');

			$user_tracking = new UserTracking($this->getArgs());
			$user_tracking->RecordHumanBeacon();

			http_response_code(204);

			return TRUE;
		}

		public function RecordUserStatistics() {
			if($this->globals->EnableStats() || $this->globals->EnableStats_LogExcessiveMemoryUse()) {
				if($this->globals->EnableStats_Log404Pages() || !$this->error_404) {
					ggreq('classes/Networking/UserTracking.php');
					
					$this->user_tracking = new UserTracking($this->getArgs());
					
					$this->user_tracking->RecordUserTracking();
				}
			}
			
			return TRUE;
		}
		
		public function HandleRequest_Content() {
			$client_location = GGCMS_DIR . $this->domain->primary_domain_lowercased . $_SERVER['SCRIPT_URL'];
			
			$shared_location = GGCMS_DIR . GGCMS_REFERENCE_DOMAIN . $_SERVER['SCRIPT_URL'];
			
			if(!is_file($client_location) && is_file($shared_location)) {
				ggreq('classes/Networking/MIMEType.php');
				
				$mimetype = new MIMEType($this->getArgs());
				$mimetypes = $mimetype->GetMIMETypeCodes();
				
				$desired_content_header = $mimetypes[$this->script_extension];
				
				if($desired_content_header) {
					$header_text = 'Content-type: ' . $desired_content_header . '; charset=utf-8';
					header($header_text);
				}
				
				if($desired_content_header == 'text/html') {
					return require($shared_location);
				} else {
					return readfile($shared_location);
				}
			}
			
			if(!is_file($this->script_location) || !$this->script_format) {
				return FALSE;
			}
			
			$this->HandleRequest_Content_Format_GetFormatObject();
			$this->script = $this->HandleRequest_Content_Format_InstantiateFormatObject();
			
			if($this->script->CanAccess()) {
				return $this->HandleRequest_Content_Format();
			}
			
			return FALSE;
		}
		
		public function HandleRequest_Content_Format() {
			$this->CheckSecurity();
			
			$this->Construct_UpgradeDBAccess();
			
			#	print("BT: ACCESS?");
			if($this->access) {
			#	print("BT: ACCESS!");
				if(method_exists($this->script->script, $this->desired_action)) {
		#			print("BT: METH!" . $this->desired_action . "|");
					$desired_action = $this->desired_action;
					$response = $this->script->Display();
					return $response;		# BT: FIXME ?  use $desired_action var pls; NO!
				}
			} else {
				if($this->authentication->redirect) {
						# handle security-triggered redirect
					$other_script_args = $this->HandleRequest_Content_Format_InstantiateFormatObject_PartialArgs();
					$other_script_args['redirect'] = $this->script->redirect_object;
					return ($this->authentication->RedirectToNewURL($other_script_args));
				}
			}
			
			return FALSE;
		}
		
			/*
				Twice in one request now and then: handle404Image() loads the
				Image format, and when it serves nothing -- a .php name under
				/image/ that is also a script's name -- the request goes on to
				load a format again.  ggreq() is plain require, and a second
				AbstractBaseFormat was a fatal.
			*/
		
		public function HandleRequest_Content_Format_GetFormatObject() {
			if(!class_exists('AbstractBaseFormat', FALSE)) {
				ggreq('classes/Format/Base/AbstractBaseFormat.php');
			}
			
			if(class_exists($this->script_format, FALSE)) {
				return TRUE;
			}

			return ggreq('classes/Format/' . $this->script_format . '.php');
		}
		
		public function HandleRequest_Content_Format_InstantiateFormatObject() {
			$script_format_args = $this->HandleRequest_Content_Format_InstantiateFormatObject_Args();
			
			return (new $this->script_format($script_format_args));
		}
		
		public function HandleRequest_Content_Format_InstantiateFormatObject_Args() {
			return [
				'handler'=>$this,
				'firstcall'=>1,
				'authentication'=>$this->authentication,
				'version'=>$this->version,
				'versionobject'=>$this->version_object,
				'cleanser'=>$this->cleanser,
				'query'=>$this->query,
				'dbaccess'=>$this->db_access,
				'globals'=>$this->globals,
				'domain'=>$this->domain,
				'time'=>$this->time,
				'cookie'=>$this->cookie,
				'language'=>$this->language,
				'desiredscript'=>$this->desired_script,
				'desiredaction'=>$this->desired_action,
				'dictionary'=>$this->dictionary,
				'objectlist'=>$this->object_list,
				'objectcode'=>$this->object_code,
				'objectparent'=>$this->object_parent,
				'scriptname'=>$this->script_name,
				'scriptfile'=>$this->script_file,
				'scriptclassname'=>$this->script_classname,
				'scriptextension'=>$this->script_extension,
				'scriptformat'=>$this->script_format,
				'scriptformatlower'=>$this->script_format_lower,
				'scriptlocation'=>$this->script_location,
				'googleapi'=>$this->google_api,
			];
		}
		
		public function HandleRequest_Content_Format_InstantiateFormatObject_PartialArgs() {
			return [
				'handler'=>$this,
				'firstcall'=>0,
				'cleanser'=>$this->cleanser,
				'dbaccess'=>$this->db_access,
				'language'=>$this->language,
				'globals'=>$this->globals,
				'domain'=>$this->domain,
				'objectcode'=>$this->object_code,
				'objectlist'=>$this->object_list,
				'scriptclassname'=>$this->script_classname,
				'scriptextension'=>$this->script_extension,
				'scriptformat'=>$this->script_format,
			];
		}
		
		public function HandleRequest_Error_404() {
			$this->error_404 = TRUE;

				/*
					Nothing in this codebase set a status code here, so every
					dead URL on every site answered 200 with an apology page.

					A crawler that receives 200 has been told the URL is real and
					comes back for it, forever, and the page cache will not store
					an error page -- so each visit is a full render.  Measured on
					31 August 2026: /w.php, a WordPress probe for a file that has
					never existed, cost 30.6 seconds of work and returned 200.
					There are thousands of such requests a day.

					Search engines call this a soft 404 and index the apology.

					The redirect handlers upstream of this method have already
					had their chance, so anything arriving here is a genuine dead
					end and can say so.
				*/

			http_response_code(404);

			$error_404 = new Error404($this->getArgs());

			$error_404->Display([]);
			
			$this->issue_logging->createLog([
				'issuetype'=>'404',
				'description'=>'404 URL',
			]);
			
			return TRUE;
		}
	}

?>
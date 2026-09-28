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
		public $script_handler;
		public $file_handler;
		public $entry_path_handler;
		public $content_handler;
		public function __construct() {
			
			$this->Construct_Redirects();
			$this->Construct_ScriptHandler();
			$this->Construct_StageHandlers();
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
			$this->script_handler->Construct_ScriptName();
			$this->script_handler->Construct_ScriptFileAndExtension();
			$this->script_handler->Construct_ScriptClassname();
			$this->script_handler->Construct_ScriptFormat();
			$this->Construct_Globals();
			$this->Construct_SiteLanguages();
			$this->Construct_ProductionSite();
			$this->Construct_DBAccess();
			$this->Construct_Dictionaries();
			$this->Construct_PresetAuthentication();
			
			$this->redirects->CheckPermalinkRedirect();
			
			if($this->script_name) {
				$this->script_handler->Construct_ScriptLocation();
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
			
			/*
				The script stage -- which script, file, class, format and
				extension answer this request -- lives in HandlerScript.php,
				and keeps nothing of its own either.
			*/
		
		public function Construct_ScriptHandler() {
			if(!class_exists('HandlerScript', FALSE)) {
				ggreq('classes/Networking/Handler/HandlerScript.php');
			}
			
			return $this->script_handler = new HandlerScript($this->getArgs());
		}
			
			/*
				The last three stages of a request, each in its own class and
				none keeping anything of its own: files answered from disk,
				whether the path walks the entry graph, and rendering.
			*/
		
		public function Construct_StageHandlers() {
			$stages = [
				'file_handler'=>'HandlerFiles',
				'entry_path_handler'=>'HandlerEntryPath',
				'content_handler'=>'HandlerContent',
			];
			
			foreach($stages as $property => $classname) {
				if(!class_exists($classname, FALSE)) {
					ggreq('classes/Networking/Handler/' . $classname . '.php');
				}
				
				$this->$property = new $classname($this->getArgs());
			}
			
			return TRUE;
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

			if($this->file_handler->handle404Image()) {
				return TRUE;
			}
			
			if($this->file_handler->handleSrvLocalFiles()) {
				return TRUE;
			}
			
				/*
					Ask whether the path resolves before loading anything that
					would render it.  A path that names nothing is answered by
					the chain below, and there is no reason for it to have
					constructed a format and a script first.
				*/

			if($this->entry_path_handler->EntryPathResolves() || $this->entry_path_handler->RepairEntryPath()) {
				if($this->content_handler->HandleRequest_Content()) {
					return TRUE;
				}
			}
			
			ggreq('classes/Networking/Error404.php');
			if($this->file_handler->isScriptImage()) {
				$this->content_handler->HandleRequest_Error_404();	# BT: FIXME, special error 404 for images?
			} else {
				ggreq('classes/Networking/Error404Redirect.php');
				$this->error404redirect = new Error404Redirect([
					'handler'=>$this,
				]);
				if(!$this->redirects->handleReservedCodeRedirect()) {
					if(!$this->redirects->handleMatchingCodeRedirect()) {
						if(!$this->redirects->handleScriptRedirect()) {
							if(!$this->redirects->handleMisplacedScriptRedirect()) {
								$this->content_handler->HandleRequest_Error_404();
							}
						}
					}
				}
			}
			
			return TRUE;
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
	}

?>
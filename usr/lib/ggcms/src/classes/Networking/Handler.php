<?php
	
	class Handler {
		use ReverseDNSNotation;
		public function __construct() {
			
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
			$this->Construct_ProductionSite();
			$this->Construct_DBAccess();
			$this->Construct_Dictionaries();
			$this->Construct_PresetAuthentication();
			
			$this->CheckPermalinkRedirect();
			
			if($this->script_name) {
				$this->Construct_ScriptLocation();
				$this->Construct_SocialMedia();
			}
			
			return TRUE;
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
		
		public function getArgs() {
			return [
				'handler'=>$this,
			];
		}
		
		public function ValidateSecurity() {
			setlocale(LC_ALL,'en_US.UTF-8');
			ini_set('session.referer_check', 'TRUE');	# HOLY GOD, WHY WOULD YOU NOT?
			
			return TRUE;
		}
		
		public function __destruct() {
			return $this->db_access->DBEnd();
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
		
		public function Construct_Globals() {
			confreq('clonefrom.php');
			$base_globals_classname = $this->ReverseDomainName(['domain'=>$this->domain->primary_domain_lowercased]) . '.php';
			
			$client_globals_location = $base_globals_classname;
			
			if(conf_isfile($client_globals_location)) {
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
		
		public function Construct_ProductionSite() {
			if(!$this->globals->isProductionSite()) {
				ini_set('display_errors', 1);
				ini_set('display_startup_errors', 1);
			}
			return TRUE;
		}
		
			// Query String Repair
			// -----------------------------------------------

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

		public function QueryStringNeedsRepair($args) {
			$query_string = $args['querystring'];

			if(strlen($query_string) === 0) {
				return FALSE;
			}

			return (strpos($query_string, '?') !== FALSE);
		}

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
			if(!property_exists($this->abstractglobals, 'script')) {
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
			
			return TRUE;#$this->db_access->DBStart();
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
		
		/*
			Repairing a URL rewrites the request and hands it back to this chain,
			so the chain has to be re-runnable.  Three passes is generous: the
			deepest real case is a permalink that resolves to a path which then
			wants its trailing view.php stripped.
		*/

		public function HandleRequest() {
			$result = $this->HandleRequest_Chain();

			while($this->request_repaired) {
				$this->request_repaired = FALSE;

				$result = $this->HandleRequest_Chain();
			}

			return $result;
		}

		public function HandleRequest_Chain() {
			if($this->SecureRequired()) {
				return $this->SecureRedirect();
			}
			
					//start redirects
			
			if(!$this->ValidateReferrals()) {
				return FALSE;
			}
			
			if($this->handleMailTo()) {
				return FALSE;
			}
			
			if($this->handleGitRedirect()) {
				return FALSE;
			}
			
			if($this->handleLinuxUserRedirect()) {
				return FALSE;
			}
			
			if($this->handleMultipleSlashesRedirect()) {
				return FALSE;
			}

			if($this->handleForceCanonicalLinkRedirect()) {
				return FALSE;
			}

			if($this->handleUndesirableParameters()) {
				return FALSE;
			}
			
			if($this->handleCopyPasteErrorRedirect()) {
				return FALSE;
			}
			
			if($this->handleBadLinkRedirect()) {
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

			if($this->EntryPathResolves()) {
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
				if(!$this->handleReservedCodeRedirect()) {
					if(!$this->handleMatchingCodeRedirect()) {
						if(!$this->handleScriptRedirect()) {
							if(!$this->handleMisplacedScriptRedirect()) {
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

		public function HandleRequest_EndRequest() {
			$this->AdminTools();
			$this->MySQLDebugging();
			$this->RecordUserStatistics();
			
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
			$filename = $_SERVER['REQUEST_URI'];
			
			if(strlen($filename) !== 0) {
				$good_filename = mb_substr($filename, 1);
				
				if(data_isfile($good_filename, $this)) {
					data_reqfile($good_filename, $this);
					
					return TRUE;
				}
			}
			
			return FALSE;
		}
		
		public function handle404Image() {
			#return FALSE;	 // hrm, is this a img src=??? problem?
			$url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			
			
			
			if(count($url_pieces) > 2) {
				$last_piece = $url_pieces[-1];
				$first_piece = $url_pieces[1];
				
				if($first_piece === 'image') {
	#				ggreq();
					$this->script_format = 'Image';
					
					$this->HandleRequest_Content_Format_GetFormatObject();
					
					$this->script = new $this->script_format(['handler'=>$this]);
					
				#	$this->script = $this->HandleRequest_Content_Format_InstantiateFormatObject();
					if(!$this->script->Display()) {
					
					#	return $this->imageRedirect();
					}
					
					return TRUE;
				}
			}
			return FALSE;
		}
		
		public function handleImageRedirect() {
			$url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			
			if(count($url_pieces) > 2) {
				$last_piece = $url_pieces[-1];
				$first_piece = $url_pieces[1];
				
				if($first_piece === 'image' && strlen($last_piece) === 0) {	# are you searching for a directory like example.com/image/blahblahblablhablh/1/2/3/ ?  Then come along!
					return $this->imageRedirect();
				}
			}
			
			return FALSE;
		}
		
		public function imageRedirect() {
			$this->redirect_url = '/image.php';
			$this->handleRedirect();
			return TRUE;
		}
		
		public function handleMailTo() {
			$possible_mailto = substr($_SERVER['REQUEST_URI'], 0, 8);
			
			if($possible_mailto === '/mailto:') {
				$good_mailto = substr($_SERVER['REQUEST_URI'], 1);
				
				$this->redirect_url = $good_mailto;
				$this->handleRedirect();
				
				return TRUE;
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
		
		public function handleLinuxUserRedirect() {
			$protocol_check_2char = mb_substr($_SERVER['REQUEST_URI'], 0, 2);
			
			if($protocol_check_2char === '/~') {
				$redirect_url = '/';
				
				$this->redirect_url = $redirect_url;
				$this->handleRedirect();
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleCopyPasteErrorRedirect() {
			$protocol_check_4char = mb_substr($_SERVER['REQUEST_URI'], 0, 4);
			$protocol_check_5char = mb_substr($_SERVER['REQUEST_URI'], 0, 5);
			
			if($protocol_check_4char === '/ftp' || $protocol_check_5char === '/http') {
				$redirect_url = '/';
				
				$this->redirect_url = $redirect_url;
				$this->handleRedirect();
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleGitRedirect() {
			$new_url_pieces = explode('/', mb_strtolower($_SERVER['REQUEST_URI'], 'utf-8'));
			
			if(strlen($new_url_pieces[1]) === 0) {
				return FALSE;
			}
			
			$possible_git_piece = $new_url_pieces[1];
			
			$git_hash = [
				'git'=>TRUE,
				'.git'=>TRUE,
			];
			
			if(!$git_hash[$possible_git_piece]) {
				return FALSE;
			}
			
			$redirect_url = $this->version->GetOpenSourceURL();
			$this->redirect_url = $redirect_url;
			$this->handleRedirect();
			
			return TRUE;
		}
		
		/*
			REQUEST_URI is a path for an ordinary browser request, but a proxy
			sending an absolute-form request -- "GET http://host/path HTTP/1.1",
			which is legal under RFC 7230 -- puts the WHOLE URL in it.

			Concatenating that onto the domain produced
			http://www.example.comhttp://www.example.com/path, the client
			followed it, and every hop prefixed the domain again.  Each hop was
			a brand-new URL, so nothing could ever be cached and every one cost
			a full render.  95% of all traffic to this host was that loop.

			This returns a path, always, whichever form arrived.
		*/

		public function RequestPath() {
			$request_uri = $_SERVER['REQUEST_URI'];

			if(preg_match('#\A[a-zA-Z][a-zA-Z0-9+.-]*://#', $request_uri)) {
				$path = parse_url($request_uri, PHP_URL_PATH);
				$query = parse_url($request_uri, PHP_URL_QUERY);

				$request_uri = strlen($path) ? $path : '/';

				if(strlen($query)) {
					$request_uri .= '?' . $query;
				}
			}

			/*
				A URL may carry only one '?'.  Every later one is a separator
				that should have been '&' -- something appended a parameter to
				a URL that already had a query string, producing

					view.pdf?mobilefriendly=1?mobilefriendly=1?action=...

				which was a steady source of 500s.  Repairing it here means a
				malformed link is understood rather than refused, which is what
				this class is for.
			*/

			$query_pieces = explode('?', $request_uri);

			if(count($query_pieces) > 2) {
				$request_uri = $query_pieces[0] . '?' . implode('&', array_slice($query_pieces, 1));
			}

			if(strlen($request_uri) === 0 || $request_uri[0] !== '/') {
				$request_uri = '/' . $request_uri;
			}

			return $request_uri;
		}

		public function handleUndesirableParameters() {
			if($_GET['stopredirect']) {		# already redirected once; never chain
				return FALSE;
			}

			if($_GET['fbclid']) {
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url = 'https://';
				} else {
					$redirect_url = 'http://';
				}
				
				$redirect_url .= $this->domain->primary_domain_lowercased;
				
				$redirect_url .= $this->RequestPath();
				
				$redirect_url = preg_replace('/fbclid=[A-Za-z0-9_%-]+[\&]*/', '', $redirect_url);
				$redirect_url = preg_replace('/\?$/', '', $redirect_url);
				
				#print($redirect_url);
				$this->redirect_url = $redirect_url;
				$this->handleRedirect();
				return TRUE;
			}
			
			if(preg_match('/\?$/', $_SERVER['REQUEST_URI'])) {
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url = 'https://';
				} else {
					$redirect_url = 'http://';
				}
				
				$redirect_url .= $this->domain->primary_domain_lowercased;
				
				$redirect_url .= $this->RequestPath();
				
				$redirect_url = preg_replace('/\?$/', '', $redirect_url);
				
				#print($redirect_url);
				$this->redirect_url = $redirect_url;
				$this->handleRedirect();
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleMatchingCodeRedirect() {
			$path = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_FILENAME);
			
			$new_url_pieces = explode('/', $path);
			$last_piece = array_pop($new_url_pieces);
			$current_code = array_pop($new_url_pieces);
			
			$full_code_pieces = explode('/', $path);
			$fullest_code = implode('/', $full_code_pieces);
			unset($full_code_pieces[-1]);
			$near_fullest_code = implode('/', $full_code_pieces);
			
			if($this->handleCodeRedirect(['code'=>$fullest_code])) {
				return TRUE;
			}
			
			if($this->handleCodeRedirect(['code'=>$near_fullest_code])) {
				return TRUE;
			}
			
			if($this->handleCodeRedirect(['code'=>$last_piece])) {
				return TRUE;
			}
			
			if($this->handleCodeRedirect(['code'=>$current_code])) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleCodeRedirect($args) {
			$code = $args['code'];
			
			if(strlen($code) !== 0) {
				$sql = 'SELECT Assignment.id, Assignment.Childid FROM Entry ';
				$sql .= 'JOIN Assignment ON Entry.id = Assignment.Childid ';
				$sql .= 'WHERE Entry.Code = ? ';
				
				$sql_args = [$code];
				
				$assignment = $this->db_access->RunQuery(['sql'=>$sql, 'args'=>$sql_args]);
				
				if($assignment && $assignment[0] && $assignment[0]['id']) {
					$permalink_id = (int)$assignment[0]['id'];
					$redirect_url = $this->BuildRedirect(['permalink_id'=>$permalink_id, 'assignment'=>$assignment[0]]);
					if($redirect_url) {
						$this->redirect_url = $redirect_url;
						$this->handleRedirect();
						return TRUE;
					}
				}
			}
			
			return FALSE;
		}
		
		public function handleMisplacedScriptRedirect() {
			$new_url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			$possible_script = array_pop($new_url_pieces);
			
			if($possible_script === '') {
				$possible_script = array_pop($new_url_pieces);
			}
			
			$possible_script = parse_url($possible_script, PHP_URL_PATH);
			
			$filename = pathinfo($possible_script, PATHINFO_FILENAME); // returns 'filename' for 'filename.md'
			
			if(is_file(GGCMS_DIR . '/scripts/' . $filename . '.php') || is_file(GGCMS_DIR . '/scripts/' . $possible_script)) {
				if($possible_script === 'view.php') {
					$possible_script = '';
				}
				
				$possible_script_extension = pathinfo($this->script_name, PATHINFO_EXTENSION);
				if(strlen($possible_script_extension) === 0) {
					$possible_script .= '.php';
				}
				
				$new_url = implode('/', $new_url_pieces) . '/' . $possible_script;
				
				if($new_url === $_SERVER['REQUEST_URI']) {
					$possible_script = basename($new_url, '.' . $possible_script_extension);
					$new_url = implode('/', $new_url_pieces) . '/' . $possible_script . '.php';
			#		die("BT:!" . $new_url);
				}
				
				if($new_url === $_SERVER['REQUEST_URI']) {
					return FALSE;
				}
				$this->redirect_url = $new_url;
	#			die($this->redirect_url);
				$this->handleRedirect();
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleScriptRedirect() {
			if($this->script->script->redirect_script) {
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url .= 'https://';
				} else {
					$redirect_url .= 'http://';
				}
				
				$redirect_url .= $this->domain->primary_domain_lowercased;
				
				if(property_exists($this->script->script, 'redirect_base')) {
					$redirect_url .= $this->script->script->redirect_base;
				} else {
					$new_url_pieces = explode('/', $_SERVER['REQUEST_URI']);
					array_pop($new_url_pieces);
					$redirect_url .= implode('/', $new_url_pieces);
					
					$redirect_url .= '/' . $this->script->script->redirect_script . '.php';
				}
				
				if($this->script->script->redirect_action) {
					$redirect_url .= '?action=' . $this->script->script->redirect_action;
				}
				
				if($this->script->script->redirect_query) {
					if($this->script->script->redirect_action) {
						$redirect_url .= '&';
					} else {
						$redirect_url .= '?';
					}
					$redirect_url .= $this->script->script->redirect_query;
				}
				
				$this->redirect_url = $redirect_url;
				
			#	print("BT: REDIR!");
			#	print($this->redirect_url);
			#	die("soy");
				
				$this->handleRedirect();
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleMultipleSlashesRedirect() {
			/*
				Test the normalised path, not REQUEST_URI.  An absolute-form
				request URI always contains "//" -- inside "http://" -- so this
				guard passed for every proxy request, the handler rebuilt the
				identical URL, and returned a 302 to the address already being
				requested.  The client asked again, forever.
			*/

			if(!preg_match("/\/\//", $this->RequestPath())) {
				return FALSE;
			}
			
			$redirect_url = '';
			
			if($_SERVER['HTTPS'] === 'on') {
				$redirect_url .= 'https://';
			} else {
				$redirect_url .= 'http://';
			}
			
			$redirect_url .= $this->domain->primary_domain_lowercased;
			
			$new_dir = $this->RequestPath();
			$new_dir = preg_replace("/[\/]+/", '/', $new_dir);
			
			$redirect_url .= $new_dir;
			
			$this->redirect_url = $redirect_url;
			
		#	print("REDIRE!" . $redirect_url . "|");
		#	die("BT:");
			
			$this->handleRedirect();
			
			return TRUE;
		}
		
			/*
				A path is an entry-graph walk rather than a directory, so
				/a/b/c/convertspelling.php and /convertspelling.php run the same
				script.  For a script that reads nothing out of the walk, every
				depth is one page wearing an unlimited number of URLs -- duplicate
				content to a crawler, and a separate file in the page cache for
				every path a crawler invents.

				ForceCanonicalLink() names the directory the script's real URL
				lives in, '/' being the document root.  Absent means disabled,
				which is every script until its own config says otherwise, so
				this costs nothing anywhere it has not been turned on.

				302 rather than 301 on purpose.  A 301 is cached by the browser
				indefinitely and a wrong value cannot be withdrawn afterwards.

				Docs/Triage.md, "Canonical-path normalisation", carries the
				warning that goes with this: the page cache must hold the
				canonical form only.  Caching both forms is what produced the
				unbounded-unique-URL outage.  A path already sitting in the cache
				tree is also served by .htaccess before PHP starts, so turning
				this on for a script does nothing for the URLs already cached
				under it until those are purged.
			*/

		public function handleForceCanonicalLinkRedirect() {
			$canonical_directory = $this->ForceCanonicalLink_Directory();

			if(!strlen($canonical_directory)) {
				return FALSE;
			}

			if($_SERVER['REQUEST_METHOD'] !== 'GET') {
				return FALSE;		# a 302 would discard the body
			}

			$request_pieces = explode('?', $this->RequestPath());
			$request_path = $request_pieces[0];
			$request_query = count($request_pieces) > 1 ? $request_pieces[1] : '';

			$path_pieces = explode('/', $request_path);
			$script_segment = $path_pieces[count($path_pieces) - 1];

			if(!strlen($script_segment)) {
				return FALSE;		# a directory URL names no script to move
			}

			$canonical_path = $canonical_directory . $script_segment;

			if($canonical_path === $request_path) {
				return FALSE;
			}

			$redirect_url = '';

			if($_SERVER['HTTPS'] === 'on') {
				$redirect_url .= 'https://';
			} else {
				$redirect_url .= 'http://';
			}

			$redirect_url .= $this->domain->primary_domain_lowercased;
			$redirect_url .= $canonical_path;

			if(strlen($request_query)) {
				$redirect_url .= '?' . $request_query;
			}

			$this->redirect_url = $redirect_url;

				/*
					Returned rather than discarded.  handleRedirect() answers
					FALSE when its own guard finds the target is the address
					already being served, and the caller must then carry on
					serving the page rather than returning a blank response.
				*/

			return $this->handleRedirect();
		}

			/*
				An absent config object, an absent method and an empty string all
				mean disabled.  Construct_Dictionaries_Wanted() is the pattern.

				The whole handler is reachable from config rather than only its
				value: a domain that needs a rule instead of a constant overrides
				ForceCanonicalLink() itself and reads what it needs off the
				handler it is passed.
			*/

		public function ForceCanonicalLink_Directory() {
			if(!property_exists($this->abstractglobals, 'script')) {
				return '';
			}

			if(!is_object($this->abstractglobals->script)) {
				return '';
			}

			if(!method_exists($this->abstractglobals->script, 'ForceCanonicalLink')) {
				return '';
			}

			$canonical_directory = $this->abstractglobals->script->ForceCanonicalLink([
				'handler'=>$this,
			]);

				//  Tested for truth rather than length: a config is at liberty to
				//  answer NULL or FALSE for disabled, and strlen(NULL) is
				//  deprecated in PHP 8.1 and fatal after it.

			if(!$canonical_directory) {
				return '';
			}

				//  A directory, so that the script name appends cleanly to it.

			if(substr($canonical_directory, -1) !== '/') {
				$canonical_directory .= '/';
			}

			return $canonical_directory;
		}

		public function handleBadLinkRedirect() {	// handles, i.e., "website.com/page)" or "website.com/page)."
			if($_GET['stopredirect']) {		// don't allow multiple redirects
				return FALSE;
			}
			
			$trimmed_url = trim(urldecode($_SERVER['REQUEST_URI']));
			
			$last_1_char = mb_substr($trimmed_url, -1, 1);
			$last_2_chars = mb_substr($trimmed_url, -2, 2);
			
			$bad_chars = [
				1=>[
					'.'=>TRUE,
					')'=>TRUE,
					']'=>TRUE,
					'}'=>TRUE,
					'\''=>TRUE,
					'"'=>TRUE,
					'\''=>TRUE,
				],
				2=>[
					'.)'=>TRUE,
					').'=>TRUE,
					'.]'=>TRUE,
					'].'=>TRUE,
					'.}'=>TRUE,
					'}.'=>TRUE,
					'".'=>TRUE,
					'\'.'=>TRUE,
				],
			];
			
			if($bad_chars[1][$last_1_char]) {
				$new_url = substr($trimmed_url, 0, -1);
			}
			
			if($bad_chars[2][$last_2_chars]) {
				$new_url = substr($trimmed_url, 0, -2);
			}
			
			if($bad_chars[1][$last_1_char] || $bad_chars[2][$last_2_chars]) {
				$redirect_url = '';
				
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url .= 'https://';
				} else {
					$redirect_url .= 'http://';
				}
				
				$redirect_url .= $this->domain->primary_domain_lowercased;
				
				$redirect_url .= $this->cleanseURL(['url'=>$new_url]);
				
				$query = parse_url($redirect_url, PHP_URL_QUERY);
				
				// Returns a string if the URL has parameters or NULL if not
				if ($query) {
					$redirect_url .= '&stopredirect=1';
				} else {
					$redirect_url .= '?stopredirect=1';
				}				
				
			#	print($redirect_url);
				
				$this->redirect_url = $redirect_url;
				
				$this->handleRedirect();
				
				return TRUE;
			}
			
			#print($last_chars);
			
			return FALSE;
		}
		
		public function badEndingCharacters() {
			return [
				1=>[
					'.'=>TRUE,
					')'=>TRUE,
					']'=>TRUE,
					'}'=>TRUE,
					'\''=>TRUE,
					'"'=>TRUE,
					'\''=>TRUE,
				],
				2=>[
					'.)'=>TRUE,
					').'=>TRUE,
					'.]'=>TRUE,
					'].'=>TRUE,
					'.}'=>TRUE,
					'}.'=>TRUE,
					'".'=>TRUE,
					'\'.'=>TRUE,
				],
			];
		}

		public function cleanseURL($args) {
			$max_depth = 10;
			
			if(array_key_exists('maxdepth', $args)) {
				$max_depth = $args['maxdepth'];		# recursion protection
				#print("BT: set max depth" . $max_depth . "|\n\n");
			}
			
			$url = $args['url'];
			
			$last_1_char = mb_substr($url, -1, 1);
			$last_2_chars = mb_substr($url, -2, 2);
			
			$bad_chars = $this->badEndingCharacters();
			
			$triggered = FALSE;
			
			$new_url = $url;

			if($bad_chars[1][$last_1_char]) {
				$triggered = TRUE;
				
			#	while($new_url
					# and then something!!!!!
				$new_url = substr($new_url, 0, -1);
			}
			
			if($bad_chars[2][$last_2_chars]) {
				$triggered = TRUE;
				
				$new_url = substr($new_url, 0, -2);
			}

			if($triggered && $max_depth !== 0) {
				$max_depth--;
				return $this->cleanseURL([
					'maxdepth'=>$max_depth,
					'url'=>$new_url,
				]);
			}

			return $new_url;
		}
		
		public function handleReservedCodeRedirect() {
		#	print("<!--");
			$url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			unset($url_pieces[0]);
			$full_code = implode('/', $url_pieces);
			
			$full_code_without_extension = pathinfo($full_code, PATHINFO_FILENAME);
			
			$alternate_short_reserved_code = $url_pieces[count($url_pieces) - 1];
			unset($url_pieces[count($url_pieces)]);
	#		print_r($url_pieces);
			
			
			$short_reserved_code = end($url_pieces);
			$full_reserved_code = implode('/', $url_pieces);
			
	#		print($alternate_short_reserved_code);
	#		print($full_reserved_code);
		#die("BT:");
			if($full_code_without_extension !== $full_code) {
				if($this->handleEntryCodeReservation(['reserved_code'=>$full_code_without_extension])) {
					return TRUE;
				}
			}
			
			if($this->handleEntryCodeReservation(['reserved_code'=>$full_code])) {
				return TRUE;
			}
			
			if($this->handleEntryCodeReservation(['reserved_code'=>$full_reserved_code])) {
				return TRUE;
			}
			
			if($this->handleEntryCodeReservation(['reserved_code'=>$short_reserved_code])) {
				return TRUE;
			}
			
			if($this->handleEntryCodeReservation(['reserved_code'=>$alternate_short_reserved_code])) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function handleEntryCodeReservation($args) {
			$reserved_code = $args['reserved_code'];
			
			$reservation_record_args = [
				'type'=>'EntryCodeReservation',
				'definition'=>[
					'Code'=>$reserved_code,
				],
			];
			
			$reservation = $this->db_access->GetRecords($reservation_record_args)[0];
			
			$assignment_record_args = [
				'type'=>'Assignment',
				'definition'=>[
					'id'=>$reservation['Assignmentid'],
				],
			];
			
			$assignment = $this->db_access->GetRecords($assignment_record_args)[0];
			
			$redirect_url = $this->BuildRedirect(['assignment'=>$assignment, 'permalink_id'=>$assignment['id']]);
			
			if($redirect_url) {
				$this->handleRedirect();
				return TRUE;
			}
		#	print($redirect_url);
			
		#	print($reserved_code);
		#	print($_SERVER['REQUEST_URI']);
		#	print("-->");
			return FALSE;
		}
		
		/*
			A redirect whose target is the URL already being served is an
			infinite loop by construction.  One such loop produced 95% of all
			traffic to this host.  Scheme changes are exempt, so the plain HTTP
			to HTTPS upgrade still works.
		*/

		public function RedirectsToSelf($args) {
			$target = $args['url'];

			if(strlen($target) === 0) {
				return FALSE;
			}

			$current_scheme = ($_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

			$target_scheme = parse_url($target, PHP_URL_SCHEME);
			$target_host = parse_url($target, PHP_URL_HOST);
			$target_path = parse_url($target, PHP_URL_PATH);
			$target_query = parse_url($target, PHP_URL_QUERY);

			if(!$target_scheme) {
				$target_scheme = $current_scheme;
			}

			if(!$target_host) {
				$target_host = $_SERVER['HTTP_HOST'];
			}

			if($target_scheme !== $current_scheme) {
				return FALSE;		# an upgrade, not a loop
			}

			if(strtolower($target_host) !== strtolower($_SERVER['HTTP_HOST'])) {
				return FALSE;
			}

			$current = $this->RequestPath();
			$current_pieces = explode('?', $current);
			$current_path = $current_pieces[0];
			$current_query = count($current_pieces) > 1 ? $current_pieces[1] : NULL;

			if(strlen($target_path) === 0) {
				$target_path = '/';
			}

			if($target_path !== $current_path) {
				return FALSE;
			}

			return ($target_query === $current_query);
		}

		/*
			A redirect that only corrects a URL on this host costs the visitor a
			round trip and this host two worker slots for one page view: a PHP
			boot to compute the Location, and another to answer the request that
			comes back.  Under prefork that is two of forty processes.

			Measured on 3 September 2026: 88% of requests were redirects.  Every
			junk path paid it twice over -- one boot to strip a trailing view.php,
			another to 404 -- and every shared permalink paid it too, which is the
			worst case, because a permalink is a link somebody meant to send.

			So repair instead.  Rewrite the request to what the redirect would
			have asked for and answer it now.  Construct_RepairQueryString has
			done exactly this for query strings since 2 September; this is the
			same move for paths, and keeps the same promise -- the arriving
			request is preserved beside the repaired one, never discarded.

			Only for a GET on this host and this scheme.  A 302 on a POST would
			discard the body, an http-to-https redirect is an upgrade rather than
			a correction, and another host is not ours to answer for.
		*/

		public function RepairInsteadOfRedirect($args) {
				/*
					OFF.  Still 500s on junk paths -- the class-loading pass did
					not reach everything, and the fix it used is wrong anyway.
				*/

			return FALSE;

			$target = $args['url'];

			if(strlen($target) === 0) {
				return FALSE;
			}

				/*
					A site whose configuration predates this feature has no such
					method, and repairing is what we want by default -- the same
					test RecordRelationEnabled makes, for the same reason.
				*/

			if(method_exists($this->globals, 'RepairInsteadOfRedirecting')) {
				if(!$this->globals->RepairInsteadOfRedirecting()) {
					return FALSE;
				}
			}

			if($_SERVER['REQUEST_METHOD'] !== 'GET') {
				return FALSE;		# a 302 would discard the body
			}

			if($this->repair_count >= 3) {
				return FALSE;		# a correction that keeps needing correcting
			}

			$current_scheme = ($_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

			$target_scheme = parse_url($target, PHP_URL_SCHEME);
			$target_host   = parse_url($target, PHP_URL_HOST);
			$target_path   = parse_url($target, PHP_URL_PATH);
			$target_query  = parse_url($target, PHP_URL_QUERY);

			if($target_scheme && ($target_scheme !== $current_scheme)) {
				return FALSE;		# an upgrade, not a correction
			}

			if($target_host && (strtolower($target_host) !== strtolower($_SERVER['HTTP_HOST']))) {
				return FALSE;		# another host answers for itself
			}

			if(strlen($target_path) === 0) {
				$target_path = '/';
			}

			return $this->RepairRequest([
				'path'=>$target_path,
				'query'=>$target_query,
			]);
		}

		/*
			The arriving request is kept under _ORIGINAL names, exactly as
			Construct_RepairQueryString keeps $GLOBALS['_ORIGINALGET'], and kept
			only once so that a second repair does not overwrite what actually
			arrived with an intermediate guess.

			Everything derived from the path has to be derived again.  These are
			the constructor's path-dependent steps and no others: the domain, the
			cookie, the globals and the database connection do not change when a
			path does.
		*/

		public function RepairRequest($args) {
			$path  = $args['path'];
			$query = $args['query'];

			$uri = $path . (strlen($query) ? '?' . $query : '');

			if(!$this->repair_count) {
				$this->original_request_uri = $_SERVER['REQUEST_URI'];
				$this->original_redirect_url = $_SERVER['REDIRECT_URL'];

				$GLOBALS['_ORIGINALREQUESTURI'] = $_SERVER['REQUEST_URI'];
				$GLOBALS['_ORIGINALREDIRECTURL'] = $_SERVER['REDIRECT_URL'];
			}

			$this->repair_count++;
			$this->repaired_to = $uri;
			$this->request_repaired = TRUE;

			$_SERVER['REDIRECT_URL'] = $path;
			$_SERVER['REQUEST_URI']  = $uri;
			$_SERVER['QUERY_STRING'] = strlen($query) ? $query : '';

			$repaired_get = [];
			parse_str($_SERVER['QUERY_STRING'], $repaired_get);
			$_GET = $repaired_get;

			$this->Construct_Query();
			$this->Construct_Action();
			$this->Construct_ObjectsAndScripts();
			$this->Construct_ScriptName();
			$this->Construct_ScriptFileAndExtension();
			$this->Construct_ScriptClassname();
			$this->Construct_ScriptFormat();

			if($this->script_name) {
				$this->Construct_ScriptLocation();
			}

				/*
					The script object was built for the old path.  Dropping it
					makes HandleRequest_Content build a fresh one.
				*/

			$this->script = NULL;
			$this->error_404 = NULL;

			return TRUE;
		}

		public function handleRedirect() {
			if($this->RedirectsToSelf(['url'=>$this->redirect_url])) {
				$this->redirect_url = '';

				return FALSE;
			}

			if($this->RepairInsteadOfRedirect(['url'=>$this->redirect_url])) {
				$this->redirect_url = '';

				return TRUE;
			}

			if($this->redirect_url) {
				if($this->globals->UseHeaderRedirects()) {
					header('Location: ' . $this->redirect_url);
				} else {
					http_response_code(200);	// "OK" (success)
					
					ggreq('classes/API/GoogleAnalytics.php');
					
					$google_analytics = new GoogleAnalytics($this->getArgs());
					
					print('<!DOCTYPE HTML><HTML><HEAD>');
					print('<META HTTP-EQUIV="REFRESH" CONTENT="0; URL=' . $this->redirect_url . '"/>');
					print('<LINK REL="CANONICAL" HREF="' . $this->redirect_url . '"/>');
					
					$google_analytics->DisplayHeaderBlock();
					
					print('</HEAD>');
					print('<BODY STYLE="font-family:arial;">');
					print('<!-- Note: don\'t tell people to `click` the link, just tell them that it is a link. -->');
					print('<h3><i><strong>Redirecting...</strong></i></h3>');
					print('<p>If you are not redirected automatically, follow this <a href="' . $this->redirect_url . '">' . $this->redirect_url . '</a>.</p>');
					print('</BODY>');
					print('</HTML>');
				}
			}
			return TRUE;
		}
		
		public function SecureRequired() {
			if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
				if($_COOKIE['loggedin']) {
					return TRUE;
				}
			}
			
			return FALSE;
		}
		
		public function SecureRedirect() {
			/*
				RequestPath() rather than REQUEST_URI: this runs first on every
				plain-HTTP request, so an absolute-form request URI concatenated
				here produced a longer broken URL on every single hop.  It was
				the largest single source of the redirect loop.
			*/

			$location = 'https://' . $_SERVER['HTTP_HOST'] . $this->RequestPath();
			header('Location: ' . $location);
			
			return TRUE;
		}
		
		public function ValidateReferrals() {
			if(!$this->domain->ValidateReferringWebsite()) {
				print('Error 403 - You done been smote.');
				return FALSE;
			}
			
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
			
			$shared_location = GGCMS_DIR . 'clonefrom.com' . $_SERVER['SCRIPT_URL'];
			
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
		
		public function HandleRequest_Content_Format_GetFormatObject() {
			ggreq('classes/Format/Base/AbstractBaseFormat.php');

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
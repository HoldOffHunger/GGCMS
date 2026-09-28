<?php

	/*
		Handler's redirect-and-repair stage: every check that looks at a
		request and answers "redirect", "repair and carry on", or "nothing
		to do".  Moved out of Handler on 28 September 2026, when it was two
		fifths of that file -- the part that grows, since there is always a
		new way to mangle a URL.  Logic only: every property it reads or
		sets is still Handler's, reached through $this->handler.
	*/

	class HandlerRedirects {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function handleImageRedirect() {
			$url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			
			if(count($url_pieces) > 2) {
				$last_piece = end($url_pieces);		# [-1] is a key named -1 in PHP, not the last item
				$first_piece = $url_pieces[1];
				
				if($first_piece === 'image' && strlen($last_piece) === 0) {	# are you searching for a directory like example.com/image/blahblahblablhablh/1/2/3/ ?  Then come along!
					return $this->imageRedirect();
				}
			}
			
			return FALSE;
		}
		
		public function imageRedirect() {
			$this->handler->redirect_url = '/image.php';
			return $this->handleRedirect();
		}
		
		public function handleMailTo() {
			$possible_mailto = substr($_SERVER['REQUEST_URI'], 0, 8);
			
			if($possible_mailto === '/mailto:') {
				$good_mailto = substr($_SERVER['REQUEST_URI'], 1);
				
				$this->handler->redirect_url = $good_mailto;
				return $this->handleRedirect();
			}
			
			return FALSE;
		}
		
		public function handleLinuxUserRedirect() {
			$protocol_check_2char = mb_substr($_SERVER['REQUEST_URI'], 0, 2);
			
			if($protocol_check_2char === '/~') {
				$redirect_url = '/';
				
				$this->handler->redirect_url = $redirect_url;
				return $this->handleRedirect();
			}
			
			return FALSE;
		}
		
		public function handleCopyPasteErrorRedirect() {
			$protocol_check_4char = mb_substr($_SERVER['REQUEST_URI'], 0, 4);
			$protocol_check_5char = mb_substr($_SERVER['REQUEST_URI'], 0, 5);
			
			if($protocol_check_4char === '/ftp' || $protocol_check_5char === '/http') {
				$redirect_url = '/';
				
				$this->handler->redirect_url = $redirect_url;
				return $this->handleRedirect();
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
			
			$redirect_url = $this->handler->version->GetOpenSourceURL();
			$this->handler->redirect_url = $redirect_url;
			return $this->handleRedirect();
		}
		
		// RequestPath()
		// Tests: HandlerRedirectsTest::testRequestPath()
		// Test file: tests/src/classes/Networking/Handler/HandlerRedirectsTest.php
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
				
				$redirect_url .= $this->handler->domain->primary_domain_lowercased;
				
				$redirect_url .= $this->RequestPath();
				
				$redirect_url = preg_replace('/fbclid=[A-Za-z0-9_%-]+[\&]*/', '', $redirect_url);
				$redirect_url = preg_replace('/\?$/', '', $redirect_url);
				
				#print($redirect_url);
				$this->handler->redirect_url = $redirect_url;
				return $this->handleRedirect();
			}
			
			if(preg_match('/\?$/', $_SERVER['REQUEST_URI'])) {
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url = 'https://';
				} else {
					$redirect_url = 'http://';
				}
				
				$redirect_url .= $this->handler->domain->primary_domain_lowercased;
				
				$redirect_url .= $this->RequestPath();
				
				$redirect_url = preg_replace('/\?$/', '', $redirect_url);
				
				#print($redirect_url);
				$this->handler->redirect_url = $redirect_url;
				return $this->handleRedirect();
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
				
				$assignment = $this->handler->db_access->RunQuery(['sql'=>$sql, 'args'=>$sql_args]);
				
				if($assignment && $assignment[0] && $assignment[0]['id']) {
					$permalink_id = (int)$assignment[0]['id'];
					$redirect_url = $this->BuildRedirect(['permalink_id'=>$permalink_id, 'assignment'=>$assignment[0]]);
					if($redirect_url) {
						$this->handler->redirect_url = $redirect_url;
						return $this->handleRedirect();
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
				
				$possible_script_extension = pathinfo($this->handler->script_name, PATHINFO_EXTENSION);
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
				$this->handler->redirect_url = $new_url;
	#			die($this->redirect_url);
				return $this->handleRedirect();
			}
			
			return FALSE;
		}
		
		public function handleScriptRedirect() {
			if($this->handler->script->script->redirect_script) {
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url .= 'https://';
				} else {
					$redirect_url .= 'http://';
				}
				
				$redirect_url .= $this->handler->domain->primary_domain_lowercased;
				
				if(isset($this->handler->script->script->redirect_base)) {
					$redirect_url .= $this->handler->script->script->redirect_base;
				} else {
					$new_url_pieces = explode('/', $_SERVER['REQUEST_URI']);
					array_pop($new_url_pieces);
					$redirect_url .= implode('/', $new_url_pieces);
					
					$redirect_url .= '/' . $this->handler->script->script->redirect_script . '.php';
				}
				
				if($this->handler->script->script->redirect_action) {
					$redirect_url .= '?action=' . $this->handler->script->script->redirect_action;
				}
				
				if($this->handler->script->script->redirect_query) {
					if($this->handler->script->script->redirect_action) {
						$redirect_url .= '&';
					} else {
						$redirect_url .= '?';
					}
					$redirect_url .= $this->handler->script->script->redirect_query;
				}
				
				$this->handler->redirect_url = $redirect_url;
				
			#	print("BT: REDIR!");
			#	print($this->redirect_url);
			#	die("soy");
				
				return $this->handleRedirect();
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
			
			$redirect_url .= $this->handler->domain->primary_domain_lowercased;
			
			$new_dir = $this->RequestPath();
			$new_dir = preg_replace("/[\/]+/", '/', $new_dir);
			
			$redirect_url .= $new_dir;
			
			$this->handler->redirect_url = $redirect_url;
			
		#	print("REDIRE!" . $redirect_url . "|");
		#	die("BT:");
			
			return $this->handleRedirect();
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

			$redirect_url .= $this->handler->domain->primary_domain_lowercased;
			$redirect_url .= $canonical_path;

			if(strlen($request_query)) {
				$redirect_url .= '?' . $request_query;
			}

			$this->handler->redirect_url = $redirect_url;

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
			if(!isset($this->handler->abstractglobals->script)) {
				return '';
			}

			if(!is_object($this->handler->abstractglobals->script)) {
				return '';
			}

			if(!method_exists($this->handler->abstractglobals->script, 'ForceCanonicalLink')) {
				return '';
			}

			$canonical_directory = $this->handler->abstractglobals->script->ForceCanonicalLink([
				'handler'=>$this->handler,
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

			// handleBadLinkRedirect()
			// Tests: HandlerRedirectsTest::testHandleBadLinkRedirect()
			// Test file: tests/src/classes/Networking/Handler/HandlerRedirectsTest.php
		public function handleBadLinkRedirect() {	// handles, i.e., "website.com/page)" or "website.com/page)."
			if($_GET['stopredirect']) {		// don't allow multiple redirects
				return FALSE;
			}
			
			$trimmed_url = trim(urldecode($_SERVER['REQUEST_URI']));
				
				/*
					nginx gives every dotless path a trailing slash before the
					engine sees it (ggcms_needs_slash), so "/people)" arrives as
					"/people)/", whose last character is the slash, and it 404ed.
					Look past one trailing slash, and keep it.  What counts as
					bad is cleanseURL()'s to say: this kept a copy of its table.
				*/
			
			$trailing_slash = (strlen($trimmed_url) > 1 && substr($trimmed_url, -1) === '/') ? '/' : '';
			$candidate = $trailing_slash ? substr($trimmed_url, 0, -1) : $trimmed_url;
			
			$new_url = $this->cleanseURL(['url'=>$candidate]);
			
			if($new_url !== $candidate) {
				$redirect_url = '';
				
				if($_SERVER['HTTPS'] === 'on') {
					$redirect_url .= 'https://';
				} else {
					$redirect_url .= 'http://';
				}
				
				$redirect_url .= $this->handler->domain->primary_domain_lowercased;
				
				$redirect_url .= $new_url . $trailing_slash;
				
				$query = parse_url($redirect_url, PHP_URL_QUERY);
				
				// Returns a string if the URL has parameters or NULL if not
				if ($query) {
					$redirect_url .= '&stopredirect=1';
				} else {
					$redirect_url .= '?stopredirect=1';
				}				
				
			#	print($redirect_url);
				
				$this->handler->redirect_url = $redirect_url;
				
				return $this->handleRedirect();
			}
			
			#print($last_chars);
			
			return FALSE;
		}
		
			// badEndingCharacters()
			// Tests: HandlerRedirectsTest::testBadEndingCharacters()
			// Test file: tests/src/classes/Networking/Handler/HandlerRedirectsTest.php
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

			// cleanseURL()
			// Tests: HandlerRedirectsTest::testCleanseURL()
			// Test file: tests/src/classes/Networking/Handler/HandlerRedirectsTest.php
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

				/*
					Two characters or one, never both.  Every two-character
					ending also ends in a one-character one -- ".)" ends in ")"
					-- so testing both cut three characters, and
					"/some/page.)" became "/some/pag".
				*/
			
			if($bad_chars[2][$last_2_chars]) {
				$triggered = TRUE;
				
				$new_url = substr($new_url, 0, -2);
			} elseif($bad_chars[1][$last_1_char]) {
				$triggered = TRUE;
			
			#	while($new_url
					# and then something!!!!!
				$new_url = substr($new_url, 0, -1);
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
			
			$reservation = $this->handler->db_access->GetRecords($reservation_record_args)[0];
			
			$assignment_record_args = [
				'type'=>'Assignment',
				'definition'=>[
					'id'=>$reservation['Assignmentid'],
				],
			];
			
			$assignment = $this->handler->db_access->GetRecords($assignment_record_args)[0];
			
			$redirect_url = $this->BuildRedirect(['assignment'=>$assignment, 'permalink_id'=>$assignment['id']]);
			
			if($redirect_url) {
				return $this->handleRedirect();
			}
		#	print($redirect_url);
			
		#	print($reserved_code);
		#	print($_SERVER['REQUEST_URI']);
		#	print("-->");
			return FALSE;
		}
		
		// RedirectsToSelf()
		// Tests: HandlerRedirectsTest::testRedirectsToSelf()
		// Test file: tests/src/classes/Networking/Handler/HandlerRedirectsTest.php
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
			$target = $args['url'];

			if(strlen($target) === 0) {
				return FALSE;
			}

				/*
					A site whose configuration predates this feature has no such
					method, and repairing is what we want by default -- the same
					test RecordRelationEnabled makes, for the same reason.
				*/

			if(method_exists($this->handler->globals, 'RepairInsteadOfRedirecting')) {
				if(!$this->handler->globals->RepairInsteadOfRedirecting()) {
					return FALSE;
				}
			}

			if(($_SERVER['REQUEST_METHOD'] !== 'GET') && ($_SERVER['REQUEST_METHOD'] !== 'HEAD')) {
				return FALSE;		# a 302 would discard the body
			}

			if($this->handler->repair_count >= 3) {
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
				'path'=>$this->CanonicalTrailingSlash(['path'=>$target_path]),
				'query'=>$target_query,
			]);
		}

			// CanonicalTrailingSlash()
			// Tests: HandlerRedirectsTest::testRepairInsteadOfRedirect()
			// Test file: tests/src/classes/Networking/Handler/HandlerRedirectsTest.php
			/*
				nginx's ggcms_needs_slash map, in the configuration repository's
				etc/nginx/sites-available/ggcms.conf: a path of one to six
				segments, none with a dot, and no trailing slash, is given one.
				A redirect goes back through nginx and gets it; a repair is
				answered here and never does, so it is given it here.  Change
				one, change both.
			*/
		
		public function CanonicalTrailingSlash($args) {
			$path = (string)$args['path'];
			
			if(preg_match('#^/([^./]+/){0,5}[^./]+$#', $path)) {
				return $path . '/';
			}
			
			return $path;
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

			if(!$this->handler->repair_count) {
				$this->handler->original_request_uri = $_SERVER['REQUEST_URI'];
				$this->handler->original_redirect_url = $_SERVER['REDIRECT_URL'];

				$GLOBALS['_ORIGINALREQUESTURI'] = $_SERVER['REQUEST_URI'];
				$GLOBALS['_ORIGINALREDIRECTURL'] = $_SERVER['REDIRECT_URL'];
			}

			$this->handler->repair_count++;
			$this->handler->repaired_to = $uri;

			$_SERVER['REDIRECT_URL'] = $path;
			$_SERVER['REQUEST_URI']  = $uri;
			$_SERVER['QUERY_STRING'] = strlen($query) ? $query : '';

			$repaired_get = [];
			parse_str($_SERVER['QUERY_STRING'], $repaired_get);
			$_GET = $repaired_get;

			$this->handler->Construct_Query();
			$this->handler->Construct_Action();
			$this->handler->Construct_ObjectsAndScripts();
			$this->handler->script_handler->Construct_ScriptName();
			$this->handler->script_handler->Construct_ScriptFileAndExtension();
			$this->handler->script_handler->Construct_ScriptClassname();
			$this->handler->script_handler->Construct_ScriptFormat();

			if($this->handler->script_name) {
				$this->handler->script_handler->Construct_ScriptLocation();
			}

				/*
					The script object was built for the old path.  Dropping it
					makes HandleRequest_Content build a fresh one.
				*/

			$this->handler->script = NULL;
			$this->handler->error_404 = NULL;

			return TRUE;
		}

		/*
			Every handler returns what this returns, because this does not
			always redirect.  RedirectsToSelf refuses a redirect to the
			address already being requested -- correctly, or the client would
			loop -- and a handler that reported success anyway stopped the
			chain with nothing written at all.
			
			That was an empty 200: no page, no error, no log line, and a
			crawler told the URL was fine.  An unpublished entry answered
			that way, which is how one stayed invisible for twenty-one
			months.  handleForceCanonicalLinkRedirect already returned this
			value; the other thirteen now do too.
		*/

		public function handleRedirect() {
			if($this->RedirectsToSelf(['url'=>$this->handler->redirect_url])) {
				$this->handler->redirect_url = '';

				return FALSE;
			}

				/*
					Collecting rather than sending.  RepairEntryPath asks the
					correction handlers what they would have redirected to,
					without letting them send it, so it can answer the request
					here instead.
				*/

			if($this->handler->collect_redirect) {
				return TRUE;
			}

				/*
					Before content, a correction on this host is answered rather
					than redirected to, and the chain carries on to serve it.
					FALSE here means 'not handled, keep going', which is exactly
					true -- the request has been corrected, not answered.
				*/

			if($this->handler->before_content && $this->RepairInsteadOfRedirect(['url'=>$this->handler->redirect_url])) {
				$this->handler->redirect_url = '';

				return FALSE;
			}

			if($this->handler->redirect_url) {
				if($this->handler->globals->UseHeaderRedirects()) {
					header('Location: ' . $this->handler->redirect_url);
				} else {
					http_response_code(200);	// "OK" (success)
					
					print('<!DOCTYPE HTML><HTML><HEAD>');
					print('<META HTTP-EQUIV="REFRESH" CONTENT="0; URL=' . $this->handler->redirect_url . '"/>');
					print('<LINK REL="CANONICAL" HREF="' . $this->handler->redirect_url . '"/>');
					
					print('</HEAD>');
					print('<BODY STYLE="font-family:arial;">');
					print('<!-- Note: don\'t tell people to `click` the link, just tell them that it is a link. -->');
					print('<h3><i><strong>Redirecting...</strong></i></h3>');
					print('<p>If you are not redirected automatically, follow this <a href="' . $this->handler->redirect_url . '">' . $this->handler->redirect_url . '</a>.</p>');
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
		
		public function CheckPermalinkRedirect() {
			$permalink_id = (int)$this->handler->query->Parameter(['parameter'=>'id']);
			
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
				
				$assignment = $this->handler->db_access->GetRecords($assignment_record_args);
				
				if($assignment && $assignment[0] && $assignment[0]['id']) {
					$this->BuildRedirect(['assignment'=>$assignment[0], 'permalink_id'=>$permalink_id]);
					return TRUE;
				} else {
					$this->handler->issue_logging->createLog([
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
			
			if(!$this->handler->orm) {
				$this->handler->orm = new ORM($this->handler->getArgs());
			}
			
			$entry_records = $this->handler->orm->SearchForEntries([
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
			$redirect_url .= $this->handler->domain->primary_domain_lowercased;
			
			$entry_record_count = count($entry_records['parents']);
			for($i = 0; $i < $entry_record_count; $i++) {
				$entry_record = $entry_records['parents'][$i];
				$redirect_url .= '/' . $entry_record['Code'];
			}
			
			$action = $this->handler->desired_action;
			
			if($action === 'Edit') {
				$redirect_url .= '/modify.php';
			} else {
				$redirect_url .= '/';
			}
			
			if($this->handler->desired_action && $this->handler->desired_action !== 'display') {
				if($action !== 'Edit') {
					$redirect_url .= 'view.php';
				}
				$redirect_url .= '?action=' . $this->handler->desired_action;
			}
			
			return $this->handler->redirect_url = $redirect_url;
		}
	}

?>
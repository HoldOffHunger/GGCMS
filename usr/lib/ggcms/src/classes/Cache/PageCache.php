<?php

	/*
		Whole-page file cache.

		A cached page is an ordinary file on disk.  Apache's `!-f` rewrite
		condition means that when the file is there, Apache serves it and PHP
		never starts -- no Handler, no database, none of the ~1700 queries a
		render costs.  This class decides whether a response may be cached,
		works out where it goes, and writes it.

		It does NOT decide when to serve the cache.  That is .htaccess, and it
		happens before any PHP runs, which is the entire point.

		See Docs/PageCache.md.
	*/

	class PageCache {

			// Construction
			// -------------------------------------------------

		public function __construct($args) {
			$this->handler = $args['handler'];

			return $this;
		}

			// Locations
			// -------------------------------------------------

		/*
			The cache lives on the mounted volume, not the root filesystem.
			Root has ~3GB spare; the volume has ~44GB.  /var/www/html/_cache is
			a symlink to this, so that Apache can reach it from the document
			root without a per-vhost Alias across seventeen sites.
		*/

		public function CacheRootLocation() {
			return '/mnt/nyc01/ggcms_cache/pages';
		}

		public function DomainCacheLocation() {
			$host = $this->SafeHost();

			if($host === FALSE) {
				return FALSE;
			}

			return $this->CacheRootLocation() . '/' . $host;
		}

		/*
			The cache key is the request URI, and the request URI is entirely
			attacker-controlled.  Everything below exists to make sure a
			crafted path cannot escape the cache tree or exhaust the disk.
		*/

		public function SafeHost() {
			$host = strtolower($_SERVER['HTTP_HOST']);

			$host = explode(':', $host)[0];		# drop any port

			if(strlen($host) === 0 || strlen($host) > 253) {
				return FALSE;
			}

			if(!preg_match('/\A[a-z0-9]([a-z0-9.-]*[a-z0-9])?\z/', $host)) {
				return FALSE;
			}

			if(strpos($host, '..') !== FALSE) {
				return FALSE;
			}

			return $host;
		}

		public function SafePath() {
			$uri = $_SERVER['REQUEST_URI'];

			$uri = explode('?', $uri)[0];		# querystrings are never cached

			if(strlen($uri) === 0 || $uri[0] !== '/') {
				return FALSE;
			}

			if(strlen($uri) > $this->MaxPathLength()) {
				return FALSE;
			}

			if(strpos($uri, "\0") !== FALSE) {
				return FALSE;
			}

			/*
				Reject anything that is not a plain, already-decoded path.  A
				percent sign is refused rather than decoded: decoding here
				would let %2e%2e%2f become ../ after validation, which is the
				classic way out of a cache directory.
			*/

			if(!preg_match('#\A[A-Za-z0-9/_.,~-]*\z#', $uri)) {
				return FALSE;
			}

			if(strpos($uri, '..') !== FALSE) {
				return FALSE;
			}

			if(strpos($uri, '//') !== FALSE) {
				return FALSE;
			}

			/*
				The URL path is a walk through the entry graph, so its depth is
				unbounded by design.  Cap it, or a crawler wandering the graph
				will fill the volume with directories nobody will ever request
				twice.
			*/

			if(substr_count($uri, '/') > $this->MaxPathDepth()) {
				return FALSE;
			}

			return $uri;
		}

		public function MaxPathLength() {
			return 512;
		}

		public function MaxPathDepth() {
			return 24;
		}

		/*
			A request ending in `/` becomes `<path>/index.html`; anything else
			gets `.html` appended.  .htaccess computes the identical two forms,
			and the pair must be changed together.
		*/

		public function CacheLocation() {
			$domain_location = $this->DomainCacheLocation();
			$path = $this->SafePath();

			if($domain_location === FALSE || $path === FALSE) {
				return FALSE;
			}

			if(substr($path, -1) === '/') {
				return $domain_location . $path . 'index.html';
			}

			return $domain_location . $path . '.html';
		}

			// Cacheability
			// -------------------------------------------------

		/*
			Conservative by construction.  Every condition must pass.  A page
			wrongly served from cache is a correctness bug that can leak one
			visitor's view to another; a page wrongly NOT cached merely costs
			what the site costs today.
		*/

		public function IsCacheable($args) {
			$output = $args['output'];

			if(!$this->IsCacheable_Method()) {
				return FALSE;
			}

			if(!$this->IsCacheable_NoQueryString()) {
				return FALSE;
			}

			if(!$this->IsCacheable_Anonymous()) {
				return FALSE;
			}

			if(!$this->IsCacheable_Script()) {
				return FALSE;
			}

			if(!$this->IsCacheable_Response()) {
				return FALSE;
			}

			if(!$this->IsCacheable_Output(['output'=>$output])) {
				return FALSE;
			}

			return TRUE;
		}

		public function IsCacheable_Method() {
			return ($_SERVER['REQUEST_METHOD'] === 'GET');
		}

		public function IsCacheable_NoQueryString() {
			return (strlen($_SERVER['QUERY_STRING']) === 0);
		}

		/*
			Anyone carrying a session cookie gets a live render.  The cookie
			names match the ones .htaccess tests; change both together or a
			logged-in visitor will be served a stranger's anonymous page.
		*/

		public function IsCacheable_Anonymous() {
			$session_cookies = [
				'loggedin',
				'AuthenticationToken',
			];

			foreach($session_cookies as $session_cookie) {
				if(array_key_exists($session_cookie, $_COOKIE)) {
					return FALSE;
				}
			}

			return TRUE;
		}

		/*
			99.99% of traffic is view.php or the bare default.  Start there.
			Widening this list is cheap; getting it wrong on modify.php is not.
		*/

		public function CacheableScripts() {
			return [
				'view.php',
			];
		}

		public function IsCacheable_Script() {
			$handler = $this->handler;

			if($handler->error_404) {
				return FALSE;
			}

			if($handler->script_format !== 'HTML') {
				return FALSE;
			}

			if(!in_array($handler->script_name, $this->CacheableScripts())) {
				return FALSE;
			}

			/*
				isSecure() marks pages that set no-store headers and
				re-authenticate.  Those must never touch the disk.
			*/

			if($handler->script && $handler->script->script) {
				if($handler->script->script->isSecure()) {
					return FALSE;
				}
			}

			return TRUE;
		}

		public function IsCacheable_Response() {
			if(http_response_code() !== 200) {
				return FALSE;
			}

			/*
				A response that sets a cookie is by definition specific to the
				visitor who asked for it.
			*/

			foreach(headers_list() as $header) {
				if(stripos($header, 'Set-Cookie:') === 0) {
					return FALSE;
				}

				if(stripos($header, 'Location:') === 0) {
					return FALSE;
				}
			}

			return TRUE;
		}

		/*
			earthfluent.com spent an unknown length of time serving a 334-byte
			error stub with a 200 status, because error_reporting(0) hides the
			fault.  Caching that would have made a transient failure permanent.
			Anything suspiciously short is refused.
		*/

		public function MinimumCacheableLength() {
			return 2048;
		}

		public function IsCacheable_Output($args) {
			$output = $args['output'];

			if(strlen($output) < $this->MinimumCacheableLength()) {
				return FALSE;
			}

			if(stripos($output, '</html>') === FALSE) {
				return FALSE;
			}

			return TRUE;
		}

			// Writing
			// -------------------------------------------------

		public function WriteCache($args) {
			$output = $args['output'];

			if(!$this->IsCacheable(['output'=>$output])) {
				return FALSE;
			}

			$location = $this->CacheLocation();

			if($location === FALSE) {
				return FALSE;
			}

			$directory = dirname($location);

			if(!is_dir($directory)) {
				if(!@mkdir($directory, 0755, TRUE)) {
					return FALSE;		# lost a race, or the volume is full
				}
			}

			/*
				Write to a temporary name and rename into place.  rename() is
				atomic within a filesystem, so a reader never sees a partial
				page -- which matters with twenty workers writing concurrently.
			*/

			$temporary_location = $location . '.' . getmypid() . '.tmp';

			if(@file_put_contents($temporary_location, $output) === FALSE) {
				return FALSE;
			}

			if(!@rename($temporary_location, $location)) {
				@unlink($temporary_location);

				return FALSE;
			}

			return TRUE;
		}

			// Flushing
			// -------------------------------------------------

		/*
			Called from the four DBAccess write methods.  The content database
			changes rarely, so flushing a whole domain on any write is both
			correct and cheap -- and unlike per-page dependency tracking, it
			cannot be subtly wrong.
		*/

		public function FlushDomain($args) {
			if(array_key_exists('domain', $args)) {
				$domain = $args['domain'];
			} else {
				$domain = $this->SafeHost();
			}

			if($domain === FALSE) {
				return FALSE;
			}

			$location = $this->CacheRootLocation() . '/' . $domain;

			return $this->RemoveDirectory(['location'=>$location]);
		}

		public function FlushAll() {
			$root = $this->CacheRootLocation();

			$domains = @scandir($root);

			if($domains === FALSE) {
				return FALSE;
			}

			foreach($domains as $domain) {
				if($domain === '.' || $domain === '..') {
					continue;
				}

				$this->RemoveDirectory(['location'=>$root . '/' . $domain]);
			}

			return TRUE;
		}

		/*
			Deliberately refuses to operate anywhere outside the cache root.
			This function deletes recursively; a bug in its caller must not be
			able to turn that loose on the filesystem.
		*/

		public function RemoveDirectory($args) {
			$location = $args['location'];

			$root = $this->CacheRootLocation();

			$real_location = realpath($location);
			$real_root = realpath($root);

			if($real_location === FALSE || $real_root === FALSE) {
				return FALSE;
			}

			if(strpos($real_location, $real_root . '/') !== 0) {
				return FALSE;		# outside the cache tree; refuse
			}

			$items = @scandir($real_location);

			if($items === FALSE) {
				return FALSE;
			}

			foreach($items as $item) {
				if($item === '.' || $item === '..') {
					continue;
				}

				$item_location = $real_location . '/' . $item;

				if(is_dir($item_location) && !is_link($item_location)) {
					$this->RemoveDirectory(['location'=>$item_location]);
				} else {
					@unlink($item_location);
				}
			}

			return @rmdir($real_location);
		}
	}

?>

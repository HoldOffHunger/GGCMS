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

			Overridable, so that pages can be rendered somewhere other than the
			machine that will serve them.  A cache key is a pure function of the
			host and the request path -- see CacheLocation() -- and a cached page
			holds nothing naming the machine that built it, so a tree built
			anywhere is valid here.  That matters because this host has one core:
			warming revoltlib's 12,545 published pages costs it hours it cannot
			spare and a developer workstation minutes it will not miss.

			Read from the environment, and under the CLI only.  A web request
			must never be able to steer where pages are written, and none can
			reach this branch to try.
		*/

		public function CacheRootLocation() {
			if(PHP_SAPI === 'cli') {
				$override = (string) getenv('GGCMS_PAGE_CACHE_ROOT');

				if(strlen($override)) {
					return rtrim($override, '/');
				}
			}

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
			A request ending in `/` becomes `<path>/index.html`.  Anything else
			gets the format's own extension appended, so a page caches to
			`<path>.html` and a stylesheet to `<path>.css` -- the suffix must
			mirror the request or Apache serves the wrong Content-Type.

			.htaccess computes the identical forms.  Change one and you must
			change the other.
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

			return $domain_location . $path . '.' . $this->CacheSuffix();
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

		/*
			style.php renders through the CSS format class; /css/view/display.css
			does not exist on disk.  Every page view therefore paid a full PHP
			boot for its stylesheet -- 0.6 to 1.4 seconds, against 0.06 for the
			cached HTML itself.  It is anonymous, deterministic and takes no
			query string, so it caches cleanly.
		*/

		/*
			BRF earns its place by being the most expensive thing this engine
			produces.  war-and-peace on revoltlib is 365 children and 3.6 MB
			of text; as .brf it took over two minutes and then returned 500
			on the execution limit, and it did that on every single request.
			There were 233 .brf requests in one day's log.
			
			The conversion is not the cost.  Measured on 4 September 2026 the
			converter runs at 252,000 characters a second, so 3.6 MB is
			fourteen seconds of it.  The rest is the pipeline around it --
			strip_tags, html_entity_decode, iconv and two preg_replace passes
			over megabytes -- plus 365 child text bodies fetched from a
			database on another host.  None of it changes between requests,
			which is the definition of something worth caching.
			
			It passes the output test already: IsCacheable_Output asks a
			non-HTML format not to begin with '<', and Braille does not.
		*/

		public function CacheableFormats() {
			return [
				'HTML'=>'html',
				'CSS'=>'css',
				'BRF'=>'brf',
			];
		}

		/*
			What a page this cache would store tells browsers and Cloudflare.

			Four hours, the same max-age Cloudflare already sends for wordweight.
			A saved edit clears the disk cache at once, but a copy already held
			by a browser or at the edge lives out its four hours; for pages that
			change rarely that is the right trade.

			nginx sends the same header on cache hits, which never reach PHP --
			etc/nginx/sites-available/ggcms.conf in the configuration repository.
			Change one and change the other.
		*/

		public function BrowserCacheHeader() {
			return 'Cache-Control: public, max-age=14400';
		}

		/*
			The cached file's extension must mirror the request's, or Apache
			serves a stylesheet as text/html and the browser discards it.  A
			request for .../display.css caches to .../display.css.css.
		*/

		public function CacheSuffix() {
			$formats = $this->CacheableFormats();

			return $formats[$this->handler->script_format];
		}

		public function IsCacheable_Script() {
			$handler = $this->handler;

			if($handler->error_404) {
				return FALSE;
			}

			if(!array_key_exists($handler->script_format, $this->CacheableFormats())) {
				return FALSE;
			}

			/*
				The script name only decides anything for HTML.  A stylesheet
				request is style.php by construction -- Format/CSS.php requires
				scripts/style.php unconditionally, whatever the URL -- while its
				script NAME is the last path segment, 'display.css' for
				/css/view/display.css.  Comparing that against 'style.php'
				rejected every stylesheet ever offered to the cache.
			*/

			if($handler->script_format === 'HTML') {
				if(!in_array($handler->script_name, $this->CacheableScripts())) {
					return FALSE;
				}
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
			$response_code = http_response_code();

				/*
					Under the CLI there is no HTTP response, so an untouched code
					reads FALSE rather than 200 and every warmed page was refused.

					Unset is not the same as unknown here.  PHP still records a
					code the moment anything sets one, so a render that failed and
					asked for a 404 still reports 404 from the command line.  What
					FALSE means is that nothing objected, which is what 200 means
					on the web.

					The web can never reach this branch: there, an untouched code
					is already 200.
				*/

			if(($response_code === FALSE) && (PHP_SAPI === 'cli')) {
				$response_code = 200;
			}

			if($response_code !== 200) {
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
			if($this->handler->script_format !== 'HTML') {
				return 64;
			}

			return 2048;
		}

		public function IsCacheable_Output($args) {
			$output = $args['output'];

			if(strlen($output) < $this->MinimumCacheableLength()) {
				return FALSE;
			}

			if($this->handler->script_format === 'HTML') {
				if(stripos($output, '</html>') === FALSE) {
					return FALSE;
				}
			} else {
					# a stylesheet that begins with a PHP error is not a stylesheet
				if(stripos(ltrim($output), '<') === 0) {
					return FALSE;
				}
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

				/*
					Two workers writing pages in one new directory both see it
					missing and both call mkdir; the loser's call fails because the
					directory now exists.  Giving up there refused a perfectly good
					page -- on 13 September 2026 a parallel warm lost one of every
					/x/ and /x/view.php pair.  Only a directory that still does not
					exist afterwards is a failure.
				*/

			if(!is_dir($directory)) {
				if(!@mkdir($directory, 0755, TRUE) && !is_dir($directory)) {
					return FALSE;		# the volume is full, or permissions forbid it
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

			$this->WriteCompressed(['location'=>$location, 'output'=>$output]);

			return TRUE;
		}

		/*
			The same page, gzipped, beside itself.

			nginx has gzip_static on, so it serves this file directly and
			compresses nothing at request time.  Without it, every one of the
			twelve thousand cached pages was compressed afresh on every hit, on
			a host with one core and a crawler on it.

			Written here rather than only in the warmer, so that a page the
			server caches for itself behaves exactly like one built off-host.
			A cache whose entries differ depending on which machine wrote them
			is a cache nobody can reason about.

			Best-effort, like every other failure path in this class: if the
			compressed copy cannot be written the page is still cached and
			nginx simply compresses on the fly, which is what it did before.
			The one thing that must not happen is a stale .gz outliving its
			page, because nginx would go on serving it -- so FlushPage removes
			this too, and RemoveDirectory takes it with everything else.
		*/

		public function CompressedLocation($args) {
			return $args['location'] . '.gz';
		}

		public function WriteCompressed($args) {
			$location = $this->CompressedLocation(['location'=>$args['location']]);

			if(!function_exists('gzencode')) {
				return FALSE;
			}

			$compressed = @gzencode($args['output'], 9);

			if($compressed === FALSE) {
				return FALSE;
			}

			$temporary_location = $location . '.' . getmypid() . '.tmp';

			if(@file_put_contents($temporary_location, $compressed) === FALSE) {
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

			/*
				Flush one page: the one this request is for.

				FlushDomain is right for a content edit -- a changed title
				appears on index pages as well as its own -- but it is far too
				much for someone clicking like.  Every write went through it,
				so a single visitor liking an entry deleted every cached page
				on the site, thousands of them, each of which then re-rendered.
				The sites with the most engagement kept the least cache.

				CacheLocation already computes exactly the file this request
				would be cached to, and SafePath drops the query string, so a
				like submitted to /some/entry/?action=... resolves to the entry
				page itself.

				A missing file is success.  There is nothing to invalidate if
				the page was never cached, and refusing here would only turn a
				no-op into an error in the caller.
			*/

		public function FlushPage($args) {
			$location = $this->CacheLocation();

			if($location === FALSE) {
				return FALSE;
			}

				/*
					The compressed copy goes first, and unconditionally.

					nginx decides whether to serve a .gz by looking for the .gz,
					not by looking at the page beside it.  A .gz that outlives
					its page is therefore served for ever, to every client that
					accepts gzip -- which is all of them.  Removing it before
					the page, and whether or not the page is there, is what
					makes that impossible.
				*/

			@unlink($this->CompressedLocation(['location'=>$location]));

			if(!is_file($location)) {
				return TRUE;
			}

			return @unlink($location);
		}

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

			/*
				What the last flush actually deleted.

				A list rather than a count, because the interesting question
				after an edit is not how many pages went but which -- a save
				that clears one page when it should have cleared a section
				looks identical to a correct one from a number alone.

				Accumulated across calls on purpose: flushing both hostnames
				is two calls and one answer.
			*/

		public $removed_files = [];

		public function RemovedFiles() {
			return $this->removed_files;
		}

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
					if(@unlink($item_location)) {
						$this->removed_files[] = $item_location;
					}
				}
			}

			return @rmdir($real_location);
		}
	}

?>

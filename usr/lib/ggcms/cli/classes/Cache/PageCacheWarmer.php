<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');

	class PageCacheWarmer {
		use CLIAccess;
		use GlobalsTrait;

			// Entry Point
			// -----------------------------------------------

			/*
				Every deploy flushes the whole page cache, and on a one-core host
				under crawler traffic the rebuild happens under load, in whatever
				order the crawlers ask for -- which is not the order that matters.
				Warming asks for the pages people actually request, in order of
				how often they request them, before anyone else has to wait for
				them.

				A miss costs roughly 1,700 database round trips to another
				datacentre.  A hit costs Apache reading a file.  Nothing else in
				this system has that ratio.
			*/

		public function warmCache() {
			$this->setGlobals();
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$urls = $this->getURLs();

			if(!$urls) {
				print('No URLs to warm.' . "\n\n");
				print('If ' . $this->accessLogLocation() . ' has traffic in it, the lines are probably' . "\n");
				print('in Apache\'s `combined` format, which does not name the site a request was' . "\n");
				print('for.  Either pass --domain=NAME, or switch the vhosts to `vhost_combined`' . "\n");
				print('and every site can be warmed in one pass.  See Docs/Operations.md.' . "\n");

				return FALSE;
			}

			return $this->requestAll(['urls'=>$urls]);
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'domain'=>'',
				'limit'=>50,
				'delay'=>250000,
				'timeout'=>120,
				'force'=>FALSE,
				'quiet'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--force') {
					$arguments['force'] = TRUE;
				} else if($argument === '--quiet') {
					$arguments['quiet'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 9) === '--domain=') {
					$arguments['domain'] = substr($argument, 9);
				} else if(substr($argument, 0, 8) === '--limit=') {
					$arguments['limit'] = (int) substr($argument, 8);
				} else if(substr($argument, 0, 8) === '--delay=') {
					$arguments['delay'] = (int) (((float) substr($argument, 8)) * 1000000);
				} else if(substr($argument, 0, 10) === '--timeout=') {
					$arguments['timeout'] = (int) substr($argument, 10);
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Page Cache Warmer' . "\n\n");
			print('  warm_cache.php [--domain=NAME] [--limit=N] [--delay=SECONDS] [--force] [--quiet]' . "\n\n");
			print('  Requests the most-asked-for pages so the cache is built before a reader waits' . "\n");
			print('  for it.  Reads the access log to decide what "most-asked-for" means.' . "\n\n");
			print('  --limit    how many URLs, most-requested first (default 50)' . "\n");
			print('  --delay    seconds to wait between requests (default 0.25)' . "\n");
			print('  --force    request pages that are already cached' . "\n");
			print('  --quiet    totals only, for cron' . "\n\n");

			return TRUE;
		}

			// URLs
			// -----------------------------------------------

			/*
				The access log rather than the sitemap.  A sitemap is every URL
				that exists, tens of thousands of them, in an order that means
				nothing.  The log is what people asked for, and asking twice is
				the only evidence that a page matters.
			*/

		public function accessLogLocation() {
			return '/var/log/apache2/access.log';
		}

		public function getURLs() {
			$location = $this->accessLogLocation();

			if(!is_readable($location)) {
				return [];
			}

			$handle = fopen($location, 'r');

			if(!$handle) {
				return [];
			}

			$counts = [];

			while(($line = fgets($handle)) !== FALSE) {
				$request = $this->parseLine(['line'=>$line]);

				if(!$request) {
					continue;
				}

				$key = $request['host'] . ' ' . $request['path'];

				if(!isset($counts[$key])) {
					$counts[$key] = 0;
				}

				$counts[$key]++;
			}

			fclose($handle);

			arsort($counts);

			$urls = [];

			foreach($counts as $key => $count) {
				if(count($urls) >= $this->arguments['limit']) {
					break;
				}

				$pieces = explode(' ', $key);

				if(!$this->arguments['force'] && $this->isCached(['host'=>$pieces[0], 'path'=>$pieces[1]])) {
					continue;
				}

				$urls[] = [
					'host'=>$pieces[0],
					'path'=>$pieces[1],
					'requests'=>$count,
				];
			}

			return $urls;
		}

			/*
				The combined log format, and only the parts that decide whether a
				URL is worth warming.  Anything the rewrite rules would refuse to
				serve from cache is refused here too, for the same reasons and in
				the same order -- see the conditions in var/www/html/.htaccess.
			*/

		public function parseLine($args) {
			$line = $args['line'];

			$matches = [];

			if(!preg_match('/"(GET) ([^ "?]+)(\?[^ "]*)? HTTP[^"]*" (\d{3}) /', $line, $matches)) {
				return FALSE;
			}

			if(isset($matches[3]) && $matches[3] !== '') {
				return FALSE;		# a query string is never served from cache
			}

			if($matches[4] !== '200') {
				return FALSE;		# only warm what already answers
			}

			$path = $matches[2];

			if(strpos($path, '/_cache/') === 0) {
				return FALSE;
			}

			if(preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|ico|woff2?|ttf|pdf|epub|xml|txt|rdf|opds|csv)$/i', $path)) {
				return FALSE;		# static files and generated documents, not pages
			}

			$host = $this->hostFromLine(['line'=>$line]);

			if(!$host) {
				return FALSE;
			}

			if($this->arguments['domain'] && $host !== $this->arguments['domain']) {
				return FALSE;
			}

			return [
				'host'=>$host,
				'path'=>$path,
			];
		}

			/*
				A `vhost_combined` line names its own site, first thing:

				    revoltlib.com:443 44.213.68.60 - - [31/Aug/2026:19:47:26 ...

				A `combined` line does not, and the Referer is "-" on nearly
				every crawler request, so such a line cannot be attributed to a
				site at all.  Both forms appear in the same file while a rotation
				straddles the format change, so both are handled: the prefix when
				it is there, --domain when it is not, and the line is skipped
				rather than guessed at when neither can answer.

				Guessing would warm one site's cache with another's URLs, which
				writes entries that are never read and takes disk to do it.
			*/

		public function hostFromLine($args) {
			$line = $args['line'];

			$matches = [];

			if(preg_match('/^([a-z0-9.-]+):\d+ /i', $line, $matches)) {
				return strtolower(preg_replace('/^www\./i', '', $matches[1]));
			}

			return $this->arguments['domain'];
		}

			// Cache State
			// -----------------------------------------------

		public function cacheRoot() {
			return '/var/www/html/_cache';
		}

			/*
				The two filename forms the rewrite rules test, and they must stay
				the two forms PageCache writes.  See Docs/PageCache.md, which says
				the same thing from the other end.
			*/

		public function isCached($args) {
			$host = $args['host'];
			$path = $args['path'];

			$base = $this->cacheRoot() . '/' . $host . $path;

			if(substr($path, -1) === '/') {
				return is_file($base . 'index.html');
			}

			return is_file($base . '.html');
		}

			// Requests
			// -----------------------------------------------

		public function requestAll($args) {
			$urls = $args['urls'];

			$results = [];

			$warmed = 0;
			$failed = 0;
			$started = microtime(TRUE);

			foreach($urls as $url) {
				$result = $this->request(['host'=>$url['host'], 'path'=>$url['path']]);

				if($result['cached']) {
					$warmed++;
				} else {
					$failed++;
				}

				$results[] = [
					'Requests'=>$url['requests'],
					'Host'=>$url['host'],
					'Path'=>substr($url['path'], 0, 52),
					'Code'=>$result['code'],
					'Seconds'=>$result['seconds'],
					'Cached'=>$result['cached'] ? 'yes' : 'no',
				];

				usleep($this->arguments['delay']);
			}

			$elapsed = round(microtime(TRUE) - $started, 1);

			if(!$this->arguments['quiet']) {
				print(arr2textTable($results));
			}

			print("\n" . $warmed . ' warmed, ' . $failed . ' not cached, ' . count($urls) . ' requested in ' . $elapsed . 's.' . "\n");

			return TRUE;
		}

			/*
				Against 127.0.0.1 with the Host header set, so warming costs no
				TLS handshake and cannot be affected by DNS.  It is the same
				request Apache would serve to a reader, and it writes the same
				cache file.
			*/

		public function request($args) {
			$host = $args['host'];
			$path = $args['path'];

			$context = stream_context_create([
				'http'=>[
					'method'=>'GET',
					'header'=>"Host: " . $host . "\r\nUser-Agent: GGCMS-cache-warmer\r\nConnection: close\r\n",
					'timeout'=>$this->arguments['timeout'],
					'ignore_errors'=>TRUE,
				],
			]);

			$started = microtime(TRUE);

			$body = @file_get_contents('http://127.0.0.1' . $path, FALSE, $context);

			$seconds = round(microtime(TRUE) - $started, 1);

			$code = 0;

			if(isset($http_response_header) && isset($http_response_header[0])) {
				$matches = [];

				if(preg_match('/ (\d{3}) /', $http_response_header[0], $matches)) {
					$code = (int) $matches[1];
				}
			}

			return [
				'code'=>$code ? $code : ($body === FALSE ? 'timeout' : '?'),
				'seconds'=>$seconds,
				'cached'=>$this->isCached(['host'=>$host, 'path'=>$path]),
			];
		}
	}

?>

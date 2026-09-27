<?php

	/*
		Builds a site's page cache without asking the server to do it.

		A cache key is a pure function of the host and the request path, and a
		cached page holds nothing naming the machine that rendered it, so the
		tree is portable: build it anywhere, ship it, serve it.  That is worth
		doing because the server has one core and revoltlib has 12,545
		published pages, each costing six seconds or more to render under the
		load it is already carrying.  The same work on an idle workstation is
		minutes.

		Nothing here writes a cache file.  The renderer runs the ordinary
		request pipeline and lets PageCache write, exactly as index.php does,
		so what lands on disk is produced by the same code that produces it in
		production -- rather than by a second implementation that agrees with
		it today and drifts tomorrow.

		Three modes, because each is small on its own:

		  --list          print every cacheable path for the domain, one per
		                  line, from ORMSiteMap -- the same enumeration that
		                  builds the public sitemap
		  --url=<path>    render exactly one path and exit
		  (neither)       list, then spawn --url workers, --jobs at a time

		One page per process, deliberately.  index.php ends in exit(), the
		render pipeline carries static and global state, and a run touching
		twelve thousand pages is the worst possible place to discover which
		piece of it leaks.  Process startup is tens of milliseconds against a
		render of hundreds, so the isolation costs little and is worth more.
		It also means no pcntl, so this runs on Windows as readily as Linux --
		which is the entire point, the workstation being the machine with the
		spare cores.
	*/

	class PageCacheWarmer {
		public $argv;
		public $options;
		

			// Construction
			// -------------------------------------------------

		public function __construct($args) {
			$this->argv = $args['argv'];
			$this->options = $this->ParseOptions();
		}

		public function ParseOptions() {
			$options = [
				'domain'=>'',
				'url'=>'',
				'format'=>'html',
				'list'=>FALSE,
				'limit'=>0,
				'jobs'=>1,
				'skip-cached'=>FALSE,
				'dry-run'=>FALSE,
				'quiet'=>FALSE,
			];

			foreach(array_slice((array) $this->argv, 1) as $argument) {
				if(substr($argument, 0, 2) !== '--') {
					continue;
				}

				$argument = substr($argument, 2);
				$value = TRUE;

				if(strpos($argument, '=') !== FALSE) {
					list($argument, $value) = explode('=', $argument, 2);
				}

				if(array_key_exists($argument, $options)) {
					$options[$argument] = is_string($value) ? $value : TRUE;
				}
			}

			$options['format'] = strtolower((string) $options['format']);

				/*
					One format per run.  PageCache decides a cached file's
					extension from the format the request resolved to, so two
					formats in one pass would mean two different files per entry
					and two different render costs, and reporting them together
					would say nothing useful about either.
				*/

			if(!array_key_exists($options['format'], $this->FormatSuffixes())) {
				$this->Fail('--format must be one of: ' . implode(', ', array_keys($this->FormatSuffixes())));
			}

			$options['limit'] = (int) $options['limit'];
			$options['jobs'] = max(1, (int) $options['jobs']);

			return $options;
		}

			// Entry point
			// -------------------------------------------------

		public function Warm() {
			if(!strlen($this->options['domain'])) {
				$this->Fail('--domain is required, and must be a host the configuration knows: --domain=revoltlib.com');
			}

			if(strlen($this->options['url'])) {
				return $this->RenderOne($this->options['url']);
			}

			if($this->options['list']) {
				return $this->PrintPaths();
			}

			return $this->Orchestrate();
		}

			// Enumeration
			// -------------------------------------------------

		/*
			ORMSiteMap already answers "every published page of this site",
			filtered by Publish at all seven levels, and it is what generates
			the sitemap search engines are handed.  Asking it is both less code
			than a second query and less opportunity to disagree with the one
			that already exists.
		*/

		public function PrintPaths() {
			foreach($this->CollectPaths() as $path) {
				print($path . "\n");
			}

			return TRUE;
		}

		/*
			What each format is asked for, and what it lands on disk as.

			An entry is a directory path ending in a slash, and CacheLocation
			turns that into index.html.  Every other format is a script inside
			that directory -- view.brf for braille -- and caches as the request
			plus the format's own suffix, so view.brf becomes view.brf.brf.
			That is the same shape style.php has always had, where
			/css/view/display.css caches as display.css.css.

			Braille is the one worth warming after HTML: it is the most
			expensive render the engine performs, something is crawling
			view.brf?mode=dotted across the library, and it is the format whose
			missing-glyph faults are still open.
		*/

		public function FormatSuffixes() {
			return [
				'html'=>'',
				'brf'=>'view.brf',
			];
		}

		public function FormatPath($path) {
			$suffix = $this->FormatSuffixes()[$this->options['format']];

			if(!strlen($suffix)) {
				return $path;
			}

			return $path . $suffix;
		}

		public function CollectPaths() {
			$handler = $this->BootHandler('/');

			$this->RequireDatabase($handler);

			ggreq('classes/Database/ORMSiteMap.php');

				/*
					ORMSiteMap takes the DBAccess object rather than the handler,
					as SimpleORMSiteMap does.  Empty arguments are deliberate:
					`page` would narrow to one second-level code and `perpage`
					would paginate, and warming wants neither -- every URL the
					site has, which is what this query answers when asked for
					nothing in particular.
				*/

			$sitemap = new ORMSiteMap(['dbaccessobject'=>$handler->db_access]);
			$rows = $sitemap->GetEntrySiteMapCodes([]);

			$paths = ['/'];

				/*
					From E2, not E1.

					E1 is the site's own master entry -- the query joins it on
					Childid = 0 -- and it is the root of the tree rather than a
					step through it.  Including it produced
					/RevoltLib.com/anarchism/ where the site serves /anarchism/,
					which the cache on the server settles: its files sit at
					revoltlib.com/feminism/..., with no master code between the
					host and the first real segment.
				*/

			foreach((array) $rows as $row) {
				$codes = [];

				for($level = 2; $level <= 7; $level++) {
					$code = $row['E' . $level . '_Code'];

					if(!strlen((string) $code)) {
						break;
					}

					$codes[] = $code;
				}

				if(!count($codes)) {
					continue;
				}

					/*
						Every ancestor, not only the row's deepest entry.

						A sitemap row names a path down to its leaf, so a page
						with children -- /hindi/nouns-animals-part-1/ above its
						words -- never had a row of its own and was never warmed.
						On 13 September 2026 earthfluent's rebuilt tree held 181
						lesson pages against the live cache's 343, and every
						lesson with words under it was among the missing.
					*/

				for($depth = 1; $depth <= count($codes); $depth++) {
					$paths[] = '/' . implode('/', array_slice($codes, 0, $depth)) . '/';
				}
			}

			$paths = array_values(array_unique($paths));

			foreach($paths as $key => $path) {
				$paths[$key] = $this->FormatPath($path);
			}

			if($this->options['limit'] > 0) {
				$paths = array_slice($paths, 0, $this->options['limit']);
			}

			return $paths;
		}

			// Rendering
			// -------------------------------------------------

		/*
			index.php, without the exit and without sending anything to a
			visitor who is not there.  The buffer is still the only place a
			finished page exists, and PageCache is still the only thing that
			decides whether it may be stored.
		*/

		public function RenderOne($path) {
			$handler = $this->BootHandler($path);

			ob_start();

			try {
				$handler->HandleRequest();
			} catch (Throwable $exception) {
				ob_end_clean();
				fwrite(STDERR, 'render failed ' . $path . ': ' . $exception->getMessage() . "\n");

				return FALSE;
			}

			$output = ob_get_contents();
			ob_end_clean();

			if(!class_exists('PageCache')) {
				ggreq('classes/Cache/PageCache.php');
			}

			$page_cache = new PageCache(['handler'=>$handler]);
			$written = $page_cache->WriteCache(['output'=>$output]);

			if(!$this->options['quiet']) {
				print(($written ? 'wrote  ' : 'refused') . ' ' . strlen($output) . "\t" . $path . "\n");
			}

			return $written;
		}

		/*
			The request the render pipeline believes it is answering.

			SCRIPT_URL and REDIRECT_URL are set by mod_rewrite in production and
			read in twenty-five places, so they are set here too.  HTTPS matters
			more than it looks: it is read in eighty-three places, and a page
			rendered without it carries http:// links into a cache that will be
			served over https.
		*/

		public function BootHandler($path) {
			$host = $this->options['domain'];

			$_SERVER['HTTPS'] = 'on';
			$_SERVER['HTTP_HOST'] = $host;
			$_SERVER['SERVER_NAME'] = $host;
			$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
			$_SERVER['REQUEST_METHOD'] = 'GET';
			$_SERVER['REQUEST_URI'] = $path;
			$_SERVER['SCRIPT_URL'] = $path;
			$_SERVER['REDIRECT_URL'] = $path;
			$_SERVER['QUERY_STRING'] = '';
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
			$_SERVER['REQUEST_TIME_FLOAT'] = microtime(TRUE);

			$_GET = [];
			$_POST = [];
			$_COOKIE = [];

			require_once(GGCMS_DIR . 'classes/StandardLibraries.php');

			return new Handler();
		}

			// Orchestration
			// -------------------------------------------------

		public function Orchestrate() {
			$paths = $this->CollectPaths();

			if($this->options['skip-cached']) {
				$paths = $this->DropAlreadyCached($paths);
			}

			$total = count($paths);

			print('warming ' . $total . ' page(s) for ' . $this->options['domain'] . ' with ' . $this->options['jobs'] . " job(s)\n");

			if($this->options['dry-run']) {
				foreach($paths as $path) {
					print("would warm\t" . $path . "\n");
				}

				return TRUE;
			}

			$started = microtime(TRUE);
			$running = [];
			$done = 0;

			while(count($paths) || count($running)) {
				while(count($paths) && count($running) < $this->options['jobs']) {
					$path = array_shift($paths);
					$worker = $this->SpawnWorker($path);

					if($worker) {
						$running[] = $worker;
					}
				}

				foreach($running as $key => $worker) {
					if(!$this->WorkerIsRunning($worker)) {
						$this->ReapWorker($worker);
						unset($running[$key]);
						$done++;
					}
				}

				$running = array_values($running);

				usleep(20000);
			}

			$elapsed = microtime(TRUE) - $started;

			printf("%d page(s) in %.1fs (%.2f pages/second)\n", $done, $elapsed, $done > 0 ? $done / max($elapsed, 0.001) : 0);

			return TRUE;
		}

		public function SpawnWorker($path) {
			$command = escapeshellarg(PHP_BINARY)
				. ' ' . escapeshellarg($this->WorkerScript())
				. ' --domain=' . escapeshellarg($this->options['domain'])
				. ' --url=' . escapeshellarg($path)
				. ' --format=' . escapeshellarg($this->options['format']);

			$descriptors = [
				1=>['pipe', 'w'],
				2=>['pipe', 'w'],
			];

			$pipes = [];
			$process = @proc_open($command, $descriptors, $pipes);

			if(!is_resource($process)) {
				fwrite(STDERR, 'could not start a worker for ' . $path . "\n");

				return FALSE;
			}

			stream_set_blocking($pipes[1], FALSE);
			stream_set_blocking($pipes[2], FALSE);

			return ['process'=>$process, 'pipes'=>$pipes, 'path'=>$path];
		}

		public function WorkerIsRunning($worker) {
			$status = proc_get_status($worker['process']);

			return (bool) $status['running'];
		}

		public function ReapWorker($worker) {
			foreach([1, 2] as $descriptor) {
				$output = stream_get_contents($worker['pipes'][$descriptor]);

				if(strlen(trim((string) $output)) && !$this->options['quiet']) {
					fwrite($descriptor === 2 ? STDERR : STDOUT, $output);
				}

				fclose($worker['pipes'][$descriptor]);
			}

			return proc_close($worker['process']);
		}

		public function WorkerScript() {
			return GGCMS_CLI_DIR . 'scripts/internal/page_cache/warm_page_cache.php';
		}

			// Housekeeping
			// -------------------------------------------------

		/*
			Asking the disk rather than the database.  A page is cached exactly
			when its file exists -- that is the whole of the serving rule in
			.htaccess -- so the file is the authority on whether warming it
			would achieve anything.
		*/

		public function DropAlreadyCached($paths) {
			$handler = $this->BootHandler('/');

			if(!class_exists('PageCache')) {
				ggreq('classes/Cache/PageCache.php');
			}

			$remaining = [];

			foreach($paths as $path) {
				$_SERVER['REQUEST_URI'] = $path;
				$_SERVER['SCRIPT_URL'] = $path;
				$_SERVER['REDIRECT_URL'] = $path;

				$page_cache = new PageCache(['handler'=>$handler]);
				$location = $page_cache->CacheLocation();

				if($location === FALSE || !is_file($location)) {
					$remaining[] = $path;
				}
			}

			return $remaining;
		}

		/*
			Say the useful thing.

			Without a database the render pipeline fails deep inside itself,
			as an uncaught call on null several frames down, followed by the
			engine trying to log the failure to the database that is not there.
			The stack trace is accurate and tells the reader nothing they can
			act on.  The actionable fact is nearly always the same one, and it
			is short.
		*/

		public function RequireDatabase($handler) {
			if(isset($handler->db_access) && $handler->db_access && $handler->db_access->db_link) {
				return TRUE;
			}

			$this->Fail(
				'no database connection for ' . $this->options['domain'] . '.' . "\n"
				. 'This tool renders pages, so it needs the site data locally: a MySQL or MariaDB' . "\n"
				. 'server this machine can reach, holding a database named for the site, with the' . "\n"
				. 'connection details in php.ini (mysqli.default_host and friends) exactly as the' . "\n"
				. 'server has them.  Import a dump of the live database and try again.'
			);
		}

		public function Fail($message) {
			fwrite(STDERR, $message . "\n");
			exit(1);
		}
	}

?>

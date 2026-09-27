<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');

	/*
		What the front end is serving, and to whom.

		human_stats.php counts people; this counts requests.  It reads nginx's
		access log, which sees everything -- cache hits nginx answers from disk,
		refusals, and the renders it hands to Apache -- in the ggcms log format
		from etc/nginx/nginx.conf:

			addr - user [time] "request" status bytes "referer" "agent" host=H rt=R urt=U up=A

		up=- means nginx answered on its own: a page-cache hit, a static file,
		a refusal or a redirect.  Anything else reached the engine, which is the
		expensive part on this host.

		Narrow by default: the last hour, a summary, the top ten.  The log runs
		to millions of lines a day, and the tool seeks to the start of the window
		rather than reading from the top, so an hour costs an hour of reading.

		The window may begin before the current log does.  Then the rotated log
		(access.log.1) is read first; older, compressed rotations are not, and
		the summary says where its data actually begins.
	*/

	class TrafficAnalytics {
		use CLIAccess;
		
		public $window_start;
		public $arguments;
		public $earliest_seen;

			// Entry Point
			// -----------------------------------------------

		public function reportTraffic() {
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$this->bannerMessage();

			$this->window_start = time() - ($this->arguments['minutes'] * 60);

			$requests = $this->loadRequests();

			print('nginx traffic, ' . $this->describeWindow() . $this->describeFilters() . "\n\n");

			if(!$requests) {
				print('No requests match.' . "\n\n");

				return FALSE;
			}

			foreach($this->arguments['reports'] as $report) {
				$method = 'report' . ucfirst($report);
				$this->$method(['requests'=>$requests]);
			}

			return TRUE;
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'reports'=>['summary'],
				'minutes'=>60,
				'host'=>'',
				'agent'=>'',
				'path'=>'',
				'status'=>'',
				'misses'=>FALSE,
				'pages'=>FALSE,
				'top'=>10,
				'log'=>'/var/log/nginx/access.log',
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if(!preg_match('/^--([a-z]+)(?:=(.*))?$/', $argument, $matches)) {
					if($argument === '-h') {
						$arguments['help'] = TRUE;
					}

					continue;
				}

				$name = $matches[1];
				$value = array_key_exists(2, $matches) ? $matches[2] : '';

				if($name === 'help' || $name === 'misses' || $name === 'pages') {
					$arguments[$name] = TRUE;
				} else if($name === 'report') {
					$arguments['reports'] = array_values(array_filter(array_map('trim', explode(',', strtolower($value)))));
				} else if($name === 'minutes' || $name === 'top') {
					$arguments[$name] = max(1, (int)$value);
				} else if($name === 'hours') {
					$arguments['minutes'] = max(1, (int)round((float)$value * 60));
				} else if(array_key_exists($name, $arguments)) {
					$arguments[$name] = $value;
				} else {
					print('Unknown argument --' . $name . "; --help lists them.\n");
					exit(1);
				}
			}

			if($arguments['reports'] === ['all']) {
				$arguments['reports'] = $this->allReports();
			}

			foreach($arguments['reports'] as $report) {
				if(!in_array($report, $this->allReports(), TRUE)) {
					print('Unknown report "' . $report . '"; --help lists them.' . "\n");
					exit(1);
				}
			}

			return $this->arguments = $arguments;
		}

		public function allReports() {
			return ['summary', 'hosts', 'crawlers', 'agents', 'paths', 'statuses', 'minutes'];
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Traffic Analytics' . "\n\n");
			print('  traffic_stats.php [--report=summary,...] [--minutes=60 | --hours=6] [--top=10]' . "\n");
			print('                    [--host=revoltlib] [--agent=TEXT] [--path=/PREFIX/] [--status=403]' . "\n");
			print('                    [--misses] [--pages] [--log=/var/log/nginx/access.log]' . "\n\n");
			print('  Reads the nginx access log.  Read-only.  Default: the last hour, summary only.' . "\n\n");
			print('  Reports (comma-separated, or --report=all):' . "\n");
			print('    summary   requests by outcome, page-cache hit rate, renders per second' . "\n");
			print('    hosts     each site: requests, pages served from cache, renders, refusals, 404s' . "\n");
			print('    crawlers  self-declared crawlers NOT being refused, by renders they cost' . "\n");
			print('    agents    user agents, by requests, renders and refusals' . "\n");
			print('    paths     the paths costing the most renders, query strings grouped' . "\n");
			print('    statuses  status codes' . "\n");
			print('    minutes   requests and renders per minute, with a bar' . "\n\n");
			print('  Filters narrow every report: --host and --agent match text anywhere, --path a prefix,' . "\n");
			print('  --status an exact code.  --misses keeps only requests that reached the engine;' . "\n");
			print('  --pages drops static files and the beacon.' . "\n\n");

			return TRUE;
		}

		public function bannerMessageText() {
			return 'Traffic Analytics';
		}

		public function describeWindow() {
			$label = ($this->arguments['minutes'] % 60 === 0)
				? 'the last ' . ($this->arguments['minutes'] / 60) . ' hour(s)'
				: 'the last ' . $this->arguments['minutes'] . ' minute(s)';

			return $label . ' to ' . gmdate('H:i', time()) . ' UTC';
		}

		public function describeFilters() {
			$filters = [];

			foreach(['host', 'agent', 'path', 'status'] as $name) {
				if($this->arguments[$name] !== '') {
					$filters[] = $name . ' ' . $this->arguments[$name];
				}
			}

			if($this->arguments['misses']) {
				$filters[] = 'engine renders only';
			}

			if($this->arguments['pages']) {
				$filters[] = 'pages only';
			}

			return $filters ? ' (' . implode(', ', $filters) . ')' : '';
		}

			// Reading the log
			// -----------------------------------------------

		public function loadRequests() {
			$requests = [];
			$this->earliest_seen = NULL;

			$files = [];
			$rotated = $this->arguments['log'] . '.1';

			if(is_file($rotated) && $this->firstTime(['file'=>$this->arguments['log']]) > $this->window_start) {
				$files[] = $rotated;
			}

			$files[] = $this->arguments['log'];

			foreach($files as $file) {
				$handle = @fopen($file, 'r');

				if(!$handle) {
					print('Cannot read ' . $file . "\n\n");
					continue;
				}

				$this->seekToTime(['handle'=>$handle, 'time'=>$this->window_start]);

				while(($line = fgets($handle)) !== FALSE) {
					$request = $this->parseLine(['line'=>$line]);

					if(!$request || $request['time'] < $this->window_start) {
						continue;
					}

					if($this->earliest_seen === NULL) {
						$this->earliest_seen = $request['time'];
					}

					if($this->matches(['request'=>$request])) {
						$requests[] = $request;
					}
				}

				fclose($handle);
			}

			return $requests;
		}

		public function firstTime($args) {
			$handle = @fopen($args['file'], 'r');

			if(!$handle) {
				return 0;
			}

			$request = $this->parseLine(['line'=>(string)fgets($handle)]);
			fclose($handle);

			return $request ? $request['time'] : 0;
		}

			/*
				Binary search on the byte offset.  The log is written in time
				order, so the first line at or after the window's start can be
				found in a few dozen reads instead of a scan of the whole file.
			*/

		public function seekToTime($args) {
			$handle = $args['handle'];
			$stat = fstat($handle);
			$low = 0;
			$high = $stat['size'];

			while(($high - $low) > 65536) {
				$middle = intdiv($low + $high, 2);
				fseek($handle, $middle);
				fgets($handle);

				$request = NULL;

				for($tries = 0; $tries < 5 && !$request; $tries++) {
					$line = fgets($handle);

					if($line === FALSE) {
						break;
					}

					$request = $this->parseLine(['line'=>$line]);
				}

				if($request && $request['time'] < $args['time']) {
					$low = $middle;
				} else {
					$high = $middle;
				}
			}

			fseek($handle, $low);

			if($low > 0) {
				fgets($handle);
			}

			return TRUE;
		}

		public function parseLine($args) {
			if(!preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+)[^"]*" (\d{3}) (\d+) "(?:[^"\\\\]|\\\\.)*" "((?:[^"\\\\]|\\\\.)*)" host=(\S*) rt=(\S*) urt=(\S*) up=(\S*)\s*$/', $args['line'], $matches)) {
				return FALSE;
			}

			$time = DateTime::createFromFormat('d/M/Y:H:i:s O', $matches[2]);

			if(!$time) {
				return FALSE;
			}

			$target = $matches[4];
			$path = strtok($target, '?');

			return [
				'time'=>$time->getTimestamp(),
				'address'=>$matches[1],
				'method'=>$matches[3],
				'target'=>$target,
				'path'=>($path === FALSE) ? $target : $path,
				'query'=>strpos($target, '?') !== FALSE,
				'status'=>(int)$matches[5],
				'bytes'=>(int)$matches[6],
				'agent'=>$matches[7],
				'host'=>$matches[8],
				'seconds'=>(float)$matches[9],
				'engine'=>($matches[11] !== '-' && $matches[11] !== ''),
			];
		}

		public function matches($args) {
			$request = $args['request'];
			$arguments = $this->arguments;

			if($arguments['host'] !== '' && stripos($request['host'], $arguments['host']) === FALSE) {
				return FALSE;
			}

			if($arguments['agent'] !== '' && stripos($request['agent'], $arguments['agent']) === FALSE) {
				return FALSE;
			}

			if($arguments['path'] !== '' && strpos($request['path'], $arguments['path']) !== 0) {
				return FALSE;
			}

			if($arguments['status'] !== '' && (string)$request['status'] !== $arguments['status']) {
				return FALSE;
			}

			if($arguments['misses'] && !$request['engine']) {
				return FALSE;
			}

			if($arguments['pages'] && !$this->isPage(['request'=>$request])) {
				return FALSE;
			}

			return TRUE;
		}

			// Classifying
			// -----------------------------------------------

			/*
				A page is a GET for something that is not a static asset and not
				the beacon.  Only pages can be page-cache hits, so the hit rate
				is measured over pages alone; counting a PNG served from disk as
				a "hit" would flatter it.
			*/

		public function isPage($args) {
			$request = $args['request'];

			if($request['method'] !== 'GET') {
				return FALSE;
			}

			if(preg_match('/\.(?:css|js|png|jpe?g|gif|ico|svg|webp|woff2?|ttf|eot|txt|xml)$/i', $request['path'])) {
				return FALSE;
			}

			return strpos($request['path'], '/humanbeacon') !== 0;
		}

		public function outcome($args) {
			$request = $args['request'];
			$status = $request['status'];

				// nginx refuses cached pages itself and Apache refuses the rest, so
				// a refusal can arrive with or without an upstream
			if($status === 403) {
				return 'refused';
			}

			if($status >= 300 && $status < 400) {
				return 'redirect';
			}

			if($status === 404) {
				return 'not found';
			}

			if($status === 503 || $status === 499) {
				return $status === 503 ? 'throttled (503)' : 'client gave up (499)';
			}

			if($request['engine']) {
				return 'rendered by engine';
			}

			return $this->isPage(['request'=>$request]) ? 'page from cache' : 'static file';
		}

		public function isDeclaredCrawler($args) {
			return (bool)preg_match('/bot|spider|crawler|slurp|headless|scrap|fetch|python|curl|wget|go-http|java\//i', $args['agent']);
		}

		public function agentName($args) {
			$agent = $args['agent'];

			if(preg_match('/(?:compatible;\s*)?([A-Za-z][A-Za-z0-9._-]*(?:bot|spider|crawler|Slurp)[A-Za-z0-9._-]*)/i', $agent, $matches)) {
				return $matches[1];
			}

			return $this->shorten(['text'=>$agent === '' ? '(none)' : $agent, 'length'=>60]);
		}

			// Reports
			// -----------------------------------------------

		public function reportSummary($args) {
			$requests = $args['requests'];
			$outcomes = [];
			$pages = 0;
			$page_hits = 0;
			$renders = 0;
			$render_queries = 0;
			$render_seconds = 0.0;

			foreach($requests as $request) {
				$outcome = $this->outcome(['request'=>$request]);
				$outcomes[$outcome] = ($outcomes[$outcome] ?? 0) + 1;

				if($request['engine']) {
					$renders++;
					$render_seconds += $request['seconds'];
					$render_queries += $request['query'] ? 1 : 0;
				}

				if($this->isPage(['request'=>$request]) && $request['status'] === 200) {
					$pages++;
					$page_hits += $request['engine'] ? 0 : 1;
				}
			}

			arsort($outcomes);

			$rows = $this->bucketRows(['buckets'=>$outcomes, 'label'=>'Outcome', 'unit'=>'Requests']);
			$this->printTable(['title'=>'Outcomes', 'rows'=>$rows]);

			$span = max(1, $this->arguments['minutes'] * 60);

			if($this->earliest_seen !== NULL && $this->earliest_seen > $this->window_start + 120) {
				$span = max(1, time() - $this->earliest_seen);
			}

			$measures = [
				['Measure'=>'Requests', 'Value'=>number_format(count($requests))],
				['Measure'=>'Pages (200) served from cache', 'Value'=>$pages ? $this->percent($page_hits / $pages) . ' of ' . number_format($pages) : '-'],
				['Measure'=>'Engine renders', 'Value'=>number_format($renders)],
				['Measure'=>'Engine renders per second', 'Value'=>number_format($renders / $span, 2)],
				['Measure'=>'Renders with a query string', 'Value'=>$renders ? $this->percent($render_queries / $renders) . ' (never cacheable)' : '-'],
				['Measure'=>'Median render time', 'Value'=>$renders ? number_format($this->medianRenderSeconds(['requests'=>$requests]), 2) . 's' : '-'],
			];

			if($this->earliest_seen !== NULL && $this->earliest_seen > $this->window_start + 120) {
				$measures[] = ['Measure'=>'NOTE', 'Value'=>'log only reaches back to ' . gmdate('H:i', $this->earliest_seen) . ' UTC'];
			}

			$this->printTable(['title'=>'Summary', 'rows'=>$measures]);

			print('Add --report=hosts,crawlers,paths for detail; --help lists everything.' . "\n\n");

			return TRUE;
		}

		public function medianRenderSeconds($args) {
			$times = [];

			foreach($args['requests'] as $request) {
				if($request['engine']) {
					$times[] = $request['seconds'];
				}
			}

			sort($times);

			return $times ? $times[intdiv(count($times), 2)] : 0;
		}

		public function reportHosts($args) {
			$hosts = [];

			foreach($args['requests'] as $request) {
				$host = $request['host'] === '' ? '(none)' : $request['host'];

				if(!array_key_exists($host, $hosts)) {
					$hosts[$host] = ['Host'=>$host, 'Requests'=>0, 'Pages'=>0, 'From cache'=>0, 'Renders'=>0, 'Refused'=>0, '404'=>0];
				}

				$row = &$hosts[$host];
				$row['Requests']++;
				$row['Renders'] += $request['engine'] ? 1 : 0;

				if($this->isPage(['request'=>$request]) && $request['status'] === 200) {
					$row['Pages']++;
					$row['From cache'] += $request['engine'] ? 0 : 1;
				}

				$outcome = $this->outcome(['request'=>$request]);
				$row['Refused'] += ($outcome === 'refused') ? 1 : 0;
				$row['404'] += ($request['status'] === 404) ? 1 : 0;
				unset($row);
			}

			usort($hosts, function($a, $b) { return $b['Renders'] <=> $a['Renders']; });

			foreach($hosts as &$row) {
				$row['From cache'] = $row['Pages'] ? $this->percent($row['From cache'] / $row['Pages']) : '-';
			}
			unset($row);

			$this->printTable(['title'=>'Hosts, by engine renders', 'rows'=>array_slice($hosts, 0, $this->arguments['top'])]);

			return TRUE;
		}

			/*
				The list worth acting on: agents that say they are crawlers and
				are still being served, ordered by what they cost.  An agent that
				appears here with many renders is the candidate for the refusal
				list -- if it is not a search engine that sends readers.
			*/

		public function reportCrawlers($args) {
			$crawlers = [];

			foreach($args['requests'] as $request) {
				if(!$this->isDeclaredCrawler(['agent'=>$request['agent']])) {
					continue;
				}

				$name = $this->agentName(['agent'=>$request['agent']]);

				if(!array_key_exists($name, $crawlers)) {
					$crawlers[$name] = ['Crawler'=>$name, 'Requests'=>0, 'Renders'=>0, 'Refused'=>0, 'Served'=>0];
				}

				$crawlers[$name]['Requests']++;
				$crawlers[$name]['Renders'] += $request['engine'] ? 1 : 0;

				if($this->outcome(['request'=>$request]) === 'refused') {
					$crawlers[$name]['Refused']++;
				} else if($request['status'] < 400) {
					$crawlers[$name]['Served']++;
				}
			}

			$crawlers = array_filter($crawlers, function($row) { return $row['Served'] > 0; });
			usort($crawlers, function($a, $b) { return [$b['Renders'], $b['Served']] <=> [$a['Renders'], $a['Served']]; });

			$this->printTable(['title'=>'Declared crawlers still being served, by engine renders', 'rows'=>array_slice(array_values($crawlers), 0, $this->arguments['top'])]);

			return TRUE;
		}

		public function reportAgents($args) {
			$agents = [];

			foreach($args['requests'] as $request) {
				$name = $this->agentName(['agent'=>$request['agent']]);

				if(!array_key_exists($name, $agents)) {
					$agents[$name] = ['Agent'=>$name, 'Requests'=>0, 'Renders'=>0, 'Refused'=>0];
				}

				$agents[$name]['Requests']++;
				$agents[$name]['Renders'] += $request['engine'] ? 1 : 0;
				$agents[$name]['Refused'] += ($this->outcome(['request'=>$request]) === 'refused') ? 1 : 0;
			}

			usort($agents, function($a, $b) { return [$b['Renders'], $b['Requests']] <=> [$a['Renders'], $a['Requests']]; });

			$this->printTable(['title'=>'User agents, by engine renders', 'rows'=>array_slice($agents, 0, $this->arguments['top'])]);

			return TRUE;
		}

		public function reportPaths($args) {
			$paths = [];

			foreach($args['requests'] as $request) {
				if(!$request['engine']) {
					continue;
				}

				$key = $request['host'] . $request['path'] . ($request['query'] ? '?...' : '');

				if(!array_key_exists($key, $paths)) {
						// arr2textTable caps a column near fifty characters
					$paths[$key] = ['Path'=>$this->shorten(['text'=>$key, 'length'=>48]), 'Renders'=>0, 'Seconds'=>0.0];
				}

				$paths[$key]['Renders']++;
				$paths[$key]['Seconds'] += $request['seconds'];
			}

			usort($paths, function($a, $b) { return $b['Renders'] <=> $a['Renders']; });

			$rows = [];

			foreach(array_slice($paths, 0, $this->arguments['top']) as $row) {
				$row['Seconds'] = number_format($row['Seconds'], 1);
				$rows[] = $row;
			}

			$this->printTable(['title'=>'Paths costing the most renders (query strings grouped as ?...)', 'rows'=>$rows]);

			return TRUE;
		}

		public function reportStatuses($args) {
			$statuses = [];

			foreach($args['requests'] as $request) {
				$statuses[$request['status']] = ($statuses[$request['status']] ?? 0) + 1;
			}

			arsort($statuses);

			$this->printTable(['title'=>'Status codes', 'rows'=>$this->bucketRows(['buckets'=>array_slice($statuses, 0, $this->arguments['top'], TRUE), 'label'=>'Status', 'unit'=>'Requests'])]);

			return TRUE;
		}

		public function reportMinutes($args) {
			$minutes = [];

			foreach($args['requests'] as $request) {
				$minute = gmdate('H:i', $request['time']);

				if(!array_key_exists($minute, $minutes)) {
					$minutes[$minute] = ['requests'=>0, 'renders'=>0];
				}

				$minutes[$minute]['requests']++;
				$minutes[$minute]['renders'] += $request['engine'] ? 1 : 0;
			}

			ksort($minutes);

			$most = 0;

			foreach($minutes as $minute) {
				$most = max($most, $minute['renders']);
			}

			$rows = [];

			foreach($minutes as $label => $minute) {
				$rows[] = ['Minute (UTC)'=>$label, 'Requests'=>$minute['requests'], 'Renders'=>$minute['renders'], ''=>$this->bar(['value'=>$minute['renders'], 'most'=>$most])];
			}

			$this->printTable(['title'=>'Per minute, bar is renders', 'rows'=>$rows]);

			return TRUE;
		}

			// Formatting
			// -----------------------------------------------

		public function printTable($args) {
			if(array_key_exists('title', $args)) {
				print($args['title'] . "\n");
			}

			if(!$args['rows']) {
				print('  (nothing)' . "\n\n");

				return FALSE;
			}

			print(arr2textTable(array_values($args['rows'])) . "\n");

			return TRUE;
		}

		public function bucketRows($args) {
			$total = array_sum($args['buckets']);
			$most = $args['buckets'] ? max($args['buckets']) : 0;
			$rows = [];

			foreach($args['buckets'] as $name => $count) {
				$rows[] = [
					$args['label']=>(string)$name,
					$args['unit']=>$count,
					'Share'=>$total ? $this->percent($count / $total) : '-',
					''=>$this->bar(['value'=>$count, 'most'=>$most]),
				];
			}

			return $rows;
		}

		public function percent($value) {
			return number_format($value * 100, 1) . '%';
		}

		public function bar($args) {
			if(!$args['most']) {
				return '';
			}

			return str_repeat('#', (int)round(30 * $args['value'] / $args['most']));
		}

		public function shorten($args) {
			$text = $args['text'];
			$length = $args['length'];

			return (strlen($text) > $length) ? substr($text, 0, $length - 3) . '...' : $text;
		}
	}

?>

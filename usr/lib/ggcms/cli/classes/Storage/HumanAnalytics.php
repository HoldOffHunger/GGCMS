<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');

	/*
		Who actually reads the sites.

		humanbeacon.js posts once per page view, and only after the reader
		first scrolls, types, points or touches.  UserTracking::RecordHumanBeacon
		writes each one to <domain>/stats/YYYY-Mon_humans.txt:

			2026-Sep-13 20:00:00 [1789329600] 203.0.113.9 /page/ https://ref/ 1920x1080 en-US America/New_York scroll 1234

		The request log counts fetches, most of them scrapers, and never sees a
		cached page at all.  This reads the beacon log and answers questions
		about people instead.

		## Visitors and visits

		A beacon carries no cookie and no account, so a visitor is the address,
		screen, language and timezone taken together, shown as an eight-letter
		id.  Two readers on one router with identical machines merge into one;
		a phone that changes network splits into two.  That is the error bar,
		and it is a far smaller one than the request log's.

		A visit is one visitor's run of page views with no gap longer than
		thirty minutes -- the convention the big analytics packages use, so the
		numbers mean what people expect them to.

		## Periods

		The default is the last seven days, compared against the seven before.
		Visits are built from both periods together and belong to the period
		they started in, so a visit is never cut in half at the boundary.

		Every _humans.txt file in the stats directory is read and filtered by
		timestamp rather than chosen by filename.  Files are named with
		date('o-M'), whose ISO year is the previous year for the first days of
		some Januaries, so a filename is not a reliable date -- and the archiver
		keeps only a few months on disk, so reading them all costs nothing.
	*/

	class HumanAnalytics {
		use CLIAccess;

		const VISIT_GAP_SECONDS = 1800;

			// Entry Point
			// -----------------------------------------------

		public function reportHumans() {
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$this->bannerMessage();

			if(!is_dir(GGCMS_LOG_DIR)) {
				print('No log directory at ' . GGCMS_LOG_DIR . "\n\n");

				return FALSE;
			}

			$this->setPeriods();

			if(!$this->arguments['domain']) {
				return $this->reportEveryDomain();
			}

			return $this->reportOneDomain(['domain'=>$this->arguments['domain']]);
		}

		public function reportOneDomain($args) {
			$domain = $args['domain'];

			$views = $this->loadViews(['domain'=>$domain]);

			if(!$views) {
				print('No beacon views for ' . $domain . ' ' . $this->describePeriod() . ".\n\n");

				return FALSE;
			}

			$visits = $this->buildVisits(['views'=>$views]);
			$current = $this->filterVisits(['visits'=>$this->visitsInPeriod(['visits'=>$visits, 'period'=>$this->period])]);
			$previous = $this->previous_period ? $this->filterVisits(['visits'=>$this->visitsInPeriod(['visits'=>$visits, 'period'=>$this->previous_period])]) : [];

			print($domain . ', ' . $this->describePeriod() . $this->describeFilters() . "\n\n");

			if($this->bots_excluded) {
				print($this->bots_excluded . ' view(s) from declared crawlers or the retired scroll trigger left out; --bots keeps them.' . "\n\n");
			}

			if($this->farm_excluded) {
				print($this->farm_excluded . ' view(s) from the scripted-browser farm left out; --farm keeps them.' . "\n\n");
			}

			if(!$current) {
				print('No visits match.' . "\n\n");

				return FALSE;
			}

			foreach($this->arguments['reports'] as $report) {
				$method = 'report' . ucfirst($report);
				$this->$method(['visits'=>$current, 'previous'=>$previous, 'domain'=>$domain]);
			}

			return TRUE;
		}

			/*
				With no --domain, one row per site: the narrowest thing worth
				printing, and the way to see which site deserves a closer look.
			*/

		public function reportEveryDomain() {
			$rows = [];

			foreach($this->listDomains() as $domain) {
				$views = $this->loadViews(['domain'=>$domain]);

				if(!$views) {
					continue;
				}

				$visits = $this->buildVisits(['views'=>$views]);
				$current = $this->filterVisits(['visits'=>$this->visitsInPeriod(['visits'=>$visits, 'period'=>$this->period])]);
				$previous = $this->previous_period ? $this->filterVisits(['visits'=>$this->visitsInPeriod(['visits'=>$visits, 'period'=>$this->previous_period])]) : [];

				if(!$current && !$previous) {
					continue;
				}

				$now = $this->measure(['visits'=>$current]);
				$before = $this->measure(['visits'=>$previous]);

				$rows[] = [
					'Domain'=>$domain,
					'Views'=>$now['views'],
					'Visitors'=>$now['visitors'],
					'Visits'=>$now['visits'],
					'Pages/visit'=>$this->decimal($now['pages_per_visit']),
					'Bounce'=>$this->percent($now['bounce_rate']),
					'Visitors before'=>$this->previous_period ? $before['visitors'] : '-',
					'Change'=>$this->previous_period ? $this->change(['now'=>$now['visitors'], 'before'=>$before['visitors']]) : '-',
					'Crawlers out'=>$this->bots_excluded,
					'Farm out'=>$this->farm_excluded,
				];
			}

			print('Every site, ' . $this->describePeriod() . $this->describeFilters() . "\n\n");

			if(!$rows) {
				print('No beacon views anywhere yet.' . "\n\n");

				return FALSE;
			}

			usort($rows, function($a, $b) { return $b['Visitors'] <=> $a['Visitors']; });

			$this->printTable(['rows'=>$rows]);

			print('Add --domain=NAME for that site\'s reports; --help lists them.' . "\n\n");

			return TRUE;
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'domain'=>'',
				'reports'=>['summary'],
				'days'=>7,
				'month'=>'',
				'all'=>FALSE,
				'top'=>10,
				'page'=>'',
				'referrer'=>'',
				'device'=>'',
				'language'=>'',
				'timezone'=>'',
				'visitor'=>'',
				'bots'=>FALSE,
				'farm'=>FALSE,
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

				if($name === 'help' || $name === 'all' || $name === 'bots' || $name === 'farm') {
					$arguments[$name] = TRUE;
				} else if($name === 'report') {
					$arguments['reports'] = array_values(array_filter(array_map('trim', explode(',', strtolower($value)))));
				} else if($name === 'days' || $name === 'top') {
					$arguments[$name] = max(1, (int)$value);
				} else if(array_key_exists($name, $arguments)) {
					$arguments[$name] = $value;
				} else {
					print('Unknown argument --' . $name . "; --help lists them.\n");
					exit(1);
				}
			}

			if($arguments['visitor'] && $arguments['reports'] === ['summary']) {
				$arguments['reports'] = ['visitor'];
			}

			if($arguments['reports'] === ['all']) {
				$arguments['reports'] = $this->allReports();
			}

			foreach($arguments['reports'] as $report) {
				if(!in_array($report, array_merge($this->allReports(), ['visitor']), TRUE)) {
					print('Unknown report "' . $report . '"; --help lists them.' . "\n");
					exit(1);
				}
			}

			return $this->arguments = $arguments;
		}

		public function allReports() {
			return [
				'summary', 'days', 'pages', 'sections', 'landings', 'flows',
				'referrers', 'devices', 'languages', 'timezones', 'hours',
				'depth', 'loyalty', 'engagement', 'visitors',
			];
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Human Analytics' . "\n\n");
			print('  human_stats.php [--domain=NAME] [--report=summary,...] [--days=7 | --month=2026-Sep | --all]' . "\n");
			print('                  [--top=10] [--page=/PREFIX/] [--referrer=TEXT] [--device=phone|tablet|desktop]' . "\n");
			print('                  [--language=en] [--timezone=TEXT] [--visitor=ID]' . "\n\n");
			print('  Reads <domain>/stats/YYYY-Mon_humans.txt, written by humanbeacon.js.' . "\n");
			print('  Without --domain: one row per site.  Read-only.' . "\n\n");
			print('  Reports (comma-separated, or --report=all):' . "\n");
			print('    summary     views, visitors, visits, bounce, depth, arrivals; against the previous period' . "\n");
			print('    days        each day, with a bar' . "\n");
			print('    pages       most-read pages, with landings and exit rate' . "\n");
			print('    sections    the same, by first path segment' . "\n");
			print('    landings    pages visits start on, and how far those visits go' . "\n");
			print('    flows       page-to-page steps readers actually take' . "\n");
			print('    referrers   where visits come from: search, social, link, direct' . "\n");
			print('    devices     phone, tablet, desktop, and screen sizes' . "\n");
			print('    languages   browser languages' . "\n");
			print('    timezones   a location proxy, with no address lookup' . "\n");
			print('    hours       hour and weekday on the reader\'s own clock' . "\n");
			print('    depth       pages per visit and visit length' . "\n");
			print('    loyalty     visits per visitor, days active, returns' . "\n");
			print('    engagement  which interaction fired, and how soon' . "\n");
			print('    visitors    the most active visitors, with ids' . "\n");
			print('    visitor     one visitor\'s visits, page by page (needs --visitor=ID)' . "\n\n");
			print('  Filters keep whole visits: --page keeps visits that read anything under the prefix,' . "\n");
			print('  --referrer matches the site a visit arrived from.' . "\n\n");
			print('  Crawlers that name themselves -- a user agent saying bot, spider, crawler or' . "\n");
			print('  headless -- are left out unless --bots is given.' . "\n\n");
			print('  Views from the scripted-browser farm -- one exact screen, language and timezone,' . "\n");
			print('  see isFarm() -- are left out unless --farm is given.' . "\n\n");

			return TRUE;
		}

		public function bannerMessageText() {
			return 'Human Analytics';
		}

			// Periods
			// -----------------------------------------------

		public function setPeriods() {
			$this->previous_period = NULL;

			if($this->arguments['all']) {
				$this->period = ['start'=>0, 'end'=>PHP_INT_MAX, 'label'=>'all time'];

				return TRUE;
			}

			if($this->arguments['month']) {
				$start = DateTime::createFromFormat('!Y-M-d', $this->arguments['month'] . '-01');

				if(!$start) {
					print('--month wants a form like 2026-Sep.' . "\n");
					exit(1);
				}

				$end = (clone $start)->modify('+1 month');
				$before = (clone $start)->modify('-1 month');

				$this->period = ['start'=>$start->getTimestamp(), 'end'=>$end->getTimestamp(), 'label'=>$start->format('F Y')];
				$this->previous_period = ['start'=>$before->getTimestamp(), 'end'=>$start->getTimestamp(), 'label'=>$before->format('F Y')];

				return TRUE;
			}

			$now = time();
			$length = $this->arguments['days'] * 86400;

			$this->period = ['start'=>$now - $length, 'end'=>$now + 1, 'label'=>'the last ' . $this->arguments['days'] . ' days'];
			$this->previous_period = ['start'=>$now - (2 * $length), 'end'=>$now - $length, 'label'=>'the ' . $this->arguments['days'] . ' days before'];

			return TRUE;
		}

		public function describePeriod() {
			return 'in ' . $this->period['label'];
		}

		public function describeFilters() {
			$filters = [];

			foreach(['page', 'referrer', 'device', 'language', 'timezone', 'visitor'] as $name) {
				if($this->arguments[$name] !== '') {
					$filters[] = $name . ' ' . $this->arguments[$name];
				}
			}

			return $filters ? ' (' . implode(', ', $filters) . ')' : '';
		}

		public function visitsInPeriod($args) {
			$period = $args['period'];

			return array_values(array_filter($args['visits'], function($visit) use ($period) {
				return $visit['start'] >= $period['start'] && $visit['start'] < $period['end'];
			}));
		}

			// Reading the log
			// -----------------------------------------------

		public function listDomains() {
			$domains = [];

			foreach(scandir(GGCMS_LOG_DIR) as $domain) {
				if($domain !== '.' && $domain !== '..' && is_dir(GGCMS_LOG_DIR . $domain . '/stats')) {
					$domains[] = $domain;
				}
			}

			return $domains;
		}

		public function loadViews($args) {
			$this->loading_domain = $args['domain'];
			$this->bots_excluded = 0;
			$this->farm_excluded = 0;

			$stats_directory = GGCMS_LOG_DIR . $args['domain'] . '/stats/';

			if(!is_dir($stats_directory)) {
				return [];
			}

			$earliest = $this->previous_period ? $this->previous_period['start'] : $this->period['start'];
			$latest = $this->period['end'];

			$views = [];

			foreach(scandir($stats_directory) as $file) {
				if(!preg_match('/^[0-9]{4}-[A-Za-z]{3}_humans\.txt$/', $file)) {
					continue;
				}

				$handle = fopen($stats_directory . $file, 'r');

				if(!$handle) {
					continue;
				}

				while(($line = fgets($handle)) !== FALSE) {
					$view = $this->parseLine(['line'=>$line]);

					if(!$view || $view['time'] < $earliest || $view['time'] >= $latest) {
						continue;
					}

						// scroll woke the first version of the beacon, and crawlers fire it
					if(!$this->arguments['bots'] && ($this->isDeclaredBot(['agent'=>$view['agent']]) || $view['event'] === 'scroll')) {
						$this->bots_excluded++;

						continue;
					}

					if(!$this->arguments['farm'] && $this->isFarm(['view'=>$view])) {
						$this->farm_excluded++;

						continue;
					}

					$views[] = $view;
				}

				fclose($handle);
			}

			return $views;
		}

		public function parseLine($args) {
			$pieces = explode(' ', trim($args['line']));

				// eleven fields until the user agent was added as a twelfth
			if(count($pieces) !== 11 && count($pieces) !== 12) {
				return FALSE;
			}

			$time = (int)trim($pieces[2], '[]');

			if($time <= 0) {
				return FALSE;
			}

			foreach($pieces as $index => $piece) {
				if($piece === '-') {
					$pieces[$index] = '';
				}
			}

			$view = [
				'time'=>$time,
				'address'=>$pieces[3],
				'page'=>$pieces[4],
				'referrer'=>$pieces[5],
				'screen'=>$pieces[6],
				'language'=>$pieces[7],
				'timezone'=>$pieces[8],
				'event'=>$pieces[9],
				'milliseconds'=>($pieces[10] === '') ? NULL : (int)$pieces[10],
				'agent'=>array_key_exists(11, $pieces) ? $pieces[11] : '',
			];

			$view['visitor'] = substr(sha1($view['address'] . '|' . $view['screen'] . '|' . $view['language'] . '|' . $view['timezone']), 0, 8);

			return $view;
		}

			// Visits
			// -----------------------------------------------

		public function buildVisits($args) {
			$views = $args['views'];

			usort($views, function($a, $b) { return $a['time'] <=> $b['time']; });

			$open = [];
			$visits = [];

			foreach($views as $view) {
				$visitor = $view['visitor'];

				if(array_key_exists($visitor, $open) && ($view['time'] - $visits[$open[$visitor]]['end']) <= self::VISIT_GAP_SECONDS) {
					$index = $open[$visitor];
					$visits[$index]['views'][] = $view;
					$visits[$index]['end'] = $view['time'];

					continue;
				}

				$visits[] = [
					'visitor'=>$visitor,
					'start'=>$view['time'],
					'end'=>$view['time'],
					'views'=>[$view],
				];

				$open[$visitor] = count($visits) - 1;
			}

			foreach($visits as $index => $visit) {
				$first = $visit['views'][0];

				$visits[$index]['landing'] = $first['page'];
				$visits[$index]['exit'] = $visit['views'][count($visit['views']) - 1]['page'];
				$visits[$index]['source'] = $this->classifyReferrer(['referrer'=>$first['referrer'], 'domain'=>$this->currentDomainOf(['views'=>$visit['views']])]);
				$visits[$index]['device'] = $this->classifyScreen(['screen'=>$first['screen']]);
				$visits[$index]['pages'] = count($visit['views']);
			}

			return $visits;
		}

		public function currentDomainOf($args) {
			return $this->loading_domain;
		}

		public function filterVisits($args) {
			$arguments = $this->arguments;

			return array_values(array_filter($args['visits'], function($visit) use ($arguments) {
				if($arguments['visitor'] !== '' && $visit['visitor'] !== $arguments['visitor']) {
					return FALSE;
				}

				if($arguments['device'] !== '' && $visit['device'] !== $arguments['device']) {
					return FALSE;
				}

				if($arguments['referrer'] !== '' && stripos($visit['source']['host'], $arguments['referrer']) === FALSE) {
					return FALSE;
				}

				$first = $visit['views'][0];

				if($arguments['language'] !== '' && stripos($first['language'], $arguments['language']) !== 0) {
					return FALSE;
				}

				if($arguments['timezone'] !== '' && stripos($first['timezone'], $arguments['timezone']) === FALSE) {
					return FALSE;
				}

				if($arguments['page'] !== '') {
					foreach($visit['views'] as $view) {
						if(strpos($view['page'], $arguments['page']) === 0) {
							return TRUE;
						}
					}

					return FALSE;
				}

				return TRUE;
			}));
		}

			/*
				A crawler that names itself.  On the beacon's first day
				revoltlib logged Applebot and Baiduspider's renderer, both of
				which run JavaScript and scroll.  The agent is flattened when
				logged, so the separators matched here are what survive that:
				a slash, dash, plus, semicolon or bracket after the word.
				Lines written before the agent was logged have none, and are
				always kept.
			*/

		public function isDeclaredBot($args) {
			if($args['agent'] === '') {
				return FALSE;
			}

			return (bool)preg_match('/(bot|spider|crawler)[\/;)+-]|headless|lighthouse|slurp|facebookexternalhit/i', $args['agent']);
		}

			/*
				The scripted-browser farm.  From the beacon's first day, 14 September
				2026, most counted "readers" shared one fingerprint exactly: a
				1920x1080 screen, zh-CN, Asia/Shanghai, a new Chinese address on every
				view, one page and gone -- 268 of wordweight's 269 views.  They fake
				the wheel and pointer events the beacon waits for, so no interaction
				test catches them.  Only the exact triple is matched: a real reader in
				Shanghai on a different screen, or anyone reporting UTC, is kept.
			*/

		public function isFarm($args) {
			$view = $args['view'];

			return $view['screen'] === '1920x1080' && $view['language'] === 'zh-CN' && $view['timezone'] === 'Asia/Shanghai';
		}

			/*
				Search, social, link or direct, by the site a visit arrived
				from.  A referrer on the site itself means the reader was here
				more than thirty minutes ago and came back through an open tab,
				so it counts as direct rather than as a link from somewhere.
			*/

		public function classifyReferrer($args) {
			$referrer = $args['referrer'];
			$host = strtolower((string)parse_url($referrer, PHP_URL_HOST));
			$host = preg_replace('/^www\./', '', $host);

			$domain = preg_replace('/^www\./', '', strtolower((string)$args['domain']));

			if($host === '' || $host === $domain) {
				return ['kind'=>'direct', 'host'=>'(direct)'];
			}

			$search = ['google.', 'bing.', 'duckduckgo.', 'yandex.', 'baidu.', 'ecosia.', 'search.brave.', 'yahoo.', 'startpage.', 'qwant.', 'kagi.', 'search.'];
			$social = ['facebook.', 't.co', 'twitter.', 'x.com', 'reddit.', 'lemmy', 'mastodon', 'bsky.', 'linkedin.', 'youtube.', 'news.ycombinator.', 'tumblr.', 'pinterest.', 'instagram.', 'discord', 'telegram.', 'vk.com'];

			foreach($search as $needle) {
				if(strpos($host, $needle) !== FALSE) {
					return ['kind'=>'search', 'host'=>$host];
				}
			}

			foreach($social as $needle) {
				if($host === $needle || strpos($host, $needle) !== FALSE) {
					return ['kind'=>'social', 'host'=>$host];
				}
			}

			return ['kind'=>'link', 'host'=>$host];
		}

			/*
				By the shorter side of the screen, then by shape: anything under
				600 CSS pixels across is a phone, and a squarish screen is a
				tablet.  Laptops are wide, tablets are near 4:3.  A square
				desktop monitor will be called a tablet, which is rare enough to
				live with.
			*/

		public function classifyScreen($args) {
			if(!preg_match('/^([0-9]+)x([0-9]+)$/', $args['screen'], $matches)) {
				return 'unknown';
			}

			$short = min((int)$matches[1], (int)$matches[2]);
			$long = max((int)$matches[1], (int)$matches[2]);

			if($short === 0) {
				return 'unknown';
			}

			if($short < 600) {
				return 'phone';
			}

			if(($long / $short) < 1.55) {
				return 'tablet';
			}

			return 'desktop';
		}

			// Measures
			// -----------------------------------------------

		public function measure($args) {
			$visits = $args['visits'];

			$views = 0;
			$bounces = 0;
			$visitors = [];
			$lengths = [];
			$interactions = [];
			$sources = ['search'=>0, 'social'=>0, 'link'=>0, 'direct'=>0];
			$visit_counts = [];

			foreach($visits as $visit) {
				$views += $visit['pages'];
				$visitors[$visit['visitor']] = TRUE;
				$visit_counts[$visit['visitor']] = ($visit_counts[$visit['visitor']] ?? 0) + 1;
				$sources[$visit['source']['kind']]++;

				if($visit['pages'] === 1) {
					$bounces++;
				} else {
					$lengths[] = $visit['end'] - $visit['start'];
				}

				foreach($visit['views'] as $view) {
					if($view['milliseconds'] !== NULL) {
						$interactions[] = $view['milliseconds'];
					}
				}
			}

			$visit_total = count($visits);
			$visitor_total = count($visitors);
			$returning = count(array_filter($visit_counts, function($count) { return $count > 1; }));

			return [
				'views'=>$views,
				'visitors'=>$visitor_total,
				'visits'=>$visit_total,
				'pages_per_visit'=>$visit_total ? $views / $visit_total : 0,
				'pages_per_visitor'=>$visitor_total ? $views / $visitor_total : 0,
				'visits_per_visitor'=>$visitor_total ? $visit_total / $visitor_total : 0,
				'bounce_rate'=>$visit_total ? $bounces / $visit_total : 0,
				'median_length'=>$this->median(['values'=>$lengths]),
				'median_interaction'=>$this->median(['values'=>$interactions]),
				'returning_rate'=>$visitor_total ? $returning / $visitor_total : 0,
				'search_rate'=>$visit_total ? $sources['search'] / $visit_total : 0,
				'social_rate'=>$visit_total ? $sources['social'] / $visit_total : 0,
				'link_rate'=>$visit_total ? $sources['link'] / $visit_total : 0,
				'direct_rate'=>$visit_total ? $sources['direct'] / $visit_total : 0,
			];
		}

			// Reports
			// -----------------------------------------------

		public function reportSummary($args) {
			$now = $this->measure(['visits'=>$args['visits']]);
			$before = $this->measure(['visits'=>$args['previous']]);
			$compare = $this->previous_period !== NULL;

			$lines = [
				['Page views', 'views', 'count'],
				['Visitors', 'visitors', 'count'],
				['Visits', 'visits', 'count'],
				['Pages per visit', 'pages_per_visit', 'decimal'],
				['Pages per visitor', 'pages_per_visitor', 'decimal'],
				['Visits per visitor', 'visits_per_visitor', 'decimal'],
				['Bounce rate (one-page visits)', 'bounce_rate', 'rate'],
				['Returning visitors (2+ visits)', 'returning_rate', 'rate'],
				['Median visit length (2+ pages)', 'median_length', 'seconds'],
				['Median time to first interaction', 'median_interaction', 'milliseconds'],
				['Arrived from search', 'search_rate', 'rate'],
				['Arrived from social', 'social_rate', 'rate'],
				['Arrived from other sites', 'link_rate', 'rate'],
				['Arrived direct', 'direct_rate', 'rate'],
			];

			$rows = [];

			foreach($lines as $line) {
				list($label, $key, $kind) = $line;

				$row = [
					'Measure'=>$label,
					'This period'=>$this->formatMeasure(['value'=>$now[$key], 'kind'=>$kind]),
				];

				if($compare && !$args['previous']) {
						// a period with no visits has no rates to compare against
					$row[ucfirst($this->previous_period['label'])] = '-';
					$row['Change'] = '-';
				} else if($compare) {
					$row[ucfirst($this->previous_period['label'])] = $this->formatMeasure(['value'=>$before[$key], 'kind'=>$kind]);
					$row['Change'] = ($kind === 'rate')
						? $this->pointChange(['now'=>$now[$key], 'before'=>$before[$key]])
						: $this->change(['now'=>$now[$key], 'before'=>$before[$key]]);
				}

				$rows[] = $row;
			}

			$this->printTable(['title'=>'Summary', 'rows'=>$rows]);
		}

		public function reportDays($args) {
			$days = [];

			foreach($args['visits'] as $visit) {
				foreach($visit['views'] as $view) {
					$day = date('Y-m-d D', $view['time']);

					if(!array_key_exists($day, $days)) {
						$days[$day] = ['views'=>0, 'visitors'=>[], 'visits'=>0];
					}

					$days[$day]['views']++;
					$days[$day]['visitors'][$visit['visitor']] = TRUE;
				}

				$days[date('Y-m-d D', $visit['start'])]['visits']++;
			}

			ksort($days);

			$most = max(array_map(function($day) { return $day['views']; }, $days));
			$rows = [];

			foreach($days as $day => $counts) {
				$rows[] = [
					'Day'=>$day,
					'Views'=>$counts['views'],
					'Visitors'=>count($counts['visitors']),
					'Visits'=>$counts['visits'],
					''=>$this->bar(['value'=>$counts['views'], 'most'=>$most]),
				];
			}

			$this->printTable(['title'=>'Days', 'rows'=>$rows]);
		}

		public function reportPages($args) {
			$pages = $this->pageTotals(['visits'=>$args['visits'], 'key'=>function($page) { return $page; }]);

			$this->printTable(['title'=>'Pages', 'rows'=>$this->pageRows(['pages'=>$pages, 'label'=>'Page'])]);
		}

		public function reportSections($args) {
			$pages = $this->pageTotals(['visits'=>$args['visits'], 'key'=>function($page) {
				$segments = explode('/', trim($page, '/'));

					// a lone file at the root, like /view.php, belongs to the root section
				if($segments[0] === '' || (count($segments) === 1 && strpos($segments[0], '.') !== FALSE)) {
					return '/';
				}

				return '/' . $segments[0] . '/';
			}]);

			$this->printTable(['title'=>'Sections', 'rows'=>$this->pageRows(['pages'=>$pages, 'label'=>'Section'])]);
		}

		public function pageTotals($args) {
			$key = $args['key'];
			$pages = [];

			foreach($args['visits'] as $visit) {
				$last = count($visit['views']) - 1;

				foreach($visit['views'] as $position => $view) {
					$name = $key($view['page']);

					if(!array_key_exists($name, $pages)) {
						$pages[$name] = ['views'=>0, 'visitors'=>[], 'landings'=>0, 'exits'=>0];
					}

					$pages[$name]['views']++;
					$pages[$name]['visitors'][$visit['visitor']] = TRUE;

					if($position === 0) {
						$pages[$name]['landings']++;
					}

					if($position === $last) {
						$pages[$name]['exits']++;
					}
				}
			}

			uasort($pages, function($a, $b) { return $b['views'] <=> $a['views']; });

			return $pages;
		}

		public function pageRows($args) {
			$rows = [];

			foreach(array_slice($args['pages'], 0, $this->arguments['top'], TRUE) as $name => $counts) {
				$rows[] = [
					$args['label']=>$this->shorten(['text'=>$name]),
					'Views'=>$counts['views'],
					'Visitors'=>count($counts['visitors']),
					'Landings'=>$counts['landings'],
					'Exit rate'=>$this->percent($counts['exits'] / $counts['views']),
				];
			}

			return $rows;
		}

			/*
				Which pages bring people in, and whether they stay.  A page
				with many landings and a high bounce rate is answering a search
				and letting the reader go; one with deep visits is a doorway.
			*/

		public function reportLandings($args) {
			$landings = [];

			foreach($args['visits'] as $visit) {
				$page = $visit['landing'];

				if(!array_key_exists($page, $landings)) {
					$landings[$page] = ['visits'=>0, 'pages'=>0, 'bounces'=>0, 'search'=>0];
				}

				$landings[$page]['visits']++;
				$landings[$page]['pages'] += $visit['pages'];
				$landings[$page]['bounces'] += ($visit['pages'] === 1) ? 1 : 0;
				$landings[$page]['search'] += ($visit['source']['kind'] === 'search') ? 1 : 0;
			}

			uasort($landings, function($a, $b) { return $b['visits'] <=> $a['visits']; });

			$rows = [];

			foreach(array_slice($landings, 0, $this->arguments['top'], TRUE) as $page => $counts) {
				$rows[] = [
					'Landing page'=>$this->shorten(['text'=>$page]),
					'Visits'=>$counts['visits'],
					'From search'=>$this->percent($counts['search'] / $counts['visits']),
					'Bounce'=>$this->percent($counts['bounces'] / $counts['visits']),
					'Pages/visit'=>$this->decimal($counts['pages'] / $counts['visits']),
				];
			}

			$this->printTable(['title'=>'Landings', 'rows'=>$rows]);
		}

		public function reportFlows($args) {
			$flows = [];

			foreach($args['visits'] as $visit) {
				for($i = 1; $i < count($visit['views']); $i++) {
					$from = $visit['views'][$i - 1]['page'];
					$to = $visit['views'][$i]['page'];

					if($from === $to) {
						continue;
					}

					$step = $from . "\t" . $to;
					$flows[$step] = ($flows[$step] ?? 0) + 1;
				}
			}

			arsort($flows);

			$rows = [];

			foreach(array_slice($flows, 0, $this->arguments['top'], TRUE) as $step => $count) {
				list($from, $to) = explode("\t", $step);

				$rows[] = [
					'From'=>$this->shorten(['text'=>$from]),
					'To'=>$this->shorten(['text'=>$to]),
					'Times'=>$count,
				];
			}

			$this->printTable(['title'=>'Flows', 'rows'=>$rows]);
		}

		public function reportReferrers($args) {
			$sources = [];

			foreach($args['visits'] as $visit) {
				$host = $visit['source']['host'];

				if(!array_key_exists($host, $sources)) {
					$sources[$host] = ['kind'=>$visit['source']['kind'], 'visits'=>0, 'visitors'=>[], 'pages'=>0, 'bounces'=>0];
				}

				$sources[$host]['visits']++;
				$sources[$host]['visitors'][$visit['visitor']] = TRUE;
				$sources[$host]['pages'] += $visit['pages'];
				$sources[$host]['bounces'] += ($visit['pages'] === 1) ? 1 : 0;
			}

			uasort($sources, function($a, $b) { return $b['visits'] <=> $a['visits']; });

			$rows = [];

			foreach(array_slice($sources, 0, $this->arguments['top'], TRUE) as $host => $counts) {
				$rows[] = [
					'Source'=>$host,
					'Kind'=>$counts['kind'],
					'Visits'=>$counts['visits'],
					'Visitors'=>count($counts['visitors']),
					'Pages/visit'=>$this->decimal($counts['pages'] / $counts['visits']),
					'Bounce'=>$this->percent($counts['bounces'] / $counts['visits']),
				];
			}

			$this->printTable(['title'=>'Referrers', 'rows'=>$rows]);
		}

		public function reportDevices($args) {
			$devices = [];
			$screens = [];

			foreach($args['visits'] as $visit) {
				$device = $visit['device'];

				if(!array_key_exists($device, $devices)) {
					$devices[$device] = ['visitors'=>[], 'visits'=>0, 'pages'=>0, 'bounces'=>0];
				}

				$devices[$device]['visitors'][$visit['visitor']] = TRUE;
				$devices[$device]['visits']++;
				$devices[$device]['pages'] += $visit['pages'];
				$devices[$device]['bounces'] += ($visit['pages'] === 1) ? 1 : 0;

				$screen = $visit['views'][0]['screen'] ?: 'unknown';
				$screens[$screen][$visit['visitor']] = TRUE;
			}

			uasort($devices, function($a, $b) { return $b['visits'] <=> $a['visits']; });

			$rows = [];

			foreach($devices as $device => $counts) {
				$rows[] = [
					'Device'=>$device,
					'Visitors'=>count($counts['visitors']),
					'Visits'=>$counts['visits'],
					'Pages/visit'=>$this->decimal($counts['pages'] / $counts['visits']),
					'Bounce'=>$this->percent($counts['bounces'] / $counts['visits']),
				];
			}

			$this->printTable(['title'=>'Devices', 'rows'=>$rows]);

			$this->printTable(['title'=>'Screens', 'rows'=>$this->countRows(['groups'=>$screens, 'label'=>'Screen', 'unit'=>'Visitors'])]);
		}

		public function reportLanguages($args) {
			$languages = [];

			foreach($args['visits'] as $visit) {
				$language = $visit['views'][0]['language'] ?: 'unknown';
				$languages[$language][$visit['visitor']] = TRUE;
			}

			$this->printTable(['title'=>'Languages', 'rows'=>$this->countRows(['groups'=>$languages, 'label'=>'Language', 'unit'=>'Visitors'])]);
		}

		public function reportTimezones($args) {
			$zones = [];
			$regions = [];

			foreach($args['visits'] as $visit) {
				$zone = $visit['views'][0]['timezone'] ?: 'unknown';
				$region = explode('/', $zone)[0];

				$zones[$zone][$visit['visitor']] = TRUE;
				$regions[$region][$visit['visitor']] = TRUE;
			}

			$this->printTable(['title'=>'Regions', 'rows'=>$this->countRows(['groups'=>$regions, 'label'=>'Region', 'unit'=>'Visitors'])]);
			$this->printTable(['title'=>'Timezones', 'rows'=>$this->countRows(['groups'=>$zones, 'label'=>'Timezone', 'unit'=>'Visitors'])]);
		}

			/*
				On the reader's clock, not the server's.  The beacon sends its
				IANA timezone, so a view at 03:00 UTC from America/Los_Angeles
				lands at 20:00 -- which is when that person was reading.
			*/

		public function reportHours($args) {
			$hours = array_fill(0, 24, 0);
			$weekdays = ['Mon'=>0, 'Tue'=>0, 'Wed'=>0, 'Thu'=>0, 'Fri'=>0, 'Sat'=>0, 'Sun'=>0];
			$placed = 0;
			$unplaced = 0;

			foreach($args['visits'] as $visit) {
				foreach($visit['views'] as $view) {
					$zone = $this->timezoneObject(['name'=>$view['timezone']]);

					if(!$zone) {
						$unplaced++;

						continue;
					}

					$local = (new DateTime('@' . $view['time']))->setTimezone($zone);

					$hours[(int)$local->format('G')]++;
					$weekdays[$local->format('D')]++;
					$placed++;
				}
			}

			$most = max($hours) ?: 1;
			$rows = [];

			foreach($hours as $hour => $count) {
				$rows[] = [
					'Local hour'=>sprintf('%02d:00', $hour),
					'Views'=>$count,
					''=>$this->bar(['value'=>$count, 'most'=>$most]),
				];
			}

			$this->printTable(['title'=>'Hours, on the reader\'s clock', 'rows'=>$rows]);

			$most = max($weekdays) ?: 1;
			$rows = [];

			foreach($weekdays as $weekday => $count) {
				$rows[] = [
					'Weekday'=>$weekday,
					'Views'=>$count,
					''=>$this->bar(['value'=>$count, 'most'=>$most]),
				];
			}

			$this->printTable(['title'=>'Weekdays, on the reader\'s clock', 'rows'=>$rows]);

			if($unplaced) {
				print($unplaced . ' of ' . ($placed + $unplaced) . ' views had no usable timezone and are not shown.' . "\n\n");
			}
		}

		public function reportDepth($args) {
			$depth = ['1 page'=>0, '2 pages'=>0, '3-4'=>0, '5-9'=>0, '10-19'=>0, '20+'=>0];
			$lengths = ['under 10s'=>0, '10-59s'=>0, '1-4 min'=>0, '5-14 min'=>0, '15-29 min'=>0, '30 min+'=>0];

			foreach($args['visits'] as $visit) {
				$pages = $visit['pages'];

				if($pages === 1) { $depth['1 page']++; }
				else if($pages === 2) { $depth['2 pages']++; }
				else if($pages <= 4) { $depth['3-4']++; }
				else if($pages <= 9) { $depth['5-9']++; }
				else if($pages <= 19) { $depth['10-19']++; }
				else { $depth['20+']++; }

				if($pages < 2) {
					continue;
				}

				$seconds = $visit['end'] - $visit['start'];

				if($seconds < 10) { $lengths['under 10s']++; }
				else if($seconds < 60) { $lengths['10-59s']++; }
				else if($seconds < 300) { $lengths['1-4 min']++; }
				else if($seconds < 900) { $lengths['5-14 min']++; }
				else if($seconds < 1800) { $lengths['15-29 min']++; }
				else { $lengths['30 min+']++; }
			}

			$this->printTable(['title'=>'Pages per visit', 'rows'=>$this->bucketRows(['buckets'=>$depth, 'label'=>'Pages', 'unit'=>'Visits'])]);
			$this->printTable(['title'=>'Visit length, first view to last (visits of 2+ pages)', 'rows'=>$this->bucketRows(['buckets'=>$lengths, 'label'=>'Length', 'unit'=>'Visits'])]);
		}

			/*
				Whether people come back.  Returning within seven days is
				measured only for visitors whose first visit left seven days of
				period after it, so a reader who arrived yesterday is not
				counted as having failed to return.
			*/

		public function reportLoyalty($args) {
			$visitors = [];

			foreach($args['visits'] as $visit) {
				$visitors[$visit['visitor']][] = $visit;
			}

			$counts = ['1 visit'=>0, '2 visits'=>0, '3-4'=>0, '5-9'=>0, '10+'=>0];
			$days_active = ['1 day'=>0, '2 days'=>0, '3-6'=>0, '7+'=>0];
			$eligible = 0;
			$returned = 0;
			$horizon = min(time(), $this->period['end']) - (7 * 86400);

			foreach($visitors as $visits) {
				$total = count($visits);

				if($total === 1) { $counts['1 visit']++; }
				else if($total === 2) { $counts['2 visits']++; }
				else if($total <= 4) { $counts['3-4']++; }
				else if($total <= 9) { $counts['5-9']++; }
				else { $counts['10+']++; }

				$days = count(array_unique(array_map(function($visit) { return date('Y-m-d', $visit['start']); }, $visits)));

				if($days === 1) { $days_active['1 day']++; }
				else if($days === 2) { $days_active['2 days']++; }
				else if($days <= 6) { $days_active['3-6']++; }
				else { $days_active['7+']++; }

				if($visits[0]['start'] <= $horizon) {
					$eligible++;

					foreach(array_slice($visits, 1) as $later) {
						if($later['start'] - $visits[0]['start'] <= 7 * 86400) {
							$returned++;

							break;
						}
					}
				}
			}

			$this->printTable(['title'=>'Visits per visitor', 'rows'=>$this->bucketRows(['buckets'=>$counts, 'label'=>'Visits', 'unit'=>'Visitors'])]);
			$this->printTable(['title'=>'Days active', 'rows'=>$this->bucketRows(['buckets'=>$days_active, 'label'=>'Days', 'unit'=>'Visitors'])]);

			if($eligible) {
				print('Came back within 7 days of their first visit: ' . $returned . ' of ' . $eligible . ' (' . $this->percent($returned / $eligible) . ").\n\n");
			} else {
				print('Came back within 7 days: needs a period longer than 7 days.' . "\n\n");
			}
		}

			/*
				What woke the beacon, and how soon.  Real readers scroll within
				a few seconds and point within a few more.  A pile of zero- or
				one-millisecond interactions is a script dispatching events, and
				is the first place to look if these numbers ever seem too good.
			*/

		public function reportEngagement($args) {
			$events = [];
			$delays = ['under 50ms'=>0, '50ms-1s'=>0, '1-3s'=>0, '3-10s'=>0, '10-30s'=>0, '30s+'=>0, 'unknown'=>0];

			foreach($args['visits'] as $visit) {
				foreach($visit['views'] as $view) {
					$event = $view['event'] ?: 'unknown';
					$events[$event] = ($events[$event] ?? 0) + 1;

					$ms = $view['milliseconds'];

					if($ms === NULL) { $delays['unknown']++; }
					else if($ms < 50) { $delays['under 50ms']++; }
					else if($ms < 1000) { $delays['50ms-1s']++; }
					else if($ms < 3000) { $delays['1-3s']++; }
					else if($ms < 10000) { $delays['3-10s']++; }
					else if($ms < 30000) { $delays['10-30s']++; }
					else { $delays['30s+']++; }
				}
			}

			arsort($events);

			$this->printTable(['title'=>'First interaction', 'rows'=>$this->bucketRows(['buckets'=>$events, 'label'=>'Event', 'unit'=>'Views'])]);
			$this->printTable(['title'=>'Time to first interaction', 'rows'=>$this->bucketRows(['buckets'=>$delays, 'label'=>'Delay', 'unit'=>'Views'])]);
		}

		public function reportVisitors($args) {
			$visitors = [];

			foreach($args['visits'] as $visit) {
				$id = $visit['visitor'];

				if(!array_key_exists($id, $visitors)) {
					$first = $visit['views'][0];

					$visitors[$id] = [
						'visits'=>0,
						'views'=>0,
						'first'=>$visit['start'],
						'last'=>$visit['end'],
						'device'=>$visit['device'],
						'language'=>$first['language'],
						'timezone'=>$first['timezone'],
					];
				}

				$visitors[$id]['visits']++;
				$visitors[$id]['views'] += $visit['pages'];
				$visitors[$id]['last'] = max($visitors[$id]['last'], $visit['end']);
			}

			uasort($visitors, function($a, $b) { return $b['views'] <=> $a['views']; });

			$rows = [];

			foreach(array_slice($visitors, 0, $this->arguments['top'], TRUE) as $id => $details) {
				$rows[] = [
					'Visitor'=>$id,
					'Visits'=>$details['visits'],
					'Views'=>$details['views'],
					'First seen'=>date('M d H:i', $details['first']),
					'Last seen'=>date('M d H:i', $details['last']),
					'Device'=>$details['device'],
					'Language'=>$details['language'] ?: '-',
					'Timezone'=>$details['timezone'] ?: '-',
				];
			}

			$this->printTable(['title'=>'Most active visitors', 'rows'=>$rows]);

			print('--visitor=ID shows one of them page by page.' . "\n\n");
		}

		public function reportVisitor($args) {
			if($this->arguments['visitor'] === '') {
				print('The visitor report needs --visitor=ID; the visitors report lists ids.' . "\n\n");

				return FALSE;
			}

			foreach($args['visits'] as $number => $visit) {
				$first = $visit['views'][0];

				print('Visit ' . ($number + 1) . ': ' . date('Y-m-d H:i', $visit['start']) . ', ' . $visit['pages'] . ' page(s), '
					. $this->formatMeasure(['value'=>$visit['end'] - $visit['start'], 'kind'=>'seconds']) . ', from ' . $visit['source']['host']
					. ', ' . $visit['device'] . ' ' . ($first['screen'] ?: '') . "\n");

				$rows = [];
				$previous_time = $visit['start'];

				foreach($visit['views'] as $view) {
					$rows[] = [
						'Time'=>date('H:i:s', $view['time']),
						'After'=>'+' . ($view['time'] - $previous_time) . 's',
						'Page'=>$this->shorten(['text'=>$view['page']]),
						'Woke on'=>$view['event'] ?: '-',
					];

					$previous_time = $view['time'];
				}

				$this->printTable(['rows'=>$rows]);
			}

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

			print(arr2textTable($args['rows']) . "\n");

			return TRUE;
		}

		public function countRows($args) {
			$totals = [];

			foreach($args['groups'] as $name => $members) {
				$totals[$name] = count($members);
			}

			arsort($totals);

			return $this->bucketRows(['buckets'=>array_slice($totals, 0, $this->arguments['top'], TRUE), 'label'=>$args['label'], 'unit'=>$args['unit']]);
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

		public function formatMeasure($args) {
			$value = $args['value'];

			if($args['kind'] === 'count') {
				return number_format($value);
			}

			if($args['kind'] === 'decimal') {
				return $this->decimal($value);
			}

			if($args['kind'] === 'rate') {
				return $this->percent($value);
			}

			if($value === NULL) {
				return '-';
			}

			if($args['kind'] === 'milliseconds') {
				return number_format($value / 1000, 1) . 's';
			}

			if($value < 60) {
				return round($value) . 's';
			}

				// a median of an even count can land on a half-second
			$value = (int)round($value);

			return intdiv($value, 60) . 'm ' . sprintf('%02d', $value % 60) . 's';
		}

		public function decimal($value) {
			return number_format($value, 2);
		}

		public function percent($value) {
			return number_format($value * 100, 1) . '%';
		}

		public function change($args) {
			if($args['now'] === NULL || $args['before'] === NULL) {
				return '-';
			}

			if($args['before'] == 0) {
				return $args['now'] == 0 ? '-' : 'new';
			}

			$change = ($args['now'] - $args['before']) / $args['before'];

			return ($change >= 0 ? '+' : '') . number_format($change * 100, 0) . '%';
		}

		public function pointChange($args) {
			$points = ($args['now'] - $args['before']) * 100;

			return ($points >= 0 ? '+' : '') . number_format($points, 1) . ' pts';
		}

		public function median($args) {
			$values = $args['values'];

			if(!$values) {
				return NULL;
			}

			sort($values);
			$middle = intdiv(count($values), 2);

			return (count($values) % 2) ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
		}

		public function bar($args) {
			if(!$args['most']) {
				return '';
			}

			return str_repeat('#', (int)round(30 * $args['value'] / $args['most']));
		}

		public function shorten($args) {
			$text = $args['text'];

			return (strlen($text) > 70) ? substr($text, 0, 67) . '...' : $text;
		}

		public function timezoneObject($args) {
			$name = $args['name'];

			if(!isset($this->timezones)) {
				$this->timezones = [];
			}

			if(!array_key_exists($name, $this->timezones)) {
				try {
					$this->timezones[$name] = ($name === '') ? FALSE : new DateTimeZone($name);
				} catch (Exception $exception) {
					$this->timezones[$name] = FALSE;
				}
			}

			return $this->timezones[$name];
		}
	}

?>

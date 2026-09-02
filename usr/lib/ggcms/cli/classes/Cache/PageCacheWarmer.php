<?php

	depreq('arr2textTable/arr2textTable.php');

	ggreq('traits/scripts/SimpleORMSiteMap.php');

	clireq('traits/CLIAccess.php');
	clireq('traits/DBAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/DomainValidation.php');

	class PageCacheWarmer {
		use CLIAccess;
		use DBAccess;
		use GlobalsTrait;
		use DomainValidation;
		use SimpleORMSiteMap;

			// Override Functions
			// -----------------------------------------------

		public function bannerMessageText() {
			return 'Page Cache Warmer';
		}

		public function confirmDomainText() {
			return 'Warming the page cache for: ';
		}

			// Entry Point
			// -----------------------------------------------

			/*
				The URLs come from the entry tree, through the same query
				sitemap.php uses: Entry codes chained by Assignment records,
				ORMSiteMap::GetEntrySiteMapCodes().  There is one description of
				what URLs this system has, and this reads it rather than keeping
				a second opinion.

				Order is the sitemap's order, which is shallowest first -- fewer
				levels filled means nearer the top of the tree, which is the
				sitemap's own definition of priority.  So the pages a reader
				actually lands on are warmed before chapter seven of anything.

				Paged the way the sitemap pages, a thousand at a time, so no run
				holds an entire site in memory.  revoltlib unpaged is 50 MB;
				paged it is about four.
			*/

		public function warmCache() {
			$this->setHandle();
			$this->bannerMessage();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setArguments();
			$this->setGlobals();
			$this->setMySQLArgs();

				//  ORMSiteMap asks its database object for one method, and this
				//  class provides it below, so the engine's own query runs from
				//  the command line without a Handler being dragged into it.

			$this->db_access_object = $this;

			$this->SetORMSiteMapObject();

			return $this->warmEveryPart();
		}

			// Walking the Tree
			// -----------------------------------------------

		public function warmEveryPart() {
			$warmed = 0;
			$skipped = 0;
			$failed = 0;
			$seen = 0;

			$results = [];

			$started = microtime(TRUE);

			if(!$this->arguments['quiet']) {
				printf("  %-4s %-56s %-8s %6s  %s
", 'Pri', 'Path', 'Code', 'Secs', 'Result');
			}

			$part = 1;

			while(TRUE) {
				$rows = $this->ormsitemap->GetEntrySiteMapCodes([
					'page'=>$this->arguments['section'],
					'perpage'=>$this->perPage(),
					'part'=>$part,
				]);

				if(!$rows) {
					break;
				}

				foreach($rows as $row) {
					$entry = $this->EntryFromRow(['row'=>$row]);

					if(!$entry) {
						continue;
					}

					$seen++;

					if($this->arguments['limit'] && $warmed + $failed >= $this->arguments['limit']) {
						break 2;
					}

					if(!$this->arguments['force'] && $this->isCached(['path'=>$entry['path']])) {
						$skipped++;

						continue;
					}

					$result = $this->request(['path'=>$entry['path']]);

					if($result['cached']) {
						$warmed++;
					} else {
						$failed++;
					}

						//  Printed as it happens, not collected for the end.  A
						//  cold page can take half a minute, so a run that saves
						//  its output until it finishes is indistinguishable
						//  from one that has hung.

					if(!$this->arguments['quiet']) {
						printf("  %-4s %-56s %-8s %5ss  %s
",
							$entry['priority'],
							substr($entry['path'], 0, 56),
							$result['code'],
							$result['seconds'],
							$result['cached'] ? 'cached' : '-'
						);
					}

					usleep($this->arguments['delay']);
				}

				if(count($rows) < $this->perPage()) {
					break;
				}

				$part++;
			}

			$elapsed = round(microtime(TRUE) - $started, 1);

			print("\n" . $warmed . ' warmed, ' . $failed . ' not cached, ' . $skipped . ' already warm, out of ' . $seen . ' URLs seen, in ' . $elapsed . 's.' . "\n");

			return TRUE;
		}

			/*
				One row of the seven-level join is one URL, built exactly as
				sitemap.php builds it: the codes that are present, joined, then
				view.php -- and ?action=index at the second level, because that
				is what the sitemap says that page is.

				Priority is the sitemap's: 1.0, less 0.1 for each level present.
			*/

		public function EntryFromRow($args) {
			$row = $args['row'];

			$codes = [];
			$priority = 1.0;
			$action = '';

			foreach(['E2', 'E3', 'E4', 'E5', 'E6', 'E7'] as $level) {
				if(!$row[$level . '_Code']) {
					continue;
				}

				$codes[] = $row[$level . '_Code'];
				$priority -= 0.1;
				$action = ($level === 'E2') ? 'index' : '';
			}

			if(!$codes) {
				return FALSE;
			}

			$path = '/' . implode('/', $codes) . '/view.php';

			if($action) {
				$path .= '?action=' . $action;
			}

			return [
				'path'=>$path,
				'priority'=>round($priority, 1),
			];
		}

			/*
				--limit is the same word the SQL uses, so it goes into the SQL.
				Asking for a thousand rows and discarding all but twenty is the
				database doing a thousand rows of work for nothing.
			*/

		public function perPage() {
			if($this->arguments['limit'] && $this->arguments['limit'] < $this->maximumPerPage()) {
				return $this->arguments['limit'];
			}

			return $this->maximumPerPage();
		}

		public function maximumPerPage() {
			return 1000;
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'section'=>'',
				'limit'=>0,
				'delay'=>250000,
				'timeout'=>60,
				'force'=>FALSE,
				'quiet'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--force') {
					$arguments['force'] = TRUE;
				} else if($argument === '--quiet') {
					$arguments['quiet'] = TRUE;
				} else if(substr($argument, 0, 10) === '--section=') {
					$arguments['section'] = substr($argument, 10);
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

			// The Database Object ORMSiteMap Expects
			// -----------------------------------------------

		public function FillArraysFromDB($args) {
			$query = $args['query'];
			$sqlbindstring = $args['sqlbindstring'];
			$recordvalues = $args['recordvalues'];

			$statement = $this->db_link->prepare($query);

			if(!$statement) {
				print('Query failed: ' . $this->db_link->error . "\n");

				return [];
			}

			if($sqlbindstring) {
				$bind_arguments = [];
				$bind_arguments[] = $sqlbindstring;

				foreach($recordvalues as $record_value_key => $record_value) {
					$bind_arguments[] = &$recordvalues[$record_value_key];
				}

				call_user_func_array([$statement, 'bind_param'], $bind_arguments);
			}

			$statement->execute();

			$result = $statement->get_result();

			$rows = [];

			if($result) {
				while($row = $result->fetch_assoc()) {
					$rows[] = $row;
				}
			}

			$statement->close();

			return $rows;
		}

			// Cache State
			// -----------------------------------------------

		public function cacheRoot() {
			return '/var/www/html/_cache';
		}

			/*
				The two filename forms the rewrite rules test, which must stay
				the two forms PageCache writes.  See Docs/PageCache.md.
			*/

		public function isCached($args) {
			$path = explode('?', $args['path'])[0];

			$base = $this->cacheRoot() . '/' . $this->domain . $path;

			if(substr($path, -1) === '/') {
				return is_file($base . 'index.html');
			}

			return is_file($base . '.html');
		}

			// Requests
			// -----------------------------------------------

			/*
				One index.php per URL, in its own process, with the environment
				a request would have arrived with.  PHP's CLI fills $_SERVER
				from the environment, so index.php sees what Apache would have
				given it, renders exactly as it renders for a reader, and
				PageCache writes the file from inside that render.

				A subprocess rather than requiring the engine in here: the CLI
				is a separate application that shares the conventions, not the
				runtime, and booting the engine inside it collides names and
				re-enters a Handler that was written to run once.

				It costs page-generation time and nothing else -- no HTTP, so no
				queueing behind whatever Apache is already busy with.
			*/

		public function request($args) {
			$path = $args['path'];

			$pieces = explode('?', $path);
			$query_string = isset($pieces[1]) ? $pieces[1] : '';

			$environment = [
				'HTTP_HOST'=>$this->domain,
				'SERVER_NAME'=>$this->domain,
				'REQUEST_URI'=>$path,
				'REDIRECT_URL'=>$pieces[0],
				'SCRIPT_URL'=>$pieces[0],
				'QUERY_STRING'=>$query_string,
				'REQUEST_METHOD'=>'GET',
				'HTTPS'=>'on',
				'REMOTE_ADDR'=>'127.0.0.1',
				'HTTP_USER_AGENT'=>'GGCMS-cache-warmer',
			];

			$command = 'cd ' . escapeshellarg($this->documentRoot()) . ' && ';

			foreach($environment as $name => $value) {
				$command .= $name . '=' . escapeshellarg($value) . ' ';
			}

			$command .= 'php ' . escapeshellarg($this->documentRoot() . '/index.php') . ' 2>&1';

			$started = microtime(TRUE);

			$output = shell_exec($command);

			return [
				'code'=>$this->isCached(['path'=>$path]) ? 'ok' : 'no cache',
				'seconds'=>round(microtime(TRUE) - $started, 1),
				'cached'=>$this->isCached(['path'=>$path]),
				'bytes'=>strlen((string) $output),
			];
		}

		public function documentRoot() {
			return '/var/www/html';
		}
	}

?>

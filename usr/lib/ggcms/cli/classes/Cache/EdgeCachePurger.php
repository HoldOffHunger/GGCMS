<?php

	clireq('traits/CLIAccess.php');

	ggreq('classes/Cache/EdgeCache.php');

	/*
		Purge Cloudflare's cache by hand, or from deploy.sh.

		  purge_edge_cache.php --domain=revoltlib.com
		  purge_edge_cache.php --all [--quiet]

		--all purges every zone the token can see.  deploy.sh runs it, since
		changed code makes every cached page suspect and Cloudflare would
		otherwise go on serving the old ones for up to four hours.

		Exit 0 when every purge succeeded, 1 when there is no token (nothing
		to purge on a machine with no edge), 2 when a purge failed.
	*/

	class EdgeCachePurger {
		use CLIAccess;

		public function purgeEdgeCache() {
			$edge_cache = new EdgeCache([]);
			$quiet = in_array('--quiet', $this->argv, TRUE);

			if($edge_cache->Token() === FALSE) {
				if(!$quiet) {
					print('No ggcms.cloudflare_token in php.ini; nothing purged.' . PHP_EOL);
				}

				return 1;
			}

			if(in_array('--all', $this->argv, TRUE)) {
				$domains = $edge_cache->ZoneNames();
			} else {
				$domains = $this->domainArguments();
			}

			if($domains === FALSE || !$domains) {
				print('No zones to purge: give --domain=example.com or --all, and check the token can read zones.' . PHP_EOL);

				return 2;
			}

			$exit_code = 0;

			foreach($domains as $domain) {
				$purged = $edge_cache->PurgeDomain(['domain'=>$domain]);

				if(!$purged) {
					$exit_code = 2;
				}

				if(!$quiet || !$purged) {
					print(($purged ? 'purged  ' : 'FAILED  ') . $domain . PHP_EOL);
				}
			}

			return $exit_code;
		}

		public function domainArguments() {
			$domains = [];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--domain=') === 0) {
					$domains[] = substr($argument, 9);
				}
			}

			return $domains;
		}

		public function bannerMessageText() {
			return 'GGCMS Edge Cache Purge';
		}
	}

?>

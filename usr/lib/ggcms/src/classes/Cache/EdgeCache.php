<?php

	/*
		Cloudflare's copy of the page cache.

		Every site is behind Cloudflare, and each zone caches what the origin
		marks Cache-Control: public -- for four hours.  Cloudflare cannot see
		PageCache's flush, so without a purge here an edit reaches the origin
		at once and readers on the edge up to four hours later.

		This purges the same scope PageCache flushes: the whole zone for a
		content write, one page (under both hostnames) for a like.

		The API token is read from php.ini as ggcms.cloudflare_token, beside
		the database credentials and never in the repository.  Where it is
		not set -- a workstation, a test run -- every method here is a quiet
		no-op, which is the right answer on a machine with no edge.

		Nothing here may break or delay a write.  Every failure returns FALSE,
		and the requests time out in seconds.
	*/

	class EdgeCache {
		public function __construct($args) {
			return TRUE;
		}

			// Token()
			// Tests: EdgeCacheTest::testToken()
			// Test file: tests/src/classes/Cache/EdgeCacheTest.php
		public function Token() {
			$token = getenv('GGCMS_CLOUDFLARE_TOKEN');

			if($token === FALSE || $token === '') {
				$token = get_cfg_var('ggcms.cloudflare_token');
			}

			if(!is_string($token) || !preg_match('#\A[A-Za-z0-9_-]{20,}\z#', $token)) {
				return FALSE;
			}

			return $token;
		}

			/*
				A Cloudflare zone is the bare domain.  www. is a record inside
				it, not a zone of its own, so it is stripped.
			*/

			// ZoneName()
			// Tests: EdgeCacheTest::testZoneName()
			// Test file: tests/src/classes/Cache/EdgeCacheTest.php
		public function ZoneName($args) {
			$domain = strtolower(trim((string)$args['domain']));

			if(substr($domain, 0, 4) === 'www.') {
				$domain = substr($domain, 4);
			}

			if(!preg_match('#\A[a-z0-9-]+(\.[a-z0-9-]+)+\z#', $domain)) {
				return FALSE;
			}

			return $domain;
		}

			/*
				Both hostnames, for the reason PageCache stores both: every
				site answers on example.com and www.example.com, and Cloudflare
				keys its cache on the full URL.
			*/

			// PageURLs()
			// Tests: EdgeCacheTest::testPageURLs()
			// Test file: tests/src/classes/Cache/EdgeCacheTest.php
		public function PageURLs($args) {
			$zone = $this->ZoneName(['domain'=>$args['domain']]);
			$path = $args['path'];

			if($zone === FALSE || !is_string($path) || strlen($path) === 0 || $path[0] !== '/') {
				return FALSE;
			}

			return [
				'https://' . $zone . $path,
				'https://www.' . $zone . $path,
			];
		}

		public function PurgeDomain($args) {
			return $this->Purge([
				'domain'=>$args['domain'],
				'body'=>['purge_everything'=>TRUE],
			]);
		}

		public function PurgePage($args) {
			$urls = $this->PageURLs([
				'domain'=>$args['domain'],
				'path'=>$args['path'],
			]);

			if($urls === FALSE) {
				return FALSE;
			}

			return $this->Purge([
				'domain'=>$args['domain'],
				'body'=>['files'=>$urls],
			]);
		}

		public function Purge($args) {
			$domain = $args['domain'];
			$body = $args['body'];

			$zone_id = $this->ZoneId(['domain'=>$domain]);

			if($zone_id === FALSE) {
				return FALSE;
			}

			$response = $this->Request([
				'method'=>'POST',
				'path'=>'/zones/' . $zone_id . '/purge_cache',
				'body'=>$body,
			]);

			return $response !== FALSE;
		}

			/*
				Looked up by name on each purge rather than stored.  A purge
				follows an edit, which is rare, and a stored id goes quietly
				wrong the day a zone is deleted and added again.
			*/

		public function ZoneId($args) {
			$zone = $this->ZoneName(['domain'=>$args['domain']]);

			if($zone === FALSE) {
				return FALSE;
			}

			$response = $this->Request([
				'method'=>'GET',
				'path'=>'/zones?name=' . rawurlencode($zone),
				'body'=>NULL,
			]);

			if($response === FALSE || !isset($response['result'][0]['id'])) {
				return FALSE;
			}

			$zone_id = $response['result'][0]['id'];

			if(!preg_match('#\A[a-f0-9]{32}\z#', $zone_id)) {
				return FALSE;
			}

			return $zone_id;
		}

			/*
				Every zone the token can see, for the deploy's purge.  Read
				from Cloudflare rather than from the sites' configuration, so
				a site added there is purged without anyone remembering to
				list it here.
			*/

		public function ZoneNames() {
			$response = $this->Request([
				'method'=>'GET',
				'path'=>'/zones?per_page=100',
				'body'=>NULL,
			]);

			if($response === FALSE) {
				return FALSE;
			}

			$names = [];

			foreach($response['result'] as $zone) {
				$name = $this->ZoneName(['domain'=>$zone['name'] ?? '']);

				if($name !== FALSE) {
					$names[] = $name;
				}
			}

			return $names;
		}

		public function Request($args) {
			$method = $args['method'];
			$path = $args['path'];
			$body = $args['body'];

			$token = $this->Token();

			if($token === FALSE || !function_exists('curl_init')) {
				return FALSE;
			}

			$curl = curl_init('https://api.cloudflare.com/client/v4' . $path);

			$options = [
				CURLOPT_CUSTOMREQUEST=>$method,
				CURLOPT_RETURNTRANSFER=>TRUE,
				CURLOPT_CONNECTTIMEOUT=>3,
				CURLOPT_TIMEOUT=>8,
				CURLOPT_HTTPHEADER=>[
					'Authorization: Bearer ' . $token,
					'Content-Type: application/json',
				],
			];

			if($body !== NULL) {
				$options[CURLOPT_POSTFIELDS] = json_encode($body);
			}

			curl_setopt_array($curl, $options);

			$raw = curl_exec($curl);

			curl_close($curl);

			if(!is_string($raw)) {
				return FALSE;
			}

			$response = json_decode($raw, TRUE);

			if(!is_array($response) || ($response['success'] ?? FALSE) !== TRUE) {
				return FALSE;
			}

			return $response;
		}
	}

?>

<?php

	class module_entrylink extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

			/*
				The way out to the site itself, under the title, for an entry
				that is a link before it is anything else -- RevoltLink's.  A
				site long gone is sent to the Wayback Machine instead, which
				is the only place left to see it.

					$links->DisplayLead(['archived'=>TRUE]);
			*/

		public function DisplayLead($args) {
			if(!$this->that->entry['link'] || $this->that->counts['link'] === 0) {
				return FALSE;
			}

			$archived = !empty($args['archived']);
			$urls = [];

				/*
					A site long gone was often catalogued three times over -- the
					address, a snapshot of it, and the Wayback Machine's calendar
					for it -- and all three go to the same place now.  One button
					a site, then, and a snapshot where there is one.
				*/

			for($i = 0; $i < $this->that->counts['link']; $i++) {
				$url = $this->that->entry['link'][$i]['URL'];
				$key = $archived ? $this->Host(['url'=>$url]) : $url;
				$snapshot = $this->ArchivedOriginal(['url'=>$url]) && strpos($url, '/web/*/') === FALSE;

				if(!isset($urls[$key]) || ($archived && $snapshot)) {
					$urls[$key] = $url;
				}
			}

			print('<div class="visit">');

			foreach($urls as $url) {
				print('<a class="visit-link" href="' . htmlspecialchars($this->VisitURL(['url'=>$url, 'archived'=>$archived]), ENT_QUOTES, 'UTF-8') . '" rel="noopener">');
				print('<span class="visit-label">' . ($archived ? 'See it on the Wayback Machine' : 'Visit the site') . '</span>');
				print('<span class="visit-host">' . htmlspecialchars($this->Host(['url'=>$url]), ENT_QUOTES, 'UTF-8') . '</span>');
				print('</a>');
			}

			print('</div>');

			return TRUE;
		}

			/*
				Some of RevoltLink's dead links were catalogued as their Wayback
				Machine copies already; those go there as they are, and are
				named by the site they preserve.
			*/

		public function ArchivedOriginal($args) {
			return preg_match('#^https?://web\.archive\.org/web/[^/]*/(.+)$#i', $args['url'], $parts) ? $parts[1] : NULL;
		}

		public function VisitURL($args) {
			if(empty($args['archived']) || $this->ArchivedOriginal(['url'=>$args['url']])) {
				return $args['url'];
			}

			return 'https://web.archive.org/web/' . $args['url'];
		}

		public function Host($args) {
			$original = $this->ArchivedOriginal(['url'=>$args['url']]);
			$host = (string) parse_url($original ? $original : $args['url'], PHP_URL_HOST);

			return $host !== '' ? preg_replace('/^www\./i', '', $host) : $args['url'];
		}

		public function Display($args) {
			if(!$this->that->entry['link'] || $this->that->counts['link'] === 0) {
				return TRUE;
			}

			print('<section class="block links" id="link">');
			print('<h2 class="block-title">Links</h2>');
			print('<ul class="link-list">');

			for($i = 0; $i < $this->that->counts['link']; $i++) {
				$link = $this->that->entry['link'][$i];

				print('<li>');
				print('<a href="' . $link['URL'] . '">');
				print($link['Title'] ? $link['Title'] : $link['URL']);
				print('</a>');

				if($link['Title']) {
					print(' <span class="link-host">' . htmlspecialchars((string) parse_url($link['URL'], PHP_URL_HOST), ENT_QUOTES, 'UTF-8') . '</span>');
				}

				print('</li>');
			}

			print('</ul>');
			print('</section>');

			return TRUE;
		}

	}

?>

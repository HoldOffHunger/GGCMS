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

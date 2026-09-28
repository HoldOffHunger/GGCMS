<?php

	class module_entrytag extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

			/*
				The entry's tags, each with how many entries on the site share
				it -- a count of 1 says nothing, so it is left off.
			*/

		public function Display($args) {
			if(!$this->that->entry['tag'] || !$this->that->counts['tag']) {
				return TRUE;
			}

			print('<section class="block tags" id="tag">');
			print('<h2 class="block-title">Tags</h2>');
			print('<ul class="chips">');

			$tags = $this->that->entry['tag'];

			$max_limit = $this->that->counts['tag'];

			shuffle($tags);

			for($i = 0; $i < $max_limit; $i++) {
				$tag = $tags[$i];
				$count = (int) $this->that->tag_counts[$tag['Tag']];

				print('<li><a href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag['Tag']) . '">');
				print($tag['Tag']);

				if($count > 1) {
					print(' <span class="chip-count">' . number_format($count) . '</span>');
				}

				print('</a></li>');
			}

			print('</ul>');
			print('</section>');

			return TRUE;
		}
	}

?>

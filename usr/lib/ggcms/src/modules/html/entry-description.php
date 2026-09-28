<?php

	class module_entrydescription extends module_spacing {
		public $that;
		public $header;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
		}

		public function Display() {
			if(!$this->that->entry['description'] || $this->that->counts['description'] === 0) {
				return FALSE;
			}

			$description = $this->that->entry['description'][0];

			print('<section class="block description" id="description">');

			if($this->header) {
				print('<h2 class="block-title">' . $this->header . '</h2>');
			}

			print('<p class="lede">');
			print($description['Description']);
			print('</p>');

				/*
					The test has to be on tag_counts, because that is what is
					used.  entry['tag'] and counts['tag'] describe the entry's
					tags; tag_counts is a separate property filled in by
					SetTagCounts, and an entry can have tags on a render where
					that has not run.  arsort(NULL) is then a TypeError that
					takes the whole page down over a tag list.
				*/

			if($this->that->entry['tag'] && $this->that->counts['tag'] !== 0 && is_array($this->that->tag_counts)) {
				$entry_tags = $this->that->tag_counts;
				arsort($entry_tags);
				$entry_tag_keys = array_keys($entry_tags);
				$tags_max = min(3, count($entry_tags));

				print('<div class="top-tags"><span class="top-tags-label">Top tags</span>');
				print('<ul class="chips">');

				for($i = 0; $i < $tags_max; $i++) {
					$tag = $entry_tag_keys[$i];

					print('<li><a href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag) . '">');
					print($tag);
					print(' <span class="chip-count">' . number_format($this->that->tag_counts[$tag]) . '</span>');
					print('</a></li>');
				}

				print('</ul>');
				print('</div>');
			}

			if($description['Source']) {
				print('<p class="source">From ');
				print($this->that->HyperlinkizeText(['text'=>$description['Source']]));
				print('</p>');
			}

			print('</section>');

			return TRUE;
		}
	}

?>

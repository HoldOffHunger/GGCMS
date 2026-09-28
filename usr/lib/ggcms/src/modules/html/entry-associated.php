<?php

	class module_entryassociated extends module_spacing {
		public $that;
		public $header;
		public $entrysort;
		public $entrylist;
		public $iframe;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->entrysort = $args['entrysort'];
			$this->entrylist = $args['entrylist'];
			$this->iframe = $args['iframe'];
		}
		
		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

		public function DisplayLinkBox() {
			return $this->DisplayCustomLinkBox([]);
		}

		public function DisplayCustomLinkBox($args) {
			if($this->that->counts['associated'] === 0) {
				return FALSE;
			}

			$header = $args['header'] ? $args['header'] : 'Works';
			$anchor = $args['anchor'] ? $args['anchor'] : 'associated';

			print('<a class="action" href="#' . $anchor . '">');
			print($header);

			if(!$args['hide_stats']) {
				print('<span class="action-count">' . number_format($this->that->counts['associated']) . '</span>');
			}

			print('</a>');

			return TRUE;
		}

			/*
				What a person wrote, or said, or took part in: a heading, a line
				of how much, then the entries as cards.  Past maxAssociated()
				the list is its own page, framed here.
			*/

		public function Display($args) {
			if(!$this->that->entry['associated'] || !$this->that->counts['associated']) {
				return TRUE;
			}

			$header_text = !empty($args['header']) ? $args['header'] : 'Works';
			$anchor = !empty($args['anchor']) ? $args['anchor'] : 'associated';

			print('<section class="block children associated" id="' . $anchor . '">');
			print('<div class="block-head">');
			print('<h2 class="block-title">' . $header_text . '</h2>');

			if(empty($args['hide_stats'])) {
				$stats = $this->that->associated_record_stats;
				$creation_type_text = !empty($args['creation_type']) ? $args['creation_type'] : 'documents';

				print('<p class="list-stats" title="Last updated ' . date("F j, Y", strtotime($stats['LastModificationDate'])) . '">');
				print(number_format($stats['AssociatedRecordCount']) . ' ' . $creation_type_text);

				if($stats['AssociatedWordCount'] !== 0) {
					print('<span class="dot" aria-hidden="true"> &middot; </span>' . number_format($stats['AssociatedWordCount']) . ' words');
				}

				print('</p>');
			}

			print('</div>');

			if($this->iframe !== 'always' && $this->that->counts['associated'] < $this->that->maxAssociated() - 1) {
				$associated_display = $this->entrysort->Sort([
					'entries'=>$this->that->entry['associated'],
				]);

				$entry_list_args = $args;
				$entry_list_args['children'] = $associated_display;

				$this->entrylist->Display($entry_list_args);
			} else {
				$url = '';

				foreach($this->that->record_list as $record) {
					$url .= '/' . $record['Code'];
				}

				$url .= '/view.php?action=browseAssociated';

				foreach(['ignore_parent', 'parents', 'item_title', 'list_author', 'stats_prefix'] as $parameter) {
					if(!empty($args[$parameter])) {
						$url .= '&amp;' . $parameter . '=' . urlencode($args[$parameter]);
					}
				}

				print('<iframe class="entry-browser" title="' . htmlspecialchars($header_text, ENT_QUOTES, 'UTF-8') . '" src="' . $url . '" onload="resizeIframe(this)"></iframe>');
				print('
					<script>
						function resizeIframe(obj) {
							obj.style.height = "0px";
							var newheight = obj.contentWindow.document.documentElement.scrollHeight + 5;
							obj.style.height = Math.min(newheight, 900) + "px";
						}
					</script>
				');
			}

			print('</section>');

			return TRUE;
		}
	}

?>
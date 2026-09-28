<?php

		/*
			A section's newest entries, as cards, with a way to the whole
			list after them.  The date an entry was catalogued leads its
			card; then its year, title and author, a passage, and its tags.
		*/

	class module_indexnew extends module_spacing {
		public $that;
		public $entrysort;
		public $header_text;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->entrysort = $args['entrysort'];

			if(array_key_exists('header_text', $args)) {
				$this->header_text = $args['header_text'];
			} else {
				$this->header_text = 'Newest Additions';
			}
		}

		public function Display() {
			print('<section class="block index-new children" aria-labelledby="index-new-title">');

			$this->Display_Header();

			print('<div class="entry-list">');
			$this->Display_Children();
			print('</div>');

			$this->Display_BrowseLink();

			print('</section>');

			return TRUE;
		}

		public function Display_Children() {
			foreach($this->that->children as $child) {
				$this->Display_Child([
					'child'=>$child,
				]);
			}

			return TRUE;
		}

		public function ChildURL($args) {
			$url = $args['child']['Code'] . '/view.php';

			if($this->that->entry['ChildAction']) {
				$url .= '?action=' . $this->that->entry['ChildAction'];
			}

			return $url;
		}

		public function Display_displayImage($args) {
			$child = $args['child'];

			$display_image = $this->Display_getImage([
				'child'=>$child,
			]);

			if($display_image) {
				print('<a class="entry-card-image" href="' . $this->ChildURL(['child'=>$child]) . '" tabindex="-1" aria-hidden="true">');
				print('<img alt="" loading="lazy" width="' . ceil($display_image['IconPixelWidth'] / 2) . '" height="' . ceil($display_image['IconPixelHeight'] / 2) . '" src="/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'] . '">');
				print('</a>');
			}

			return TRUE;
		}

		public function Display_Child($args) {
			$child = $args['child'];
			$url = $this->ChildURL(['child'=>$child]);
			$author = $child['association'][0]['entry'] ?? NULL;

			print('<article class="entry-card">');

			$this->Display_displayImage([
				'child'=>$child,
			]);

			print('<div class="entry-card-body">');

			$meta = [];

			$creation_date = explode(' ', $child['OriginalCreationDate'])[0];
			$meta[] = '<time class="entry-card-date" datetime="' . $creation_date . '">Catalogued ' . date("j M Y", strtotime($creation_date)) . '</time>';

			$time_frame = $this->PublicationYear(['child'=>$child]);

			if($time_frame) {
				$meta[] = $time_frame;
			}

			$words = $this->WordCount(['child'=>$child]);

			if($words) {
				$meta[] = number_format($words) . ' words';
			}

			print('<h3 class="entry-card-title"><a href="' . $url . '">');
			print($child['Title']);

			if($child['Subtitle']) {
				print('<span class="entry-card-subtitle">: ' . $child['Subtitle'] . '</span>');
			}

			print('</a>');

			if(!empty($author['Title'])) {
				print('<span class="entry-card-by">, by <a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$author['Code']]) . '">' . $author['Title'] . '</a></span>');
			}

			print('</h3>');

			print('<p class="entry-card-meta">' . implode('<span class="dot" aria-hidden="true"> &middot; </span>', $meta) . '</p>');

			print('<p class="entry-card-excerpt">');
			$this->Display_Passage(['child'=>$child]);
			print('</p>');

			$this->Display_Tags(['child'=>$child]);

			print('</div>');
			print('</article>');

			return TRUE;
		}

		public function PublicationYear($args) {
			$child = $args['child'];

			if(!$child['eventdate']) {
				return '';
			}

			foreach($child['eventdate'] as $child_event) {
				if($child_event['Title'] == 'Publication') {
					if($child_event['EventDateTime'] != '0000-00-00 00:00:00') {
						return explode('-', $child_event['EventDateTime'])[0];
					}

					return '';
				}
			}

			return '';
		}

		public function WordCount($args) {
			$child = $args['child'];

			if($child['textbody'] && count($child['textbody'])) {
				return (int) $child['textbody'][0]['WordCount'];
			}

			return 0;
		}

		public function Display_Passage($args) {
			$child = $args['child'];

			if($child['description'] && $child['description'][0] && $child['description'][0]['Description']) {
				print('<em>' . $child['description'][0]['Description'] . '</em> ');
			}

			if($child['quote']) {
				$child_quotes = $child['quote'];
				shuffle($child_quotes);
				$max_limit = min(3, count($child_quotes));

				for($i = 0; $i < $max_limit; $i++) {
					$quote = $child_quotes[$i];

					if($quote && $quote['Quote']) {
						print('<span class="entry-card-quote">&ldquo;' . str_replace('"', '\'', $quote['Quote']) . '&rdquo;</span> ');
					}
				}

				return TRUE;
			}

			if($child['textbody'] && count($child['textbody'])) {
				$text_display = $this->that->cleanser_object->FormatListOutput([
					'text'=>$child['textbody'][0]['FirstThousandCharacters'],
				]);

				if($text_display) {
					print($text_display);

					if($child['textbody'][0]['Source']) {
						print(' <span class="source">(' . $this->ShortSource(['source'=>$child['textbody'][0]['Source']]) . ')</span>');
					}

					return TRUE;
				}
			}

			$grand_children = $child['children'];

			if($grand_children && is_array($grand_children) && count($grand_children)) {
				$grand_child = NULL;

				foreach($this->entrysort->Sort(['entries'=>$grand_children]) as $single_grand_child) {
					if(!$grand_child) {
						$grand_child = $single_grand_child['textbody'][0];
					}
				}

				print($this->that->cleanser_object->FormatListOutput([
					'text'=>$grand_child['FirstThousandCharacters'],
				]));
			}

			return TRUE;
		}

		public function ShortSource($args) {
			$source = trim(strip_tags((string) $args['source']));

			return strlen($source) > 50 ? substr($source, 0, 50) . '...' : $source;
		}

		public function Display_Tags($args) {
			$child = $args['child'];

			if(!$child['tag'] || !count($child['tag'])) {
				return FALSE;
			}

			$tags = $child['tag'];
			shuffle($tags);
			$max_limit = min(10, count($tags));

			print('<ul class="chips chips-small">');

			for($i = 0; $i < $max_limit; $i++) {
				$tag = $tags[$i];
				$count = (int) $this->that->tag_counts[$tag['Tag']];

				print('<li><a href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag['Tag']) . '">' . $tag['Tag']);

				if($count > 1) {
					print(' <span class="chip-count">' . number_format($count) . '</span>');
				}

				print('</a></li>');
			}

			print('</ul>');

			return TRUE;
		}

		public function Display_Header() {
			print('<h2 class="block-title" id="index-new-title">' . $this->header_text . '</h2>');

			return TRUE;
		}

		public function Display_getImage($args) {
			$child = $args['child'];

			if($child['image']) {
				$child_images = $child['image'];
				$child_image_count = count($child_images);
				if($child_image_count) {
					shuffle($child_images);
					$child_image = $child_images[0];
					$display_image = $child_image;

					return $display_image;
				}
			}

			if(!empty($this->that->master_record['image'][0])) {
				return $this->that->master_record['image'][0];
			}

			return NULL;
		}

		public function Display_BrowseLink() {
			$count = count($this->that->record_list);
			if($count !== 0 && array_key_exists($count-2, $this->that->record_list)) {
				$parent = $this->that->record_list[$count-2];
				$adjective = $parent['GrandChildAdjective'];
				$noun_plural = $parent['GrandChildNounPlural'];
			} else {
				$adjective = $this->that->entry['ChildAdjective'];
				$noun_plural = $this->that->entry['ChildNounPlural'];
			}

			print('<p class="browse-all">');
			print('<a class="btn btn-line" href="view.php?action=browse">');
			print('Browse all ' . number_format((int) $this->that->children_count) . ' ' . strtolower(trim($adjective . ' ' . $noun_plural)) . ' &rarr;');
			print('</a>');
			print('</p>');

			return TRUE;
		}
	}

?>

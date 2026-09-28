<?php

	require_once(GGCMS_DIR . 'modules/html/entry-child-legacy.php');

	/*
		One child entry in a listing, as a card: its thumbnail, its title,
		a line of what it is -- year, length or subtitle, author -- then its
		description, quotes or the start of its text, and its tags.

		The switches, the order of the parts and the data all live in
		module_entrychildlegacy (entry-child-legacy.php), which this extends:
		only the markup is new here.  Titles are no longer cut short; a card
		has room for them.

		Display() prints the whole entry.  A template that puts something of
		its own between the parts calls DisplayStart(), DisplayHeader(),
		DisplayDetails(), DisplayTags() and DisplayEnd() itself, as before.

		$legacy_markup prints the old markup instead.  Only the conversion
		proofs set it (Development/entrychild in the configuration
		repository), so a conversion is still compared with the loop it
		replaced; no page does.
	*/

	class module_entrychild extends module_entrychildlegacy {
		public static $legacy_markup = FALSE;

			// Start: the card and its thumbnail
			// -------------------------------------------------------------

		public function DisplayStart() {
			if(self::$legacy_markup) {
				return parent::DisplayStart();
			}

			print('<article class="entry-card">');

			$display_image = $this->DisplayImage();

				// Kept for a template that shows the same image again, as
				// revoltsource's grandchild tiles do.

			$this->display_image = $display_image;

			if(!empty($display_image)) {
				$this->DisplayThumbnail(['image'=>$display_image]);
			}

			print('<div class="entry-card-body">');

			return TRUE;
		}

		public function DisplayThumbnail($args) {
			if(self::$legacy_markup) {
				return parent::DisplayThumbnail($args);
			}

			$display_image = $args['image'];
			$banner = $this->thumbnail === 'banner';

			$source = '/image/' . ($this->image_directory ? implode('/', str_split($display_image['FileDirectory'])) . '/' : '') . $display_image['IconFileName'];

			$image = '<img alt="" loading="lazy" width="' . ($banner ? 200 : ceil($display_image['IconPixelWidth'] / 2)) . '" height="' . ($banner ? 50 : ceil($display_image['IconPixelHeight'] / 2)) . '" src="' . $source . '">';

			if($this->linked) {
				print('<a class="entry-card-image' . ($banner ? ' entry-card-banner' : '') . '" href="' . $this->ChildURL() . '" tabindex="-1" aria-hidden="true">' . $image . '</a>');
			} else {
				print('<span class="entry-card-image' . ($banner ? ' entry-card-banner' : '') . '">' . $image . '</span>');
			}

			return TRUE;
		}

			// Header: the title, whole
			// -------------------------------------------------------------

		public function DisplayHeader() {
			if(self::$legacy_markup) {
				return parent::DisplayHeader();
			}

			$level = (int) $this->header_level === 2 ? 2 : 3;

			print('<h' . $level . ' class="entry-card-title">' . $this->CardTitle() . '</h' . $level . '>');

			return TRUE;
		}

		public function CardTitle() {
			$child = $this->child;

			$title = $this->linked ? '<a href="' . $this->ChildURL() . '">' . $child['Title'] . '</a>' : $child['Title'];

			if($this->title_style === 'subtitle' && $child['Subtitle'] && $this->detail_line !== 'subtitle' && $this->detail_line !== 'linkedsubtitle') {
				$title .= '<span class="entry-card-subtitle">: ' . $child['Subtitle'] . '</span>';
			}

			if($this->title_style === 'author' && $child['association'] && count($child['association'])) {
				$author = $child['association'][0]['entry'];

				$title .= '<span class="entry-card-by">, by <a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$author['Code']]) . '">' . $author['Title'] . '</a></span>';
			}

			return $title;
		}

			// Details: a line of what it is, then a passage from it
			// -------------------------------------------------------------

		public function DisplayDetailsOpen() {
			if(self::$legacy_markup) {
				return parent::DisplayDetailsOpen();
			}

			$this->time_frame = $this->TimeFrame();

			$meta = [];

			if($this->time_frame) {
				$meta[] = '<span class="entry-card-date">' . $this->time_frame . '</span>';
			}

			if($this->detail_line === 'subtitle' && $this->child['Subtitle']) {
				$meta[] = '<strong>' . $this->child['Subtitle'] . '</strong>';
			} elseif($this->detail_line === 'linkedsubtitle') {
				$meta = array_merge($meta, $this->LinkedSubtitleMeta());
			} elseif($this->detail_line === 'length' && $this->child['textbody'] && count($this->child['textbody'])) {
				$meta[] = number_format($this->child['textbody'][0]['WordCount']) . ' words';
			}

			if($meta) {
				print('<p class="entry-card-meta">' . implode('<span class="dot" aria-hidden="true"> &middot; </span>', $meta) . '</p>');
			}

			print('<p class="entry-card-excerpt">');

			return TRUE;
		}

		public function LinkedSubtitleMeta() {
			$child = $this->child;
			$author = $child['association'][0]['entry'] ?? NULL;
			$meta = [];

			if($child['Subtitle']) {
				$meta[] = '<a href="' . $this->ChildURL() . '">' . $child['Subtitle'] . '</a>';
			}

			if(!empty($author['Title'])) {
				$meta[] = 'by <a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$author['Code']]) . $this->link_suffix . '">' . $author['Title'] . '</a>';
			}

			return $meta;
		}

		public function DisplayDescription($args) {
			if(self::$legacy_markup) {
				return parent::DisplayDescription($args);
			}

			$description = $this->child['description'][0] ?? NULL;

			if(!$description || !$description['Description']) {
				return FALSE;
			}

			print('<em>' . $description['Description'] . '</em> ');

			$this->DisplaySource(['source'=>$description['Source']]);

			return TRUE;
		}

		public function DisplayQuotes() {
			if(self::$legacy_markup) {
				return parent::DisplayQuotes();
			}

			$child_quotes = $this->child['quote'];
			$max_limit = min(count($child_quotes), 3);

			shuffle($child_quotes);

			for($i = 0; $i < $max_limit; $i++) {
				$quote = $child_quotes[$i];

				if($quote && $quote['Quote']) {
					print('<span class="entry-card-quote">&ldquo;' . str_replace('"', '\'', $quote['Quote']) . '&rdquo;');
					$this->DisplaySource(['source'=>$quote['Source']]);
					print('</span> ');
				}
			}

			return TRUE;
		}

		public function DisplayExcerpt() {
			if(self::$legacy_markup) {
				return parent::DisplayExcerpt();
			}

			$child = $this->child;

			if(!$child['textbody'] || !count($child['textbody'])) {
				return $this->DisplayGrandchildExcerpt();
			}

			$first_textbody = $child['textbody'][0];

			if($this->excerpt === 'formatted') {
				$text_display = $this->that->cleanser_object->FormatListOutput([
					'text'=>$first_textbody['FirstThousandCharacters'],
				]);
			} else {
				$text_display = strip_tags($first_textbody['FirstThousandCharacters']);

				if(strlen($text_display) > 750) {
					$text_display = substr($text_display, 0, 750) . '...';
				}
			}

			if(!$text_display) {
				return $this->DisplayGrandchildExcerpt();
			}

			print($text_display);

			$this->DisplaySource(['source'=>$first_textbody['Source']]);

			return TRUE;
		}

		public function DisplayGrandchildExcerpt() {
			if(self::$legacy_markup) {
				return parent::DisplayGrandchildExcerpt();
			}

			if(!$this->grandchildren || !$this->grandchild_excerpt) {
				return FALSE;
			}

			$first_grandchild = $this->FirstGrandchild();

			if(!$first_grandchild) {
				return FALSE;
			}

			$text = $first_grandchild['textbody']['FirstThousandCharacters'] ?? '';

			if($this->grandchild_excerpt === 'plain') {
				print(strip_tags((string) $text));
			} else {
				print($this->that->cleanser_object->FormatListOutput(['text'=>$text]));
			}

			return TRUE;
		}

		public function DisplaySource($args) {
			if(self::$legacy_markup) {
				return parent::DisplaySource($args);
			}

			$source = trim(strip_tags((string) $args['source']));

			if(!$source) {
				return FALSE;
			}

			if(strlen($source) > 50) {
				$source = substr($source, 0, 50) . '...';
			}

			print(' <span class="source">(' . $source . ')</span>');

			return TRUE;
		}

		public function DisplayDetailsClose() {
			if(self::$legacy_markup) {
				return parent::DisplayDetailsClose();
			}

			print('</p>');

			return TRUE;
		}

			// Tags, and the end of the card
			// -------------------------------------------------------------

		public function DisplayTags() {
			if(self::$legacy_markup) {
				return parent::DisplayTags();
			}

			$child = $this->child;

			if(!$child['tag'] || !count($child['tag'])) {
				return FALSE;
			}

			$tags = $child['tag'];
			$max_limit = min(count($tags), 10);

			shuffle($tags);

			$tag_counts = NULL;

			if($this->tag_counts) {
				$tag_counts = $this->tag_counts === 'children' ? $this->that->tag_counts['children'] : $this->that->tag_counts;
			}

			print('<ul class="chips chips-small">');

			for($i = 0; $i < $max_limit; $i++) {
				$tag = $tags[$i];
				$count = $tag_counts ? (int) ($tag_counts[$tag['Tag']] ?? 0) : 0;

				print('<li><a href="' . ($this->root_links ? '/' : '') . 'view.php?action=browseByTag&amp;tag=' . urlencode($tag['Tag']) . '">');
				print($tag['Tag']);

				if($count > 1) {
					print(' <span class="chip-count">' . number_format($count) . '</span>');
				}

				print('</a></li>');
			}

			print('</ul>');

			return TRUE;
		}

		public function DisplayEnd() {
			if(self::$legacy_markup) {
				return parent::DisplayEnd();
			}

			print('</div>');
			print('</article>');

			return TRUE;
		}

		public function DisplayClearFloat() {
			if(self::$legacy_markup) {
				return parent::DisplayClearFloat();
			}

			return TRUE;
		}
	}

?>

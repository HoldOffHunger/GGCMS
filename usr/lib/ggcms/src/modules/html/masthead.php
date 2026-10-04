<?php

	require_once(GGCMS_DIR . 'modules/html/entry-header.php');
	require_once(GGCMS_DIR . 'modules/html/entry-index-header.php');

		/*
			A front page's masthead: the site bar, then the site's name set
			large on the dark band with its header picture ghosted behind,
			its introduction, a search box, its sections, and one of its
			quotes.  Everything comes from the site's own master record --
			its title and subtitle, its text, its header image, its quotes.

			Extends the index header for its image and author helpers.
			Switches:
			  intro       HTML for the introduction; the entry's own text
			              when not given
			  searchhint  the search box's placeholder
			  picture     TRUE to show the site's own picture beside the
			              name -- a one-page site whose picture is its face
		*/

	class module_masthead extends module_entryindexheader {
		public $intro;
		public $search_hint;
		public $picture;

		public function __construct($args) {
			parent::__construct($args);

			$this->intro = $args['intro'];
			$this->search_hint = $args['searchhint'];
			$this->picture = !empty($args['picture']);
		}

		public function Display() {
			print('<a id="top"></a>');

			$this->DisplaySiteBar();

			print('<header class="masthead">');

			$this->DisplayBackdrop();

			print('<div class="masthead-inner">');
			print('<div class="masthead-main">');

			if($this->that->entry['Subtitle']) {
				print('<p class="masthead-kicker">' . $this->that->entry['Subtitle'] . '</p>');
			}

			print('<h1 class="masthead-title">' . $this->Wordmark() . '</h1>');

			$this->DisplayIntro();
			$this->DisplaySearch();
			$this->DisplaySections();

			print('</div>');

			$this->DisplayQuote();

			if($this->picture) {
				$this->DisplayPicture();
			}

			print('</div>');
			print('</header>');

			return TRUE;
		}

			/*
				The site's own picture, full size: the master record's first
				image that is not its header backdrop.  On a front page the
				pictures are the master record's, as the backdrop's are.
			*/

		public function DisplayPicture() {
			$picture = NULL;
			$images = !empty($this->that->master_record['image']) ? $this->that->master_record['image'] : $this->that->entry['image'];

			foreach((array) $images as $image) {
				if($image && !empty($image['id']) && $image['Description'] !== 'header') {
					$picture = $image;
					break;
				}
			}

			if(!$picture) {
				return FALSE;
			}

			print('<figure class="masthead-picture">');
			print('<img alt="" src="' . $this->ImageURL(['image'=>$picture]) . '"' . $this->ImageTitle(['image'=>$picture]) . '>');
			print('</figure>');

			return TRUE;
		}

			/*
				The last word of the name is set apart -- in RevoltLib's theme,
				in flag red -- so a two-word name reads as a mark.
			*/

		public function Wordmark() {
			$words = preg_split('/\s+/', trim(strip_tags($this->that->entry['Title'])));

				/*
					A name run together -- RevoltSource, RevoltLink -- splits where
					its last word begins.  Left whole it is one word too long for
					the masthead and broke wherever the line ran out: REVOLTS,
					OURCE.
				*/

			if(count($words) < 2) {
				if(preg_match('/^(.+\p{Ll})(\p{Lu}.*)$/u', $words[0], $parts)) {
					return $parts[1] . '<em>' . $parts[2] . '</em>';
				}

				return implode(' ', $words);
			}

			$last = array_pop($words);

			return implode(' ', $words) . ' <em>' . $last . '</em>';
		}

		public function DisplayBackdrop() {
			$backdrop = $this->getBackgroundHeaderImage();

			if(!$backdrop || empty($backdrop['id'])) {
				return FALSE;
			}

			print('<img class="masthead-backdrop" alt="" aria-hidden="true" src="' . $this->ImageURL(['image'=>$backdrop]) . '">');

			return TRUE;
		}

		public function DisplayIntro() {
			$intro = $this->intro;

			if(!strlen((string) $intro) && !empty($this->that->entry['textbody'][0]['Text'])) {
				$intro = $this->that->entry['textbody'][0]['Text'];
			}

			if(strlen((string) $intro)) {
				print('<div class="masthead-intro">' . $intro . '</div>');
			}

			return TRUE;
		}

		public function DisplaySearch() {
			if(!$this->that->handler->globals->mainmenu['search']['enabled']) {
				return FALSE;
			}

			$hint = $this->search_hint ? $this->search_hint : 'Search';

			print('<form class="masthead-search" role="search" action="/search.php" method="get">');
			print('<label class="sr-only" for="masthead-search-input">Search</label>');
			print('<input id="masthead-search-input" type="search" name="search" placeholder="' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '">');
			print('<button class="btn btn-primary" type="submit">Search</button>');
			print('</form>');

			return TRUE;
		}

		public function DisplaySections() {
			$globals = $this->that->handler->globals;
			$sections = method_exists($globals, 'SiteSections') ? $globals->SiteSections() : [];

			if(!$sections) {
				return FALSE;
			}

			print('<p class="masthead-sections"><span>Browse</span>');

			foreach($sections as $section) {
				print('<a href="' . htmlspecialchars($section['url'], ENT_QUOTES, 'UTF-8') . '">' . $section['title'] . '</a>');
			}

			print('</p>');

			return TRUE;
		}

			// One of the site's own quotes, a different one on each render.

		public function DisplayQuote() {
			$quotes = $this->that->entry['quote'];

			if(!$quotes || !is_array($quotes)) {
				return FALSE;
			}

			$quotes = array_values(array_filter($quotes, function($quote) {
				return $quote && !empty($quote['Quote']);
			}));

			if(!$quotes) {
				return FALSE;
			}

			$quote = $quotes[array_rand($quotes)];

			print('<figure class="masthead-quote">');
			print('<blockquote><p>' . str_replace('"', '\'', $quote['Quote']) . '</p></blockquote>');

			if($quote['Source']) {
				print('<figcaption>' . $quote['Source'] . '</figcaption>');
			}

			print('</figure>');

			return TRUE;
		}
	}

?>

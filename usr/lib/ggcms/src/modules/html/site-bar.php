<?php

		/*
			The dark bar across the top of every page: the site's name and
			mark, its sections, search, night reading and sign-in.

			Printed by the page headers (entry-header, entry-index-header),
			so every template that prints a header gets it without changing.
			Once per page: a template that also prints it on its own -- to
			put the title somewhere else -- is not given two.
		*/

	class module_sitebar extends module_spacing {
		public $that;

		public static $displayed = FALSE;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display() {
			if(self::$displayed) {
				return FALSE;
			}

			self::$displayed = TRUE;

			print('<header class="site-bar">');
			print('<div class="site-bar-inner">');

			$this->DisplayBrand();
			$this->DisplaySearch();
			$this->DisplaySections();
			$this->DisplayTools();

			print('</div>');
			print('</header>');

			return TRUE;
		}

		public function DisplayBrand() {
			print('<a class="site-brand" href="/">');

			$logo = $this->Logo();

			if($logo) {
				print('<img class="site-brand-mark" src="' . $logo . '" alt="" width="40" height="40">');
			}

			print('<span class="site-brand-name">' . $this->SiteName() . '</span>');
			print('</a>');

			return TRUE;
		}

		public function DisplaySearch() {
			if(!$this->that->handler->globals->mainmenu['search']['enabled']) {
				return FALSE;
			}

			print('<form class="site-search" role="search" action="/search.php" method="get">');
			print('<label class="sr-only" for="site-search-input">Search ' . $this->SiteName() . '</label>');
			print('<input id="site-search-input" type="search" name="search" placeholder="Search" autocomplete="off">');
			print('</form>');

			return TRUE;
		}

		public function DisplaySections() {
			$sections = $this->Sections();

			if(!$sections) {
				return FALSE;
			}

			print('<nav class="site-nav" aria-label="Sections">');

			foreach($sections as $section) {
				print('<a href="' . htmlspecialchars($section['url'], ENT_QUOTES, 'UTF-8') . '">' . $section['title'] . '</a>');
			}

			print('</nav>');

			return TRUE;
		}

		public function DisplayTools() {
			print('<div class="site-tools">');

			if($this->NightReading()) {
				print('<button type="button" class="site-tool night-toggle" data-night-toggle aria-pressed="false" title="Night reading">');
				print('<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>');
				print('<span>Night</span>');
				print('</button>');
			}

			$this->DisplayAuth();

			print('</div>');

			return TRUE;
		}

			/*
				Signed in, the reader's name and a way out; otherwise a way in.
				Plain /login.php, as the auth module explains: a ?redirect= on
				every page made crawlers nest login links without end.
			*/

		public function DisplayAuth() {
			$session = $this->that->handler->authentication->user_session;

			if($session) {
				$name = $session['User.Username'] ? $session['User.Username'] : $session['User.EmailAddress'];
				$redirect = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

				print('<span class="site-account" title="Signed in">' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . '</span>');
				print('<a class="site-tool" href="/logout.php?redirect=' . $redirect . '" rel="nofollow">Sign out</a>');
			} else {
				print('<a class="site-tool" href="/login.php" rel="nofollow">Sign in</a>');
			}

			return TRUE;
		}

			// Data
			// -------------------------------------------------

		public function SiteName() {
			$master_record = $this->that->master_record;

			if($master_record && $master_record['Title']) {
				return $master_record['Title'];
			}

			return $this->that->handler->domain->primary_domain;
		}

		public function Logo() {
			$master_record = $this->that->master_record;

			if(!$master_record || empty($master_record['image'])) {
				return '';
			}

			foreach($master_record['image'] as $image) {
				if($image && $image['id'] && $image['Description'] !== 'header') {
					$file = $image['IconFileName'] ? $image['IconFileName'] : $image['FileName'];

					return '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $file;
				}
			}

			return '';
		}

			/*
				The site's own sections first, then About, when the main menu
				has it.  Search is its own box.
			*/

		public function Sections() {
			$globals = $this->that->handler->globals;
			$sections = method_exists($globals, 'SiteSections') ? $globals->SiteSections() : [];

			if($globals->mainmenu['about']['enabled']) {
				$sections[] = ['title'=>'About', 'url'=>'/about.php'];
			}

			return $sections;
		}

		public function NightReading() {
			$globals = $this->that->handler->globals;

			return method_exists($globals, 'NightReading') && $globals->NightReading();
		}
	}

?>

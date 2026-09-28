<?php

	class module_navigation extends module_spacing {
		public $globals;
		public $language_object;
		public $domain_object;
		
		public function __construct($args) {
			$this->globals = $args['globals'];
			$this->language_object = $args['languageobject'];
			$this->domain_object = $args['domainobject'];
		}
		
			/*
				The foot of every page: the main menu, on the dark band the
				site bar uses, so a page is framed top and bottom by the same
				material.
			*/

		public function DisplayBottomNavigation($args) {
			print('<footer class="site-foot">');
			print('<div class="site-foot-inner">');
			print('<nav class="site-foot-links" aria-label="Site">');

			print($this->DisplayBottomNavigation_Links($args));

			print('</nav>');
			print('</div>');
			print('</footer>');

			return TRUE;
		}

		public function DisplayBottomNavigation_Link($args) {
			if($args['current']) {
				return '<span aria-current="page">' . $args['text'] . '</span>';
			}

			return '<a href="' . $args['url'] . '">' . $args['text'] . '</a>';
		}

		public function DisplayBottomNavigation_Links($args) {
			$this_page = $args['thispage'];
			$url_divider = '';
			$primary_url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
			
			$display_text = '';
			
			if($this->globals->mainmenu['home']['enabled']) {
				$home_content_text = $this->DisplayBottomNavigation_Links_HomeText();
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Home', 'url'=>$primary_url . '/', 'text'=>$home_content_text]);
			}
			
			if($this->globals->mainmenu['about']['enabled']) {
				$display_text .= $url_divider;
				
				$about_content_text = $this->DisplayBottomNavigation_Links_AboutText();
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'About', 'url'=>$primary_url . '/about.php', 'text'=>$about_content_text]);
			}
			
			if($this->globals->MainMenu_Enabled_News()) {
				$display_text .= $url_divider;
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'News', 'url'=>$primary_url . '/news.php', 'text'=>'News']);
			}
			
			if($this->globals->MainMenu_Enabled_Feeds()) {
				$display_text .= $url_divider;
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Feeds', 'url'=>$primary_url . '/news.php?action=docs', 'text'=>'Feeds']);
			}
			
			if($this->globals->mainmenu['updates']['enabled']) {
				$display_text .= $url_divider;

				$updates_content_text = $this->DisplayBottomNavigation_Links_UpdatesText();

				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Updates', 'url'=>$primary_url . $this->globals->mainmenu['updates']['url'], 'text'=>$updates_content_text]);
			}

			if($this->globals->mainmenu['search']['enabled']) {
				$display_text .= $url_divider;
				
				$search_content_text = $this->DisplayBottomNavigation_Links_SearchText();
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Search', 'url'=>$primary_url . '/search.php', 'text'=>$search_content_text]);
			}
			
			if($this->globals->mainmenu['contact']['enabled']) {
				$display_text .= $url_divider;
				
				$contact_content_text = $this->DisplayBottomNavigation_Links_ContactText();
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Contact', 'url'=>$primary_url . '/contact.php', 'text'=>$contact_content_text]);
			}
			
			if($this->globals->mainmenu['languages']['enabled']) {
				$display_text .= $url_divider;
				
				$languages_content_text = $this->DisplayBottomNavigation_Links_LanguagesText();
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Languages', 'url'=>$primary_url . '/languages.php', 'text'=>$languages_content_text]);
			}
			
			if($this->globals->mainmenu['privacypolicy']['enabled']) {
				$display_text .= $url_divider;
				
				$privacy_content_text = $this->DisplayBottomNavigation_Links_PrivacyText();
				
				$display_text .= $this->DisplayBottomNavigation_Link(['current'=>$this_page === 'Privacy', 'url'=>$primary_url . '/privacy.php', 'text'=>$privacy_content_text]);
			}
			
			$extra_link = $this->globals->SiteLinks_ExtraURL();
			
			if($extra_link) {
			#	$display_text .= $url_divider;
				
			#	$display_text .= $extra_link;
			}
			
			return $display_text;
		}
		
		public function DisplayBottomNavigation_Links_HomeText () {
			return $this->globals->mainmenu['home']['text'][$this->language_object->getLanguageCode()];
		}
		
		public function DisplayBottomNavigation_Links_UpdatesText () {
			return $this->globals->mainmenu['updates']['text'][$this->language_object->getLanguageCode()];
		}
		public function DisplayBottomNavigation_Links_AboutText () {
			return $this->globals->mainmenu['about']['text'][$this->language_object->getLanguageCode()];
		}
		
		public function DisplayBottomNavigation_Links_ContactText () {
			return $this->globals->mainmenu['contact']['text'][$this->language_object->getLanguageCode()];
		}
		
		public function DisplayBottomNavigation_Links_LanguagesText () {
			return $this->globals->mainmenu['languages']['text'][$this->language_object->getLanguageCode()];
		}
		
		public function DisplayBottomNavigation_Links_SearchText () {
			return $this->globals->mainmenu['search']['text'][$this->language_object->getLanguageCode()];
		}
		
		public function DisplayBottomNavigation_Links_PrivacyText() {
			return $this->globals->mainmenu['privacypolicy']['text'][$this->language_object->getLanguageCode()];
		}
	}

?>
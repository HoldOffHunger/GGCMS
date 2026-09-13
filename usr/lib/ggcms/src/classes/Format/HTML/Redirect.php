<?php

	class HTML_Redirect {
		public $cleanser;
		public $domain_object;
		public $script_file;
		public $formats;
		public $language;
		
		public $url_cleansed;
		public $base_directory;
		
		public function __construct ($args) {
			$this->cleanser = $args['cleanser'];
			$this->domain_object = $args['domainobject'];
			$this->script_file = $args['scriptfile'];
			$this->formats = $args['formats'];
			$this->language = $args['language'];
			
			$cleanser_args = [
				'input'=>mb_substr(
					$_SERVER['REDIRECT_URL'],
					0,
					1000,
					'UTF-8',
				),
			];
			
			$this->url_cleansed = $this->cleanser->CleanseInput($cleanser_args)['cleansedinput'];
			$this->base_directory = $this->SetBaseDirectory();
		}
		
		public function GetAllVersionURLs() {
			$all_versions_array = [];
			
			foreach($this->formats->GetListOfAlternateVersionFormats() as $key => $value) {
				$url_query = $this->cleanser->CleanseInput_GetQuery();
				if($url_query) {
					$all_versions_array[$key] = ($this->base_directory . "/view." . $value . (strpos($value, '?') === FALSE ? "?" : "&") . $url_query);
				} else {
					$all_versions_array[$key] = ($this->base_directory . "/view." . $value);
				}
			}
			
			return($all_versions_array);
		}
		
		public function PrintAllVersionURLs($args) {
			$script = $args['script'];

			$primary_domain = $this->domain_object->GetPrimaryDomain([
				'www'=>1,
				'lowercased'=>1,
			]);

				/*
					Alternate formats, unless the site does not serve them.
					wordweight answers view.pdf, view.rdf and the rest with a
					302 back to the page, so linking them gave crawlers sixteen
					dead ends on every word page.  See ShowAlternateFormats in
					etc/ggcms/clonefrom/site/identity.php.
				*/

			if($this->ShowAlternateFormats(['script'=>$script])) {
				foreach($this->GetAllVersionURLs() as $media => $url) {
					print("\t");
					print('<link rel="alternate" media="' . $media . '" href="' . $primary_domain . $url . '">');
					print("\n\n");
				}
			}

				/*
					One alternate per language, each pointing at this page in
					that language.  This loop used to reuse $url and $media left
					over from the format loop above, so every language alternate
					on every site pointed at view.rdf -- thirteen query-string
					URLs per page, none of which any cache can serve.
				*/

			if(!$script->handler->abstractglobals->site->NotReadyForLanguages()) {
				print("\n");

				foreach($this->language->GetListOfLanguageCodes() as $language_code => $language_name) {
					print("\t");
					print('<link rel="alternate" hreflang="' . $language_code . '" href="' . $primary_domain . $this->base_directory . '/?language=' . $language_code . '">');
					print("\n");
				}
			}

			return TRUE;
		}

		public function ShowAlternateFormats($args) {
			$site = $args['script']->handler->abstractglobals->site;

			if(!is_object($site) || !method_exists($site, 'ShowAlternateFormats')) {
				return TRUE;
			}

			return $site->ShowAlternateFormats();
		}

		public function SetBaseDirectory() {
			$redirect_url_explosion = explode('/', $this->url_cleansed);
			$redirect_url_useful = $redirect_url_explosion;
			$throwaway_item = array_pop($redirect_url_useful);
			$redirect_url_useful_imploded = implode('/', $redirect_url_useful);
			return $redirect_url_useful_imploded;
		}
		
		public function RedirectToSecuredConnection($args) {
			return header('Location:' . $this->RedirectToSecuredConnection_RedirectURL(), TRUE, 307);
		}
		
		public function Redirect_SetArgs($args) {
			$args['desiredscript'] = 'redirect.php';
			$args['scriptname'] = 'redirect.php';
			$args['scriptfile'] = 'redirect';
			$args['scriptclassname'] = 'redirect';
			$args['scriptobject'] = 'redirect';
			$args['scriptlocation'] = GGCMS_DIR . 'scripts/redirect.php';
			$args['scriptformat'] = 'HTML';
			
			return $args;
		}
		
		public function RedirectToSecuredConnection_RedirectURL() {
			$url = $_SERVER['REQUEST_URI'];
			$primary_domain_args = [
				'secure'=>1,
				'www'=>1,
				'lowercased'=>1,
			];
			$full_redirect_url = $this->domain_object->GetPrimaryDomain($primary_domain_args) . $url;
			return $full_redirect_url;
		}
		
		public function RedirectToLogin($args) {
			return header('Location:' . $this->RedirectToLogin_RedirectURL(), TRUE, 307);
		}
		
		public function RedirectToLogin_RedirectURL() {
			$primary_domain_args = [
				'secure'=>1,
				'www'=>1,
				'lowercased'=>1,
			];
			
			$url = '/login.php';
			$full_redirect_url = $this->domain_object->GetPrimaryDomain($primary_domain_args) . $url;
			return $full_redirect_url;
		}
	}

?>
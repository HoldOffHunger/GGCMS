<?php

	class Language {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$this->SetLanguage();
			
			return $this;
		}
		
		public function SetLanguage() {
			$language = $this->handler->query->Parameter(['parameter'=>'language']);
			
			if(!$language) {
				$language = $this->handler->query->Parameter(['parameter'=>'lang']);
			}
			
			$language_codes = $this->GetListOfLanguageCodes();
			
			$this->requested_language_code = '';
			
			if($language) {
				if(!empty($language_codes[$language])) {
					$this->requested_language_code = $language;
					
					$this->language_code = $language;
					$this->language = $language_codes[$language];
				}
			} else {
				$get_cookie_args = [
					'cookie'=>'language',
				];
				
				$language = $this->handler->cookie->GetCookie($get_cookie_args);
				
				if($language && !empty($language_codes[$language])) {
					$this->language_code = $language;
					$this->language = $language_codes[$language];
				}
			}
			
			if(!$this->language) {
				$this->language_code = 'en';
				$this->language = 'English';
			}
			
			header('language: ' . $this->language_code);
			
			return $this->language;
		}
		
		/*
			?language=en asks explicitly for the default language, and the page
			it renders is the plain page: on 14 September 2026 revoltlib's
			/people/?language=en differed from /people/ only in links to itself.
			A query string is never cached, so every one was a fresh render.  It
			now answers 301 to the plain URL.  The cookie set above goes out with
			the redirect, so a reader switching back to English from another
			language keeps the choice.  Only a query string holding nothing but
			the language is redirected; anything else carries on as before.
		*/

			/*
				Which languages a site offers is the site's own answer --
				DefaultLanguage() and SupportedLanguages() in its identity -- but
				the language is chosen before that config is built, because the
				config's language scripts are chosen by it.  So SetLanguage()
				only records what was asked for, and this settles it once the
				site is known.

				A request for the default language, or for one the site does not
				offer, is the plain page: with nothing else in the query it
				answers 301 to the plain URL, as ?language=en always has, so an
				English-only site has one cacheable page where it had thirteen.
				The cookie is set only for a language the site offers.

				Returns TRUE when the language changed, so the caller can
				rebuild what was built for the old one.
			*/
		
		public function ApplySiteLanguages($args) {
			$site = $args['site'];
			
			$language_codes = $this->GetListOfLanguageCodes();
			
			$default_language_code = 'en';
			$supported_language_codes = array_keys($language_codes);
			
			if(is_object($site) && method_exists($site, 'SupportedLanguages')) {
				if(!empty($language_codes[$site->DefaultLanguage()])) {
					$default_language_code = $site->DefaultLanguage();
				}
				
				$supported_language_codes = array_values(array_intersect(array_keys($language_codes), (array)$site->SupportedLanguages()));
			}
			
			if(!in_array($default_language_code, $supported_language_codes, TRUE)) {
				$supported_language_codes[] = $default_language_code;
			}
			
			$this->default_language_code = $default_language_code;
			$this->supported_language_codes = $supported_language_codes;
			
			$requested_language_code = $this->requested_language_code;
			
			if($requested_language_code !== '') {
				$requested_is_plain =
					$requested_language_code === $default_language_code ||
					!in_array($requested_language_code, $supported_language_codes, TRUE);
				
				if($requested_is_plain && $this->IsOnlyLanguageParameter()) {
					if(in_array($requested_language_code, $supported_language_codes, TRUE)) {
						$this->SetLanguageCookie(['languagecode'=>$requested_language_code]);
					}
					
					return $this->RedirectToPlainURL();
				}
				
				if(!$requested_is_plain) {
					$this->SetLanguageCookie(['languagecode'=>$requested_language_code]);
				}
			}
			
			if(in_array($this->language_code, $supported_language_codes, TRUE)) {
				return FALSE;
			}
			
			$this->language_code = $default_language_code;
			$this->language = $language_codes[$default_language_code];
			
			header('language: ' . $this->language_code);
			
			return TRUE;
		}
		
		public function SetLanguageCookie($args) {
			$language_code = $args['languagecode'];
			
			return $this->handler->cookie->SetCookie([
				'key'=>'language',
				'value'=>$language_code,
				'secure'=>0,
			]);
		}
		
		public function GetListOfSupportedLanguageCodes() {
			$language_codes = $this->GetListOfLanguageCodes();
			
			if(!isset($this->supported_language_codes)) {
				return $language_codes;
			}
			
			return array_intersect_key($language_codes, array_flip($this->supported_language_codes));
		}
		
		public function IsOnlyLanguageParameter() {
			if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
				return FALSE;
			}

			parse_str((string)($_SERVER['QUERY_STRING'] ?? ''), $parameters);

			return count($parameters) === 1 && (array_key_exists('language', $parameters) || array_key_exists('lang', $parameters));
		}

		public function RedirectToPlainURL() {
			$path = strtok((string)$_SERVER['REQUEST_URI'], '?');

			header('Location: ' . $this->handler->domain->GetPrimaryDomain(['lowercased'=>TRUE]) . $path, TRUE, 301);

			exit;
		}

		public function GetLanguage() {
			return $this->language;
		}
		
		public function GetLanguageCode() {
			return $this->language_code;
		}
		
					// Lists
					// --------------------------------------------------------
		
				// Country Code List
				// --------------------------------------------------------
		
		public function GetOnlyCountryCodeList() {
			return [
				'de',		// German
				'en',		// English
				'es',		// Spanish
				'fr',		// French
				'it',		// Italian
				'ja',		// Japanese
				'nl',		// Dutch
				'pl',		// Polish
				'pt',		// Portuguese
				'ru',		// Russian
				'tr',		// Turkish
				'zh',		// Chinese
			];
		}
		
		public function GetCountryCodeList() {
			return [
				'de'=>
					[
						'de',		# 639-1
						'deu',		# 639-2/T
						'ger',		# 639-2/B
					#	'deu',		# 639-3
						'deus',		# 639-6
					#	'de',		# 3166-1 2-Code
					#	'deu',		# 3166-1 3-Code
					],
				'en'=>
					[
						'en',		# 639-1
						'eng',		# 639-2/T
					#	'eng',		# 639-2/B
					#	'eng',		# 639-3
						'engs',		# 639-6
						'us',		# 3166-1 2-Code
						'usa',		# 3166-1 3-Code
					],
				'es'=>
					[
						'es',		# 639-1
						'spa',		# 639-2/T
					#	'spa',		# 639-2/B
					#	'spa',		# 639-3
					#	'',		# 639-6
					#	'es',		# 3166-1 2-Code
						'esp',		# 3166-1 3-Code
					],
				'fr'=>
					[
						'fr',		# 639-1
						'fra',		# 639-2/T
						'fre',		# 639-2/B
					#	'fra',		# 639-3
						'fras',		# 639-6
					#	'fr',		# 3166-1 2-Code
					#	'fra',		# 3166-1 3-Code
					],
				'it'=>
					[
						'it',		# 639-1
						'ita',		# 639-2/T
					#	'ita',		# 639-2/B
					#	'ita',		# 639-3
						'itas',		# 639-6
					#	'it',		# 3166-1 2-Code
					#	'ita',		# 3166-1 3-Code
					],
				'ja'=>
					[
						'ja',		# 639-1
						'jpn',		# 639-2/T
					#	'jpn',		# 639-2/B
					#	'jpn',		# 639-3
					#	'jpn',		# 639-6	
						'jp',		# 3166-1 2-Code
					#	'jpn',		# 3166-1 3-Code
					],
				'nl'=>
					[
						'nl',		# 639-1
						'nld',		# 639-2/T
						'dut',		# 639-2/B
					#	'nld',		# 639-3
					#	'',		# 639-6
					#	'nl',		# 3166-1 2-Code
					#	'nld',		# 3166-1 3-Code
					],
				'pl'=>
					[
						'pl',		# 639-1
						'pol',		# 639-2/T
					#	'pol',		# 639-2/B
					#	'pol',		# 639-3
						'pols',		# 639-6
					#	'pl',		# 3166-1 2-Code
					#	'pols',		# 3166-1 3-Code
					],
				'pt'=>
					[
						'pt',		# 639-1
						'por',		# 639-2/T
					#	'por',		# 639-2/B
					#	'por',		# 639-3
					#	'',		# 639-6
					#	'pt',		# 3166-1 2-Code
						'prt',		# 3166-1 3-Code
					],
				'ru'=>
					[
						'ru',		# 639-1
						'rus',		# 639-2/T
					#	'rus',		# 639-2/B
					#	'rus',		# 639-3
					#	'',		# 639-6
					#	'ru',		# 3166-1 2-Code
					#	'rus',		# 3166-1 3-Code
					],
				'tr'=>
					[
						'tr',		# 639-1
						'tur',		# 639-2/T
					#	'tur',		# 639-2/B
					#	'tur',		# 639-3
					#	'',		# 639-6
						'tr',		# 3166-1 2-Code
						'tur',		# 3166-1 3-Code
					],
				'zh'=>
					[
						'zh',		# 639-1
						'zho',		# 639-2/T
						'chi',		# 639-2/B
					#	'zho',		# 639-3
					#	'',		# 639-6
						'cn',		# 3166-1 2-Code
						'chn',		# 3166-1 3-Code
					],
			];
		}
		
				// Filename Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageFlags() {
			return [
				'de'=>'de_Germany.png',
				'en'=>'us_United_States.png',
				'es'=>'es_Spain.png',
				'fr'=>'fr_France.png',
				'it'=>'it_Italy.png',
				'ja'=>'jp_Japan.png',
				'nl'=>'nl_Netherlands.png',
				'pl'=>'pl_Poland.png',
				'pt'=>'pt_Portugal.png',
				'ru'=>'ru_Russian_Federation.png',
				'tr'=>'tr_Turkey.png',
				'zh'=>'cn_China.png',
			];
		}
		
				// Multi-Language Lists
				// --------------------------------------------------------
		
		public function GetListOfNativeLanguageNames() {
			return [
				'de'=>'Deutsch',
				'en'=>'English',
				'es'=>'Español',
				'fr'=>'Français',
				'it'=>'Italiano',
				'ja'=>'日本語',
				'nl'=>'Nederlands',
				'pl'=>'Polskie',
				'pt'=>'Português',
				'ru'=>'Русский',
				'tr'=>'Türkçe',
				'zh'=>'汉语',
			];
		}
		
				// English (EN) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_any($args) {
			$language_code = $args['languagecode'];
			$function_name = 'GetListOfLanguageCodes_' . $language_code;
			return $this->$function_name();
		}
		
		public function GetListOfLanguageCodes_en() {
			return $this->GetListOfLanguageCodes();
		}
		
		public function GetListOfLanguageCodes() {
			return [
				'de'=>'German',
				'en'=>'English',
				'es'=>'Spanish',
				'fr'=>'French',
				'it'=>'Italian',
				'ja'=>'Japanese',
				'nl'=>'Dutch',
				'pl'=>'Polish',
				'pt'=>'Portuguese',
				'ru'=>'Russian',
				'tr'=>'Turkish',
				'zh'=>'Chinese',
			];
		}
		
				// German (DE) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_de() {
			return $this->GetListOfLanguageCodes_German();
		}
		
			# recheck for "language" versus "nationality"
		
		public function GetListOfLanguageCodes_German() {
			return [
				'de'=>'Deutsch',
				'en'=>'Englisch',
				'es'=>'Spanisch',
				'fr'=>'Französisch',
				'it'=>'Italienisch',
				'ja'=>'Japanisch',
				'nl'=>'Niederländer',
				'pl'=>'Polieren',
				'pt'=>'Portugiesisch',
				'ru'=>'Russisch',
				'tr'=>'Türkisch',
				'zh'=>'Chinesisch',
			];
		}
		
				// Spanish (ES) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_es() {
			return $this->GetListOfLanguageCodes_Spanish();
		}
		
			# recheck for "language" versus "nationality"
		
		public function GetListOfLanguageCodes_Spanish() {
			return [
				'de'=>'Alemán',
				'en'=>'Inglés',
				'es'=>'Español',
				'fr'=>'Francés',
				'it'=>'Italiano',
				'ja'=>'Japonés',
				'nl'=>'Holandés',
				'pl'=>'Polaco',
				'pt'=>'Portugués',
				'ru'=>'Ruso',
				'tr'=>'Turco',
				'zh'=>'Chino',
			];
		}
		
				// French (FR) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_fr() {
			return $this->GetListOfLanguageCodes_French();
		}
		
			# recheck for "language" versus "nationality"
		
		public function GetListOfLanguageCodes_French() {
			return [
				'de'=>'Allemand',
				'en'=>'Anglais',
				'es'=>'Espanol',
				'fr'=>'Français',
				'it'=>'Italien',
				'ja'=>'Japonais',
				'nl'=>'Néerlandais',
				'pl'=>'Polonais',
				'pt'=>'Portugais',
				'ru'=>'Russe',
				'tr'=>'Turc',
				'zh'=>'Chinois',
			];
		}
		
				// Japanese (JA) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_ja() {
			return $this->GetListOfLanguageCodes_Japanese();
		}
		
		public function GetListOfLanguageCodes_Japanese() {
			return [
				'de'=>'ドイツ語',
				'en'=>'英語',
				'es'=>'スペイン語',
				'fr'=>'フランス語',
				'it'=>'イタリア語',
				'ja'=>'日本語',
				'nl'=>'オランダの',
				'pl'=>'ポーランド語',
				'pt'=>'ポルトガル語',
				'ru'=>'ロシア語',
				'tr'=>'トルコ語',
				'zh'=>'中国語',
			];
		}
		
				// Italian (IT) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_it() {
			return $this->GetListOfLanguageCodes_Italian();
		}
		
		public function GetListOfLanguageCodes_Italian() {
			return [
				'de'=>'Tedesco',
				'en'=>'Inglese',
				'es'=>'Spagnolo',
				'fr'=>'Francese',
				'it'=>'Italiano',
				'ja'=>'Giapponese',
				'nl'=>'Olandese',
				'pl'=>'Polacco',
				'pt'=>'Portoghese',
				'ru'=>'Russo',
				'tr'=>'Turco',
				'zh'=>'Cinese',
			];
		}
		
				// Dutch (NL) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_nl() {
			return $this->GetListOfLanguageCodes_Dutch();
		}
		
		public function GetListOfLanguageCodes_Dutch() {
			return [
				'de'=>'Duits',
				'en'=>'Engels',
				'es'=>'Spaans',
				'fr'=>'Frans',
				'it'=>'Italiaans',
				'ja'=>'Japans',
				'nl'=>'Nederlands',
				'pl'=>'Pools',
				'pt'=>'Portugees',
				'ru'=>'Russisch',
				'tr'=>'Turks',
				'zh'=>'Chinese',
			];
		}
		
				// Polish (PL) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_pl() {
			return $this->GetListOfLanguageCodes_Polish();
		}
		
		public function GetListOfLanguageCodes_Polish() {
			return [
				'de'=>'Niemiecki',
				'en'=>'Angielski',
				'es'=>'Hiszpański',
				'fr'=>'Francuski',
				'it'=>'Włoski',
				'ja'=>'Japoński',
				'nl'=>'Holenderski',
				'pl'=>'Polskie',
				'pt'=>'Portugalski',
				'ru'=>'Rosyjski',
				'tr'=>'Turecki',
				'zh'=>'Chiński',
			];
		}
		
				// Portuguese (PT) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_pt() {
			return $this->GetListOfLanguageCodes_Portuguese();
		}
		
		public function GetListOfLanguageCodes_Portuguese() {
			return [
				'de'=>'Alemão',
				'en'=>'Inglês',
				'es'=>'Espanhol',
				'fr'=>'Francês',
				'it'=>'Italiano',
				'ja'=>'Japonês',
				'nl'=>'Holandês',
				'pl'=>'Polonês',
				'pt'=>'Português',
				'ru'=>'Russo',
				'tr'=>'Turco',
				'zh'=>'Chinês',
			];
		}
		
				// Russian (RU) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_ru() {
			return $this->GetListOfLanguageCodes_Russian();
		}
		
		public function GetListOfLanguageCodes_Russian() {
			return [
				'de'=>'Немецкий',
				'en'=>'Английский',
				'es'=>'Испанский',
				'fr'=>'Французский',
				'it'=>'Итальянский',
				'ja'=>'Японский',
				'nl'=>'Голландский',
				'pl'=>'Польский',
				'pt'=>'Португальский',
				'ru'=>'Русский',
				'tr'=>'Турецкий',
				'zh'=>'Китайский',
			];
		}
		
				// Turkish (TR) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_tr() {
			return $this->GetListOfLanguageCodes_Turkish();
		}
		
		public function GetListOfLanguageCodes_Turkish() {
			return [
				'de'=>'Almanca',
				'en'=>'İngilizce',
				'es'=>'İspanyol',
				'fr'=>'Fransızca',
				'it'=>'İtalyan',
				'ja'=>'Japonca',
				'nl'=>'Flemenkçe',
				'pl'=>'Lehçe',
				'pt'=>'Portekizce',
				'ru'=>'Rusça',
				'tr'=>'Türkçe',
				'zh'=>'Çince',
			];
		}
		
				// Chinese (ZH) Lists
				// --------------------------------------------------------
		
		public function GetListOfLanguageCodes_zh() {
			return $this->GetListOfLanguageCodes_Chinese();
		}
		
		public function GetListOfLanguageCodes_Chinese() {
			return [
				'de'=>'德语',
				'en'=>'英语',
				'es'=>'西班牙语',
				'fr'=>'法语',
				'it'=>'意大利语',
				'ja'=>'日本',
				'nl'=>'菏兰语',
				'pl'=>'抛光',
				'pt'=>'葡萄牙语',
				'ru'=>'俄语',
				'tr'=>'土耳其',
				'zh'=>'汉语',
			];
		}
				
/*		
German.
English.
Spanish.
French.
Italian.
Japanese.
Dutch.
Polish.
Portuguese.
Russian.
Turkish.
Chinese.
*/
	}

?>
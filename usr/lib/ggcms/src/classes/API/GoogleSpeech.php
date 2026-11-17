<?php

	/*
		Google's speech, for a page that wants every visitor to hear the same
		voice.

		The browser's own speechSynthesis reads with whatever voices the
		browser happens to ship -- Chrome has dozens, Brave a handful, and a
		word pronounced on one is a different word on the other.  This asks
		Google's Text-to-Speech instead, so the audio is the same everywhere.

		Nothing is stored.  The text goes to Google, the MP3 comes back as
		base64 and goes straight on to the browser in the JSON answer.  With
		no language chosen, Google's Translation API names the language first;
		with no voice named, Google picks its own default voice for it.

		The API key is read from php.ini as ggcms.google_speech_key, beside
		the database credentials and the Cloudflare token, and never in the
		repository.  Where it is not set every method answers FALSE.

		Both APIs are billed by the character, and the page that calls this
		takes text from anyone, so each domain has a daily allowance, counted
		in a one-line file under GGCMS_LOG_DIR.  Past it, nothing is sent.
	*/

	class GoogleSpeech {
		public $domain;
		public $log_directory;

		public function __construct($args) {
			$this->domain = $args['domain'];
			$this->log_directory = $args['logdirectory'] ?? GGCMS_LOG_DIR;	# a test's own folder, otherwise the host's

			return TRUE;
		}

			// Languages
			// -----------------------------------------------

			/*
				Every language Google has a voice for, one voice each, in
				alphabetical order for PronounceThat's dropdown.  EarthFluent's
				buttons send only their own course's language.

				Keyed by the code Translation's detection answers, which is not
				always the voice's: Filipino is detected as tl, Norwegian as no.
				Where Google has a language in more than one country, the
				country is EarthFluent's -- Brazilian Portuguese, as its course
				is, and Latin American Spanish, as its browser voices were.
			*/

		public function Languages() {
			return [
				'af'=>['title'=>'Afrikaans', 'locale'=>'af-ZA'],
				'am'=>['title'=>'Amharic', 'locale'=>'am-ET'],
				'ar'=>['title'=>'Arabic', 'locale'=>'ar-XA'],
				'eu'=>['title'=>'Basque', 'locale'=>'eu-ES'],
				'bn'=>['title'=>'Bengali', 'locale'=>'bn-IN'],
				'bg'=>['title'=>'Bulgarian', 'locale'=>'bg-BG'],
				'ca'=>['title'=>'Catalan', 'locale'=>'ca-ES'],
				'yue'=>['title'=>'Chinese (Cantonese)', 'locale'=>'yue-HK'],
				'zh'=>['title'=>'Chinese (Mandarin)', 'locale'=>'cmn-CN'],
				'hr'=>['title'=>'Croatian', 'locale'=>'hr-HR'],
				'cs'=>['title'=>'Czech', 'locale'=>'cs-CZ'],
				'da'=>['title'=>'Danish', 'locale'=>'da-DK'],
				'nl'=>['title'=>'Dutch', 'locale'=>'nl-NL'],
				'en'=>['title'=>'English', 'locale'=>'en-US'],
				'et'=>['title'=>'Estonian', 'locale'=>'et-EE'],
				'tl'=>['title'=>'Filipino', 'locale'=>'fil-PH'],
				'fi'=>['title'=>'Finnish', 'locale'=>'fi-FI'],
				'fr'=>['title'=>'French', 'locale'=>'fr-FR'],
				'gl'=>['title'=>'Galician', 'locale'=>'gl-ES'],
				'de'=>['title'=>'German', 'locale'=>'de-DE'],
				'el'=>['title'=>'Greek', 'locale'=>'el-GR'],
				'gu'=>['title'=>'Gujarati', 'locale'=>'gu-IN'],
				'he'=>['title'=>'Hebrew', 'locale'=>'he-IL'],
				'hi'=>['title'=>'Hindi', 'locale'=>'hi-IN'],
				'hu'=>['title'=>'Hungarian', 'locale'=>'hu-HU'],
				'is'=>['title'=>'Icelandic', 'locale'=>'is-IS'],
				'id'=>['title'=>'Indonesian', 'locale'=>'id-ID'],
				'it'=>['title'=>'Italian', 'locale'=>'it-IT'],
				'ja'=>['title'=>'Japanese', 'locale'=>'ja-JP'],
				'kn'=>['title'=>'Kannada', 'locale'=>'kn-IN'],
				'ko'=>['title'=>'Korean', 'locale'=>'ko-KR'],
				'lv'=>['title'=>'Latvian', 'locale'=>'lv-LV'],
				'lt'=>['title'=>'Lithuanian', 'locale'=>'lt-LT'],
				'ms'=>['title'=>'Malay', 'locale'=>'ms-MY'],
				'ml'=>['title'=>'Malayalam', 'locale'=>'ml-IN'],
				'mr'=>['title'=>'Marathi', 'locale'=>'mr-IN'],
				'no'=>['title'=>'Norwegian', 'locale'=>'nb-NO'],
				'pl'=>['title'=>'Polish', 'locale'=>'pl-PL'],
				'pt'=>['title'=>'Portuguese', 'locale'=>'pt-BR'],
				'pa'=>['title'=>'Punjabi', 'locale'=>'pa-IN'],
				'ro'=>['title'=>'Romanian', 'locale'=>'ro-RO'],
				'ru'=>['title'=>'Russian', 'locale'=>'ru-RU'],
				'sr'=>['title'=>'Serbian', 'locale'=>'sr-RS'],
				'sk'=>['title'=>'Slovak', 'locale'=>'sk-SK'],
				'sl'=>['title'=>'Slovenian', 'locale'=>'sl-SI'],
				'es'=>['title'=>'Spanish', 'locale'=>'es-US'],
				'sw'=>['title'=>'Swahili', 'locale'=>'sw-KE'],
				'sv'=>['title'=>'Swedish', 'locale'=>'sv-SE'],
				'ta'=>['title'=>'Tamil', 'locale'=>'ta-IN'],
				'te'=>['title'=>'Telugu', 'locale'=>'te-IN'],
				'th'=>['title'=>'Thai', 'locale'=>'th-TH'],
				'tr'=>['title'=>'Turkish', 'locale'=>'tr-TR'],
				'uk'=>['title'=>'Ukrainian', 'locale'=>'uk-UA'],
				'ur'=>['title'=>'Urdu', 'locale'=>'ur-IN'],
				'vi'=>['title'=>'Vietnamese', 'locale'=>'vi-VN'],
			];
		}

			// Key()
			// Tests: GoogleSpeechTest::testKey()
			// Test file: tests/src/classes/API/GoogleSpeechTest.php
		public function Key() {
			$key = getenv('GGCMS_GOOGLE_SPEECH_KEY');

			if($key === FALSE || $key === '') {
				$key = get_cfg_var('ggcms.google_speech_key');
			}

			if(!is_string($key) || !preg_match('#\A[A-Za-z0-9_-]{20,}\z#', $key)) {
				return FALSE;
			}

			return $key;
		}

			// Pronounce
			// -----------------------------------------------

			/*
				The whole round: detect when asked to, then synthesise.  Answers
				a hash with 'error' set to one of none, key, allowance, detect,
				unknown or synthesis, so the script can say which.
			*/

		public function Pronounce($args) {
			$text = $args['text'];
			$language = $args['language'];
			$daily_characters = $args['dailycharacters'];

			$languages = $this->Languages();

			if($this->Key() === FALSE) {
				return ['error'=>'key'];
			}

				// detection reads the text once, synthesis once more
			$characters = mb_strlen($text, 'UTF-8') * ($language === 'auto' ? 2 : 1);

			if(!$this->Pronounce_Allowance(['characters'=>$characters, 'dailycharacters'=>$daily_characters])) {
				return ['error'=>'allowance'];
			}

			if($language === 'auto') {
				$language = $this->Detect(['text'=>$text]);

				if($language === FALSE) {
					return ['error'=>'detect'];
				}

				if(!isset($languages[$language])) {
					return ['error'=>'unknown', 'language'=>$language];
				}
			}

			$audio = $this->Synthesize([
				'text'=>$text,
				'locale'=>$languages[$language]['locale'],
			]);

			if($audio === FALSE) {
				return ['error'=>'synthesis'];
			}

			return [
				'error'=>'none',
				'language'=>$language,
				'audio'=>$audio,
			];
		}

			/*
				One file a day per domain holding the characters sent so far.
				Locked, so two visitors at once cannot both read the old count.
				A count that cannot be kept refuses the request -- an allowance
				that fails open is not one.
			*/

			// Pronounce_Allowance()
			// Tests: GoogleSpeechTest::testPronounce_Allowance()
			// Test file: tests/src/classes/API/GoogleSpeechTest.php
		public function Pronounce_Allowance($args) {
			$characters = $args['characters'];
			$daily_characters = $args['dailycharacters'];

			$directory = $this->log_directory . $this->domain . '/speech/';

			if(!is_dir($directory)) {
				@mkdir($directory, 0755, TRUE);
			}

			$handle = @fopen($directory . gmdate('Y-m-d') . '.txt', 'c+');

			if(!$handle) {
				return FALSE;
			}

			if(!flock($handle, LOCK_EX)) {
				fclose($handle);
				return FALSE;
			}

			$used = (int)stream_get_contents($handle);
			$allowed = ($used + $characters) <= $daily_characters;

			if($allowed) {
				ftruncate($handle, 0);
				rewind($handle);
				fwrite($handle, (string)($used + $characters));
				fflush($handle);
			}

			flock($handle, LOCK_UN);
			fclose($handle);

			return $allowed;
		}

			// Google
			// -----------------------------------------------

		public function Detect($args) {
			$response = $this->Request([
				'url'=>'https://translation.googleapis.com/language/translate/v2/detect',
				'body'=>['q'=>$args['text']],
			]);

			if($response === FALSE) {
				return FALSE;
			}

			return $this->DetectLanguageCode([
				'language'=>$response['data']['detections'][0][0]['language'] ?? '',
			]);
		}

			/*
				Translation answers zh-CN, zh-TW, pt-PT and the like; the list
				here is keyed by the bare language.  It still answers Hebrew
				with the code withdrawn in 1989, iw, as well as he.
			*/

			// DetectLanguageCode()
			// Tests: GoogleSpeechTest::testDetectLanguageCode()
			// Test file: tests/src/classes/API/GoogleSpeechTest.php
		public function DetectLanguageCode($args) {
			$language = strtolower(trim((string)$args['language']));
			$language = explode('-', $language)[0];

			if($language === 'iw') {
				$language = 'he';
			}

			if(!preg_match('#\A[a-z]{2,3}\z#', $language)) {
				return FALSE;
			}

			return $language;
		}

		public function Synthesize($args) {
			$response = $this->Request([
				'url'=>'https://texttospeech.googleapis.com/v1/text:synthesize',
				'body'=>[
					'input'=>['text'=>$args['text']],
					'voice'=>['languageCode'=>$args['locale']],
					'audioConfig'=>['audioEncoding'=>'MP3'],
				],
			]);

			$audio = $response['audioContent'] ?? '';

			if(!is_string($audio) || $audio === '') {
				return FALSE;
			}

			return $audio;	# base64 already, as the browser wants it
		}

			/*
				The key goes in a header, not the query string, so it is never
				in a URL anyone logs.  Seconds, not the default minutes: a
				visitor is waiting on the button.
			*/

		public function Request($args) {
			$request = curl_init($args['url']);

			curl_setopt_array($request, [
				CURLOPT_POST=>TRUE,
				CURLOPT_POSTFIELDS=>json_encode($args['body']),
				CURLOPT_HTTPHEADER=>[
					'Content-Type: application/json; charset=utf-8',
					'X-Goog-Api-Key: ' . $this->Key(),
				],
				CURLOPT_RETURNTRANSFER=>TRUE,
				CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4,	# the key is restricted to the host's IPv4 address
				CURLOPT_CONNECTTIMEOUT=>3,
				CURLOPT_TIMEOUT=>10,
			]);

			$response = curl_exec($request);
			$status = curl_getinfo($request, CURLINFO_HTTP_CODE);

			if($status !== 200 || !is_string($response)) {
				return FALSE;
			}

			$decoded = json_decode($response, TRUE);

			if(!is_array($decoded)) {
				return FALSE;
			}

			return $decoded;
		}
	}

?>

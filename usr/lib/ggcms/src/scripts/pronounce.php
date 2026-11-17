<?php

	/*
		/pronounce.json -- a word or phrase, read aloud by Google, for a page
		whose speaker button should sound the same in every browser.

		POST text, and language: a code from GoogleSpeech::Languages(), or
		auto to have Google name it.  The answer is JSON: the language read
		and the MP3 as base64, or an error saying why there is none.

		Google bills by the character and the text comes from anyone, so it
		is off unless a domain turns it on, and each domain that does has a
		daily allowance -- both in AbstractGlobals, the same way as
		randomwords.json.  See com.pronouncethat/scripts/pronounce.php.

		POST only.  A GET is a link, and a crawler following one would
		spend the allowance on nothing.
	*/

	class pronounce extends basicscript {
		public $rpc_results;

						// Security Data
						// ---------------------------------------------

		public function IsSecure() {
			return FALSE;
		}

		public function RequiresLogin() {
			return FALSE;
		}

						// Functionality
						// ---------------------------------------------

		public function display() {
			$globals = $this->handler->abstractglobals->script;

			if(!is_object($globals) || !method_exists($globals, 'Speech_enabled') || !$globals->Speech_enabled()) {
				return FALSE;	# 404
			}

			if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
				return FALSE;	# 404
			}

			header('Cache-Control: no-store');

			ggreq('classes/API/GoogleSpeech.php');

			$speech = new GoogleSpeech([
				'domain'=>$this->handler->domain->primary_domain_lowercased,
			]);

				// the query cleanser leaves entities; Google would read "&amp;" aloud
			$text = trim(html_entity_decode((string)$this->Param('text'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			$language = (string)$this->Param('language');

			if($text === '') {
				return $this->display_Answer(['error'=>'empty']);
			}

			if(mb_strlen($text, 'UTF-8') > $globals->Speech_characters_per_request()) {
				return $this->display_Answer(['error'=>'long', 'limit'=>$globals->Speech_characters_per_request()]);
			}

			if($language !== 'auto' && !array_key_exists($language, $speech->Languages())) {
				return $this->display_Answer(['error'=>'language']);
			}

			return $this->display_Answer($speech->Pronounce([
				'text'=>$text,
				'language'=>$language,
				'dailycharacters'=>$globals->Speech_characters_per_day(),
			]));
		}

		public function display_Answer($args) {
			$this->rpc_results = $args;	# what SetRecordToUseForMetadata() hands the JSON format

			return TRUE;
		}
	}

?>

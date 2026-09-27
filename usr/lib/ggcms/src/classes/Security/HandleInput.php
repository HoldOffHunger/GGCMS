<?php

	class HandleInput {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$html_entity_characters = new HTMLEntities();
			$this->html_entity_characters = $html_entity_characters;
			
			$utf8_characters = new UTF8Characters();
			$this->utf8_characters = $utf8_characters;
			
			$phishing_characters = new PhishingCharacters();
			$this->phishing_characters = $phishing_characters;
		}
		
			// FormatTitleOuput()
			// Tests: HandleInputTest::testFormatTitleOuput()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function FormatTitleOuput($args) {
			$text = $args['text'];
			
			if(strlen($text) > 50) {
				$text = substr($text, 0, 50);
				$text = rtrim($text);
				$text .= '...';
			}
			
			return $text;
		}
		
			// FormatListOutput()
			// Tests: HandleInputTest::testFormatListOutput()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function FormatListOutput($args) {
			if(!$this->ValidToFormatListOutput($args)) {
				return $args['text'];		# $text is not assigned until below
			}
			
			$text = $args['text'];
			$text = $this->StripBCMLCode(['text'=>$text]);
			$text = $this->StripCitationMarks(['text'=>$text]);
			$text = $this->StripCitationNumbers(['text'=>$text]);
			$text = $this->StripExcessiveDashes(['text'=>$text]);
			$text = $this->SwapHTMLWithSpaces(['text'=>$text]);
			$text = $this->StripTags(['text'=>$text]);
			
			#$text = $this->DecodeHTMLEntities(['text'=>$text]);
			$text = $this->SwapMultipleSpacesWithSingleSpaces(['text'=>$text]);
			$text = $this->HandlePossibleUTF8Corruption(['text'=>$text]);
			$text = $this->TrimText(['text'=>$text]);
			$text = $this->AppendTruncatingPeriods(['text'=>$text]);
			
			return $text;
		}
		
			// ValidToFormatListOutput()
			// Tests: HandleInputTest::testValidToFormatListOutput()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function ValidToFormatListOutput($args) {
			$text = $args['text'];
			
			if(strlen($text) < 1) {
				return FALSE;
			}
			
			return TRUE;
		}
		
			// StripBCMLCode()
			// Tests: HandleInputTest::testStripBCMLCode()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function StripBCMLCode($args) {
			$text = $args['text'];
			
			$text = preg_replace('/FullImage::(\d+)/', ' ', $text);
			$text = preg_replace('/Image::(\d+)/', ' ', $text);
			
			return $text;
		}
		
			// StripCitationMarks()
			// Tests: HandleInputTest::testStripCitationMarks()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function StripCitationMarks($args) {
			$text = $args['text'];
			
				/*
					* dagger double-dagger pilcrow.  These were once the
					Windows-1252 bytes 86 87 B6, which in UTF-8 text are not
					characters but the tails of others -- C4 86 is C-acute,
					C4 87 is c-acute, D0 B6 is Cyrillic zhe -- so they cut those
					in half.  Matched
					now as the UTF-8 characters they were meant to be.
				*/
			
			$text = preg_replace('/\*|\xE2\x80\xA0|\xE2\x80\xA1|\xC2\xB6/', '', $text);
			
			return $text;
		}
		
			// StripCitationNumbers()
			// Tests: HandleInputTest::testStripCitationNumbers()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function StripCitationNumbers($args) {
			$text = $args['text'];
			
			$text = preg_replace("/\[[\d\s]+\]/", '', $text);
			$text = preg_replace("/\([\d\s]+\)/", '', $text);
			
			return $text;
		}
		
		public function StripExcessiveDashes($args) {
			$text = $args['text'];
			
			$text = $this->StripCommonDashes(['text'=>$text]);
			$text = $this->StripUncommonDashes(['text'=>$text]);
			
			return $text;
		}
		
			// StripCommonDashes()
			// Tests: HandleInputTest::testStripCommonDashes()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function StripCommonDashes($args) {
			$text = $args['text'];
			
			$text = preg_replace("/[-]{4,1000}/", ' ', $text);
			$text = preg_replace("/[_]{2,1000}/", ' ', $text);
			
			return $text;
		}
		
			// StripUncommonDashes()
			// Tests: HandleInputTest::testStripUncommonDashes()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function StripUncommonDashes($args) {
			$text = $args['text'];
			
				/*
					Runs of four or more em dashes, then en dashes, as UTF-8.
					They were the Windows-1252 bytes 97 and 96, which UTF-8
					text only holds inside other characters.
				*/
			
			$text = preg_replace('/(?:\xE2\x80\x94){4,1000}/', ' ', $text);
			$text = preg_replace('/(?:\xE2\x80\x93){4,1000}/', ' ', $text);
			
			return $text;
		}
		
			// SwapHTMLWithSpaces()
			// Tests: HandleInputTest::testSwapHTMLWithSpaces()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function SwapHTMLWithSpaces($args) {
			$text = $args['text'];
			
			$preformat = $this->GetReplaceableHTMLAndTextSpacing();
			
			$text = str_replace($preformat, ' ', $text);
			
			return $text;
		}
		
		public function GetReplaceableHTMLAndTextSpacing() {
			$replaceable_whitespacing = [];
			
			$replaceable_whitespacing = array_merge($replaceable_whitespacing, $this->GetReplaceableHTMLSpacing());
			$replaceable_whitespacing = array_merge($replaceable_whitespacing, $this->GetReplaceableTextSpacing());
			
			return $replaceable_whitespacing;
		}
		
			// GetReplaceableHTMLSpacing()
			// Tests: HandleInputTest::testGetReplaceableHTMLSpacing()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function GetReplaceableHTMLSpacing() {
			$replaceable_tags = $this->GetReplaceableHTMLTags();
			$replaceable_tags_count = count($replaceable_tags);
			
			$replaceable_html = [];
			
			for($i = 0; $i < $replaceable_tags_count; $i++) {
				$replaceable_tag = $replaceable_tags[$i];
				$replaceable_html[] = '<' . $replaceable_tag . '>';
				$replaceable_html[] = '<' . $replaceable_tag . ' >';
				$replaceable_html[] = '<' . $replaceable_tag . '/>';
				$replaceable_html[] = '<' . $replaceable_tag . ' />';
			}
			
			return $replaceable_html;
		}
		
		public function GetReplaceableHTMLTags() {
			$replaceable = [
				'hr',
				'br',
				'p',
			];
			
			return $replaceable;
		}
		
		public function GetReplaceableTextSpacing() {
			$replaceable = [
				"\t",
				"\n",
				"\r",
				'&nbsp;',
			];
			
			return $replaceable;
		}
		
		public function StripTags($args) {	
			$text = $args['text'];
			
			$text = strip_tags($text);
			
			return $text;
		}
		
		public function DecodeHTMLEntities($args) {
			$text = $args['text'];
			
			$text = html_entity_decode($text);
			
			return $text;
		}
		
			// SwapMultipleSpacesWithSingleSpaces()
			// Tests: HandleInputTest::testSwapMultipleSpacesWithSingleSpaces()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function SwapMultipleSpacesWithSingleSpaces($args) {
			$text = $args['text'];
			
			$text = preg_replace('!\s+!', ' ', $text);
			
			return $text;
		}
		
			// HandlePossibleUTF8Corruption()
			// Tests: HandleInputTest::testHandlePossibleUTF8Corruption()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function HandlePossibleUTF8Corruption($args) {
			$text = $args['text'];
			
				/*
					Keeps printable ASCII and every well-formed UTF-8 character;
					drops control characters, the C1 controls, and any byte that
					is not part of a well-formed sequence.
					
					This was [[:^print:]], which without the u flag means every
					byte above 7F, so an index excerpt of "Rossiya -- deja vu"
					written in Cyrillic with an em dash and accents came out
					"dj vu", and Japanese came out as nothing at all.
					The alternation is the W3C's well-formed UTF-8 pattern.
				*/
			
			$text = preg_replace('/(?:[\x20-\x7E]|\xC2[\xA0-\xBF]|[\xC3-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})(*SKIP)(*FAIL)|./s', '', $text);
			
			return $text;
		}
		
			// TrimText()
			// Tests: HandleInputTest::testTrimText()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function TrimText($args) {
			$text = $args['text'];
			
			$text = trim($text);
			
			return $text;
		}
		
			// AppendTruncatingPeriods()
			// Tests: HandleInputTest::testAppendTruncatingPeriods()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function AppendTruncatingPeriods($args) {
			$text = $args['text'];
			
				/*
					The last character, not the last byte: excerpts keep UTF-8
					now, and a Japanese sentence ending in its own full stop
					would otherwise read as unfinished.
				*/
			
			$last_char = mb_substr($text, -1, 1, 'UTF-8');
			
			if(!in_array($last_char, ['.', '!', '?', "\u{3002}", "\u{FF01}", "\u{FF1F}"], TRUE)) {
				$text .= '...';
			}
			
			return $text;
		}
		
		public function CleanseInput($args) {
			$input = $args['input'];
			
				// Handle Input
			
			$cleansed_input = $input;
			
				// HTML Entities
			
			$cleanse_input_html_entities_args = [
				'input'=>$cleansed_input,
				'format'=>$this->CleanseInput_Format(),
			];
			
			$cleansed_input = $this->html_entity_characters->CleanseInput_HTMLEntities($cleanse_input_html_entities_args)['cleansedinput'];
			
				// UTF-8 Conversion
				
			$cleanse_escape_bit_variable_chars = [
				'input'=>$cleansed_input,
			];
				
			$cleansed_input = $this->CleanseInput_EscapeBitVariableChars($cleanse_escape_bit_variable_chars)['cleansedinput'];
			
				// Phishing Characters Conversion
			
			$cleanse_input_phishing_characters_args = [
				'input'=>$cleansed_input,
			];
			
			$cleansed_input = $this->phishing_characters->CleanseInput_PhishingCharacters($cleanse_input_phishing_characters_args)['cleansedinput'];
			
				// Return Results
			
			$cleanse_input_results = [
				'cleansedinput'=>$cleansed_input,
			];
			
			return $cleanse_input_results;
		}
		
		public function CleanseInput_EscapeBitVariableChars($args) {
			$input = $args['input'];
			
			$cleanse_input_utf8_args = [
				'input'=>$input,
				'format'=>$this->CleanseInput_Format(),
			];
			
			$cleansed_input = $this->utf8_characters->CleanseInput_UTF8($cleanse_input_utf8_args)['cleansedinput'];
			
			return [
				'cleansedinput'=>$cleansed_input,
			];
		}
		
			// CleanseInput_Integer()
			// Tests: HandleInputTest::testCleanseInput_Integer()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function CleanseInput_Integer($args) {
			$input = $args['input'];
			
			$cleansed_input = $input;
			
			$cleansed_input = (int)$cleansed_input;
			
			$cleanse_input_results = [
				'cleansedinput'=>$cleansed_input,
			];
			
			return $cleanse_input_results;
		}
		
		public function EscapeHTML($args) {
			$input = $args['input'];
			
			$cleansed_input = $input;
			
			$cleansed_input = htmlentities($cleansed_input, ENT_QUOTES | ENT_HTML401, $this->CleanseInput_Format());
			
			$cleanse_input_results = ['cleansedinput'=>$cleansed_input];
			
			return $cleanse_input_results;
		}
		
			// CleanseInput_Filename()
			// Tests: HandleInputTest::testCleanseInput_Filename()
			// Test file: tests/src/classes/Security/HandleInputTest.php
		public function CleanseInput_Filename($args) {
			$input = $args['input'];
			
			$cleansed_input = $input;
			
			$cleansed_input = str_replace('/', '', $cleansed_input);
			
			$cleanse_input_results = [
				'cleansedinput'=>$cleansed_input,
			];
			
			return $cleanse_input_results;
		}
		
		public function CleanseInput_GetQuery() {
			$query = $_GET;
			unset($query['url']);
			
			$query_string_array = [];
			
				/*
					PHP reads filter[]=x as an array, and htmlentities() takes
					only a string, so any query with brackets in it was a 500 on
					every page of every site -- this runs for the alternate links
					in every head.  Scanners send them constantly.  Each value of
					an array goes back out as name[]=value; an array nested
					inside one is dropped, since no link here makes one.
				*/
			
			foreach($query as $key => $value) {
				$getquery_cleansing_key_args = [
					'input'=>(string)$key,
				];
				
				$cleansed_key = $this->CleanseInput_GetQuery_Cleanse($getquery_cleansing_key_args)['cleansedinput'];
				
				$key_suffix = is_array($value) ? '[]' : '';
				$values = is_array($value) ? $value : [$value];
				
				foreach($values as $single_value) {
					if(!is_scalar($single_value) && $single_value !== NULL) {
						continue;
					}
					
					$getquery_cleansing_value_args = [
						'input'=>(string)$single_value,
					];
					
					$cleansed_value = $this->CleanseInput_GetQuery_Cleanse($getquery_cleansing_value_args)['cleansedinput'];
					
					$cleansed_key_to_value = $cleansed_key . $key_suffix . "=" . $cleansed_value;
					
					$query_string_array[] = $cleansed_key_to_value;
				}
			}
			
			$query_string = implode("&", $query_string_array);
			
			return ($query_string);
		}
		
		public function CleanseInput_GetQuery_Cleanse($args) {
			$input = $args['input'];
			
				// Handle Input
			
			$cleansed_input = $input;
			
				// HTML Entities
			
			$cleanse_input_html_entities_args = [
				'input'=>$cleansed_input,
				'format'=>$this->CleanseInput_Format(),
			];
			
			$cleansed_input = $this->html_entity_characters->CleanseInput_HTMLEntities($cleanse_input_html_entities_args)['cleansedinput'];
			
				// UTF-8 Conversion
			
			$cleanse_input_utf8_args = [
				'input'=>$cleansed_input,
				'format'=>$this->CleanseInput_Format(),
			];
			
			$cleansed_input = $this->utf8_characters->CleanseInput_UTF8($cleanse_input_utf8_args);
			$cleansed_input = $cleansed_input['cleansedinput'];
			
				// Return Results
			
			$cleanse_input_results = [
				'cleansedinput'=>$cleansed_input,
			];
			
			return $cleanse_input_results;
		}
		
		public function CleanseInput_Format() {
			return 'UTF-8';
		}
	}

?>
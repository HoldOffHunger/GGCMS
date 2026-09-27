<?php

	class TextCleanup
	{
		public $grammar;
		
		
						// Construction
						// ------------------------------------------------------------------------
		
		public function __construct($args)
		{
			$this->grammar = $args['grammar'];
		}
		
						// Before-Start Prepare Functions
						// ------------------------------------------------------------------------
		
			// PrepareSentences()
			// Tests: TextCleanupTest::testPrepareSentences()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function PrepareSentences($args)
		{
			$text = $this->EscapeCommonFalsePositiveSentenceEnds(['text'=>$args['text']]);
			
			$possible_sentences = preg_split('/[\.\n\r]/', $text);
			
			$sentences = [];
			
			$possible_sentences_count = count($possible_sentences);
			
			for($i = 0; $i < $possible_sentences_count; $i++)
			{
				$possible_sentence = $this->PrepareSentence(['sentence'=>$possible_sentences[$i]]);
				
				if(strlen($possible_sentence) > 10)
				{
					$sentences[] = $possible_sentence;
					
					$subsentences = $this->GetSubSentences(['text'=>$possible_sentence]);
					
					if($subsentences && count($subsentences))
					{
						foreach($subsentences as $subsentence)
						{
							$sentences[] = $this->PrepareSentence(['sentence'=>$subsentence]);
						}
					}
				}
			}
			
			return $sentences;
		}
		
			// GetSubSentences()
			// Tests: TextCleanupTest::testGetSubSentences()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function GetSubSentences($args)
		{
			$text = $args['text'];
			
			$text_pieces = explode(':', $text);
			$text_pieces_counts = count($text_pieces);
			
			$subsentences = [];
			
			if($text_pieces_counts > 1)
			{
				unset($text_pieces[0]);
				$new_sentence = implode(':', $text_pieces);
				
				if(strlen($new_sentence) > 10)
				{
					$subsentences[] = $new_sentence;
				}
			}
			
			return $subsentences;
		}
		
			// PrepareSentence()
			// Tests: TextCleanupTest::testPrepareSentence()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function PrepareSentence($args)
		{
			$sentence = $args['sentence'];
			
			$escape_pattern = $this->EscapeSequencesPattern(['escapesequences'=>$this->GetEscapeSequences()]);
			
			$sentence = preg_replace('/\A' . $escape_pattern . '+|' . $escape_pattern . '+\z/', '', $sentence);
			
			$sentence = preg_replace('/(?:\s|' . $escape_pattern . ')+/', ' ', $sentence);
			
			return $sentence;
		}
		
			// PrepareSentenceFully()
			// Tests: TextCleanupTest::testPrepareSentenceFully()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function PrepareSentenceFully($args)
		{
			$sentence = $args['sentence'];
			
			$escape_pattern = $this->EscapeSequencesPattern(['escapesequences'=>$this->GetFullEscapeSequences()]);
			
			$sentence = preg_replace('/\A' . $escape_pattern . '+|' . $escape_pattern . '+\z/', '', $sentence);
			
			$sentence = preg_replace('/(?:\s|' . $escape_pattern . ')+/', ' ', $sentence);
			
			return $sentence;
		}
		
			// EscapeSequencesPattern()
			// Tests: TextCleanupTest::testEscapeSequencesPattern()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
			/*
				The escape sequences hold the UTF-8 no-break space, C2 A0, and
				a bare A0, the Latin-1 one.  They used to go to trim() and into
				a [...] class, where every byte counts alone -- so the A0 that
				ends "a-grave" (C3 A0) and Cyrillic "Er" (D0 A0) was cut out,
				and "voila" with its accent came back as invalid UTF-8.
				
				This matches C2 A0 whole, a bare A0 only where no multibyte
				character can own it, and everything else one byte at a time.
			*/
		
		public function EscapeSequencesPattern($args)
		{
			$escape_sequences = $args['escapesequences'];
			
			$single_bytes = str_replace([urldecode('%C2%A0'), urldecode('%A0')], '', $escape_sequences);
			
			return '(?:\xC2\xA0|(?<![\x80-\xFF])\xA0|[' . preg_quote($single_bytes, '/') . '])';
		}
			
			// GetEscapeSequences()
			// Tests: TextCleanupTest::testGetEscapeSequences()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function GetEscapeSequences()
		{
			$escape_sequences = 
				urldecode("%C2%A0") .
				urldecode("+") .
				urldecode("%A0") .
				urldecode("%20") .
				urldecode("%0D") .
				urldecode("%0A");
				
			return $escape_sequences;
		}
		
			// GetFullEscapeSequences()
			// Tests: TextCleanupTest::testGetFullEscapeSequences()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function GetFullEscapeSequences()
		{
			$escape_sequences = 
				urldecode("%C2%A0") .
				urldecode("+") .
				urldecode("%A0") .
				urldecode("%20") .
				urldecode("%21") .
				urldecode("%22") .
				urldecode("%23") .
				urldecode("%24") .
				urldecode("%25") .
				urldecode("%26") .
				urldecode("%27") .
				urldecode("%28") .
				urldecode("%29") .
				urldecode("%0D") .
				urldecode("%0A");
				
			return $escape_sequences;
		}
		
		public function EscapeCommonFalsePositiveSentenceEnds($args)
		{
			$text = $args['text'];
			
			$text = $this->CleanupTextBeforeEscaping(['text'=>$text]);
			$text = $this->EscapeAcronym(['line'=>$text]);
			$text = $this->EscapePeriodValidPhrase(['line'=>$text]);
			
			return $text;
		}
		
			// CleanupTextBeforeEscaping()
			// Tests: TextCleanupTest::testCleanupTextBeforeEscaping()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function CleanupTextBeforeEscaping($args)
		{
			$text = $args['text'];
			
				/*
					Curly quotes to straight ones, and below, an em dash given
					room.  These were the Windows-1252 bytes 94 93 92 91 and 97;
					in UTF-8 text those are only ever the tails of other
					characters -- the 94 of an em dash itself, the 93 of Cyrillic
					"Ge" -- which they cut in half.  Now the UTF-8 characters.
				*/
			
			$text = str_replace("\u{201D}", '\'', $text);
			$text = str_replace("\u{201C}", '\'', $text);
			$text = str_replace("\u{2019}", '\'', $text);
			$text = str_replace("\u{2018}", '\'', $text);
			$text = str_replace('`', '\'', $text);
			$text = str_replace('"', '\'', $text);
			
			$text = preg_replace('/\n/i', ' ', $text);
			$text = preg_replace('/\r/i', ' ', $text);
			
			$text = preg_replace('/[\s]*\?/i', '?', $text);
			$text = preg_replace('/[\s]*\!/i', '!', $text);
			
			$text = preg_replace('/----/i', ' ---- ', $text);
			$text = preg_replace('/---/i', ' --- ', $text);
			$text = preg_replace('/--/i', ' -- ', $text);
			
			$text = preg_replace('/\xE2\x80\x94/', " \u{2014} ", $text);
			
			$text = preg_replace('/ to-day /i', ' today ', $text);
			
			$text = preg_replace('/ isn\'t /i', ' is not ', $text);
			$text = preg_replace('/ aren\'t /i', ' are not ', $text);
			
			return $text;
		}
		
		public function EscapeAcronym($args)
		{
			$line = $args['line'];
			
			$acronyms = $this->grammar->Acronyms();
			
			foreach($acronyms as $acronym_index => $acronym)
			{
				$acronyms[$acronym_index] = implode('.', str_split(strtoupper($acronym))) . '.';
			}
			
			return $this->EscapeSinglePhrase(['line'=>$line, 'escapethis'=>$acronyms]);
		}
		
		public function EscapePeriodValidPhrase($args)
		{
			$line = $args['line'];
			
			$phrases = $this->grammar->AllValidPhrasesWithPeriods();
			
			return $this->EscapeSinglePhrase(['line'=>$line, 'escapethis'=>$phrases]);
		}
		
			// EscapeSinglePhrase()
			// Tests: TextCleanupTest::testEscapeSinglePhrase()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function EscapeSinglePhrase($args)
		{
			$line = ' ' . $args['line'] . ' ';
			$escape_this = $args['escapethis'];
			$escape_this_count = count($escape_this);
			
			for($i = 0; $i < $escape_this_count; $i++)
			{
				$escape_piece = $escape_this[$i];
				$escape_piece_delimited = str_replace('.', '_', $escape_piece);
				$escape_piece = str_replace('.', '\.', $escape_piece);
				
				$line = preg_replace('/[\s\b]' . $escape_piece . '[\s\b]/i', ' ' . $escape_piece_delimited . ' ', $line);
				$line = preg_replace('/[\s\b]\(' . $escape_piece . '/i', ' (' . $escape_piece_delimited . ' ', $line);
			}
			
			return $line;
		}
		
		public function UnescapeCommonFalsePositiveSentenceEnds($args)
		{
			$definitions = $args['definitions'];
			
			foreach ($definitions as $definition_key => $definition_values)
			{
				foreach($definition_values as $definition_index => $definition_line)
				{
					
					$definitions[$definition_key][$definition_index] = $this->UnescapeCommonFalsePositiveSentenceEndsSingular(['line'=>$definition_line]);
				}
			}
			
			return $definitions;
		}
		
		public function UnescapeCommonFalsePositiveSentenceEndsSingular($args)
		{
			$line = $args['line'];
			
			$line = $this->UnescapeAcronym(['line'=>$line]);
			$line = $this->UnescapePeriodValidPhrase(['line'=>$line]);
			
			return $line;
		}
		
		public function UnescapeAcronym($args)
		{
			$line = $args['line'];
			
			$acronyms = $this->grammar->Acronyms();
			
			foreach($acronyms as $acronym_index => $acronym)
			{
				$acronyms[$acronym_index] = implode('.', str_split(strtoupper($acronym))) . '.';
			}
			
			return $this->UnescapeSinglePhrase(['line'=>$line, 'escapethis'=>$acronyms]);
		}
		
		public function UnescapePeriodValidPhrase($args)
		{
			$line = $args['line'];
			
			$phrases = $this->grammar->AllValidPhrasesWithPeriods();
			
			return $this->UnescapeSinglePhrase(['line'=>$line, 'escapethis'=>$phrases]);
		}
		
			// UnescapeSinglePhrase()
			// Tests: TextCleanupTest::testUnescapeSinglePhrase()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function UnescapeSinglePhrase($args)
		{
			$line = ' ' . $args['line'] . ' ';
			$escape_this = $args['escapethis'];
			$escape_this_count = count($escape_this);
			
			for($i = 0; $i < $escape_this_count; $i++)
			{
				$escape_piece = $escape_this[$i];
				$escape_piece_delimited = str_replace('.', '_', $escape_piece);
				
				$line = preg_replace('/[\s\b]' . $escape_piece_delimited . '[\s\b]/i', ' ' . $escape_piece . ' ', $line);
				$line = preg_replace('/[\s\b]\(' . $escape_piece_delimited . '/i', ' (' . $escape_piece . ' ', $line);
			}
			
			return $line;
		}
		
						// Cleanse Functions
						// ------------------------------------------------------------------------
		
			// CleansePhrase()
			// Tests: TextCleanupTest::testCleansePhrase()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function CleansePhrase($args)
		{
			$phrase = $args['phrase'];
			
			$phrase = str_replace('*', '', $phrase);
			$phrase = preg_replace('/\A(?:\xE2\x80\x94|[\-_])+|(?:\xE2\x80\x94|[\-_])+\z/', '', $phrase);		# em dash, hyphen, underscore; trim() would cut bytes
			
			return trim($phrase);
		}
		
			// CleansePhraseFully()
			// Tests: TextCleanupTest::testCleansePhraseFully()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function CleansePhraseFully($args)
		{
			$phrase = $args['phrase'];
			
			$phrase = preg_replace('/\A(?:\xE2\x80[\x98\x99\x9C\x9D\x94]|[*"`\'\-_,;:!? ])+|(?:\xE2\x80[\x98\x99\x9C\x9D\x94]|[*"`\'\-_,;:!? ])+\z/', '', $phrase);		# curly quotes and em dash as UTF-8; trim() would cut bytes
			$phrase = str_replace('*', '', $phrase);
			$phrase = str_replace('(', '', $phrase);
			$phrase = str_replace(')', '', $phrase);
			$phrase = str_replace('[', '', $phrase);
			$phrase = str_replace(']', '', $phrase);
			
			return trim($phrase);
		}
		
			// BuildWords()
			// Tests: TextCleanupTest::testBuildWords()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function BuildWords($args)
		{
			$sentence = $args['sentence'];
			$sentence_pieces = explode(' ', $sentence);
			
			$sentence_pieces_to_compare = [];
			for($i = 0; $i < count($sentence_pieces); $i++)
			{
				$sentence_piece = strtolower($this->PrepareSentence(['sentence'=>$sentence_pieces[$i]]));
				$sentence_pieces_to_compare[$i] = $sentence_piece;
			}
			
			return $sentence_pieces_to_compare;
		}
		
			// BuildWordsFully()
			// Tests: TextCleanupTest::testBuildWordsFully()
			// Test file: tests/src/classes/Language/TextCleanupTest.php
		public function BuildWordsFully($args)
		{
			$sentence = $args['sentence'];
			$sentence_pieces = explode(' ', $sentence);
			
			$sentence_pieces_to_compare = [];
			for($i = 0; $i < count($sentence_pieces); $i++)
			{
				$sentence_piece = strtolower($this->PrepareSentenceFully(['sentence'=>$sentence_pieces[$i]]));
				$sentence_pieces_to_compare[$i] = $sentence_piece;
			}
			
			return $sentence_pieces_to_compare;
		}
	}

?>
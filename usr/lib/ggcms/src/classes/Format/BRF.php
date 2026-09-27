<?php

	class BRF extends AbstractBaseFormat {
		public $braille_handler;
		
			// TXT MimeType
			// -----------------------------------------------
		
		public function MimeType() {
			return 'text/plain';
			return 'application/braille';
		}
		
			// Display CSV
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			print($this->GenerateBraille());
			
			$this->generateIssues();
			
			return TRUE;
		}
		
		public function MaxGlyphIssuesPerRender() {
			return 20;
		}

		public function generateIssues() {
			#	'issuetype'=>'Missing Braille Glyph',
			#print_r($this->braille_handler->errors);
			
			$handler_errors = $this->braille_handler->errors;
			$handler_errors_count = count($handler_errors);
			
				/*
					Once per distinct glyph, not once per occurrence.
					
					Every createLog is a SELECT, an UPDATE and an instance INSERT
					against a database on another host, and this loop runs after
					the page has already been sent -- so a document with five
					hundred unsupported characters held a worker through fifteen
					hundred round trips to tell us the same six things.
					
					The description carries the word the character appeared in, so
					the same missing glyph becomes a different issue in every word
					that contains it: 2,407 tickets on revoltlib for about six
					characters.  Deduplicating here does not fix that -- the word
					would have to come out of the signature, which is a decision
					about the queue rather than about Braille -- but it does stop
					one render paying for the same glyph hundreds of times.
				*/

			$logged = [];
			$logged_count = 0;

			for($i = 0; $i < $handler_errors_count; $i++) {
				$handler_error = $handler_errors[$i];
				
				if($logged[$handler_error['description']]) {
					continue;
				}
				
				$logged[$handler_error['description']] = TRUE;
				$logged_count++;
				
				/*
					And a ceiling, because deduplicating is not enough on its own.
					
					The description carries the word the character appeared in, so a
					book generates a fresh signature for every word containing a bad
					character, and each one is an INSERT to a database on another
					host.  On 4 September 2026 war-and-peace as .brf spent 140
					seconds doing that and then died on the execution limit inside
					IssueLogging -- a 500 for the reader, a worker held for over two
					minutes, and 233 .brf requests in that day's log alone.  The same
					entry as HTML takes six seconds.
					
					Twenty distinct glyphs is far more than anyone needs to know that
					a character is unsupported, and it is a real fault worth seeing
					rather than one to suppress -- so it is capped, not silenced.
				*/
				
				if($logged_count >= $this->MaxGlyphIssuesPerRender()) {
					break;
				}
				
				
				$this->handler->issue_logging->createLog([
					'issuetype'=>'Missing Braille Glyph',
					'description'=>$handler_error['description'],
				]);
				
			#	print("BT: ERROR!");
				
			#	print_r($handler_error);
			}
			
			return TRUE;
		}
		
		public function GenerateBraille() {
			depreq('braille-handler/braille.php');
			
			$braille_input = $this->RunTemplates();
			
			$braille_input = str_replace('<p', "\n\n" . '<p', $braille_input);
			$braille_input = str_replace('<br>', "<br>\n", $braille_input);
			
			$braille_input = str_replace("&nbsp;", " ", $braille_input);
			$braille_input = html_entity_decode($braille_input);
			
			$braille_input = strip_tags($braille_input);
			
			$braille_input = $this->RunConversionTable(['output'=>$braille_input]);
			$braille_input = preg_replace("/\n[\n]+/", "\n\n", $braille_input);
			$braille_input = iconv('UTF-8', 'ASCII//TRANSLIT', $braille_input);
		#	print_r($braille_input);
		#	$braille_input = $this->fixSmartQuotes(['input'=>$braille_input]);
			
		#	$braille_input = mb_ereg_replace("—", "-", $braille_input);
		#	$braille_input = "mary" . html_entity_decode("&rsquo;", ENT_HTML401, 'UTF-8') . "s";
			
		#	print("<PRE>");
		#	print_r($braille_input);
		#	print("</PRE>");
			
			$requested_mode = $this->script->param('mode');
			
			switch($requested_mode) {
				case 'ascii':
				case 'dotted':
				case 'binary':
					$mode = $requested_mode;
					break;
					
				default:
					$mode = 'ascii';
			}
			
			if($mode !== 'ascii') {
				$braille_input = str_replace('&', 'and', $braille_input);
			}
			
			$this->braille_handler = new brailleHandler(['mode'=>$mode]);
			
			return $this->braille_handler->formattedOutput($braille_input);
		}
		
		public function fixSmartQuotes($args) {
			$input = $args['input'];
$chr_map = [
   // Windows codepage 1252
   
   '_' => ' ',
   "\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
   "\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
   "\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
   "\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
   "\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
   "\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
   "\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
   "\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

   // Regular Unicode     // U+0022 quotation mark (")
                          // U+0027 apostrophe     (')
   "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
   "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
   "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
   "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
   "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
   "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
   "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
   "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
   "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
   "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
   "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
   "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
   
   "\xC2\x80" => "\xE2\x82\xAC", // U+20AC Euro sign
   "\xC2\x83" => "\xC6\x92",     // U+0192 latin small letter f with hook
   "\xC2\x85" => "\xE2\x80\xA6", // U+2026 horizontal ellipsis
   "\xC2\x86" => "\xE2\x80\xA0", // U+2020 dagger
   "\xC2\x87" => "\xE2\x80\xA1", // U+2021 double dagger
   "\xC2\x88" => "\xCB\x86",     // U+02C6 modifier letter circumflex accent
   "\xC2\x89" => "\xE2\x80\xB0", // U+2030 per mille sign
   "\xC2\x8A" => "\xC5\xA0",     // U+0160 latin capital letter s with caron
   "\xC2\x8C" => "\xC5\x92",     // U+0152 latin capital ligature oe
   "\xC2\x8E" => "\xC5\xBD",     // U+017D latin capital letter z with caron
   "\xC2\x95" => "\xE2\x80\xA2", // U+2022 bullet
   "\xC2\x96" => "\xE2\x80\x93", // U+2013 en dash
   "\xC2\x97" => "\xE2\x80\x94", // U+2014 em dash
   "\xC2\x98" => "\xCB\x9C",     // U+02DC small tilde
   "\xC2\x99" => "\xE2\x84\xA2", // U+2122 trade mark sign
   "\xC2\x9A" => "\xC5\xA1",     // U+0161 latin small letter s with caron
   "\xC2\x9C" => "\xC5\x93",     // U+0153 latin small ligature oe
   "\xC2\x9E" => "\xC5\xBE",     // U+017E latin small letter z with caron
   "\xC2\x9F" => "\xC5\xB8",     // U+0178 latin capital letter y with diaeresis
];
$chr = array_keys  ($chr_map); // but: for efficiency you should
$rpl = array_values($chr_map); // pre-calculate these two arrays
$input = str_replace($chr, $rpl, html_entity_decode($input, ENT_QUOTES, "UTF-8"));
return $input;
		}
		
		/*
			The numeric references below are why 2,407 `Missing Braille Glyph`
			tickets name a `#`, and why several carry the word "and#151;".

			html_entity_decode() will not decode &#145; through &#159;.  They
			address the C1 control block, which it cannot represent, so it leaves
			them standing as literal text -- and the &-to-"and" replacement that
			dotted and binary mode perform in GenerateBraille() then rewrites `&#151;`
			into `and#151;`, which reaches the converter as eight characters, one
			of them a `#` that has no braille glyph.  That is why the mangled
			entities appear in no stored text and in no HTML output: this pipeline
			is what makes them.

			They are Windows-1252 punctuation mislabelled as Unicode, so they are
			translated here, before anything downstream can read them as an
			ampersand followed by a number.
		*/
		
		public function FormatConversionTable() {
			return [
				"\r"=>"",
				"\t"=>' ',
				
				'&#145;'=>"'",
				'&#146;'=>"'",
				'&#147;'=>'"',
				'&#148;'=>'"',
				'&#149;'=>'*',
				'&#150;'=>'-',
				'&#151;'=>'--',
				'&#152;'=>'~',
				'&#153;'=>'(TM)',
				'&#155;'=>'>',
				'&#156;'=>'oe',
				'&#158;'=>'z',
				'&#159;'=>'Y',
				
				/*
					ASCII punctuation with no braille glyph.
					
					These fifteen characters accounted for 2,685 `Missing Braille
					Glyph` tickets on revoltlib alone.  Once the Windows-1252
					references above stopped being mangled into `and#151;`, what
					remained was ordinary punctuation in the source text --
					hashtags, typewriter quotes, rules of underscores -- reaching a
					converter that has no cell for any of it.
					
					ORDER MATTERS.  str_replace works through this table in
					sequence, so the doubled backtick has to be listed before the
					single one or ``Anarchist becomes ""Anarchist.  And every entry
					here sits AFTER the numeric references above, so a stray
					&#NNN; is translated before the # rule could turn it into
					&hashtag NNN;.
					
					The words carry their own spacing because the source rarely
					does: `50%` has to read as `50 percent`, not `50percent`.  The
					cost is a double space where the source already had one, which
					is the cheaper of the two mistakes.
				*/
				
					//  TeX-style quotes, opened with `` and closed with ''.  The
					//  backtick has no braille cell at all; the doubled apostrophe
					//  has one and merely reads as two apostrophes, so it raised no
					//  ticket and was wrong anyway.  Both halves, or neither.
				
				'``'=>'"',
				"''"=>'"',
				'`'=>'"',
				
				'#'=>'hashtag ',
				'$'=>'dollars ',
				'%'=>' percent',
				'='=>' equals ',
				'^'=>' caret ',
				'<'=>' less than ',
				'>'=>' greater than ',
				'+'=>' plus ',
				'@'=>' at ',
				
					//  Stripped: separators and markup leakage, not prose.
				
				'_'=>'',
				'{'=>'',
				'}'=>'',
				'|'=>'',
				'~'=>'',
			];
		}
		
		public function RunConversionTable($args) {
			$output = $args['output'];
			
			$conversion_table = $this->FormatConversionTable();
			
			$output = str_replace(array_keys($conversion_table), array_values($conversion_table), $output);
			
			return $output;
		}
	}

?>
<?php

	class BRF extends AbstractBaseFormat {
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
		
		public function generateIssues() {
			#	'issuetype'=>'Missing Braille Glyph',
			#print_r($this->braille_handler->errors);
			
			$handler_errors = $this->braille_handler->errors;
			$handler_errors_count = count($handler_errors);
			
			for($i = 0; $i < $handler_errors_count; $i++) {
				$handler_error = $handler_errors[$i];
				
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
		
		public function FormatConversionTable() {
			return [
				"\r"=>"",
				"\t"=>' ',
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
<?php

	class RTF extends AbstractBaseFormat {
		public function MimeType() {
			return 'text/richtext';
		}
		
		public function ReadableInBrowser() {
			return FALSE;
		}
		
			// Display RTF
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$source_file_location = $this->SetSourceFileLocation();
			$rtf_file_location = $this->SetOutputFileLocation();
			
			$rtf_input = $this->RunTemplates();
			
			if(!$rtf_input) {
				return FALSE;
			}
			
			$old_rtf_input = '';
			
			if(is_file($source_file_location)) {
				$old_rtf_input = file_get_contents($source_file_location);
			}
			
			if($_GET['forceregen'] || !is_file($rtf_file_location) || $old_rtf_input != $rtf_input) {
				$rtf_output = $this->ConvertHTMLToFormat();
				
				$file_handle_for_source = fopen($rtf_file_location, 'w+');
				fwrite($file_handle_for_source, $rtf_output);
				fclose($file_handle_for_source);
				
				$file_handle_for_source = fopen($source_file_location, 'w+');
				fwrite($file_handle_for_source, $rtf_input);
				fclose($file_handle_for_source);
			}
			
			return readfile($rtf_file_location);
		}
		
		public function FormatConversionTable() {
			return [
				'<h1>'=>'\fs48 ',
				'</h1>'=>'\par ',
				
				'<h2>'=>'\fs40\par ',
				'</h2>'=>'\par ',
				
				'<p>'=>'\fs16\par ',
				'</p>'=>'\par ',
				
				'<li>'=>'\fs16\par ',
				'</li>'=>'\par ',
				
				'<br>'=>'\par ',
				
				'<blockquote>'=>'\par\fs16\li200\ri200 ',
				'</blockquote>'=>'\par\li0\ri0 ',
				
				'<b>'=>'\b ',
				'</b>'=>'\b0 ',
				'<strong>'=>'\b ',
				'</strong>'=>'\b0 ',
				
				'<i>'=>'\i ',
				'</i>'=>'\i0 ',
				'<em>'=>'\i ',
				'</em>'=>'\i0 ',
				
				'<u>'=>'\ul ',
				'</u>'=>'\ulnone ',
				'<ins>'=>'\ul ',
				'</ins>'=>'\ulnone ',
				
				'<s>'=>' }{\strike\afs16\rtlch \ltrch\loch\fs16' . "\n",
				'</s>'=>'}{\afs16\rtlch \ltrch\loch\fs16' . "\n",
				'<del>'=>' }{\strike\afs16\rtlch \ltrch\loch\fs16' . "\n",
				'</del>'=>'}{\afs16\rtlch \ltrch\loch\fs16' . "\n",
				
				'<sup>'=>'}{{\*\updnprop5801}\up7\afs16\rtlch \ltrch\loch\fs16' . "\n",
				'</sup>'=>'}{\afs16\rtlch \ltrch\loch\fs16' . "\n",
				
				'<sub>'=>'}{{\*\updnprop5801}\dn7\afs16\rtlch \ltrch\loch\fs16' . "\n",
				'</sub>'=>'}{\afs16\rtlch \ltrch\loch\fs16' . "\n",
			];
		}
		
		public function RunConversionTable($args) {
			$output = $args['output'];
			
			$conversion_table = $this->FormatConversionTable();
			
			$output = str_replace(array_keys($conversion_table), array_values($conversion_table), $output);
			
			return $output;
		}
		
		public function ConvertHTMLToFormat() {
				# http://www.biblioscape.com/rtf15_spec.htm
			$rtf_output = $this->RunTemplates();
			
			$rtf_output = $this->RunConversionTable(['output'=>$rtf_output]);
			$rtf_output = $this->FixUTF8(['output'=>$rtf_output]);
			
			$rtf_output = preg_replace('/<a href=[\"\']*([^\"\']+)[\"\']*>/', ' $1 ', $rtf_output);
			
			$rtf_output = html_entity_decode($rtf_output);
			$rtf_output = strip_tags($rtf_output);	# URLs and anything else
			
			$full_rtf_document =
				'{\rtf1\ansi\ansicpg1252\deff0{\fonttbl{\f0\fswiss\fprq2\fcharset0 Tahoma;}{\f1\froman\fprq2\fcharset0 Times New Roman;}}' . "\n" .
				'{\*\generator Msftedit 5.41.15.1515;}\viewkind4\uc1\pard\lang1033\f0' . "\n" .
				$rtf_output .
				'}';
			
			return $this->rtf_output = $full_rtf_document;
		}
		
		public function FixUTF8($args) {
			$output = $args['output'];
			
			$output = $this->convertNamedHTMLEntitiesToNumeric(['input'=>$output]);
			
			preg_match_all('/(&#[0-9]+;)/', $output, $matches, PREG_OFFSET_CAPTURE);
			$full_matches = $matches[0];
			
			$found = [];
			$search = [];
			$replace = [];
			
			for($i = 0; $i < count($full_matches); $i++) {
				$match = $full_matches[$i];
				$word = $match[0];
				if(!$found[$word]) {
					$found[$word] = TRUE;
					$search[] = $word;
					$replacement = str_replace(['&#', ';'], ['\uc1\u', '*'], $word);
					$replace[] = $replacement;
				}
			}

			$new_output = str_replace($search, $replace, $output);
			
			return $new_output;
		}
		
		public function convertNamedHTMLEntitiesToNumeric($args) {
			$input = $args['input'];
			return preg_replace_callback('/(&[a-zA-Z][a-zA-Z0-9]*;)/', function($m) {
				$c = html_entity_decode($m[0], ENT_HTML5, 'UTF-8');
				
				$convmap = [0x80, 0xffff, 0, 0xffff];
				return mb_encode_numericentity($c, $convmap, 'UTF-8');
			}, $input);
		}
		
		public function SetHTMLForRTFConversion() {
			$this->script->DisplayTemplates();
			
			return $this->rtf_input = $this->script->html_for_rtf;
		}
		
		public function SetRTFFileName() {			# BT: Look at this, prob useful???  (how is this only in RTF?  it looks good.)
			$rtf_filename = $this->script->entry['id'];
			
			if($this->desired_action == 'exportuser') {
				$pdf_filename = 'user-' . $this->script->user['id'];
			} elseif($this->script_name == 'privacy') {
				$language_code = $this->language->GetLanguageCode();
				$pdf_filename = 'privacy-policy_' . $language_code;
			} elseif($this->script_name == 'terms') {
				$language_code = $this->language->GetLanguageCode();
				$pdf_filename = 'terms-and-conditions_' . $language_code;
			} else {
				if($this->script->entry['textbody']) {
					$textbody_count = count($this->script->entry['textbody']);
					
					if($textbody_count) {
						$textbody_for_use = $this->script->entry['textbody'][0];
						if($textbody_for_use && $textbody_for_use['id']) {
							$rtf_filename .= '_' . $textbody_for_use['id'];
						}
					}
				}
			}
			
			return $this->rtf_filename = $rtf_filename;
		}
	}
	
?>
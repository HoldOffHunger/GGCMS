<?php

	trait SimpleForms {	
				# GenerateEntryListTitle ()
			# GIVEN :
		#	A Test: An Other  Test -- The Last Test, Part 14, Section 138, Sub-Section Theta 55
			# RETURNS :
		#	Test, A: Other Test, An -- Last Test, Part 00014, Section 00138, Sub-Section Theta 00055, The
		public function GenerateCode($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			$title = $entry['Code'];
			
			if($this->Param('autoincrement-title')) {
				$this->SetLastAdd(['entry'=>$this->parent]);
				
				$last_entry = $this->last_added_entry;
				
				if(!$last_entry) {
					return '1';
				}
				
				return ($last_entry['Code'] + 1);
			}
			
			return $title;
		}
		
		public function GenerateTitle($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			$title = $entry['Title'];
			
			if(!$title && $this->Param('autoincrement-title')) {
				$this->SetLastAdd(['entry'=>$this->parent]);
				
				$last_entry = $this->last_added_entry;
				
				if(!$last_entry) {
					return '1';
				}
				
				return ($last_entry['Title'] + 1);
			}
			
			return $title;
		}
	
		public function GenerateEntryListTitle($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			$list_title = $entry['Title'];
			
			if(!$list_title && $this->Param('autoincrement-title')) {
				$this->SetLastAdd(['entry'=>$this->parent]);
				
				$last_entry = $this->last_added_entry;
				
				if(!$last_entry) {
					return '1';
				}
				
				return ($last_entry['ListTitle'] + 1);
			}
			
			return $this->GenerateEntryListTitle_SubTitle(['title'=>$list_title]);
		}
	
		public function GenerateEntryListTitleSortKey($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			$list_title = $entry['ListTitle'];
			
			if(!$list_title) {
				$list_title = $entry['Title'];
			}
			
			if(!$list_title && $this->Param('autoincrement-title')) {
				$this->SetLastAdd(['entry'=>$this->parent]);
				
				$last_entry = $this->last_added_entry;
				
				if(!$last_entry) {
					$list_title =  '1';
				}
				
				$list_title = ($last_entry['ListTitle'] + 1);
			}
			
			$list_title = str_replace('“', '', $list_title);
			$list_title = str_replace('”', '', $list_title);
			$list_title = str_replace('"', '', $list_title);
			
			$list_title = preg_replace('/[\x{0300}-\x{036f}]/u','',normalizer_normalize($list_title, Normalizer::FORM_D));
			
			return $this->GenerateEntryListTitle_FullSubTitle(['title'=>$list_title]);
		}
		
		public function GenerateEntryListTitle_ExpandNumbers($args) {
			$list_title = $args['title'];
			
			$expand = 10;
			
			$list_pieces = explode(' ', $list_title);
			$list_pieces_count = count($list_pieces);
			
			$acceptable_pieces = [
				',',
				'!',
				'?',
				'.',
			];
			
			$acceptable_pieces_count = count($acceptable_pieces);
			
			for($i = 0; $i < $list_pieces_count; $i++) {
				$list_piece = $list_pieces[$i];
				
				for($j = 0; $j < $acceptable_pieces_count; $j++) {
					$acceptable_piece = $acceptable_pieces[$j];
					
					$list_pieces_explosion = explode($acceptable_piece, $list_piece);
					$list_pieces_explosion_count = count($list_pieces_explosion);
					
					for($k = 0; $k < $list_pieces_explosion_count; $k++) {
						$list_pieces_explosion_subpiece = $list_pieces_explosion[$k];
						
						if(is_numeric($list_pieces_explosion_subpiece)) {
							$list_pieces_explosion_subpiece_int = (int)$list_pieces_explosion_subpiece;
							
							if($list_pieces_explosion_subpiece == $list_pieces_explosion_subpiece_int && strlen($list_pieces_explosion_subpiece_int) < $expand) {
							#	print("BT: equal?" . $list_piece . ' | ' . $list_piece_int . '|<BR><BR>');
								$new_list_pieces_explosion_subpiece = str_pad($list_pieces_explosion_subpiece, $expand, '0', STR_PAD_LEFT);
								
								$list_pieces_explosion[$k] = $new_list_pieces_explosion_subpiece;
							}
						}
					}
					
					$list_piece = implode($acceptable_piece, $list_pieces_explosion);
				}
				
				$list_pieces[$i] = $list_piece;
			}
			
			$list_title = implode(' ', $list_pieces);
			
			return $list_title;
		}
		
		public function GenerateEntryListTitle_FullSubTitle($args) {
			$list_title = $args['title'];
			
			$list_title = str_replace(',', '', $list_title);
			$list_title = preg_replace('/\s+/', ' ', $list_title);
			
			$list_title = $this->GenerateEntryListTitle_ExpandNumbers(['title'=>$list_title]);
			
			return $this->GenerateEntryListTitle_SubTitle(['title'=>$list_title]);
		}
		
		public function GenerateEntryListTitle_SubTitle($args) {
			$list_title = $args['title'];
			
			$list_title = preg_replace('/\s+/', ' ', $list_title);
			
			$special_delimiter = ' _~!~_ ';
			
			$title_operators = [
				'a',
				'an',
				'the',
			];
			$title_operators_count = count($title_operators);
			
			$explosion_operators = [
				' -- ',
				' - ',
				': ',
			];
			$explosion_operators_count = count($explosion_operators);
			
			$all_list_title_pieces = [$list_title];
			$all_list_title_piece_count = count($all_list_title_pieces);
			
			for($i = 0; $i < $explosion_operators_count; $i++) {
				$explosion_operator = $explosion_operators[$i];
				
				$new_all_list = [];
				
				for($j = 0; $j < $all_list_title_piece_count; $j++) {
					$list_title_piece = $all_list_title_pieces[$j];
					
					$new_title_pieces = explode($explosion_operator, $list_title_piece);
					$new_title_pieces_count = count($new_title_pieces);
					
					if($new_title_pieces_count <= 1) {
						$new_all_list[] = $list_title_piece;
					} else {
						for($k = 0; $k < $new_title_pieces_count; $k++) {
							$new_title_piece = $new_title_pieces[$k];
							if($k + 1 < $new_title_pieces_count) {
								$new_all_list[] = $new_title_piece . $special_delimiter . $explosion_operator;
							} else {
								$new_all_list[] = $new_title_piece;
							}
						}
					}
				}
				
				$all_list_title_pieces = $new_all_list;
				$all_list_title_piece_count = count($all_list_title_pieces);
			}
			
			$full_title_text = '';
			
			for($i = 0; $i < $all_list_title_piece_count; $i++) {
				$list_piece = $all_list_title_pieces[$i];
				
				$list_piece_subpieces = explode(' ', $list_piece);
				$first_list_subpiece = $list_piece_subpieces[0];
				$first_list_subpiece_comparable = strtolower($first_list_subpiece);
				
				$found = 0;
				
				for($j = 0; $j < $title_operators_count; $j++) {
					$title_operator = $title_operators[$j];
					
					if($title_operator == $first_list_subpiece_comparable) {
						$found = 1;
						$j = $title_operators_count;
					}
				}
				
				if($found) {
					$first_title_piece_subpiece = $list_piece_subpieces[0];
					unset($list_piece_subpieces[0]);
				}
			
				$new_title_piece = implode(' ', $list_piece_subpieces);
				
				$new_title_piece_reexplode = explode($special_delimiter, $new_title_piece);
				$new_title_piece_reexplode_count = count($new_title_piece_reexplode);
				
				$phrase_separator = FALSE;
				if($new_title_piece_reexplode_count > 1) {
					$phrase_separator = $new_title_piece_reexplode[$new_title_piece_reexplode_count - 1];
					
					unset($new_title_piece_reexplode[$new_title_piece_reexplode_count - 1]);
				}
				$new_title_piece = implode($special_delimiter, $new_title_piece_reexplode);
				
				if($found) {
					$new_title_piece .= ', ' . $first_title_piece_subpiece;
				}
				
				if($phrase_separator) {
					$new_title_piece .= $phrase_separator;
				}
				
				$full_title_text .= $new_title_piece;
			}
			
			return ucfirst($full_title_text);
		}
		
		public function GenerateEntryCode() {
			$values = [
				$this->entry['Title'],
				$this->entry['Subtitle'],
			];
			
			foreach($this->tag as $tag) {
				$values[] = $tag['Tag'];
			}
			
			$values[] = $this->quote[0]['Quote'];
			$values[] = $this->description[0]['Description'];
			
			return $this->GenerateURLCodeFromValues(['values'=>$values]);
		}
		
		public function GenerateURLCodeFromValues_MaxLength() {
			if($this->entry_unset['id'] !== $this->master_record['id'] && $this->entry_unset['association'] && $this->entry_unset['association'][0] && $this->entry_unset['association'][0]['entry'] && $this->entry_unset['association'][0]['entry']['ListTitle'] && $this->entry_unset['association'][0]['entry']['id'] !== $this->master_record['id']) {
				$test_piece = implode('-', $this->values);
				$second_test_piece = $this->entry_unset['association'][0]['entry']['Title'];
				#print($second_test_piece);
				#print(!strpos($test_piece, $second_test_piece));
				if(strpos($test_piece, $this->entry_unset['association'][0]['entry']['Code'], 0) === FALSE && strpos($test_piece, $second_test_piece, 0) === FALSE) {
					return 40;
				}
			}
		
			return 50;
		}
		
		public function GenerateURLCodeFromValues($args) {
			$values = $args['values'];
			$this->values = $values;
			
			$pieces = [];
			
			$current_length = -1;
			
			$max_length = $this->GenerateURLCodeFromValues_MaxLength();
			$total_length = strlen(implode('-', $values));
			
			if($total_length > ceil($max_length * 1.1)) {
				$mode = 'strip-leading-and-trailing-articles';
			} else {
				$mode = 'standard';
			}
			
			for($i = 0; $i < count($values); $i++) {
				$value = $values[$i];
				$value = str_replace(['—', '–'], '-', $value);
				
				$generate_value_code = $this->GenerateURLCodeFromValue([
					'value'=>$value,
					'current_length'=>$current_length,
					'pieces'=>$pieces,
					'mode'=>$mode,
				]);
				
				$current_length = $generate_value_code['current_length'];
				$pieces = $generate_value_code['pieces'];
				
				if($current_length === $max_length) {
					$i = count($values);
				}
			}
			
			#print_r($this->entry_unset['association']);
		#	print ("BT: " . $this->entry_unset['association'][0]['entry']['id'] . "|<BR><BR>");
		#	print("BT: " . $this->master_record['id']  . "|<BR><BR>");
			if($this->entry_unset['association'] && $this->entry_unset['association'][0] && $this->entry_unset['association'][0]['entry'] && $this->entry_unset['association'][0]['entry']['ListTitle'] && $this->entry_unset['association'][0]['entry']['id'] !== $this->master_record['id']) {
				$test_piece = implode('-', $pieces);
				$second_test_piece = mb_strtolower(str_replace([' ', ',',], ['-', '',], $this->entry_unset['association'][0]['entry']['Title']));
				#print($second_test_piece);
				#print(!strpos($test_piece, $second_test_piece));
				if(strpos($test_piece, $this->entry_unset['association'][0]['entry']['Code'], 0) === FALSE && strpos($test_piece, $second_test_piece, 0) === FALSE) {
					$author_title = $this->entry_unset['association'][0]['entry']['ListTitle'];
	
					$generate_value_code = $this->GenerateURLCodeFromValue([
						'value'=>$author_title,
						'current_length'=>$current_length,
						'pieces'=>$pieces,
						'max_length'=>70,
					]);
					
					$current_length = $generate_value_code['current_length'];
					$pieces = $generate_value_code['pieces'];
				}
			}
			
			$dates = $this->eventdate;
			$date_count = count($this->eventdate);
			
			$birth_day = FALSE;
			$death_day = FALSE;
			$publication_day = FALSE;
			
			for($i = 0; $i < $date_count; $i++) {
				$event_date = $this->eventdate[$i];
				
				if($event_date['Title'] === 'Birth Day') {
					$birth_day = $event_date['EventDate'];
				}
				
				if($event_date['Title'] === 'Death Day') {
					$death_day = $event_date['EventDate'];
				}
				
				if($event_date['Title'] === 'Publication') {
					$publication_day = $event_date['EventDate'];
				}
				
				if($event_date['Title'] === 'Written' && !$publication_day) {
					$publication_day = $event_date['EventDate'];
				}
			}
			
			$publication_year = explode('-', $publication_day)[0];
			if($publication_day && end($pieces) !== $publication_day) {
				$pieces[] = $publication_year;
			}
			
			$birth_year = explode('-', $birth_day)[0];
			if($birth_day && end($pieces) !== $birth_year) {
				$pieces[] = $birth_year;
			}
			
			$death_year = explode('-', $death_day)[0];
			if($death_day && end($pieces) !== $death_year) {
				$pieces[] = $death_year;
			}
			
		#	print_r($dates);
			
		#	print("BT:!");
		#	print($this->entry_unset['association']['entry']);
		#	print($author_code);
		#	print_r($this->entry_unset);
#			print("BT:!");
#			print("<PRE>");
#			print_r(get_object_vars($this));
		#	print("<PRE>");
		#	print_r(array_keys($this));
			
			$good_code = implode('-', $pieces);
			$good_code = $this->GenerateURLCodeFromValues_UserSubmission(['code'=>$good_code]);
			return $good_code;
		}
		
		public function GenerateURLCodeFromValue($args) {
			$new_value = $args['value'];
			$pieces = $args['pieces'];
			$current_length = $args['current_length'];
			$mode = $args['mode'];		// 'strip-leading-and-trailing-articles'
			
			if(strlen($new_value) === 0) {
				return [
					'pieces'=>$pieces,
					'current_length'=>$current_length,
				];
			}
				# MORE: https://www.fileformat.info/info/unicode/block/latin_supplement/index.htm
			if($args['max_length']) {
				$max_length = $args['max_length'];
			} else {
				$max_length = $this->GenerateURLCodeFromValues_MaxLength();
			}
			$new_value = $this->GenerateURLCodeFromValue_FormatValue(['value'=>$new_value]);
			
			$new_value_pieces = preg_split('/[\s]+/', $new_value);
			
			$additions_made = FALSE;
			
			for($i = 0; $i < count($new_value_pieces); $i++) {
				$new_value_piece = $new_value_pieces[$i];
				
				if($current_length === $max_length) {
					if($mode === 'standard') {
						$i = count($new_value_pieces);
						break;
					} elseif($mode === 'strip-leading-and-trailing-articles') {
						for($j = 0; $j < 5; $j++) {
							if(count($pieces) === 0) {
								$j = 5;
							} else {
								$last_piece = end($pieces);
								if($this->isWordCommon(['word'=>$last_piece])) {
									$current_length -= (strlen($last_piece) + 1);
									array_pop($pieces);
								}
							}
						}
					}
				}
				
				if($current_length < $max_length) {
					if($new_value_piece !== '-') {
						$formatted_piece = preg_replace('/[\x{0300}-\x{036f}]/u','',normalizer_normalize($new_value_piece, Normalizer::FORM_D));
						if($mode !== 'strip-leading-and-trailing-articles' || ($additions_made || !$this->isWordCommon(['word'=>$formatted_piece]))) {
							if((strlen($formatted_piece) + $current_length + 1) <= $max_length) {
								$pieces[] = $formatted_piece;
								$current_length += strlen($formatted_piece) + 1;
								$additions_made = TRUE;
							}
						}
					}
				}
			}
			
			if($mode === 'strip-leading-and-trailing-articles') {
				$valid_pieces = [];
				
				$pieces_count = count($pieces);
				$started = FALSE;
				
				for($i = $pieces_count - 1; $i >= 0; $i--) {
					$piece = $pieces[$i];
					if($started) {
						$valid_pieces[] = $piece;
					} else {
						if(!$this->isWordCommon(['word'=>$piece])) {
							$started = TRUE;
							$valid_pieces[] = $piece;
						}
					}
				}
			
				$pieces = array_reverse($valid_pieces);
			}
			
			return [
				'pieces'=>$pieces,
				'current_length'=>$current_length,
			];
		}
		
		public function GenerateURLCodeFromValue_FormatValue($args) {
			$new_value = $args['value'];
			
			$new_value = mb_strtolower($new_value, 'UTF-8');
			
			$string_replacements = $this->GenerateURLCodeFromValue_FormatValue_stringReplacements();
			$new_value = str_replace(array_keys($string_replacements), array_values($string_replacements), $new_value);
			
			$regex_replacements = $this->GenerateURLCodeFromValue_FormatValue_regexReplacements();
			$new_value = preg_replace(array_keys($regex_replacements), array_values($regex_replacements), $new_value);
			
			return $new_value;
		}
		
		public function GenerateURLCodeFromValue_FormatValue_stringReplacements() {
			return [
				'@'=>'at',
				'&'=>'and',
				'Æ'=>'ae',
				'æ'=>'ae',
			];
		}
		
		public function GenerateURLCodeFromValue_FormatValue_regexReplacements() {
			return [
				'/[^-\p{L}\p{N}\s]/u'=>'',
				'/^[-]+/'=>'',
				'/[-]+$/'=>'',
				'/[-]+/'=>'-',
			];
		}
		
		public function GenerateURLCodeFromValues_UserSubmission($args) {
			$code = $args['code'];
			
			if(!$this->isUserAdmin()) {
				if($this->handler->desired_action === 'Edit' && $this->entry['Publish'] !== 1) {
					# no code changes
				} else {
					$user_id = $this->handler->authentication->user_account['id'];
					$code .= '-';
					if($this->authentication_object->user_session['User.id']) {
						$code .= $this->authentication_object->user_session['User.id'];
					} else {
						$code .= '0';
					}
					
					$code .= '-' . time();
				}
			}
			
			return $code;
		}
		
		public function FormatSavedRecordFromQuery_Base_SingleLine($args) {
			$text_for_newline_formatting_args = [
				'texttoformat'=>$args['texttoformat'],
			];
			
			$text_for_tab_formatting_args = [
				'texttoformat'=>$this->FormatNewLines($text_for_newline_formatting_args),
			];
			
			return $this->FormatTabs($text_for_tab_formatting_args);
		}
		
		public function CleanseForDisplay($input) {
			$cleanse_input_args = [
				'input'=>$input,
				'convertentities'=>1,
			];
			
			$cleansed_data = $this->cleanser_object->utf8_characters->CleanseInput_UTF8($cleanse_input_args)['cleansedinput'];
			
			return $cleansed_data;
		}
		
		public function EscapeHTML($text) {
			$text = str_replace('>', '&gt;', $text);
			$text = str_replace('<', '&lt;', $text);
			$text = str_replace('"', '&quot;', $text);
			
			return $text;
		}
		
		public function FormatOptionsForForm($args) {
			$options = $args['options'];
			$label_key = $args['labelkey'];
			$record_Type = $args['recordtype'];
			
			$optionslist = [];
			
			foreach ($options as $option) {
				$label = $option[$label_key];
				$optionslist[] = [
					'optionvalue'=>$label,
					'optiontitle'=>$label,
					'optionmouseover'=>'Assign Object as a ' . $label . ' ' . $record_type . '.',
				];
			}
			
			return $optionslist;
		}
		
		public function IFrameDisplayWithoutNavigation() {
			$this->navigation = 0;
			return TRUE;
		}
		
		public function DisplayOnePieceOfData() {
			$function = $this->php_command->CallableFunctionName;
			$data = $function();
			return $this->SetOnePieceOfDataForDisplay([pieceofdata=>$data]);
		}
		
		public function DisplayNumberedArrayOfData() {
			$function = $this->php_command->CallableFunctionName;
			$data = $function();
			
			$number_list_args = [
				'arrayofstrings'=>$data,
			];
			
			return $this->StatusDataArray = $this->NumberArrayOfStrings($number_list_args);
		}
		
		public function DisplaySingleResultFunctionForOnePieceOfInput() {
			$set_input_and_function_results = [
				'displaytext'=>$this->GetGoodFunctionName(),
				'parameter'=>$this->php_command->Parameters,
				'function'=>$this->php_command->CallableFunctionName,
			];
			
			return $this->SetInputAndFunctionResults($set_input_and_function_results);
		}
		
		public function DisplayListFunctionForOnePieceOfInput() {
			$set_input_and_function_results = [
				'displaytext'=>$this->GetGoodFunctionName(),
				'parameter'=>$this->php_command->Parameters,
				'function'=>$this->php_command->CallableFunctionName,
			];
			
			return $this->SetInputAndFunctionListResults($set_input_and_function_results);
		}
		
		public function DisplayKeyedArrayOfData() {
			$function = $this->php_command->CallableFunctionName;
			$data = $function();
			
			$keyed_list_args = [
				'list'=>$data,
			];
			
			return $this->StatusDataArray = $this->DisplayListFromKeys($keyed_list_args);
		}
		
		public function SetOnePieceOfDataForDisplay($args) {
			$piece_of_data = $args['pieceofdata'];
			
			if(strlen($piece_of_data) < 1) {
				$piece_of_data = '0';
			}
			
			return $this->StatusDataArray = [
				[
					$this->GetNonLineBreakGoodFunctionName(),
					$piece_of_data,
				],
			];
		}
		
		public function DisplayListFromKeys($args) {
			$list = $args['list'];
			$list_returnable = [];
			
			foreach ($list as $listkey => $listvalue) {
				$list_returnable[] = [
					$listkey,
					$listvalue,
				];
			}
			
			return $list_returnable;
		}
		
		public function NumberArrayOfStrings($args) {
			$array_of_strings = $args['arrayofstrings'];
			$array_of_strings_returnable = [];
			
			$i = 1;
			
			foreach ($array_of_strings as $file) {
				$array_of_strings_returnable[] = [
					$i,
					$file,
				];
				$i++;
			}
			
			return $array_of_strings_returnable;
		}
		
		public function SetInputAndMethodResults($args) {
			$parameter = $args['parameter'];
			$object = $args['object'];
			$function = $args['function'];
			$display_text = $args['displaytext'];
			$arguments = $args['arguments'];
			
			if(is_array($parameter)) {
				$this->SubmittedValue = [];
				
				foreach($parameter as $param) {
					$param_results = $this->query_object->Parameter(['parameter'=>$param]);
					
					if(isset($param_results)) {
						$this->SubmittedValue[] = $param_results;
					}
				}
				
				if(!count($this->SubmittedValue)) {
					unset($this->SubmittedValue);
				}
			} else {
				$this->SubmittedValue = $this->query_object->Parameter(['parameter'=>$parameter]);
			}
			
			if(isset($this->SubmittedValue)) {
				if(is_array($this->SubmittedValue)) {
					$this->SubmittedValuePrintable = [];
					
					$parameter_i = 0;
					foreach($this->SubmittedValue as $value) {
						$this->SubmittedValuePrintable[$display_text[$parameter_i]] = $value;
						$parameter_i++;
					}
					
					$this->StatusDataArray = [];
					$param_display_text = implode(' / ', $display_text);
					
					$object_function_args = [];
					
					$i = 0;
					foreach($arguments as $argument_key => $argument_value) {
						$object_function_args[$argument_key] = $this->SubmittedValue[$i];
						$i++;
					}
					
					$get_function_results = $object->$function($object_function_args);
					
					if(!$get_function_results) {
						$get_function_results = 0;
					}
					
					$this->StatusDataArray[] = [
						'<nobr>' . $param_display_text . ':</nobr>',
						$get_function_results,
					];
				} else {
					$this->SubmittedValuePrintable = $this->cleanser_object->EscapeHTML(['input'=>$this->SubmittedValue])['cleansedinput'];
				
					$get_function_results = $object->$function([$arguments=>$this->SubmittedValue]);
					
					if(!$get_function_results) {
						$get_function_results = 0;
					}
					
					$this->StatusDataArray = [
						[
							'<nobr>' . $display_text . ':</nobr>',
							$get_function_results,
						],
					];
				}
			}
			
			return $this->StatusDataArray;
		}
		
		public function SetInputAndFunctionResults($args) {
			$parameter = $args['parameter'];
			$function = $args['function'];
			$display_text = $args['displaytext'];
			$arguments = $args['arguments'];
			
			if(is_array($parameter)) {
				$this->SubmittedValue = [];
				
				foreach($parameter as $param) {
					$param_results = $this->query_object->Parameter(['parameter'=>$param]);
					
					if(isset($param_results)) {
						$this->SubmittedValue[] = $param_results;
					}
				}
				
				if(!count($this->SubmittedValue)) {
					unset($this->SubmittedValue);
				}
			} else {
				$this->SubmittedValue = $this->query_object->Parameter(['parameter'=>$parameter]);
			}
			
			if(isset($this->SubmittedValue)) {
				if(is_array($this->SubmittedValue)) {
					$this->SubmittedValuePrintable = [];
					
					$parameter_i = 0;
					foreach($this->SubmittedValue as $value) {
						$this->SubmittedValuePrintable[$display_text[$parameter_i]] = $value;
						$parameter_i++;
					}
					
					$this->StatusDataArray = [];
					$param_display_text = implode(' / ', $display_text);
					switch(count($parameter)) {
						case 1:
							$get_function_results = $function($this->SubmittedValue[0]);
							break;
							
						case 2:
							$get_function_results = $function($this->SubmittedValue[0], $this->SubmittedValue[1]);
							break;
							
						case 3:
							$get_function_results = $function($this->SubmittedValue[0], $this->SubmittedValue[1], $this->SubmittedValue[2]);
							break;
							
						case 4:
							$get_function_results = $function($this->SubmittedValue[0], $this->SubmittedValue[1], $this->SubmittedValue[2], $this->SubmittedValue[3]);
							break;
					}
					
					if(!$get_function_results) {
						$get_function_results = 0;
					}
					
					$this->StatusDataArray[] = [
						'<nobr>' . $param_display_text . ':</nobr>',
						$get_function_results,
					];
				} else {
					$this->SubmittedValuePrintable = $this->cleanser_object->EscapeHTML(['input'=>$this->SubmittedValue])['cleansedinput'];
					
					if($arguments) {
						$get_function_results = [];
						
						foreach ($arguments as $argument) {
							$get_function_results[] = [
								'<nobr>' . $display_text . ' (' . $argument . '):</nobr>',
								$function($this->SubmittedValue, $argument),
							];
						}
						
						$this->StatusDataArray = $get_function_results;
					} else {
						$get_function_results = $function($this->SubmittedValue);
						
						if(!$get_function_results) {
							$get_function_results = 0;
						}
						
						$this->StatusDataArray = [
							[
								'<nobr>' . $display_text . ':</nobr>',
								$get_function_results,
							],
						];
					}
				}
			}
			
			return TRUE;
		}
		
		public function SetInputAndFunctionArrayResults($args) {
			$parameter = $args['parameter'];
			$function = $args['function'];
			$arrays = $args['arrays'];
			
			$this->SubmittedValue = $this->query_object->Parameter(['parameter'=>$parameter]);
			
			if(isset($this->SubmittedValue)) {
				$this->SubmittedValuePrintable = $this->cleanser_object->EscapeHTML(['input'=>$this->SubmittedValue])['cleansedinput'];
				
				$array_of_results = [];
				$get_function_results = [];
				
				switch($arrays) {
					case 5:
						$function($this->SubmittedValue, $array_of_results[], $array_of_results[], $array_of_results[], $array_of_results[], $array_of_results[]);
						
						for($i = 0; $i < count($array_of_results[0]); $i++) {
							$get_function_results[] = [
								$array_of_results[0][$i],
								$array_of_results[1][$i],
								$array_of_results[2][$i],
								$array_of_results[3][$i],
								$array_of_results[4][$i],
							];
						}
						
						break;
					case 4:
						$function($this->SubmittedValue, $array_of_results[], $array_of_results[], $array_of_results[], $array_of_results[]);
						
						for($i = 0; $i < count($array_of_results[0]); $i++) {
							$get_function_results[] = [
								$array_of_results[0][$i],
								$array_of_results[1][$i],
								$array_of_results[2][$i],
								$array_of_results[3][$i],
							];
						}
						
						break;
						
					case 3:
						$function($this->SubmittedValue, $array_of_results[], $array_of_results[], $array_of_results[]);
						
						for($i = 0; $i < count($array_of_results[0]); $i++) {
							$get_function_results[] = [
								$array_of_results[0][$i],
								$array_of_results[1][$i],
								$array_of_results[2][$i],
							];
						}
						
						break;
						
					case 2:
						$function($this->SubmittedValue, $array_of_results[], $array_of_results[]);
						
						for($i = 0; $i < count($array_of_results[0]); $i++) {
							$get_function_results[] = [
								$array_of_results[0][$i],
								$array_of_results[1][$i],
							];
						}
						
						break;
						
					case 1:
					default:
						$function($this->SubmittedValue, $array_of_results[]);
						
						for($i = 0; $i < count($array_of_results[0]); $i++) {
							$get_function_results[] = [
								$array_of_results[0][$i],
							];
						}
				}
				
				$this->StatusDataArray = $get_function_results;
			}
			
			return $this->StatusDataArray;
		}
		
		public function SetInputAndFunctionListResults($args) {
			$parameter = $args['parameter'];
			$function = $args['function'];
			$desired_output = $args['desiredoutput'];
			
			$this->SubmittedValue = $this->query_object->Parameter(['parameter'=>$parameter]);
			if(isset($this->SubmittedValue)) {
				$this->SubmittedValuePrintable = $this->cleanser_object->EscapeHTML(['input'=>$this->SubmittedValue])['cleansedinput'];
				
				$get_function_results = $function($this->SubmittedValue);
				$get_function_results_returned = [];
				
				if(!$get_function_results) {
					$get_function_results_returned[] = ['0'];
				} else {
					foreach($get_function_results as $item_key => $item) {
						$item_key_useful = $item_key;
						
						if(!$item_key) {
							$item_key_useful = 0;
						}
						
						$item_useful = $item;
						
						if(!$item) {
							$item_useful = 0;
						}
						
						if($desired_output === 'key') {
							$get_function_results_returned[] = [
								$item_key_useful,
							];
						} elseif($desired_output === 'list') {
							$get_function_results_returned[] = [
								$item_key_useful,
								$item_useful,
							];
						} else {
							$get_function_results_returned[] = [
								$item_useful,
							];
						}
					}
				}
				
				$this->StatusDataArray = $get_function_results_returned;
			}
			
			return $this->StatusDataArray;
		}
	}

?>
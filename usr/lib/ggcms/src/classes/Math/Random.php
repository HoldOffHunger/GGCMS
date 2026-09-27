<?php

	class Random {
			// GetRandomString()
			// Tests: RandomTest::testGetRandomString()
			// Test file: tests/src/classes/Math/RandomTest.php
		public function GetRandomString($args) {
			$string_length = $args['stringlength'];
			
			if(!$string_length) {
				$string_length = 1;
			}
			
			$get_array_options_args = [
				'usenumbers'=>$args['usenumbers'],
				'uselowercaseletters'=>$args['uselowercaseletters'],
				'useuppercaseletters'=>$args['useuppercaseletters'],
			];
			
			$options = $this->GetRandomString_OptionsArray($get_array_options_args);
				
				/*
					No set chosen left random_int() below an empty range, and it
					threw a ValueError.  No set means all three, as no length
					means one character.
				*/
			
			if(!$options) {
				$all_options_args = [
					'usenumbers'=>TRUE,
					'uselowercaseletters'=>TRUE,
					'useuppercaseletters'=>TRUE,
				];
				
				$options = $this->GetRandomString_OptionsArray($all_options_args);
			}
			
			$random_string = '';
				
				/*
					random_int() rather than array_rand().  This makes the login
					cookie token in Authentication::GenerateCookieToken_Random(),
					and array_rand() draws from the Mersenne Twister, whose
					future output can be worked out from enough of its past.
					random_int() is the operating system's CSPRNG.
				*/
			
			$last_option_key = count($options) - 1;
			
			for($i = 0; $i < $string_length; $i++) {
				$random_option_key = random_int(0, $last_option_key);
				$random_string .= $options[$random_option_key];
			}
			
			return $random_string;
		}
		
			// GetRandomString_OptionsArray()
			// Tests: RandomTest::testGetRandomString_OptionsArray()
			// Test file: tests/src/classes/Math/RandomTest.php
		public function GetRandomString_OptionsArray($args) {
			$use_numbers = $args['usenumbers'];
			$use_lower_case_letters = $args['uselowercaseletters'];
			$use_upper_case_letters = $args['useuppercaseletters'];
			
			$array_of_options = [];
			
			if($use_numbers) {
				$array_of_options = array_merge($array_of_options, $this->GetNumbersArray());
			}
			
			if($use_lower_case_letters) {
				$array_of_options = array_merge($array_of_options, $this->GetLowerCaseLettersArray());
			}
			
			if($use_upper_case_letters) {
				$array_of_options = array_merge($array_of_options, $this->GetUpperCaseLettersArray());
			}
			
			return $array_of_options;
		}
		
			// GetNumbersArray()
			// Tests: RandomTest::testGetNumbersArray()
			// Test file: tests/src/classes/Math/RandomTest.php
		public function GetNumbersArray() {
			return ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
		}
		
			// GetLowerCaseLettersArray()
			// Tests: RandomTest::testGetLowerCaseLettersArray()
			// Test file: tests/src/classes/Math/RandomTest.php
		public function GetLowerCaseLettersArray() {
			return ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
		}
		
			// GetUpperCaseLettersArray()
			// Tests: RandomTest::testGetUpperCaseLettersArray()
			// Test file: tests/src/classes/Math/RandomTest.php
		public function GetUpperCaseLettersArray() {
			return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
		}
	}
	
?>
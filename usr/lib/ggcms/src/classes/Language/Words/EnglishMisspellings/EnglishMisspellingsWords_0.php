<?php
		
		/* UTF-8 TEST
		
			中文汉字漢字
		
		*/
				
				/* EnglishMisspellingsWords_0
					
					Class for English-Misspellings for all words beginning with : (numbers).
					
				*/

	class EnglishMisspellingsWords_0 {
			/* __construct($args)
			
				Constructor.
				
				Nothing to do here.
			
			*/
			
		public function __construct($args) {
			return TRUE;
		}
		
			/* EnglishMisspellingsWords()
			
				List of English misspellings for words starting with : (numbers).
			
			*/

		public function EnglishMisspellingsWords() {
		
				/* TODO
				
					"1870s"=>"1870's"
					
					https://www.oreilly.com/library/view/mysql-cookbook/0596001452/ch04s13.html
					
				*/
		
			$generated_issues = $this->GenerateNumberProblems();
			$static_issues = $this->GetStaticNumberIssues();
			
			$all_issues = array_merge($generated_issues, $static_issues);
			
			return $all_issues;
		}
		
		public function getStaticNumberIssues() {
			return [
				'1/1000'=>'1/1000th',
				'1/100'=>'1/100th',
				'1/10'=>'1/10th',
				'1/12'=>'1/12th',
				'1/16'=>'1/16th',
				'1/2'=>'1/2nd',
				'1/2'=>'1/2th',
				'1/30'=>'1/30th',
				'1/32'=>'1/32nd',
				'1/360'=>'1/360th',
				'1/3'=>'1/3rd',
				'1/3'=>'1/3th',
				'1/48'=>'1/48th',
				'1/4'=>'1/4th',
				'1/50'=>'1/50th',
				'1/5'=>'1/5th',
				'1/6'=>'1/6th',
				'1/7'=>'1/7th',
				'1/8'=>'1/8th',
				'1/9'=>'1/9th',
				'hundreds of'=>'100\'s of',
				'thousands of'=>'1000\'s of',
				'thousands of'=>'1000s of',
				'100 cc'=>'100cc',
				'100 cm'=>'100cm',
				'100 km'=>'100km',
				'100 m'=>'100m',
				'100 mm'=>'100mm',
				'hundreds of'=>'100s of',
				'10 million'=>'10M',
				'10 V'=>'10V',
				'10 cc'=>'10cc',
				'10 km'=>'10km',
				'10 m'=>'10m',
				'110 V'=>'110V',
				'120 V'=>'120V',
				'12 V'=>'12V',
				'2/3'=>'2/3rd',
				'2/3'=>'2/3rds',
				'2/5'=>'2/5th',
				'3/4'=>'3/4th',
				'3/5'=>'3/5th',
				'3/8'=>'3/8ths',
				'5,000 m'=>'5,000m',
				'5/8'=>'5/8ths',
				'5 V'=>'5V',
				'60 W'=>'60W',
			];
		}
		public function GetOCRIgnores() {
			$ignores = [
				'I'=>true,
				'II'=>true,
				'III'=>true,
				'IO'=>true,
			];
			
			return $ignores;
		}
		
		public function GetOCRMatchups() {
			$matchups = [
				'0'=>'O',
				'1'=>'I',
		#		'5'=>'S',
		#		'8'=>'S',
		#		'9'=>'S',
			];
			
			return $matchups;
		}
		
		public function GenerateNumberProblems() {
			$misspelled_words = [];
			
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberOCRProblems());
		#	$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberProblems_MissingCommas());
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberProblems_YearOlds());
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberProblems_AMPMTimes());
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberProblems_IncorrectNumberths());
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberProblems_Decades());
			
			return $misspelled_words;
		}
		
		public function GenerateNumberProblems_Decades() {
			$misspelled_words = [];
			
			$decade_endings = [
				'\'s',
				'-s',
				'ies',
			];
			
			for($i = 100; $i < 202; $i++) {
				$misspelled_base = (string)$i . '0';
				
				$misspelled_words[$misspelled_base . 's'] = [];
				
				foreach($decade_endings as $decade_ending) {
					$misspelled_words[$misspelled_base . 's'][] = $misspelled_base . $decade_ending;
				}
				
				$short_misspelled_base = substr($misspelled_base, -2);
				
				if(array_key_exists($short_misspelled_base . 's', $misspelled_words)) {
					$misspelled_words[$short_misspelled_base . 's'] = [];
					
					foreach($decade_endings as $decade_ending) {
						$misspelled_words[$short_misspelled_base . 's'][] = $short_misspelled_base . $decade_ending;
					}
				}
			}
			
			return $misspelled_words;
		}
		
		public function GenerateNumberProblems_IncorrectNumberths() {
			$misspelled_words = [];
			
			$numberth_separators = ['', ' '];
			
			for($i = 0; $i < 1000; $i++) {
				foreach($numberth_separators as $numberth_separator) {
					$modulo = $i % 10;
					
					$authentic_extension = $this->GetNumberPlaceExtension(['number'=>$i]);
					
					if(!array_key_exists($i . $numberth_separator . $authentic_extension, $misspelled_words)) {
						$misspelled_words[$i . $numberth_separator . $authentic_extension] = [];
					}
					
					if($modulo != 1) {
						$misspelled_words[$i . $numberth_separator . $authentic_extension][] = $i . $numberth_separator . 'st';
					}
					
					if($modulo != 2) {
						$misspelled_words[$i . $numberth_separator . $authentic_extension][] = $i . $numberth_separator . 'nd';
					}
					
					if($modulo != 3) {
						$misspelled_words[$i . $numberth_separator . $authentic_extension][] = $i . $numberth_separator . 'rd';
					}
				}
			}
			
			return $misspelled_words;
		}
		
		public function GenerateNumberProblems_AMPMTimes() {
			$misspelled_words = [];
			
			for($i = 1; $i <= 12; $i++) {
				$misspelled_words[$i . ' am'] = $i . 'am';
				$misspelled_words[$i . ' a.m.'] = $i . 'a.m.';
				$misspelled_words[$i . ' pm'] = $i . 'pm';
				$misspelled_words[$i . ' p.m.'] = $i . 'p.m.';
			}
			
			return $misspelled_words;
		}
		
		public function GenerateNumberProblems_YearOlds() {
			$misspelled_words = [];
			
			$min = 1;
			$max = 200;
			
			for($i = $min; $i < $max; $i++) {
				$index = $i . '-year-old';
				$index_plural = $index . 's';
				
				$misspelled_words[$index] = [
					$i . ' year old',
					$i . ' year-old',
					$i . '-year old',
				];
				
				$misspelled_words[$index_plural] = [
					$i . ' year olds',
					$i . ' year-olds',
					$i . '-year olds',
				];
			}
			
			return $misspelled_words;
		}
		
		public function GenerateNumberProblems_MissingCommas() {
			$misspelled_words = [];
			
			$min = 1000;
			$max = 10000;
			
			for($i = $min; $i < $max; $i++) {
				$misspelled_words[number_format($i)][] = $i;
			}
			
			return $misspelled_words;
		}
		
		public function GenerateNumberOCRProblems_Numberths() {
			$misspelled_words = [];
			
			$matchups = $this->GetOCRMatchups();
			
			$numberth_separators = ['', ' '];
			
			for($i = 0; $i < 1000; $i++) {
				foreach($numberth_separators as $numberth_separator) {
					$extension = $this->GetNumberPlaceExtension(['number'=>$i]);
					
					$word = $i . $numberth_separator . $extension;
					
					$interpolated_options = $this->InterpolateStrings(['matchups'=>$matchups, 'number'=>$word]);
					
					if(count($interpolated_options)) {
						$misspelled_words[$word] = $interpolated_options;
					}
				}
			}
			
			return $misspelled_words;
		}
		
		public function GetNumberPlaceExtension($args) {
			$number = $args['number'];
			
			$modulo = $number % 10;
			
			if($modulo == 1) {
				return 'st';
			} elseif($modulo == 2) {
				return 'nd';
			} elseif($modulo == 3) {
				return 'rd';
			}
			
			return 'th';
		}
		
		public function GenerateNumberOCRProblems_Numbers() {
			$misspelled_words = [];
			$matchups = $this->GetOCRMatchups();
			
			$min = 1;
			$max = 10000;
			
			for($i = $min; $i < $max; $i++) {
				$interpolated_options = $this->InterpolateStrings(['matchups'=>$matchups, 'number'=>$i]);
				
				if(count($interpolated_options)) {
					$misspelled_words[$i] = $interpolated_options;
				}
				
				$formatted = number_format($i);
				
				if($formatted != $i) {
					$interpolated_options = $this->InterpolateStrings(['matchups'=>$matchups, 'number'=>$formatted]);
					
					if(count($interpolated_options)) {
						$misspelled_words[$formatted] = $interpolated_options;
					}
				}
			}
			
			return $misspelled_words;
		}
		
		public function GenerateNumberOCRProblems() {
			$misspelled_words = [];
			
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberOCRProblems_Numberths());
			$misspelled_words = array_merge($misspelled_words, $this->GenerateNumberOCRProblems_Numbers());
			
			return $misspelled_words;
		}
		
		public function InterpolateStrings_getInteroperableChars($args) {
			$number = $args['number'];
			$matchups = $args['matchups'];
			
			$possible_chars = str_split($number);
			$possible_chars_count = count($possible_chars);
			$interoperable_chars = [];
			$interoperable_indexes = [];
			
			for($i = 0; $i < $possible_chars_count; $i++) {
				$possible_char = $possible_chars[$i];
				
				if(array_key_exists($possible_char, $matchups) && $matchups[$possible_char]) {
					$interoperable_chars[] = $possible_char;
					$interoperable_indexes[] = $i;
				}
			}
			
			return $interoperable_indexes;
		}
		
			/* InterpolateString($args)
			 *
			 * Given a binary index, return a string interpolated according to given matchups and that binary index on a given number string.
			 *
			*/
		
		public function InterpolateString($args) {
			$number = $args['number'];
			$matchups = $args['matchups'];
			$index = $args['index'];
			$interoperable_indexes = $args['indexes'];
			$interoperable_indexes_count = count($interoperable_indexes);
			$pad_length = $interoperable_indexes_count;
			
			$binary = decbin($index);
			$full_binary = str_pad($binary, $pad_length, '0', STR_PAD_LEFT);
			
			$binary_chars = str_split($full_binary);
			$binary_index = 0;
			
			$interpreted_string = (string)$number;
			
			for($i = 0; $i < $interoperable_indexes_count; $i++) {
				$possible_index = $interoperable_indexes[$i];
				$possible_char = $interpreted_string[$possible_index];
				
				if($binary_chars[$binary_index]) {
					$matchup = $matchups[$possible_char];
					$interpreted_string[$possible_index] = $matchup;
				}
				$binary_index++;
			}
			
			return $interpreted_string;
		}
		
			/* InterpolateStrings($args)
			 *
			 * Given a number-type string ("100th") and matchups (i.e., [0=>O, 1=>I], GetOCRMatchups()), return all possible interpolations of the two.  I.E. In this case, ["10Oth", "1O0th", "IOOth", "IO0th", ...etc.].
			 *
			*/
		
		public function InterpolateStrings($args) {
			$number = $args['number'];
			$matchups = $args['matchups'];
			$ignores = $this->getOCRIgnores();
			
			$interoperable_indexes = $this->InterpolateStrings_getInteroperableChars($args);
			$interoperable_indexes_count = count($interoperable_indexes);
			
			if($interoperable_indexes_count == 0) {
				return [];
			}
			
			$max_interpolations = pow(2, $interoperable_indexes_count);
			$pad_length = $interoperable_indexes_count;
			$matchup_keys = array_keys($matchups);
			
			$interpolated_strings = [];
			
			for($i = 1; $i < $max_interpolations; $i++) {
				$interpolated_string = $this->InterpolateString(['number'=>$number, 'matchups'=>$matchups, 'index'=>$i, 'indexes'=>$interoperable_indexes]);
				
				if(!array_key_exists($interpolated_string, $ignores)) {
					$interpolated_strings[] = $interpolated_string;
				}
			}
			
			return $interpolated_strings;
		}
	}
	
	#$number_class = new EnglishMisspellingsWords_0([]);
 
	#print("Count ::: " . count($number_class->EnglishMisspellingsWords()) . "\n\n");
	
	#print_r($number_class->EnglishMisspellingsWords());
	#print_r($number_class->getStaticNumberIssues());
	
?>
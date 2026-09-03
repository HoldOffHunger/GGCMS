<?php

	trait CLIAccess {
			// Override Functions
		
		public function bannerMessageText() {
			die('Abstract method.  Please override to proceed.');
		}
		
		public function confirmDomainText() {
			die('Abstract method.  Please override to proceed.');
		}
		
			// Constructor
			
		public function __construct($args) {
			$this->argv = $args['argv'];
			
			return $this;
		}
		
			// Standard Functions
			
			/*
				Render whatever columns the query returned.

				This used to assume every result was two columns named Count
				and URL: field 0 became the count, field 1 became the URL, and
				any row where either was empty was dropped.

				Two tools paid for that.  list500Errors selects four columns --
				IncidentCount, Script, LastModificationDate, URL -- and Script
				is empty on most tickets, so field 1 was blank and every row was
				discarded.  revoltlib had seventy-six open tickets and the tool
				printed an empty table, which reads exactly like good news.  And
				the 500-counts table put domain names under a heading that said
				Count, and counts under one that said URL, because the labels
				were invented here rather than read from the result.

				mysql -e emits tab-separated values with a header line.  That
				header is the column names, so it is now used instead of thrown
				away, and a row is skipped only when the whole line is blank.
			*/

		public function formatTable($args) {
			$output = $args['output'];

			$output_lines = explode("\n", $output);

			if(count($output_lines) < 2) {
				return arr2textTable([]);
			}

			$headers = explode("\t", trim(array_shift($output_lines)));
			$header_count = count($headers);

			$table_input = [];

			foreach($output_lines as $output_line) {
				if(strlen(trim($output_line)) === 0) {
					continue;
				}

				$output_line_pieces = explode("\t", rtrim($output_line, "\r"));

				$output_array = [];

				for($i = 0; $i < $header_count; $i++) {
					$name = strlen($headers[$i]) ? $headers[$i] : ('column ' . ($i + 1));
					$output_array[$name] = array_key_exists($i, $output_line_pieces) ? $output_line_pieces[$i] : ' ';
				}

				$table_input[] = $output_array;
			}

			return arr2textTable($table_input);
		}

		public function setHandle() {
			$this->handle = fopen('php://stdin', 'r');
			
			return TRUE;
		}
		
		public function bannerMessage() {
			print("\n");
			print("GGCMS - " . $this->bannerMessageText() . "\n");
			print("###################################################\n");
			print("###################################################\n");
			print("###################################################\n\n");
			
			return TRUE;
		}
		
		public function basicConfirmDialogue($args) {
			$message = $args['message'];
			
			if(array_key_exists('argv_index', $args)) {
				$argv_index = $args['argv_index'];
			} else {
				$argv_index = 2;
			}
			
			print($message);
			print(PHP_EOL . PHP_EOL);
			print('Proceed? (y/n)');
			
			if(property_exists($this, 'answer_type')) {
				if($this->answer_type === 'y' || $this->answer_type === 'yes') {
					return TRUE;
				}
			}
			
			$all_yes_argv_index = 0;
			
			if(array_key_exists('all_yes_argv_index', $args)) {
				$all_yes_argv_index = $args['all_yes_argv_index'];
			}
			
			if(array_key_exists($argv_index, $this->argv) && ($this->argv[$argv_index] === 'y' || $this->argv[$argv_index] === 'yes')) {
				$proceed = $this->argv[$argv_index];
				print($proceed . PHP_EOL);
			} elseif($all_yes_argv_index && ($this->argv[$all_yes_argv_index] === 'y' || $this->argv[$all_yes_argv_index] === 'yes')) {
				$proceed = $this->argv[$all_yes_argv_index];
				print($proceed . PHP_EOL);
			} else {
				$proceed = strtolower(trim(fgets($this->handle)));
			}
			
			print(PHP_EOL);
			
			if($proceed === 'y' || $proceed === 'yes') {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function cancelAction($args) {
			print($args['message'] .  "  Exiting..." . PHP_EOL . PHP_EOL);
			return exit(1);
		}
		
		public function setDomain() {
			print("Enter Fully-Qualified Domain Name (without subnet): ");
			
			if(array_key_exists(1, $this->argv) && $this->argv[1]) {
				$this->domain = $this->argv[1];
				print($this->domain . PHP_EOL);
			} else {
				$this->domain = strtolower(trim(fgets($this->handle)));
			}
			
			$domain_parts = explode('.', $this->domain);
			
			if(!$this->validateDomain(['domain_parts'=>$domain_parts])) {
				return FALSE;
			}
			
			$this->host = $domain_parts[0];
			
			print("\n");
			
			print($this->confirmDomainText());
			print($this->domain);
			
			print("\n\n");
			
			return TRUE;
		}
		
		/*
			
			Example:
			

			return $this->abstractConfirmDialogue([
				'message'=>'Archive is for removing data.  Backup is for data you may need to immediately restore.',
				'prompt'=>'Archive or Backup? (a)/(b)?',
				'index'=>3,
				'internal_key'=>'archive_or_backup',
				'valid_answers'=>[
					'a',
					'b',
				],
			]);
		*/
		
		public function abstractConfirmDialogue($args) {
			$message = $args['message'];
			$prompt = $args['prompt'];
			$index = $args['index'];
			
			print($message);
			print("\n\n");
			print($prompt);
			
			/*
			if(property_exists($this, 'answer_type')) {
				if($this->answer_type === 'y' || $this->answer_type === 'yes') {
					return TRUE;
				}
			}
			*/
			
			if(array_key_exists($index, $this->argv) && $this->argv[$index]) {
				$answer = $this->argv[$index];
				print($answer . "\n");
			} else {
				$answer = strtolower(trim(fgets($this->handle)));
			}
			
			if(array_key_exists('internal_key', $args)) {
				$argv_internal_key = $args['internal_key'];
			} else {
				$argv_internal_key = 'argv' . $index;
			}
			
			$this->$argv_internal_key = $answer;
			
			print("\n");
			
			$answer_length = strlen($answer);
			
			if($answer_length === 0) {
				return FALSE;
			}
			
			$valid_answers = $args['valid_answers'];
			$valid_answer_hash = [];
			
			foreach($valid_answers as $valid_answer) {
				$valid_answer_hash[$valid_answer] = TRUE;
			}
			
			if(!array_key_exists($answer, $valid_answer_hash)) {
				print('invalid response: ' . $answer);
				
				print("\n\n");
				
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function successResults() {
			print("\033[32mPASS\033[0m");
			
			return TRUE;
		}
		
		public function failResults() {
			print("\033[31mFAIL\033[0m");
			
			return TRUE;
		}
	}

?>
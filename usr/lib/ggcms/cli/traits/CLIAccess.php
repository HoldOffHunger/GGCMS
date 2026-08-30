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
			
		public function formatTable($args) {
			$output = $args['output'];
			
			$output_lines = explode("\n", $output);
			
			$table_input = [];
			
			unset($output_lines[0]);
			
			foreach($output_lines as $output_line) {
				$output_line = trim($output_line);
				$output_line_pieces = explode("\t", $output_line);
				$count = $output_line_pieces[0];
				$url = $output_line_pieces[1];
				
				if(strlen($count) !== 0 && strlen($url) !== 0) {
					$output_array = [
						'Count'=>$count,
						'URL'=>$url,
					];
					
					$table_input[] = $output_array;
				}
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
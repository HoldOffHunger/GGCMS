<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	
	class List404 {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		
		public function list404Errors() {
			$this->setHandle();
			$this->bannerMessage();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			$this->userInputType();
			$this->setGlobals();
			$this->setMySQLArgs();
			$this->getAndList404Errors();
			
			return $this->cancelAction(['message'=>'User cancelled.']);
		}
		
		public function getAndList404Errors(){
			$sql_command = 'SELECT COUNT(URL) as Count, URL from ' . $this->host . '.InternalServerIssue WHERE IssueType = \'404\' GROUP BY URL ORDER BY Count DESC LIMIT ' . $this->answer_type . ';';
			print("Getting 404's for " . $this->domain . '.' . PHP_EOL . PHP_EOL);
			
			$create_database_command = 'mysql -e "' . $sql_command . '"';
				
			$output = shell_exec($create_database_command);
			
			if(strlen($output) === 0) {
				print(PHP_EOL . 'No 404\'s.  Hooray!' . PHP_EOL . PHP_EOL);
			} else {
				print($this->formatTable(['output'=>$output]));
			}
		#	print_r($output);
		#	$output_pieces = explode("\t", $output);
		#	print("BT: COUNT!" . count($output_pieces) . "|");
			
			print("404's successfully retrieved for " . $this->domain . '.' . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function userInputType() {
			print(PHP_EOL . PHP_EOL);
			print('How many?  (Default is 10.)');
			
			$this->answer_type = strtolower(trim(fgets($this->handle)));
			
			if(!$this->answer_type) {
				$this->answer_type = 10;
			}
			
			return true;
		}
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall installation beginning.',
			]);
		}
		
		public function userConfirmSource() {
			$source_filename = $this->source_filename;
			
			print('Rebuild template database (`' . $source_filename . '`)?' . PHP_EOL);
			
			if(!cli_isfile($source_filename)) {
				print(PHP_EOL . 'Source file does not exist!  Install will crash if you attempt to proceed with "n"!' . PHP_EOL);
			}
			
			$creation_time = filectime(GGCMS_CLI_DIR . $source_filename);
			$modification_time = filemtime(GGCMS_CLI_DIR . $source_filename);
			$access_time = fileatime(GGCMS_CLI_DIR . $source_filename);
			
			print($source_filename .
				' - Created: ' .
				date('Y-m-d H:i:s', $creation_time) .
				'; Modified: ' .
				date('Y-m-d H:i:s', $modification_time) .
				'; Last Access: ' .
				date('Y-m-d H:i:s', $access_time)
			. PHP_EOL);
			
			return $this->basicConfirmDialogue([
				'message'=>'Rebuild?',
			]);
		}
		
		public function bannerMessageText() {
			return 'List 404\'s';
		}
		
		public function confirmDomainText() {
			return 'Getting 404s For: ';
		}
	}
	
?>
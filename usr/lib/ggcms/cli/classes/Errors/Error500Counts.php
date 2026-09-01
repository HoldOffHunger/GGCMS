<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/MySQLDatabases.php');
	clireq('traits/MySQLInternalDatabases.php');
	clireq('traits/MySQLGGCMSInternalDatabases.php');
	clireq('traits/MySQLClustersInternalDatabases.php');

	class Error500Counts {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use MySQLDatabases;
		use MySQLInternalDatabases;
		use MySQLGGCMSInternalDatabases;
		use MySQLClustersInternalDatabases;
		
		public function count500errors() {
			$this->setHandle();
			$this->bannerMessage();
			
			print('Getting 500 counts for all domains.' . PHP_EOL . PHP_EOL);
			
			$this->setGlobals();
			$this->setMySQLArgs();
			$this->getAndList500Errors();
			
			return TRUE;
		}
		
		public function getAndList500Errors(){
			#$sql_command = 'SELECT COUNT(URL) as Count, URL from ' . $this->host . '.InternalServerError GROUP BY URL ORDER BY Count DESC LIMIT ' . $this->answer_type . ';';
			
			$databases = $this->getUserMySQLDatabases();
			
			$sql_command = '';
			$sql_command_selects = [];
			
			foreach($databases as $database) {
				$sql_command_selects[] = "SELECT '" . $database . "', COUNT(id), IFNULL(SUM(IncidentCount), 0) FROM " . $database . ".InternalServerError WHERE Resolved = 0";
			}
			
			$sql_command .= implode(' UNION ', $sql_command_selects);
			$sql_command .= ';';
			
		#	print($sql_command);
			
			$select_command = 'mysql -e "' . $sql_command . '"';
				
			$output = trim(shell_exec($select_command));
			
	#		print_r($database_error_counts);
			
			if(strlen($output) === 0) {
				print(PHP_EOL . 'No 500\'s.  Hooray!' . PHP_EOL . PHP_EOL);
			} else {
				print($this->formatTable(['output'=>$output]));
			}
		#	print_r($output);
		#	$output_pieces = explode("\t", $output);
		#	print("BT: COUNT!" . count($output_pieces) . "|");
			
			print("500's successfully retrieved for all domains." . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall installation beginning.',
			]);
		}
		
		public function bannerMessageText() {
			return 'List 500\'s';
		}
		
		public function confirmDomainText() {
			return 'Getting 500\'s For: ';
		}
	}
	
?>
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

	class IssueCounts {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		use MySQLDatabases;
		use MySQLInternalDatabases;
		use MySQLGGCMSInternalDatabases;
		use MySQLClustersInternalDatabases;
		
		public function countIssues() {
			$this->setHandle();
			$this->bannerMessage();
			
			print('Getting issue counts for all domains.' . PHP_EOL . PHP_EOL);
			
			$this->setGlobals();
			$this->setMySQLArgs();
			$this->getAndListIssues();
			
			return TRUE;
		}
		
		public function getAndListIssues(){
			#$sql_command = 'SELECT COUNT(URL) as Count, URL from ' . $this->host . '.InternalServerError GROUP BY URL ORDER BY Count DESC LIMIT ' . $this->answer_type . ';';
			
			$databases = $this->getUserMySQLDatabases();
			
			$sql_command = '';
			$sql_command_selects = [];
			
			foreach($databases as $database) {
				$sql_command_selects[] = "SELECT '" . $database . "', COUNT(id), IFNULL(SUM(IncidentCount), 0) FROM " . $database . ".InternalServerIssue WHERE Resolved = 0";
			}
			
			$sql_command .= implode(' UNION ', $sql_command_selects);
			$sql_command .= ';';
			
		#	print($sql_command);
			
			$select_command = 'mysql -e "' . $sql_command . '"';
				
			$output = trim(shell_exec($select_command));
			
	#		print_r($database_error_counts);
			
			if(strlen($output) === 0) {
				print(PHP_EOL . 'No issues\'s.  Hooray!' . PHP_EOL . PHP_EOL);
			} else {
				print($this->formatTable(['output'=>$output]));
			}
		#	print_r($output);
		#	$output_pieces = explode("\t", $output);
		#	print("BT: COUNT!" . count($output_pieces) . "|");
			
			print("Issues successfully retrieved for all domains." . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall installation beginning.',
			]);
		}
		
		public function bannerMessageText() {
			return 'List Issues';
		}
	}
	
?>
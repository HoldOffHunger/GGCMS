<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/MySQLDatabases.php');
	clireq('traits/MySQLInternalDatabases.php');
	clireq('traits/MySQLGGCMSInternalDatabases.php');
	clireq('traits/MySQLClustersInternalDatabases.php');

	class ClearErrors {
		use DBAccess;
		use CLIAccess;
		use GlobalsTrait;
		use MySQLDatabases;
		use MySQLInternalDatabases;
		use MySQLGGCMSInternalDatabases;
		use MySQLClustersInternalDatabases;
		
		public function clearURLsByURL() {
			$this->setHandle();
			$this->bannerMessage();
			
			$this->setGlobals();
		#	$this->setMySQLArgs();
			$this->clearErrorsByURL();
			
			return TRUE;
		}
		
		public function clearErrorsByURL(){			#$sql_command = 'SELECT COUNT(URL) as Count, URL from ' . $this->host . '.InternalServerError GROUP BY URL ORDER BY Count DESC LIMIT ' . $this->answer_type . ';';
			
			$databases = $this->getUserMySQLDatabases();
			
			$sql_command = '';
			$sql_command_selects = [];
			
			foreach($databases as $database) {
				$sql_command = "DELETE FROM " . $database . ".InternalServerError ";
				
				$select_command = 'mysql -e "' . $sql_command . '"';
				
				shell_exec($select_command);
				
				print("Cleared! " . $database . PHP_EOL);
			}
			
			print("500's successfully cleared for all domains." . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall 500 clearing.',
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
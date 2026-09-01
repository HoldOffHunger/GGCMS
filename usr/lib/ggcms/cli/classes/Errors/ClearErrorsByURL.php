<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/MySQLDatabases.php');
	clireq('traits/MySQLInternalDatabases.php');
	clireq('traits/MySQLGGCMSInternalDatabases.php');
	clireq('traits/MySQLClustersInternalDatabases.php');

	class ClearErrorsByURL {
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
			
			if(!$this->setURL()) {
				return $this->cancelAction(['message'=>'Invalid URL.  Please submit a URL in the form of `example.php`.']);
			}
			
			$this->setGlobals();
			$this->setMySQLArgs();
			$this->clearErrorsByURL();
			
			return TRUE;
		}
		
		public function setURL() {
			print("Enter URL to Clear: ");
			
			if(array_key_exists(1, $this->argv) && $this->argv[1]) {
				$this->url = $this->argv[1];
				print($this->url . PHP_EOL);
			} else {
				$this->url = strtolower(trim(fgets($this->handle)));
			}
			
			print("\n");
			
			print('Clearing this URL: ');
			print($this->url);
			
			print("\n\n");
			
			return TRUE;
		}
		
		public function clearErrorsByURL(){			#$sql_command = 'SELECT COUNT(URL) as Count, URL from ' . $this->host . '.InternalServerError GROUP BY URL ORDER BY Count DESC LIMIT ' . $this->answer_type . ';';
			
			$databases = $this->getUserMySQLDatabases();
			
			$sql_command = '';
			$sql_command_selects = [];
			
			foreach($databases as $database) {
				$sql_command = "DELETE FROM " . $database . ".InternalServerErrorInstance WHERE URL = " . escapeshellarg($this->url) . "; ";
				$sql_command .= "DELETE FROM " . $database . ".InternalServerError WHERE URL = " . escapeshellarg($this->url) . " AND id NOT IN (SELECT Errorid FROM " . $database . ".InternalServerErrorInstance); ";
				
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
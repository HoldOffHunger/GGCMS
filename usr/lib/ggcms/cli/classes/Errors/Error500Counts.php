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
			
			$databases = $this->getDatabasesWithErrorTable();

			if(!count($databases)) {
				print(PHP_EOL . '!!! FAILED: no database has an InternalServerError table, so there is nothing to count.  This is not "no 500\'s". !!!' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$sql_command = '';
			$sql_command_selects = [];

			foreach($databases as $database) {
				$sql_command_selects[] = "SELECT '" . $database . "', COUNT(id), IFNULL(SUM(IncidentCount), 0) FROM " . $database . ".InternalServerError WHERE Resolved = 0";
			}

			$sql_command .= implode(' UNION ', $sql_command_selects);
			$sql_command .= ';';

		#	print($sql_command);

				/*
					One statement for every database, so one bad database fails
					all of them.  Its error went to stderr and the empty stdout
					read as "No 500's.  Hooray!": from the RevoltLib restore on
					29 September 2026, the empty revoltlib_2023 left beside the
					live database blanked this report every morning for three
					weeks.  An exit code that is not 0 is a failed count, never a
					clean one.
				*/

			$select_command = 'mysql -e "' . $sql_command . '" 2>&1';

			$output_lines = [];
			$exit_code = 0;

			exec($select_command, $output_lines, $exit_code);

			$output = trim(implode(PHP_EOL, $output_lines));

			if($exit_code !== 0) {
				print(PHP_EOL . '!!! FAILED: mysql exited ' . $exit_code . ' and nothing was counted.  This is not "no 500\'s". !!!' . PHP_EOL);
				print(strlen($output) ? $output : 'mysql said nothing.');
				print(PHP_EOL . PHP_EOL);

				return FALSE;
			}

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

			/*
				SHOW DATABASES also lists whatever sits beside the sites --
				revoltlib_2023 and revoltlib_jan2022, left by the restore, have
				no InternalServerError table -- so only a database that has one
				is asked.
			*/

		public function getDatabasesWithErrorTable() {
			$databases = $this->getUserMySQLDatabases();

			$sql_command = "SELECT TABLE_SCHEMA FROM information_schema.TABLES WHERE TABLE_NAME = 'InternalServerError'";

			$output_lines = [];
			$exit_code = 0;

			exec('mysql -N -e ' . escapeshellarg($sql_command) . ' 2>&1', $output_lines, $exit_code);

			if($exit_code !== 0) {
				print(PHP_EOL . '!!! FAILED: mysql exited ' . $exit_code . ' listing the InternalServerError tables. !!!' . PHP_EOL);
				print(trim(implode(PHP_EOL, $output_lines)) . PHP_EOL . PHP_EOL);

				return [];
			}

			$has_table = array_flip(array_map('trim', $output_lines));
			$databases_with_table = [];

			foreach($databases as $database) {
				if(array_key_exists($database, $has_table)) {
					$databases_with_table[] = $database;
				} else {
					print('Skipping ' . $database . ': it has no InternalServerError table.' . PHP_EOL);
				}
			}

			return $databases_with_table;
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
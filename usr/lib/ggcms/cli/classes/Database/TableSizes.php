<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/DNSRecords.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	
	class TableSizes {
		use DBAccess;
		use DomainValidation;
		use DNSRecords;
		use CLIAccess;
		use GlobalsTrait;
		
		public function listTableSizes() {
			$this->setHandle();
			$this->bannerMessage();
			
			$this->setGlobals();
			$this->setMySQLArgs();
			
			$this->enableGroupConcatMax();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			$sql_generator = "SET SESSION group_concat_max_len = 1000000;SELECT CONCAT('SELECT ', GROUP_CONCAT(table1.count SEPARATOR ',')) FROM (" .
			    "SELECT concat('(SELECT count(id) AS \'',table_name,' Count\' ','FROM ',table_name,') AS ',table_name,'_Count') AS 'count' " .
			    "FROM information_schema.tables " . 
			    "WHERE table_schema = '" . $this->host . "' " .
			") AS table1";
			
			$ssh_command = 'mysql -e "' . $sql_generator . '"';
			
			#print('Soybeans');
	#		$create_database_command = 'mysql -e "SHOW DATABASES;"';
			
		#	print($create_database_command);
				
			print(PHP_EOL . PHP_EOL);
	#		print($sql_generator);
			print(PHP_EOL . PHP_EOL);
			$output = trim(shell_exec($ssh_command));
			
			print(PHP_EOL . PHP_EOL . "Eyyy???" . PHP_EOL . PHP_EOL);
			
			$output_pieces = explode(PHP_EOL, $output);
		#	print($output);
			$good_sql = $output_pieces[1];
		#	print($good_sql);
			$tables_sql = 'mysql -e "use ' . $this->host . ';' . $good_sql . '"';
			
			$real_output = trim(shell_exec($tables_sql));
			
			$real_output_pieces = explode(PHP_EOL, $real_output);
			
			$header = trim($real_output_pieces[0]);
			$data = trim($real_output_pieces[1]);
			
			print("HEADER!" . $header . PHP_EOL . PHP_EOL);
			print("DATA!" . $data . PHP_EOL . PHP_EOL);
			
			$table_names = [];
			$data_values = [];
			
			$header_pieces = explode("\t", $header);
			$data_pieces = explode("\t", $data);
			
			$formatted_output = [];
			
			$header_pieces_count = count($header_pieces);
			
			for($i = 0; $i < $header_pieces_count; $i++) {
				$header_piece = $header_pieces[$i];
				$data_piece = $data_pieces[$i];
				
				$real_header_piece = str_replace('_Count', '', $header_piece);
				
				$formatted_output[] = [
					'Table'=>$real_header_piece,
					'Count'=>$data_piece,
				];
			}
			print("HUH!" . $header_pieces[1] . "|");
		#	print_r($formatted_output);
			
		#	print_r($real_output);
		#	print_r($output);
			print(PHP_EOL);
			print(arr2textTable($formatted_output));
			
			return TRUE;
		}
		
		public function enableGroupConcatMax() {
			# this no work =\
			$create_database_command = 'mysql -e "SET SESSION group_concat_max_len = 1000000;"';
			
			$output = shell_exec($create_database_command);
			
			return TRUE;
		}
		
		public function bannerMessageText() {
			return 'MySQL Table Sizes';
		}
		
		public function confirmDomainText() {
			return 'Getting MySQL Table Sizes for: ';
		}
	}

?>
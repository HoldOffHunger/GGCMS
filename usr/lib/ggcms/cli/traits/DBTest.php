<?php

	trait DBTest {
		public function runMySQLTest($args) {
			$display = $args['display'];
			
			/*		BROKEN!!!
			
			$raw_test_sql = "select 'hello, world!' as TEST_STRING";
			$test_sql_command = 'mysql -e "' . escapeshellarg($raw_test_sql) . '"';
			
			$raw_test_sql = "select 'hello, world!' as TEST_STRING;";
			$test_sql_command = escapeshellcmd('mysql -e "' . $raw_test_sql . '"');
			*/
			
			/*
			$test_sql_command = 'mysql -e "select \'hello, world\\!\' as TEST_STRING"';
			
			
			
			print(PHP_EOL . PHP_EOL);
			
			$output = shell_exec($test_sql_command);
			*/
			
			$this->db_link->select_db('clonefrom');
			
			$output = $this->runQuery([
				'query'=>'select \'hello, world!\' as TEST_STRING;',
			]);
			
			print('Test Query results: ');
			
			if($display === 'short') {
				if($output[0]['TEST_STRING'] === 'hello, world!') {
					$this->successResults();
				} else {
					$this->failResults();
				}
	#			print("|" . $output_pieces[1] . "|");
			} else {
				print(PHP_EOL);
				print(arr2textTable([$output[0]]));
			}
			
			print(PHP_EOL);
			
			return TRUE;
		}
	}

?>
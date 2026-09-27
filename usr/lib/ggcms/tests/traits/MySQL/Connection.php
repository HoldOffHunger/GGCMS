<?php
	trait ConnectionTrait {
		public $db_link;
		
		public function testConnection() {
			$test_string = 'Hello, World!';
			$test_result = '';
			
			try {
				$this->db_link = new mysqli(
					ini_get("mysqli.default_host"),
					ini_get("mysqli.default_user"),
					ini_get("mysqli.default_pw"),
					'clonefrom',
					ini_get("mysqli.default_port"),
				);
				$query = 'SELECT "' . $test_string . '" as test;';
				$query_result = $this->db_link->query($query);
				
				while ($row = $query_result->fetch_assoc()) {
					$test_result= $row['test'];
				}
			} catch (Exception $e){
			    $error = $e->getMessage();
			    echo $error;
			}
			
			$this->assertEquals($test_result, $test_string);
			
			return TRUE;
		}
	}
?>
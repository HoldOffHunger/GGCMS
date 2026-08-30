<?php

	trait MySQLDatabases {
		public function getUserMySQLDatabases() {
			$mysql_internal_databases = $this->getInternalMySQLDatabaseLookup();		// this function is in trait MySQLInternalDatabases
			$ggcms_internal_databases = $this->getGGCMSInternalMySQLDatabaseLookup();		// this function is in trait MySQLGGCMSInternalDatabases
			$cluster_internal_databases = $this->getClustersInternalMySQLDatabaseLookup();		// this function is in trait MySQLClustersInternalDatabases
			
			$sql_command = 'SHOW DATABASES;';
			
			$create_database_command = 'mysql -e "' . $sql_command . '"';
				
			$databases = explode(PHP_EOL, shell_exec($create_database_command));
			$databases_output_count = count($databases);
			
			unset($databases[0]);
			unset($databases[$databases_output_count - 1]);
			
			$valid_databases = [];
			for($i = 1; $i < $databases_output_count - 1; $i++) {
				$database = $databases[$i];
				if(!array_key_exists($database, $mysql_internal_databases) && !array_key_exists($database, $ggcms_internal_databases) && !array_key_exists($database, $cluster_internal_databases)) {
					$valid_databases[] = $database;
					
					if($database === 'defaultdb') {
						print("VALIDATED!!!!" . $database . PHP_EOL . PHP_EOL);
						print_r($cluster_internal_databases);
					}
				}
			}
			
			return $valid_databases;
		}
	}

?>
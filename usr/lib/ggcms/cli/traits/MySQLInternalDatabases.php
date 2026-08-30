<?php

	trait MySQLInternalDatabases {
		public function getInternalMySQLDatabases() {
			return [
				'information_schema',
				'mysql',
				'performance_schema',
				'sys',
			];
		}
		
		public function getInternalMySQLDatabaseLookup() {
			$databases = $this->getInternalMySQLDatabases();
			
			$lookup = [];
			
			foreach($databases as $database) {
				$lookup[$database] = TRUE;
			}
			
			return $lookup;
		}
	}

?>
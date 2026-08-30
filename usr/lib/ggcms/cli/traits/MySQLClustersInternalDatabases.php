<?php

	trait MySQLClustersInternalDatabases {
		public function getClustersInternalMySQLDatabases() {
			return [
				'defaultdb',
			];
		}
		
		public function getClustersInternalMySQLDatabaseLookup() {
			$databases = $this->getClustersInternalMySQLDatabases();
			
			$lookup = [];
			
			foreach($databases as $database) {
				$lookup[$database] = TRUE;
			}
			
			return $lookup;
		}
	}

?>
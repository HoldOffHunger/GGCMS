<?php

	trait MySQLGGCMSInternalDatabases {
		public function getGGCMSInternalMySQLDatabases() {
			return [
				'alldictionaries',
				'clonefrom',
			];
		}
		
		public function getGGCMSInternalMySQLDatabaseLookup() {
			$databases = $this->getGGCMSInternalMySQLDatabases();
			
			$lookup = [];
			
			foreach($databases as $database) {
				$lookup[$database] = TRUE;
			}
			
			return $lookup;
		}
	}

?>
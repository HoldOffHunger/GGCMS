<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/DNSRecords.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	
	class DomainLister {
		use DBAccess;
		use DNSRecords;
		use CLIAccess;
		use GlobalsTrait;
		
		public function listDomains() {
			$this->setGlobals();
			$this->setMySQLArgs();
			
			$full_mysql = 'SHOW DATABASES;';
			
			$host_count_records = $this->runQuery([
				'query'=>$full_mysql,
			]);
			
			$skip_hash = $this->skipDatabasesHash();
			
			foreach($host_count_records as $host_count_record_key => $host_count_record)  {
				if(array_key_exists($host_count_record['Database'], $skip_hash)) {
					unset($host_count_records[$host_count_record_key]);
				}
			}
			
			print(arr2textTable($host_count_records));
			
			return TRUE;
		}
		
		public function skipDatabasesHash() {
			$skip_databases = $this->skipDatabases();
			
			$skip_database_hash = [];
			
			foreach($skip_databases as $skip_index => $skip_database) {
				$skip_database_hash[$skip_database] = TRUE;
			}
			
			return $skip_database_hash;
		}
		
		public function skipDatabases() {
			return [
				'Database',
				'defaultdb',
				'information_schema',
				'mysql',
				'performance_schema',
				'sys',
				'alldictionaries',
				'clonefrom',
			];
		}
	}
	
?>
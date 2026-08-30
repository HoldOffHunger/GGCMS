<?php

	#depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/BackupTrait.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');
	
	ggreq('traits/ReverseDNSNotation.php');
	
	class DBCacheBlanksChecker {
		use Apache;
		use BackupTrait;
		use DBAccess;
		use DBTest;
		use CLIAccess;
		use DomainValidation;
		use ReverseDNSNotation;
		
		public function checkDBCacheBlanks() {
			$this->setHandle();
			$this->bannerMessage();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			if($this->userConfirm()) {
				$this->checkDBBlanksForDomainCache();
			}
			
			return TRUE;
		}
		
		public function removeBomUtf8($s){
			if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))){
				return substr($s,3);
			} else {
				return $s;
			}
		}
		
		public function checkDBBlanksForDomainCache() {
			$this->reversed_domain = $this->ReverseDomainName(['domain'=>$this->domain]);
			
			ggreq('classes/Database/DBFileCache.php');
			
			$db_cache = new DBFileCache(['handler'=>NULL]);
			
			$this->db_cache_location = $db_cache->DBFileCacheLocation();
			$this->domain_cache_location = $this->db_cache_location . '/' . $this->reversed_domain;
			
			$this->checkDBBlanksForDomainCache_comments();
			$this->checkDBBlanksForDomainCache_userids();
			$this->checkDBBlanksForDomainCache_recordtrees();
			$this->checkDBBlanksForDomainCache_entrychildrecords();
			
			return TRUE;
		}
		
		public function checkDBBlanksForDomainCache_abstractCacheCheck($args) {
			$location = $args['location'];
			$type = $args['type'];
		
			print('Checking Blanks ' . $type . ' File: ' . $location . PHP_EOL);
			
			if(is_file($location)) {
				$file_cache_data = $this->removeBomUtf8(file_get_contents($location));
				
				$file_cache = explode("\n", $file_cache_data);
				
				$sorted = array_values($file_cache);
				
				print("\t");
				
				if($file_cache === $sorted) {
					$this->successResults();
				} else {
					$this->failResults();
				}
				
				print(PHP_EOL);
			} else {
				$this->failResults();
			}
		}
		
		public function checkDBBlanksForDomainCache_comments() {
			$comments_location = $this->domain_cache_location . '/ggcms_Comments_approved_blanks.txt';
			
			return $this->checkDBBlanksForDomainCache_abstractCacheCheck([
				'location'=>$comments_location,
				'type'=>'Comments',
			]);
		}
		
		public function checkDBBlanksForDomainCache_userids() {
			$userids_location = $this->domain_cache_location . '/ggcms_UserIds_blanks.txt';
			
			return $this->checkDBBlanksForDomainCache_abstractCacheCheck([
				'location'=>$userids_location,
				'type'=>'Userids',
			]);
		}
		
		public function checkDBBlanksForDomainCache_recordtrees() {
			$recordtrees_location = $this->domain_cache_location . '/ggcms_RecordTree_blanks.txt';
			
			return $this->checkDBBlanksForDomainCache_abstractCacheCheck([
				'location'=>$recordtrees_location,
				'type'=>'Record Trees',
			]);
		}
		
		public function checkDBBlanksForDomainCache_entrychildrecords() {
			$domain_cache_entrychildrecords_location = $this->domain_cache_location . '/ggcms_EntryChildRecords';
			
			$cache_tables = $this->cacheTables();
			$cache_tables_count = count($cache_tables);
			
			for($i = 0; $i < $cache_tables_count; $i++) {
				$cache_table = $cache_tables[$i];
				
				$cache_table_file_location = $domain_cache_entrychildrecords_location . '/' . $cache_table . '_blanks.txt';
				
				$this->checkDBBlanksForDomainCache_abstractCacheCheck([
					'location'=>$cache_table_file_location,
					'type'=>'EntryChildRecords',
				]);
			}
			
			return TRUE;
		}
		
		public function displayFolderCreateSuccess() {
			print("\t");
			$this->successResults();
			print(' - Directory cleared!' . PHP_EOL);
			
			return TRUE;
		}
		
		public function displayFolderCreateFail() {
			print("\t");
			$this->failResults();
			print(' - Directory already exists!' . PHP_EOL);
			
			return TRUE;
		}
		
		public function defaultFolderMode() {
			return 0744;
		}
		
		public function cacheTables() {
			return [
				'Associated',
				'Association',
				'AvailabilityDateRange',
				'Definition',
				'Description',
				'EntryPermission',
				'EntryTranslation',
				'EventDate',
				'Image',
				'ImageTranslation',
				'Link',
				'Quote',
				'Tag',
				'TextBody',
			];
		}
		
					// Script-Level Functions
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Database caching blanks clearing.',
			]);
		}
		
		public function bannerMessageText() {
			return 'Database Cache Enabler';
		}
		
		public function confirmDomainText() {
			return 'Enabling Database Cache with MySQL For Domain: ';
		}
	}
	
?>
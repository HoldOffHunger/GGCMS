<?php

	#depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/BackupTrait.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');
	
	ggreq('traits/ReverseDNSNotation.php');
	
	class DBCacheClearer {
		use Apache;
		use BackupTrait;
		use DBAccess;
		use DBTest;
		use CLIAccess;
		use DomainValidation;
		use ReverseDNSNotation;
		
		public function clearDBCache() {
			$this->setHandle();
			$this->bannerMessage();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			if($this->userConfirm()) {
				$this->enableDomainCache();
			}
			
			return TRUE;
		}
		
		public function enableDomainCache() {
			$reversed_domain = $this->ReverseDomainName(['domain'=>$this->domain]);
			
			ggreq('classes/Database/DBFileCache.php');
			
			$db_cache = new DBFileCache(['handler'=>NULL]);
			
			$db_cache_location = $db_cache->DBFileCacheLocation();
			
			$domain_cache_location = $db_cache_location . '/' . $reversed_domain;
			$domain_cache_entrychildrecords_location = $domain_cache_location . '/ggcms_EntryChildRecords';
			
			$cache_tables = $this->cacheTables();
			$cache_tables_count = count($cache_tables);
			
			for($i = 0; $i < $cache_tables_count; $i++) {
				$cache_table = $cache_tables[$i];
				
				$cache_table_file_location = $domain_cache_entrychildrecords_location . '/' . $cache_table;
			
				print('Clearing Directory: ' . $cache_table_file_location . PHP_EOL);
				
				if(is_dir($cache_table_file_location)) {
					$command = 'rm -f -r '. $cache_table_file_location . '/*';
					print("\t\t" . $command . PHP_EOL);
					exec($command);
					$this->displayFolderCreateSuccess();
				} else {
			#		mkdir($cache_table_file_location, $this->defaultFolderMode());
					$this->displayFolderCreateFail();
				}
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
				'message'=>'Database caching clearing.',
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
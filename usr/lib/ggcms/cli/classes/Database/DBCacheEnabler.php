<?php

	#depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/BackupTrait.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');
	
	ggreq('traits/ReverseDNSNotation.php');
	
	class DBCacheEnabler {
		use Apache;
		use BackupTrait;
		use DBAccess;
		use DBTest;
		use CLIAccess;
		use DomainValidation;
		use ReverseDNSNotation;
		
		public function enableDomains() {
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
			$this->enableDomainCache_DomainRootDirectory();
			$this->enableDomainCache_EntryChildRecords();
			$this->enableDomainCache_MasterRecord();
			$this->enableDomainCache_RecordTree();
			$this->enableDomainCache_Userids();
			$this->enableDomainCache_Comments_approved();
			$this->enableDomainCache_ChildRecordCount();
			
			$this->changeUserOwnership();
			
			return TRUE;
		}
		
		public function enableDomainCache_DomainRootDirectory() {
			$reversed_domain = $this->ReverseDomainName(['domain'=>$this->domain]);
			
			ggreq('classes/Database/DBFileCache.php');
			
			$db_cache = new DBFileCache(['handler'=>NULL]);
			
			$db_cache_location = $db_cache->DBFileCacheLocation();
			
			$domain_cache_location = $db_cache_location . '/' . $reversed_domain;
			$this->domain_cache_location = $domain_cache_location;
			
			print('Creating Directory: ' . $domain_cache_location . PHP_EOL);
			
			if(is_dir($domain_cache_location)) {
				$this->displayFolderCreateFail();
			} else {
				mkdir($domain_cache_location, $this->defaultFolderMode());
				$this->displayFolderCreateSuccess();
			}
			
			return TRUE;
		}
		
		public function enableDomainCache_EntryChildRecords() {
			$domain_cache_entrychildrecords_location = $this->domain_cache_location . '/ggcms_EntryChildRecords';
			
			print('Creating Directory: ' . $domain_cache_entrychildrecords_location . PHP_EOL);
			
			if(is_dir($domain_cache_entrychildrecords_location)) {
				$this->displayFolderCreateFail();
			} else {
				mkdir($domain_cache_entrychildrecords_location, $this->defaultFolderMode());
				$this->displayFolderCreateSuccess();
			}
			
			$cache_tables = $this->cacheTables();
			$cache_tables_count = count($cache_tables);
			
			for($i = 0; $i < $cache_tables_count; $i++) {
				$cache_table = $cache_tables[$i];
				
				$cache_table_file_location = $domain_cache_entrychildrecords_location . '/' . $cache_table;
			
				print('Creating Directory: ' . $cache_table_file_location . PHP_EOL);
				
				if(is_dir($cache_table_file_location)) {
					$this->displayFolderCreateFail();
				} else {
					mkdir($cache_table_file_location, $this->defaultFolderMode());
					$this->displayFolderCreateSuccess();
				}
				
				$blanks_file_location = $cache_table_file_location . $this->blanksFileSuffix();
				
				print('Creating Directory Blanks-File: ' . $blanks_file_location . PHP_EOL);
				
				if(is_file($blanks_file_location)) {
					$this->displayFileCreateFail();
				} else {
					file_put_contents($blanks_file_location, '', $this->defaultFileMode());
					$this->displayFileCreateSuccess();
				}
			}
			
			return TRUE;
		}
		
		public function enableDomainCache_MasterRecord() {
			$domain_cache_masterrecord_location = $this->domain_cache_location . '/ggcms_MasterRecord';
			
			print('Creating Directory: ' . $domain_cache_masterrecord_location . PHP_EOL);
			
			if(is_dir($domain_cache_masterrecord_location)) {
				$this->displayFolderCreateFail();
			} else {
				mkdir($domain_cache_masterrecord_location, $this->defaultFolderMode());
				$this->displayFolderCreateSuccess();
			}
			
			return TRUE;
		}
		
		public function enableDomainCache_RecordTree() {
			return $this->enableDomainCache_abstract([
				'type'=>'RecordTree',
			]);
		}
		
		public function enableDomainCache_Userids() {
			return $this->enableDomainCache_abstract([
				'type'=>'UserIds',
			]);
		}
		
		public function enableDomainCache_Comments_approved() {
			return $this->enableDomainCache_abstract([
				'type'=>'Comments_approved',
			]);
		}
		
		public function enableDomainCache_ChildRecordCount() {
			$domain_cache_masterrecord_location = $this->domain_cache_location . '/ggcms_ChildRecordCount';
			
			print('Creating Directory: ' . $domain_cache_masterrecord_location . PHP_EOL);
			
			if(is_dir($domain_cache_masterrecord_location)) {
				$this->displayFolderCreateFail();
			} else {
				mkdir($domain_cache_masterrecord_location, $this->defaultFolderMode());
				$this->displayFolderCreateSuccess();
			}
			
			$blanks_file_location = $domain_cache_masterrecord_location . $this->blanksFileSuffix();
			
			print('Creating Directory Blanks-File: ' . $blanks_file_location . PHP_EOL);
			
			if(is_file($blanks_file_location)) {
				$this->displayFileCreateFail();
			} else {
				file_put_contents($blanks_file_location, '', $this->defaultFileMode());
				$this->displayFileCreateSuccess();
			}
			
			return TRUE;
		}
		
		public function enableDomainCache_abstract($args) {
			$domain_cache_userids_location = $this->domain_cache_location . '/ggcms_' . $args['type'];
			
			print('Creating Directory: ' . $domain_cache_userids_location . PHP_EOL);
			
			if(is_dir($domain_cache_userids_location)) {
				$this->displayFolderCreateFail();
			} else {
				mkdir($domain_cache_userids_location, $this->defaultFolderMode());
				$this->displayFolderCreateSuccess();
			}
				
			$blanks_file_location = $domain_cache_userids_location . $this->blanksFileSuffix();
			
			print('Creating Directory Blanks-File: ' . $blanks_file_location . PHP_EOL);
			
			if(is_file($blanks_file_location)) {
				$this->displayFileCreateFail();
			} else {
				file_put_contents($blanks_file_location, '', $this->defaultFileMode());
				$this->displayFileCreateSuccess();
			}
			
			return TRUE;
		}
			
		public function changeUserOwnership() {
			$change_ownership_line = 'chown -R ' . $this->defaultWebServerUser() . ' ' . $this->domain_cache_location;
			
			shell_exec($change_ownership_line);
			
			return TRUE;
		}
		
		public function displayFolderCreateSuccess() {
			print("\t");
			$this->successResults();
			print(' - Directory created!' . PHP_EOL);
			
			return TRUE;
		}
		
		public function displayFolderCreateFail() {
			print("\t");
			$this->failResults();
			print(' - Directory already exists!' . PHP_EOL);
			
			return TRUE;
		}
		
		public function displayFileCreateSuccess() {
			print("\t");
			$this->successResults();
			print(' - File created!' . PHP_EOL);
			
			return TRUE;
		}
		
		public function displayFileCreateFail() {
			print("\t");
			$this->failResults();
			print(' - File already exists!' . PHP_EOL);
			
			return TRUE;
		}
		
		public function defaultFolderMode() {
			return 0744;
		}
		
		public function defaultFileMode() {
			return LOCK_EX;
		}
		
		public function blanksFileSuffix() {
			return '_blanks.txt';
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
				'TextBody_short',
			];
		}
		
					// Script-Level Functions
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Database caching enabling.',
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
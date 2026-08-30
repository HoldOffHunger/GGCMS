<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/Base64.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DataArrays.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/Directories.php');
	clireq('traits/DNSRecords.php');
	clireq('traits/ErrorCLI.php');
	clireq('traits/FileSystem.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/SSL.php');
	clireq('traits/VersionNumber.php');
	ggreq('traits/ReverseDNSNotation.php');
	clireq('traits/DigitalOceanDNSRecords.php');
	
	class DomainRecordLister {
		use Apache;
		use Base64;
		use CLIAccess;
		use DataArrays;
		use DBAccess;
		use DBTest;
		use Directories;
		use DNSRecords;
		use ErrorCLI;
		use FileSystem;
		use GlobalsTrait;
		use SSL;
		use VersionNumber;
		use ReverseDNSNotation;
		use DigitalOceanDNSRecords;
		
		public function listDomainRecords() {
			$this->setHandle();
			$this->bannerMessage();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			$this->listDomainRecordsForDomain();
			
			return TRUE;
		}
		
		public function listDomainRecordsForDomain() {
			$total_records = $this->getDigitalOceanDNSRecords([
				'quiet'=>FALSE,
			]);
			$record_count = count($total_records);
			
			print('Rows Returned:' . PHP_EOL . PHP_EOL);
			
			print("\t\t");
			
			print($record_count);
			
			print(PHP_EOL . PHP_EOL);
			
			print('Records Returned:' . PHP_EOL . PHP_EOL);
			
			print(arr2textTable($total_records));
			
			print(PHP_EOL);
			
			return TRUE;
		}
		
					// Script-Level Functions
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall checking beginning.',
			]);
		}
		
		public function bannerMessageText() {
			return 'DNS Record Checker';
		}
		
		public function confirmDomainText() {
			return 'Checking Domain: ';
		}
	}
	
?>
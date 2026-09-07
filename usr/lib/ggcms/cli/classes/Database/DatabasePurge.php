<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/BackupTrait.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');
	
	class DatabasePurge {
		use Apache;
		use BackupTrait;
		use DBAccess;
		use DBTest;
		use CLIAccess;
		use DomainValidation;
		
		public function purge() {
			$this->setHandle();
			$this->bannerMessage();
			
			if($this->userConfirm()) {
				$this->identifyArchivesToPurge();
			}
			
			return TRUE;
		}
		
		public function identifyArchivesToPurge() {
			print("Archives to Purge --");
			
			print(PHP_EOL . PHP_EOL);
			
			$purge_files = [];
			
			/*
				Dumps moved off the root disk to the volume; see
				BackupTrait::databaseDumpRoot. This walked GGCMS_LOG_DIR for
				<domain>/<application>/archive/, which after the move matches
				nothing at all -- a tool that silently finds no work is worse
				than one that errors, so it walks the new root instead.

				The old loop also had an off-by-two. array_diff removes '.'
				and '..' but keeps their keys, so the remaining entries are
				numbered from 2, while the loop ran from 2 to count-1 -- with
				three archived dumps it purged two of them and left the last
				untouched, every time. A foreach has no index to get wrong.
			*/

			$dump_root = $this->databaseDumpRoot();

			if(!is_dir($dump_root)) {
				$this->purge_files = [];

				print('No dump directory at ' . $dump_root . PHP_EOL . PHP_EOL);

				return TRUE;
			}

			foreach(scandir($dump_root) as $domain) {
				if($domain === '.' || $domain === '..') {
					continue;
				}

				$archive_directory = $this->databaseDumpDirectory([
					'domain'=>$domain,
					'type'=>'archive',
				]);

				if(!is_dir($archive_directory)) {
					continue;
				}

				foreach(scandir($archive_directory) as $archive) {
					if($archive === '.' || $archive === '..') {
						continue;
					}

					$archive_location = $archive_directory . $archive;

					if(is_file($archive_location)) {
						$purge_files[] = $archive_location;
					}
				}
			}
			$this->purge_files = $purge_files;
			$purge_files_count = count($purge_files);
			
			print("Identified Archives to Purge?: ");
			
			print(PHP_EOL . PHP_EOL);
			
			if($purge_files_count === 0) {
				$this->failResults();
			} else {
				$purge_files_displayable = [];
				
				foreach($purge_files as $purge_file) {
					$purge_files_displayable[] = [
						'purge-file'=>$purge_file,
					];
				}
				
				print(arr2textTable($purge_files_displayable));
				
				foreach($purge_files as $purge_file) {
					if(is_file($purge_file)) {
						print('Removing: ' . $purge_file . PHP_EOL);
						
						unlink($purge_file);
					}
				}
				
				$this->successResults();
			}
			
			print(PHP_EOL);
			
			return TRUE;
		}
		
					// Script-Level Functions
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Database archive purging beginning.',
				'argv_index'=>'1',
			]);
		}
		
		public function bannerMessageText() {
			return 'Database Purge Utility';
		}
		
		public function confirmDomainText() {
			return 'Purging MySQL Archive For Domain: ';
		}
	}
	
?>
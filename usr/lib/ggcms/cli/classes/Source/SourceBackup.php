<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/BackupTrait.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	
	class SourceBackup {
		use BackupTrait;
		use DBAccess;
		use DBTest;
		use CLIAccess;
		
		public function backup() {
			$this->setHandle();
			$this->bannerMessage();
			$this->userConfirmSourceCodeTypeMessage();
			
			if($this->userConfirmSourceCodeType()) {
				if($this->userConfirmOutput()) {
					if($this->userConfirm()) {
						$this->generateBackupFolderLocations();
						$this->generateBackupFilenames();
						$this->moveBackupToArchive();
						$this->backupSource();
					}
				}
			}
			
			return TRUE;
		}
		
		public function generateBackupFolderLocations() {
			$source_backup_location = GGCMS_LOG_DIR . 'source/';
			
			if(!is_dir($source_backup_location)) {
				mkdir($source_backup_location);
			}
			
			$source_backup_archive_location = $source_backup_location . 'archive/';
			
			if(!is_dir($source_backup_archive_location)) {
				mkdir($source_backup_archive_location);
			}
			
			$source_backup_backup_location = $source_backup_location . 'backup/';
			
			if(!is_dir($source_backup_backup_location)) {
				mkdir($source_backup_backup_location);
			}
			
			$this->source_location = $source_backup_location;
			$this->archive_location = $source_backup_archive_location;
			$this->backup_location = $source_backup_backup_location;
			
			return TRUE;
		}
		
		public function generateBackupFilenames() {
			print('Source Location: ' . $this->source_location . PHP_EOL);
			print('Archive Location: ' . $this->archive_location . PHP_EOL);
			print('Backup Location: ' . $this->backup_location . PHP_EOL . PHP_EOL);
			
			$backup_modes = $this->backupSourceTree();
			
			$base_backup_filename = 'sourcecode_';
			
			$this->backup_filenames = [];
			$displayable_backup_filenames = [];
			
			if($backup_modes['GGCMS_DIR'][$this->backup]) {
				$backup_file_name = $this->generateCurrentBackupFilename([
					'base_filename'=>$base_backup_filename . 'ggcms-dir',
					'extension'=>'tar',
				]);
				
				$this->backup_filenames['GGCMS_DIR'] = $backup_file_name;
				
				$displayable_backup_filenames[] = [
					'dir'=>'GGCMS_DIR',
					'file'=>$backup_file_name,
				];
			}
			
			if($backup_modes['GGCMS_DEP_DIR'][$this->backup]) {
				$backup_file_name = $this->generateCurrentBackupFilename([
					'base_filename'=>$base_backup_filename . 'ggcms-dep-dir',
					'extension'=>'tar',
				]);
				
				$this->backup_filenames['GGCMS_DEP_DIR'] = $backup_file_name;
				
				$displayable_backup_filenames[] = [
					'dir'=>'GGCMS_DEP_DIR',
					'file'=>$backup_file_name,
				];
			}
			
			if($backup_modes['GGCMS_DOC_ROOT'][$this->backup]) {
				$backup_file_name = $this->generateCurrentBackupFilename([
					'base_filename'=>$base_backup_filename . 'ggcms-doc-root',
					'extension'=>'tar',
				]);
				
				$this->backup_filenames['GGCMS_DOC_ROOT'] = $backup_file_name;
				
				$displayable_backup_filenames[] = [
					'dir'=>'GGCMS_DOC_ROOT',
					'file'=>$backup_file_name,
				];
			}
			
			print(arr2textTable($displayable_backup_filenames));
			print(PHP_EOL);
			
			return TRUE;
		}
		
		public function moveBackupToArchive() {
			print('Move Backup Files to Archive --' . PHP_EOL . PHP_EOL);
			
			$moved_files = [];
			
			foreach (new DirectoryIterator($this->backup_location) as $backup_file) {
				if($backup_file->isDot()) continue;
				$backup_filename = $backup_file->getFilename();
				
				$moved_files[] = ['Archived Backup'=>$backup_filename];
			}
			
			$moved_files_count = count($moved_files);
			
			if($moved_files_count !== 0) {
				print(arr2textTable($moved_files));
				
				foreach($moved_files as $moved_file) {
					$moved_file_location = $moved_file['Archived Backup'];
					$from_location = $this->backup_location . $moved_file_location;
					$to_location = $this->archive_location . $moved_file_location;
					
					if(!rename($from_location, $to_location)) {
						print(PHP_EOL . PHP_EOL . "\t\tMOVE FILE FAILED!!!" . PHP_EOL . PHP_EOL);
						
						print("Source: " . $from_location . PHP_EOL);
						print("Destination: " . $to_location . PHP_EOL . PHP_EOL);
					}
				}
			}
			
			print(PHP_EOL . 'Move Backup Files to Archive Results: [moved files: ' . $moved_files_count . '] ');
			
			$this->successResults();
			
			return TRUE;
		}
		
		public function backupSource() {
			if($this->output === 'l') {
				print(PHP_EOL . PHP_EOL . 'Proceeding with Backup Type: ' . $this->backup . PHP_EOL . PHP_EOL);
			}
			
			foreach($this->backup_filenames as $backup_location_constant => $backup_filename) {
				$backup_location = constant($backup_location_constant);
				
				$destination_location = $this->backup_location . $backup_filename;
				
				if(!is_dir($backup_location)) {
					print(PHP_EOL . PHP_EOL . "\t\tINVALID DESTINATION LOCATION!!!" . PHP_EOL . PHP_EOL);
				}
				
				$tar_file = new PharData($destination_location);
				
				$this->backupSourceRecursive([
					'tar_file'=>$tar_file,
					'location'=>$backup_location,
					'recursive_max'=>1,
				]);
				$tar_file->compress(Phar::GZ);
				
				unlink($destination_location);
			}
			
			return TRUE;
		}
		
		public function backupSourceRecursive($args) {
			$tar_file = $args['tar_file'];
			$location = $args['location'];
			$recursive_max = $args['recursive_max'];
			
			$tar_file->addEmptyDir($location);
			
			foreach (new DirectoryIterator($location) as $backup_folder_item) {
				if($backup_folder_item->isDot()) continue;
				
				$backup_folder_item_location = $location . $backup_folder_item->getFilename();
				
				if($this->output === 'l') {
					print('Backing Up ');
				}
				
				if($backup_folder_item->isDir()) {
					$backup_folder_item_location .= '/';
					if($recursive_max > 0) {
						if($this->output === 'l') {
							print('Directory');
						}
						$recursive_max--;
						$this->backupSourceRecursive([
							'tar_file'=>$tar_file,
							'location'=>$backup_folder_item_location,
							'recursive_max'=>$recursive_max,
						]);
					} else {
						print("-- Error, recursive max!!!");
						print(PHP_EOL);
						print(PHP_EOL);
					}
				} elseif($backup_folder_item->isFile()) {
					if($this->output === 'l') {
						print('File');
					}
					$tar_file->addFile($backup_folder_item_location);
				}
				
				if($this->output === 'l') {
					print(' :: ');
					print($backup_folder_item_location);
					print(PHP_EOL);
				}
			}
			
			return TRUE;
		}
		
		public function backupSourceTree() {
			if(property_exists($this, 'source_tree')) {
				return $this->source_tree;
			}
			
			$source_tree = [
				'GGCMS_DIR'=>[
					'a'=>TRUE,
					'b'=>TRUE,
				],
				'GGCMS_DEP_DIR'=>[
					'a'=>TRUE,
					'd'=>TRUE,
				],
				'GGCMS_DOC_ROOT'=>[
					'a'=>TRUE,
					'r'=>TRUE,
				],
			];
			
			return $this->source_tree = $source_tree;
		}
		
			/*
			
				define('GGCMS_DIR', '/usr/lib/ggcms/src/');
				define('GGCMS_DEP_DIR', '/usr/lib/ggcms/dep/');
				define('GGCMS_LOG_DIR', '/var/log/ggcms/');
				define('GGCMS_DATA_DIR', '/srv/ggcms/');
				define('GGCMS_CONFIG_DIR', '/etc/ggcms/');
				define('GGCMS_DOC_ROOT', '/var/www/html/');
			
			*/
		
		public function userConfirmSourceCodeTypeMessage() {
			print("\t" . '(a)ll : backup all code' . PHP_EOL);
			print(PHP_EOL);
			print("\t\t" . 'Backup GGCMS_DIR + GGCMS_DEP_DIR + GGCMS_DOC_ROOT: ' . GGCMS_DIR  . ' + ' . GGCMS_DEP_DIR . ' + ' . GGCMS_DOC_ROOT . PHP_EOL);
			print(PHP_EOL);
			
			print("\t" . '(b)asics : basics' . PHP_EOL);
			print(PHP_EOL);
			print("\t\t" . 'Backup GGCMS_DIR: ' . GGCMS_DIR . PHP_EOL);
			print(PHP_EOL);
			
			print("\t" . '(d)ependencies : dependencies' . PHP_EOL);
			print(PHP_EOL);
			print("\t\t" . 'Backup GGCMS_DEP_DIR: ' . GGCMS_DEP_DIR . PHP_EOL);
			print(PHP_EOL);
			
			print("\t" . '(r)oot : docroot' . PHP_EOL);
			print(PHP_EOL);
			print("\t\t" . 'Backup GGCMS_DOC_ROOT: ' . GGCMS_DOC_ROOT . PHP_EOL);
			
			print(PHP_EOL);
			
			return TRUE;
		}
		
		public function userConfirmSourceCodeType() {
			return $this->abstractConfirmDialogue([
				'message'=>'Choose Source Code Backup Type --',
				'prompt'=>'Backup Code -- (a)ll, (b)asics, (d)ependencies, (r)oot:',
				'index'=>1,
				'internal_key'=>'backup',
				'valid_answers'=>[
					'a',
					'b',
					'd',
					'r',
				],
			]);
		}
		
		public function userConfirmOutput() {
			return $this->abstractConfirmDialogue([
				'message'=>'Choose Backup Output --',
				'prompt'=>'Backup Code -- (l)oud, (s)ilent:',
				'index'=>1,
				'internal_key'=>'output',
				'valid_answers'=>[
					'l',
					's',
				],
			]);
		}
		
					// Script-Level Functions
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall backup beginning.',
			]);
		}
		
		public function bannerMessageText() {
			return 'GGCMS Source Code Backup Utility';
		}
		
		public function confirmDomainText() {
			return 'Checking Domain: ';
		}
	}
	
?>
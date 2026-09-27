<?php

	#depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/BackupTrait.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	
	class DatabaseBackup {
		use Apache;
		use BackupTrait;
		use DBAccess;
		use DomainValidation;
		use DBTest;
		use CLIAccess;
		
		public $archive_or_backup;
		public $archive_or_backup_nicetype;
		public $backup_databases;
		public $backup_dir;
		public $archive_dir;
		public $file_location;
		
		public function backup() {
			$this->setHandle();
			$this->bannerMessage();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			if($this->userConfirm()) {
				$this->setMySQLArgs();
				
				#if($this->confirmArchiveOrBackup()) {
				#	print(PHP_EOL . PHP_EOL . "BT: CONFIRMED!" . PHP_EOL . PHP_EOL . "|" . $this->archive_or_backup . "|" . PHP_EOL . PHP_EOL);
					$this->runMySQLTest(['display'=>'short']);
					$this->verifyBackupFolderLocation();
					$this->confirmArchiveOrBackup();
					
					if($this->archive_or_backup === 'a') {
						$this->moveLastBackupToArchive();
					}
					
					$this->verifyBackupFileLocation();
					$this->mySQLDump();
				#}
			}
			
#			print(PHP_EOL . PHP_EOL . "SOYBEANS!!!!");
			
			return TRUE;
		}
		
		public function confirmArchiveOrBackup() {
			print("Backup or Archive?: ");
			
			$databases_backed_up = $this->getBackedUpMySQLDatabases();
			
			#print_r($databases_backed_up);
			#die("BT:");
			
			if(array_key_exists($this->host, $databases_backed_up)) {
				$this->archive_or_backup = 'a';
				$this->archive_or_backup_nicetype = 'Archive';
			} else {
				$this->archive_or_backup = 'b';
				$this->archive_or_backup_nicetype = 'Backup';
			}
			
			print ('[');
			
			print($this->archive_or_backup_nicetype);
			
			print(' (');
			print($this->archive_or_backup);
			print(')');
			
			print('] ');
			
			$this->successResults();
			
			print(PHP_EOL);
			
			return TRUE;
		}
		
		public function getBackedUpMySQLDatabases() {
			if(isset($this->backup_databases)) {
				return $this->backup_databases;
			}
			$backup_directory = scandir($this->backup_dir);
			
			#print_r($backup_directory);
			$contents = array_diff($backup_directory, ['.', '..']);
		#	print("!!!!");
		#	print_r($contents);
		#	print("!!!!");
			
			$backedup_domains = [];
			
			foreach($contents as $content) {
				$content_pieces = explode('_', $content);
				
				$content_pieces_count = count($content_pieces);
				
				if($content_pieces_count > 2) {
					$backup_type = $content_pieces[0];
					
				#print("BT: CONTENT!" . $backup_type . "|");
					if($backup_type === 'mysqldump') {
						$backup_domain = $content_pieces[1];
						
						if(!array_key_exists($backup_domain, $backedup_domains)) {
							$backedup_domains[$backup_domain] = [];
						}
						
						$backedup_domains[$backup_domain][] = $content;
					}
				}
			}
			
			$this->backup_databases = $backedup_domains;
			
			#print("BT: huh????????");
			#print_r($backedup_domains);
			
			return $backedup_domains;
		}
		
		public function verifyBackupFolderLocation() {
			print('Verify Backup Folder Location(s) -- ' . PHP_EOL . PHP_EOL);
			
			$backup_dir = $this->databaseDumpDirectory(['domain'=>$this->domain, 'type'=>'backup']);
			$archive_dir = $this->databaseDumpDirectory(['domain'=>$this->domain, 'type'=>'archive']);
			
			$new_dirs = [
				$backup_dir,
				$archive_dir,
			];
			
			$this->backup_dir = $backup_dir;
			$this->archive_dir = $archive_dir;
			
			$errors = [];
			
			foreach ($new_dirs as $new_dir) {
				if(!is_dir($new_dir)) {
					if(!mkdir(
						$new_dir,
						$this->defaultWebServerUserMode(),
						TRUE
					)) {
						$errors[] = 'could not make new dir, ' . $new_dir;
					}
					
					if(!chown($new_dir, $this->defaultWebServerUser())) {
						$errors[] = 'could not assign new dir to default web server user, ' . $new_dir;
					}
				}
			}
			
			$error_count = count($errors);
			
			if($error_count === 0) {
				print("\t" . 'Backup Directory: ' . $backup_dir);
				print(PHP_EOL);
				print("\t" . 'Archive Directory: ' . $archive_dir);
				print(PHP_EOL . PHP_EOL);
				
				print('Verify Backup Folder Location(s): ');
				$this->successResults();
			} else {
				print('Verify Backup Folder Location(s): ');
				$this->failResults();
				print(' (errors: ' . implode(', ', $errors) . ')');
			}
			
			print(PHP_EOL);
			
			return TRUE;
		}
		
		public function verifyBackupFileLocation() {
			print("Verify Backup File Location(s) -- " . PHP_EOL . PHP_EOL);
			
			$errors = [];
		
			if($this->archive_or_backup === 'a') {
				$dir_type = 'Archive';
				$folder_location = $this->archive_dir;
			} elseif($this->archive_or_backup === 'b') {
				$dir_type = 'Backup';
				$folder_location = $this->backup_dir;
			}
			
			$backup_file_name = $this->generateCurrentBackupFilename([
				'base_filename'=>'mysqldump_' . $this->domain,
				'extension'=>'sql',
			]);
			
			$file_location = $folder_location . $backup_file_name;
			
			$this->file_location = $file_location;
			
			if(is_file($file_location)) {
				$errors[] = 'file already exists - ' . $file_location;
			}
			
			$error_count = count($errors);
			
			if($error_count === 0) {
				print("\t" . $dir_type . ' File Location: ' . $file_location);
				print(PHP_EOL . PHP_EOL);
				
				print('Verify Backup File Location(s): ');
				$this->successResults();
			} else {
				print('Verify Backup File Location(s): ');
				$this->failResults();
				print(' (errors: ' . implode(', ', $errors) . ')');
			}
			
			print(PHP_EOL);
			
			return TRUE;
		}
		
		public function mySQLDump() {
			print('Run MySQL Dump --');
			
			print(PHP_EOL . PHP_EOL);
			print('    ');
			
			/*
				utf8mb4 on the connection, and SET NAMES left in the file.

				The second half of that was not true until 6 September 2026.  The
				command carried -N, which mysqldump documents as --no-set-names,
				"Same as --skip-set-charset" -- so it explicitly stripped the SET
				NAMES line this comment claimed was present.  The 1 September fix
				corrected the connection, so dumps taken after it hold correct
				utf8mb4 bytes, but the file never said so and a restore through a
				client defaulting to anything else would have misread them.  -N is
				gone; it was doing nothing anybody wanted.

				This dumped through a latin1 connection until 1 September 2026, and
				that is lossy.  MySQL's latin1 is really cp1252, so dashes and curly
				quotes did survive -- but nothing beyond cp1252 did, and it failed
				silently.  Measured on revoltlib the day it was found: 35 titles, 9
				text bodies and 1 description restored as question marks.

				Every dump taken before that date is a cp1252 file carrying no SET
				NAMES, and has to be restored the way it was written:
				`mysql --default-character-set=latin1`.  A file from before the change
				and a file from after are not interchangeable, and nothing in the
				filename says which one you are holding.
			*/
			$mysqldump = 'nice mysqldump --max_allowed_packet=1M --default-character-set=utf8mb4 --no-tablespaces --routines --quick --skip-triggers --set-gtid-purged=OFF ' . $this->host . ' > ' . $this->file_location;
			
			$mysqldump_pieces = explode(' ', $mysqldump);
			print(implode(PHP_EOL . '    ', $mysqldump_pieces));
			
		#	$mysqldump_results = '';	# BT: turn me on for testing
			$mysqldump_results = shell_exec($mysqldump);
			
			print(PHP_EOL . PHP_EOL);
			
			print('MySQL Dump: ');
			
			if(strlen($mysqldump_results) === 0) {
				$this->successResults();
			} else {
				$this->failResults();
			}
			
			print(PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function moveLastBackupToArchive() {
			print('Move Last Backups to Archives Directory --');
			
			print(PHP_EOL . PHP_EOL);
			
			$backups_to_move = $this->backup_databases[$this->domain];
			$backup_dir = $this->backup_dir;
			$archive_dir = $this->archive_dir;
			
			foreach($backups_to_move as $backup_to_move) {
				$source_location = $backup_dir . $backup_to_move;
				$destination_location = $archive_dir . $backup_to_move;
				
				print('    ');
				print('Source: ' . $source_location . PHP_EOL);
				print('    ');
				print('Destination: ' . $destination_location . PHP_EOL);
				
				print('    ');
				print('Move: ');
				
				if(rename($source_location, $destination_location)) {
					$this->successResults();
				} else {
					$this->failResults();
				}
				
				print(PHP_EOL . PHP_EOL);
			}
			
			return TRUE;
		}
		
					// Script-Level Functions
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Database backup beginning.',
			]);
		}
		
		public function bannerMessageText() {
			return 'Database Backup Utility';
		}
		
		public function confirmDomainText() {
			return 'Backing Up MySQL For Domain: ';
		}
	}
	
?>
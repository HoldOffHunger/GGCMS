<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/ByteDisplay.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/CLIAccess.php');
	
	class FreeSpace {
		use ByteDisplay;
		use DBAccess;
		use DBTest;
		use CLIAccess;
		
		public function checkFreeSpace() {
			$this->setHandle();
			$this->bannerMessage();
			
			$free_bytes = disk_free_space('/');
			$free_bytes_formatted = $this->formatBytes([
				'number'=>$free_bytes,
			]);
			
			print('PHP disk_free_space: ' . $free_bytes_formatted . PHP_EOL);
						
			$disk_usage_command = "du -s / 2>&1 | grep -v  '^du:'";
			$disk_usage_output = shell_exec($disk_usage_command);
			
			#print_r($disk_usage_output);
			$disk_usage_output_pieces = explode("\t", $disk_usage_output);
			
			#print_r($disk_usage_output_pieces);
			$disk_usage_space = $disk_usage_output_pieces[0];
			
			print('DU -S (Disk Usage, --separate-dirs): ');
			
			print($disk_usage_space);
			
			print(PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
					// Script-Level Functions
		
		public function bannerMessageText() {
			return 'GGCMS Free Space';
		}
	}
	
?>
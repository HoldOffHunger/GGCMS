<?php

	trait BackupTrait {
		public function generateCurrentBackupFilename($args) {
			$base_filename = $args['base_filename'];
			$extension = $args['extension'];
			
			$backup_filename = $base_filename;
			
			$microtime = microtime();
			
			#print("BT: MICRO TIIIIIIIIIIIME!" . $microtime . '|' . PHP_EOL . PHP_EOL);
			
			$microtime_pieces = explode(' ', $microtime);
			
			$fraction_value = $microtime_pieces[0];
			
			/*
			print_r(
	preg_replace('/^0./', '', $string)
);
			*/
			$fraction_value = preg_replace('/^0./', '', $fraction_value);
			
			$integer_value = $microtime_pieces[1];
			
			$backup_filename .= '_' . $integer_value . '_' . $fraction_value;
			
			if(strlen($extension) !== 0) {
				$backup_filename .= '.' . $extension;
			}
			
			#print_r($microtime_pieces);
			
			return $backup_filename;
		}
	}

?>
<?php

	trait DigitalOceanDNSRecords {
		public function getDigitalOceanDNSRecords($args) {
			$command = 'doctl compute domain records list ' . $this->domain;
			
			if(!$args['quiet']) {
				print('Running the following command:' . PHP_EOL . PHP_EOL);
				
				print("\t\t");
				
				print($command);
				
				print(PHP_EOL . PHP_EOL);
			}
			
			$output = trim(shell_exec($command));
			
			$records = explode(PHP_EOL, $output);
			
			$record_count = count($records);
			
			$headers = preg_split('/\s+/', $records[0]);
			
			$total_records = [];
			
			for($i = 1; $i < $record_count; $i++) {
				$new_record = [];
				
				$record = $records[$i];
				
				$row_pieces = preg_split('/\s+/', $record);
				$row_pieces_count = count($row_pieces);
				
				for($j = 0; $j < $row_pieces_count; $j++) {
					$row_piece = $row_pieces[$j];
					
					$new_record[$headers[$j]] = $row_piece;
				}
				
				$total_records[] = $new_record;
			}
			
			return $total_records;
		}
		
		public function formatDomainRecords($args) {
			$total_records = $args['total_records'];
			
			$formatted_records = [];
			
			foreach($total_records as $total_record) {
				if(!array_key_exists($total_record['Type'], $formatted_records)) {
					$formatted_records[$total_record['Type']] = [];
				}
				
				$formatted_records[$total_record['Type']][] = $total_record;
			}
			
			return $formatted_records;
		}
		
		public function getFormattedDomainRecords() {
			$total_records = $this->getDigitalOceanDNSRecords([
				'quiet'=>TRUE,
			]);
			
			$formatted_records = $this->formatDomainRecords([
				'total_records'=>$total_records,
			]);
			
			return $formatted_records;
		}
	}

?>
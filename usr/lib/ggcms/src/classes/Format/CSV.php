<?php

	class CSV extends AbstractBaseFormat {
		public function MimeType() {
			return 'text/csv';
		}
		
			// Display CSV
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$this->script->DisplayTemplates();
			
			return print($this->GenerateCSV());
		}
		
		public function GenerateCSV() {
			$data = [
				[
					'RecordType',
					'Recordid',
					'FieldName',
					'FieldValue',
				],
			];
			
			foreach($this->script->record_to_use as $record_key => $record_value) {
				if($record_value || $record_value === 0 || $record_value === 0.0 || $record_value === '0') {
					if(is_array($record_value)) {
						foreach($record_value as $child_index => $child) {
							foreach($child as $child_field => $child_key) {
								$data[] = [
									$record_key,
									$child['id'],
									$child_field,
									$child_key,
								];
							}
						}
					} else {
						$data[] = [
							'Entry',
							$this->script->record_to_use['id'],
							$record_key,
							$record_value,
						];
					}
				}
			}
			
			$escaped_data = $this->EscapeDataForCSV(['data'=>$data]);
			
			return $escaped_data;
		}
		
		public function EscapeDataForCSV($args) {
			$data = $args['data'];
			$fp = fopen('php://temp', 'w+');
			
			foreach ($data as $fields) {
				// Add row to CSV buffer
				fputcsv($fp, $fields, ',', '"', '');
			}
			rewind($fp); // Set the pointer back to the start
			$csv_contents = stream_get_contents($fp); // Fetch the contents of our CSV
			fclose($fp); // Close our pointer and free up memory and /tmp space
			return $csv_contents;
		}
	}
	
?>
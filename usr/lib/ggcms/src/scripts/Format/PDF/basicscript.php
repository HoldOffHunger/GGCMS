<?php

	class basicscript extends baseformat {
			// Display Info
			// -------------------------------------------------------
		
		public function SetDocumentAttributes() {
			$record_to_use = $this->SetRecordToUseForMetadata();
			
			$pdf_data = [];
			$pdf_data['Title'] = $this->SetMetadata_Title();
			$pdf_data['Author'] = $this->SetMetadata_Author();
			$pdf_data['Subject'] = $this->SetMetadata_Subject();
			$pdf_data['Keywords'] = $this->SetMetadata_Keywords();
			
			$this->pdf_data = $pdf_data;
			
			return TRUE;
		}
		
		public function SetMetadata_Title() {
			$title = '';
			$record_to_use = $this->record_to_use;
			
			if($record_to_use) {
				if($record_to_use['Title']) {
					$title .= $record_to_use['Title'];
				}
				
				if($record_to_use['Subtitle']) {
					if($title) {
						$title .= ' : ';
					}
					
					$title .= $record_to_use['Subtitle'];
				}
			}
			
			return $title;
		}
		
		public function SetMetadata_Author() {
			$author = '';
			$record_to_use = $this->record_to_use;
			
			if($record_to_use) {
				if($record_to_use['textbody']) {
					$textbody = $record_to_use['textbody'];
					
					$textbody_to_use = $textbody[0];
					
					$author .= 'From : ' . $textbody_to_use['Source'] . '.';
				}
			}
			
			return $author;
		}
		
		public function SetMetadata_Subject() {
			$subject = '';
			$record_to_use = false;
			
			$parent_code = $this->object_parent;
			
			$record_count = count($this->record_list);
			
			for($i = 0; $i < $record_count; $i++) {
				$record = $this->record_list[$i];
				
				if($record['Code'] === $parent_code) {
					$record_to_use = $record;
					$i = $record_count;
				}
			}
			
			if($record_to_use) {
				if($record_to_use['Title']) {
					$subject .= $record_to_use['Title'];
				}
				
				if($record_to_use['Subtitle']) {
					if($subject) {
						$subject .= ' : ';
					}
					
					$subject .= $record_to_use['Subtitle'];
				}
			} else {
				$primary_host_record = $this->primary_host_record;
				
				if($primary_host_record) {
					if($primary_host_record['Subject']) {
						$subject .= $primary_host_record['Subject'];
					}
					
					if($primary_host_record['Classification']) {
						if($subject) {
							$subject .= ', ';
						}
						
						$subject .= $primary_host_record['Classification'];
					}
					
					if($primary_host_record['NewsKeywords']) {
						if($subject) {
							$subject .= ', ';
						}
						
						$subject .= $primary_host_record['NewsKeywords'];
					}
				}
			}
			
			return $subject;
		}
		
		public function SetMetadata_Keywords() {
			$keywords = '';
			$record_to_use = $this->record_to_use;
			
			if($record_to_use) {
				if($record_to_use['tag']) {
					$tags = $record_to_use['tag'];
					$tag_count = count($tags);
					
					$tag_display = [];
					
					for($i = 0; $i < $tag_count; $i++) {
						$tag = $tags[$i];
						
						$tag_display[] = $tag['Tag'];
					}
					
					$keywords = implode(', ', $tag_display);
				}
			}
			
			return $keywords;
		}
	}

?>
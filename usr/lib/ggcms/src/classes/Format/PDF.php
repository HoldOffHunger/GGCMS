<?php

	class PDF extends AbstractBaseFormat {
		public function MimeType() {
			return 'application/pdf';
		}
		
		public function HTMLEntities() {
			return FALSE;
		}
		
		public function Construct_Requires_Extras() {
			depreq('php-html-to-pdf/php-html-to-pdf.php');
			
			return TRUE;
		}
		
			// Display PDF
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$source_file_location = $this->SetSourceFileLocation();
			$pdf_file_location = $this->SetOutputFileLocation();
			
			$pdf_input = $this->RunTemplates();
			
			if(!$pdf_input) {
				return FALSE;
			}
			
			$old_pdf_input = '';
			
			if(is_file($source_file_location)) {
				$old_pdf_input = file_get_contents($source_file_location);
			}
			if($_GET['forceregen'] || !is_file($pdf_file_location) || $old_pdf_input !== $pdf_input) {
				$pdf_object = $this->SetPDFObject();
				
				$this->ConvertHTMLToFormat();
				
				$pdf_object->Output($pdf_file_location, 'F', TRUE);
						
				$file_handle_for_source = fopen($source_file_location, 'w+');
				fwrite($file_handle_for_source, $pdf_input);
				fclose($file_handle_for_source);
			}
			
			return readfile($pdf_file_location);
		}
		
		public function ConvertHTMLToFormat() {
			$pdf_input = $this->RunTemplates();
			
			$this->pdf_object->WriteHTML([
				'html'=>$pdf_input,
				'language'=>$this->language->GetLanguageCode(),
			]);
			
			return TRUE;
		}
		
		public function SetPDFObject() {
			$meta_data = $this->SetPDFObject_MetaData();
			$pdf_object = new HTMLtoPDF($meta_data);
			$this->pdf_object = $pdf_object;
			
			return $this->pdf_object = $pdf_object;
		}
		
		public function SetPDFObject_MetaData() {
			$this->script->SetDocumentAttributes();
			$pdf_data = $this->script->pdf_data;
			
			$pdf_data['Creator'] = $this->version_object->GetSoftwareName() . ', v. ' . $this->version_object->GetSoftwareVersion();
			
			return $pdf_data;
		}
	}
	
?>
<?php

	class SGML extends AbstractBaseFormat {
		public function MimeType() {
			return 'text/sgml';
		}
		
		public function DocumentStartSyntax() {
			return '<sgml>';
		}
		
		public function DocumentEndSyntax() {
			return '</sgml>';
		}
		
			// Display SGML
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$source_file_location = $this->SetSourceFileLocation();
			$sgml_file_location = $this->SetOutputFileLocation();
			
			$sgml_input = $this->RunTemplates();
			
			if(!$sgml_input) {
				return FALSE;
			}
			
			$old_sgml_input = '';
			
			if(is_file($source_file_location)) {
				$old_sgml_input = file_get_contents($source_file_location);
			}
			
			if($_GET['forceregen'] ||  !is_file($sgml_file_location) || $old_sgml_input != $sgml_input) {
				$sgml_output = $this->ConvertHTMLToFormat();
				
				$file_handle_for_source = fopen($sgml_file_location, 'w+');
				fwrite($file_handle_for_source, $sgml_output);
				fclose($file_handle_for_source);
				
				$file_handle_for_source = fopen($source_file_location, 'w+');
				fwrite($file_handle_for_source, $sgml_input);
				fclose($file_handle_for_source);
			}
			
			$this->StartDocument();
			readfile($sgml_file_location);
			$this->EndDocument();
			
			return TRUE;
		}
		
		public function ConvertHTMLToFormat() {
			$sgml_output = $this->RunTemplates();
			
			$swaps = $this->HTMLtoSGMLReplacements_OpeningsAndClosings();
			
			$sgml_output = str_replace(array_keys($swaps), array_values($swaps), $sgml_output);
			
			$sgml_document = $sgml_output;
			
			return $this->sgml_output = $sgml_document;
		}
		
		public function HTMLtoSGMLReplacements() {
			return [
				'h1'=>'header-level-1',
				'h2'=>'header-level-2',
				'h3'=>'header-level-3',
				'h4'=>'header-level-4',
				'h5'=>'header-level-5',
				'h6'=>'header-level-6',
				'h7'=>'header-level-7',
				'p'=>'paragraph',
				'br'=>'linebreak',
				
				'i'=>'italic',
				'em'=>'italic',
				
				'b'=>'bold',
				'strong'=>'bold',
				
				'u'=>'underline',
				's'=>'strikethrough',
				
				'li'=>'listitem',
				'ul'=>'list',
				
				'img'=>'image',
				
				'a'=>'link',
			];
		}
			
		public function HTMLtoSGMLReplacements_OpeningsAndClosings() {
			$base = $this->HTMLtoSGMLReplacements();
			
			$swaps = [];
			
			foreach($base as $base_key => $base_value) {
				$swaps['<' . $base_key] = '<' . $base_value;
				$swaps['</' . $base_key] = '</' . $base_value;
			}
			
			return $swaps;
		}
	}
	
?>
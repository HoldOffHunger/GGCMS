<?php

	class TEX extends AbstractBaseFormat {
		public $tex_output;
		public $title;
		public $author;
		public $description;
		
		public function MimeType() {
			return 'application/x-tex';
		}
		
		public function DocumentStartSyntax() {
			return 
				'\documentclass{article}' . "\n\n" .
				'\usepackage{ulem}' . "\n" .
				'\usepackage{csquotes}' . "\n\n" .
				'\begin{document}' . "\n\n" .
				'\title{' . $this->title . '}' . "\n" .
				'\author{From: ' . $this->author . '}' . "\n\n" .
				'\maketitle' . "\n\n" .
				'\begin{abstract}' . "\n" .
				$this->description . "\n" .				# BT:!!!!
				'\end{abstract}' . "\n\n";
		}
		
		public function DocumentEndSyntax() {
			return '\end{document}';
		}
		
			// Display TEX
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$source_file_location = $this->SetSourceFileLocation();
			$latex_file_location = $this->SetOutputFileLocation();
			
			$this->SetTitle();
			$this->SetAuthor();
			$this->SetDescription();
			$latex_input = $this->RunTemplates();
			
			if(!$latex_input) {
				return FALSE;
			}
			
			$old_latex_input = '';
			
			if(is_file($source_file_location)) {
				$old_latex_input = file_get_contents($source_file_location);
			}
			
			if(!is_file($latex_file_location) || $old_latex_input != $latex_input) {
				$latex_output = $this->ConvertHTMLToFormat();
				
				$file_handle_for_source = fopen($latex_file_location, 'w+');
				fwrite($file_handle_for_source, $latex_output);
				fclose($file_handle_for_source);
				
				$file_handle_for_source = fopen($source_file_location, 'w+');
				fwrite($file_handle_for_source, $latex_input);
				fclose($file_handle_for_source);
			}
			
			return readfile($latex_file_location);
		}
		
		public function ConvertHTMLToFormat() {
			$tex_output = $this->RunTemplates();
			
			$tex_output = str_replace('<h1>', '\section{', $tex_output);
			$tex_output = str_replace('</h1>', '}' . "\n", $tex_output);
			
			$tex_output = str_replace('<h2>', '\subsection{', $tex_output);
			$tex_output = str_replace('</h2>', '}' . "\n", $tex_output);
			
			$tex_output = str_replace('<blockquote>', '\begin{displayquote}' . "\n", $tex_output);
			$tex_output = str_replace('</blockquote>', "\n" . '\end{displayquote}', $tex_output);
			
			$tex_output = str_replace('<b>', '\textbf{', $tex_output);
			$tex_output = str_replace('</b>', '}', $tex_output);
			
			$tex_output = str_replace('<i>', '\textit{', $tex_output);
			$tex_output = str_replace('</i>', '}', $tex_output);
			
			$tex_output = str_replace('<u>', '\underline{', $tex_output);
			$tex_output = str_replace('</u>', '}', $tex_output);
			
			$tex_output = str_replace('<s>', 'sout{', $tex_output);
			$tex_output = str_replace('</s>', '}', $tex_output);
			
			$tex_output = html_entity_decode($tex_output);
			$tex_output = strip_tags($tex_output);
			
			$tex_document_header = $this->DocumentStartSyntax();
			
			$tex_document_footer = $this->DocumentEndSyntax();
			
			$tex_document =
				$tex_document_header .
				$tex_output .
				$tex_document_footer;
			
			return $this->tex_output = $tex_document;
		}
		
		public function SetTitle() {
			$title = '';
			
			if($this->script->record_to_use['Title']) {
				$title = $this->script->record_to_use['Title'];
			}
			
			if($this->script->record_to_use['Subtitle']) {
				if($title) {
					$title .= ' : ';
				}
				
				$title .= $this->script->record_to_use['Subtitle'];
			}
			
			return $this->title = $title;
		}
		
		public function SetAuthor() {
			$author_text = '';
			if($this->script->record_to_use['textbody']) {
				$textbody_count = count($this->script->record_to_use['textbody']);
				if($textbody_count) {
					$textbody = $this->script->record_to_use['textbody'][0];
					
					if($textbody['Source']) {
						$author_text .= 'From : ' . $textbody['Source'] . '. ';
					}
				}
			}
			
			$this->author = $author_text;
		}
		
		public function SetDescription() {
			$description_text = '';
			if($this->script->record_to_use['description']) {
				$description_count = count($this->script->record_to_use['description']);
				if($description_count) {
					$description = $this->script->record_to_use['description'][0];
					
					$description_text .= $description['Description'];
					
					if($description['Source']) {
						$description_text .= ' (From : ' . $description['Source'] . '.)';
					}
				}
			}
			
			return $this->description = $description_text;
		}
	}
	
?>
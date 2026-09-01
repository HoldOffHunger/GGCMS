<?php

	class EPub extends AbstractBaseFormat {
		public function MimeType() {
			return 'application/epub+zip';
		}
		
			// Display Epub
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
		
			$source_file_location = $this->SetSourceFileLocation();
			$epub_file_location = $this->SetOutputFileLocation();
			
			$epub_input = $this->RunTemplates();
			
			if(!$epub_input) {
				return FALSE;
			}
		
			$old_epub_input = $this->getLastScriptRun();
			
			if($_GET['forceregen'] || !is_file($epub_file_location) || $old_epub_input !== $epub_input) {
				$this->ConvertHTMLToFormat();
				
			#	$file_handle_for_source = fopen($source_file_location, 'w+');
			#	fwrite($file_handle_for_source, $epub_input);
			#	fclose($file_handle_for_source);
			}
			
			
			
			return readfile($this->SetOutputFileLocation());
		}
		
		public function ConvertHTMLToFormat() {
			$epub_input = $this->RunTemplates();
			
			$epub_document =
				'<html>' . "\n\n" .
				'<head>' . "\n\n" .
				'<title>' . "\n\n" .
				
				'</title>' . "\n\n" .
				'</head>' . "\n\n" .
				'<body>' . "\n\n" .
				$epub_input .
				'</body>' . "\n\n" .
				'</html>';
			
			$zip_file = new ZipArchive;
			$zip_file->open($this->SetOutputFileLocation(), ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
			$zip_file->addEmptyDir('EPUB/');
			$zip_file->addEmptyDir('EPUB/css/');
			$zip_file->addEmptyDir('META-INF/');
			$zip_file->addFromString('META-INF/container.xml', $this->SetContainerXML());
			$zip_file->addFromString('EPUB/css/view.css', $this->SetCSSFile());
			$zip_file->addFromString('EPUB/package.opf', $this->SetPackageOPF());
			$zip_file->addFromString('EPUB/' . $this->epub_filename . '.xhtml', $epub_document);
			$zip_file->addFromString('mimetype', $this->MimeType());
			$zip_file->close();
			
			return TRUE;
		}
		
		public function SetContainerXML() {
			return $this->container_xml =
				'<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
				'<container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0">' . "\n" .
				"\t" . '<rootfiles>' . "\n" .
				"\t\t" . '<rootfile full-path="EPUB/package.opf" media-type="application/oebps-package+xml"/>' . "\n" .
				"\t" . '</rootfiles>' . "\n" .
				'</container>' . "\n";
		}
		
		public function SetPackageOPF() {
			$primary_host_record = $this->script->primary_host_record;
			
			$package_opf =
				'<?xml version="1.0" encoding="UTF-8"?>' . "\n\n" .
				'<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="id">' . "\n\n" .
				"\t" . '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n\n" .
				
				"\t\t" . '<dc:title id="title">';
			
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
			
			$package_opf .= $title;
				
			$package_opf .= '</dc:title>' . "\n" .
				"\t\t" . '<dc:creator id="creator">';
			
			if($this->script->record_to_use['textbody']) {
				$textbody_count = count($this->script->record_to_use['textbody']);
				if($textbody_count) {
					$textbody = $this->script->record_to_use['textbody'][0];
					
					if($textbody['Source'])
					{
						$package_opf .= 'From : ' . $textbody['Source'] . '. ';
					}
				}
			}
			
			$package_opf .= ' EPub file created using software made by : ' . $this->script->handler->abstractglobals->site->Creator() . '.';
			
			$package_opf .= '</dc:creator>' . "\n" .
				"\t\t" . '<dc:subject id="subject">';
			
			$subject = $primary_host_record['Subject'];
			
			if($primary_host_record['NewsKeywords']) {
				if($subject) {
					$subject .= ', ';
				}
				
				$subject .= $primary_host_record['NewsKeywords'];
			}
			
			$package_opf .= $subject;
			
			$package_opf .= '</dc:subject>' . "\n\n" .
				
			$package_opf .= "\t\t" . '<dc:description id="description">';
			
			if($this->script->record_to_use['description']) {
				$description_count = count($this->script->record_to_use['description']);
				if($description_count) {
					$description = $this->script->record_to_use['description'][0];
					
					$package_opf .= $description['Description'];
					
					if($description['Source']) {
						$package_opf .= ' (From : ' . $description['Source'] . '.)';
					}
				}
			}
				
			$package_opf .= '</dc:description>' . "\n" .
				"\t\t" . '<dc:publisher id="publisher">';
			
			$package_opf .= $this->script->handler->abstractglobals->site->Publisher();
			
			$package_opf .= '</dc:publisher>' . "\n" .
				"\t\t" . '<dc:contributor id="contributor">';
			
			$package_opf .= $this->script->handler->abstractglobals->site->Contributor();
			
			$package_opf .= '</dc:contributor>' . "\n" .
				"\t\t" . '<dc:date id="date">';
			
			$package_opf .= $this->script->record_to_use['OriginalCreationDate'];
			
			$package_opf .= '</dc:date>' . "\n" .
				"\t\t" . '<dc:type id="text">Text</dc:type>' . "\n" .
				"\t\t" . '<dc:format id="format">TXT / HTML / XHTML / CSS / EPUB</dc:format>' . "\n" .
				"\t\t" . '<dc:identifier id="identifier">';
			
			$package_opf .= $this->domain_object->host;
			$package_opf .= '_';
			$package_opf .= $this->epub_filename;
			
			$package_opf .= '</dc:identifier>' . "\n" .
				"\t\t" . '<dc:source id="source">';
			
			$package_opf .= $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]);
			
			$package_opf .= '/</dc:source>' . "\n" .
				"\t\t" . '<dc:language id="language">en-US</dc:language>' . "\n\n" .
				
				"\t\t" . '<dc:relation id="relation">';
			
			$package_opf .= $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]);
			
			$package_opf .= '</dc:relation>' . "\n"  .
				"\t\t" . '<dc:coverage id="coverage">eternity</dc:coverage>' . "\n" .
				"\t\t" . '<dc:rights id="rights">';
				
			$package_opf .= $this->script->handler->abstractglobals->site->Rights();
			
			$package_opf .= '</dc:rights>' . "\n\n" .
				
				"\t" . '</metadata>' . "\n\n" .
				
				"\t" . '<manifest>' . "\n\n" .
				
				"\t\t" . '<item href="css/view.css" id="css1" media-type="text/css"/>' . "\n" .
				"\t\t" . '<item href="' . $this->epub_filename . '.xhtml" id="document" media-type="application/xhtml+xml"/>' . "\n\n" .
				
				"\t" . '<spine>' . "\n\n" .
				
				"\t\t" . '<itemref idref="document"/>' . "\n\n" .
				
				"\t" . '</spine>' . "\n\n" .
				
				'</package>' . "\n";
				
			return $this->package_opf = $package_opf;
		}
		
		public function SetCSSFile() {
			$url = $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]);
			$url .= '/css/view/display.css';
			$css = file_get_contents($url);
			
			return $this->css = $css;
		}
	}
	
?>
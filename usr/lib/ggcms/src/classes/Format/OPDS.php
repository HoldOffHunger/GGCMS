<?php

	class OPDS extends AbstractBaseFormat {
		public $script;
		public $mimetype;
		public $format_object;
		public $opds_output;
		public $id;
		public $title;
		public $author;
		public $description;
		
		public function MimeType() {
			return 'application/xml';
		}
		
		public function __construct($args) {
			$this->SetArgs($args);
			
			$constructor_args = $this->SetScriptConstructorArgs($args);
			
			$this->Construct_Requires();
			$this->SetMimeTypeAndFormats();
			
			require($this->script_location);
			$this->script = new $this->script_classname($constructor_args);
		}
		
		public function SetMimeTypeAndFormats() {	# TODO: Use this style for the rel-alts in HTML as well
			ggreq('classes/Networking/MIMEType.php');
			ggreq('classes/Format/Base/Formats.php');
			$this->mimetype = new MIMEType(['handler'=>$this->handler]);
			$this->format_object = new Formats(['handler'=>$this->handler]);
			
			return TRUE;
		}
		
			// Display OPDS
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$this->script->DisplayTemplates();
			
			$opds_output = $this->ConvertHTMLToFormat();
			
			return print($opds_output);
		}
		
		public function ConvertHTMLToFormat() {
			$opds_header = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			
			$opds_header .= '<feed xmlns="http://www.w3.org/2005/Atom"' . "\n";
			$opds_header .= '      xmlns:dc="http://purl.org/dc/terms/"' . "\n";
			$opds_header .= '      xmlns:opds="http://opds-spec.org/2010/catalog">' . "\n\n";
			
			$opds_header .= '  <id>' . $this->script->master_record['Code'] . '</id>' . "\n\n";
			
			$opds_header .= '  <title>' . $this->XMLText($this->script->master_record['Title']) . '</title>' . "\n";
			$opds_header .= '  <updated>' . $this->script->master_record['OriginalCreationDate'] . '</updated>' . "\n";
			$opds_header .= '  <author>' . "\n";
			$opds_header .= '    <name>' . $this->XMLText($this->script->handler->abstractglobals->site->Creator()) . '</name>' . "\n";
			$opds_header .= '    <uri>' . $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '</uri>' . "\n";
			$opds_header .= '  </author>' . "\n";
			
			$id = $this->SetID();
			$title = $this->SetTitle();
			$author = $this->SetAuthor();
			$description = $this->SetDescription();
			
			$opds_body = '';
			$opds_body .= '  <entry>' . "\n";
			$opds_body .= '    <title>' . $this->XMLText($title) . '</title>' . "\n";
			$opds_body .= '    <id>' . $id . '</id>' . "\n";
			$opds_body .= '    <updated>' . $this->script->record_to_use['LastModificationDate'] . '</updated>' . "\n";
			$opds_body .= '    <author>' . "\n";
			$opds_body .= '      <name>' . $this->XMLText($author) . '</name>' . "\n";
			$opds_body .= '    </author>' . "\n";
			$opds_body .= '    <dc:language>en</dc:language>' . "\n";
			$opds_body .= '    <dc:issued>' . $this->script->record_to_use['OriginalCreationDate'] . '</dc:issued>' . "\n";
			$opds_body .= '    <category label="' . $this->XMLText($this->script->subject) . '"></category>' . "\n";
			$opds_body .= '    <summary>' . $this->XMLText($description) . '</summary>' . "\n";
			
			$valid_record_fields = [
				'privacypolicy'=>TRUE,
				'termsofservice'=>TRUE,
			];
			
			$record_fields = array_keys($this->script->record_to_use);
			$record_fields_count = count($record_fields);
			
			for($i = 0; $i < $record_fields_count; $i++) {
				$record_field = $record_fields[$i];
				
				if(!empty($valid_record_fields[$record_field])) {
					$opds_body .= '    <' . $record_field . '>' . $this->XMLEscape($this->script->record_to_use[$record_field]) . '</' . $record_field . '>' . "\n";
				}
			}
			
			$base_url = $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]);
			$url_end_piece = preg_replace('/view\.opds$/i', '', $_SERVER['REDIRECT_URL']);
			$base_url .= $url_end_piece;
			
			$opds_body .= '    <link rel="self"' . "\n";
			$opds_body .= '          href="' . $this->XMLEscape($base_url) . 'view.php"' . "\n";
			$opds_body .= '          type="text/html; charset=utf-8">' . "\n";
			$opds_body .= '    </link>' . "\n";
			
			$extension_mimetypes = $this->mimetype->GetMIMETypeCodes();
			$supported_formats = $this->format_object->GetListOfSupportedFormatExtensions();
			
			foreach($supported_formats as $supported_format_name => $supported_format_extension) {
				if($supported_format_name !== 'HTML') {
					$actual_extension_pieces = explode('?', $supported_format_extension);
					$actual_extension = $actual_extension_pieces[0];
					$opds_body .= '    <link rel="http://opds-spec.org/acquisition"' . "\n";
					$opds_body .= '          href="' . $this->XMLEscape($base_url) . 'view.' . $this->XMLEscape($supported_format_extension) . '"' . "\n";
					$opds_body .= '          type="' . $extension_mimetypes[$actual_extension] . '">' . "\n";
					$opds_body .= '    </link>' . "\n";
				}
			}
			
			/*
				An entry with no image child records has a NULL here, not an
				empty array, so count() fatalled on any .opds request for such
				an entry.  RDF.php already guards its equivalents this way; the
				same idiom is wanted in DAISY, EPub, TEX and the two remaining
				unguarded counts in RDF -- see Docs/Triage.md.
			*/

			$images = $this->script->record_to_use['image'] ?? [];
			$image_count = count($images);
			
			for($i = 0; $i < $image_count; $i++) {
				$image = $images[$i];
				$image_directory = !empty($image['FileDirectory']) ? implode('/', str_split($image['FileDirectory'])) . '/' : '';
				
				$image_extension_pieces = explode('.', $image['FileName']);
				$image_extension = $image_extension_pieces[(count($image_extension_pieces) - 1)];
				
				$opds_body .= '    <link rel="http://opds-spec.org/image"' . "\n";
				$opds_body .= '          href="' . $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/image/' . $image_directory . rawurlencode($image['FileName']) . '"' . "\n";
				$opds_body .= '          type="' . $extension_mimetypes[$image_extension] . '" />' . "\n";
				
				$image_icon_extension_pieces = explode('.', $image['IconFileName']);
				$image_icon_extension = $image_icon_extension_pieces[(count($image_icon_extension_pieces) - 1)];
				
				$opds_body .= '    <link rel="http://opds-spec.org/image/thumbnail"' . "\n";
				$opds_body .= '          href="' . $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/image/' . $image_directory . rawurlencode($image['IconFileName']) . '"' . "\n";
				$opds_body .= '          type="' . $extension_mimetypes[$image_icon_extension] . '" />' . "\n";
			}
			
			$opds_body .= '  </entry>' . "\n";
			
			$opds_footer = '</feed>' . "\n";
			
			$opds_document =
				$opds_header .
				$opds_body .
				$opds_footer
			;
			
			return $this->opds_output = $opds_document;
		}
		
		public function SetID() {
			$id = $this->domain_object->host;
			$id .= '_';
			$id .= $this->opds_filename;
			
			return $this->id = $id;
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
			if(!empty($this->script->record_to_use['textbody'])) {
				$textbody_count = count($this->script->record_to_use['textbody']);
				if($textbody_count) {
					$textbody = $this->script->record_to_use['textbody'][0];
					
					if(!empty($textbody['Source'])) {
						$author_text .= 'From : ' . $textbody['Source'] . '.';
					}
				}
			}
			
			return $this->author = $author_text;
		}
		
		public function SetDescription() {
			$description_text = '';
			
			if(!empty($this->script->record_to_use['description'])) {
				$description_count = count($this->script->record_to_use['description']);
				if($description_count) {
					$description = $this->script->record_to_use['description'][0];
					$description_text .= $description['Description'] ?? '';
				}
			}
			
			return $this->description = $description_text;
		}
	}
	
?>
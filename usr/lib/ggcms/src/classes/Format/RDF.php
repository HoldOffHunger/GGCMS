<?php

	class RDF extends AbstractBaseFormat {
		public function MimeType() {
			return 'application/rdf+xml';
		}
		
			// Display RDF
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$this->script->DisplayTemplates();
			
			$rdf_output = $this->ConvertHTMLToFormat();
			
			return print($rdf_output);
		}
		
		public function ConvertHTMLToFormat() {
			$rdf_header = '';
			
			$rdf_header .= '<?xml version="1.0"?>' . "\n\n";
			
			$base_url = $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]);
			$url_end_piece = preg_replace('/view\.rdf$/i', '', $_SERVER['REDIRECT_URL']);
			$base_url .= $url_end_piece;
			
			$rdf_header .= '<rdf:RDF' . "\n";
			$rdf_header .= 'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"' . "\n";
			foreach(['tag', 'image', 'description', 'quote', 'text', 'event', 'link'] as $association) {
				$rdf_header .= 'xmlns:entry_' . $association . '="' . $this->XMLEscape($base_url . 'view.php#' . $association . ':') . '"' . "\n";
			}
			$rdf_header .= 'xmlns:entry="' . $this->XMLEscape($base_url) . 'view.php">' . "\n\n";
			
			$rdf_body = '';
			
			$rdf_body .= '<rdf:Description' . "\n";
			$rdf_body .= 'rdf:about="' . $this->XMLEscape($base_url) . 'view.php">' . "\n\n";
			
			$rdf_body .= '  <entry:id>' . $this->XMLText($this->script->record_to_use['id']) . '</entry:id>' . "\n";
			$rdf_body .= '  <entry:Code>' . $this->XMLText($this->script->record_to_use['Code']) . '</entry:Code>' . "\n";
			$rdf_body .= '  <entry:Title>' . $this->XMLText($this->script->record_to_use['Title']) . '</entry:Title>' . "\n";
			$rdf_body .= '  <entry:Subtitle>' . $this->XMLText($this->script->record_to_use['Subtitle']) . '</entry:Subtitle>' . "\n";
			$rdf_body .= '  <entry:ListTitle>' . $this->XMLText($this->script->record_to_use['ListTitle']) . '</entry:ListTitle>' . "\n";
			$rdf_body .= '  <entry:OriginalCreationDate>' . $this->XMLText($this->script->record_to_use['OriginalCreationDate']) . '</entry:OriginalCreationDate>' . "\n";
			$rdf_body .= '  <entry:LastModificationDate>' . $this->XMLText($this->script->record_to_use['LastModificationDate']) . '</entry:LastModificationDate>' . "\n";
			
			$rdf_body .= "\n";
			
			$tag_count = count($this->script->record_to_use['tag'] ?? []);
			
			if($tag_count) {
				$tags = $this->script->record_to_use['tag'];
				
				$rdf_body .= '  <entry:tag>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $tag_count; $i++) {
					$tag = $tags[$i];
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_tag:id>' . $this->XMLText($tag['id']) . '</entry_tag:id>' . "\n";
					$rdf_body .= '        <entry_tag:Tag>' . $this->XMLText($tag['Tag']) . '</entry_tag:Tag>' . "\n";
					$rdf_body .= '        <entry_tag:Language>' . $this->XMLText($tag['Language']) . '</entry_tag:Language>' . "\n";
					$rdf_body .= '        <entry_tag:OriginalCreationDate>' . $this->XMLText($tag['OriginalCreationDate']) . '</entry_tag:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_tag:LastModificationDate>' . $this->XMLText($tag['LastModificationDate']) . '</entry_tag:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:tag>' . "\n\n";
			}
			
			$image_count = count($this->script->record_to_use['image'] ?? []);
			
			if($image_count) {
				$images = $this->script->record_to_use['image'];
				
				$rdf_body .= '  <entry:image>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $image_count; $i++) {
					$image = $images[$i];
					$image_directory = (string) ($image['FileDirectory'] ?? '');
					$image_directory = $image_directory !== '' ? implode('/', str_split($image_directory)) . '/' : '';
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_image:id>' . $this->XMLText($image['id']) . '</entry_image:id>' . "\n";
					$rdf_body .= '        <entry_image:FileURL>' . $this->XMLEscape($this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/image/' . $image_directory . rawurlencode($image['FileName'])) . '</entry_image:FileURL>' . "\n";
					$rdf_body .= '        <entry_image:IconFileURL>' . $this->XMLEscape($this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/image/' . $image_directory . rawurlencode($image['IconFileName'])) . '</entry_image:IconFileURL>' . "\n";
					$rdf_body .= '        <entry_image:PixelWidth>' . $this->XMLText($image['PixelWidth']) . '</entry_image:PixelWidth>' . "\n";
					$rdf_body .= '        <entry_image:PixelHeight>' . $this->XMLText($image['PixelHeight']) . '</entry_image:PixelHeight>' . "\n";
					$rdf_body .= '        <entry_image:IconPixelWidth>' . $this->XMLText($image['IconPixelWidth']) . '</entry_image:IconPixelWidth>' . "\n";
					$rdf_body .= '        <entry_image:IconPixelHeight>' . $this->XMLText($image['IconPixelHeight']) . '</entry_image:IconPixelHeight>' . "\n";
					$rdf_body .= '        <entry_image:OriginalCreationDate>' . $this->XMLText($image['OriginalCreationDate']) . '</entry_image:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_image:LastModificationDate>' . $this->XMLText($image['LastModificationDate']) . '</entry_image:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:image>' . "\n\n";
			}
			
			$description_count = count($this->script->record_to_use['description'] ?? []);
			
			if($description_count) {
				$descriptions = $this->script->record_to_use['description'];
				
				$rdf_body .= '  <entry:description>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $description_count; $i++) {
					$description = $descriptions[$i];
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_description:id>' . $this->XMLText($description['id']) . '</entry_description:id>' . "\n";
					$rdf_body .= '        <entry_description:Description>' . $this->XMLText($description['Description']) . '</entry_description:Description>' . "\n";
					$rdf_body .= '        <entry_description:Source>' . $this->XMLText($description['Source']) . '</entry_description:Source>' . "\n";
					$rdf_body .= '        <entry_description:Language>' . $this->XMLText($description['Language']) . '</entry_description:Language>' . "\n";
					$rdf_body .= '        <entry_description:OriginalCreationDate>' . $this->XMLText($description['OriginalCreationDate']) . '</entry_description:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_description:LastModificationDate>' . $this->XMLText($description['LastModificationDate']) . '</entry_description:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:description>' . "\n\n";
			}
			
			$quote_count = count($this->script->record_to_use['quote'] ?? []);
			
			if($quote_count) {
				$quotes = $this->script->record_to_use['quote'];
				
				$rdf_body .= '  <entry:quote>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $quote_count; $i++) {
					$quote = $quotes[$i];
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_quote:id>' . $this->XMLText($quote['id']) . '</entry_quote:id>' . "\n";
					$rdf_body .= '        <entry_quote:Quote>' . $this->XMLText($quote['Quote']) . '</entry_quote:Quote>' . "\n";
					$rdf_body .= '        <entry_quote:Source>' . $this->XMLText($quote['Source']) . '</entry_quote:Source>' . "\n";
					$rdf_body .= '        <entry_quote:Language>' . $this->XMLText($quote['Language']) . '</entry_quote:Language>' . "\n";
					$rdf_body .= '        <entry_quote:OriginalCreationDate>' . $this->XMLText($quote['OriginalCreationDate']) . '</entry_quote:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_quote:LastModificationDate>' . $this->XMLText($quote['LastModificationDate']) . '</entry_quote:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:quote>' . "\n\n";
			}
			
			$textbody_count = count($this->script->record_to_use['textbody'] ?? []);
			
			if($textbody_count) {
				$textbodies = $this->script->record_to_use['textbody'];
				
				$rdf_body .= '  <entry:text>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $textbody_count; $i++) {
					$textbody = $textbodies[$i];
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_text:id>' . $this->XMLText($textbody['id']) . '</entry_text:id>' . "\n";
					$rdf_body .= '        <entry_text:Text>' . $this->XMLText($textbody['Text']) . '</entry_text:Text>' . "\n";
					$rdf_body .= '        <entry_text:Source>' . $this->XMLText($textbody['Source']) . '</entry_text:Source>' . "\n";
					$rdf_body .= '        <entry_text:Language>' . $this->XMLText($textbody['Language']) . '</entry_text:Language>' . "\n";
					$rdf_body .= '        <entry_text:WordCount>' . $this->XMLText($textbody['WordCount']) . '</entry_text:WordCount>' . "\n";
					$rdf_body .= '        <entry_text:CharacterCount>' . $this->XMLText($textbody['CharacterCount']) . '</entry_text:CharacterCount>' . "\n";
					$rdf_body .= '        <entry_text:OriginalCreationDate>' . $this->XMLText($textbody['OriginalCreationDate']) . '</entry_text:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_text:LastModificationDate>' . $this->XMLText($textbody['LastModificationDate']) . '</entry_text:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:text>' . "\n\n";
			}
			
			$eventdate_count = count($this->script->record_to_use['eventdate'] ?? []);
			
			if($eventdate_count) {
				$eventdates = $this->script->record_to_use['eventdate'];
				
				$rdf_body .= '  <entry:event>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $eventdate_count; $i++) {
					$eventdate = $eventdates[$i];
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_event:id>' . $this->XMLText($eventdate['id']) . '</entry_event:id>' . "\n";
					$rdf_body .= '        <entry_event:EventDateTime>' . $this->XMLText($eventdate['EventDateTime']) . '</entry_event:EventDateTime>' . "\n";
					$rdf_body .= '        <entry_event:Title>' . $this->XMLText($eventdate['Title']) . '</entry_event:Title>' . "\n";
					$rdf_body .= '        <entry_event:Description>' . $this->XMLText($eventdate['Description']) . '</entry_event:Description>' . "\n";
					$rdf_body .= '        <entry_event:Language>' . $this->XMLText($eventdate['Language']) . '</entry_event:Language>' . "\n";
					$rdf_body .= '        <entry_event:OriginalCreationDate>' . $this->XMLText($eventdate['OriginalCreationDate']) . '</entry_event:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_event:LastModificationDate>' . $this->XMLText($eventdate['LastModificationDate']) . '</entry_event:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:event>' . "\n\n";
			}
			
			$link_count = count($this->script->record_to_use['link'] ?? []);
			
			if($link_count) {
				$links = $this->script->record_to_use['link'];
				
				$rdf_body .= '  <entry:link>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $link_count; $i++) {
					$link = $links[$i];
					
					$rdf_body .= '      <rdf:li rdf:parseType="Resource">' . "\n";
					$rdf_body .= '        <entry_link:id>' . $this->XMLText($link['id']) . '</entry_link:id>' . "\n";
					$rdf_body .= '        <entry_link:Title>' . $this->XMLText($link['Title']) . '</entry_link:Title>' . "\n";
					$rdf_body .= '        <entry_link:URL>' . $this->XMLEscape($link['URL']) . '</entry_link:URL>' . "\n";
					$rdf_body .= '        <entry_link:Language>' . $this->XMLText($link['Language']) . '</entry_link:Language>' . "\n";
					$rdf_body .= '        <entry_link:OriginalCreationDate>' . $this->XMLText($link['OriginalCreationDate']) . '</entry_link:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry_link:LastModificationDate>' . $this->XMLText($link['LastModificationDate']) . '</entry_link:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:link>' . "\n\n";
			}
			
			$valid_record_fields = [
				'privacypolicy'=>TRUE,
				'termsofservice'=>TRUE,
				'userdata'=>TRUE,
				'comments'=>TRUE,
				'likesdislikes'=>TRUE,
			];
			
			$record_fields = array_keys($this->script->record_to_use);
			$record_fields_count = count($record_fields);
			
			for($i = 0; $i < $record_fields_count; $i++) {
				$record_field = $record_fields[$i];
				
				if(!empty($valid_record_fields[$record_field])) {
					$rdf_body .= '  <entry:' . $record_field . '>';
					if(is_array($this->script->record_to_use[$record_field])) {
						$rdf_body .= $this->ArrayToRDF(['values'=>$this->script->record_to_use[$record_field]]);
					} else {
						$rdf_body .= $this->XMLEscape($this->script->record_to_use[$record_field]);
					}
					$rdf_body .= '</entry:' . $record_field . '>' . "\n\n";
				}
			}
			
			$rdf_body .= '</rdf:Description>' . "\n\n";
			
			$rdf_footer = '</rdf:RDF>' . "\n";
			
			$rdf_document =
				$rdf_header .
				$rdf_body .
				$rdf_footer
			;
			
			return $this->rdf_output = $rdf_document;
		}
		
		public function ArrayToRDF($args) {
			$output = '<rdf:Bag>';
			foreach($args['values'] as $key => $value) {
				$type = gettype($value);
				if(!is_array($value) && !is_scalar($value) && $value !== NULL) {
					throw new RuntimeException('Unsupported RDF array value.');
				}
				$output .= '<rdf:li rdf:parseType="Resource"><entry:key>';
				$output .= $this->XMLEscape($key);
				$output .= '</entry:key><entry:type>' . $type . '</entry:type><entry:value>';
				if(is_array($value)) {
					$output .= $this->ArrayToRDF(['values'=>$value]);
				} else {
					$text = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
					$output .= $this->XMLEscape($text);
				}
				$output .= '</entry:value></rdf:li>';
			}
			return $output . '</rdf:Bag>';
		}
		
		public function SetID() {
			$id = $this->domain_object->host;
			$id .= '_';
			$id .= $this->rdf_filename;
			
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
			if($this->script->record_to_use['textbody']) {
				$textbody_count = count($this->script->record_to_use['textbody']);
				if($textbody_count) {
					$textbody = $this->script->record_to_use['textbody'][0];
					
					if($textbody['Source']) {
						$author_text .= 'From : ' . $textbody['Source'] . '.';
					}
				}
			}
			
			return $this->author = $author_text;
		}
		
		public function SetDescription() {
			$description_text = '';
			
			if($this->script->record_to_use['description']) {
				$description_count = count($this->script->record_to_use['description']);
				if($description_count) {
					$description = $this->script->record_to_use['description'][0];
					$description_text .= $description['Description'];
				}
			}
			
			return $this->description = $description_text;
		}
	}
	
?>
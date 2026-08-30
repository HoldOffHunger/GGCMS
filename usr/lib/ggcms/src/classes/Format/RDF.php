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
			$rdf_header .= 'xmlns:entry="' . $base_url . 'view.php">' . "\n\n";
			
			$rdf_body = '';
			
			$rdf_body .= '<rdf:Description' . "\n";
			$rdf_body .= 'rdf:about="' . $base_url . 'view.php">' . "\n\n";
			
			$rdf_body .= '  <entry:id>' . $this->script->record_to_use['id'] . '</entry:id>' . "\n";
			$rdf_body .= '  <entry:Code>' . $this->script->record_to_use['Code'] . '</entry:Code>' . "\n";
			$rdf_body .= '  <entry:Title>' . $this->script->record_to_use['Title'] . '</entry:Title>' . "\n";
			$rdf_body .= '  <entry:Subtitle>' . $this->script->record_to_use['Subtitle'] . '</entry:Subtitle>' . "\n";
			$rdf_body .= '  <entry:ListTitle>' . $this->script->record_to_use['ListTitle'] . '</entry:ListTitle>' . "\n";
			$rdf_body .= '  <entry:OriginalCreationDate>' . $this->script->record_to_use['OriginalCreationDate'] . '</entry:OriginalCreationDate>' . "\n";
			$rdf_body .= '  <entry:LastModificationDate>' . $this->script->record_to_use['LastModificationDate'] . '</entry:LastModificationDate>' . "\n";
			
			$rdf_body .= "\n";
			
			$tag_count = $this->script->record_to_use['tag'] ? count($this->script->record_to_use['tag']) : 0;
			
			if($tag_count) {
				$tags = $this->script->record_to_use['tag'];
				
				$rdf_body .= '  <entry:tag>' . "\n";
				$rdf_body .= '    <rdf:Bag>' . "\n";
				
				for($i = 0; $i < $tag_count; $i++) {
					$tag = $tags[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:tag:id>' . $tag['id'] . '</entry:tag:id>' . "\n";
					$rdf_body .= '        <entry:tag:Tag>' . $tag['Tag'] . '</entry:tag:Tag>' . "\n";
					$rdf_body .= '        <entry:tag:Language>' . $tag['Language'] . '</entry:tag:Language>' . "\n";
					$rdf_body .= '        <entry:tag:OriginalCreationDate>' . $tag['OriginalCreationDate'] . '</entry:tag:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:tag:LastModificationDate>' . $tag['LastModificationDate'] . '</entry:tag:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:Bag>' . "\n";
				$rdf_body .= '  </entry:tag>' . "\n\n";
			}
			
			$image_count = $this->script->record_to_use['image'] ? count($this->script->record_to_use['image']) : 0;
			
			if($image_count) {
				$images = $this->script->record_to_use['image'];
				
				$rdf_body .= '  <entry:image>' . "\n";
				$rdf_body .= '    <rdf:bag>' . "\n";
				
				for($i = 0; $i < $image_count; $i++) {
					$image = $images[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:image:id>' . $image['id'] . '</entry:image:id>' . "\n";
					$rdf_body .= '        <entry:image:FileURL>' . $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/image/' . $image['FileName'] . '</entry:image:FileURL>' . "\n";
					$rdf_body .= '        <entry:image:IconFileURL>' . $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/image/' . $image['IconFileName'] . '</entry:image:IconFileURL>' . "\n";
					$rdf_body .= '        <entry:image:PixelWidth>' . $image['PixelWidth'] . '</entry:image:PixelWidth>' . "\n";
					$rdf_body .= '        <entry:image:PixelHeight>' . $image['PixelHeight'] . '</entry:image:PixelHeight>' . "\n";
					$rdf_body .= '        <entry:image:IconPixelWidth>' . $image['IconPixelWidth'] . '</entry:image:IconPixelWidth>' . "\n";
					$rdf_body .= '        <entry:image:IconPixelHeight>' . $image['IconPixelHeight'] . '</entry:image:IconPixelHeight>' . "\n";
					$rdf_body .= '        <entry:image:OriginalCreationDate>' . $image['OriginalCreationDate'] . '</entry:image:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:image:LastModificationDate>' . $image['LastModificationDate'] . '</entry:image:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:bag>' . "\n";
				$rdf_body .= '  </entry:image>' . "\n\n";
			}
			
			$description_count = $this->script->record_to_use['description'] ? count($this->script->record_to_use['description']) : 0;
			
			if($description_count) {
				$descriptions = $this->script->record_to_use['description'];
				
				$rdf_body .= '  <entry:description>' . "\n";
				$rdf_body .= '    <rdf:bag>' . "\n";
				
				for($i = 0; $i < $description_count; $i++) {
					$description = $descriptions[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:description:id>' . $description['id'] . '</entry:description:id>' . "\n";
					$rdf_body .= '        <entry:description:Description>' . $description['Description'] . '</entry:description:Description>' . "\n";
					$rdf_body .= '        <entry:description:Source>' . $description['Source'] . '</entry:description:Source>' . "\n";
					$rdf_body .= '        <entry:description:Language>' . $description['Language'] . '</entry:description:Language>' . "\n";
					$rdf_body .= '        <entry:description:OriginalCreationDate>' . $description['OriginalCreationDate'] . '</entry:description:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:description:LastModificationDate>' . $description['LastModificationDate'] . '</entry:description:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:bag>' . "\n";
				$rdf_body .= '  </entry:description>' . "\n\n";
			}
			
			$quote_count = $this->script->record_to_use['quote'] ? count($this->script->record_to_use['quote']) : 0;
			
			if($quote_count) {
				$quotes = $this->script->record_to_use['quote'];
				
				$rdf_body .= '  <entry:quote>' . "\n";
				$rdf_body .= '    <rdf:bag>' . "\n";
				
				for($i = 0; $i < $quote_count; $i++) {
					$quote = $quotes[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:quote:id>' . $quote['id'] . '</entry:quote:id>' . "\n";
					$rdf_body .= '        <entry:quote:Quote>' . $quote['Description'] . '</entry:quote:Quote>' . "\n";
					$rdf_body .= '        <entry:quote:Source>' . $quote['Source'] . '</entry:quote:Source>' . "\n";
					$rdf_body .= '        <entry:quote:Language>' . $quote['Language'] . '</entry:quote:Language>' . "\n";
					$rdf_body .= '        <entry:quote:OriginalCreationDate>' . $quote['OriginalCreationDate'] . '</entry:quote:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:quote:LastModificationDate>' . $quote['LastModificationDate'] . '</entry:quote:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:bag>' . "\n";
				$rdf_body .= '  </entry:quote>' . "\n\n";
			}
			
			$textbody_count = $this->script->record_to_use['textbody'] ? count($this->script->record_to_use['textbody']) : 0;
			
			if($textbody_count) {
				$textbodies = $this->script->record_to_use['textbody'];
				
				$rdf_body .= '  <entry:text>' . "\n";
				$rdf_body .= '    <rdf:bag>' . "\n";
				
				for($i = 0; $i < $textbody_count; $i++) {
					$textbody = $textbodies[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:text:id>' . $textbody['id'] . '</entry:text:id>' . "\n";
					$rdf_body .= '        <entry:text:Text>' . $textbody['Text'] . '</entry:text:Text>' . "\n";
					$rdf_body .= '        <entry:text:Source>' . $textbody['Source'] . '</entry:text:Source>' . "\n";
					$rdf_body .= '        <entry:text:Language>' . $textbody['Language'] . '</entry:text:Language>' . "\n";
					$rdf_body .= '        <entry:text:WordCount>' . $textbody['WordCount'] . '</entry:text:WordCount>' . "\n";
					$rdf_body .= '        <entry:text:CharacterCount>' . $textbody['CharacterCount'] . '</entry:text:CharacterCount>' . "\n";
					$rdf_body .= '        <entry:text:OriginalCreationDate>' . $textbody['OriginalCreationDate'] . '</entry:text:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:text:LastModificationDate>' . $textbody['LastModificationDate'] . '</entry:text:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:bag>' . "\n";
				$rdf_body .= '  </entry:text>' . "\n\n";
			}
			
			$eventdate_count = $this->script->record_to_use['eventdate'] ? count($this->script->record_to_use['eventdate']) : 0;
			
			if($eventdate_count) {
				$eventdates = $this->script->record_to_use['eventdate'];
				
				$rdf_body .= '  <entry:event>' . "\n";
				$rdf_body .= '    <rdf:bag>' . "\n";
				
				for($i = 0; $i < $eventdate_count; $i++) {
					$eventdate = $eventdates[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:event:id>' . $eventdate['id'] . '</entry:event:id>' . "\n";
					$rdf_body .= '        <entry:event:EventDateTime>' . $eventdate['EventDateTime'] . '</entry:event:EventDateTime>' . "\n";
					$rdf_body .= '        <entry:event:Title>' . $eventdate['Title'] . '</entry:event:Title>' . "\n";
					$rdf_body .= '        <entry:event:Description>' . $eventdate['Description'] . '</entry:event:Description>' . "\n";
					$rdf_body .= '        <entry:event:Language>' . $eventdate['Language'] . '</entry:event:Language>' . "\n";
					$rdf_body .= '        <entry:event:OriginalCreationDate>' . $eventdate['OriginalCreationDate'] . '</entry:event:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:event:LastModificationDate>' . $eventdate['LastModificationDate'] . '</entry:event:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:bag>' . "\n";
				$rdf_body .= '  </entry:event>' . "\n\n";
			}
			
			$link_count = $this->script->record_to_use['link'] ? count($this->script->record_to_use['link']) : 0;
			
			if($link_count) {
				$links = $this->script->record_to_use['link'];
				
				$rdf_body .= '  <entry:link>' . "\n";
				$rdf_body .= '    <rdf:bag>' . "\n";
				
				for($i = 0; $i < $link_count; $i++) {
					$link = $links[$i];
					
					$rdf_body .= '      <rdf:li>' . "\n";
					$rdf_body .= '        <entry:link:id>' . $link['id'] . '</entry:link:id>' . "\n";
					$rdf_body .= '        <entry:link:Title>' . $link['Title'] . '</entry:link:Title>' . "\n";
					$rdf_body .= '        <entry:link:URL>' . $link['URL'] . '</entry:link:URL>' . "\n";
					$rdf_body .= '        <entry:link:Language>' . $link['URL'] . '</entry:link:Language>' . "\n";
					$rdf_body .= '        <entry:link:OriginalCreationDate>' . $link['OriginalCreationDate'] . '</entry:link:OriginalCreationDate>' . "\n";
					$rdf_body .= '        <entry:link:LastModificationDate>' . $link['LastModificationDate'] . '</entry:link:LastModificationDate>' . "\n";
					$rdf_body .= '      </rdf:li>' . "\n";
				}
				
				$rdf_body .= '    </rdf:bag>' . "\n";
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
				
				if($valid_record_fields[$record_field]) {
					$rdf_body .= '  <entry:' . $record_field . '>' . "\n";
					$rdf_body .= '  <entry:' . $record_field . ':value>';
					
					$rdf_body .= $this->script->record_to_use[$record_field];
					
					$rdf_body .= '<entry:' . $record_field . ':/value>' . "\n";
					$rdf_body .= '  </entry' . $record_field . '>' . "\n\n";
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
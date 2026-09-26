<?php

	class XML extends AbstractBaseFormat {
			// XML MimeType
			// -----------------------------------------------
		
		public function MimeType() {
			return 'application/xml';
		}
		
		public function StartSyntax() {
			return "<?xml version='1.0' encoding='UTF-8'?>";
		}
		
			// Display XML
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$this->StartDocument();
			
			print($this->ConvertHTMLToFormat());
			
			$this->EndDocument();
			
			return TRUE;
		}
		
		public function ConvertHTMLToFormat() {
			$content = $this->RunTemplates();
			
			if($content) {
				$xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
				
				$this->array_to_xml($content, $xml_data);
				$xml = $xml_data->asXML();
				
				$xml = str_replace('<?xml version="1.0"?>', '', $xml);
				
				return $xml;
			}
			
			return '';
		}
		
		public function array_to_xml($data, &$xml_data) {
		    foreach($data as $key => $value) {
		        if(is_numeric($key)){
		        	$key = 'item' . $key; //dealing with <0/>..<n/> issues
		        }
		        if(is_array($value)) {
				$subnode = $xml_data->addChild($key);
				$this->array_to_xml($value, $subnode);
		        } else {
				$xml_data->addChild("$key", htmlspecialchars("$value", ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, $this->handler->cleanser->utf8_characters->SystemCharSet()));
		        }
		     }
		}
	}

?>
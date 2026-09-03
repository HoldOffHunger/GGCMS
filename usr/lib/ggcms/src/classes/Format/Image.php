<?php

	class Image extends AbstractBaseFormat {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		public function HTMLEntities() {
			return FALSE;
		}
		
			// Display Image
			// -----------------------------------------------
		
		public function Display() {
			#return false;
		#die('huh' . $this->handler->domain->primary_domain_lowercased);
				#if($this->handler->domain->primary_domain_lowercased == 'wordweight.com') {
				#	die('soy!');
				#}
		#	$this->SetFileNameDisplay();
		#	$this->HandleHTTPHeaders();
			
		#	$source_file_location = $this->SetSourceFileLocation();
		#	$pdf_file_location = $this->SetOutputFileLocation();
			
		#	print("BT: Source????" . $source_file_location . "|");
			
			$extension = strtolower(pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION));
			
			if(strlen($extension) === 0) {
				return FALSE;
			}
			
			ggreq('classes/Networking/MIMEType.php');
			
			$mimetype = new MIMEType(['handler'=>$this->handler]);
			$mimetypes = $mimetype->GetMIMETypeCodes();
			
		#	print_r($mimetypes);
			
			$chosen_mimetype = $mimetypes[$extension];
			
			if(strlen($chosen_mimetype) === 0) {
				return FALSE;
			}
			
		#	print($chosen_mimetype);
			
			$image_request = $_SERVER['REQUEST_URI'];
			#print($image_request);
			
			$image_location = GGCMS_DATA_DIR . $this->handler->domain->primary_domain_lowercased . '/www' . urldecode($image_request);
			
		#	print("LOC!!!" . $image_location . "|");
			
			if(is_file($image_location)) {
				$image_dir_pieces = explode('/', $image_request);
				$image_filename = array_pop($image_dir_pieces);
			#	print($image_filename);
			#	print("HECKYES!");
				
				header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
				header('Content-Disposition:inline;filename=' . $image_filename);
				header('Content-Type: ' . $chosen_mimetype);
				
				return readfile($image_location);
			}
			
			
			return FALSE;
		#	return readfile($pdf_file_location);
		}
		
		public function Construct_Requires() {
			return TRUE;
		}
	}
	
?>
<?php

	class Image extends AbstractBaseFormat {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
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
			
			$image = $this->ImageRequest(['requesturi'=>$_SERVER['REQUEST_URI']]);
			
			if(!$image) {
				return FALSE;
			}
			
			header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
			header('Content-Disposition: inline; filename="' . addcslashes($image['filename'], '"\\') . '"');
			header('Content-Type: ' . $image['mimetype']);
			header('X-Content-Type-Options: nosniff');
			
			return readfile($image['location']);
		}
			
			// ImageRequest()
			// Tests: ImageTest::testImageRequest()
			// Test file: tests/src/classes/Format/ImageTest.php
			/*
				Which file a request under /image/ names, and what it is served
				as -- or FALSE, when the request ends in the image 404.
				
				It read the extension from the whole REQUEST_URI, so photo.jpg?x
				was "jpg?x" and came back an empty 200.  It served whatever the
				MIME table knew, which is text/html for .html and .php and
				image/svg+xml for .svg: an upload named that became a page, or a
				script, on the site's own domain.  And the table had never heard
				of .webp or .jfif, so the 410 of those on the sites on 27
				September 2026 all came back empty.  Only the path is read now,
				and only an image type on ServableImageTypes() is served.
			*/
		
		public function ImageRequest($args) {
			$path = (string)parse_url((string)$args['requesturi'], PHP_URL_PATH);
			$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
			
			$servable = $this->ServableImageTypes();
			
			if(!isset($servable[$extension])) {
				return FALSE;
			}
			
			$location = GGCMS_DATA_DIR . $this->handler->domain->primary_domain_lowercased . '/www' . urldecode($path);
			
			if(!is_file($location)) {
				return FALSE;
			}
			
			return [
				'location'=>$location,
				'mimetype'=>$servable[$extension],
				'filename'=>basename(urldecode($path)),
			];
		}
			
			/*
				Raster images and nothing that can carry a script.  Not SVG:
				it is XML, and can.  Every SVG on the sites is a top-level icon,
				served by HandlerFiles::handleSrvLocalFiles(), not an upload.
			*/
		
		public function ServableImageTypes() {
			return [
				'apng'=>'image/apng',
				'avif'=>'image/avif',
				'bmp'=>'image/bmp',
				'cur'=>'image/x-icon',
				'gif'=>'image/gif',
				'ico'=>'image/vnd.microsoft.icon',
				'jfif'=>'image/jpeg',
				'jpe'=>'image/jpeg',
				'jpeg'=>'image/jpeg',
				'jpg'=>'image/jpeg',
				'pjp'=>'image/jpeg',
				'pjpeg'=>'image/jpeg',
				'png'=>'image/png',
				'tif'=>'image/tiff',
				'tiff'=>'image/tiff',
				'webp'=>'image/webp',
			];
		}
		
		public function Construct_Requires() {
			return TRUE;
		}
	}
	
?>
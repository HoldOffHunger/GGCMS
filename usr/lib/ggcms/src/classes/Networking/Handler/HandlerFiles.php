<?php

	/*
		Handler's file stage: the requests answered from disk rather than
		rendered -- images under /image/, and the files beside them, favicons,
		manifests, verification pages.  Moved out of Handler on 28 September
		2026.  Logic only: every property it reads or sets is still Handler's,
		reached through $this->handler.
	*/

	class HandlerFiles {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function handleSrvLocalFiles() {
			$file = $this->SrvLocalFile(['requesturi'=>$_SERVER['REQUEST_URI']]);
			
			if(!$file) {
				return FALSE;
			}
			
			foreach($file['headers'] as $header) {
				header($header);
			}
			
			return (bool)readfile($file['location']);
		}
			
			// SrvLocalFile()
			// Tests: HandlerFilesTest::testSrvLocalFile()
			// Test file: tests/src/classes/Networking/Handler/HandlerFilesTest.php
			/*
				The files a site keeps beside its images -- favicons, manifests,
				search engines' verification pages, the word-game demos -- and
				the headers to send with each.  They went out through
				print(file_get_contents()) with no Content-Type, so PHP called
				everything text/html, and the name came from the whole
				REQUEST_URI, query string and all.
				
				Anything under image/ is Image::ImageRequest()'s, which serves
				only images; never .php, whose source would be printed; SVG
				sandboxed, since it is XML and can carry a script; nosniff on
				everything.
			*/
		
		public function SrvLocalFile($args) {
			$path = ltrim((string)parse_url((string)$args['requesturi'], PHP_URL_PATH), '/');
			
			if($path === '' || str_starts_with(strtolower($path), 'image/')) {
				return FALSE;
			}
			
			$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
			
			if(in_array($extension, ['php', 'phtml', 'phar', 'inc'], TRUE)) {
				return FALSE;
			}
			
			if(!data_isfile($path, $this->handler)) {
				return FALSE;
			}
			
			if(!class_exists('MIMEType')) {
				ggreq('classes/Networking/MIMEType.php');		# plain require, so once
			}
			$mimetypes = (new MIMEType(['handler'=>$this->handler]))->GetMIMETypeCodes();
			
			$mimetype = $mimetypes[$extension] ?? 'application/octet-stream';
			
			$headers = [
				'Content-Type: ' . $mimetype,
				'X-Content-Type-Options: nosniff',
			];
			
			if($mimetype === 'image/svg+xml') {
				$headers[] = 'Content-Security-Policy: sandbox';
			}
			
			return [
				'location'=>GGCMS_DATA_DIR . $this->handler->domain->primary_domain_lowercased . '/www/' . $path,
				'mimetype'=>$mimetype,
				'headers'=>$headers,
			];
		}
		
		public function handle404Image() {
			#return FALSE;	 // hrm, is this a img src=??? problem?
			$url_pieces = explode('/', $_SERVER['REQUEST_URI']);
			
			
			
			if(count($url_pieces) > 2) {
				$first_piece = $url_pieces[1];
				
				if($first_piece === 'image') {
	#				ggreq();
					$this->handler->script_format = 'Image';
					
					$this->handler->content_handler->HandleRequest_Content_Format_GetFormatObject();
					
					$this->handler->script = new $this->handler->script_format(['handler'=>$this->handler]);
						
						/*
							What Display() answers.  This returned TRUE whether or
							not an image was sent, so a missing or refused one was
							an empty 200 and never reached the image 404 below.
						*/
					
					return (bool)$this->handler->script->Display();
				}
			}
			return FALSE;
		}
		
		public function isScriptImage() {
			$extension = strtolower(pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION));
			$image_extension_hash = $this->imageFileExtensionsHash();
			
			if($image_extension_hash[$extension]) {
				return TRUE;	# we never want to redirect image 404's the way to redirect entry 404's
			}
			
			return FALSE;
		}
		
		public function imageFileExtensionsHash() {
			$image_file_extensions = $this->imageFileExtensions();
			$image_file_extensions_hash = [];
			
			foreach($image_file_extensions as $image_file_extension) {
				$image_file_extensions_hash[$image_file_extension] = TRUE;
			}
			
			return $image_file_extensions_hash;
		}
		
		public function imageFileExtensions() {
			return [
				'apng',
				'avif',
				'bmp',
				'cur',
				'gif',
				'ico',
				'jfif',
				'jpeg',
				'jpg',
				'pjp',
				'pjpeg',
				'png',
				'svg',
				'tif',
				'tiff',
				'webp',
			];
		}
	}

?>
<?php

	trait SimpleImages {
		public function GetImageFolderDirectory() {
			if($this->image_folder_directory) {
				return $this->image_folder_directory;
			}
			
			return $this->image_folder_directory = GGCMS_DATA_DIR . $this->handler->domain->primary_domain_lowercased . '/www/image/';
		}
		
		public function makeIcon($args) {
			$args['maxheight'] = 200;
			$args['maxwidth'] = 200;
			
			return $this->resizeAndSaveFile($args);
		}
		
		public function makeStandardImage($args) {
			$args['maxheight'] = 1000;
			$args['maxwidth'] = 1000;
			
			return $this->resizeAndSaveFile($args);
		}
		
		public function UpdateImagesDirectory($args) {
			$subdir = $args['subdir'];
			
			$subdirectories = str_split($subdir);
			$subdirectories_count = count($subdirectories);
			
		#	print("BT: SUBDIR!" . $subdir . "|");
			
			$full_directory = $this->GetImageFolderDirectory();
			
			
	#		$file = $full_directory . 'test';
			
		#	$file = '/srv/ggcms/holdoffhunger.com/www/test';
		#	print($file . "_____");
			
		/*	$filename_handle = fopen($file, 'a+');
			
			while (!flock($filename_handle, LOCK_EX)) {
				usleep(round(rand(0, 100)*1000)); //0-100 milliseconds
			}
			
			chmod($filename, 0755);
			fwrite($filename_handle, "SOY");
			
			flock($filename_handle, LOCK_UN);
			fclose($filename_handle);
		*/	
			
			
			
			for($i = 0; $i < $subdirectories_count; $i++) {
				$single_subdirectory = $subdirectories[$i];
				
				$full_directory .= $single_subdirectory . '/';
				
				if(!is_dir($full_directory)) {
			#		print("BT: IS NOT DIR!" . $full_directory . "|");
					if(!mkdir($full_directory, 0755)) {
						return FALSE;
					}
				}
			}
			
			return $full_directory;
		}
		
		public function resizeAndSaveFile($args) {
			$file_location = $args['filelocation'];
			$new_icon_location = $args['resizedlocation'];
			$max_height = $args['maxheight'];
			$max_width = $args['maxwidth'];
			
			$image_filehandle = FALSE;
			$image_icon_filehandle = FALSE;
			try {
				$image = new Imagick();
				
				$image_filehandle = fopen($file_location, 'rb');
				if(!$image_filehandle || !$image->readImageFile($image_filehandle)) {
					return FALSE;
				}
				
				$image_height = $image->getImageHeight();
				$image_width = $image->getImageWidth();
				
				if($image_height > $max_height || $image_width > $max_width) {
					if($image_height > $image_width) {
						$new_height = $max_height;
						$new_width = ceil($image_width * ($max_height / $image_height));
					} else {
						$new_width = $max_width;
						$new_height = ceil($image_height * ($max_width / $image_width));
					}
					
					if(!$image->scaleImage($new_width, $new_height)) {
						return FALSE;
					}
				} else {	# image is smaller than absolute icon limits, so, this is its new size.
					$new_height = $image_height;
					$new_width = $image_width;
				}
				
				$image_icon_filehandle = fopen($new_icon_location, 'w+');
				if(!$image_icon_filehandle || !$image->writeImageFile($image_icon_filehandle)) {
					return FALSE;
				}
				
				$icon_results = [
					'originalheight'=>$image_height,
					'originalwidth'=>$image_width,
					'resizedheight'=>$new_height,
					'resizedwidth'=>$new_width,
				];
				
				return $icon_results;
			} catch(ImagickException $exception) {
				return FALSE;
			} finally {
				if(is_resource($image_filehandle)) {
					fclose($image_filehandle);
				}
				if(is_resource($image_icon_filehandle)) {
					fclose($image_icon_filehandle);
				}
			}
		}
	}

?>
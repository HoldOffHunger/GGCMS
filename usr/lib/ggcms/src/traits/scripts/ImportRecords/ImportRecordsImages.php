<?php

	/*
		Images, from a zip.

		A new Image row names its file by Image_FileName, and the file is
		the one in the zip with that name -- folders inside the zip ignored,
		case ignored.  The belt is handed it the way a browser hands it an
		upload, in $_FILES['Image'], one slot for every image the entry
		posts: the images it already had get an empty slot, which is what
		the Edit form sends for a picture left as it was.

		The Save stage moves an upload with move_uploaded_file(), which
		refuses anything PHP did not receive itself.  The zip was received;
		the files in it were taken out by this script.  So the handler is
		told which paths those are, by VouchForUpload(), and Save moves
		exactly those and no others -- see ChangeRecordsSave's
		MoveUploadedFile().
	*/

	trait ImportRecordsImages {

		public function ImportImageFiles($args) {
			$records = $args['records'];
			$existing = (int)$args['existing'];

			$new_images = isset($records['image']) ? $records['image'] : [];

			if(!count($new_images)) {
				return ['files'=>[]];
			}

			$upload = ['name'=>[], 'type'=>[], 'tmp_name'=>[], 'error'=>[], 'size'=>[]];

			for($i = 0; $i < $existing; $i++) {
				$upload['name'][] = '';
				$upload['type'][] = '';
				$upload['tmp_name'][] = '';
				$upload['error'][] = UPLOAD_ERR_NO_FILE;
				$upload['size'][] = 0;
			}

			foreach($new_images as $record) {
				$filename = isset($record['fields']['image_FileName']) ? $record['fields']['image_FileName'] : '';

				if(!strlen($filename)) {
					return ['error'=>'A new Image needs an Image_FileName naming its file in the zip.'];
				}

				$image = $this->import_file ? $this->import_file->Image(['filename'=>$filename]) : NULL;

				if(!$image) {
					return ['error'=>'There is no ' . $filename . ' in the zip for this Image.'];
				}

				if(!is_file($image['path'])) {
					return ['error'=>$filename . ' was already used by another row; each file in the zip makes one image.'];
				}

				$this->handler->VouchForUpload(['file'=>$image['path']]);

				$upload['name'][] = $image['name'];
				$upload['type'][] = (string)mime_content_type($image['path']);
				$upload['tmp_name'][] = $image['path'];
				$upload['error'][] = UPLOAD_ERR_OK;
				$upload['size'][] = $image['size'];
			}

			return ['files'=>['Image'=>$upload]];
		}
	}

?>

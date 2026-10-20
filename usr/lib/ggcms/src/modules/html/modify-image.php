<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's images on a modify.php form: a thumbnail, then the
			title, file name, description and a file to upload.

			The images are numbered from 1, for the index-adjustment
			buttons that reorder them: each row has its number, the hidden
			row the next, and image_lastindex says the one after that.

			As Edit always printed them, a thumbnail is printed for every
			image, before the check for whether its row is shown.
		*/

	class module_modifyimage extends module_modifyrecords {
		public $index;

		public function Records() {
			return $this->that->image;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['FileName']);
		}

		public function Slug() {
			return 'image';
		}

		public function AddLabel() {
			return 'Add Image';
		}

		public function Display() {
			$this->index = 1;

			parent::Display();

			print('<input type="hidden" id="image_lastindex" value="' . $this->index . '">');

			return TRUE;
		}

		public function DisplayRecordStart($args) {
			$image = $args['record'];
			$directory = implode('/', str_split($image['FileDirectory']));

			print('<div style="margin:2px;float:left;width:50px;">');
			print('<center>');
			print('<a href="/image/' . $directory . '/' . $image['FileName'] . '" target="_blank">');
			print('<img style="border:1px solid black;max-width:50px;max-height:50px;" src="/image/' . $directory . '/' . $image['IconFileName'] . '">');
			print('</a>');
			print('</center>');
			print('</div>');

			return TRUE;
		}

		public function DisplayRow($args) {
			parent::DisplayRow($args);

			$this->index++;

			return TRUE;
		}

		public function DisplayFields($args) {
			$mode = $args['mode'];

			$this->DisplayIndex(['mode'=>$mode]);

			$fields = [
				'image_Title'=>NULL,
				'image_FileName'=>'Filename : ',
				'image_Description'=>'Description : ',
				'Image'=>'File : ',
			];

			foreach($fields as $name => $label) {
				if($label) {
					print($label);
				} else {
					$this->that->TitleDisplay();
				}

				$this->DisplayInput(['name'=>$name, 'record'=>$args['record'], 'mode'=>$mode]);
			}

			return TRUE;
		}

			// A row's number, with its id; the hidden row's, without, and
			// counted here, since the hidden row is printed once.

		public function DisplayIndex($args) {
			if($args['mode'] === 'row') {
				print('<button id="index-adjustment-' . $this->index . '" class="index-adjustment" data-type="image" data-index="' . $this->index . '">');
			} elseif($args['mode'] === 'hidden') {
				print('<button class="index-adjustment" data-type="image" data-index="' . $this->index . '">');
			} else {
				return FALSE;
			}

			print($this->index);
			print('</button>');

			if($args['mode'] === 'hidden') {
				$this->index++;
			}

			return TRUE;
		}

		public function DisplayInput($args) {
			$name = $args['name'];
			$file = $name === 'Image';

			$input_args = [
				'type'=>$file ? 'file' : 'text',
				'name'=>$this->Name($args),
				'size'=>40,
				'maxlength'=>255,
			];

			if($args['mode'] === 'row') {
				$input_args['id'] = $name . '_' . $this->index;

				if(!$file) {
					$input_args['value'] = $args['record'][substr($name, strlen('image_'))];
				}
			}

			$this->form->DisplayFormField($input_args);

			return TRUE;
		}
	}

?>

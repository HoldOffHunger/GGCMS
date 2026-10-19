<?php

		/*
			The entry's images on the modify form: a title, file name,
			description and upload box, one row each, with a thumbnail of
			every image the entry has.  See modify-repeater.php for the
			rows around them.

			Each value row and the hidden row carry a numbered
			index-adjustment button, and the value rows' inputs are given
			ids by that number; the last number given out goes in
			image_lastindex, for the page's script to count on from.

			Switches:
			  nothumbnails     no thumbnails; Save and Update
			  noindexing       no index buttons, ids or image_lastindex;
			                   Save and Update
			  plaintitlelabel  label the title with a plain Title :
			                   rather than TitleDisplay(); Update
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifyimage extends module_modifyrepeater {
		public $code = 'image';
		public $label = 'Image';
		public $nothumbnails;
		public $noindexing;
		public $plaintitlelabel;
		public $index;

		public function __construct($args) {
			parent::__construct($args);

			$this->nothumbnails = !empty($args['nothumbnails']);
			$this->noindexing = !empty($args['noindexing']);
			$this->plaintitlelabel = !empty($args['plaintitlelabel']);
			$this->index = 1;
		}

		public function Values() {
			return $this->that->image;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['FileName']);
		}

		public function DisplayAny($args) {
			if($this->nothumbnails) {
				return FALSE;
			}

			$image = $args['value'];
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

		public function DisplayValue($args) {
			parent::DisplayValue($args);

			$this->index++;

			return TRUE;
		}

		public function DisplayList() {
			parent::DisplayList();

			if(!$this->noindexing) {
				print('<input type="hidden" id="image_lastindex" value="' . $this->index . '">');
			}

			return TRUE;
		}

		public function DisplayInputs($args) {
			$image = $args['value'];
			$row = $args['row'];
			$ids = !$this->noindexing && $row === 'value';

			$this->DisplayIndex(['row'=>$row]);

			if($this->plaintitlelabel) {
				print('Title : ');
			} else {
				$this->that->TitleDisplay();
			}

			$this->DisplayText(['field'=>'Title', 'value'=>$image, 'row'=>$row, 'id'=>$ids]);

			print('Filename : ');
			$this->DisplayText(['field'=>'FileName', 'value'=>$image, 'row'=>$row, 'id'=>$ids]);

			print('Description : ');
			$this->DisplayText(['field'=>'Description', 'value'=>$image, 'row'=>$row, 'id'=>$ids]);

			print('File : ');

			$this->form->DisplayFormField([
				'type'=>'file',
				'id'=>$ids ? 'Image_' . $this->index : NULL,
				'name'=>$row === 'hidden' ? 'Image-Hidden' : 'Image[]',
				'size'=>40,
				'maxlength'=>255,
			]);

			return TRUE;
		}

			// the numbered button on a value row, and on the hidden row without an id, which takes the next number
		public function DisplayIndex($args) {
			if($this->noindexing || $args['row'] === 'blank') {
				return FALSE;
			}

			if($args['row'] === 'value') {
				print('<button id="index-adjustment-' . $this->index . '" class="index-adjustment" data-type="image" data-index="' . $this->index . '">');
			} else {
				print('<button class="index-adjustment" data-type="image" data-index="' . $this->index . '">');
			}

			print($this->index);
			print('</button>');

			if($args['row'] === 'hidden') {
				$this->index++;
			}

			return TRUE;
		}

		public function DisplayText($args) {
			return $this->form->DisplayFormField([
				'type'=>'text',
				'id'=>$args['id'] ? 'image_' . $args['field'] . '_' . $this->index : NULL,
				'name'=>$this->Name(['name'=>'image_' . $args['field'], 'row'=>$args['row']]),
				'size'=>40,
				'value'=>$args['value'][$args['field']],
				'maxlength'=>255,
			]);
		}
	}

?>

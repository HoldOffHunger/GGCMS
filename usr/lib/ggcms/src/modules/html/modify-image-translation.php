<?php

		/*
			The entry's image translations on the modify form: a title,
			file name and description in another language, one row each.
			See modify-repeater.php for the rows around them.

			Two things here differ from the other fields, and always
			have: the blank row's button says Add Image, and the hidden
			row's language is named imagetranslation_Language[] like the
			rows it is copied into.
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifyimagetranslation extends module_modifyrepeater {
		public $code = 'image-translation';
		public $label = 'Image Translation';

		public function Values() {
			return $this->that->imagetranslation;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Title']);
		}

		public function DisplayBlank() {
			$this->DisplayInputs(['value'=>NULL, 'row'=>'blank']);

			$this->form->DisplayFormField([
				'type'=>'button',
				'id'=>'add-image-translation-button',
				'class'=>'float-right',
				'value'=>'Add Image',
			]);

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function DisplayInputs($args) {
			$image_translation = $args['value'];
			$row = $args['row'];

			$this->that->TitleDisplay();
			$this->DisplayText(['field'=>'Title', 'value'=>$image_translation, 'row'=>$row]);

			print('Filename : ');
			$this->DisplayText(['field'=>'FileName', 'value'=>$image_translation, 'row'=>$row]);

			print('Description : ');
			$this->DisplayText(['field'=>'Description', 'value'=>$image_translation, 'row'=>$row]);

			print('Language : ');

				// named as on a value row whatever the row, the hidden one too
			$this->DisplayLanguage([
				'name'=>'imagetranslation_Language',
				'row'=>'value',
				'selected'=>$row === 'value' ? $image_translation['Language'] : 'en',
			]);

			return TRUE;
		}

		public function DisplayText($args) {
			return $this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'imagetranslation_' . $args['field'], 'row'=>$args['row']]),
				'size'=>40,
				'value'=>$args['value'][$args['field']],
				'maxlength'=>255,
			]);
		}
	}

?>

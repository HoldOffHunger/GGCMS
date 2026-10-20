<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's image translations on a modify.php form: the title,
			file name, description and language.

			As Edit always printed them: the blank row's button reads Add
			Image, and the hidden row's language is named
			imagetranslation_Language[], not -Hidden, so it is submitted
			with the real rows.
		*/

	class module_modifyimagetranslation extends module_modifyrecords {
		public function Records() {
			return $this->that->imagetranslation;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['Title']);
		}

		public function Slug() {
			return 'image-translation';
		}

		public function AddLabel() {
			return 'Add Image Translation';
		}

		public function BlankAddLabel() {
			return 'Add Image';
		}

		public function DisplayFields($args) {
			$mode = $args['mode'];

			$fields = [
				'Title'=>NULL,
				'FileName'=>'Filename : ',
				'Description'=>'Description : ',
			];

			foreach($fields as $field => $label) {
				if($label) {
					print($label);
				} else {
					$this->that->TitleDisplay();
				}

				$this->DisplayText(['field'=>$field, 'record'=>$args['record'], 'mode'=>$mode]);
			}

			print('Language : ');

			$this->DisplayLanguageSelect($args);

			return TRUE;
		}

			// Edit, Save and Update have always named the hidden row's
			// language [], not -Hidden, so the hidden row sends one and
			// each added translation's language arrives one place late.
			// Add never did, and does not now.

		public function DisplayLanguageSelect($args) {
			$name = 'imagetranslation_Language[]';

			if($this->adding) {
				$name = $this->Name(['name'=>'imagetranslation_Language', 'mode'=>$args['mode']]);
			}

			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$name,
				'options'=>$this->that->SelectableLanguages,
				'selected'=>$this->Language($args),
			]);

			return TRUE;
		}

		public function DisplayText($args) {
			$text_args = [
				'type'=>'text',
				'name'=>$this->Name(['name'=>'imagetranslation_' . $args['field'], 'mode'=>$args['mode']]),
				'size'=>40,
				'maxlength'=>255,
			];

			if($args['mode'] === 'row') {
				$text_args['value'] = $args['record'][$args['field']];
			}

			$this->form->DisplayFormField($text_args);

			return TRUE;
		}
	}

?>

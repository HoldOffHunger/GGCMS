<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's image translations on a modify.php form: the title,
			file name, description and language.
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

		public function DisplayLanguageSelect($args) {
			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$this->Name(['name'=>'imagetranslation_Language', 'mode'=>$args['mode']]),
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

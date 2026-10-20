<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's translations on a modify.php form: the title,
			subtitle, list title, list title sort key and language.

			As Edit always printed them, a row's "List Title Sort Key: " has
			no space before its colon.
		*/

	class module_modifyentrytranslation extends module_modifyrecords {
		public function Records() {
			return $this->that->entrytranslation;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['Title']);
		}

		public function Slug() {
			return 'entry-translation';
		}

		public function AddLabel() {
			return 'Add Entry Translation';
		}

		public function DisplayFields($args) {
			$mode = $args['mode'];

			$fields = [
				'Title'=>NULL,
				'Subtitle'=>'Subtitle : ',
				'ListTitle'=>'List Title : ',
				'ListTitleSortKey'=>$mode === 'row' ? 'List Title Sort Key: ' : 'List Title Sort Key : ',
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

			$this->DisplayLanguage(['name'=>'entrytranslation_Language', 'record'=>$args['record'], 'mode'=>$mode]);

			return TRUE;
		}

		public function DisplayText($args) {
			$text_args = [
				'type'=>'text',
				'name'=>$this->Name(['name'=>'entrytranslation_' . $args['field'], 'mode'=>$args['mode']]),
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

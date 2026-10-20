<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's text bodies on a modify.php form: the text, its
			source, the three cleaning checkboxes and its language.

			As Edit always printed them: the hidden row has no checkboxes,
			only a row's checkboxes have a size, and the blank row is
			followed by a stray </div> (DisplayBlank()).
		*/

	class module_modifytextbody extends module_modifyrecords {
		public function Records() {
			return $this->that->textbody;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['Text']);
		}

		public function Slug() {
			return 'text';
		}

		public function AddLabel() {
			return 'Add Text';
		}

		public function DisplayBlank() {
			parent::DisplayBlank();

			$this->divider->displayend([]);

			return TRUE;
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];

			$text_args = [
				'type'=>'textarea',
				'name'=>$this->Name(['name'=>'Text', 'mode'=>$mode]),
				'cols'=>60,
				'rows'=>20,
				'class'=>'float-left',
			];

			if($mode === 'row') {
				$text_args['value'] = $record['Text'];
			}

			$this->form->DisplayFormField($text_args);

			$this->DisplaySource(['name'=>'textbody_Source', 'record'=>$record, 'mode'=>$mode]);

			if($mode !== 'hidden') {
				$this->DisplayCheckboxes(['mode'=>$mode]);
			}

			print('Language : ');

			$this->DisplayLanguage(['name'=>'textbody_Language', 'record'=>$record, 'mode'=>$mode]);

			return TRUE;
		}

		public function DisplayCheckboxes($args) {
			$checkboxes = [
				'textbody_StripURLs'=>'Strip URLs : ',
				'textbody_AmericanizeVocabulary'=>'Americanize Vocabulary : ',
				'textbody_HTMLFormatting'=>'HTML Formatting : ',
			];

			print("<br>");

			foreach($checkboxes as $name => $label) {
				print($label);

				$checkbox_args = [
					'type'=>'checkbox',
					'name'=>$name . '[]',
					'value'=>1,
					'maxlength'=>512,
				];

				if($args['mode'] === 'row') {
					$checkbox_args['size'] = 30;
				}

				$this->form->DisplayFormField($checkbox_args);

				print("<br>");
			}

			return TRUE;
		}
	}

?>

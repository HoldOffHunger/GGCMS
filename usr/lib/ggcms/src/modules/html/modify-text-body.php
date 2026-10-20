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

			$this->DisplayText($args);

			$this->DisplaySource([
				'name'=>'textbody_Source',
				'record'=>$record,
				'mode'=>$mode,
				'class'=>$this->Replicated($args) ? 'textbody_Source' : NULL,
			]);

			if($mode !== 'hidden') {
				$this->DisplayCheckboxes(['mode'=>$mode]);
			}

			print('Language : ');

			$this->DisplayLanguage(['name'=>'textbody_Language', 'record'=>$record, 'mode'=>$mode]);

			return TRUE;
		}

			// Adding, the blank text has the focus where the site generates
			// titles; otherwise the title has it.

		public function DisplayText($args) {
			$mode = $args['mode'];

			$text_args = [
				'type'=>'textarea',
				'name'=>$this->Name(['name'=>'Text', 'mode'=>$mode]),
				'cols'=>60,
				'rows'=>20,
				'class'=>'float-left',
			];

			if($mode === 'row') {
				$text_args['value'] = $args['record']['Text'];
			}

			if($this->Replicated($args) && $this->that->handler->globals->AutoGenerateTitleDefault()) {
				$text_args['autofocus'] = TRUE;
			}

			$this->form->DisplayFormField($text_args);

			return TRUE;
		}

			// Add's blank text, whose source the Replicate button fills.

		public function Replicated($args) {
			return $this->adding && $args['mode'] === 'blank';
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
					'checked'=>$this->Checked(['name'=>$name]),
				];

				if($args['mode'] === 'row') {
					$checkbox_args['size'] = 30;
				}

				$this->form->DisplayFormField($checkbox_args);

				print("<br>");
			}

			return TRUE;
		}

			// A new entry's text is cleaned up by default, and formatted as
			// HTML where the site says so; an entry being modified has them
			// unticked.

		public function Checked($args) {
			if(!$this->adding) {
				return FALSE;
			}

			if($args['name'] === 'textbody_HTMLFormatting') {
				return $this->that->handler->globals->AddEntryHTMLFormatting();
			}

			return TRUE;
		}
	}

?>

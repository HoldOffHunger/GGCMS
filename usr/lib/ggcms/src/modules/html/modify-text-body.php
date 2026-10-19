<?php

		/*
			The entry's text bodies on the modify form: the text, its
			source, the clean-ups to run on it and its language, one row
			each.  See modify-repeater.php for the rows around them.

			The blank row is followed by a </div> it never opened; it
			always has been, on Edit and Save.

			Switches:
			  nooptions      no Strip URLs, Americanize Vocabulary and
			                 HTML Formatting checkboxes; Save and Update
			  textmaxlength  the text's box on a value row takes 512
			                 characters at most; Save
			  balancedblank  no stray </div> after the blank row; Update
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifytextbody extends module_modifyrepeater {
		public $code = 'text';
		public $label = 'Text';
		public $nooptions;
		public $textmaxlength;
		public $balancedblank;

		public function __construct($args) {
			parent::__construct($args);

			$this->nooptions = !empty($args['nooptions']);
			$this->textmaxlength = !empty($args['textmaxlength']);
			$this->balancedblank = !empty($args['balancedblank']);
		}

		public function Values() {
			return $this->that->textbody;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Text']);
		}

		public function DisplayBlank() {
			parent::DisplayBlank();

			if(!$this->balancedblank) {
				$this->divider->displayend([]);
			}

			return TRUE;
		}

		public function DisplayInputs($args) {
			$textbody = $args['value'];
			$row = $args['row'];

			$this->form->DisplayFormField([
				'type'=>'textarea',
				'name'=>$row === 'hidden' ? 'Text-Hidden' : 'Text[]',
				'cols'=>60,
				'rows'=>20,
				'value'=>$textbody['Text'],
				'maxlength'=>($this->textmaxlength && $row === 'value') ? 512 : NULL,
				'class'=>'float-left',
			]);

			$this->that->SourceDisplay();

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'textbody_Source', 'row'=>$row]),
				'size'=>30,
				'value'=>$textbody['Source'],
				'maxlength'=>512,
			]);

			if(!$this->nooptions && $row !== 'hidden') {
				$this->DisplayOptions(['row'=>$row]);
			}

			print('Language : ');

			$this->DisplayLanguage([
				'name'=>'textbody_Language',
				'row'=>$row,
				'selected'=>$row === 'value' ? $textbody['Language'] : 'en',
			]);

			return TRUE;
		}

		public function DisplayOptions($args) {
			$options = [
				'StripURLs'=>'Strip URLs',
				'AmericanizeVocabulary'=>'Americanize Vocabulary',
				'HTMLFormatting'=>'HTML Formatting',
			];

			print("<br>");

			foreach($options as $option => $option_label) {
				print($option_label . ' : ');

				$this->form->DisplayFormField([
					'type'=>'checkbox',
					'name'=>'textbody_' . $option . '[]',
					'size'=>$args['row'] === 'value' ? 30 : NULL,
					'value'=>1,
					'maxlength'=>512,
				]);

				print("<br>");
			}

			return TRUE;
		}
	}

?>

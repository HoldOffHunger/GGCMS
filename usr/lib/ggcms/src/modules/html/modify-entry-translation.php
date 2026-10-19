<?php

		/*
			The entry's translations on the modify form: a title,
			subtitle, list title and sort key in another language, one row
			each.  See modify-repeater.php for the rows around them.

			Switches:
			  plaintitlelabel  the blank and hidden rows label the title
			                   with a plain Title : rather than
			                   TitleDisplay(); Update
			  subtitlerows     the rows that ask for a subtitle; value and
			                   blank by default, all three on Save, value
			                   and hidden on Update
			  sortkeylabels    the sort key's label, by row, over the
			                   defaults below; Save and Update drop the
			                   space before the colon on more rows than
			                   Edit does
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifyentrytranslation extends module_modifyrepeater {
		public $code = 'entry-translation';
		public $label = 'Entry Translation';
		public $plaintitlelabel;
		public $subtitlerows;
		public $sortkeylabels;

		public function __construct($args) {
			parent::__construct($args);

			$this->plaintitlelabel = !empty($args['plaintitlelabel']);
			$this->subtitlerows = !empty($args['subtitlerows']) ? $args['subtitlerows'] : ['value', 'blank'];
			$this->sortkeylabels = array_merge([
				'value'=>'List Title Sort Key: ',
				'blank'=>'List Title Sort Key : ',
				'hidden'=>'List Title Sort Key : ',
			], !empty($args['sortkeylabels']) ? $args['sortkeylabels'] : []);
		}

		public function Values() {
			return $this->that->entrytranslation;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Title']);
		}

		public function DisplayInputs($args) {
			$entry_translation = $args['value'];
			$row = $args['row'];

			if($this->plaintitlelabel && $row !== 'value') {
				print('Title : ');
			} else {
				$this->that->TitleDisplay();
			}

			$this->DisplayText(['field'=>'Title', 'value'=>$entry_translation, 'row'=>$row]);

			if(in_array($row, $this->subtitlerows)) {
				print('Subtitle : ');
				$this->DisplayText(['field'=>'Subtitle', 'value'=>$entry_translation, 'row'=>$row]);
			}

			print('List Title : ');
			$this->DisplayText(['field'=>'ListTitle', 'value'=>$entry_translation, 'row'=>$row]);

			print($this->sortkeylabels[$row]);
			$this->DisplayText(['field'=>'ListTitleSortKey', 'value'=>$entry_translation, 'row'=>$row]);

			print('Language : ');

			$this->DisplayLanguage([
				'name'=>'entrytranslation_Language',
				'row'=>$row,
				'selected'=>$row === 'value' ? $entry_translation['Language'] : 'en',
			]);

			return TRUE;
		}

		public function DisplayText($args) {
			return $this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'entrytranslation_' . $args['field'], 'row'=>$args['row']]),
				'size'=>40,
				'maxlength'=>255,
				'value'=>$args['value'][$args['field']],
			]);
		}
	}

?>

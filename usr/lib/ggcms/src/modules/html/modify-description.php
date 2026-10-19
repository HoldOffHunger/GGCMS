<?php

		/*
			The entry's descriptions on the modify form: the text, its
			source and its language, one row each.  See modify-repeater.php
			for the rows around them.

			Switches:
			  hiddenlanguagelabel  the label before the hidden row's
			                       language; Language : on Save and
			                       Update, Language : without the last
			                       space by default
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifydescription extends module_modifyrepeater {
		public $code = 'description';
		public $label = 'Description';
		public $hiddenlanguagelabel;

		public function __construct($args) {
			parent::__construct($args);

			$this->hiddenlanguagelabel = !empty($args['hiddenlanguagelabel']) ? $args['hiddenlanguagelabel'] : 'Language :';
		}

		public function Values() {
			return $this->that->description;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Description']);
		}

		public function DisplayInputs($args) {
			$description = $args['value'];
			$row = $args['row'];

			$this->form->DisplayFormField([
				'type'=>'textarea',
				'name'=>$row === 'hidden' ? 'Description-Hidden' : 'Description[]',
				'cols'=>60,
				'rows'=>5,
				'value'=>$description['Description'],
				'maxlength'=>$row === 'value' ? 512 : NULL,
				'class'=>'float-left',
			]);

			$this->that->SourceDisplay();

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'description_Source', 'row'=>$row]),
				'size'=>30,
				'value'=>$description['Source'],
				'maxlength'=>512,
			]);

			print($row === 'hidden' ? $this->hiddenlanguagelabel : 'Language : ');

			$this->DisplayLanguage([
				'name'=>'description_Language',
				'row'=>$row,
				'selected'=>$row === 'value' ? $description['Language'] : 'en',
			]);

			return TRUE;
		}
	}

?>

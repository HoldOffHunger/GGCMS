<?php

		/*
			The entry's definitions on the modify form: a term and what it
			means, one row each, with the Autogenerate Definitions
			checkbox before the first.  See modify-repeater.php for the
			rows around them.  Edit is the only template with the field.
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifydefinition extends module_modifyrepeater {
		public $code = 'definition';
		public $label = 'Definition';

		public function Values() {
			return $this->that->definition;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Term']);
		}

		public function DisplayInputs($args) {
			$definition = $args['value'];
			$row = $args['row'];

			if($row !== 'hidden' && !$this->displayed) {
				$this->DisplayAutogenerate();
			}

			print('Definition : ');

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$row === 'hidden' ? 'Term-Hidden' : 'Term[]',
				'size'=>20,
				'value'=>$definition['Term'],
				'maxlength'=>255,
				'class'=>'definition',
			]);

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'definition_Definition', 'row'=>$row]),
				'size'=>60,
				'value'=>$definition['Definition'],
				'maxlength'=>$row === 'hidden' ? 255 : NULL,
				'class'=>'definition',
			]);

			return TRUE;
		}

		public function DisplayAutogenerate() {
			print('Autogenerate Definitions : ');

			$this->form->DisplayFormField([
				'type'=>'checkbox',
				'name'=>'autogenerate-definitions',
				'value'=>1,
				'maxlength'=>512,
			]);

			print('<br>');

			return TRUE;
		}
	}

?>

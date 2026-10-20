<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's definitions on a modify.php form: the term and its
			definition, under one Autogenerate Definitions checkbox for
			them all, printed before the first row or the blank one.

			As Edit always printed them, only the hidden row gives the
			definition a maxlength.
		*/

	class module_modifydefinition extends module_modifyrecords {
		public function Records() {
			return $this->that->definition;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['Term']);
		}

		public function Slug() {
			return 'definition';
		}

		public function AddLabel() {
			return 'Add Definition';
		}

		public function DisplayRowStart($args) {
			if($args['first']) {
				return $this->DisplayAutogenerate();
			}

			return parent::DisplayRowStart($args);
		}

		public function DisplayBlank() {
			$this->DisplayAutogenerate();

			return parent::DisplayBlank();
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

		public function DisplayFields($args) {
			$mode = $args['mode'];
			$row = $mode === 'row';

			print('Definition : ');

			$term_args = ['type'=>'text', 'name'=>$this->Name(['name'=>'Term', 'mode'=>$mode]), 'size'=>20, 'maxlength'=>255, 'class'=>'definition'];
			$definition_args = ['type'=>'text', 'name'=>$this->Name(['name'=>'definition_Definition', 'mode'=>$mode]), 'size'=>60, 'class'=>'definition'];

			if($row) {
				$term_args['value'] = $args['record']['Term'];
				$definition_args['value'] = $args['record']['Definition'];
			}

			if($mode === 'hidden') {
				$definition_args['maxlength'] = 255;
			}

			$this->form->DisplayFormField($term_args);
			$this->form->DisplayFormField($definition_args);

			return TRUE;
		}
	}

?>

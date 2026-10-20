<?php

		/*
			What every repeatable field on the modify form shares: the
			descriptions, quotes, tags and the rest, anything an entry can
			have more than one of.

			Display() prints a row for each value the entry already has,
			the first with an Add button and every later one boxed in an
			input-divider with a Remove button, or a blank row when it has
			none.  Then the hidden row that the Add button copies, and the
			empty list the copies go into.

			A field extends this, names itself in $code and $label, and
			says what it lists and how a row looks:

			  Values()         the values it lists, from $this->that
			  Accepts()        whether a value gets a row
			  DisplayInputs()  one row's inputs; the row is value, blank
			                   or hidden, and the value is NULL on the
			                   last two

			and may override any other step of Display() it does
			differently.

			Afterwards $this->displayed says whether any value had a row,
			and $this->last holds the last value looked at, accepted or
			not.  Both are read by fields that lean on them.
		*/

	class module_modifyrepeater extends module_spacing {
		public $that;
		public $form;
		public $divider;
		public $code;
		public $label;
		public $displayed;
		public $last;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->form = new module_form;
			$this->divider = new module_divider;
		}

			// Display()
			// Tests: ModifyRepeaterTest::testDisplay(), ModifyRepeaterTest::testDisplayBlank()
			// Test file: tests/src/modules/html/ModifyRepeaterTest.php
		public function Display() {
			$this->displayed = 0;

			foreach($this->Values() as $value) {
				$this->last = $value;
				$this->DisplayAny(['value'=>$value]);

				if($this->Accepts(['value'=>$value])) {
					$this->DisplayValue(['value'=>$value]);
				}
			}

			if(!$this->displayed) {
				$this->DisplayBlank();
			}

			$this->DisplayHidden();
			$this->DisplayList();

			return TRUE;
		}

			// before each value, accepted or not; nothing here, and Image's thumbnail
		public function DisplayAny($args) {
			return TRUE;
		}

		public function DisplayValue($args) {
			if($this->displayed) {
				$this->DisplayDividerStart();
			}

			$this->DisplayInputs(['value'=>$args['value'], 'row'=>'value']);
			$this->DisplayButton(['add'=>!$this->Follows()]);

			if($this->Follows()) {
				$this->divider->displayend([]);
			}

			print('<div class="clear-float"></div>');

			$this->displayed = 1;

			return TRUE;
		}

		public function DisplayBlank() {
			$this->DisplayInputs(['value'=>NULL, 'row'=>'blank']);
			$this->DisplayButton(['add'=>TRUE]);

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function DisplayHidden() {
			$this->divider->displaystart([
				'id'=>'hidden-' . $this->code . '-input',
				'class'=>'display-none',
			]);

			$this->DisplayInputs(['value'=>NULL, 'row'=>'hidden']);
			$this->DisplayButton(['add'=>FALSE]);

			print('<div class="clear-float"></div>');

			$this->divider->displayend([]);

			return TRUE;
		}

		public function DisplayList() {
			$this->divider->displaystart(['id'=>$this->code . '-list']);
			$this->divider->displayend([]);

			return TRUE;
		}

		public function DisplayDividerStart() {
			return $this->divider->displaystart(['class'=>'input-divider']);
		}

		public function DisplayButton($args) {
			if($args['add']) {
				return $this->form->DisplayFormField([
					'type'=>'button',
					'id'=>'add-' . $this->code . '-button',
					'class'=>'float-right',
					'value'=>'Add ' . $this->label,
				]);
			}

			return $this->form->DisplayFormField([
				'type'=>'button',
				'id'=>'delete-' . $this->code . '-button',
				'class'=>'float-right',
				'value'=>'Remove',
			]);
		}

		public function DisplayLanguage($args) {
			return $this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$this->Name($args),
				'options'=>$this->that->SelectableLanguages,
				'selected'=>$args['selected'],
			]);
		}

			// Data
			// -------------------------------------------------

			// whether the row being printed comes after another; Association overrides it
		public function Follows() {
			return $this->displayed;
		}

			// Name()
			// Tests: ModifyRepeaterTest::testName()
			// Test file: tests/src/modules/html/ModifyRepeaterTest.php
			// the input's name on this row: Name[] on a value or blank row, Name-Hidden on the hidden one
		public function Name($args) {
			return $args['name'] . ($args['row'] === 'hidden' ? '-Hidden' : '[]');
		}
	}

?>

<?php

	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');

		/*
			The inputs for one kind of record an entry has many of --
			descriptions, quotes, tags, links -- on a modify.php form.

			Display() prints, in order:

			  the rows   one per record that has something in it; the first
			             carries the Add button, the rest a Remove button and
			             an input-divider around them
			  a blank    when no record had anything in it, one empty row
			             with the Add button
			  a hidden   row, display-none, that the Add button copies; its
			             names end in -Hidden rather than []
			  a list     div that the copies are added to

			A kind of record extends this and says which records are its
			own -- Records(), IsShown() -- what its ids and Add button are
			called -- Slug(), AddLabel() -- and prints its inputs in
			DisplayFields(), given the record and the mode: row, blank or
			hidden.  The rest it overrides only where it differs.
		*/

	class module_modifyrecords extends module_spacing {
		public $that;
		public $form;
		public $divider;
		public $last_record;

		public function __construct($args) {
			$this->that = $args['that'];

			$this->form = new module_form;
			$this->divider = new module_divider;
		}

		public function Display() {
			$displayed = FALSE;
			$this->last_record = NULL;

			foreach($this->Records() as $record) {
				$this->last_record = $record;

				$this->DisplayRecordStart(['record'=>$record]);

				if($this->IsShown(['record'=>$record])) {
					$this->DisplayRow(['record'=>$record, 'first'=>!$displayed]);
					$displayed = TRUE;
				}
			}

			if(!$displayed) {
				$this->DisplayBlank();
			}

			$this->DisplayHidden();
			$this->DisplayList();

			return TRUE;
		}

			// Rows
			// -------------------------------------------------

			// Before each record, shown or not; nothing, but for images.

		public function DisplayRecordStart($args) {
			return TRUE;
		}

		public function DisplayRow($args) {
			$this->DisplayRowStart($args);
			$this->DisplayFields(['record'=>$args['record'], 'mode'=>'row']);

			if($args['first']) {
				$this->DisplayAddButton(['label'=>$this->AddLabel()]);
			} else {
				$this->DisplayRemoveButton();
			}

			$this->DisplayRowEnd($args);

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function DisplayRowStart($args) {
			if(!$args['first']) {
				$this->divider->displaystart(['class'=>'input-divider']);
			}

			return TRUE;
		}

		public function DisplayRowEnd($args) {
			if(!$args['first']) {
				$this->divider->displayend([]);
			}

			return TRUE;
		}

		public function DisplayBlank() {
			$this->DisplayFields(['record'=>NULL, 'mode'=>'blank']);
			$this->DisplayAddButton(['label'=>$this->BlankAddLabel()]);

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function DisplayHidden() {
			$this->divider->displaystart(['id'=>'hidden-' . $this->Slug() . '-input', 'class'=>'display-none']);

			$this->DisplayFields(['record'=>NULL, 'mode'=>'hidden']);
			$this->DisplayRemoveButton();

			print('<div class="clear-float"></div>');

			$this->divider->displayend([]);

			return TRUE;
		}

		public function DisplayList() {
			$this->divider->displaystart(['id'=>$this->Slug() . '-list']);
			$this->divider->displayend([]);

			return TRUE;
		}

			// Buttons
			// -------------------------------------------------

		public function DisplayAddButton($args) {
			$this->form->DisplayFormField([
				'type'=>'button',
				'id'=>'add-' . $this->Slug() . '-button',
				'class'=>'float-right',
				'value'=>$args['label'],
			]);

			return TRUE;
		}

		public function DisplayRemoveButton() {
			$this->form->DisplayFormField([
				'type'=>'button',
				'id'=>'delete-' . $this->Slug() . '-button',
				'class'=>'float-right',
				'value'=>'Remove',
			]);

			return TRUE;
		}

		public function BlankAddLabel() {
			return $this->AddLabel();
		}

			// Inputs
			// -------------------------------------------------

			// An input's name: Name[] in a row or the blank, Name-Hidden in
			// the hidden row.

		public function Name($args) {
			return $args['name'] . ($args['mode'] === 'hidden' ? '-Hidden' : '[]');
		}

			// The source a text was taken from.

		public function DisplaySource($args) {
			$this->that->SourceDisplay();

			$source_args = [
				'type'=>'text',
				'name'=>$this->Name($args),
				'size'=>30,
				'maxlength'=>512,
			];

			if($args['mode'] === 'row') {
				$source_args['value'] = $args['record']['Source'];
			}

			$this->form->DisplayFormField($source_args);

			return TRUE;
		}

			// A record's language, or English for a new one.

		public function Language($args) {
			return $args['mode'] === 'row' ? $args['record']['Language'] : 'en';
		}

		public function DisplayLanguage($args) {
			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$this->Name(['name'=>$args['name'], 'mode'=>$args['mode']]),
				'options'=>$this->that->SelectableLanguages,
				'selected'=>$this->Language($args),
			]);

			return TRUE;
		}
	}

?>

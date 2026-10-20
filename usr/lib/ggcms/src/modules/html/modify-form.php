<?php

	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/table.php');

		/*
			The form of a modify.php page -- Edit, Save, Update: a table
			per modifiable field, in the order modifiableRecordTypes()
			gives, each with its heading and hints on the left and its
			inputs on the right, and the buttons last.

			The fields with many records -- descriptions, quotes, tags and
			the rest -- are each their own module (modify-description.php
			and its kind), found by RecordModules(); the single-valued ones
			are printed here.

			Display() opens the form and prints it; DisplayEnd() closes it.
			They are separate because the templates have always closed the
			form whether or not they opened it.

			Switches:
			  action  Save, for Save.php: a Save button and no Delete, and
			          Publish ticked for a new entry; Update otherwise, as
			          Edit and Update submit
		*/

	class module_modifyform extends module_spacing {
		public $that;
		public $action;
		public $form;
		public $table;
		public $subtle_hints;
		public $hints;
		public $record_modules = [];

		public function __construct($args) {
			$this->that = $args['that'];
			$this->action = !empty($args['action']) ? $args['action'] : 'Update';

			$this->form = new module_form;
			$this->table = new module_table;
		}

		public function Display() {
			$this->form->StartForm([
				'action'=>0,
				'method'=>'post',
				'files'=>1,
				'formclass'=>'margin-0px',
			]);

			$this->that->displayAssociationTypes();
			$this->that->displayAssociationSubTypes();

			$this->subtle_hints = $this->that->SubtleFieldHints();
			$this->hints = $this->that->ActiveFieldHints();

			foreach($this->that->modifiableRecordTypes() as $element) {
				$this->DisplayElement(['element'=>$element]);
			}

			return TRUE;
		}

		public function DisplayEnd() {
			$this->form->EndForm([]);

			return TRUE;
		}

			// Element
			// -------------------------------------------------

			// A table with the heading and the inputs side by side; the
			// buttons' table is one cell across both.

		public function DisplayElement($args) {
			$element = $args['element'];
			$subtle_hint = $this->subtle_hints[$element];

			if($element !== 'Save') {
				$this->table->DisplayComponent_StartTable(['id'=>$element, 'tableclass'=>'width-100percent blank-element', 'tableborder'=>'3', 'cellvalign'=>'top']);
				$this->DisplayHeading($args);
				$this->table->DisplayComponent_SeparateCells(['cellwidth'=>'80%', 'cellvalign'=>'top']);
			} else {
				$this->table->DisplayComponent_StartTable(['id'=>$element, 'tableclass'=>'width-100percent blank-element', 'tableborder'=>'3', 'cellvalign'=>'top', 'cellcolspan'=>2]);
			}

			$this->DisplaySubtleHintStart(['hint'=>$subtle_hint]);
			$this->DisplayInputs($args);
			$this->DisplaySubtleHintEnd(['hint'=>$subtle_hint]);

			$this->table->DisplayComponent_EndTable([]);

			return TRUE;
		}

		public function DisplayHeading($args) {
			$element = $args['element'];

			$this->DisplaySubtleHintStart(['hint'=>$this->subtle_hints[$element]]);

			print($this->that->ElementDisplay(['element'=>$element]));

			if($element === 'Code') {
				print(' (For Page)');
			}

			if($this->hints[$element]) {
				print('<div style="max-width:200px;">');
				print($this->hints[$element]);
				print('</div>');
			}

			$this->DisplaySubtleHintEnd(['hint'=>$this->subtle_hints[$element]]);

			return TRUE;
		}

			// A subtle hint is a tooltip over the heading, and again over
			// the inputs.

		public function DisplaySubtleHintStart($args) {
			if($args['hint']) {
				print('<div title="');
				print(htmlentities($args['hint']));
				print('">');
			}

			return TRUE;
		}

		public function DisplaySubtleHintEnd($args) {
			if($args['hint']) {
				print('</div>');
			}

			return TRUE;
		}

			// Inputs
			// -------------------------------------------------

		public function DisplayInputs($args) {
			$element = $args['element'];
			$record_modules = $this->RecordModules();

			if(array_key_exists($element, $record_modules)) {
				return $this->RecordModule(['file'=>$record_modules[$element]])->Display();
			}

			switch($element) {
				case 'Availability Start':
					return $this->DisplayAvailability(['which'=>'Start']);

				case 'Availability End':
					return $this->DisplayAvailability(['which'=>'End']);

				case 'Publish':
					return $this->DisplayPublish();

				case 'Save':
					return $this->DisplayButtons();
			}

			if(array_key_exists($element, $this->Checkboxes())) {
				return $this->DisplayText($args);
			}

			return FALSE;
		}

			// The fields with many records, each its own module, by the
			// file it is in: modify-<file>.php holds module_modify<file>.

		public function RecordModules() {
			return [
				'Entry Translation'=>'entry-translation',
				'Description'=>'description',
				'Quote'=>'quote',
				'Text Body'=>'text-body',
				'Image'=>'image',
				'Image Translation'=>'image-translation',
				'Tag'=>'tag',
				'Link'=>'link',
				'Event Date'=>'event-date',
				'Association'=>'association',
				'Definition'=>'definition',
			];
		}

		public function RecordModule($args) {
			$file = $args['file'];

			if(!$this->record_modules[$file]) {
				require_once(GGCMS_DIR . 'modules/html/modify-' . $file . '.php');

				$class = 'module_modify' . str_replace('-', '', $file);

				$this->record_modules[$file] = new $class(['that'=>$this->that]);
			}

			return $this->record_modules[$file];
		}

			// Single values
			// -------------------------------------------------

			// The entry's own text fields, each named as its heading without
			// spaces, with the checkboxes after it that the site turns on.

		public function DisplayText($args) {
			$element = $args['element'];
			$name = str_replace(' ', '', $element);

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$name,
				'size'=>60,
				'value'=>$this->that->entry[$name],
				'maxlength'=>255,
			]);

			foreach($this->Checkboxes()[$element] as $checkbox_name => $checkbox) {
				$this->DisplayCheckbox(['name'=>$checkbox_name, 'checkbox'=>$checkbox]);
			}

			return TRUE;
		}

		public function DisplayCheckbox($args) {
			$checkbox = $args['checkbox'];
			$globals = $this->that->handler->globals;

			if(!$globals->{$checkbox['global']}()) {
				return FALSE;
			}

			print('<input type="checkbox" value="1" name="' . $args['name'] . '" id="' . $args['name'] . '" ');

			if(!empty($checkbox['default']) && $globals->{$checkbox['default']}()) {
				print('CHECKED="CHECKED"');
			}

			print(' /> ' . $checkbox['label']);

			return TRUE;
		}

			// Each text field's checkboxes: the global that shows it and,
			// where it is ticked by default, the global that says so.  The
			// defaults commented out are as they were in Edit.php.

		public function Checkboxes() {
			return [
				'Title'=>[
					'autoincrement-title'=>['global'=>'TitleAutoIncrement', 'label'=>'Autoincrement Title', 'default'=>'AutoGenerateTitleDefault'],
					'title-smart-title-case'=>['global'=>'TitleAutoSmartTitleCase', 'label'=>'Smart Title-Case', 'default'=>'AutoGenerateTitleDefault'],
					'title-americanize'=>['global'=>'TitleAmericanize', 'label'=>'Americanize'],		# 'default'=>'AmericanizeTitleDefault'
					'title-de-romanize-numbers'=>['global'=>'TitleDeRomanizeNumbers', 'label'=>'De-Romanize Numbers'],		# 'default'=>'AutoGenerateTitleDefault'
				],
				'Subtitle'=>[
					'subtitle-smart-title-case'=>['global'=>'SubTitleAutoSmartTitleCase', 'label'=>'Smart Title-Case', 'default'=>'AutoGenerateTitleDefault'],
					'subtitle-americanize'=>['global'=>'SubtitleAmericanize', 'label'=>'Americanize'],		# 'default'=>'AmericanizeSubtitleDefault'
				],
				'List Title'=>[
					'listtitle-smart-title-case'=>['global'=>'ListTitleAutoSmartTitleCase', 'label'=>'Smart Title-Case'],		# 'default'=>'AutoGenerateTitleDefault'
					'listtitle-americanize'=>['global'=>'ListTitleAmericanize', 'label'=>'Americanize'],		# 'default'=>'AmericanizeListTitleDefault'
				],
				'List Title Sort Key'=>[],
				'Code'=>[],
				'Child Adjective'=>[],
				'Child Noun'=>[],
				'Child Noun Plural'=>[],
				'Grand Child Adjective'=>[],
				'Grand Child Noun'=>[],
				'Grand Child Noun Plural'=>[],
			];
		}

		public function DisplayAvailability($args) {
			$which = $args['which'];
			$range = $this->that->availabilitydaterange[0];

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>'Availability' . $which . 'Date[]',
				'size'=>20,
				'class'=>'datepicker',
				'value'=>$range['Availability' . $which . 'Date'],
			]);

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>'Availability' . $which . 'Time[]',
				'size'=>20,
				'class'=>'timepicker',
				'value'=>$range['Availability' . $which . 'Time'],
			]);

			return TRUE;
		}

			// Save ticks Publish for a new entry whatever it was; it once
			// tested the entry as Edit does:
			//	#if($this->entry['Publish'] === 1) {		# FIXME: Globals-ify

		public function DisplayPublish() {
			print('<input type="checkbox" name="Publish" value="1"');

			if($this->action === 'Save' || $this->that->entry['Publish'] === 1) {
				print(' CHECKED="CHECKED"');
			}

			print('>');

			return TRUE;
		}

			// Buttons
			// -------------------------------------------------

		public function DisplayButtons() {
			if($this->action === 'Save') {
				print('<center>');
				$this->form->DisplayFormField(['type'=>'submit', 'name'=>'Save', 'value'=>'Save']);
			} else {
				print('<center>Update This Entry : ');
				$this->form->DisplayFormField(['type'=>'submit', 'name'=>'Update', 'value'=>'Update']);
				print('</center>');

				$this->DisplayDeleteButton();
			}

			$this->form->DisplayFormField(['type'=>'hidden', 'name'=>'action', 'value'=>$this->action]);

			print('</center>');

			return TRUE;
		}

			// Delete, beside Update, for an entry that is not the site's
			// primary record and a user who may delete it.

		public function DisplayDeleteButton() {
			if(!$this->that->parent['id'] || !$this->that->canUserDelete()) {
				return FALSE;
			}

			$this->table->DisplayComponent_SeparateCells(['cellwidth'=>'50%', 'cellvalign'=>'top']);

			print('<center>');
			$this->form->DisplayFormField(['type'=>'submit', 'name'=>'Delete', 'value'=>'Delete', 'class'=>'confirm']);
			print('</center>');

			return TRUE;
		}
	}

?>

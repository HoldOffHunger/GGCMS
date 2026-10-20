<?php

	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/table.php');

		/*
			The form of a modify.php page -- Add, Edit, Save, Update: a table
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
			  action  Save, for Add.php and Save.php: a Save button and no
			          Delete, and Publish ticked for a new entry; Update
			          otherwise, as Edit and Update submit
			  adding  for Add.php: a blank form for a new entry, whose
			          $that->entry is still the entry it goes under, so
			          nothing is filled in from it; the title or the text
			          has the focus, and the checkboxes take Add's defaults
		*/

	class module_modifyform extends module_spacing {
		public $that;
		public $action;
		public $adding;
		public $form;
		public $table;
		public $subtle_hints;
		public $hints;
		public $record_modules = [];

		public function __construct($args) {
			$this->that = $args['that'];
			$this->action = !empty($args['action']) ? $args['action'] : 'Update';
			$this->adding = $args['adding'];

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

				$this->record_modules[$file] = new $class([
					'that'=>$this->that,
					'adding'=>$this->adding,
				]);
			}

			return $this->record_modules[$file];
		}

			// Single values
			// -------------------------------------------------

			// The entry's own text fields, each named as its heading without
			// spaces, with the checkboxes after it that the site turns on.
			// Adding, they are empty, and the title has the focus unless the
			// site generates titles, when the text has it.

		public function DisplayText($args) {
			$element = $args['element'];
			$name = str_replace(' ', '', $element);

			$text_args = [
				'type'=>'text',
				'name'=>$name,
				'size'=>60,
				'maxlength'=>255,
			];

			if(!$this->adding) {
				$text_args['value'] = $this->that->entry[$name];
			} elseif($element === 'Title' && !$this->that->handler->globals->AutoGenerateTitleDefault()) {
				$text_args['autofocus'] = TRUE;
			}

			$this->form->DisplayFormField($text_args);

			foreach($this->Checkboxes()[$element] as $checkbox_name => $checkbox) {
				$this->DisplayCheckbox(['name'=>$checkbox_name, 'checkbox'=>$checkbox]);
			}

			return TRUE;
		}

		public function DisplayCheckbox($args) {
			$checkbox = $args['checkbox'];
			$globals = $this->that->handler->globals;
			$default = $this->adding ? $checkbox['adding'] : $checkbox['default'];

			if(!$globals->{$checkbox['global']}()) {
				return FALSE;
			}

			print('<input type="checkbox" value="1" name="' . $args['name'] . '" id="' . $args['name'] . '" ');

			if($default === TRUE || (!empty($default) && $globals->{$default}())) {
				print('CHECKED="CHECKED"');
			}

			print(' /> ' . $checkbox['label']);

			return TRUE;
		}

			// Each text field's checkboxes: the global that shows it and,
			// where it is ticked by default, the global that says so --
			// 'default' for an entry being modified, 'adding' for a new one,
			// where TRUE is always.  The defaults commented out are as they
			// were in Edit.php; Add.php had them, and has them still.

		public function Checkboxes() {
			return [
				'Title'=>[
					'autoincrement-title'=>['global'=>'TitleAutoIncrement', 'label'=>'Autoincrement Title', 'default'=>'AutoGenerateTitleDefault', 'adding'=>'AutoGenerateTitleDefault'],
					'title-smart-title-case'=>['global'=>'TitleAutoSmartTitleCase', 'label'=>'Smart Title-Case', 'default'=>'AutoGenerateTitleDefault', 'adding'=>TRUE],
					'title-americanize'=>['global'=>'TitleAmericanize', 'label'=>'Americanize', 'adding'=>'AmericanizeTitleDefault'],		# 'default'=>'AmericanizeTitleDefault'
					'title-de-romanize-numbers'=>['global'=>'TitleDeRomanizeNumbers', 'label'=>'De-Romanize Numbers'],		# 'default'=>'AutoGenerateTitleDefault'
				],
				'Subtitle'=>[
					'subtitle-smart-title-case'=>['global'=>'SubTitleAutoSmartTitleCase', 'label'=>'Smart Title-Case', 'default'=>'AutoGenerateTitleDefault', 'adding'=>TRUE],
					'subtitle-americanize'=>['global'=>'SubtitleAmericanize', 'label'=>'Americanize', 'adding'=>'AmericanizeSubtitleDefault'],		# 'default'=>'AmericanizeSubtitleDefault'
				],
				'List Title'=>[
					'listtitle-smart-title-case'=>['global'=>'ListTitleAutoSmartTitleCase', 'label'=>'Smart Title-Case', 'adding'=>TRUE],		# 'default'=>'AutoGenerateTitleDefault'
					'listtitle-americanize'=>['global'=>'ListTitleAmericanize', 'label'=>'Americanize', 'adding'=>'AmericanizeListTitleDefault'],		# 'default'=>'AmericanizeListTitleDefault'
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

			// Add's Save button has a label before it, as Edit's Update
			// button does; Save's never had one.

		public function DisplayButtons() {
			if($this->action === 'Save') {
				print($this->adding ? '<center>Save This Entry : ' : '<center>');
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

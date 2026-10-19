<?php

		/*
			The modify form's fields: a table for each element
			modifiableRecordTypes() lists, its label on the left and its
			inputs on the right, or the buttons across both for Save.

			The template says which fields its form has, and the switches
			each takes; an element it does not list still gets its table,
			with nothing in it.  Each field is its own module, loaded as
			it is first needed: modify-text-field.php, modify-tag.php and
			so on.

			Labels are ElementDisplay()'s, with the field's hints: the
			subtle one as a hover over the whole row, the active one under
			the label.  Code is labelled for the page it names.

			Switches:
			  fields              element => that field's switches
			  plainlabels         label each field by its element's name,
			                      with no hints; Save and Update
			  noassociationtypes  no association type and subtype
			                      datalists before the fields; Save
		*/

	class module_modifyfields extends module_spacing {
		public $that;
		public $table;
		public $fields;
		public $plainlabels;
		public $noassociationtypes;
		public $subtlehints;
		public $hints;
		public $tagsdisplayed;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->table = new module_table;
			$this->fields = $args['fields'];
			$this->plainlabels = !empty($args['plainlabels']);
			$this->noassociationtypes = !empty($args['noassociationtypes']);
		}

		public function Display() {
			if(!$this->noassociationtypes) {
				$this->that->displayAssociationTypes();
				$this->that->displayAssociationSubTypes();
			}

			$elements = $this->that->modifiableRecordTypes();

			if(!$this->plainlabels) {
				$this->subtlehints = $this->that->SubtleFieldHints();
				$this->hints = $this->that->ActiveFieldHints();
			}

			foreach($elements as $element) {
				$this->DisplayElement(['element'=>$element]);
			}

			return TRUE;
		}

		public function DisplayElement($args) {
			$element = $args['element'];
			$subtle_hint = $this->subtlehints[$element];

			if($element !== 'Save') {
				$this->table->DisplayComponent_StartTable($this->TableArgs(['element'=>$element]));
				$this->DisplayLabel(['element'=>$element]);
				$this->table->DisplayComponent_SeparateCells([
					'cellwidth'=>'80%',
					'cellvalign'=>'top',
				]);
			} else {
				$this->table->DisplayComponent_StartTable($this->TableArgs(['element'=>$element]) + ['cellcolspan'=>2]);
			}

			$this->DisplaySubtleHintStart(['hint'=>$subtle_hint]);
			$this->DisplayField(['element'=>$element]);

			if($subtle_hint) {
				print('</div>');
			}

			$this->table->DisplayComponent_EndTable([]);

			return TRUE;
		}

		public function DisplayLabel($args) {
			$element = $args['element'];

			if($this->plainlabels) {
				print($element);

				return TRUE;
			}

			$this->DisplaySubtleHintStart(['hint'=>$this->subtlehints[$element]]);

			print($this->that->ElementDisplay(['element'=>$element]));

			if($element === 'Code') {
				print(' (For Page)');
			}

			if($this->hints[$element]) {
				print('<div style="max-width:200px;">');
				print($this->hints[$element]);
				print('</div>');
			}

			if($this->subtlehints[$element]) {
				print('</div>');
			}

			return TRUE;
		}

		public function DisplaySubtleHintStart($args) {
			if(!$args['hint']) {
				return FALSE;
			}

			print('<div title="');
			print(htmlentities($args['hint']));
			print('">');

			return TRUE;
		}

			// Save and Update's association rows lean on whether the tag rows printed any; see modify-association.php
		public function DisplayField($args) {
			$element = $args['element'];

			if(!array_key_exists($element, $this->fields)) {
				return FALSE;
			}

			$field = $this->Fields()[$element];
			$class = 'module_' . str_replace('-', '', $field['module']);

			if(!class_exists($class)) {
				ggreq('modules/html/' . $field['module'] . '.php');
			}

			$field_args = ['that'=>$this->that] + (array) $this->fields[$element] + $field['args'];

			if($element === 'Association') {
				$field_args['tagsdisplayed'] = $this->tagsdisplayed;
			}

			$module = new $class($field_args);
			$module->Display();

			if($element === 'Tag') {
				$this->tagsdisplayed = $module->displayed;
			}

			return TRUE;
		}

			// in place of the fields, on a submission that had errors
		public function DisplayUnavailable() {
			$this->table->DisplayComponent_StartTable($this->TableArgs(['element'=>NULL]));

			print('<center><h4>Form Unavailable</h4></center>');

			$this->table->DisplayComponent_EndTable([]);

			return TRUE;
		}

			// Data
			// -------------------------------------------------

		public function TableArgs($args) {
			return [
				'id'=>$args['element'],
				'tableclass'=>'width-100percent blank-element',
				'tableborder'=>'3',
				'cellvalign'=>'top',
			];
		}

			// every field a form may have: the module that prints it, as modify-<name>.php and module_modify<name>, and what it is always given
		public function Fields() {
			return [
				'Title'=>['module'=>'modify-text-field', 'args'=>['name'=>'Title']],
				'Subtitle'=>['module'=>'modify-text-field', 'args'=>['name'=>'Subtitle']],
				'List Title'=>['module'=>'modify-text-field', 'args'=>['name'=>'ListTitle']],
				'List Title Sort Key'=>['module'=>'modify-text-field', 'args'=>['name'=>'ListTitleSortKey']],
				'Code'=>['module'=>'modify-text-field', 'args'=>['name'=>'Code']],
				'Child Adjective'=>['module'=>'modify-text-field', 'args'=>['name'=>'ChildAdjective']],
				'Child Noun'=>['module'=>'modify-text-field', 'args'=>['name'=>'ChildNoun']],
				'Child Noun Plural'=>['module'=>'modify-text-field', 'args'=>['name'=>'ChildNounPlural']],
				'Grand Child Adjective'=>['module'=>'modify-text-field', 'args'=>['name'=>'GrandChildAdjective']],
				'Grand Child Noun'=>['module'=>'modify-text-field', 'args'=>['name'=>'GrandChildNoun']],
				'Grand Child Noun Plural'=>['module'=>'modify-text-field', 'args'=>['name'=>'GrandChildNounPlural']],
				'Entry Translation'=>['module'=>'modify-entry-translation', 'args'=>[]],
				'Availability Start'=>['module'=>'modify-availability', 'args'=>['part'=>'Start']],
				'Availability End'=>['module'=>'modify-availability', 'args'=>['part'=>'End']],
				'Availability Dates'=>['module'=>'modify-availability', 'args'=>[]],
				'Description'=>['module'=>'modify-description', 'args'=>[]],
				'Quote'=>['module'=>'modify-quote', 'args'=>[]],
				'Text Body'=>['module'=>'modify-text-body', 'args'=>[]],
				'Image'=>['module'=>'modify-image', 'args'=>[]],
				'Image Translation'=>['module'=>'modify-image-translation', 'args'=>[]],
				'Tag'=>['module'=>'modify-tag', 'args'=>[]],
				'Link'=>['module'=>'modify-link', 'args'=>[]],
				'Event Date'=>['module'=>'modify-event-date', 'args'=>[]],
				'Association'=>['module'=>'modify-association', 'args'=>[]],
				'Definition'=>['module'=>'modify-definition', 'args'=>[]],
				'Publish'=>['module'=>'modify-publish', 'args'=>[]],
				'Save'=>['module'=>'modify-submit', 'args'=>[]],
			];
		}
	}

?>

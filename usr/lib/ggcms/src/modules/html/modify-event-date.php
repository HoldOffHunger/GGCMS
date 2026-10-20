<?php

		/*
			The entry's event dates on the modify form: a date, time,
			title, description, language and whether it is approximate,
			one row each.  See modify-repeater.php for the rows around
			them.

			The Approximate checkbox is given a selected rather than a
			checked, so a value row's is printed selected="selected"
			whenever the date has an Approximate, and the blank and
			hidden rows' always are.

			Switches:
			  bydate               a date gets a row when it has an
			                       EventDate, not an EventDateTime; Save
			                       and Update
			  dividerbreaks        the divider before a later row is
			                       module_divider's, with its line breaks;
			                       Save and Update
			  approximatefromlast  the blank row's Approximate takes the
			                       last date's, and the hidden row's none;
			                       Save and Update
			  plaintitlelabel      label the title with a plain Title :
			                       rather than TitleDisplay(); Update
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifyeventdate extends module_modifyrepeater {
		public $code = 'event-date';
		public $label = 'Event Date';
		public $bydate;
		public $dividerbreaks;
		public $approximatefromlast;
		public $plaintitlelabel;

		public function __construct($args) {
			parent::__construct($args);

			$this->bydate = !empty($args['bydate']);
			$this->dividerbreaks = !empty($args['dividerbreaks']);
			$this->approximatefromlast = !empty($args['approximatefromlast']);
			$this->plaintitlelabel = !empty($args['plaintitlelabel']);
		}

		public function Values() {
			return $this->that->eventdate;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value'][$this->bydate ? 'EventDate' : 'EventDateTime']);
		}

		public function DisplayDividerStart() {
			if($this->dividerbreaks) {
				return parent::DisplayDividerStart();
			}

			print('<div class="input-divider">');

			return TRUE;
		}

		public function DisplayInputs($args) {
			$eventdate = $args['value'];
			$row = $args['row'];

			print('Date : ');
			$this->DisplayText(['name'=>'EventDate', 'field'=>'EventDate', 'value'=>$eventdate, 'row'=>$row, 'class'=>'datepicker']);

			print('Time : ');
			$this->DisplayText(['name'=>'EventTime', 'field'=>'EventTime', 'value'=>$eventdate, 'row'=>$row]);

			if($this->plaintitlelabel) {
				print('Title : ');
			} else {
				$this->that->TitleDisplay();
			}

			$this->DisplayText(['name'=>'eventdate_Title', 'field'=>'Title', 'value'=>$eventdate, 'row'=>$row]);

			print('Description : ');
			$this->DisplayText(['name'=>'eventdate_Description', 'field'=>'Description', 'value'=>$eventdate, 'row'=>$row]);

			print('Language : ');

			$this->DisplayLanguage([
				'name'=>'eventdate_Language',
				'row'=>$row,
				'selected'=>$row === 'value' ? $eventdate['Language'] : 'en',
			]);

			print('Approximate : ');

			$this->form->DisplayFormField([
				'type'=>'checkbox',
				'name'=>$this->Name(['name'=>'eventdate_Approximate', 'row'=>$row]),
				'selected'=>$this->Approximate(['value'=>$eventdate, 'row'=>$row]),
			]);

			return TRUE;
		}

		public function DisplayText($args) {
			return $this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name($args),
				'size'=>40,
				'maxlength'=>255,
				'value'=>$args['value'][$args['field']],
				'class'=>$args['class'],
			]);
		}

			// Data
			// -------------------------------------------------

			// Approximate()
			// Tests: ModifyEventDateTest::testApproximate()
			// Test file: tests/src/modules/html/ModifyEventDateTest.php
		public function Approximate($args) {
			if($args['row'] === 'value') {
				return $args['value']['Approximate'];
			}

			if($this->approximatefromlast) {
				return $args['row'] === 'blank' ? $this->last['Approximate'] : NULL;
			}

			return 'en';
		}
	}

?>

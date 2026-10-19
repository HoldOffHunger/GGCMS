<?php

		/*
			When the entry is available, on the modify form: a date and a
			time for the start and the end of each range.

			With no part, every range the entry has is shown, its start
			above its end, as on Save and Update.  Edit asks for each end
			on its own, and shows only the first range's.

			Switches:
			  part  Start or End: only that end of the first range
		*/

	class module_modifyavailability extends module_spacing {
		public $that;
		public $form;
		public $part;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->form = new module_form;
			$this->part = $args['part'];
		}

		public function Display() {
			if($this->part) {
				return $this->DisplayEnd(['range'=>$this->that->availabilitydaterange[0], 'part'=>$this->part]);
			}

			foreach($this->that->availabilitydaterange as $range) {
				$this->DisplayEnd(['range'=>$range, 'part'=>'Start']);

				print('<br>');

				$this->DisplayEnd(['range'=>$range, 'part'=>'End']);
			}

			return TRUE;
		}

			// one end of a range: its date, then its time
		public function DisplayEnd($args) {
			foreach(['Date'=>'datepicker', 'Time'=>'timepicker'] as $field => $class) {
				$name = 'Availability' . $args['part'] . $field;

				$this->form->DisplayFormField([
					'type'=>'text',
					'name'=>$name . '[]',
					'size'=>20,
					'class'=>$class,
					'value'=>$args['range'][$name],
				]);
			}

			return TRUE;
		}
	}

?>

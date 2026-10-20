<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's event dates on a modify.php form: the date, time,
			title, description, language and whether it is approximate.

			As Edit always printed them: a row's input-divider is opened
			by hand, without the divider's line breaks, and a new row's
			Approximate checkbox is passed 'en' as selected, which ticks
			nothing but prints selected="selected".
		*/

	class module_modifyeventdate extends module_modifyrecords {
		public function Records() {
			return $this->that->eventdate;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['EventDateTime']);
		}

		public function Slug() {
			return 'event-date';
		}

		public function AddLabel() {
			return 'Add Event Date';
		}

		public function DisplayRowStart($args) {
			if(!$args['first']) {
				print('<div class="input-divider">');
			}

			return TRUE;
		}

		public function DisplayFields($args) {
			$mode = $args['mode'];

			$fields = [
				'EventDate'=>'Date : ',
				'EventTime'=>'Time : ',
				'eventdate_Title'=>NULL,
				'eventdate_Description'=>'Description : ',
			];

			foreach($fields as $name => $label) {
				if($label) {
					print($label);
				} else {
					$this->that->TitleDisplay();
				}

				$this->DisplayText(['name'=>$name, 'record'=>$args['record'], 'mode'=>$mode]);
			}

			print('Language : ');

			$this->DisplayLanguage(['name'=>'eventdate_Language', 'record'=>$args['record'], 'mode'=>$mode]);

			print('Approximate : ');

			$this->form->DisplayFormField([
				'type'=>'checkbox',
				'name'=>$this->Name(['name'=>'eventdate_Approximate', 'mode'=>$mode]),
				'selected'=>$mode === 'row' ? $args['record']['Approximate'] : 'en',
			]);

			return TRUE;
		}

		public function DisplayText($args) {
			$name = $args['name'];

			$text_args = [
				'type'=>'text',
				'name'=>$this->Name($args),
				'size'=>40,
				'maxlength'=>255,
			];

			if($args['mode'] === 'row') {
				$text_args['value'] = $args['record'][str_replace('eventdate_', '', $name)];
			}

			if($name === 'EventDate') {
				$text_args['class'] = 'datepicker';
			}

			$this->form->DisplayFormField($text_args);

			return TRUE;
		}
	}

?>

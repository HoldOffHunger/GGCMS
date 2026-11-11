<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's text bodies on a modify.php form: the text, its
			source, the three cleaning switches, whether the spellcheck skips
			it, and its language -- the switches on every row, the hidden one
			Add Text copies included.
		*/

	class module_modifytextbody extends module_modifyrecords {
		public function Records() {
			return $this->that->textbody;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['Text']);
		}

		public function Slug() {
			return 'text';
		}

		public function AddLabel() {
			return 'Add Text';
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];

			$this->DisplayText($args);

			$this->DisplaySource([
				'name'=>'textbody_Source',
				'record'=>$record,
				'mode'=>$mode,
				'class'=>$this->Replicated($args) ? 'textbody_Source' : NULL,
			]);

			$this->DisplaySwitches(['record'=>$record, 'mode'=>$mode]);

			print('Language : ');

			$this->DisplayLanguage(['name'=>'textbody_Language', 'record'=>$record, 'mode'=>$mode]);

			return TRUE;
		}

			// Adding, the blank text has the focus where the site generates
			// titles; otherwise the title has it.

		public function DisplayText($args) {
			$mode = $args['mode'];

			$text_args = [
				'type'=>'textarea',
				'name'=>$this->Name(['name'=>'Text', 'mode'=>$mode]),
				'cols'=>60,
				'rows'=>20,
				'class'=>'float-left',
			];

			if($mode === 'row') {
				$text_args['value'] = $args['record']['Text'];
			}

			if($this->Replicated($args) && $this->that->handler->globals->AutoGenerateTitleDefault()) {
				$text_args['autofocus'] = TRUE;
			}

			$this->form->DisplayFormField($text_args);

			return TRUE;
		}

			// Add's blank text, whose source the Replicate button fills.

		public function Replicated($args) {
			return $this->adding && $args['mode'] === 'blank';
		}

			/*
				A No/Yes select for each switch, on every text body -- the
				hidden row Add Text copies as well, so a text added on the page
				has them too.

				They were checkboxes until 11 November 2026, and a checkbox
				posts nothing when it is clear.  The Prepare stage reads these
				by position, one per text body, so with two texts and only the
				second ticked Spell Check Immune, the one value that came went
				to the first text and the second lost it.  A select always
				posts, so the nth value is always the nth text's.
			*/

		public function DisplaySwitches($args) {
			$switches = [
				'textbody_StripURLs'=>'Strip URLs : ',
				'textbody_AmericanizeVocabulary'=>'Americanize Vocabulary : ',
				'textbody_HTMLFormatting'=>'HTML Formatting : ',
				'textbody_SpellCheckImmune'=>'Spell Check Immune : ',
			];

			print("<br>");

			foreach($switches as $name => $label) {
				print($label);

				$this->form->DisplayFormField([
					'type'=>'select',
					'name'=>$this->Name(['name'=>$name, 'mode'=>$args['mode']]),
					'options'=>[
						['optionvalue'=>'', 'optiontitle'=>'No'],
						['optionvalue'=>'1', 'optiontitle'=>'Yes'],
					],
					'selected'=>$this->Checked(['name'=>$name, 'record'=>$args['record']]) ? '1' : '',
				]);

				print("<br>");
			}

			return TRUE;
		}

			// A new entry's text is cleaned up by default, and formatted as
			// HTML where the site says so; an entry being modified has them
			// unticked.  Spell Check Immune is stored, not a cleaning, so it
			// shows what the text has.

		public function Checked($args) {
			if($args['name'] === 'textbody_SpellCheckImmune') {
				return $args['record'] && $args['record']['SpellCheckImmune'];
			}

			if(!$this->adding) {
				return FALSE;
			}

			if($args['name'] === 'textbody_HTMLFormatting') {
				return $this->that->handler->globals->AddEntryHTMLFormatting();
			}

			return TRUE;
		}
	}

?>

<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			A book's publication details on a modify.php form, as a
			citation prints them: its authors, editors and translators,
			publisher, edition, year and ISBN.  Plain text rather than
			Association roles, so the people are written as the book
			credits them without each needing an entry of their own.

			One per book in practice, though the form allows more, as
			every record kind here does.
		*/

	class module_modifypublication extends module_modifyrecords {
		public function Records() {
			return $this->that->publication;
		}

		public function IsShown($args) {
			$record = $args['record'];

			if(!$record) {
				return FALSE;
			}

			foreach($this->Fields() as $field) {
				if(strlen((string)$record[$field])) {
					return TRUE;
				}
			}

			return FALSE;
		}

		public function Slug() {
			return 'publication';
		}

		public function AddLabel() {
			return 'Add Publication';
		}

		public function Fields() {
			return ['Authors', 'Editors', 'Translators', 'Publisher', 'Edition', 'Year', 'ISBN'];
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];

			$sizes = [
				'Authors'=>[60, 1024],
				'Editors'=>[60, 1024],
				'Translators'=>[60, 1024],
				'Publisher'=>[40, 255],
				'Edition'=>[24, 255],
				'Year'=>[8, 32],
				'ISBN'=>[17, 32],
			];

			$first = TRUE;

			foreach($sizes as $field => $size) {
				if(!$first) {
					print(in_array($field, ['Edition', 'Year', 'ISBN'], TRUE) ? ' ' : '<br>');
				}

				$first = FALSE;

				print($field . ' : ');

				$text_args = [
					'type'=>'text',
					'name'=>$this->Name(['name'=>'publication_' . $field, 'mode'=>$mode]),
					'size'=>$size[0],
					'maxlength'=>$size[1],
				];

				if($mode === 'row') {
					$text_args['value'] = $record[$field];
				}

				$this->form->DisplayFormField($text_args);
			}

			return TRUE;
		}
	}

?>

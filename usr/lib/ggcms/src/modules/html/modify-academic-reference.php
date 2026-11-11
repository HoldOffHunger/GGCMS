<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's academic references on a modify.php form: the book
			that backs it, where in the book -- headword, volume, chapter,
			page -- and whether the book supports what the entry says.

			The book is chosen from the entries under the site's Reference
			branch, by way of modify.php's ReferenceChoices().  A site with
			no Reference branch yet gets the raw entry id instead, as
			Association does when it shows raw ids.

			Every input is named academicreference_<Field>, which
			SetRecordFromQuery() strips back to the column: Page, Note and
			the rest would otherwise share a name with another kind's input.
		*/

	class module_modifyacademicreference extends module_modifyrecords {
		public function Records() {
			return $this->that->academicreference;
		}

		public function IsShown($args) {
			return $args['record'] && (int)$args['record']['ReferenceEntryid'];
		}

		public function Slug() {
			return 'academicreference';
		}

		public function AddLabel() {
			return 'Add Academic Reference';
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];

			print('Book : ');
			$this->DisplayBook($args);

			print('Headword : ');
			$this->DisplayText(['field'=>'Headword', 'size'=>24, 'maxlength'=>255, 'record'=>$record, 'mode'=>$mode]);

			print('<br>Volume : ');
			$this->DisplayText(['field'=>'VolumeNumber', 'size'=>6, 'maxlength'=>32, 'record'=>$record, 'mode'=>$mode]);
			print(' Volume Title : ');
			$this->DisplayText(['field'=>'VolumeTitle', 'size'=>24, 'maxlength'=>255, 'record'=>$record, 'mode'=>$mode]);

			print('<br>Chapter : ');
			$this->DisplayText(['field'=>'ChapterNumber', 'size'=>6, 'maxlength'=>32, 'record'=>$record, 'mode'=>$mode]);
			print(' Chapter Title : ');
			$this->DisplayText(['field'=>'ChapterTitle', 'size'=>24, 'maxlength'=>255, 'record'=>$record, 'mode'=>$mode]);

			print('<br>Page : ');
			$this->DisplayText(['field'=>'Page', 'size'=>6, 'maxlength'=>32, 'record'=>$record, 'mode'=>$mode]);

			print(' Verdict : ');
			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$this->Name(['name'=>'academicreference_Verdict', 'mode'=>$mode]),
				'options'=>$this->VerdictOptions(),
				'selected'=>($mode === 'row' && strlen((string)$record['Verdict'])) ? $record['Verdict'] : 'supports',
			]);

			print('<br>Note : ');
			$this->DisplayText(['field'=>'Note', 'size'=>60, 'maxlength'=>512, 'record'=>$record, 'mode'=>$mode]);

			return TRUE;
		}

			// A text input for one column, filled from the record in a row.

		public function DisplayText($args) {
			$field = $args['field'];

			$text_args = [
				'type'=>'text',
				'name'=>$this->Name(['name'=>'academicreference_' . $field, 'mode'=>$args['mode']]),
				'size'=>$args['size'],
				'maxlength'=>$args['maxlength'],
			];

			if($args['mode'] === 'row') {
				$text_args['value'] = $args['record'][$field];
			}

			$this->form->DisplayFormField($text_args);

			return TRUE;
		}

		public function DisplayBook($args) {
			$mode = $args['mode'];
			$name = $this->Name(['name'=>'academicreference_ReferenceEntryid', 'mode'=>$mode]);
			$chosen = $mode === 'row' ? (string)$args['record']['ReferenceEntryid'] : '';
			$books = $this->that->ReferenceChoices();

			if(!count($books)) {
				$id_args = ['type'=>'text', 'name'=>$name, 'size'=>8, 'maxlength'=>11];

				if($mode === 'row') {
					$id_args['value'] = $chosen;
				}

				$this->form->DisplayFormField($id_args);

				return TRUE;
			}

			$options = [[
				'optionvalue'=>'',
				'optiontitle'=>'(none)',
				'optionmouseover'=>'No book',
			]];

			foreach($books as $book) {
				$options[] = [
					'optionvalue'=>$book['id'],
					'optiontitle'=>mb_strlen($book['Title']) > 50 ? mb_substr($book['Title'], 0, 50) . '...' : $book['Title'],
					'optionmouseover'=>'[' . $book['id'] . '] ' . $book['Title'],
				];
			}

			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$name,
				'options'=>$options,
				'selected'=>$chosen,
			]);

			return TRUE;
		}

		public function VerdictOptions() {
			$options = [];

			foreach($this->that->AcademicReferenceVerdicts() as $verdict) {
				$options[] = [
					'optionvalue'=>$verdict,
					'optiontitle'=>$verdict,
					'optionmouseover'=>'Set Verdict to ' . $verdict,
				];
			}

			return $options;
		}
	}

?>

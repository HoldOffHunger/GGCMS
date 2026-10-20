<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's associations on a modify.php form: the entry it is
			associated with, by raw id or chosen from a list, and the
			association's type and subtype, typed with the suggestions
			displayAssociationTypes() printed above the form.

			As Edit always printed them, the blank row's type and subtype
			are those of the last association looked at, shown or not --
			the loop's variable was still set -- and are blank when there
			were none.
		*/

	class module_modifyassociation extends module_modifyrecords {
		public function Records() {
			return $this->that->association;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['ChosenEntryid']);
		}

		public function Slug() {
			return 'association';
		}

		public function AddLabel() {
			return 'Add Association';
		}

		public function DisplayFields($args) {
			$mode = $args['mode'];
			$record = $mode === 'blank' ? $this->last_record : $args['record'];

			print('Entry ID Associated With : ');

			$this->DisplayChosenEntry($args);

			print('Type : ');
			print($this->TypeInput(['list'=>'Association_Type', 'name'=>'association_Type', 'field'=>'Type', 'record'=>$record, 'mode'=>$mode]));

			print('SubType : ');
			print($this->TypeInput(['list'=>'Association_SubType', 'name'=>'association_SubType', 'field'=>'SubType', 'record'=>$record, 'mode'=>$mode]));

			return TRUE;
		}

		public function DisplayChosenEntry($args) {
			$mode = $args['mode'];
			$name = $this->Name(['name'=>'ChosenEntryid', 'mode'=>$mode]);

			if($this->that->showAssociationRawIds()) {
				$id_args = ['type'=>'text', 'name'=>$name, 'size'=>40, 'maxlength'=>255];

				if($mode === 'row') {
					$id_args['value'] = $args['record']['ChosenEntryid'];
				}

				$this->form->DisplayFormField($id_args);

				return TRUE;
			}

			$associations_args = ['defaultblankoption'=>TRUE];

			if($mode === 'row') {
				$associations_args = ['selected'=>$args['record']['ChosenEntryid'], 'defaultblankoption'=>TRUE];
			}

			print('<select name="' . $name . '">');
			$this->that->displayAssociations($associations_args);
			print('</select>');

			return TRUE;
		}

			// The hidden row's inputs have no value at all; the others' have
			// one, empty or not.

		public function TypeInput($args) {
			$input = '<input type="text" list="' . $args['list'] . '" name="' . $this->Name($args) . '" size="40" maxlength="255"';

			if($args['mode'] !== 'hidden') {
				$input .= ' value="' . $args['record'][$args['field']] . '"';
			}

			return $input . '>';
		}
	}

?>

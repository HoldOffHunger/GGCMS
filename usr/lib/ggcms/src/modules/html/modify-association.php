<?php

		/*
			The entry's associations on the modify form: the entry it is
			associated with, and the association's type and subtype, one
			row each.  See modify-repeater.php for the rows around them.

			The associated entry is a raw id box where
			showAssociationRawIds() says so, and otherwise a select of the
			entries it may be.  On Edit the blank row's type and subtype
			start as the last association's.

			Switches:
			  followtags     whether a later row gets a Remove button and
			                 its divider closed goes by whether the tag
			                 rows printed any, from tagsdisplayed, rather
			                 than by this field's own rows; Save and
			                 Update
			  tagsdisplayed  the tag field's displayed, for followtags
			  rawidrows      the rows whose associated entry is always a
			                 raw id box; hidden on Save, every row on
			                 Update
			  noblanktypes   the blank row's type and subtype start empty;
			                 Save and Update
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifyassociation extends module_modifyrepeater {
		public $code = 'association';
		public $label = 'Association';
		public $followtags;
		public $tagsdisplayed;
		public $rawidrows;
		public $noblanktypes;

		public function __construct($args) {
			parent::__construct($args);

			$this->followtags = !empty($args['followtags']);
			$this->tagsdisplayed = $args['tagsdisplayed'];
			$this->rawidrows = !empty($args['rawidrows']) ? $args['rawidrows'] : [];
			$this->noblanktypes = !empty($args['noblanktypes']);
		}

		public function Values() {
			return $this->that->association;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['ChosenEntryid']);
		}

		public function Follows() {
			return $this->followtags ? $this->tagsdisplayed : $this->displayed;
		}

		public function DisplayInputs($args) {
			$association = $args['value'];
			$row = $args['row'];

			print('Entry ID Associated With : ');

			if(in_array($row, $this->rawidrows) || $this->that->showAssociationRawIds()) {
				$this->form->DisplayFormField([
					'type'=>'text',
					'name'=>$row === 'hidden' ? 'ChosenEntryid-Hidden' : 'ChosenEntryid[]',
					'size'=>40,
					'maxlength'=>255,
					'value'=>$association['ChosenEntryid'],
				]);
			} else {
				$this->DisplayEntries(['value'=>$association, 'row'=>$row]);
			}

				// the value row starts from its own type and subtype, Edit's blank row from the last association's
			$typed = $row === 'value' || ($row === 'blank' && !$this->noblanktypes);
			$types = $row === 'value' ? $association : $this->last;

			print('Type : ');
			$this->DisplayType(['field'=>'Type', 'value'=>$types, 'typed'=>$typed, 'row'=>$row]);

			print('SubType : ');
			$this->DisplayType(['field'=>'SubType', 'value'=>$types, 'typed'=>$typed, 'row'=>$row]);

			return TRUE;
		}

		public function DisplayEntries($args) {
			$select_args = ['defaultblankoption'=>TRUE];

			if($args['row'] === 'value') {
				$select_args = ['selected'=>$args['value']['ChosenEntryid'], 'defaultblankoption'=>TRUE];
			}

			print('<select name="' . ($args['row'] === 'hidden' ? 'ChosenEntryid-Hidden' : 'ChosenEntryid[]') . '">');
			$this->that->displayAssociations($select_args);
			print('</select>');

			return TRUE;
		}

		public function DisplayType($args) {
			$value = $args['typed'] ? ' value="' . $args['value'][$args['field']] . '"' : '';

			print('<input type="text" list="Association_' . $args['field'] . '" name="' . $this->Name(['name'=>'association_' . $args['field'], 'row'=>$args['row']]) . '" size="40" maxlength="255"' . $value . '>');

			return TRUE;
		}
	}

?>

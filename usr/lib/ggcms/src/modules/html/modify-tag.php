<?php

		/*
			The entry's tags on the modify form: the tag and its language,
			one row each.  See modify-repeater.php for the rows around
			them.

			Save and Update's Association reads $this->displayed after
			this has run; see modify-association.php.

			Switches:
			  notagclass          the tag's box has no tag class; Save
			                      and Update
			  nolanguagedefault   the blank row's language has no
			                      default; Save
			  hiddenlanguagename  the hidden row's language's name in
			                      place of tag_Language-Hidden; Save
			                      names it tag_Language[]
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifytag extends module_modifyrepeater {
		public $code = 'tag';
		public $label = 'Tag';
		public $notagclass;
		public $nolanguagedefault;
		public $hiddenlanguagename;

		public function __construct($args) {
			parent::__construct($args);

			$this->notagclass = !empty($args['notagclass']);
			$this->nolanguagedefault = !empty($args['nolanguagedefault']);
			$this->hiddenlanguagename = !empty($args['hiddenlanguagename']) ? $args['hiddenlanguagename'] : 'tag_Language-Hidden';
		}

		public function Values() {
			return $this->that->tag;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Tag']);
		}

		public function DisplayInputs($args) {
			$tag = $args['value'];
			$row = $args['row'];

			print('Tag : ');

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$row === 'hidden' ? 'Tag-Hidden' : 'Tag[]',
				'size'=>40,
				'value'=>$tag['Tag'],
				'maxlength'=>255,
				'class'=>$this->notagclass ? NULL : 'tag',
			]);

			print('Language : ');

			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$row === 'hidden' ? $this->hiddenlanguagename : 'tag_Language[]',
				'options'=>$this->that->SelectableLanguages,
				'selected'=>$this->Language(['value'=>$tag, 'row'=>$row]),
			]);

			return TRUE;
		}

			// Data
			// -------------------------------------------------

		public function Language($args) {
			if($args['row'] === 'value') {
				return $args['value']['Language'];
			}

			if($this->nolanguagedefault && $args['row'] === 'blank') {
				return NULL;
			}

			return 'en';
		}
	}

?>

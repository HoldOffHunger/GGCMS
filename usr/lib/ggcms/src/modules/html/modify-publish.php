<?php

		/*
			The entry's Publish checkbox on the modify form, checked when
			the entry is published.

			Switches:
			  alwayschecked    checked whatever the entry says; Save, which
			                   commented its check out
			  unclosedchecked  the CHECKED attribute's value is printed
			                   without its closing quote; Update
		*/

	class module_modifypublish extends module_spacing {
		public $that;
		public $alwayschecked;
		public $unclosedchecked;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->alwayschecked = !empty($args['alwayschecked']);		# FIXME: Globals-ify -- Save's note, where it commented out the check
			$this->unclosedchecked = !empty($args['unclosedchecked']);
		}

		public function Display() {
			print('<input type="checkbox" name="Publish" value="1"');

			if($this->alwayschecked || $this->that->entry['Publish'] === 1) {
				print($this->unclosedchecked ? ' CHECKED="CHECKED' : ' CHECKED="CHECKED"');
			}

			print('>');

			return TRUE;
		}
	}

?>

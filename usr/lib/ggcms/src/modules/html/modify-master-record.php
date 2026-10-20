<?php

		/*
			A note at the top of the modify page when the entry is the
			site's primary record, the one with no parents: that its Code
			should be the host's domain, in its case.

			Switches:
			  unpadded  the box around it has no margin or padding class;
			            Update, which never set one
		*/

	class module_modifymasterrecord extends module_spacing {
		public $that;
		public $divider;
		public $generic_list;
		public $unpadded;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->divider = new module_divider;
			$this->generic_list = new module_genericlist;
			$this->unpadded = !empty($args['unpadded']);
		}

		public function Display() {
			if(count($this->that->record_list)) {
				return FALSE;
			}

			$this->divider->displaystart($this->unpadded ? NULL : ['class'=>'margin-5px padding-5px']);

			$this->generic_list->Display([
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>[
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					],
				],
				'list'=>[
					[
						'<nobr>Editing Primary Entry Record:</nobr>',
						'The Code should be the case-sensitive version of the domain URL for this host.',
					],
				],
			]);

			$this->divider->displayend([]);

			return TRUE;
		}
	}

?>

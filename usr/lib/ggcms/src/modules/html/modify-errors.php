<?php

		/*
			What went wrong with a submission to the modify form: one
			heading, There was an error with your submission, then a
			table for each error.  An admin is shown the admin errors
			before everyone's.

			Switches:
			  unpadded  the boxes around them have no margin or padding
			            class; Save and Update, which never set one
		*/

	class module_modifyerrors extends module_spacing {
		public $that;
		public $divider;
		public $generic_list;
		public $unpadded;
		public $headed;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->divider = new module_divider;
			$this->generic_list = new module_genericlist;
			$this->unpadded = !empty($args['unpadded']);
		}

		public function Display() {
			$this->headed = FALSE;

			if($this->that->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin() && $this->that->admin_errors) {
				$this->DisplayErrors(['errors'=>$this->that->admin_errors_display]);
			}

			if($this->that->errors) {
				$this->DisplayErrors(['errors'=>$this->that->errors_display]);
			}

			return TRUE;
		}

			// the heading, unless it has been printed already, and the errors
		public function DisplayErrors($args) {
			if(!$this->headed) {
				$this->DisplayBox([
					'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'cellclass'=>[
						'border-1px vertical-top horizontal-center font-size-150percent',
						'border-1px width-100percent vertical-top horizontal-center',
					],
					'list'=>[['There was an error with your submission.']],
				]);

				$this->headed = TRUE;
			}

			foreach($args['errors'] as $error) {
				$this->DisplayBox([
					'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'cellclass'=>[
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					],
					'list'=>$error,
				]);
			}

			return TRUE;
		}

		public function DisplayBox($args) {
			$this->divider->displaystart($this->unpadded ? NULL : ['class'=>'margin-5px padding-5px']);

			$this->generic_list->Display([
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>$args['tableclass'],
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>$args['cellclass'],
				],
				'list'=>$args['list'],
			]);

			$this->divider->displayend([]);

			return TRUE;
		}
	}

?>

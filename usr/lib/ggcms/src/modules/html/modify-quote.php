<?php

	require_once(GGCMS_DIR . 'modules/html/modify-description.php');

		/*
			An entry's quotes on a modify.php form: entered exactly as a
			description is, under their own names.
		*/

	class module_modifyquote extends module_modifydescription {
		public $records_name = 'quote';
		public $text_name = 'Quote';
		public $prefix = 'quote';
		public $slug = 'quote';
		public $add_label = 'Add Quote';
		public $blank_maxlength = TRUE;
	}

?>

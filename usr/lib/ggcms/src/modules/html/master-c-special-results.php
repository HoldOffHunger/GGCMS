<?php

	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/form.php');

		/*
			What master-c's Special action did, under the page's heading: a
			bordered box holding a form holding a paragraph of the
			script's results, which masterc::Special() fills.

			The box and the form are left open, as the template left them:
			master-c/Special.php never ended its form and closed one divider
			of two.
		*/

	class module_mastercspecialresults extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display() {
			$divider = new module_divider;
			$form = new module_form;

			$divider->displaystart(['class'=>'horizontal-center width-70percent margin-top-5px border-2px']);

			$form->StartForm([
				'action'=>0,
				'method'=>'post',
				'files'=>0,
				'formclass'=>'margin-0px',
			]);

			$divider->displaystart(['class'=>'horizontal-center width-100percent margin-top-5px margin-bottom-5px']);

			print('<p class="horizontal-left margin-5px font-family-tahoma">');
			print($this->that->results);
			print('</p>');

			$divider->displayend([]);

			return TRUE;
		}
	}

?>

<?php

	/*
		Which links get a row on the modify form: Edit lists one with a
		title or a URL, Save and Update only one with a URL.
	*/

	require_once(GGCMS_DIR . 'modules/spacing.php');
	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/modify-repeater.php');
	require_once(GGCMS_DIR . 'modules/html/modify-link.php');

	class ModifyLinkTest extends GGCMSTestCase {
		public function testAccepts() {
			$edit = new module_modifylink(['that'=>(object)[]]);
			$save = new module_modifylink(['that'=>(object)[], 'urlonly'=>TRUE]);

			$titled = ['Title'=>'A site', 'URL'=>''];
			$addressed = ['Title'=>'', 'URL'=>'https://example.com/'];
			$empty = ['Title'=>'', 'URL'=>''];

			$this->assertTrue($edit->Accepts(['value'=>$titled]));
			$this->assertTrue($edit->Accepts(['value'=>$addressed]));
			$this->assertFalse($edit->Accepts(['value'=>$empty]));
			$this->assertFalse((bool) $edit->Accepts(['value'=>NULL]));

			$this->assertFalse($save->Accepts(['value'=>$titled]), 'urlonly: a title alone is not enough');
			$this->assertTrue($save->Accepts(['value'=>$addressed]));
		}
	}

?>

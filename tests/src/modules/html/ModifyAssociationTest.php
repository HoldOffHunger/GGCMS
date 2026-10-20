<?php

	/*
		Whether a later association row follows another: by its own rows
		on Edit, by the tag rows above it on Save and Update.
	*/

	require_once(GGCMS_DIR . 'modules/spacing.php');
	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/modify-repeater.php');
	require_once(GGCMS_DIR . 'modules/html/modify-association.php');

	class ModifyAssociationTest extends GGCMSTestCase {
		public function testFollows() {
			$edit = new module_modifyassociation(['that'=>(object)[]]);
			$edit->displayed = 1;

			$this->assertSame(1, $edit->Follows());

			$save = new module_modifyassociation(['that'=>(object)[], 'followtags'=>TRUE, 'tagsdisplayed'=>0]);
			$save->displayed = 1;

			$this->assertSame(0, $save->Follows(), 'followtags: the tag rows decide, not its own');

			$no_tags = new module_modifyassociation(['that'=>(object)[], 'followtags'=>TRUE, 'tagsdisplayed'=>NULL]);

			$this->assertNull($no_tags->Follows(), 'with no Tag element there is nothing to follow');
		}
	}

?>

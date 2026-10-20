<?php

	/*
		The Approximate checkbox's selected on each event date row.
	*/

	require_once(GGCMS_DIR . 'modules/spacing.php');
	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/modify-repeater.php');
	require_once(GGCMS_DIR . 'modules/html/modify-event-date.php');

	class ModifyEventDateTest extends GGCMSTestCase {
		public function testApproximate() {
			$date = ['Approximate'=>'year'];

			$edit = new module_modifyeventdate(['that'=>(object)[]]);
			$edit->last = ['Approximate'=>'day'];

			$this->assertSame('year', $edit->Approximate(['value'=>$date, 'row'=>'value']));
			$this->assertSame('en', $edit->Approximate(['value'=>NULL, 'row'=>'blank']));
			$this->assertSame('en', $edit->Approximate(['value'=>NULL, 'row'=>'hidden']));

			$save = new module_modifyeventdate(['that'=>(object)[], 'approximatefromlast'=>TRUE]);
			$save->last = ['Approximate'=>'day'];

			$this->assertSame('year', $save->Approximate(['value'=>$date, 'row'=>'value']));
			$this->assertSame('day', $save->Approximate(['value'=>NULL, 'row'=>'blank']), 'the blank row takes the last date looked at');
			$this->assertNull($save->Approximate(['value'=>NULL, 'row'=>'hidden']));
		}
	}

?>

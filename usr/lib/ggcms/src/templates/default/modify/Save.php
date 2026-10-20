<?php
	print('<div style="font-family:arial, tahoma;">');
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
	ggreq('modules/html/text.php');
	$text = new module_text;
	
	ggreq('modules/html/form.php');
	$form = new module_form;
	
	ggreq('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/html/table.php');
	$table = new module_table;
	
	ggreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	ggreq('modules/html/header.php');
	$header = new module_header;
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-header.php');
	$modifyheader = new module_modifyheader([
		'that'=>$this,
		'heading'=>!empty($this->saveattemptresults) ? 'Saved!' : 'Save result',
		'subpage'=>!empty($this->saveattemptresults) ? 'Saved Entry' : 'Save Result',
	]);
	$modifyheader->Display();

	
			// Display Notices
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-notices.php');
	$modifynotices = new module_modifynotices([
		'that'=>$this,
		'adding'=>TRUE,
	]);
	$modifynotices->DisplayStatus();
	$modifynotices->DisplayErrors();
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = array(
		'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
	);
	
	$divider->displaystart($divider_padding_start_args);
	
			// Display Form Save Results
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-results.php');
	$modifyresults = new module_modifyresults(['that'=>$this]);
	
			// Re-Display Form
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-form.php');
	$modifyform = new module_modifyform([
		'that'=>$this,
		'action'=>'Save',
	]);
	
	if($this->saveattemptresults) {
		$modifyresults->Display();
	} else {
		$modifyform->Display();
	}
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$modifyform->DisplayEnd();
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
	$bottom_navigation_args = [
		'thispage'=>'Save',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
	
?>
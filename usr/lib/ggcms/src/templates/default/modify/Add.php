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
		'heading'=>'Adding Entry To',
		'subpage'=>'Add Entry',
	]);
	$modifyheader->Display();
	
			// Display Notices
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-notices.php');
	$modifynotices = new module_modifynotices([
		'that'=>$this,
		'padded'=>TRUE,
		'adding'=>TRUE,
	]);
	$modifynotices->DisplayEntryInfo();
	$modifynotices->DisplayErrors();
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
	];
	
	$divider->displaystart($divider_padding_start_args);
	
	ggreq('modules/html/modify-form.php');
	$modifyform = new module_modifyform([
		'that'=>$this,
		'action'=>'Save',
		'adding'=>TRUE,
	]);
	
	if(count($this->admin_errors) || count($this->errors)) {
				// Display Form Elements : Unavailable Message
			
			// -------------------------------------------------------------
			
		$table_start_args = [
			'tableclass'=>'width-100percent blank-element',
			'tableborder'=>'3',
			'cellvalign'=>'top',
		];
		$table->DisplayComponent_StartTable($table_start_args);
		
		print('<center><h4>Form Unavailable</h4></center>');
		
		$table_end_args = [
		];
		$table->DisplayComponent_EndTable($table_end_args);
	} else {
				// Display Form
			
			// -------------------------------------------------------------
		
		$modifyform->Display();
		$modifyform->DisplayEnd();
	}
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
	$bottom_navigation_args = [
		'thispage'=>'Add',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
?>
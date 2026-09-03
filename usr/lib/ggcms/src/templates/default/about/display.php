<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	classreq('modules/spacing.php');
	
	classreq('modules/html/text.php');
	$text = new module_text;
	
	classreq('modules/html/form.php');
	$form = new module_form;
	
	classreq('modules/html/divider.php');
	$divider = new module_divider;
	
	classreq('modules/html/table.php');
	$table = new module_table;
	
	classreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	classreq('modules/html/languages.php');
	$languages_args = [
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$languages = new module_languages($languages_args);
	
	classreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
	classreq('modules/html/entry-header.php');
	classreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'About ' . $this->master_record['Title'],
		'sub_text'=>'A Little Bit More about ' . $this->master_record['Subtitle'],
		'sub2_text'=>$sub2_text,
		'sub_title'=>$sub_title,
	]);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$entryindexheader->Display();
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	if($this->authentication_object->user_session['UserAdmin.id']) {
		classreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_navigation_args = [
		'class'=>'width-95percent horizontal-center margin-top-14px border-2px',
	];
	
	$divider_instruction_area_start_args = [
		'class'=>'width-50percent border-1px horizontal-center margin-top-22px',
	];
	
			// Get About Info Language
		
		// -------------------------------------------------------------
	
	$about_header_title_text = $this->handler->abstractglobals->script->GetAboutSubHeader();
	$about_content_text = $this->handler->abstractglobals->script->GetAboutContent();
	
			// Display About Info
		
		// -------------------------------------------------------------
	
	$divider->displaystart($divider_instruction_area_start_args);
	
	print('<center><h2 class="margin-5px font-family-tahoma">' . $about_header_title_text . '</h2></center>');
	
	print('<div class="padding-5px horizontal-left font-family-arial">' . $about_content_text . '</div>');
	
	$divider->displayend($divider_end_args);
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$languages->Display();
	
	$bottom_navigation_args = [
		'thispage'=>'About',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
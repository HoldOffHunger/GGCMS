<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreqonce('modules/spacing.php');
	
	ggreqonce('modules/html/text.php');
	$text = new module_text;
	
	ggreqonce('modules/html/form.php');
	$form = new module_form;
	
	ggreqonce('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreqonce('modules/html/table.php');
	$table = new module_table;
	
	ggreqonce('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	ggreqonce('modules/html/header.php');
	$header = new module_header;
	
	ggreqonce('modules/html/languages.php');
	$languages_args = [
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$languages = new module_languages($languages_args);
	
	ggreqonce('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreqonce('modules/html/entry-header.php');
	ggreqonce('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->master_record['Title'] . ' Social Media Share Center',
	]);
	
	$entryindexheader->Display();
	
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
	
	$about_header_title_text = 'Social Sharing';
	$about_content_text = 'Share Lineup';
	
			// Display About Info
		
		// -------------------------------------------------------------
		
				// Display About Info : Start
			
			// -------------------------------------------------------------
	
	$divider->displaystart($divider_instruction_area_start_args);
		
				// Display About Info : Content
			
			// -------------------------------------------------------------
	
print('<center><h2 class="margin-5px font-family-tahoma">' . $about_header_title_text . ' :</h2></center>');
	
	
	print('<div class="padding-5px horizontal-left font-family-arial">');
	
	print('<p>Instagram : </p>');
	
	print('<ul>');
	
	print('<li>');
	print('<a href="socialmedia.php?action=login_instagram">');
	print('Login');
	print('</a>');
	print('</li>');
	
	print('<li>');
	print('<a href="socialmedia.php?action=post_instagram">');
	print('Post');
	print('</a>');
	print('</li>');

	print('</ul>');
		
				// Display About Info : End
			
			// -------------------------------------------------------------
	
	print('</div>');
	
	$divider->displayend($divider_end_args);
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
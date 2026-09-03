<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreqonce('modules/spacing.php');
	
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
	
	ggreqonce('modules/html/entry-header.php');
	ggreqonce('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'Configure Submission Details for ' . $this->entry['Title'],
		'sub_text'=>'Form-Maker - ' . $this->entry['Subtitle'],
		'sub2_text'=>$sub2_text,
		'sub_title'=>$sub_title,
	]);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$entryindexheader->Display();
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	if($this->authentication_object->user_session['UserAdmin.id']) {
		ggreqonce('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
			// Display About Info
		
		// -------------------------------------------------------------
	
	print('<center><h2 class="margin-5px font-family-tahoma">' . $this->entry['Title'] . ' Form-Maker</h2>');
	
	print('<div class="font-family-tahoma" style="border: 1px solid black; width:80%;">');
	
	print('<center><h3>');
	print('Configure Submission Details for ' . $this->entry['Title']);
	print('</h3></center>');
	
	print('<div class="horizontal-left font-family-tahoma" style="border: 1px solid black;">');
	
	print('<ul>');
	
	print('<li><a href="formmaker.php?action=newform">Create New Form</a></li>');
	print('<li><a href="formmaker.php?action=list">Modify Pre-Existing Form</a></li>');
	
	print('</ul>');
	
	print('</div>');
	
	print('<div style="clear:left;"></div>');
	
	print('</div>');
	print('</center>');
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
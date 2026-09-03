<?php
		
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
	
	ggreq('modules/html/languages.php');
	$languages_args = [
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$languages = new module_languages($languages_args);
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Get Info Header Language
		
		// -------------------------------------------------------------
	
	if($this->language_object->getLanguageCode() === 'en') {
		$div_mouseover = $this->master_record['description'][0]['Description'];
	} else {
	}
	
	if($this->language_object->getLanguageCode() === 'en') {
		$quote_text = $this->master_record['quote'][0]['Quote'];
	} else {
	}
				// Display Header
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->handler->abstractglobals->language_script->Contact() . ' ' . $this->master_record['Title'],
		'sub_text'=>$this->handler->abstractglobals->language_script->CommunicateWithUsWeWantToHearYourOpinions() . ' ' . $this->master_record['Subtitle'],
	]);
	
	$entryindexheader->Display();
	
			// Display About Info
		
		// -------------------------------------------------------------
	
	print('<div class="width-50percent border-1px horizontal-center margin-top-22px">');

	print('<center><h2 class="margin-5px font-family-tahoma">' . $this->handler->abstractglobals->language_script->ContactUs() . ' :</h2></center>');
	
	if($this->handler->abstractglobals->script->GetEmailContact()) {
		$contact = $this->handler->abstractglobals->script->GetEmailContact();
	} else {
		$contact = $this->handler->globals->ContactCreator();
	}
	
	if(strpos($contact, '@') !== FALSE) {
		$contact = '<a href="mailto:' . $contact . '">' . $contact . '</a>';
	}
	
	if($this->handler->abstractglobals->script->GetCreator()) {
		$creator = $this->handler->abstractglobals->script->GetCreator();
	} else {
		$creator = $this->handler->globals->SiteCreator();
	}
	
	if($this->handler->abstractglobals->script->GetPublicReleaseDate()) {
		$created_on = $this->handler->abstractglobals->script->GetPublicReleaseDate();
	} else {
		$created_on = $this->handler->globals->SiteCreatedOn();
	}
	
	print(
			'<div class="padding-5px horizontal-left font-family-arial">' .
			'<p class="margin-0px margin-top-5px"><strong>' . $this->handler->abstractglobals->language_script->SiteCreator() . ' :</strong> ' . $creator . '</p>' .
			'<p class="margin-0px margin-top-5px"><strong>' . $this->handler->abstractglobals->language_script->SiteCreatedOn() . ' :</strong> ' . $created_on . '</p>' .
			'<p class="margin-0px margin-top-5px"><strong>' . $this->handler->abstractglobals->language_script->ContactCreator() . ' :</strong> ' . $contact . '</p>' .
			'</div>');
	
	
	print('</div>');
	
			// Display Languages
		
		// -------------------------------------------------------------
	
	$languages->Display();
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'Contact',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
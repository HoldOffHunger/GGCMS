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
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'Editing &mdash; ' . $this->entry['Title'],
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();


/*
	ggreq('modules/html/entry-header.php');
	$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_data['text']]);	
	
		$entryheader->Display();*/
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'margin-5px padding-5px',
	];
	
	$divider_end_args = [
	];
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>'Edit Entry',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this, 'noredirect'=>TRUE]);
		$auth->Display();
		
		print('<div class="clear-float"></div>');
	
			// Display Parent Record Info
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-entry-summary.php');
	$entry_summary = new module_modifyentrysummary(['that'=>$this]);
	$entry_summary->Display();
	
			// Display Master Record Instructions
		
		// -------------------------------------------------------------
	
	if(!count($this->record_list))
	{
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				],
			],
			'list'=>[
				[
					'<nobr>Editing Primary Entry Record:</nobr>',
					'The Code should be the case-sensitive version of the domain URL for this host.',
				],
			],
		];
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
	}
	
			// Display Errors
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-errors.php');
	$errors = new module_modifyerrors(['that'=>$this]);
	$errors->Display();
	
			// Display Form Elements
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-fields.php');
	$fields = new module_modifyfields([
		'that'=>$this,
		'fields'=>[
			'Title'=>[],
			'Subtitle'=>[],
			'List Title'=>[],
			'List Title Sort Key'=>[],
			'Code'=>[],
			'Child Adjective'=>[],
			'Child Noun'=>[],
			'Child Noun Plural'=>[],
			'Grand Child Adjective'=>[],
			'Grand Child Noun'=>[],
			'Grand Child Noun Plural'=>[],
			'Entry Translation'=>[],
			'Availability Start'=>[],
			'Availability End'=>[],
			'Description'=>[],
			'Quote'=>[],
			'Text Body'=>[],
			'Image'=>[],
			'Image Translation'=>[],
			'Tag'=>[],
			'Link'=>[],
			'Event Date'=>[],
			'Association'=>[],
			'Definition'=>[],
			'Publish'=>[],
			'Save'=>[],
		],
	]);
	
	$divider->displaystart(['class'=>'horizontal-center width-80percent margin-top-5px border-2px']);
	
	if(count($this->admin_errors) || count($this->errors)) {
		$fields->DisplayUnavailable();
		
		$divider->displayend([]);
	} else {
		$form->StartForm([
			'action'=>0,
			'method'=>'post',
			'files'=>1,
			'formclass'=>'margin-0px',
		]);
		
		$fields->Display();
	}
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = [
	];
	$form->EndForm($end_form_args);
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
	$bottom_navigation_args = [
		'thispage'=>'Edit',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
	
?>
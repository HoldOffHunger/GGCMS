<?php
	print('<div style="font-family:arial, tahoma;">');
		
			// Standard Requires
		
		// -------------------------------------------------------------

	require(GGCMS_DIR . 'modules/spacing.php');
	
	require(GGCMS_DIR . 'modules/html/text.php');
	$text = new module_text;
	
	require(GGCMS_DIR . 'modules/html/form.php');
	$form = new module_form;
	
	require(GGCMS_DIR . 'modules/html/divider.php');
	$divider = new module_divider;
	
	require(GGCMS_DIR . 'modules/html/table.php');
	$table = new module_table;
	
	require(GGCMS_DIR . 'modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	require(GGCMS_DIR . 'modules/html/header.php');
	$header = new module_header;
	
	require(GGCMS_DIR . 'modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	require(GGCMS_DIR . 'modules/html/entry-header.php');
	require(GGCMS_DIR . 'modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>(!empty($this->saveattemptresults) ? 'Updated!' : 'Update result') . ' &mdash; ' . $this->entry['Title'],
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();
	
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		require(GGCMS_DIR . 'modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>!empty($this->saveattemptresults) ? 'Updated Entry' : 'Update Result',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		require(GGCMS_DIR . 'modules/html/auth.php');
		$auth = new module_auth(['that'=>$this, 'noredirect'=>TRUE]);
		$auth->Display();
		
		print('<div class="clear-float"></div>');
		
			// Display Parent Record Info
		
		// -------------------------------------------------------------
	
	print('<center><div style="width:70%;">');
	print('<table style="width:100%;" class="border-2px background-color-gray13 margin-top-5px">');

	print('<tbody><tr class="border-1px horizontal-left">');

	print('<td class="border-1px vertical-top">');

	print('<nobr>Updating Entry:</nobr>');

	print('</td>');

	print('<td class="border-1px width-100percent vertical-top">');

	print($this->GetHyperlinkedEntryView([
		'entry'=>$this->entry,
		'entrylist'=>$this->record_list,
	]));

	print('</td>');

	print('</tr>');

	print('<tr class="border-1px horizontal-left">');

	print('<td class="border-1px vertical-top">');

	print('<nbr>Status:');

	print('</nbr></td>');

	print('<td class="border-1px width-100percent vertical-top">');
	
	print($this->save_status);
	
	print('</td>');

	print('</tr>');

	print('</tbody></table>');
	print('</div></center>');
	
			// Display Master Record Instructions
		
		// -------------------------------------------------------------
	
	if(!count($this->record_list)) {
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = array(
			'options'=>array(
				'tableheaders'=>0,
				'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>array(
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				),
			),
			'list'=>[
				[
					'<nobr>Editing Primary Entry Record:</nobr>',
					'The Code should be the case-sensitive version of the domain URL for this host.',
				],
			],
		);
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
	}
	
			// Display Admin Errors
		
		// -------------------------------------------------------------
	
	$error_header_displayed = 0;
	
	if($this->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin() && $this->admin_errors)
	{
		$error_header_displayed = 1;
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13 margin-top-5px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top horizontal-center font-size-150percent',
					'border-1px width-100percent vertical-top horizontal-center',
				],
			],
			'list'=>[
				[
					'There was an error with your submission.',
				],
			],
		];
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
		
		foreach($this->admin_errors_display as $admin_error_to_display)
		{
			$divider->displaystart($divider_padding_start_args);
			
			$version_list_display_args = array(
				'options'=>array(
					'tableheaders'=>0,
					'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>array(
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					),
				),
				'list'=>$admin_error_to_display,
			);
			$generic_list->Display($version_list_display_args);
			
			$divider->displayend($divider_end_args);
		}
	}
	
			// Display Errors
		
		// -------------------------------------------------------------
	
	if($this->errors)
	{
		if(!$error_header_displayed)
		{
			$divider->displaystart($divider_padding_start_args);
			
			$version_list_display_args = [
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>[
						'border-1px vertical-top horizontal-center font-size-150percent',
						'border-1px width-100percent vertical-top horizontal-center',
					],
				],
				'list'=>[
					[
						'There was an error with your submission.',
					],
				],
			];
			$generic_list->Display($version_list_display_args);
			
			$divider->displayend($divider_end_args);
		}
		
		foreach($this->errors_display as $error_to_display)
		{
			$divider->displaystart($divider_padding_start_args);
			
			$version_list_display_args = array(
				'options'=>array(
					'tableheaders'=>0,
					'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>array(
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					),
				),
				'list'=>$error_to_display,
			);
			$generic_list->Display($version_list_display_args);
			
			$divider->displayend($divider_end_args);
		}
	}
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = array(
		'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
	);
	
	$divider->displaystart($divider_padding_start_args);
	
			// Display Form Save Results
		
		// -------------------------------------------------------------
	
	if($this->saveattemptresults)
	{
		ggreq('modules/html/modify-results.php');
		$results = new module_modifyresults([
			'that'=>$this,
			'associationtypes'=>TRUE,
			'imagetranslations'=>TRUE,
			'eventdescriptions'=>TRUE,
		]);
		
		$results->Display();
	}
	else
	
			// Re-Display Form
		
		// -------------------------------------------------------------
	
	{
		$start_form_args = [
			'action'=>0,
			'method'=>'post',
			'files'=>1,
			'formclass'=>'margin-0px',
		];
		
		$form->StartForm($start_form_args);
		
				// Display Form Elements : Standard Elements
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/modify-fields.php');
		$fields = new module_modifyfields([
			'that'=>$this,
			'plainlabels'=>TRUE,
			'fields'=>[
				'Title'=>[
					'noamericanize'=>TRUE,
				],
				'Subtitle'=>[
					'noamericanize'=>TRUE,
				],
				'List Title'=>[
					'noamericanize'=>TRUE,
					'nolisttitlecase'=>TRUE,
				],
				'List Title Sort Key'=>[],
				'Code'=>[],
				'Child Adjective'=>[],
				'Child Noun'=>[],
				'Child Noun Plural'=>[],
				'Entry Translation'=>[
					'plaintitlelabel'=>TRUE,
					'subtitlerows'=>['value', 'hidden'],
					'sortkeylabels'=>['blank'=>'List Title Sort Key: ', 'hidden'=>'List Title Sort Key: '],
				],
				'Availability Dates'=>[],
				'Description'=>[
					'hiddenlanguagelabel'=>'Language : ',
				],
				'Quote'=>[
					'hiddenlanguagelabel'=>'Language : ',
					'plainblank'=>TRUE,
				],
				'Text Body'=>[
					'nooptions'=>TRUE,
					'balancedblank'=>TRUE,
				],
				'Image'=>[
					'nothumbnails'=>TRUE,
					'noindexing'=>TRUE,
					'plaintitlelabel'=>TRUE,
				],
				'Tag'=>[
					'notagclass'=>TRUE,
				],
				'Link'=>[
					'urlonly'=>TRUE,
					'nolanguage'=>TRUE,
					'plaintitlelabel'=>TRUE,
					'hiddentitlename'=>'link_Title[]',
				],
				'Event Date'=>[
					'bydate'=>TRUE,
					'dividerbreaks'=>TRUE,
					'approximatefromlast'=>TRUE,
					'plaintitlelabel'=>TRUE,
				],
				'Association'=>[
					'followtags'=>TRUE,
					'rawidrows'=>['value', 'blank', 'hidden'],
					'noblanktypes'=>TRUE,
				],
				'Publish'=>[
					'unclosedchecked'=>TRUE,
				],
				'Save'=>[
					'nolabel'=>TRUE,
					'alwaysdelete'=>TRUE,
				],
			],
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
		'thispage'=>'Update',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
	
?>
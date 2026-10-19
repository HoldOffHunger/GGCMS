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
		
	$divider->displaystart($divider_padding_start_args);
	
	$info_list = [
			[
				'<nobr>Updating Entry:</nobr>', $this->GetHyperlinkedEntryView(['entry'=>$this->entry, 'entrylist'=>$this->record_list]),],
			[
				'<nobr>Updating Entry ID:</nobr>', $this->entry['id'],
			],
		];
	
	$last_edited_entry = $this->last_edited_entry;
	
	if($last_edited_entry && $last_edited_entry['id'])
	{
		$last_edited_entry_url = '<a href="' . $last_edited_entry['Code'] . '/view.php">' . $last_edited_entry['Title'];
		if($last_edited_entry['Subtitle']) {
			$last_edited_entry_url .= ' : ' . $last_edited_entry['Subtitle'];
		}
		$last_edited_entry_url .= '</a>';
		
		$info_list[] = [
			'<nobr>Last Edited Entry:</nobr>', $last_edited_entry_url,
		];
	}
	
	$last_edited_entry = $this->last_unpublished_add;
	
	if($last_edited_entry && $last_edited_entry['id'])
	{
		$last_edited_entry_url = '<a href="' . $last_edited_entry['Code'] . '/view.php">' . $last_edited_entry['Title'];
		if($last_edited_entry['Subtitle']) {
			$last_edited_entry_url .= ' : ' . $last_edited_entry['Subtitle'];
		}
		$last_edited_entry_url .= '</a>';
		
		$info_list[] = [
			'<nobr>Last Unpublished Add:</nobr>', $last_edited_entry_url,
		];
	}
	
	$last_edited_entry = $this->last_unpublished_edit;
	
	if($last_edited_entry && $last_edited_entry['id'])
	{
		$last_edited_entry_url = '<a href="' . $last_edited_entry['Code'] . '/view.php">' . $last_edited_entry['Title'];
		if($last_edited_entry['Subtitle']) {
			$last_edited_entry_url .= ' : ' . $last_edited_entry['Subtitle'];
		}
		$last_edited_entry_url .= '</a>';
		
		$info_list[] = [
			'<nobr>Last Unpublished Edit:</nobr>', $last_edited_entry_url,
		];
	}
	
	if(!$this->isUserAdmin()) {
		$info_list[] = [
			'<nobr>Want extra help?</nobr>', 'Not sure what something does?  Just float your cursor over it!  That should cause a popup to appear that will help you.  Still lost?  Then just <a href="/contact.php">Contact Us</a> and we\'ll help you get connected with our community!',
		];
	}
	
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
		'list'=>$info_list,
	];
	$generic_list->Display($version_list_display_args);
	
	$divider->displayend($divider_end_args);
	
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
	
			// Display Admin Errors
		
		// -------------------------------------------------------------
	
	$error_header_displayed = 0;
	
	if($this->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin() && count($this->admin_errors))
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
				'list'=>$admin_error_to_display,
			];
			$generic_list->Display($version_list_display_args);
			
			$divider->displayend($divider_end_args);
		}
	}
	
			// Display Errors
		
		// -------------------------------------------------------------
	
	if(count($this->errors))
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
	
	if(count($this->admin_errors) || count($this->errors))
	{
				// Display Form Elements : Start
			
			// -------------------------------------------------------------
		
		$divider_padding_start_args = array(
			'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
		);
		
		$divider->displaystart($divider_padding_start_args);
		
				// Display Form Elements : Unavailable Message
			
			// -------------------------------------------------------------
			
		$table_start_args = array(
			'id'=>$element,
			'tableclass'=>'width-100percent blank-element',
			'tableborder'=>'3',
			'cellvalign'=>'top',
		);
		$table->DisplayComponent_StartTable($table_start_args);
		
print('<center><h4>Form Unavailable</h4></center>');
		
		
		$table_end_args = [
		];
		$table->DisplayComponent_EndTable($table_end_args);
		
				// Display Form Elements : End
			
			// -------------------------------------------------------------
		
		$divider_end_args = array(
		);
		$divider->displayend($divider_end_args);
	} else {
				// Display Form Elements : Start
			
			// -------------------------------------------------------------
		
		$divider_padding_start_args = array(
			'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
		);
		
		$divider->displaystart($divider_padding_start_args);
		
		$start_form_args = [
			'action'=>0,
			'method'=>'post',
			'files'=>1,
			'formclass'=>'margin-0px',
		];
		
		$form->StartForm($start_form_args);
		
				// Display Form Elements : Standard Elements
			
			// -------------------------------------------------------------
		
		$this->displayAssociationTypes();
		$this->displayAssociationSubTypes();
		
		ggreq('modules/html/modify-text-field.php');
		ggreq('modules/html/modify-availability.php');
		
		$elements = $this->modifiableRecordTypes();
		$subtle_hints = $this->SubtleFieldHints();
		$hints = $this->ActiveFieldHints();
		
		foreach ($elements as $element) {
			if($element !== 'Save') {
				$table_start_args = [
					'id'=>$element,
					'tableclass'=>'width-100percent blank-element',
					'tableborder'=>'3',
					'cellvalign'=>'top',
				];
				$table->DisplayComponent_StartTable($table_start_args);
				
				if($subtle_hints[$element]) {
					print('<div title="');
					print(htmlentities($subtle_hints[$element]));
					print('">');
				}
				
				print($this->ElementDisplay(['element'=>$element]));
				
				if($element === 'Code') {
					print(' (For Page)');
				}
				
				$element_hint = $hints[$element];
				
				if($element_hint) {
					print('<div style="max-width:200px;">');
					print($element_hint);
					print('</div>');
				}
				
				if($subtle_hints[$element]) {
					print('</div>');
				}
				
				$separate_cells_args = [
					'cellwidth'=>'80%',
					'cellvalign'=>'top',
				];
				$table->DisplayComponent_SeparateCells($separate_cells_args);
			} else {
				$table_start_args = [
					'id'=>$element,
					'tableclass'=>'width-100percent blank-element',
					'tableborder'=>'3',
					'cellvalign'=>'top',
					'cellcolspan'=>2,
				];
				$table->DisplayComponent_StartTable($table_start_args);
			}
			
			if($subtle_hints[$element]) {
				print('<div title="');
				print(htmlentities($subtle_hints[$element]));
				print('">');
			}
			
			switch($element) {
				case 'Title':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'Title']);
					$text_field->Display();
					
					break;
				
				case 'Subtitle':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'Subtitle']);
					$text_field->Display();
					
					break;
				
				case 'List Title':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'ListTitle']);
					$text_field->Display();
					
					break;
				
				case 'List Title Sort Key':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'ListTitleSortKey']);
					$text_field->Display();
					
					break;
					
				case 'Code':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'Code']);
					$text_field->Display();
					
					break;
					
				case 'Child Adjective':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'ChildAdjective']);
					$text_field->Display();
					
					break;
					
				case 'Child Noun':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'ChildNoun']);
					$text_field->Display();
					
					break;
					
				case 'Child Noun Plural':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'ChildNounPlural']);
					$text_field->Display();
					
					break;
					
				case 'Grand Child Adjective':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'GrandChildAdjective']);
					$text_field->Display();
					
					break;
					
				case 'Grand Child Noun':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'GrandChildNoun']);
					$text_field->Display();
					
					break;
					
				case 'Grand Child Noun Plural':
					$text_field = new module_modifytextfield(['that'=>$this, 'name'=>'GrandChildNounPlural']);
					$text_field->Display();
					
					break;
					
				case 'Entry Translation':
					ggreq('modules/html/modify-entry-translation.php');
					$entry_translation_field = new module_modifyentrytranslation(['that'=>$this]);
					$entry_translation_field->Display();
					
					break;
					
				case 'Availability Start':
					$availability_field = new module_modifyavailability(['that'=>$this, 'part'=>'Start']);
					$availability_field->Display();
					
					break;
					
				case 'Availability End':
					$availability_field = new module_modifyavailability(['that'=>$this, 'part'=>'End']);
					$availability_field->Display();
					
					break;
					
				case 'Description':
					ggreq('modules/html/modify-description.php');
					$description_field = new module_modifydescription(['that'=>$this]);
					$description_field->Display();
					
					break;
					
				case 'Quote':
					ggreq('modules/html/modify-quote.php');
					$quote_field = new module_modifyquote(['that'=>$this]);
					$quote_field->Display();
					
					break;
					
				case 'Text Body':
					ggreq('modules/html/modify-text-body.php');
					$text_body_field = new module_modifytextbody(['that'=>$this]);
					$text_body_field->Display();
					
					break;
				
				case 'Image':
					ggreq('modules/html/modify-image.php');
					$image_field = new module_modifyimage(['that'=>$this]);
					$image_field->Display();
					
					break;
					
				case 'Image Translation':
					ggreq('modules/html/modify-image-translation.php');
					$image_translation_field = new module_modifyimagetranslation(['that'=>$this]);
					$image_translation_field->Display();
					
					break;
				
				case 'Tag':
					ggreq('modules/html/modify-tag.php');
					$tag_field = new module_modifytag(['that'=>$this]);
					$tag_field->Display();
					
					break;
					
				case 'Link':
					ggreq('modules/html/modify-link.php');
					$link_field = new module_modifylink(['that'=>$this]);
					$link_field->Display();
					
					break;
				
				case 'Event Date':
					ggreq('modules/html/modify-event-date.php');
					$event_date_field = new module_modifyeventdate(['that'=>$this]);
					$event_date_field->Display();
					
					break;
				
				case 'Association':
					ggreq('modules/html/modify-association.php');
					$association_field = new module_modifyassociation(['that'=>$this]);
					$association_field->Display();
					
					break;
				
				case 'Definition':
					ggreq('modules/html/modify-definition.php');
					$definition_field = new module_modifydefinition(['that'=>$this]);
					$definition_field->Display();
					
					break;
				
				case 'Publish':
					ggreq('modules/html/modify-publish.php');
					$publish_field = new module_modifypublish(['that'=>$this]);
					$publish_field->Display();
					
					break;
				
				case 'Save':
					ggreq('modules/html/modify-submit.php');
					$submit_field = new module_modifysubmit(['that'=>$this]);
					$submit_field->Display();
					
					break;
			}
			
			if($subtle_hints[$element]) {
				print('</div>');
			}
			
			$table_end_args = [
			];
			$table->DisplayComponent_EndTable($table_end_args);
		}
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
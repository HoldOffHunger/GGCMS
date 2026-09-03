<?php
	print('<div style="font-family:arial, tahoma;">');
		
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
		'main_text'=>'Editing &mdash; ' . $this->entry['Title'],
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();


/*
	ggreqonce('modules/html/entry-header.php');
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
			
		ggreqonce('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>'Edit Entry',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreqonce('modules/html/auth.php');
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
					$type_args = [
						'type'=>'text',
						'name'=>'Title',
						'size'=>60,
						'value'=>$this->entry['Title'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					if($this->handler->globals->TitleAutoIncrement()) {
						print('<input type="checkbox" value="1" name="autoincrement-title" id="autoincrement-title" ');
						
						if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
						print(' /> Autoincrement Title');
					}
					
					if($this->handler->globals->TitleAutoSmartTitleCase()) {
						print('<input type="checkbox" value="1" name="title-smart-title-case" id="title-smart-title-case" ');
						
						if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
						print(' /> Smart Title-Case');
					}
					
					if($this->handler->globals->TitleAmericanize()) {
						print('<input type="checkbox" value="1" name="title-americanize" id="title-americanize" ');
						
					#	if($this->handler->globals->AmericanizeTitleDefault()) {
					#		print('CHECKED="CHECKED"');
					#	}
						
						print(' /> Americanize');
					}
					
					if($this->handler->globals->TitleDeRomanizeNumbers()) {
						print('<input type="checkbox" value="1" name="title-de-romanize-numbers" id="title-de-romanize-numbers" ');
						
						#if($this->handler->globals->AutoGenerateTitleDefault()) {
						#	print('CHECKED="CHECKED"');
						#}
						
						print(' /> De-Romanize Numbers');
					}
				
					break;
				
				case 'Subtitle':
					$type_args = [
						'type'=>'text',
						'name'=>'Subtitle',
						'size'=>60,
						'value'=>$this->entry['Subtitle'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					if($this->handler->globals->SubTitleAutoSmartTitleCase()) {
						print('<input type="checkbox" value="1" name="subtitle-smart-title-case" id="subtitle-smart-title-case" ');
						
						if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
						print(' /> Smart Title-Case');
					}
					
					if($this->handler->globals->SubtitleAmericanize()) {
						print('<input type="checkbox" value="1" name="subtitle-americanize" id="subtitle-americanize" ');
						
					#	if($this->handler->globals->AmericanizeSubtitleDefault()) {
					#		print('CHECKED="CHECKED"');
					#	}
						
						print(' /> Americanize');
					}
					
					break;
				
				case 'List Title':
					$type_args = [
						'type'=>'text',
						'name'=>'ListTitle',
						'size'=>60,
						'value'=>$this->entry['ListTitle'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					if($this->handler->globals->ListTitleAutoSmartTitleCase()) {
						print('<input type="checkbox" value="1" name="listtitle-smart-title-case" id="listtitle-smart-title-case" ');
						
					#	if($this->handler->globals->AutoGenerateTitleDefault()) {
					#		print('CHECKED="CHECKED"');
					#	}
						
						print(' /> Smart Title-Case');
					}
					
					if($this->handler->globals->ListTitleAmericanize()) {
						print('<input type="checkbox" value="1" name="listtitle-americanize" id="listtitle-americanize" ');
						
					#	if($this->handler->globals->AmericanizeListTitleDefault()) {
					#		print('CHECKED="CHECKED"');
					#	}
						
						print(' /> Americanize');
					}
					
					break;
				
				case 'List Title Sort Key':
					$type_args = [
						'type'=>'text',
						'name'=>'ListTitleSortKey',
						'size'=>60,
						'value'=>$this->entry['ListTitleSortKey'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Code':
					$type_args = [
						'type'=>'text',
						'name'=>'Code',
						'size'=>60,
						'value'=>$this->entry['Code'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Child Adjective':
					$type_args = [
						'type'=>'text',
						'name'=>'ChildAdjective',
						'size'=>60,
						'value'=>$this->entry['ChildAdjective'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Child Noun':
					$type_args = [
						'type'=>'text',
						'name'=>'ChildNoun',
						'size'=>60,
						'value'=>$this->entry['ChildNoun'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Child Noun Plural':
					$type_args = [
						'type'=>'text',
						'name'=>'ChildNounPlural',
						'size'=>60,
						'value'=>$this->entry['ChildNounPlural'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Grand Child Adjective':
					$type_args = [
						'type'=>'text',
						'name'=>'GrandChildAdjective',
						'size'=>60,
						'value'=>$this->entry['GrandChildAdjective'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Grand Child Noun':
					$type_args = [
						'type'=>'text',
						'name'=>'GrandChildNoun',
						'size'=>60,
						'value'=>$this->entry['GrandChildNoun'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Grand Child Noun Plural':
					$type_args = [
						'type'=>'text',
						'name'=>'GrandChildNounPlural',
						'size'=>60,
						'value'=>$this->entry['GrandChildNounPlural'],
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Entry Translation':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$entry_translations_displayed = 0;
					
					foreach ($this->entrytranslation as $entry_translation)
					{
						if($entry_translation && strlen($entry_translation['Title']))
						{
							if($entry_translations_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
								
										// Display Field
										// -------------------------------------------------------
									
							$this->TitleDisplay();
							
							$type_args = [
								'type'=>'text',
								'name'=>'entrytranslation_Title[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$entry_translation['Title'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
									
print('Subtitle : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'entrytranslation_Subtitle[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$entry_translation['Subtitle'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
									
print('List Title : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'entrytranslation_ListTitle[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$entry_translation['ListTitle'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
									
print('List Title Sort Key: ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'entrytranslation_ListTitleSortKey[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$entry_translation['ListTitleSortKey'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'entrytranslation_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$entry_translation['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$entry_translations_displayed) {
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'add-entry-translation-button',
									'class'=>'float-right',
									'value'=>'Add Entry Translation',
								];
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'delete-entry-translation-button',
									'class'=>'float-right',
									'value'=>'Remove',
								];
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($entry_translations_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$entry_translations_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$entry_translations_displayed) {
									// Display Field
									// -------------------------------------------------------
								
						$this->TitleDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'entrytranslation_Title[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
								
print('Subtitle : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'entrytranslation_Subtitle[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
								
print('List Title : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'entrytranslation_ListTitle[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
								
print('List Title Sort Key : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'entrytranslation_ListTitleSortKey[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'entrytranslation_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = [
							'type'=>'button',
							'id'=>'add-entry-translation-button',
							'class'=>'float-right',
							'value'=>'Add Entry Translation',
						];
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-entry-translation-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
							
								// Display Field
								// -------------------------------------------------------
					
					$this->TitleDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'entrytranslation_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
							
print('List Title : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'entrytranslation_ListTitle-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
							
print('List Title Sort Key : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'entrytranslation_ListTitleSortKey-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'entrytranslation_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-entry-translation-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'entry-translation-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
					
				case 'Availability Start':
					$availability_start_args = [
						'type'=>'text',
						'name'=>'AvailabilityStartDate[]',
						'size'=>20,
						'class'=>'datepicker',
						'value'=>$this->availabilitydaterange[0]['AvailabilityStartDate'],
					];
					
					$form->DisplayFormField($availability_start_args);
					
					$availability_start_args = [
						'type'=>'text',
						'name'=>'AvailabilityStartTime[]',
						'size'=>20,
						'class'=>'timepicker',
						'value'=>$this->availabilitydaterange[0]['AvailabilityStartTime'],
					];
					
					$form->DisplayFormField($availability_start_args);
				
					break;
					
				case 'Availability End':
					$availability_end_args = [
						'type'=>'text',
						'name'=>'AvailabilityEndDate[]',
						'size'=>20,
						'class'=>'datepicker',
						'value'=>$this->availabilitydaterange[0]['AvailabilityEndDate'],
					];
					
					$form->DisplayFormField($availability_end_args);
					
					$availability_end_args = [
						'type'=>'text',
						'name'=>'AvailabilityEndTime[]',
						'size'=>20,
						'class'=>'timepicker',
						'value'=>$this->availabilitydaterange[0]['AvailabilityEndTime'],
					];
					
					$form->DisplayFormField($availability_end_args);
				
					break;
					
				case 'Description':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$descriptions_displayed = 0;
					
					foreach ($this->description as $description)
					{
						if($description && strlen($description['Description']))
						{
							if($descriptions_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
								
										// Display Field
										// -------------------------------------------------------
							
							$type_args = [
								'type'=>'textarea',
								'name'=>'Description[]',
								'cols'=>60,
								'rows'=>5,
								'value'=>$description['Description'],
								'maxlength'=>512,
								'class'=>'float-left',
							];
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
							$this->SourceDisplay();
							
							$type_args = [
								'type'=>'text',
								'name'=>'description_Source[]',
								'size'=>30,
								'value'=>$description['Source'],
								'maxlength'=>512,
							];
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'description_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$description['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$descriptions_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'add-description-button',
									'class'=>'float-right',
									'value'=>'Add Description',
								];
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'delete-description-button',
									'class'=>'float-right',
									'value'=>'Remove',
								];
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($descriptions_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$descriptions_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$descriptions_displayed)
					{
								// Display Field
								// -------------------------------------------------------
								
						$type_args = [
							'type'=>'textarea',
							'name'=>'Description[]',
							'cols'=>60,
							'rows'=>5,
							'class'=>'float-left',
						];
						
						$form->DisplayFormField($type_args);
							
								// Display Field
								// -------------------------------------------------------
						
						$this->SourceDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'description_Source[]',
							'size'=>30,
							'maxlength'=>512,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'description_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = [
							'type'=>'button',
							'id'=>'add-description-button',
							'class'=>'float-right',
							'value'=>'Add Description',
						];
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-description-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
							
					$type_args = [
						'type'=>'textarea',
						'name'=>'Description-Hidden',
						'cols'=>60,
						'rows'=>5,
						'class'=>'float-left',
					];
					
					$form->DisplayFormField($type_args);
							
							// Display Field
							// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'description_Source-Hidden',
						'size'=>30,
						'maxlength'=>512,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
					print('Language :');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'description_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-description-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'description-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
					
				case 'Quote':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$quotes_displayed = 0;
					
					foreach ($this->quote as $quote)
					{
						if($quote && strlen($quote['Quote']))
						{
							if($quotes_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
								
										// Display Field
										// -------------------------------------------------------
							
							$type_args = [
								'type'=>'textarea',
								'name'=>'Quote[]',
								'cols'=>60,
								'rows'=>5,
								'value'=>$quote['Quote'],
								'maxlength'=>512,
								'class'=>'float-left',
							];
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
							$this->SourceDisplay();
							
							$type_args = [
								'type'=>'text',
								'name'=>'quote_Source[]',
								'size'=>30,
								'value'=>$quote['Source'],
								'maxlength'=>512,
							];
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'quote_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$quote['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$quotes_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'add-quote-button',
									'class'=>'float-right',
									'value'=>'Add Quote',
								];
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'delete-quote-button',
									'class'=>'float-right',
									'value'=>'Remove',
								];
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($quotes_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');

							$quotes_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$quotes_displayed)
					{
									// Display Field
									// -------------------------------------------------------
						
						$type_args = [
							'type'=>'textarea',
							'name'=>'Quote[]',
							'cols'=>60,
							'rows'=>5,
							'maxlength'=>512,
							'class'=>'float-left',
						];
						
						$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
						
						$this->SourceDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'quote_Source[]',
							'size'=>30,
							'maxlength'=>512,
						];
						
						$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'quote_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = [
							'type'=>'button',
							'id'=>'add-quote-button',
							'class'=>'float-right',
							'value'=>'Add Quote',
						];
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-quote-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
							
					$type_args = [
						'type'=>'textarea',
						'name'=>'Quote-Hidden',
						'cols'=>60,
						'rows'=>5,
						'class'=>'float-left',
					];
					
					$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'quote_Source-Hidden',
						'size'=>30,
						'maxlength'=>512,
					];
					
					$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
					
					print('Language :');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'quote_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-quote-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'quote-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
					
				case 'Text Body':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$text_bodies_displayed = 0;
					
					foreach ($this->textbody as $textbody)
					{
						if($textbody && strlen($textbody['Text']))
						{
							if($text_bodies_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
								
										// Display Field
										// -------------------------------------------------------

							
							$type_args = [
								'type'=>'textarea',
								'name'=>'Text[]',
								'cols'=>60,
								'rows'=>20,
								'value'=>$textbody['Text'],
								'class'=>'float-left',
							];
							
							$form->DisplayFormField($type_args);
								
									// Display Field
									// -------------------------------------------------------
							
							$this->SourceDisplay();
							
							$type_args = [
								'type'=>'text',
								'name'=>'textbody_Source[]',
								'size'=>30,
								'value'=>$textbody['Source'],
								'maxlength'=>512,
							];
							
							$form->DisplayFormField($type_args);
								
									// Display Field
									// -------------------------------------------------------
							
							print("<br>");
print('Strip URLs : ');
							
							
							$type_args = [
								'type'=>'checkbox',
								'name'=>'textbody_StripURLs[]',
								'size'=>30,
								'value'=>1,
								'maxlength'=>512,
							];
							
							$form->DisplayFormField($type_args);
								
									// Display Field
									// -------------------------------------------------------
							
							print("<br>");
							
print('Americanize Vocabulary : ');
							
							
							$type_args = [
								'type'=>'checkbox',
								'name'=>'textbody_AmericanizeVocabulary[]',
								'size'=>30,
								'value'=>1,
								'maxlength'=>512,
							];
							
							$form->DisplayFormField($type_args);
								
									// Display Field
									// -------------------------------------------------------
							
							print("<br>");
							
print('HTML Formatting : ');
							
							
							$type_args = [
								'type'=>'checkbox',
								'name'=>'textbody_HTMLFormatting[]',
								'size'=>30,
								'value'=>1,
								'maxlength'=>512,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
							print("<br>");
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'textbody_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$textbody['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$text_bodies_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'add-text-button',
									'class'=>'float-right',
									'value'=>'Add Text',
								];
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'delete-text-button',
									'class'=>'float-right',
									'value'=>'Remove',
								];
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($text_bodies_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$text_bodies_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$text_bodies_displayed)
					{
								// Display Field
								// -------------------------------------------------------
								
						$type_args = [
							'type'=>'textarea',
							'name'=>'Text[]',
							'cols'=>60,
							'rows'=>20,
							'class'=>'float-left',
						];
						
						$form->DisplayFormField($type_args);
								
								// Display Field
								// -------------------------------------------------------
						
						$this->SourceDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'textbody_Source[]',
							'size'=>30,
							'maxlength'=>512,
						];
						
						$form->DisplayFormField($type_args);
								
								// Display Field
								// -------------------------------------------------------
						
						print("<br>");
print('Strip URLs : ');
						
						
						$type_args = [
							'type'=>'checkbox',
							'name'=>'textbody_StripURLs[]',
							'value'=>1,
							'maxlength'=>512,
						];
						
						$form->DisplayFormField($type_args);
								
								// Display Field
								// -------------------------------------------------------
						
						print("<br>");
print('Americanize Vocabulary : ');
						
						
						$type_args = [
							'type'=>'checkbox',
							'name'=>'textbody_AmericanizeVocabulary[]',
							'value'=>1,
							'maxlength'=>512,
						];
						
						$form->DisplayFormField($type_args);
								
								// Display Field
								// -------------------------------------------------------
						
						print("<br>");
print('HTML Formatting : ');
						
						
						$type_args = [
							'type'=>'checkbox',
							'name'=>'textbody_HTMLFormatting[]',
							'value'=>1,
							'maxlength'=>512,
						];
						
						$form->DisplayFormField($type_args);
							
								// Display Field
								// -------------------------------------------------------
						
						print("<br>");
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'textbody_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = [
							'type'=>'button',
							'id'=>'add-text-button',
							'class'=>'float-right',
							'value'=>'Add Text',
						];
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
						
						$divider->displayend($clear_float_divider_end_args);
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-text-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
							
					$type_args = [
						'type'=>'textarea',
						'name'=>'Text-Hidden',
						'cols'=>60,
						'rows'=>20,
						'class'=>'float-left',
					];
					
					$form->DisplayFormField($type_args);
							
							// Display Field
							// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'textbody_Source-Hidden',
						'size'=>30,
						'maxlength'=>512,
					];
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'textbody_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-text-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'text-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
				
				case 'Image':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$images_displayed = 0;
					
					$image_index = 1;
					
					foreach ($this->image as $image) {
						$directory = implode('/', str_split($image['FileDirectory']));
						
						print('<div style="margin:2px;float:left;width:50px;">');
						print('<center>');
						print('<a href="/image/' . $directory . '/' . $image['FileName'] . '" target="_blank">');
						print('<img style="border:1px solid black;max-width:50px;max-height:50px;" src="/image/' . $directory . '/' . $image['IconFileName'] . '">');
						print('</a>');
						print('</center>');
						print('</div>');
						
						if($image && strlen($image['FileName'])) {
							if($images_displayed) {
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
							
									// Display Field
									// -------------------------------------------------------
							
							print('<button id="index-adjustment-' . $image_index . '" class="index-adjustment" data-type="image" data-index="' . $image_index . '">');
							print($image_index);
							print('</button>');
							
							$this->TitleDisplay();
							
							$type_args = [
								'type'=>'text',
								'id'=>'image_Title_' . $image_index,
								'name'=>'image_Title[]',
								'size'=>40,
								'value'=>$image['Title'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Filename : ');
							
							
							$type_args = [
								'type'=>'text',
								'id'=>'image_FileName_' . $image_index,
								'name'=>'image_FileName[]',
								'size'=>40,
								'value'=>$image['FileName'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Description : ');
							
							
							$type_args = [
								'type'=>'text',
								'id'=>'image_Description_' . $image_index,
								'name'=>'image_Description[]',
								'size'=>40,
								'value'=>$image['Description'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('File : ');
							
									
							$type_args = [
								'type'=>'file',
								'id'=>'Image_' . $image_index,
								'name'=>'Image[]',
								'size'=>40,
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Buttons
									// -------------------------------------------------------
							
							if(!$images_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'add-image-button',
									'class'=>'float-right',
									'value'=>'Add Image',
								];
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = [
									'type'=>'button',
									'id'=>'delete-image-button',
									'class'=>'float-right',
									'value'=>'Remove',
								];
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($images_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$images_displayed = 1;
							
					//		print('<input type="hidden" id="image_index-adjustment-' . $image_index . '" name="image_index-adjustment[]" value="0" />');
							
							$image_index++;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$images_displayed)
					{
								// Display Field
								// -------------------------------------------------------
						
						$this->TitleDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'image_Title[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Filename : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'image_FileName[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Description : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'image_Description[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('File : ');
						
								
						$type_args = [
							'type'=>'file',
							'name'=>'Image[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = [
							'type'=>'button',
							'id'=>'add-image-button',
							'class'=>'float-right',
							'value'=>'Add Image',
						];
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-image-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
					print('<button class="index-adjustment" data-type="image" data-index="' . $image_index . '">');
					print($image_index);
					print('</button>');
					
					$image_index++;
					
					$this->TitleDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'image_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Filename : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'image_FileName-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Description : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'image_Description-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('File : ');
					
							
					$type_args = [
						'type'=>'file',
						'name'=>'Image-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-image-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'image-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					print('<input type="hidden" id="image_lastindex" value="' . $image_index . '">');
					
					break;
					
				case 'Image Translation':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$image_translations_displayed = 0;
					
					foreach ($this->imagetranslation as $image_translation)
					{
						if($image_translation && strlen($image_translation['Title']))
						{
							if($image_translations_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
							
									// Display Field
									// -------------------------------------------------------
							
							$this->TitleDisplay();
							
							$type_args = array(
								'type'=>'text',
								'name'=>'imagetranslation_Title[]',
								'size'=>40,
								'value'=>$image_translation['Title'],
								'maxlength'=>255,
							);
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Filename : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'imagetranslation_FileName[]',
								'size'=>40,
								'value'=>$image_translation['FileName'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Description : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'imagetranslation_Description[]',
								'size'=>40,
								'value'=>$image_translation['Description'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'imagetranslation_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$image_translation['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
									// Display Buttons
									// -------------------------------------------------------
							
							if(!$image_translations_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-image-translation-button',
									'class'=>'float-right',
									'value'=>'Add Image Translation',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-image-translation-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($image_translations_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$image_translations_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$image_translations_displayed)
					{
								// Display Field
								// -------------------------------------------------------
						
						$this->TitleDisplay();
						
						$type_args = array(
							'type'=>'text',
							'name'=>'imagetranslation_Title[]',
							'size'=>40,
							'maxlength'=>255,
						);
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Filename : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'imagetranslation_FileName[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Description : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'imagetranslation_Description[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'imagetranslation_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-image-translation-button',
							'class'=>'float-right',
							'value'=>'Add Image',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-image-translation-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
					$this->TitleDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'imagetranslation_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Filename : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'imagetranslation_FileName-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Description : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'imagetranslation_Description-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'imagetranslation_Language[]',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-image-translation-button',
						'class'=>'float-right',
						'value'=>'Remove',
					);
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'image-translation-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
				
				case 'Tag':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$tags_displayed = 0;
					
					foreach ($this->tag as $tag)
					{
						if($tag && strlen($tag['Tag']))
						{
							if($tags_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
							
									// Display Field
									// -------------------------------------------------------
							
print('Tag : ');
							
							
							$type_args = array(
								'type'=>'text',
								'name'=>'Tag[]',
								'size'=>40,
								'value'=>$tag['Tag'],
								'maxlength'=>255,
								'class'=>'tag',
							);
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'tag_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$tag['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$tags_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-tag-button',
									'class'=>'float-right',
									'value'=>'Add Tag',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-tag-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($tags_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$tags_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$tags_displayed)
					{
								// Display Field
								// -------------------------------------------------------
						
print('Tag : ');
						
						
						$type_args = array(
							'type'=>'text',
							'name'=>'Tag[]',
							'size'=>40,
							'maxlength'=>255,
							'class'=>'tag',
						);
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'tag_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-tag-button',
							'class'=>'float-right',
							'value'=>'Add Tag',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-tag-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
print('Tag : ');
					
					
					$type_args = array(
						'type'=>'text',
						'name'=>'Tag-Hidden',
						'size'=>40,
						'maxlength'=>255,
						'class'=>'tag',
					);
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'tag_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-tag-button',
						'class'=>'float-right',
						'value'=>'Remove',
					);
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'tag-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
					
				case 'Link':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$links_displayed = 0;
					
					foreach ($this->link as $link)
					{
						if($link && (strlen($link['Title']) || strlen($link['URL'])))
						{
							if($links_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
							
									// Display Title Field
									// -------------------------------------------------------
							
							$this->TitleDisplay();
							
							$type_args = [
								'type'=>'text',
								'name'=>'link_Title[]',
								'size'=>40,
								'value'=>$link['Title'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('URL : ');
							
							
							$type_args = array(
								'type'=>'text',
								'name'=>'URL[]',
								'size'=>40,
								'value'=>$link['URL'],
								'maxlength'=>255,
							);
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'link_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$link['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$links_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-url-button',
									'class'=>'float-right',
									'value'=>'Add URL',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-url-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($links_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$links_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$links_displayed)
					{
								// Display Title Field
								// -------------------------------------------------------
						
						$this->TitleDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'link_Title[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('URL : ');
						
						
						$type_args = array(
							'type'=>'text',
							'name'=>'URL[]',
							'size'=>40,
						);
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'link_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-url-button',
							'class'=>'float-right',
							'value'=>'Add URL',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-url-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
					$this->TitleDisplay();
					
					$type_args = array(
						'type'=>'text',
						'name'=>'link_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					);
					
					$form->DisplayFormField($type_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
print('URL : ');
					
					
					$type_args = array(
						'type'=>'text',
						'name'=>'URL-Hidden',
						'size'=>40,
						'maxlength'=>255,
					);
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'link_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
						
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-url-button',
						'class'=>'float-right',
						'value'=>'Remove',
					);
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'url-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
				
				case 'Event Date':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$event_dates_displayed = 0;
					
					foreach ($this->eventdate as $eventdate) {
						if($eventdate && strlen($eventdate['EventDateTime'])) {
							if($event_dates_displayed) {
										// Start Field
										// -------------------------------------------------------
								
								print('<div class="input-divider">');
							}
							
									// Display Title Field
									// -------------------------------------------------------
							
print('Date : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'EventDate[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$eventdate['EventDate'],
								'class'=>'datepicker',
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Title Field
									// -------------------------------------------------------
							
print('Time : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'EventTime[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$eventdate['EventTime'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Title Field
									// -------------------------------------------------------
							
							$this->TitleDisplay();
							
							$type_args = [
								'type'=>'text',
								'name'=>'eventdate_Title[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$eventdate['Title'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Title Field
									// -------------------------------------------------------
							
print('Description : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'eventdate_Description[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$eventdate['Description'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Title Field
									// -------------------------------------------------------
									
print('Language : ');
							
							
							$language_args = [
								'type'=>'select',
								'name'=>'eventdate_Language[]',
								'options'=>$this->SelectableLanguages,
								'selected'=>$eventdate['Language'],
							];
							
							$form->DisplayFormField($language_args);
							
									// Display Title Field
									// -------------------------------------------------------
									
print('Approximate : ');
							
							
							$language_args = [
								'type'=>'checkbox',
								'name'=>'eventdate_Approximate[]',
								'selected'=>$eventdate['Approximate'],
							];
							
							$form->DisplayFormField($language_args);
							
							if(!$event_dates_displayed) {
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-event-date-button',
									'class'=>'float-right',
									'value'=>'Add Event Date',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-event-date-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($event_dates_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$event_dates_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$event_dates_displayed)
					{
								// Display Title Field
								// -------------------------------------------------------
						
print('Date : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'EventDate[]',
							'size'=>40,
							'maxlength'=>255,
							'class'=>'datepicker',
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Title Field
								// -------------------------------------------------------
						
print('Time : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'EventTime[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Title Field
								// -------------------------------------------------------
						
						$this->TitleDisplay();
						
						$type_args = [
							'type'=>'text',
							'name'=>'eventdate_Title[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Title Field
								// -------------------------------------------------------
						
print('Description : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'eventdate_Description[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Title Field
								// -------------------------------------------------------
								
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'eventdate_Language[]',
							'options'=>$this->SelectableLanguages,
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display Title Field
								// -------------------------------------------------------
								
print('Approximate : ');
						
						
						$language_args = [
							'type'=>'checkbox',
							'name'=>'eventdate_Approximate[]',
							'selected'=>'en',
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-event-date-button',
							'class'=>'float-right',
							'value'=>'Add Event Date',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-event-date-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Title Field
							// -------------------------------------------------------
					
print('Date : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'EventDate-Hidden',
						'size'=>40,
						'maxlength'=>255,
						'class'=>'datepicker',
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Title Field
							// -------------------------------------------------------
					
print('Time : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'EventTime-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Title Field
							// -------------------------------------------------------
					
					$this->TitleDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'eventdate_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Title Field
							// -------------------------------------------------------
					
print('Description : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'eventdate_Description-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Title Field
							// -------------------------------------------------------
							
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'eventdate_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display Title Field
							// -------------------------------------------------------
							
print('Approximate : ');
					
					
					$language_args = [
						'type'=>'checkbox',
						'name'=>'eventdate_Approximate-Hidden',
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-event-date-button',
						'class'=>'float-right',
						'value'=>'Remove',
					);
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'event-date-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
				
				case 'Association':
					
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$associations_displayed = 0;
					
					foreach ($this->association as $association) {
						if($association && strlen($association['ChosenEntryid'])) {
							if($associations_displayed) {
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							}
							
									// Display Field
									// -------------------------------------------------------
							
print('Entry ID Associated With : ');
							
							
							if($this->showAssociationRawIds()) {
								$type_args = [
									'type'=>'text',
									'name'=>'ChosenEntryid[]',
									'size'=>40,
									'maxlength'=>255,
									'value'=>$association['ChosenEntryid'],
								];
								
								$form->DisplayFormField($type_args);
							} else {
								print('<select name="ChosenEntryid[]">');
								
								$this->displayAssociations([
									'selected'=>$association['ChosenEntryid'],
									'defaultblankoption'=>TRUE,
								]);
								
								print('</select>');
							}
							
									// Display Field
									// -------------------------------------------------------
							
							print('Type : ');
							
							print('<input type="text" list="Association_Type" name="association_Type[]" size="40" maxlength="255" value="' . $association['Type'] . '">');
							
									// Display Field
									// -------------------------------------------------------
							
							print('SubType : ');
							
							print('<input type="text" list="Association_SubType" name="association_SubType[]" size="40" maxlength="255" value="' . $association['SubType'] . '">');
							
							if(!$associations_displayed) {
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-association-button',
									'class'=>'float-right',
									'value'=>'Add Association',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-association-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($associations_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');
							
							$associations_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$associations_displayed) {
								// Display Field
								// -------------------------------------------------------
						
print('Entry ID Associated With : ');
						
						
						if($this->showAssociationRawIds()) {
							$type_args = [
								'type'=>'text',
								'name'=>'ChosenEntryid[]',
								'size'=>40,
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
						} else {
							print('<select name="ChosenEntryid[]">');
							
							$this->displayAssociations([
								'defaultblankoption'=>TRUE,
							]);
							
							print('</select>');
						}
						
								// Display Field
								// -------------------------------------------------------
						
						print('Type : ');
							
						print('<input type="text" list="Association_Type" name="association_Type[]" size="40" maxlength="255" value="' . $association['Type'] . '">');
						
								// Display Field
								// -------------------------------------------------------
						
						print('SubType : ');
						
						print('<input type="text" list="Association_SubType" name="association_SubType[]" size="40" maxlength="255" value="' . $association['SubType'] . '">');
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-association-button',
							'class'=>'float-right',
							'value'=>'Add Association',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-association-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Field
							// -------------------------------------------------------
					
print('Entry ID Associated With : ');
					
					
					if($this->showAssociationRawIds()) {
						$type_args = [
							'type'=>'text',
							'name'=>'ChosenEntryid-Hidden',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
					} else {
						print('<select name="ChosenEntryid-Hidden">');
						
						$this->displayAssociations([
							'defaultblankoption'=>TRUE,
						]);
						
						print('</select>');
					}
					
							// Display Field
							// -------------------------------------------------------
					
					print('Type : ');

					print('<input type="text" list="Association_Type" name="association_Type-Hidden" size="40" maxlength="255">');

					
							// Display Field
							// -------------------------------------------------------
					
					print('SubType : ');

					print('<input type="text" list="Association_SubType" name="association_SubType-Hidden" size="40" maxlength="255">');
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-association-button',
						'class'=>'float-right',
						'value'=>'Remove',
					);
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'association-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
				
				case 'Definition':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$definitions_displayed = 0;
					
					foreach ($this->definition as $definition)
					{
						if($definition && strlen($definition['Term']))
						{
							if($definitions_displayed)
							{
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
							} else {
								
										// Autogen?
										// -------------------------------------------------------
							
print('Autogenerate Definitions : ');
								
								
								$type_args = array(
									'type'=>'checkbox',
									'name'=>'autogenerate-definitions',
									'value'=>1,
									'maxlength'=>512,
								);
								
								$form->DisplayFormField($type_args);
								
								print('<br>');
							}
							
									// Display Field
									// -------------------------------------------------------
							
print('Definition : ');
							
							
							$type_args = array(
								'type'=>'text',
								'name'=>'Term[]',
								'size'=>20,
								'value'=>$definition['Term'],
								'maxlength'=>255,
								'class'=>'definition',
							);
							
							$form->DisplayFormField($type_args);
							
							$type_args = array(
								'type'=>'text',
								'name'=>'definition_Definition[]',
								'size'=>60,
								'value'=>$definition['Definition'],
								'class'=>'definition',
							);
							
							$form->DisplayFormField($type_args);
							
							if(!$definitions_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-definition-button',
									'class'=>'float-right',
									'value'=>'Add Definition',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-definition-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
								$form->DisplayFormField($type_args);
							}
							
									// Finish Field
									// -------------------------------------------------------
							
							if($definitions_displayed)
							{
								$clear_float_divider_end_args = [
								];
								
								$divider->displayend($clear_float_divider_end_args);
							}
							
									// Clear Float
									// -------------------------------------------------------
							
							print('<div class="clear-float"></div>');

							$definitions_displayed = 1;
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$definitions_displayed) {
						
								// Autogen?
								// -------------------------------------------------------
					
print('Autogenerate Definitions : ');
						
						
						$type_args = array(
							'type'=>'checkbox',
							'name'=>'autogenerate-definitions',
							'value'=>1,
							'maxlength'=>512,
						);
						
						$form->DisplayFormField($type_args);
						
						print('<br>');
						
								// Display Field
								// -------------------------------------------------------
						
print('Definition : ');
						
						
						$type_args = array(
							'type'=>'text',
							'name'=>'Term[]',
							'size'=>20,
							'maxlength'=>255,
							'class'=>'definition',
						);
						
						$form->DisplayFormField($type_args);
						
						$type_args = array(
							'type'=>'text',
							'name'=>'definition_Definition[]',
							'size'=>60,
							'class'=>'definition',
						);
						
						$form->DisplayFormField($type_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-definition-button',
							'class'=>'float-right',
							'value'=>'Add Definition',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
					}
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-definition-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
print('Definition : ');
					
					
					$type_args = array(
						'type'=>'text',
						'name'=>'Term-Hidden',
						'size'=>20,
						'maxlength'=>255,
						'class'=>'definition',
					);
					
					$form->DisplayFormField($type_args);
					
					$type_args = array(
						'type'=>'text',
						'name'=>'definition_Definition-Hidden',
						'size'=>60,
						'maxlength'=>255,
						'class'=>'definition',
					);
					
					$form->DisplayFormField($type_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-definition-button',
						'class'=>'float-right',
						'value'=>'Remove',
					);
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'definition-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
				
				case 'Publish':
					
					print('<input type="checkbox" name="Publish" value="1"');
					
					if($this->entry['Publish'] === 1) {
						print(' CHECKED="CHECKED"');
					}
					
					print('>');
					
					break;
				
				case 'Save':
						// Save Button
						// -----------------------------------------------------
						
					print('<center>Update This Entry : ');
					
					$type_args = [
						'type'=>'submit',
						'name'=>'Update',
						'value'=>'Update',
					];
					
					$form->DisplayFormField($type_args);
					
print('</center>');
					
					
					if($this->parent['id']) {
						if($this->canUserAccess()) {
								// Save/Delete Button Separator
								// -----------------------------------------------------
							
							$separate_cells_args = [
								'cellwidth'=>'50%',
								'cellvalign'=>'top',
							];
							$table->DisplayComponent_SeparateCells($separate_cells_args);
							
							// Delete Button
							// -----------------------------------------------------
						
print('<center>');
							
							
							$type_args = [
								'type'=>'submit',
								'name'=>'Delete',
								'value'=>'Delete',
								'class'=>'confirm',
							];
							
							$form->DisplayFormField($type_args);
							
print('</center>');
							
						}
					}
					
						// Hidden Action
						// -----------------------------------------------------
					
					$type_args = [
						'type'=>'hidden',
						'name'=>'action',
						'value'=>'Update',
					];
					
					$form->DisplayFormField($type_args);
					
print('</center>');
					
					
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
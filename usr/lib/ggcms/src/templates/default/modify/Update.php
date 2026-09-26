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
				// Display Record Creation IDs
			
			// -------------------------------------------------------------
	
		if($this->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin())
		{
					// Display Div : Start
				
				// -------------------------------------------------------------
	
			$divider_padding_start_args = array(
				'class'=>'horizontal-center width-70percent',
			);
			$divider->displaystart($divider_padding_start_args);
			
					// Display IDs
				
				// -------------------------------------------------------------
			
			#print_r($this->GetCreatedRecordsList());
			
			#print("sup?");
			
			$version_list_display_args = array(
				'options'=>array(
					'tableheaders'=>0,
					'tableclass'=>'width-100percent horizontal-center border-2px background-color-gray13 margin-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>array(
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					),
				),
				'list'=>$this->GetCreatedRecordsList(),
			);
			$generic_list->Display($version_list_display_args);
			
					// Display Div : End
				
				// -------------------------------------------------------------
				
			$divider_end_args = array(
			);
			$divider->displayend($divider_end_args);
		}
		
				// Display Form Elements : Standard Elements
			
			// -------------------------------------------------------------
		
		$this->displayAssociationTypes();
		$this->displayAssociationSubTypes();
		
		$elements = $this->modifiableRecordTypes();
		
		foreach ($elements as $element) {
				// Start Table Display
				// ----------------------------------------
				
			$table_start_args = array(
				'id'=>$element,
				'tableclass'=>'width-100percent blank-element',
				'tableborder'=>'3',
				'cellvalign'=>'top',
			);
			$table->DisplayComponent_StartTable($table_start_args);
			
			print($this->ElementDisplay(['element'=>$element]));
			
			$element_hint = $hints[$element];
			
			if($element_hint) {
				print('<div style="max-width:200px;">');
				print($element_hint);
				print('</div>');
			}
			
			$separate_cells_args = array(
				'cellwidth'=>'80%',
				'cellvalign'=>'top',
			);
			$table->DisplayComponent_SeparateCells($separate_cells_args);
			
				// Display Record Fields
				// ----------------------------------------
			
			switch($element) {
				case 'Title':

					print($this->entry['Title']);
					
					
					break;
					
				case 'Subtitle':

					print($this->entry['Subtitle']);
					
					
					break;
					
				case 'List Title':

					print($this->entry['ListTitle']);
					
					
					break;
				
				case 'List Title Sort Key':

					print($this->entry['ListTitleSortKey']);
					
					
					break;
					
				case 'Code':

					print($this->entry['Code']);
					
					
					break;
				
				case 'Child Adjective':

					print($this->entry['ChildAdjective']);
					
					
					break;
				
				case 'Child Noun':

					print($this->entry['ChildNoun']);
					
					
					break;
				
				case 'Child Noun Plural':

					print($this->entry['ChildNounPlural']);
					
					
					break;
					
				case 'Grand Child Adjective':

					print($this->entry['GrandChildAdjective']);
					
					
					break;
					
				case 'Grand Child Noun':

					print($this->entry['GrandChildNoun']);
					
					
					break;
					
				case 'Grand Child Noun Plural':

					print($this->entry['GrandChildNounPlural']);
					
					
					break;
					
				case 'Entry Translation':
					$printable_entry_translations = [];
					foreach ($this->entrytranslation as $entry_translation)
					{
						if($entry_translation['Title'])
						{
							$printable_entry_translations[] = $entry_translation['Title'] . ': ' . $entry_translation['Subtitle'] . ' (Language: ' . $entry_translation['Language'] . ')';
						}
					}
					
					if(count($printable_entry_translations))
					{
						$element_text = implode('<br>', $printable_entry_translations);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
					
				case 'Availability Dates':
					$printable_availability_dates = [];
					
					foreach ($this->availabilitydaterange as $availabilitydaterange)
					{
						$availability_start_date = $availabilitydaterange['AvailabilityStart'];
						$availability_end_date = $availabilitydaterange['AvailabilityEnd'];
						
						$printable_availability_dates[] = $availability_start_date . ' to ' . $availability_end_date;
					}
					

					print(implode('<br>', $printable_availability_dates));
					
					
					break;
					
				case 'Description':
					$printable_descriptions = [];
					foreach ($this->description as $description)
					{
						$description_description = $description['Description'];
						
						if($description_description)
						{
							$printable_descriptions[] = $description_description . ' (Language: ' . $description['Language'] . ')';
						}
					}
					
					if(count($printable_descriptions))
					{
						$element_text = implode('<br>', $printable_descriptions);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
					
				case 'Quote':
					$printable_quotes = [];
					foreach ($this->quote as $quote)
					{
						$quote_quote = $quote['Quote'];
						
						if($quote_quote)
						{
							$printable_quotes[] = $quote_quote . ' (Language: ' . $quote['Language'] . ')';
						}
					}
					
					if(count($printable_quotes))
					{
						$element_text = implode('<br>', $printable_quotes);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
					
				case 'Text Body':
					$printable_texts = [];
					foreach ($this->textbody as $textbody)
					{
						$textbody_text = $textbody['Text'];
						
						if($textbody_text)
						{
							$printable_texts[] = $textbody_text . ' (Language: ' . $textbody['Language'] . ')';
						}
					}
					
					if(count($printable_texts))
					{
						$element_text = implode('<br>', $printable_texts);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
				
				case 'Image':
					$printable_images = [];
					foreach($this->image as $image)
					{
						if($image && $image['Title'])
						{
							$image_text = $image['Title'] . ' (' . $image['FileName'] . ')';
							
							$printable_images[] = $image_text;
						}
					}
					
					if(count($printable_images))
					{
						$element_text = implode('<br>', $printable_images);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
				
				case 'Image Translation':
					$printable_image_translations = [];
					foreach($this->imagetranslation as $image_translation)
					{
						if($image_translation['FileName'])
						{
							$image_translation_text = $image_translation['Title'] . ' (' . $image_translation['FileName'] . ' ~ ' . $image_translation['Language'] . ')';
							
							$printable_image_translations[] = $image_translation_text;
						}
					}
					
					if(count($printable_image_translations))
					{
						$element_text = implode('<br>', $printable_image_translations);
					}
					else
					{
						$element_text = 'N/A';
					}
					
					print($element_text);
					
					break;
				
				case 'Tag':
					$printable_tags = [];
					
					foreach ($this->tag as $tag)
					{
						$tag_text = $tag['Tag'];
						if($tag_text)
						{
							$tag_text = '&bull; ' . $tag_text;
							
							if($tag['Language'])
							{
								$tag_text .= ' (' . $tag['Language'] . ')';
							}
							
							$printable_tags[] = $tag_text;
						}
					}
					
					if(count($printable_tags))
					{
						$element_text = implode('<br>', $printable_tags);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
					
				case 'Link':
					$printable_links = [];
					
					foreach ($this->link as $link)
					{
						$link_url = $link['URL'];
						
						if($link_url)
						{
							$link_text = $link['Title'];
							
							$link_display = '';
							
							if($link_url)
							{
								$link_display .= '&bull; <a href="' . $link_url . '" target="_blank">';
							}
							
							$link_display .= $link_text;
							
							if($link['Language'])
							{
								$link_display .= ' (' . $link['Language'] . ')';
							}
							
							if($link_url)
							{
								$link_display .= '</a>';
							}
							
							$printable_links[] = $link_display;
						}
					}
					
					if(count($printable_links))
					{
						$element_text = implode('<br>', $printable_links);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
				
				case 'Event Date':
					$printable_event_dates = [];
					
					foreach ($this->eventdate as $eventdate)
					{
						$event_date_time = $eventdate['EventDateTime'];
						if($event_date_time)
						{
							$event_date_time_text = '&bull; ' . $event_date_time;
							
							if($eventdate['Title'] || $eventdate['Description'])
							{
								$event_date_time_text .= ' (';
								
								if($eventdate['Title'])
								{
									$event_date_time_text .= $eventdate['Title'];
									
									if($eventdate['Description'])
									{
										$event_date_time_text .= ' ~ ';
									}
								}
								
								if($eventdate['Description'])
								{
									$event_date_time_text .= $eventdate['Description'];
								}
								
								$event_date_time_text .= ')';
							}
							
							$printable_event_dates[] = $event_date_time_text;
						}
					}
					if(count($printable_event_dates))
					{
						$element_text = implode('<br>', $printable_event_dates);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
				
				case 'Association':
					$printable_associations = [];
					
					foreach ($this->association as $association)
					{
						$association_text = $association['ChosenEntryid'];
						if($association_text)
						{
							$association_text = '&bull; ' . $association_text;
							
							if($association['Type'] || $association['SubType'])
							{
								
								$association_text .= ' (';
								
								if($association['Type'])
								{
									$association_text .= $association['Type'];
									
									if($association['SubType'])
									{
										$association_text .= ' ~ ';
									}
								}
								
								if($association['SubType'])
								{
									$association_text .= $association['SubType'];
								}
								
								$association_text .= ')';
								
								$printable_associations[] = $association_text;
							}
						}
					}
					
					if(count($printable_associations))
					{
						$element_text = implode('<br>', $printable_associations);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
				
				case 'Definition':
					$printable_definitions = [];
					
					foreach ($this->definition as $definition)
					{
						$term_text = $definition['Term'];
						if($tag_text)
						{
							$term_text = '&bull; ' . $term_text . ' : ' . $definition['Definition'];
							
							$printable_definitions[] = $term_text;
						}
					}
					
					if(count($printable_definitions))
					{
						$element_text = implode('<br>', $printable_definitions);
					}
					else
					{
						$element_text = 'N/A';
					}
					

					print($element_text);
					
					
					break;
			}
			
				// End Table Display
				// ----------------------------------------
			
			$table_end_args = [
			];
			$table->DisplayComponent_EndTable($table_end_args);
		}
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
		
		$this->displayAssociationTypes();
		$this->displayAssociationSubTypes();
		$elements = $this->modifiableRecordTypes();
		
		foreach ($elements as $element)
		{
			if($element != 'Save')
			{
				$table_start_args = [
					'id'=>$element,
					'tableclass'=>'width-100percent blank-element',
					'tableborder'=>'3',
					'cellvalign'=>'top',
				];
				$table->DisplayComponent_StartTable($table_start_args);
				

				print($element);
				
				
				$separate_cells_args = [
					'cellwidth'=>'80%',
					'cellvalign'=>'top',
				];
				$table->DisplayComponent_SeparateCells($separate_cells_args);
			}
			else
			{
				$table_start_args = [
					'id'=>$element,
					'tableclass'=>'width-100percent blank-element',
					'tableborder'=>'3',
					'cellvalign'=>'top',
					'cellcolspan'=>2,
				];
				$table->DisplayComponent_StartTable($table_start_args);
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
					
					if($this->handler->globals->TitleDeRomanizeNumbers()) {
						print('<input type="checkbox" value="1" name="title-de-romanize-numbers" id="title-de-romanize-numbers" ');
						
						#if($this->handler->globals->AutoGenerateTitleDefault()) {
						#	print('CHECKED="CHECKED"');
						#}
						
						print(' /> De-Romanize Numbers');
					}
				
					break;
				
				case 'Subtitle':
					$type_args = array(
						'type'=>'text',
						'name'=>'Subtitle',
						'size'=>60,
						'value'=>$this->entry['Subtitle'],
						'maxlength'=>255,
					);
					
					$form->DisplayFormField($type_args);
					
					if($this->handler->globals->SubTitleAutoSmartTitleCase()) {
						print('<input type="checkbox" value="1" name="subtitle-smart-title-case" id="subtitle-smart-title-case" ');
						
						if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
						print(' /> Smart Title-Case');
					}
					
					break;
				
				case 'List Title':
					$type_args = array(
						'type'=>'text',
						'name'=>'ListTitle',
						'size'=>60,
						'value'=>$this->entry['ListTitle'],
						'maxlength'=>255,
					);
					
					$form->DisplayFormField($type_args);
					
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
					$type_args = array(
						'type'=>'text',
						'name'=>'Code',
						'size'=>60,
						'value'=>$this->entry['Code'],
						'maxlength'=>255,
					);
					
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
					
				case 'Entry Translation':
							// Preset Check if Displayed
							// -------------------------------------------------------
					
					$entry_translations_displayed = 0;
					
					foreach ($this->entrytranslation as $entry_translation) {
						if($entry_translation && strlen($entry_translation['Title'])) {
							if($entry_translations_displayed) {
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
							
							if(!$entry_translations_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-entry-translation-button',
									'class'=>'float-right',
									'value'=>'Add Entry Translation',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-entry-translation-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
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
					
					if(!$entry_translations_displayed)
					{
									// Display Field
									// -------------------------------------------------------
								
print('Title : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'entrytranslation_Title[]',
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
								
print('List Title Sort Key: ');
						
						
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
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-entry-translation-button',
							'class'=>'float-right',
							'value'=>'Add Entry Translation',
						);
						
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
							
print('Title : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'entrytranslation_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Field
							// -------------------------------------------------------
							
print('Subtitle : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'entrytranslation_Subtitle-Hidden',
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
							
print('List Title Sort Key: ');
					
					
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
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-entry-translation-button',
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
						'id'=>'entry-translation-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
					
					break;
					
				case 'Availability Dates':
					foreach ($this->availabilitydaterange as $availabilitydaterange)
					{
						$availability_start_args = array(
							'type'=>'text',
							'name'=>'AvailabilityStartDate[]',
							'size'=>20,
							'class'=>'datepicker',
							'value'=>$availabilitydaterange['AvailabilityStartDate'],
						);
						
						$form->DisplayFormField($availability_start_args);
						
						$availability_start_args = array(
							'type'=>'text',
							'name'=>'AvailabilityStartTime[]',
							'size'=>20,
							'class'=>'timepicker',
							'value'=>$availabilitydaterange['AvailabilityStartTime'],
						);
						
						$form->DisplayFormField($availability_start_args);
						
print('<br>');
						
						
						$availability_end_args = array(
							'type'=>'text',
							'name'=>'AvailabilityEndDate[]',
							'size'=>20,
							'class'=>'datepicker',
							'value'=>$availabilitydaterange['AvailabilityEndDate'],
						);
						
						$form->DisplayFormField($availability_end_args);
						
						$availability_end_args = array(
							'type'=>'text',
							'name'=>'AvailabilityEndTime[]',
							'size'=>20,
							'class'=>'timepicker',
							'value'=>$availabilitydaterange['AvailabilityEndTime'],
						);
						
						$form->DisplayFormField($availability_end_args);
					}
					
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
							
							$type_args = array(
								'type'=>'textarea',
								'name'=>'Description[]',
								'cols'=>60,
								'rows'=>5,
								'value'=>$description['Description'],
								'maxlength'=>512,
								'class'=>'float-left',
							);
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
							$this->SourceDisplay();
							
							$type_args = array(
								'type'=>'text',
								'name'=>'description_Source[]',
								'size'=>30,
								'value'=>$description['Source'],
								'maxlength'=>512,
							);
							
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
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-description-button',
									'class'=>'float-right',
									'value'=>'Add Description',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-description-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
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
								
						$type_args = array(
							'type'=>'textarea',
							'name'=>'Description[]',
							'cols'=>60,
							'rows'=>5,
							'class'=>'float-left',
						);
						
						$form->DisplayFormField($type_args);
							
								// Display Field
								// -------------------------------------------------------
						
						$this->SourceDisplay();
						
						$type_args = array(
							'type'=>'text',
							'name'=>'description_Source[]',
							'size'=>30,
							'maxlength'=>512,
						);
						
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
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-description-button',
							'class'=>'float-right',
							'value'=>'Add Description',
						);
						
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
							
					$type_args = array(
						'type'=>'textarea',
						'name'=>'Description-Hidden',
						'cols'=>60,
						'rows'=>5,
						'class'=>'float-left',
					);
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = array(
						'type'=>'text',
						'name'=>'description_Source-Hidden',
						'size'=>30,
						'maxlength'=>512,
					);
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'description_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-description-button',
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
							
							$type_args = array(
								'type'=>'textarea',
								'name'=>'Quote[]',
								'cols'=>60,
								'rows'=>5,
								'value'=>$quote['Quote'],
								'maxlength'=>512,
								'class'=>'float-left',
							);
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
							$this->SourceDisplay();
							
							$type_args = array(
								'type'=>'text',
								'name'=>'quote_Source[]',
								'size'=>30,
								'value'=>$quote['Source'],
								'maxlength'=>512,
							);
							
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
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-quote-button',
									'class'=>'float-right',
									'value'=>'Add Quote',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-quote-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
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
						
						$type_args = array(
							'type'=>'textarea',
							'name'=>'Quote[]',
							'cols'=>60,
							'rows'=>5,
							'maxlength'=>512,
						);
						
						$form->DisplayFormField($type_args);
								
									// Display Field
									// -------------------------------------------------------
						
						$this->SourceDisplay();
						
						$type_args = array(
							'type'=>'text',
							'name'=>'quote_Source[]',
							'size'=>30,
							'maxlength'=>512,
						);
						
						$form->DisplayFormField($type_args);
								
									// Display Field
									// -------------------------------------------------------
						
print('Language : ');
						
						
						$language_args = [
							'type'=>'select',
							'name'=>'quote_Language[]',
							'options'=>$this->SelectableLanguages,
						];
						
						$form->DisplayFormField($language_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-quote-button',
							'class'=>'float-right',
							'value'=>'Add Quote',
						);
						
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
							
					$type_args = array(
						'type'=>'textarea',
						'name'=>'Quote-Hidden',
						'cols'=>60,
						'rows'=>5,
						'class'=>'float-left',
					);
					
					$form->DisplayFormField($type_args);
							
								// Display Field
								// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = array(
						'type'=>'text',
						'name'=>'quote_Source-Hidden',
						'size'=>30,
						'maxlength'=>512,
					);
					
					$form->DisplayFormField($type_args);
							
								// Display Field
								// -------------------------------------------------------
					
print('Language : ');
					
					
					$language_args = [
						'type'=>'select',
						'name'=>'quote_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-quote-button',
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

							
							$type_args = array(
								'type'=>'textarea',
								'name'=>'Text[]',
								'cols'=>60,
								'rows'=>20,
								'value'=>$textbody['Text'],
								'class'=>'float-left',
							);
							
							$form->DisplayFormField($type_args);
								
										// Display Field
										// -------------------------------------------------------
							
							$this->SourceDisplay();
							
							$type_args = array(
								'type'=>'text',
								'name'=>'textbody_Source[]',
								'size'=>30,
								'value'=>$textbody['Source'],
								'maxlength'=>512,
							);
							
							$form->DisplayFormField($type_args);
							
										// Display Field
										// -------------------------------------------------------
							
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
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-text-button',
									'class'=>'float-right',
									'value'=>'Add Text',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-text-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
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
								
						$type_args = array(
							'type'=>'textarea',
							'name'=>'Text[]',
							'cols'=>60,
							'rows'=>20,
							'class'=>'float-left',
						);
						
						$form->DisplayFormField($type_args);
							
								// Display Field
								// -------------------------------------------------------
						
						$this->SourceDisplay();
						
						$type_args = array(
							'type'=>'text',
							'name'=>'textbody_Source[]',
							'size'=>30,
							'maxlength'=>512,
						);
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
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
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-text-button',
							'class'=>'float-right',
							'value'=>'Add Text',
						);
						
						$form->DisplayFormField($type_args);
						
								// Clear Float
								// -------------------------------------------------------
						
						print('<div class="clear-float"></div>');
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
							
					$type_args = array(
						'type'=>'textarea',
						'name'=>'Text-Hidden',
						'cols'=>60,
						'rows'=>20,
						'class'=>'float-left',
					);
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = array(
						'type'=>'text',
						'name'=>'textbody_Source-Hidden',
						'size'=>30,
						'maxlength'=>512,
					);
					
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
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-text-button',
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
					
					foreach ($this->image as $image)
					{
						if($image && strlen($image['FileName']))
						{
							if($images_displayed)
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
							
print('Title : ');
							
							
							$type_args = array(
								'type'=>'text',
								'name'=>'image_Title[]',
								'size'=>40,
								'value'=>$image['Title'],
								'maxlength'=>255,
							);
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('Filename : ');
							
							
							$type_args = [
								'type'=>'text',
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
								'name'=>'image_Description[]',
								'size'=>40,
								'value'=>$image['Description'],
								'maxlength'=>255,
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
print('File : ');
							
									
							$type_args = array(
								'type'=>'file',
								'name'=>'Image[]',
								'size'=>40,
								'maxlength'=>255,
							);
							
							$form->DisplayFormField($type_args);
							
									// Display Buttons
									// -------------------------------------------------------
							
							if(!$images_displayed)
							{
										// Display 'Add' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'add-image-button',
									'class'=>'float-right',
									'value'=>'Add Image',
								);
								
								$form->DisplayFormField($type_args);
							}
							else
							{
										// Display 'Delete' Button
										// -------------------------------------------------------
								
								$type_args = array(
									'type'=>'button',
									'id'=>'delete-image-button',
									'class'=>'float-right',
									'value'=>'Remove',
								);
								
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
						}
					}
					
							// Display Field if Not Yet Displayed
							// -------------------------------------------------------
					
					if(!$images_displayed)
					{
								// Display Field
								// -------------------------------------------------------
						
print('Title : ');
						
						
						$type_args = array(
							'type'=>'text',
							'name'=>'image_Title[]',
							'size'=>40,
							'maxlength'=>255,
						);
						
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
						
								
						$type_args = array(
							'type'=>'file',
							'name'=>'Image[]',
							'size'=>40,
							'maxlength'=>255,
						);
						
						$form->DisplayFormField($type_args);
						
								// Display 'Add' Button
								// -------------------------------------------------------
						
						$type_args = array(
							'type'=>'button',
							'id'=>'add-image-button',
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
						'id'=>'hidden-image-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
print('Title : ');
					
					
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
					
							
					$type_args = array(
						'type'=>'file',
						'name'=>'Image-Hidden',
						'size'=>40,
						'maxlength'=>255,
					);
					
					$form->DisplayFormField($type_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = array(
						'type'=>'button',
						'id'=>'delete-image-button',
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
						'id'=>'image-list',
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
						if($link && strlen($link['URL']))
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
							
									// Display Field
									// -------------------------------------------------------
							
print('Title : ');
							
							
							$type_args = array(
								'type'=>'text',
								'name'=>'link_Title[]',
								'size'=>40,
								'value'=>$link['Title'],
								'maxlength'=>255,
							);
							
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
								'selected'=>$link['URL'],
							];
							
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
						
print('Title : ');
						
						
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
							// Display Title Field
							// -------------------------------------------------------
					
print('Title : ');
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'link_Title[]',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display URL Field
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
					
					foreach ($this->eventdate as $eventdate) {
						if($eventdate && strlen($eventdate['EventDate'])) {
							if($event_dates_displayed) {
										// Start Field
										// -------------------------------------------------------
								
								$clear_float_divider_start_args = [
									'class'=>'input-divider',
								];
								
								$divider->displaystart($clear_float_divider_start_args);
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
							
print('Title : ');
							
							
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
							
							if(!$event_dates_displayed)
							{
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
						
print('Title : ');
						
						
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
							'selected'=>$eventdate['Approximate'],
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
					
print('Title : ');
					
					
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
					
					foreach ($this->association as $association)
					{
						if($association && strlen($association['ChosenEntryid']))
						{
							if($associations_displayed)
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
							
print('Entry ID Associated With : ');
							
							
							$type_args = [
								'type'=>'text',
								'name'=>'ChosenEntryid[]',
								'size'=>40,
								'maxlength'=>255,
								'value'=>$association['ChosenEntryid'],
							];
							
							$form->DisplayFormField($type_args);
							
									// Display Field
									// -------------------------------------------------------
							
							print('Type : ');
									
							print('<input type="text" list="Association_Type" name="association_Type[]" size="40" maxlength="255" value="' . $association['Type'] . '">');
							
									// Display Field
									// -------------------------------------------------------
							
							print('SubType : ');
							
							print('<input type="text" list="Association_SubType" name="association_SubType[]" size="40" maxlength="255" value="' . $association['SubType'] . '">');
							
							if(!$tags_displayed) {
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
							
							if($tags_displayed)
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
					
					if(!$associations_displayed)
					{
								// Display Field
								// -------------------------------------------------------
						
print('Entry ID Associated With : ');
						
						
						$type_args = [
							'type'=>'text',
							'name'=>'ChosenEntryid[]',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
						
								// Display Field
								// -------------------------------------------------------
						
						print('Type : ');
								
						print('<input type="text" list="Association_Type" name="association_Type[]" size="40" maxlength="255">');
						
								// Display Field
								// -------------------------------------------------------
						
						print('SubType : ');
						
						print('<input type="text" list="Association_SubType" name="association_SubType[]" size="40" maxlength="255">');
						
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
					
					
					$type_args = [
						'type'=>'text',
						'name'=>'ChosenEntryid-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
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

				case 'Publish':
					
					print('<input type="checkbox" name="Publish" value="1"');
					
					if($this->entry['Publish'] === 1) {
						print(' CHECKED="CHECKED');
					}
					
					print('>');
					
					break;
				
				case 'Save':
						// Save Button
						// -----------------------------------------------------
						
print('<center>');
					
					
					$type_args = [
						'type'=>'submit',
						'name'=>'Update',
						'value'=>'Update',
					];
					
					$form->DisplayFormField($type_args);
					
print('</center>');
					
					
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
					
					
						// Hidden Action
						// -----------------------------------------------------
					
					$type_args = [
						'type'=>'hidden',
						'name'=>'action',
						'value'=>'Update',
					];
					
					$form->DisplayFormField($type_args);
					
print('</center>');
					
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
		'thispage'=>'Update',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
	
?>
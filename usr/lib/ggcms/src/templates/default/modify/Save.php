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
		'main_text'=>(!empty($this->saveattemptresults) ? 'Saved!' : 'Save result') . ' &mdash; ' . $this->entry['Title'],
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>!empty($this->saveattemptresults) ? 'Saved Entry' : 'Save Result',
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
	
	print('<center><div style="width:70%;">');
	print('<table style="width:100%;" class="border-2px background-color-gray13 margin-top-5px">');

	print('<tbody><tr class="border-1px horizontal-left">');

	print('<td class="border-1px vertical-top">');

	print('<nobr>Adding Entry To:</nobr>');

	print('</td>');

	print('<td class="border-1px width-100percent vertical-top">');

	print($this->GetHyperlinkedEntryView([
		'entry'=>$this->parent,
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
	
			// Display Admin Errors
		
		// -------------------------------------------------------------
	
	$error_header_displayed = 0;
	
	if($this->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin() && $this->admin_errors) {
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
		
		foreach($this->admin_errors_display as $admin_error_to_display) {
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
					
					$element_text_args = [
						'text'=>$element_text,
					];
					
					
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
					
					if(count($printable_links)) {
						$element_text = implode('<br>', $printable_links);
					} else {
						$element_text = 'N/A';
					}
					
print($element_text);
					
					
					break;
				
				case 'Event Date':
					$printable_event_dates = [];
					
					foreach ($this->eventdate as $eventdate) {
						$event_date_time = $eventdate['EventDateTime'];
						
						if($event_date_time) {
							$event_date_time_printable = '&bull; ' . $event_date_time . ' (' . $eventdate['Title'] . ' ~ ' . $eventdate['Language'] . ')';
							$printable_event_dates[] = $event_date_time_printable;
						}
					}
					
					if(count($printable_event_dates)) {
						$element_text = implode('<br>', $printable_event_dates);
					} else {
						$element_text = 'N/A';
					}
					
print($element_text);
					
					
					break;
				
				case 'Association':
					$printable_associations = [];
					
					foreach ($this->association as $association) {
						$association_text = $association['ChosenEntryid'];
						if($association_text) {
							$association_text = '&bull; ' . $association_text;
							
							if($association['Type'] || $association['SubType']) {
								
								$association_text .= ' (';
								
								if($association['Type']) {
									$association_text .= $association['Type'];
									
									if($association['SubType']) {
										$association_text .= ' ~ ';
									}
								}
								
								if($association['SubType']) {
									$association_text .= $association['SubType'];
								}
								
								$association_text .= ')';
								
								$printable_associations[] = $association_text;
							}
						}
					}
					
					if(count($printable_associations)) {
						$element_text = implode('<br>', $printable_associations);
					} else {
						$element_text = 'N/A';
					}
					
print($element_text);
					
					
					break;
				
				case 'Definition':
					$printable_definitions = [];
					
					foreach ($this->definition as $definition) {
						$term_text = $definition['Term'];
						if($tag_text) {
							$term_text = '&bull; ' . $term_text . ' : ' . $definition['Definition'];
							
							$printable_definitions[] = $term_text;
						}
					}
					
					if(count($printable_definitions)) {
						$element_text = implode('<br>', $printable_definitions);
					} else {
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
	} else {
	
			// Re-Display Form
		
		// -------------------------------------------------------------
		
		$start_form_args = [
			'action'=>0,
			'method'=>'post',
			'files'=>1,
			'formclass'=>'margin-0px',
		];
		
		$form->StartForm($start_form_args);
		
				// Display Form Elements : Standard Elements
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/modify-text-field.php');
		ggreq('modules/html/modify-availability.php');
		
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
					$text_field = new module_modifytextfield([
						'that'=>$this,
						'name'=>'Title',
						'noamericanize'=>TRUE,
					]);
					$text_field->Display();
					
					break;
				
				case 'Subtitle':
					$text_field = new module_modifytextfield([
						'that'=>$this,
						'name'=>'Subtitle',
						'noamericanize'=>TRUE,
					]);
					$text_field->Display();
					
					break;
				
				case 'List Title':
					$text_field = new module_modifytextfield([
						'that'=>$this,
						'name'=>'ListTitle',
						'noamericanize'=>TRUE,
						'nolisttitlecase'=>TRUE,
					]);
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
					$entry_translation_field = new module_modifyentrytranslation([
						'that'=>$this,
						'subtitlerows'=>['value', 'blank', 'hidden'],
						'sortkeylabels'=>['blank'=>'List Title Sort Key: '],
					]);
					$entry_translation_field->Display();
					
					break;
					
				case 'Availability Dates':
					$availability_field = new module_modifyavailability(['that'=>$this]);
					$availability_field->Display();
					
					break;
					
				case 'Description':
					ggreq('modules/html/modify-description.php');
					$description_field = new module_modifydescription(['that'=>$this, 'hiddenlanguagelabel'=>'Language : ']);
					$description_field->Display();
					
					break;
					
				case 'Quote':
					ggreq('modules/html/modify-quote.php');
					$quote_field = new module_modifyquote([
						'that'=>$this,
						'hiddenlanguagelabel'=>'Language : ',
						'plainblank'=>TRUE,
					]);
					$quote_field->Display();
					
					break;
					
				case 'Text Body':
					ggreq('modules/html/modify-text-body.php');
					$text_body_field = new module_modifytextbody([
						'that'=>$this,
						'nooptions'=>TRUE,
						'textmaxlength'=>TRUE,
					]);
					$text_body_field->Display();
					
					break;
					
				case 'Image':
					ggreq('modules/html/modify-image.php');
					$image_field = new module_modifyimage([
						'that'=>$this,
						'nothumbnails'=>TRUE,
						'noindexing'=>TRUE,
					]);
					$image_field->Display();
					
					break;
				
				case 'Image Translation':
					ggreq('modules/html/modify-image-translation.php');
					$image_translation_field = new module_modifyimagetranslation(['that'=>$this]);
					$image_translation_field->Display();
					
					break;
				
				case 'Tag':
					ggreq('modules/html/modify-tag.php');
					$tag_field = new module_modifytag([
						'that'=>$this,
						'notagclass'=>TRUE,
						'nolanguagedefault'=>TRUE,
						'hiddenlanguagename'=>'tag_Language[]',
					]);
					$tag_field->Display();
					$tags_displayed = $tag_field->displayed;
					
					break;
					
				case 'Link':
					ggreq('modules/html/modify-link.php');
					$link_field = new module_modifylink([
						'that'=>$this,
						'urlonly'=>TRUE,
						'languagefromurl'=>TRUE,
						'hiddentitlename'=>'link_Title-Hidden[]',
					]);
					$link_field->Display();
					
					break;
				
				case 'Event Date':
					ggreq('modules/html/modify-event-date.php');
					$event_date_field = new module_modifyeventdate([
						'that'=>$this,
						'bydate'=>TRUE,
						'dividerbreaks'=>TRUE,
						'approximatefromlast'=>TRUE,
					]);
					$event_date_field->Display();
					
					break;
				
				case 'Association':
					ggreq('modules/html/modify-association.php');
					$association_field = new module_modifyassociation([
						'that'=>$this,
						'followtags'=>TRUE,
						'tagsdisplayed'=>$tags_displayed,
						'rawidrows'=>['hidden'],
						'noblanktypes'=>TRUE,
					]);
					$association_field->Display();
					
					break;
				
				case 'Publish':
					ggreq('modules/html/modify-publish.php');
					$publish_field = new module_modifypublish([
						'that'=>$this,
						'alwayschecked'=>TRUE,
					]);
					$publish_field->Display();
					
					break;
				
				case 'Save':
					ggreq('modules/html/modify-submit.php');
					$submit_field = new module_modifysubmit([
						'that'=>$this,
						'save'=>TRUE,
					]);
					$submit_field->Display();
					
					break;
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
		'thispage'=>'Save',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
	
?>
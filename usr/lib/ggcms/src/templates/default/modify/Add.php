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
	
	ggreq('modules/html/modify-header.php');
	$modifyheader = new module_modifyheader([
		'that'=>$this,
		'heading'=>'Adding Entry To',
		'subpage'=>'Add Entry',
	]);
	$modifyheader->Display();
	
			// Display Notices
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-notices.php');
	$modifynotices = new module_modifynotices([
		'that'=>$this,
		'padded'=>TRUE,
		'adding'=>TRUE,
	]);
	$modifynotices->DisplayEntryInfo();
	$modifynotices->DisplayErrors();
	
	if(count($this->admin_errors) || count($this->errors)) {
				// Display Form Elements : Start
			
			// -------------------------------------------------------------
		
		$divider_padding_start_args = [
			'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
		];
		
		$divider->displaystart($divider_padding_start_args);
		
				// Display Form Elements : Unavailable Message
			
			// -------------------------------------------------------------
			
		$table_start_args = [
			'id'=>$element,
			'tableclass'=>'width-100percent blank-element',
			'tableborder'=>'3',
			'cellvalign'=>'top',
		];
		$table->DisplayComponent_StartTable($table_start_args);
		
		print('<center><h4>Form Unavailable</h4></center>');
		
		$table_end_args = [
		];
		$table->DisplayComponent_EndTable($table_end_args);
		
				// Display Form Elements : End
			
			// -------------------------------------------------------------
		
		$divider_end_args = [
		];
		$divider->displayend($divider_end_args);
	} else {
				// Display Form Elements : Start
			
			// -------------------------------------------------------------
		
		$divider_padding_start_args = [
			'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
		];
		
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
				
				if($element === 'Event Date') {
					print('<div title="You may set a negative value, for instance, &quot;-571-00-00&quot;, to indicate the year 571 BCE.">');
				}
				
				print($this->ElementDisplay(['element'=>$element]));
				
				$element_hint = $hints[$element];
				
				if($element_hint) {
					print('<div style="max-width:200px;">');
					print($element_hint);
					print('</div>');
				}
				
				if($element === 'Event Date') {
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
			
			switch($element) {
				case 'Title':
					$type_args = [
						'type'=>'text',
						'name'=>'Title',
						'size'=>60,
						'maxlength'=>255,
					];
					
					if(!$this->handler->globals->AutoGenerateTitleDefault()) {
						$type_args['autofocus'] = true;
					}
					
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
						
						#if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
						#}
						
						print(' /> Smart Title-Case');
					}
					
					if($this->handler->globals->TitleAmericanize()) {
						print('<input type="checkbox" value="1" name="title-americanize" id="title-americanize" ');
						
						if($this->handler->globals->AmericanizeTitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
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
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					if($this->handler->globals->SubTitleAutoSmartTitleCase()) {
						print('<input type="checkbox" value="1" name="subtitle-smart-title-case" id="subtitle-smart-title-case" ');
						
					#	if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
					#	}
						
						print(' /> Smart Title-Case');
					}
					
					if($this->handler->globals->SubtitleAmericanize()) {
						print('<input type="checkbox" value="1" name="subtitle-americanize" id="subtitle-americanize" ');
						
						if($this->handler->globals->AmericanizeSubtitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
						print(' /> Americanize');
					}
					
					break;
				
				case 'List Title':
					$type_args = [
						'type'=>'text',
						'name'=>'ListTitle',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					if($this->handler->globals->ListTitleAutoSmartTitleCase()) {
						print('<input type="checkbox" value="1" name="listtitle-smart-title-case" id="listtitle-smart-title-case" ');
						
					#	if($this->handler->globals->AutoGenerateTitleDefault()) {
							print('CHECKED="CHECKED"');
					#	}
						
						print(' /> Smart Title-Case');
					}
					
					if($this->handler->globals->ListTitleAmericanize()) {
						print('<input type="checkbox" value="1" name="listtitle-americanize" id="listtitle-americanize" ');
						
						if($this->handler->globals->AmericanizeListTitleDefault()) {
							print('CHECKED="CHECKED"');
						}
						
						print(' /> Americanize');
					}
					
					break;
				
				case 'List Title Sort Key':
					$type_args = [
						'type'=>'text',
						'name'=>'ListTitleSortKey',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Entry Translation':
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
							
					print('List Title : ');
					
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
						'id'=>'add-entrytranslation-button',
						'class'=>'float-right',
						'value'=>'Add Entry Translation',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-entrytranslation-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
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
							
					print('List Title : Sort key ');
					
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
						'id'=>'delete-entrytranslation-button',
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
						'id'=>'entrytranslation-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					$clear_float_divider_end_args = [
					];
					
					$divider->displayend($clear_float_divider_end_args);
				
					break;
					
				case 'Code':
					$type_args = [
						'type'=>'text',
						'name'=>'Code',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Child Adjective':
					$type_args = [
						'type'=>'text',
						'name'=>'ChildAdjective',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Child Noun':
					$type_args = [
						'type'=>'text',
						'name'=>'ChildNoun',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Child Noun Plural':
					$type_args = [
						'type'=>'text',
						'name'=>'ChildNounPlural',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Grand Child Adjective':
					$type_args = [
						'type'=>'text',
						'name'=>'GrandChildAdjective',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Grand Child Noun':
					$type_args = [
						'type'=>'text',
						'name'=>'GrandChildNoun',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Grand Child Noun Plural':
					$type_args = [
						'type'=>'text',
						'name'=>'GrandChildNounPlural',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					break;
					
				case 'Availability Start':
					$availability_start_args = [
						'type'=>'text',
						'name'=>'AvailabilityStartDate[]',
						'size'=>20,
						'class'=>'datepicker',
					];
					
					$form->DisplayFormField($availability_start_args);
					
					$availability_start_args = [
						'type'=>'text',
						'name'=>'AvailabilityStartTime[]',
						'size'=>20,
						'class'=>'timepicker',
					];
					
					$form->DisplayFormField($availability_start_args);
				
					break;
					
				case 'Availability End':
					$availability_end_args = [
						'type'=>'text',
						'name'=>'AvailabilityEndDate[]',
						'size'=>20,
						'class'=>'datepicker',
					];
					
					$form->DisplayFormField($availability_end_args);
					
					$availability_end_args = [
						'type'=>'text',
						'name'=>'AvailabilityEndTime[]',
						'size'=>20,
						'class'=>'timepicker',
					];
					
					$form->DisplayFormField($availability_end_args);
				
					break;
					
				case 'Description':
							// Display Field
							// -------------------------------------------------------
							
					$type_args = [
						'type'=>'textarea',
						'name'=>'Description[]',
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
						'maxlength'=>512,
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
					
					print('Language: ');
					
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
							// Display Field
							// -------------------------------------------------------
							
					$type_args = [
						'type'=>'textarea',
						'name'=>'Text[]',
						'cols'=>60,
						'rows'=>20,
						'class'=>'float-left',
					];
					
					if($this->handler->globals->AutoGenerateTitleDefault()) {
						$type_args['autofocus'] = true;
					}
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
					$this->SourceDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'textbody_Source[]',
						'class'=>'textbody_Source',
						'size'=>30,
						'maxlength'=>512,
					];
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
					print("<br>");
print('Strip URLS : ');
					
					
					$type_args = [
						'type'=>'checkbox',
						'name'=>'textbody_StripURLs[]',
						'value'=>'1',
						'size'=>30,
						'maxlength'=>512,
						'checked'=>'CHECKED',
					];
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
					print("<br>");
					
print('Americanize Vocabulary : ');
					
					
					$type_args = [
						'type'=>'checkbox',
						'name'=>'textbody_AmericanizeVocabulary[]',
						'value'=>'1',
						'size'=>30,
						'maxlength'=>512,
						'checked'=>'CHECKED',
					];
					
					$form->DisplayFormField($type_args);
						
							// Display Field
							// -------------------------------------------------------
					
					print("<br>");
					
print('HTML Formatting : ');
					
					
					$type_args = [
						'type'=>'checkbox',
						'name'=>'textbody_HTMLFormatting[]',
						'value'=>'1',
						'size'=>30,
						'maxlength'=>512,
						'checked'=>$this->handler->globals->AddEntryHTMLFormatting(),
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
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-image-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
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
					
					break;
					
				case 'Image Translation':
							// Display Field
							// -------------------------------------------------------
					
					$this->TitleDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'imagetranslation_Title[]',
						'size'=>40,
						'maxlength'=>255,
					];
					
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
					
					$type_args = [
						'type'=>'button',
						'id'=>'add-image-translation-button',
						'class'=>'float-right',
						'value'=>'Add Image Translation',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-image-translation-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Field
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
						'name'=>'imagetranslation_Language-Hidden',
						'options'=>$this->SelectableLanguages,
						'selected'=>'en',
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-image-translation-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					print('</div>');
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'image-translation-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					print('</div>');
					
					break;
				
				case 'Tag':
							// Display Field
							// -------------------------------------------------------
					
					print('Tag : ');
							
					$type_args = [
						'type'=>'text',
						'name'=>'Tag[]',
						'size'=>40,
						'maxlength'=>255,
					];
					
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
					
					$type_args = [
						'type'=>'button',
						'id'=>'add-tag-button',
						'class'=>'float-right',
						'value'=>'Add Tag',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
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
					
					$type_args = [
						'type'=>'text',
						'name'=>'Tag-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
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
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-tag-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					print('</div>');
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'tag-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					print('</div>');
					
					break;
					
				case 'Link':
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
					
							// Display URL Field
							// -------------------------------------------------------
					
					print('URL : ');
							
					$type_args = [
						'type'=>'text',
						'name'=>'URL[]',
						'size'=>40,
						'maxlength'=>255,
					];
					
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
					
					$type_args = [
						'type'=>'button',
						'id'=>'add-link-button',
						'class'=>'float-right',
						'value'=>'Add Link',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-link-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
					$this->TitleDisplay();
					
					$type_args = [
						'type'=>'text',
						'name'=>'link_Title-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display Hidden Input
							// -------------------------------------------------------
					
					print('URL : ');
					
					$type_args = [
						'type'=>'text',
						'name'=>'URL-Hidden',
						'size'=>40,
						'maxlength'=>255,
					];
					
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
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-link-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					print('</div>');
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'link-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					print('</div>');
					
					break;
					
				case 'Event Date':
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
						'selected'=>$eventdate['Approximate'],
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Add' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'add-event-date-button',
						'class'=>'float-right',
						'value'=>'Add Event Date',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-event-date-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Hidden Input
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
						'selected'=>$eventdate['Approximate'],
					];
					
					$form->DisplayFormField($language_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-event-date-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					print('</div>');
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'event-date-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					print('</div>');
					
					break;
					
				case 'Association':
							// Display Field
							// -------------------------------------------------------
					
					print('Entry ID Associated With : ');
					
					$entrycodes = $this->handler->globals->AssociationEntryCodes();
					
					if($this->showAssociationRawIds()) {
						$type_args = [
							'type'=>'text',
							'name'=>'ChosenEntryid[]',
							'class'=>'ChosenEntryid',
							'size'=>40,
							'maxlength'=>255,
						];
						
						$form->DisplayFormField($type_args);
					} else {
						print('<select class="ChosenEntryid" name="ChosenEntryid[]">');
						
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
					
							// Display 'Add' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'add-association-button',
						'class'=>'float-right',
						'value'=>'Add Association',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Start Hidden Field
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'hidden-association-input',
						'class'=>'display-none',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
							// Display Title Field
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
							'selected'=>$association['ChosenEntryid'],
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
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-association-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					print('</div>');
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'association-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					print('</div>');
					
					break;
					
				case 'Definition':
							// Autogen?
							// -------------------------------------------------------
				
					print('Autogenerate Definitions : ');
					
					$type_args = [
						'type'=>'checkbox',
						'name'=>'autogenerate-definitions',
						'value'=>1,
						'maxlength'=>512,
					];
					
					$form->DisplayFormField($type_args);
					
					print('<br>');
					
							// Display Field
							// -------------------------------------------------------
					
					print('Definition : ');
					
					$type_args = [
						'type'=>'text',
						'name'=>'Term[]',
						'size'=>20,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					$type_args = [
						'type'=>'text',
						'name'=>'definition_Definition[]',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display 'Add' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'add-definition-button',
						'class'=>'float-right',
						'value'=>'Add Definition',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
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
					
					$type_args = [
						'type'=>'text',
						'name'=>'Term-Hidden',
						'size'=>20,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
					$type_args = [
						'type'=>'text',
						'name'=>'definition_Definition-Hidden',
						'size'=>60,
						'maxlength'=>255,
					];
					
					$form->DisplayFormField($type_args);
					
							// Display 'Delete' Button
							// -------------------------------------------------------
					
					$type_args = [
						'type'=>'button',
						'id'=>'delete-definition-button',
						'class'=>'float-right',
						'value'=>'Remove',
					];
					
					$form->DisplayFormField($type_args);
					
							// Clear Float
							// -------------------------------------------------------
					
					print('<div class="clear-float"></div>');
					
							// Finish Hidden Field
							// -------------------------------------------------------
					
					print('</div>');
					
							// Establish Bottom Hidden Div for JS-Showing
							// -------------------------------------------------------
					
					$clear_float_divider_start_args = [
						'id'=>'definition-list',
					];
					
					$divider->displaystart($clear_float_divider_start_args);
					
					print('</div>');
					
					break;
				
				case 'Publish':
					
					print('<input type="checkbox" name="Publish" value="1"');
					
					#if($this->entry['Publish'] === 1) {		# FIXME: Globals-ify
						print(' CHECKED="CHECKED"');
					#}
					
					print('>');
					
					break;
				
				case 'Save':
					print('<center>Save This Entry : ');
					
					$type_args = [
						'type'=>'submit',
						'name'=>'Save',
						'value'=>'Save',
					];
					
					$form->DisplayFormField($type_args);
					
					$type_args = [
						'type'=>'hidden',
						'name'=>'action',
						'value'=>'Save',
					];
					
					$form->DisplayFormField($type_args);
					
					print('</center>');
					
					break;
			}
			
			$table_end_args = [
			];
			$table->DisplayComponent_EndTable($table_end_args);
		}
		
				// Display Form Elements : End
			
			// -------------------------------------------------------------
		
		print('</form>');
		print('</div>');
	}
	
	$bottom_navigation_args = [
		'thispage'=>'Add',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
?>
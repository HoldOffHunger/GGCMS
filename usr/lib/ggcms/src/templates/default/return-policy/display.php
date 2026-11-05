<?php

			// Alternate Formats

		// -------------------------------------------------------------

	if(	($this->script_format_lower == 'pdf') ||
		($this->script_format_lower == 'rtf') ||
		($this->script_format_lower == 'epub') ||
		($this->script_format_lower == 'daisy') ||
		($this->script_format_lower == 'sgml') ||
		($this->script_format_lower == 'tex') ||
		($this->script_format_lower == 'json') ||
		($this->script_format_lower == 'xml') ||
		($this->script_format_lower == 'txt') ||
		($this->script_format_lower == 'csv') ||
		($this->script_format_lower == 'opds') ||
		($this->script_format_lower == 'rdf') ||
		($this->script_format_lower == 'html' && $this->Param('printerfriendly')) ||
		($this->script_format_lower == 'html' && $this->Param('invertedcolors'))
	) {
		$html_document = implode("\n", $this->getReturnPolicyParagraphs());

		$html_document = '<h1>' . $this->GetHTMLFormatData_Title() . '</h1>' . "\n" . $html_document;

		if(	($this->script_format_lower == 'pdf') ||
			($this->script_format_lower == 'tex') ||
			($this->script_format_lower == 'rtf') ||
			($this->script_format_lower == 'daisy') ||
			($this->script_format_lower == 'sgml')
		) {
			$this->source_content = $html_document;
		} elseif(	($this->script_format_lower == 'opds') ||
					($this->script_format_lower == 'rdf') ||
					($this->script_format_lower == 'json')
		) {
			$this->record_to_use['returnpolicy'] = $html_document;
		} elseif($this->script_format_lower == 'xml' || $this->script_format_lower == 'csv') {
			$this->record_to_use['returnpolicy'] = $html_document;
			$this->source_content = $this->record_to_use;
		} elseif($this->script_format_lower == 'epub') {
			$this->record_to_use = $this->entry;
			$this->source_content = $html_document;
		} elseif($this->script_format_lower == 'txt') {
			$text_document = strip_tags($html_document);

			if($this->Param('wrapped'))
			{
				$text_document = wordwrap($text_document, 75, "\n", FALSE);
			}

			print($text_document);
		} else {
			print('<div class="font-family-arial">');

			print($html_document);

			print('</div>');
		}
	} elseif($this->script_format_lower == 'html') {

				// Standard Requires

			// -------------------------------------------------------------

		ggreq('modules/spacing.php');

		ggreq('modules/' . $this->script_format_lower . '/divider.php');
		$divider = new module_divider;

		ggreq('modules/' . $this->script_format_lower . '/list/generic.php');
		$generic_list = new module_genericlist;

		ggreq('modules/' . $this->script_format_lower . '/languages.php');
		$languages_args = [
			'languageobject'=>$this->language_object,
			'domainobject'=>$this->domain_object,
		];
		$languages = new module_languages($languages_args);

		ggreq('modules/' . $this->script_format_lower . '/navigation.php');
		$navigation_args = [
			'globals'=>$this->handler->globals,
			'languageobject'=>$this->language_object,
			'divider'=>$divider,
			'domainobject'=>$this->domain_object,
		];
		$navigation = new module_navigation($navigation_args);

				// Display Header

			// -------------------------------------------------------------

		$third_color = 'B7CEEC';

		ggreq('modules/html/entry-header.php');
		ggreq('modules/html/entry-index-header.php');
		$entryindexheader = new module_entryindexheader([
			'that'=>$this,
			'main_text'=>$this->header_title_text,
		]);

		$entryindexheader->Display();

				// Basic Divider Arguments

			// -------------------------------------------------------------

		$file_divider = [
			'class'=>'width-100percent horizontal-left margin-5px font-family-arial',
		];

		$divider_instruction_area_start_args = [
			'class'=>'width-50percent border-1px horizontal-center margin-top-22px',
		];

		$divider_end_args = [
		];

				// Return Policy Instructions

			// -------------------------------------------------------------

		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-70percent horizontal-center border-2px background-color-' . $third_color . ' margin-top-14px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				],
			],
			'list'=>[[
				'This is the return policy for ' . $this->master_record['Code'] . '.',
			]],
		];
		$generic_list->Display($version_list_display_args);

				// Start Top Bar

			// -------------------------------------------------------------

		print('<div class="horizontal-center width-95percent margin-top-5px">');

				// Breadcrumbs Info

			// -------------------------------------------------------------

		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>'Return Policy']);
		$breadcrumbs->Display();

				// Login Info

			// -------------------------------------------------------------

		ggreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();

				// End Top Bar

			// -------------------------------------------------------------

		print('</div>');

		$divider->displaystart(['class'=>'clear-float']);
		$divider->displayend([]);

				// Alternate Formats Info

			// -------------------------------------------------------------

		ggreq('modules/html/alternateformats.php');
		$alternateformats = new module_alternateformats(['that'=>$this]);
		$alternateformats->Display();

		$divider->displaystart(['class'=>'clear-float']);
		$divider->displayend([]);

		print("</div>");

				// Display Return Policy

			// -------------------------------------------------------------

		$divider->displaystart($divider_instruction_area_start_args);

		print('<center><h2 class="margin-5px font-family-tahoma">' . $this->master_record['Code'] . ' Return Policy :</h2></center>');

		$divider->displaystart($file_divider);

		$return_policy_text = $this->getReturnPolicy();
		$return_policy_text = str_replace('<p>', '<p class="margin-top-10px margin-bottom-0px text-indent-25px">', $return_policy_text);

		print('<div class="text-to-play-as-audio">');
		print($return_policy_text);
		print('</div>');

		$divider->displayend($divider_end_args);

		$divider->displayend($divider_end_args);

				// Display Language Options

			// -------------------------------------------------------------

		$languages->display();

				// Display Final Ending Navigation

			// -------------------------------------------------------------

		$navigation->DisplayBottomNavigation(['thispage'=>'ReturnPolicy']);
	}

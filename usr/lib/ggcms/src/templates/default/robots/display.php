<?php
	
			// HTML Displaying
		
		// -------------------------------------------------------------
	
	if($this->script_format_lower == 'html')
	{
				// Standard Requires
			
			// -------------------------------------------------------------
	
		require(GGCMS_DIR . 'modules/spacing.php');
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/text.php');
		$text = new module_text;
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/form.php');
		$form = new module_form;
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/divider.php');
		$divider = new module_divider;
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/table.php');
		$table = new module_table;
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/list/generic.php');
		$generic_list = new module_genericlist;
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/header.php');
		$header = new module_header;
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/languages.php');
		$languages_args = [
			'languageobject'=>$this->language_object,
			'domainobject'=>$this->domain_object,
		];
		$languages = new module_languages($languages_args);
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/navigation.php');
		$navigation_args = [
			'globals'=>$this->handler->globals,
			'languageobject'=>$this->language_object,
			'divider'=>$divider,
			'domainobject'=>$this->domain_object,
		];
		$navigation = new module_navigation($navigation_args);
		
				// Get Info Header Language
			
			// -------------------------------------------------------------
		
		if($this->language_object->getLanguageCode() == 'en')
		{
			$div_mouseover = $this->master_record['description'][0]['Description'];
		}
		else
		{
			$instructions_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainInstructionsContent']);
			
			if($instructions_language_translations[$this->language_object->getLanguageCode()])
			{
				$div_mouseover = $instructions_language_translations[$this->language_object->getLanguageCode()];
			}
			else
			{
				$div_mouseover = $this->master_record['description'][0]['Description'];
			}
		}
		
		if($this->language_object->getLanguageCode() == 'en')
		{
			$quote_text = $this->master_record['quote'][0]['Quote'];
		}
		else
		{
			$image_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainImageQuote']);
			
			if($image_language_translations[$this->language_object->getLanguageCode()])
			{
				$quote_text = $image_language_translations[$this->language_object->getLanguageCode()];
			}
			else
			{
				$quote_text = $this->master_record['quote'][0]['Quote'];
			}
		}
		
				// Display Header
			
			// -------------------------------------------------------------
		
		$primary_color = '6495ED';
		$third_color = 'B7CEEC';
		
		require(GGCMS_DIR . 'modules/html/entry-header.php');
		require(GGCMS_DIR . 'modules/html/entry-index-header.php');
		$entryindexheader = new module_entryindexheader([
			'that'=>$this,
			'main_text'=>$this->header_title_text,
		]);
		
		$entryindexheader->Display();
		
				// Basic Divider Arguments
			
			// -------------------------------------------------------------
		
		$divider_navigation_args = [
			'class'=>'width-95percent horizontal-center margin-top-14px border-2px',
		];
		
		$robots_file_divider = [
			'class'=>'width-100percent horizontal-left margin-5px',
		];
		
		$divider_instruction_area_start_args = [
			'class'=>'width-50percent border-1px horizontal-center margin-top-22px',
		];
		
				// Get Info Header Language
			
			// -------------------------------------------------------------
		
		if($this->language_object->getLanguageCode() == 'en')
		{
			$robots_instructions_text = 'This is the human-readable version of our robots.txt file.  The <a href="' . $primary_url . '/robots.txt">Robots.txt File</a> is what actual search engines will crawl.  An alternate <a href="' . $primary_url . '/robots.xml">Robots.xml File</a> has also been provided if you need something more machine-readable.  The XML version also has a <a href="' . $primary_url . '/robots.xml?humanreadable=1">Robots.xml Human-Readable File</a>.';
		}
		else
		{
			$robots_instructions_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesRobotsInstructions']);
			
			if($robots_instructions_language_translations[$this->language_object->getLanguageCode()])
			{
				$robots_instructions_text = $robots_instructions_language_translations[$this->language_object->getLanguageCode()];
			}
			else
			{
				$robots_instructions_text = 'This is the human-readable version of our robots.txt file.  The <a href="' . $primary_url . '/robots.txt">Robots.txt File</a> is what actual search engines will crawl.  An alternate <a href="' . $primary_url . '/robots.xml">Robots.xml File</a> has also been provided if you need something more machine-readable.  The XML version also has a <a href="' . $primary_url . '/robots.xml?humanreadable=1">Robots.xml Human-Readable File</a>.';
			}
		}
		
				// Link to Actual Robots File
			
			// -------------------------------------------------------------
		
		$primary_url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
		
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
				$robots_instructions_text,
			]],
		];
		$generic_list->Display($version_list_display_args);
		
				// Describe Robots File
			
			// -------------------------------------------------------------
		
		$version_list_display_args = array(
			'options'=>array(
				'tableheaders'=>0,
				'tableclass'=>'width-90percent horizontal-center border-2px background-color-' . $third_color . ' margin-top-14px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>array(
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				),
			),
			'list'=>$this->robots,
		);
		$generic_list->Display($version_list_display_args);
		
				// Get Info Header Language
			
			// -------------------------------------------------------------
		
		if($this->language_object->getLanguageCode() == 'en')
		{
			$robots_filename_text = 'Robots.txt File';
		}
		else
		{
			$robots_filename_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesRobotsFilename']);
			
			if($robots_filename_language_translations[$this->language_object->getLanguageCode()])
			{
				$robots_filename_text = $robots_filename_language_translations[$this->language_object->getLanguageCode()];
			}
			else
			{
				$robots_filename_text = 'Robots.txt File';
			}
		}
		
				// Display Robots File
			
			// -------------------------------------------------------------
		
		$divider->displaystart($divider_instruction_area_start_args);
		
print('<center><h2 class="margin-5px font-family-tahoma">' . $robots_filename_text . ' :</h2></center>');
		
		
		$divider->displaystart($robots_file_divider);
		
print('<code><pre>' . $this->robots_txt_file . '</pre></code>');
		
		
		$divider->displayend($divider_end_args);
		
		$divider->displayend($divider_end_args);
		
				// Display Final Ending Navigation
			
			// -------------------------------------------------------------
		
		$bottom_navigation_args = [
			'thispage'=>'',
		];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
	}
	
			// TXT Displaying
		
		// -------------------------------------------------------------
	
	elseif($this->script_format_lower == 'txt')
	{
				// Standard Requires
			
			// -------------------------------------------------------------
	
		require(GGCMS_DIR . 'modules/spacing.php');
		
		require(GGCMS_DIR . 'modules/html/text.php');
		$text = new module_text;
		
		#print("robots.txt, please");
		
print($this->robots_txt_file);
		
	}
	
	elseif($this->script_format_lower == 'xml')
	{
				// Display Robots Data
			
			// -------------------------------------------------------------
		
		require(GGCMS_DIR . 'modules/spacing.php');
		
		require(GGCMS_DIR . 'modules/' . $this->script_format_lower . '/list/generic.php');
		$generic_list = new module_genericlist;
		
		$version_list_display_args = array(
			'options'=>array(
				'tableheaders'=>0,
				'tableclass'=>'width-90percent horizontal-center border-2px background-color-B7CEEC margin-top-14px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>array(
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				),
				'humanreadable'=>$this->humanreadable,
			),
			'list'=>$this->robots,
		);
		$generic_list->Display($version_list_display_args);
	}
	
?>
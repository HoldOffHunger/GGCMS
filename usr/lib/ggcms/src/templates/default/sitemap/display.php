<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------
	
	classreq('modules/spacing.php');
	
	ggreq('modules/' . $this->script_format_lower . '/text.php');
	$text = new module_text;
	
	ggreq('modules/' . $this->script_format_lower . '/form.php');
	$form = new module_form;
	
	ggreq('modules/' . $this->script_format_lower . '/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/' . $this->script_format_lower . '/table.php');
	$table = new module_table;
	
	ggreq('modules/' . $this->script_format_lower . '/list/generic.php');
	$generic_list = new module_genericlist;
	
	ggreq('modules/' . $this->script_format_lower . '/header.php');
	$header = new module_header;
	
	if($this->script_format_lower == 'html')
	{
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
	}
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_navigation_args = [
		'class'=>'width-95percent horizontal-center margin-top-14px border-2px',
	];
	
	$divider_instruction_area_start_args = [
		'class'=>'width-50percent border-1px horizontal-center margin-top-22px',
	];
	
			// Display Header
		
		// -------------------------------------------------------------
	
	classreq('modules/html/entry-header.php');
	classreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'SiteMap of ' . $this->master_record['Title'],
	]);
	
	$entryindexheader->Display();
	
			// Get Instructions Language
		
		// -------------------------------------------------------------
	
	if($this->language_object->getLanguageCode() == 'en')
	{
		$sitemap_instructions_text = 'This is the site map.  You will find a list of every page on this site here.  The <a href="' . $primary_url . '/sitemap.xml">XML Sitemap</a> and a <a href="' . $primary_url . '/sitemap.xml?humanreadable=1">Human-Readable XML Sitemap</a> are also available, as well as a <a href="' . $primary_url . '/sitemap.txt">TXT Sitemap</a>.';
	}
	else
	{
		$sitemap_header_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesSitemapInstructions']);
		
		if($sitemap_header_language_translations[$this->language_object->getLanguageCode()])
		{
			$sitemap_instructions_text = $sitemap_header_language_translations[$this->language_object->getLanguageCode()];
		}
		else
		{
			$sitemap_instructions_text = 'This is the site map.  You will find a list of every page on this site here.  The <a href="' . $primary_url . '/sitemap.xml">XML Sitemap</a> and a <a href="' . $primary_url . '/sitemap.xml?humanreadable=1">Human-Readable XML Sitemap</a> are also available, as well as a <a href="' . $primary_url . '/sitemap.txt">TXT Sitemap</a>.';
		}
	}
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	if($this->script_format_lower == 'html')
	{
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
				$sitemap_instructions_text,
			]],
		];
		$generic_list->Display($version_list_display_args);
	}
	
			// Display Sitemap
		
		// -------------------------------------------------------------
	
	$indent_level = 1;
	
	if($this->IsPaginatedSitemap()) {
		$indent_level = 0;
	}
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-90percent horizontal-center border-2px background-color-' . $third_color . ' margin-top-14px',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px vertical-top',
				'border-1px width-100percent vertical-top',
			],
			'humanreadable'=>$this->humanreadable,
		],
		'list'=>$this->sitemap,
	];
	$generic_list->Display($version_list_display_args);
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	if($this->script_format_lower == 'html')
	{
		$bottom_navigation_args = [
			'thispage'=>'Sitemap',
		];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
	}
	
?>
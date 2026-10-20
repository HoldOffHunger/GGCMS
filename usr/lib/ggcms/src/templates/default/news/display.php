<?php
	
			// Format-Universal Formatting
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
			// Every Format but the Web Page
		
		// -------------------------------------------------------------
	
	if($this->script_format_lower != 'html' || $this->Param('printerfriendly') || $this->Param('invertedcolors'))
	{
		ggreq('modules/html/entry-formats.php');
		$formats = new module_entryformats(['that'=>$this]);
		$formats->Display();
	}
	
			// HTML Format
		
		// -------------------------------------------------------------
			
	else
	{
				// Basic Requirements
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-quote.php');
		$entry_quotes = new module_entryquotes(['that'=>$this]);
		
		ggreq('modules/html/entry-sort.php');
		$entrysort = new module_entrysort(['that'=>$this]);
		
				// Timeframe and Header
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-date.php');
		$entrydate = new module_entrydate(['that'=>$this]);
		$time_data = $entrydate->getSimpleData();
		
		ggreq('modules/html/entry-header.php');
		$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_data['text']]);
		
				// Standard Requires
			
			// -------------------------------------------------------------
	
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
		
		$entryheader->Display();
		
				// Admin Controls and Top Bar
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/view-top-bar.php');
		$viewtopbar = new module_viewtopbar([
			'that'=>$this,
			'subpage'=>'News',
			'width'=>'100percent',
		]);
		$viewtopbar->Display(['file'=>__FILE__]);
		
				// Display Associations
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-association.php');
		$entry_association = new module_entryassociation(['that'=>$this, 'header'=>'']);
		$entry_association->Display([]);
		
				// Display Description
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-description.php');
		$entry_description = new module_entrydescription(['that'=>$this, 'header'=>'']);
		$entry_description->Display();
		
				// Display Quotes
			
			// -------------------------------------------------------------
			
		$entry_quotes->Display(['max'=>1, 'header'=>'']);
		
		print('<div class="horizontal-center width-95percent">');
		
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<h2 class="horizontal-left margin-5px font-family-arial">');
		print('New Items');
		print('</h2>');
		print('</div>');
		
		print('<div class="border-2px background-color-gray15 margin-5px float-right">');
		
		print('<p style="margin:5px;padding:0px;font-family:\'arial\', \'tahoma\';">');
		
		print('<a href="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php?items=20">');
		print('20 Items');
		print('</a>');
		
		print(' | ');
		
		print('<a href="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php?items=50">');
		print('50 Items');
		print('</a>');
		
		print(' | ');
		
		print('<a href="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php?items=100">');
		print('100 Items');
		print('</a>');
		
		print(' | ');
		
		print('<a href="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php?items=500">');
		print('500 Items');
		print('</a>');
		
		print(' | ');
		
		print('<a href="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php?items=1000">');
		print('1,000 Items');
		print('</a>');
		
		print('</p>');
		
	#	print('<h2 class="horizontal-left margin-5px font-family-arial">');
	#	print('New Items');
	#	print('</h2>');
		print('</div>');
		
		print('</div>');
		
				// Display Children
			
			// -------------------------------------------------------------
		ggreq('modules/html/entry-children.php');
		$entry_children = new module_entrychildren(['that'=>$this, 'entrysort'=>$entrysort, 'header'=>'']);
		$entry_children->Display_Entries([
			'entries'=>$this->newest_entries,
			'count'=>count($this->newest_entries),
			'datefield'=>'OriginalCreationDate',
		]);
		
				// DEBUG
			
			// -------------------------------------------------------------
			
	#	ggreq('modules/html/entry-debug.php');
	#	$entry_debug = new module_entrydebug(['that'=>$this]);
	#	$entry_debug->Debug([]);
		
				// Display Final Ending Navigation
			
			// -------------------------------------------------------------
		
		$bottom_navigation_args = [
			'thispage'=>'News',
		];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
	}
?>
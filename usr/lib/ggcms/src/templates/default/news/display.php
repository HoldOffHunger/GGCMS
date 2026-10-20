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
		
				// Timeframe and Header
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-date.php');
		$entrydate = new module_entrydate(['that'=>$this]);
		$time_data = $entrydate->getSimpleData();
		
		ggreq('modules/html/entry-header.php');
		$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_data['text']]);
		
				// Standard Requires
			
			// -------------------------------------------------------------
	
		ggreq('modules/html/divider.php');
		$divider = new module_divider;
		
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
		
				// Display New Items
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/news-items.php');
		$news_items = new module_newsitems(['that'=>$this]);
		$news_items->Display();
		
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
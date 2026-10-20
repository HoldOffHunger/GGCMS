<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
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
	
	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text,
	]);
	
	$entryheader->Display();
	
			// Admin Controls and Top Bar
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/view-top-bar.php');
	$viewtopbar = new module_viewtopbar(['that'=>$this]);
	$viewtopbar->Display(['file'=>__FILE__]);
	
			// Total and Introduction
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-introduction.php');
	$index_introduction = new module_indexintroduction(['that'=>$this]);
	$index_introduction->Display();
			
			// New Entries
	
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-new.php');
	$index_new = new module_indexnew([
		'that'=>$this,
		'entrysort'=>$entrysort,
	]);
	$index_new->Display();
			
			// Random Entries
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-random.php');
	$index_random = new module_indexrandom([
		'that'=>$this,
		'entrysort'=>$entrysort,
	]);
	$index_random->Display();
	
			// Images, Tags, Quotes, Descriptions, Textbodies, Event Dates and Likes
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-sections.php');
	$index_sections = new module_indexsections(['that'=>$this]);
	$index_sections->DisplayImages(['header'=>'I Never Forget a ' . $this->entry['ChildNoun'] . ' Book']);
	$index_sections->DisplayTags(['header'=>'Tagged']);
	$index_sections->DisplayQuotes(['header'=>'I\'ve Heard It Said...']);
	$index_sections->DisplayDescriptions(['header'=>'Let Me Tell You About a Book I Know']);
	$index_sections->DisplayTexts(['header'=>'Texts']);
	$index_sections->DisplayEventDates(['header'=>'Fragments of Our Past']);
	$index_sections->DisplayLikes(['header'=>'Well-Liked Books']);
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-share.php');
	$index_share = new module_indexshare(['that'=>$this]);
	$index_share->Display();
			
			// Debug
		
		// -------------------------------------------------------------
	
	/*
	print("<PRE>RECORD LIST:");
	print_r($this->record_list);
	print("\n\nMASTER RECORD:\n\n");
	print_r($this->master_record);
	print("\n\nPARENT:\n\n");
	print_r($this->parent);
	print("\n\nENTRY:\n\n");
	print_r($this->entry);
	print("\n\nCHILDREN:\n\n");
	print_r($this->children);
	print("</PRE>");
	*/
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
<?php
		
			// To Globalize
		
		// -------------------------------------------------------------
		
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
	
			// Stats
		
		// -------------------------------------------------------------

	$date_epoch_time = strtotime($this->child_record_stats['LastModificationDate']);
	$full_date = date("F d, Y; H:i:s", $date_epoch_time);
	/*
	$sub_text =
		str_replace('<p>', '<p class="horizontal-left margin-5px font-family-tahoma">', $this->entry['textbody'][0]['Text']) . 
		'<p class="horizontal-left margin-5px font-family-tahoma">' .
		'This SITE contains ' . number_format($this->child_record_stats['ChildRecordCount']) . ' texts, with ' . number_format($this->child_record_stats['ChildWordCount']) . ' words or ' . number_format($this->child_record_stats['ChildCharacterCount']) . ' characters.' .
		'</p>';
	*/
	
	$sub_text = 'Abstract Collection, Meeting Scheduling, Session Planning, Peer Review, Continuing Education, Registration.';
	$sub_title = 'Last Updated: ' . $full_date . '.';
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text,
		'sub_text'=>$sub_text,
		'sub_title'=>$sub_title,
	]);
	
	$entryheader->Display();
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/view-top-bar.php');
	$viewtopbar = new module_viewtopbar(['that'=>$this]);
	$viewtopbar->DisplayAdminControls(['file'=>__FILE__]);
	
			// Newest-Entries Record List
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-newest.php');
	$entry_newest = new module_entrynewest(['that'=>$this]);
	
	$entry_newest->Display();
	
			// Sections, and the Entries of Writings and People
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-collections.php');
	$index_collections = new module_indexcollections([
		'that'=>$this,
		'order'=>[
			'political-struggle'=>0,
			'economic-struggle'=>1,
			'education-struggle'=>2,
			'religious-struggle'=>3,
			'social-struggle'=>4,
			'ecological-struggle'=>5,
			
			'people'=>10,
			
			'writings'=>20,
			'galleries'=>21,
		],
		'indexcodes'=>['writings', 'people'],
	]);
	$index_collections->Display();
	
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
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-share.php');
	$index_share = new module_indexshare(['that'=>$this, 'boxed'=>TRUE]);
	$index_share->Display();
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'Home',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
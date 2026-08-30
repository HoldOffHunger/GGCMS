<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
	ggreq('modules/html/text.php');
	$text = new module_text;
	
	ggreq('modules/html/form.php');
	$form = new module_form;
	
	ggreq('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
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
	
				// Header_REAL
			
			// -------------------------------------------------------------
	
	$date_epoch_time = strtotime($this->child_record_stats['LastModificationDate']);
	$full_date = date("F d, Y; H:i:s", $date_epoch_time);
	
	$sub_text =
		str_replace('<p>', '<p class="horizontal-left margin-5px font-family-tahoma">', $this->entry['textbody'][0]['Text']) . 
		'<p class="horizontal-left margin-5px font-family-tahoma">' .
		'This blog contains ' . number_format($this->child_record_stats['ChildRecordCount']) . ' texts, with ' . number_format($this->child_record_stats['ChildWordCount']) . ' words or ' . number_format($this->child_record_stats['ChildCharacterCount']) . ' characters.' .
		'</p>';
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
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_one_third_start_args = [
		'class'=>'width-33percent float-left',
	];
	
	$divider_case_start_args = [
		'class'=>'width-100percent height-400px overflow-auto',
	];
	
	$divider_frame_start_args = [
		'class'=>'width-100percent border-2px',
	];
	
	$divider_padding_start_args = [
		'class'=>'margin-5px padding-5px',
	];
	
	$divider_end_args = [
	];
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	if($this->authentication_object->user_session['UserAdmin.id']) {
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
			// Start Top Bar
		
		// -------------------------------------------------------------
	
	print('<div class="horizontal-center width-95percent margin-top-5px">');
	
			// Login Info
		
		// -------------------------------------------------------------
		
	ggreq('modules/html/auth.php');
	$auth = new module_auth(['that'=>$this]);
	$auth->Display();
	
			// End Top Bar
		
		// -------------------------------------------------------------
	
	print('</div>');
	
			// Finish Breadcrumb Trails
		
		// -------------------------------------------------------------
							
	print('<div class="clear-float"></div>');
	
	
			// Newest-Entries Record List
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-newest.php');
	$entry_newest = new module_entrynewest(['that'=>$this]);
	
	$entry_newest->Display();
	
			// View Selected Record List
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-children-grandchildren.php');
	$entry_children_grandchildren = new module_entrychildrengrandchildren(['that'=>$this, 'entrysort'=>$entrysort]);
	
	$entry_children_grandchildren->Display();
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/socialmediasharelinks.php');
	$social_media_share_links_args = [
		'globals'=>$this->handler->globals,
		'textonly'=>$this->mobile_friendly,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
		'socialmedia'=>$this->social_media,
		'sharewithtext'=>'Share With',
		'socialmediasharelinkargs'=>[
			'url'=>$this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/',
			'title'=>$this->header_title_text,
			'desc'=>$image_mouseover,
			'provider'=>$this->domain_object->primary_domain_lowercased,
		],
	];
	$social_media_share_links = new module_socialmediasharelinks($social_media_share_links_args);
	
		
				// Display Social Networking Options
			
			// -------------------------------------------------------------
	
	$social_media_share_links->display();

				// Finish Textbody Header
			
			// -------------------------------------------------------------
	
	print('<div class="clear-float"></div>');
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'Home',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
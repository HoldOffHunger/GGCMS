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
		'sharewithtext'=>$this->share_with_text,
		'socialmediasharelinkargs'=>[
			'url'=>$this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/?id=' . $this->entry['assignment'][0]['id'],
			'title'=>$this->header_title_text,
			'desc'=>$instructions_content_text,
			'provider'=>$this->domain_object->primary_domain_lowercased,
		],
	];
	$social_media_share_links = new module_socialmediasharelinks($social_media_share_links_args);
	
			// Display Header
		
		// -------------------------------------------------------------
		
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'User Panel',
	]);
	
	$entryindexheader->Display();
	
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
	
	

		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center width-100percent margin-top-5px">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>'User Panel',
		]);
		$breadcrumbs->Display();
		
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
		
				// User Panel Stuff
			
			// -------------------------------------------------------------
	
	
	print('<hr>');
	
	print('<div style="margin-left:200px;" class="float-left border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">');
	
	$display_name = $this->handler->authentication->user_account['Username'];
	
	if(!$display_name) {
		$display_name = $this->handler->authentication->user_account['EmailAddress'];
	}
	
	print('Hello, ' . $display_name . '!');
	print('</p></div>');
	
	print('<div style="margin-right:200px;" class="float-right border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">');
	
	print('<a href="/users.php?action=viewuser&user=' . urlencode($display_name) . '">');
	print('Your Public Profile Page');
	print('</a>');
	
	print('</p></div>');
	
	print('<div class="clear-float"></div>');
	
	
	
	
	
	
	
	
	
	print('<hr>');
	
	
	print('<center>');
	print('<div style="margin:0px;width:33%;">');
	print('<div class="float-left border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">');
	print('Now accepting submissions!');
	print('</p></div>');
	print('</div>');
	print('</center>');
	
	print('<div class="clear-float"></div>');
	
	
	
	
	
	print('<center>');
	
	print('<div style="margin:0px;width:50%;">');
	
	foreach($this->primary_children as $primary_child) {
		print('<div class="float-left border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">');
		print('<a href="');
		print('/');
		print($primary_child['Code']);
		print('/modify.php?action=Add');
		print('">');
		print($primary_child['Title']);
		print('</a>');
		print('</p></div>');
	}
	
	print('</div>');
	
	print('</center>');
	
	print('<div class="clear-float"></div>');
	
	print('<hr>');
	
	
	
	
	print('<center>');
	print('<div style="margin:0px;width:33%;">');
	print('<div class="float-left border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">');
	print('Your pending submissions are listed below...');
	print('</p></div>');
	print('</div>');
	print('</center>');
	
	print('<div class="clear-float"></div>');
	
	
	
	print('<center>');
	print('<div style="margin:0px;width:50%;">');
	print('<div class="float-left border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">Your Submissions Published: ');
	print(number_format($this->submission_counts['Published_Count']));
	print('</p></div>');
	
	print('<div class="float-left border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">Your Submissions Pending (not yet published): ');
	print(number_format($this->submission_counts['Unpublished_Count']));
	print('</p></div>');
	print('</div>');
	
	print('</center>');
	
	print('<div class="clear-float"></div>');
	
	print('<center>');
	
	if($this->submission_counts['Unpublished_Count'] !== '0') {
	print('<div style="width:60%;" class="border-2px margin-5px background-color-gray13 margin-left-20px">');
		for($i = 0; $i < $this->submission_counts['Unpublished_Count']; $i++) {
			print('<div style="margin:2px;border:solid 1px black;text-align:left;">');
			
			print('<div style="border:1px solid black;float:right;margin:2px;font-family: arial;">');
			$unpublished_submission = $this->unpublished_submissions[$i];
			
		#	print("<PRE>");
		#	print_r($unpublished_submission['PermaLinkid']);
		#	print("</PRE>");
			
			print(date("M. j, Y, H:i:s", strtotime($unpublished_submission['LastModificationDate'])));
			
		#	print('soypeas');
			print('</div>');
			print('<div style="margin:2px;font-family: arial;">');
			$unpublished_submission = $this->unpublished_submissions[$i];
			
		#	print("<PRE>");
		#	print_r($unpublished_submission);
		#	print("</PRE>");
			
			print('<a href="/?id=');
			print($unpublished_submission['PermaLinkid']);
			print('">');
			
			print($unpublished_submission['Title']);
			
			if($unpublished_submission['Subtitle']) {
				print(': ');
				print($unpublished_submission['Title']);
			}
			
			if($unpublished_submission['OriginalEntryid']) {
				print(' [edit]');
			} else {
				print(' [new-item]');
			}
			
			print('</a>');
			
			print('</div>');
			print('</div>');
		}
	print('</div>');
	} else {
		print('<div style="margin:0px;width:66%;">');
		print('<div class="border-2px margin-5px background-color-gray13 margin-left-20px"><p class="horizontal-left margin-5px font-family-arial">');
		print('You have no pending submissions!<br><br>Why don\'t you submit some stuff?');
		print('</p></div>');
		print('</div>');
	}
	
	print('</center>');
	
			// DEBUG
		
		// -------------------------------------------------------------
	
#	print("BT: INDEX view.php script, display.php template, CHILDOF-people<BR><BR>");
	
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
		'thispage'=>'UserPanel',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
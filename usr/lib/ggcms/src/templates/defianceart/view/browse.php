<?php

	if($this->Param('headless')) {
		print("<script type='text/javascript'>");
		print("
			function inIframe () {
				try {
					return window.self !== window.top;
				} catch (e) {
					return true;
				}
			}
			
			if(!inIframe()) {
				document.location = 'view.php#children';
			}
		");
		print("</script>");
	}
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
		
				// Timeframe
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-date.php');
	$entrydate = new module_entrydate(['that'=>$this]);
	$time_data = $entrydate->getSimpleData();
	$time_frame = $time_data['text'];
	
				// Header_REAL
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_frame]);
	
	ggreq('modules/html/header.php');
	$header = new module_header;
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
	ggreq('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this]);
	
	ggreq('modules/html/entry-list-navigation.php');
	$entrylistnavigation = new module_entrylistnavigation(['that'=>$this]);

	$breadcrumbs_title = 'Browsing';	
	
	if($this->entry['ChildAdjective']) {
		$breadcrumbs_title .= ' ' . $this->entry['ChildAdjective'];
	}
	
	if($this->entry['ChildNounPlural']) {
		$breadcrumbs_title .= ' ' . $this->entry['ChildNounPlural'];
	}
	
	$this->header_title_text .= ' &mdash; ' . $breadcrumbs_title;
	
			// Display Header
		
		// -------------------------------------------------------------
	
	if(!$this->Param('headless')) {
		$entryheader->Display();
	}
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	if(!$this->Param('headless') && $this->authentication_object->user_session['UserAdmin.id']) {
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
	if(!$this->Param('headless')) {
			// Start Top Bar
		
		// -------------------------------------------------------------
	
		print('<div class="horizontal-center width-95percent margin-top-5px">');
	
			// Breadcrumbs Info
		
		// -------------------------------------------------------------
		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>$breadcrumbs_title]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();
	
				// End Top Bar
			
			// -------------------------------------------------------------
		
		print('</div>');
	
		print('<div class="clear-float"></div>');
	}
	
			// View Browsing Numbers
		
		// -------------------------------------------------------------
	
	$header_secondary_args = [
		'title'=>'Browsing : ' . $this->child_record_start_index . ' to ' . $this->child_record_end_index . ' of ' . $this->children_count ,
		'imagemouseover'=>$this->total_pages . ' Total Pages Available',
		'divmouseover'=>$this->total_children_viewed . ' Items Viewed, ' . $this->total_children_left . ' Remaining to Be Viewed',
		'level'=>3,
		'divclass'=>'width-33percent border-2px background-color-gray13 margin-5px float-left',
		'textclass'=>'font-family-arial padding-0px margin-5px horizontal-center vertical-center',
		'imagedivclass'=>'border-2px margin-5px background-color-gray10',
		'imageclass'=>'border-1px',
		'domainobject'=>$this->domain_object,
		'leftimageenable'=>0,
		'rightimageenable'=>0,
	];
	$header->display($header_secondary_args);
	
			// View Browsing Per-Page Setting
		
		// -------------------------------------------------------------
	
	print('<form class="margin-0px" method="POST">');
	
	$browsing_options = 'Results Per Page : <select id="perpage" name="perpage">';
	
	for($i = 10; $i <= 200; $i += 10) {
		$browsing_options .= '<option value="' . $i . '"';
		
		if($i == $this->perpage && !$this->custom_per_page_selected) {
			$browsing_options .= ' SELECTED="SELECTED"';
		}
		
		$browsing_options .= '>' . $i . '</option>';
	}
	
	$browsing_options .= '<option value="custom"';
	if($this->custom_per_page_selected) {
		$browsing_options .= ' SELECTED="SELECTED"';
	}
	$browsing_options .= '>Custom</option>';
	$browsing_options .= '</select> ';
	$browsing_options .= '<input id="CustomPerPage" name="CustomPerPage" type="text" size="5" value="' . $this->perpage . '"> ';
	$browsing_options .= '<input type="submit" value="Update"> ';
	$browsing_options .= '<input type="hidden" name="page" value="1">';
	
	$header_secondary_args = [
		'title'=>$browsing_options,
		'divmouseover'=>'Adjust results per page here.',
		'level'=>3,
		'divclass'=>'width-33percent border-2px background-color-gray13 margin-5px float-right',
		'textclass'=>'font-family-arial padding-0px margin-5px horizontal-center vertical-center',
		'imagedivclass'=>'border-2px margin-5px background-color-gray10',
		'imageclass'=>'border-1px',
		'domainobject'=>$this->domain_object,
		'leftimageenable'=>0,
		'rightimageenable'=>0,
	];
	$header->display($header_secondary_args);
	
	print('</form>');
	
	print('<div class="clear-float"></div>');
	
			// View Selected Record List Pages
		
		// -------------------------------------------------------------
		
	$entrylistnavigation->Display([]);
	
			// View Selected Record List Pages
		
		// -------------------------------------------------------------
	
	$child_display = $entrysort->Sort(['entries'=>$this->children]);
	
	#print('<div class="horizontal-center width-90percent">');
	
	#$entrylist->Display(['children'=>$child_display]);
	
		foreach($this->children as $child) {
		unset($display_image);
		unset($url);
		
		if($child['image']) {
			$child_images = $child['image'];
			$child_image_count = count($child_images);
			if($child_image_count) {
				shuffle($child_images);
				$child_image = $child_images[0];
				$display_image = $child_image;
			}
		}
		
		if(!$display_image && $child['link']) {
			$url = $child['link'][1]['URL'];
			$display_image = [
				'IconPixelWidth'=>200,
				'IconPixelHeight'=>200,
			];
		}
		
		if(!$display_image) {
			$display_image = [
				'IconFileName'=>$this->primary_host_record['PrimaryImageLeft'],
				'IconPixelWidth'=>200,
				'IconPixelHeight'=>200,
			];
		}
		
		if(!$url) {
			$url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) .
			'/image/' .
			implode('/', str_split($display_image['FileDirectory'])) .
			'/' .
			$display_image['IconFileName'];
		}
		
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="background-color-gray0" style="height:200px;width:200px;">');
		print('<div class="vertical-specialcenter">');
		print('<a href="' . $child['Code'] . '/view.php">');
		print('<center>');
		print('<img');
		#max-width="200"');
#		print(ceil($display_image['IconPixelWidth'] / 2));
	#	print('" max-height="200"');
#		print(ceil($display_image['IconPixelHeight'] / 2));
		print(' style="max-width:200px;max-height:200px;"');
		print(' src="');
		print($url);
		print('">');
		print('</center>');
		print('</a>');
		print('</div>');
		print('</div>');
		print('</div>');
		print('</div>');
	}
	print('<div class="clear-float"></div>');
	
	print('</div>');
	
			// View Selected Record List Pages
		
		// -------------------------------------------------------------
	
	print('<br>');
	
	$entrylistnavigation->Display([]);
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	if(!$this->Param('headless')) {
		$bottom_navigation_args = [
			'thispage'=>'',
		];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
	}
	
			// Display Debug
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/debug.php');
	$debug = new module_debug(['that'=>$this]);
	#$debug->DisplayBasicRecords([]);
	
?>
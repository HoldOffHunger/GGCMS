<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	require(GGCMS_DIR . 'modules/spacing.php');
	
			// Display Header
		
		// -------------------------------------------------------------
		
	require(GGCMS_DIR . 'modules/html/entry-header.php');
	require(GGCMS_DIR . 'modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'Logging to ' . $this->domain_object->primary_domain,
	]);
	
	$entryindexheader->Display();
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		require(GGCMS_DIR . 'modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>'Login',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		require(GGCMS_DIR . 'modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();
		
		print('<div class="clear-float"></div>');
	
			// Display Message
		
		// -------------------------------------------------------------
		
				// Determine Message
			
			// -------------------------------------------------------------
	
	if($this->login_results['status'] == 'Success') {
		$message = 'Logged in successfully!';
	} else {
		$message = 'Login failed!  Redirecting to login screen.';
	}
		
				// Show Message
			
			// -------------------------------------------------------------
	
	print('<center>');
	print('<div class="width-50percent border-2px background-color-gray13 margin-top-5px">');
	print('<h3 style="margin:5px;font-family:arial;">');
	print($message);
	print('</h3>');
	print('</div>');
	print('</center>');
		
				// Navigation
			
			// -------------------------------------------------------------
	
	require(GGCMS_DIR . 'modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'Authenticate',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
		
?>
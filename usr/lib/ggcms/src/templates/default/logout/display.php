<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreqonce('modules/spacing.php');
	
	ggreqonce('modules/html/divider.php');
	$divider = new module_divider;
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreqonce('modules/html/entry-header.php');
	ggreqonce('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'Logging Out : ' . $this->logout_status,
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		ggreqonce('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>'Logout',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreqonce('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this, 'noredirect'=>TRUE]);
		$auth->Display();
		
		print('<div class="clear-float"></div>');
	
			// Display Message
		
		// -------------------------------------------------------------
		
				// Determine Message
			
			// -------------------------------------------------------------
	
	if($this->logout_status == 'Success') {
		$message = 'Logged out successfully!';
	} else {
		$message = 'Log out failed!  Perhaps you were not logged in?';
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
	
			// Display Form Elements : Google Login
		
		// -------------------------------------------------------------
	
	print('<BR>');
	
	print('<div style="display:none;">');
	print('<center>');
	print('<div class="g-signin2" data-onsuccess="onSignIn"></div>');
	print('</center>');
	print('</div>');
	
	print('<BR>');
	
			// Hidden Data
		
		// -------------------------------------------------------------
	
	print('<input type="hidden" id="google_token_id" name="google_token_id" class="google_token_id">');
	print('<input type="hidden" id="logout" name="logout" value="1">');
	$redirect = $this->param('redirect');
	
	if($this->validateRedirect(['url'=>$redirect])) {
		print('<input type="hidden" id="redirect" name="redirect" value="' . $redirect . '">');
	}
	
	ggreqonce('modules/html/navigation.php');
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
		'thispage'=>'Logout',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------
	
	ggreq('modules/spacing.php');
	
	ggreq('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/html/header.php');
	$header = new module_header;
	
	ggreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	ggreq('modules/html/master-c-panel.php');
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$primary_color = '6495ED';
	
	$header_primary_args = [
		'title'=>'Master Control Program',
		'image'=>'master-c-icon.jpg',
		'divmouseover'=>'The Grand Master C.',
		'imagemouseover'=>'Master C is in the house!',
		'level'=>1,
		'divclass'=>'horizontal-center width-100percent border-2px margin-top-5px background-color-' . $primary_color,
		'textclass'=>'padding-0px margin-0px horizontal-center vertical-center padding-top-22px',
		'imagedivclass'=>'border-2px margin-5px background-color-gray10',
		'imageclass'=>'border-1px height-75px',
		'domainobject'=>$this->domain_object,
		'leftimageenable'=>1,
		'rightimageenable'=>1,
	];
	
	$header->display($header_primary_args);
	
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_special_start_args = [
		'class'=>'margin-top-5px margin-right-5px margin-left-5px padding-5px margin-bottom-2px',
	];
	
	$divider_end_args = [
	];
	
			// Display Quick-Options Panel
		
		// -------------------------------------------------------------
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-100percent horizontal-center border-2px background-color-gray13 margin-top-5px font-family-tahoma',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px width-25percent vertical-top link-clickable cursor-pointer',
				'border-1px width-25percent vertical-top link-clickable cursor-pointer',
				'border-1px width-25percent vertical-top link-clickable cursor-pointer',
				'border-1px width-25percent vertical-top link-clickable cursor-pointer',
			],
		],
		'list'=>[
			[
				'&bull; <a href="/">Home</a>',
				'&bull; <a href="logout.php">Logout</a>',
				'&bull; <a href="/login.php" rel="nofollow">Login</a>',
				'&bull; <a href="user-panel.php">User Panel</a>',
			],
		],
	];
	$generic_list->Display($version_list_display_args);
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/view-top-bar.php');
	$viewtopbar = new module_viewtopbar(['that'=>$this]);
	$viewtopbar->DisplayAdminControls(['file'=>__FILE__]);
	
			// Display Column #2 : Administration
		
		// -------------------------------------------------------------
	
	$administration_panel = new module_mastercpanel([
		'that'=>$this,
		'menu'=>'administration',
		'title'=>'Admin',
		'image'=>'master-c/admin-icon.jpg',
		'divmouseover'=>'Toolage',
		'imagemouseover'=>'Toolage Imageryage!',
	]);
	$administration_panel->Display();
	
			// Display Column #1 : Entry Management
		
		// -------------------------------------------------------------
	
	$entries_panel = new module_mastercpanel([
		'that'=>$this,
		'menu'=>'entries',
		'title'=>'Entries',
		'image'=>'master-c/entries-icon.jpg',
		'divmouseover'=>'Toolage',
		'imagemouseover'=>'Toolage Imageryage!',
	]);
	$entries_panel->Display();
	
			// Display Column #2 : User Management
		
		// -------------------------------------------------------------
	
	$users_panel = new module_mastercpanel([
		'that'=>$this,
		'menu'=>'users',
		'title'=>'Users',
		'image'=>'master-c/users-icon.jpg',
		'divmouseover'=>'Toolage',
		'imagemouseover'=>'Toolage Imageryage!',
	]);
	$users_panel->Display();
	
			// Display Column #2 : 3rd Party Management
		
		// -------------------------------------------------------------
	
	$thirdparties_panel = new module_mastercpanel([
		'that'=>$this,
		'menu'=>'thirdparties',
		'title'=>'3rd Parties',
		'image'=>'tools-icon.jpg',
		'divmouseover'=>'Toolage',
		'imagemouseover'=>'Toolage Imageryage!',
	]);
	$thirdparties_panel->Display();
	
			// Display Column #2 : System Testing
		
		// -------------------------------------------------------------
	
	$systems_panel = new module_mastercpanel([
		'that'=>$this,
		'menu'=>'systems',
		'title'=>'Systems',
		'image'=>'diagnostics-icon.jpg',
		'divmouseover'=>'diag',
		'imagemouseover'=>'diag-yo mousevoer!',
	]);
	$systems_panel->Display();
	
			// Display Column #3 : DB
		
		// -------------------------------------------------------------
	
	$db_panel = new module_mastercpanel([
		'that'=>$this,
		'menu'=>'db',
		'title'=>'DB',
		'image'=>'db-icon.jpg',
		'divmouseover'=>'dub buh',
		'imagemouseover'=>'dub buhhhhh mousevoer!',
		'textclass'=>'padding-0px margin-0px vertical-center padding-top-14px',
	]);
	$db_panel->Display();
		
				// Extra Blank Space
			
			// -------------------------------------------------------------
	
	print('<div class="clear-float"></div>');
	$divider->displaystart($divider_padding_special_start_args);
	
	$divider->displayend($divider_end_args);
	
?>
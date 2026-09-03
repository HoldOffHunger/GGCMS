<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	classreq('modules/spacing.php');
	
	classreq('modules/html/text.php');
	$text = new module_text;
	
	classreq('modules/html/form.php');
	$form = new module_form;
	
	classreq('modules/html/divider.php');
	$divider = new module_divider;
	
	classreq('modules/html/table.php');
	$table = new module_table;
	
	classreq('modules/html/header.php');
	$header = new module_header;
	
			// Display Header
		
		// -------------------------------------------------------------
	
	classreq('modules/html/entry-header.php');
	classreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'Login to ' . $this->domain_object->primary_domain,
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		classreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>'Login',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		classreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();
		
		print('<div class="clear-float"></div>');
		
			// Display Login Failure Message
		
		// -------------------------------------------------------------
	
	if($this->query_object->Parameter(['parameter'=>'failure'])) {
		$divider_padding_start_args = [
			'class'=>'horizontal-center width-40percent margin-top-5px border-2px background-color-red15',
		];
		
		$divider->displaystart($divider_padding_start_args);
		
		print('<b>Login attempt failed.  Please try again.</b>');
		
		$divider_end_args = [
		];
		$divider->displayend($divider_end_args);
	}
	
			// Display Form Dividers
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
	];
	
	$divider->displaystart($divider_padding_start_args);
		
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	print('<FORM method="POST" class="margin-0px">');
		
			// Display Form Elements : Fields
		
		// -------------------------------------------------------------
	
	$divider_fields_args = [
		'class'=>'horizontal-center width-80percent margin-top-5px margin-bottom-5px',
	];
	
	$divider->displaystart($divider_fields_args);
	
	$table_start_args = [
		'id'=>'user-login-credentials-table',
		'tableclass'=>'width-100percent margin-5px font-family-tahoma',
		'tableborder'=>'3',
	];
	
	$table->DisplayComponent_StartTable($table_start_args);
	
	print('Username');
	
	$separate_cells_args = [
		'cellwidth'=>'99%',
	];
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$username_args = [
		'type'=>'text',
		'name'=>'username',
		'size'=>40,
		'autofocus'=>true,
	];
	
	$form->DisplayFormField($username_args);
	
	$separate_cells_and_rows_args = [
	];
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
	print('Password');
	
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$password_args = [
		'type'=>'password',
		'name'=>'password',
		'size'=>40,
	];
	
	$form->DisplayFormField($password_args);
	
	$separate_cells_and_rows_args = [
		'cellcolspan'=>2,
	];
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
	$divider_fields_args = [
		'class'=>'horizontal-center',
	];
	
	$divider->displaystart($divider_fields_args);
	
	print('<input type="submit" value="Login" id="submit" />');
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
	$table_end_args = [
	];
	$table->DisplayComponent_EndTable($table_end_args);
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
			// Display Form Elements : Google Login
		
		// -------------------------------------------------------------
	
	print('<BR>');
	
	print('<center>');
	print('<div class="g-signin2" data-onsuccess="onSignIn"></div>');
	print('</center>');
	
	print('<BR>');
	
			// Display Form Elements : Hidden
		
		// -------------------------------------------------------------
	
	$hidden_action_args = [
		'name'=>'action',
		'value'=>'Authenticate',
		'type'=>'hidden',
	];
	
	$form->DisplayFormField($hidden_action_args);
	
	print('<input type="hidden" name="google_token_id" id="google_token_id" class="google_token_id">');
	print('<input type="hidden" id="redirect" name="redirect" value="' . $this->param('redirect') . '">');
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	print('</form>');
	print('</div>');
	
	classreq('modules/html/navigation.php');
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
		'thispage'=>'Login',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);

?>
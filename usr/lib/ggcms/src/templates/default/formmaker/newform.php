<STYLE>
	.name-field {
		padding:2px;
	}
	
	.input-field {
		float:left;
		margin:2px;
	}
	
	.field-name-div {
		width:calc(20% - 2px);
	}
	
	.field-value-div {
		width:calc(80% - 2px);
	}
	
	.field-div {
		border: 1px solid black;
		float:left;
		padding:0px;
		margin:0px;
		font-family:tahoma;
	}
</STYLE>
<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
	ggreq('modules/html/languages.php');
	$languages_args = [
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$languages = new module_languages($languages_args);
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>'Configure Submission Details for ' . $this->entry['Title'],
		'sub_text'=>'Form-Maker - ' . $this->entry['Subtitle'],
		'sub2_text'=>$sub2_text,
		'sub_title'=>$sub_title,
	]);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$entryindexheader->Display();
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	if($this->authentication_object->user_session['UserAdmin.id']) {
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
			// Display About Info
		
		// -------------------------------------------------------------
	
	print('<form method="POST">');
	
	print('<center><h2 class="margin-5px font-family-tahoma">' . $this->entry['Title'] . ' Form-Maker: Create New Form</h2>');
	
	print('<div class="font-family-tahoma" style="border: 1px solid black; width:80%;">');
	
	print('<center><h3>');
	print('Create New Form - Configure Submission Details for ' . $this->entry['Title']);
	print('</h3></center>');
	
			// Form Type Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Form Type');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="FormType" class="input-field" size="100" autofocus>');
	print('</div>');
	
			// Public Access Start Date Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Public Access Start Date');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="PublicAccessStartDate" class="input-field datepicker" size="30">');
	print('</div>');
	
			// Public Access Start Time Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Public Access Start Time');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="PublicAccessStartTime" class="input-field timepicker" size="20">');
	print('</div>');
	
			// Public Access End Date Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Public Access End Date');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="PublicAccessEndDate" class="input-field datepicker" size="30">');
	print('</div>');
	
			// Public Access End Time Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Public Access End Time');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="PublicAccessEndTime" class="input-field timepicker" size="20">');
	print('</div>');
	
			// Row Quantity Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Rows');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="RowQuantity" class="input-field" size="10">');
	print('</div>');
	
			// Column Quantity Field
		
		// -------------------------------------------------------------
	
	print('<div class="field-div field-name-div">');
	print('<div class="name-field">');
	print('Columns');
	print('</div>');
	print('</div>');
	
	print('<div class="field-div field-value-div">');
	print('<input type="text" name="ColumnQuantity" class="input-field" size="10">');
	print('</div>');
	
			// Display About Info
		
		// -------------------------------------------------------------
	
	print('<div style="clear:left;"></div>');
	
	print('<input type="submit" value="Save New Form">');
	
	print('<input type="hidden" name="action" value="saveform">');
	
	print('</div>');
	print('</center>');
	
	print('</form>');
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
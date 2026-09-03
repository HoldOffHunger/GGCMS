<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreqonce('modules/spacing.php');
	
	ggreqonce('modules/html/text.php');
	$text = new module_text;
	
	ggreqonce('modules/html/form.php');
	$form = new module_form;
	
	ggreqonce('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreqonce('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	ggreqonce('modules/html/header.php');
	$header = new module_header;
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'margin-5px padding-5px',
	];
	
	$divider_end_args = [
	];
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$good_header_text = $this->domain_object->primary_domain . ' System Status : Detect Missing List Title Sort Keys';
	
	ggreqonce('modules/html/entry-header.php');
	ggreqonce('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->domain_object->primary_domain . ' -&gt; ' . $good_header_text,
		'sub_text'=>$good_header_text,
	]);
	
	$entryindexheader->Display();
			
			// Display 'Back to Master C.' Link
		
		// -------------------------------------------------------------
	
	$return_to_master_c_args = [
		'title'=>'Return to the Master Control Program',
		'image'=>'master-c-icon.jpg',
		'divmouseover'=>'The Grand Master C.',
		'imagemouseover'=>'Master C is in the house!',
		'level'=>3,
		'divclass'=>'width-400px border-2px margin-left-20px margin-top-5px background-color-gray13',
		'textclass'=>'margin-0px horizontal-center vertical-center',
		'imagedivclass'=>'border-2px margin-5px background-color-gray10',
		'imageclass'=>'border-1px height-75px',
		'domainobject'=>$this->domain_object,
		'leftimageenable'=>1,
		'rightimageenable'=>0,
		'link'=>'master-c.php',
	];
	
	$header->display($return_to_master_c_args);
			
			// Display Instructions
		
		// -------------------------------------------------------------
	
	$primary_url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-14px',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px vertical-top',
				'border-1px width-100percent vertical-top',
			],
		],
		'list'=>[[
			'Note: Detected a total of ' . number_format($this->broken_records_count) . ' missing textbodies.',
		]],
	];
	$generic_list->Display($version_list_display_args);
			
			// Display Instructions
		
		// -------------------------------------------------------------
	
	$primary_url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-14px',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px width-100percent vertical-top',
			],
		],
		'list'=>$this->broken_records,
	];
	$generic_list->Display($version_list_display_args);
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
	];
	
	$divider->displaystart($divider_padding_start_args);
	
	$start_form_args = [
		'action'=>0,
		'method'=>'post',
		'files'=>0,
		'formclass'=>'margin-0px',
	];
	
	$form->StartForm($start_form_args);
	
			// Display Form Save Button : Save
		
		// -------------------------------------------------------------
	
	print('<div class="horizontal-left">');
	
	print('Entry Level : <select name="level">');
	for($i = 1; $i < 6; $i++) {
		print('<option value="' . $i . '">' . $i . '</option>');
	}
	print('</select>');
	
	print('<center>');
	print('<input type="submit" name="detect-broken-records" value="Detect Broken Records">');
	print('</center>');
	
	print('<input type="hidden" name="fix" value="1">');
	
	print('</div>');
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = [
	];
	$form->EndForm($end_form_args);
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
?>
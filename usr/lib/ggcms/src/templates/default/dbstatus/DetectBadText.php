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
	
	$good_header_text = 'System Status : Detect Bad Text';
	
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
	
	$note = 'Note: This will detect bad text.  Total filters: ' . number_format($this->misspellingscount) . '. Total entries (estimate): ' . number_format($this->entry_end - $this->entry_start) . '.';
	
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
			$note,
		]],
	];
	$generic_list->Display($version_list_display_args);
	
	if($this->search_results_count) {
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13 margin-top-14px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				],
			],
			'list'=>[[
				'<nobr>Search Results</nobr>', number_format($this->search_results_count),
			]],
		];
		$generic_list->Display($version_list_display_args);
	}
	
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

print('<center>');
	
	
	print('<select name="algorithm" id="algorithm">');
	
	print('<option value="standard"');
	if($this->algorithm == 'standard') {
		print('selected');
	}
	print('>Standard Algorithm</option>');
	
	print('<option value="intensive"');
	if($this->algorithm == 'intensive') {
		print('selected');
	}
	print('>Intensive Algorithm</option>');
	
	print('</select>');
	
	print('<BR>');
	
	print('<select name="type" id="type">');
	
	print('<option value="Entry_Title"');
	if($this->type == 'Entry_Title') {
		print('selected');
	}
	print('>Entry: Title</option>');
	
	print('<option value="Entry_Subtitle"');
	if($this->type == 'Entry_Subtitle') {
		print('selected');
	}
	print('>Entry: Subtitle</option>');
	
	print('<option value="Entry_ListTitle"');
	if($this->type == 'Entry_ListTitle') {
		print('selected');
	}
	print('>Entry: ListTitle</option>');
	
	print('<option value="Entry_ListTitleSortKey"');
	if($this->type == 'Entry_ListTitleSortKey') {
		print('selected');
	}
	print('>Entry: ListTitleSortKey</option>');
	
	print('<option value="Entry_Code"');
	if($this->type == 'Entry_Code') {
		print('selected');
	}
	print('>Entry: Code</option>');
	
	print('<option value="TextBody_Text"');
	if($this->type == 'TextBody_Text') {
		print('selected');
	}
	print('>TextBody: Text</option>');
	
	print('</select>');
	
	print('<br>');
	
	print('First Correction To Test : ');
	print('<input type="text" name="correction-id-start" value="' . $this->correction_start_id . '"> (first: 1)');
	print('<br>');
	
	print('Last Correction To Test : ');
	print('<input type="text" name="correction-id-last" value="' . $this->correction_end_id . '"> (normal-last: ' . $this->misspellingscount . '; intensive-last: ' . $this->intensivemisspellingscount . ')');
	print('<br>');
	
	print('First Entry To Test : ');
	print('<input type="text" name="entry-id-start" value="' . $this->maxmin['min'] . '">');
	print('<br>');
	
	print('Last Entry To Test : ');
	print('<input type="text" name="entry-id-last" value="' . $this->maxmin['max'] . '">');
	print('<br>');
	
	$type_args = [
		'type'=>'submit',
		'name'=>'fix-broken-records',
		'class'=>'margin-5px',
		'value'=>'Detect Bad-Text Records',
	];
	
	$form->DisplayFormField($type_args);
	
print('</center>');
	
			
			// Display Instructions
		
		// -------------------------------------------------------------
	
	$primary_url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
	
	if(count($this->search_results) > 0) {
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
			'list'=>array_merge(
				$this->search_header,
				$this->search_results
			),
		];
		$generic_list->Display($version_list_display_args);
	}
	
		// Hidden Action
		// -----------------------------------------------------
	
	$type_args = [
		'type'=>'hidden',
		'name'=>'fix',
		'value'=>'1',
	];
	
	$form->DisplayFormField($type_args);
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = [
	];
	$form->EndForm($end_form_args);
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
?>
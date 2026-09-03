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
	
	classreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	classreq('modules/html/header.php');
	$header = new module_header;
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'margin-5px padding-5px',
	];
	
	$divider_end_args = [];
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$good_header_text = 'System Status : Detect and Fix British Spellings';
	
	classreq('modules/html/entry-header.php');
	classreq('modules/html/entry-index-header.php');
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
			'Note: This will detect records with British spellings and correct them to their American equivalent.',
		]],
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
	
			// Display Form Save Button : Options
		
		// -------------------------------------------------------------
	
	$record_types = [
		'Entry',
		'TextBody',
		'Tag',
		'Link',
		'Description',
		'Image',
		'Quote',
		'EventDate',
		'Definition',
		'Association',
	];
	$record_types_count = count($record_types);
	
	print('<table border="1" width="100%">');
	print('<tr><td width="99%">Record Type : </td><td><select name="record-type">');
	for($i = 0; $i < $record_types_count; $i++) {
		$record_type = $record_types[$i];
		print('<option value="' . $record_type . '"');
		
		if($record_type == $this->recordtype) {
			print(' SELECTED="SELECTED"');
		}
		
		print('>' . $record_type . '</option>');
	}
	
	print('<tr><td>Specific Record (0 for all) :</td><td><input name="specific-record" value="');
	if($this->specificrecord) {
		print($this->specificrecord);
	} else {
		print('0');
	}
	print('"></td></tr>');
	print('</table>');
	
			// Display Form Save Button : Save
		
		// -------------------------------------------------------------
	

	print('<center>');
	
	
	$type_args = [
		'type'=>'submit',
		'name'=>'fix-broken-records',
		'class'=>'margin-5px',
		'value'=>'Fix Broken Records',
	];
	
	$form->DisplayFormField($type_args);
	

	print('</center>');
	
	
		// Hidden Action
		// -----------------------------------------------------
	
	$type_args = [
		'type'=>'hidden',
		'name'=>'fix',
		'value'=>'1',
	];
	
	$form->DisplayFormField($type_args);
	
		// End Form
		// -----------------------------------------------------
	
	$end_form_args = [
	];
	$form->EndForm($end_form_args);
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
		// Show Conversion Results
		// -----------------------------------------------------
	
	if($this->fixresults) {
		$divider_padding_start_args = [
			'class'=>'horizontal-center width-90percent margin-top-5px border-2px',
		];
		
		$divider->displaystart($divider_padding_start_args);
		
		print('<table border="1" width="100%">');
		print('<tr><td width="100%">');
		print('<ul style="margin:0px;">');
		
		foreach($this->fixresults as $fixed_table => $fixed_table_ids) {
			foreach($fixed_table_ids as $fixed_table_id => $fixed_record) {
				print('<li>' . $fixed_table . ' : id ' . $fixed_table_id . '</li>');
			}
		}
		
		print('</ul>');
		print('</td></tr>');
		print('</table>');
		
		$divider_end_args = [
		];
		$divider->displayend($divider_end_args);
	}
	
?>
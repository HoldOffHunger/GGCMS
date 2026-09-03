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
	
	classreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	classreq('modules/html/header.php');
	$header = new module_header;
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = array(
		'class'=>'margin-5px padding-5px',
	);
	
	$divider_end_args = array(
	);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$header_primary_args = array(
		'title'=>$this->domain_object->primary_domain . ' System Status : Assert Options',
		'image'=>'system-status-icon.jpg',
		'divmouseover'=>'The Grand Master C.',
		'imagemouseover'=>'Master C is in the house!',
		'level'=>1,
		'divclass'=>'horizontal-center width-100percent border-2px margin-top-5px background-color-gray13',
		'textclass'=>'margin-0px horizontal-center vertical-center padding-top-22px margin-bottom-10px',
		'imagedivclass'=>'border-2px margin-5px background-color-gray10',
		'imageclass'=>'border-1px height-75px',
		'domainobject'=>$this->domain_object,
		'leftimageenable'=>1,
		'rightimageenable'=>1,
	);
	
	$header->display($header_primary_args);
			
			// Display 'Back to Master C.' Link
		
		// -------------------------------------------------------------
	
	$return_to_master_c_args = array(
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
	);
	
	$header->display($return_to_master_c_args);
	
			// Display Assert Options
		
		// -------------------------------------------------------------
	
	if(isset($this->SubmittedValue))
	{
				// Display Submitted Value
			
			// -------------------------------------------------------------
				// Display Submitted Value
			
			// -------------------------------------------------------------
		
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = array(
			'options'=>array(
				'tableheaders'=>0,
				'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>array(
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				),
			),
			'list'=>array(
				array(
					'<nobr>Assert Option:</nobr>',
					$this->SubmittedValuePrintable,
				),
			),
		);
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
		
				// Display Actual Function Results
			
			// -------------------------------------------------------------
		
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = array(
			'options'=>array(
				'tableheaders'=>0,
				'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>array(
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				),
			),
			'list'=>$this->StatusDataArray,
		);
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
	}
	
			// Display Form Dividers
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = array(
		'class'=>'horizontal-center width-70percent margin-top-5px border-2px',
	);
	
	$divider->displaystart($divider_padding_start_args);
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$start_form_args = array(
		'action'=>0,
		'method'=>'post',
		'files'=>0,
		'formclass'=>'margin-0px',
	);
	
	$form->StartForm($start_form_args);
	
			// Display Form Elements : Fields
		
		// -------------------------------------------------------------
	
	$divider_fields_args = array(
		'class'=>'horizontal-center width-70percent margin-top-5px margin-bottom-5px',
	);
	
	$divider->displaystart($divider_fields_args);
	
	$table_start_args = array(
		'id'=>'user-login-credentials-table',
		'tableclass'=>'width-100percent margin-5px',
		'tableborder'=>'3',
		'cellwidth'=>'100%',
	);
	
	$table->DisplayComponent_StartTable($table_start_args);
	
		// ASSERT_ACTIVE ROW
	
print('ASSERT_ACTIVE (assert.active)');
	
	
	$separate_cells_args = array(
	);
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$assert_args = array(
		'type'=>'radio',
		'name'=>'assert-option',
		'value'=>'ASSERT_ACTIVE',
		'size'=>30,
	);
	
	$form->DisplayFormField($assert_args);
	
	$separate_cells_and_rows_args = array(
	);
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
		// ASSERT_WARNING ROW
	
print('ASSERT_WARNING (assert.warning)');
	
	
	$separate_cells_args = array(
	);
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$assert_args = array(
		'type'=>'radio',
		'name'=>'assert-option',
		'value'=>'ASSERT_WARNING',
		'size'=>30,
	);
	
	$form->DisplayFormField($assert_args);
	
	$separate_cells_and_rows_args = array(
	);
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
		// ASSERT_BAIL ROW
	
print('ASSERT_BAIL (assert.bail)');
	
	
	$separate_cells_args = array(
	);
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$assert_args = array(
		'type'=>'radio',
		'name'=>'assert-option',
		'value'=>'ASSERT_BAIL',
		'size'=>30,
	);
	
	$form->DisplayFormField($assert_args);
	
	$separate_cells_and_rows_args = array(
	);
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
		// ASSERT_QUIET_EVAL ROW
	
print('ASSERT_QUIET_EVAL (assert.quiet_eval)');
	
	
	$separate_cells_args = array(
	);
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$assert_args = array(
		'type'=>'radio',
		'name'=>'assert-option',
		'value'=>'ASSERT_QUIET_EVAL',
		'size'=>30,
	);
	
	$form->DisplayFormField($assert_args);
	
	$separate_cells_and_rows_args = array(
	);
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
		// ASSERT_CALLBACK ROW
	
print('ASSERT_CALLBACK (assert.callback)');
	
	
	$separate_cells_args = array(
	);
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$assert_args = array(
		'type'=>'radio',
		'name'=>'assert-option',
		'value'=>'ASSERT_CALLBACK',
		'size'=>30,
	);
	
	$form->DisplayFormField($assert_args);
	
		// ASSERT BUTTON
	
	$separate_cells_and_rows_args = array(
		'cellcolspan'=>2,
	);
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_and_rows_args);
	
	$divider_fields_args = array(
		'class'=>'horizontal-center',
	);
	
	$divider->displaystart($divider_fields_args);
	
	$login_args = array(
		'value'=>'Assert Option',
		'type'=>'submit',
	);
	
	$form->DisplayFormField($login_args);
	
	$divider_end_args = array(
	);
	$divider->displayend($divider_end_args);
	
	$table_end_args = array(
	);
	$table->DisplayComponent_EndTable($table_end_args);
	
	$divider_end_args = array(
	);
	$divider->displayend($divider_end_args);
	
	$hidden_action_args = array(
		'name'=>'action',
		'value'=>'AssertOptions',
		'type'=>'hidden',
	);
	
	$form->DisplayFormField($hidden_action_args);
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = array(
	);
	$form->EndForm($end_form_args);
	
	$divider_end_args = array(
	);
	$divider->displayend($divider_end_args);
	
?>
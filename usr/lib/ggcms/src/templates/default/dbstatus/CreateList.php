<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	require(GGCMS_DIR . 'modules/spacing.php');
	
	require(GGCMS_DIR . 'modules/html/text.php');
	$text = new module_text;
	
	require(GGCMS_DIR . 'modules/html/form.php');
	$form = new module_form;
	
	require(GGCMS_DIR . 'modules/html/divider.php');
	$divider = new module_divider;
	
	require(GGCMS_DIR . 'modules/html/table.php');
	$table = new module_table;
	
	require(GGCMS_DIR . 'modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	require(GGCMS_DIR . 'modules/html/header.php');
	$header = new module_header;
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'',
	];
	
	$divider_end_args = [];
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$good_header_text = 'System Status : Create List';
	
	require(GGCMS_DIR . 'modules/html/entry-header.php');
	require(GGCMS_DIR . 'modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->domain_object->primary_domain . ' -&gt; ' . $good_header_text,
		'sub_text'=>$good_header_text,
	]);
	
	$entryindexheader->Display();
	
			// Display List Items
		
		// -------------------------------------------------------------
	
	if($this->title) {
		$list_message = 'Your list and its items were created.  The results are detailed below.';
	} else {
		$list_message = 'Create a list with a title and a list of options, each option possessing both a Key (optional) and a Value (required).';
	}
	
	$divider->displaystart($divider_padding_start_args);
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px vertical-top',
				'border-1px width-100percent vertical-top',
			],
		],
		'list'=>[
			[
				'<nobr>Lists :</nobr>', $list_message,
			],
		],
	];
	$generic_list->Display($version_list_display_args);
	
	$divider->displayend($divider_end_args);
	
			// Display Parent Record Info
		
		// -------------------------------------------------------------
	
	if($this->title)
	{
		$other_list_actions = ' &bull; <a href="dbstatus.php?action=EditList&LookupListid=' . $this->lookup_list['id'] . '">Edit This list</a><br> &bull; <a href="dbstatus.php?action=CreateList">Create Another List</a><br> &bull; <a href="dbstatus.php?action=ViewAllLists">View All Lists</a><br> &bull; <a href="dbstatus.php?action=ViewAllListsAndItems">View All Lists And Items</a>';
	}
	else
	{
		$other_list_actions = ' &bull; <a href="dbstatus.php?action=CreateList">Create Another List</a><br> &bull; <a href="dbstatus.php?action=ViewAllLists">View All Lists</a><br> &bull; <a href="dbstatus.php?action=ViewAllListsAndItems">View All Lists And Items</a>';
	}
	
	$divider->displaystart($divider_padding_start_args);
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px vertical-top',
				'border-1px width-100percent vertical-top',
			],
		],
		'list'=>[
			[
				'<nobr>Other List Actions:</nobr>', $other_list_actions,
			],
		],
	];
	$generic_list->Display($version_list_display_args);
	
	$divider->displayend($divider_end_args);
	
			// Display Admin Errors
		
		// -------------------------------------------------------------
	
	$error_header_displayed = 0;
	
	if($this->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin() && count($this->admin_errors))
	{
		$error_header_displayed = 1;
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13 margin-top-5px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top horizontal-center font-size-150percent',
					'border-1px width-100percent vertical-top horizontal-center',
				],
			],
			'list'=>[
				[
					'There was an error with your submission.',
				],
			],
		];
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
		
		foreach($this->admin_errors_display as $admin_error_to_display)
		{
			$divider->displaystart($divider_padding_start_args);
			
			$version_list_display_args = [
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>[
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					],
				],
				'list'=>$admin_error_to_display,
			];
			$generic_list->Display($version_list_display_args);
			
			$divider->displayend($divider_end_args);
		}
	}
			// Display Saved Results
		
		// -------------------------------------------------------------
	
	if($this->title)
	{
	#	print("BT: Title???...|" . $this->title . "|<BR><BR>");
				// Display List Title
			
			// -------------------------------------------------------------
		
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-90percent horizontal-center border-2px background-color-gray13 margin-top-5px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				],
			],
			'list'=>[
				[
					'<nobr>List Title :</nobr>', $this->title,
				],
			],
		];
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
		
				// Display List Items
			
			// -------------------------------------------------------------
		
		$divider->displaystart($divider_padding_start_args);
		
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'width-90percent horizontal-center border-2px background-color-gray13 margin-top-5px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				],
			],
			'list'=>$this->items,
		];
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
		
	//	print("<PRE>");
	//	print_r($this->lookup_list_items);
	//	print("</PRE>");
	}
	
			// Display Form
		
		// -------------------------------------------------------------
	
	else
	{
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
		
				// Display List Title
			
			// -------------------------------------------------------------
		
		$divider_padding_start_args = [
			'class'=>'horizontal-left',
		];
		
		$divider->displaystart($divider_padding_start_args);
		

		print('<span class="margin-left-5px">List Title</span>');
		
		
		$type_args = [
			'type'=>'text',
			'name'=>'Title',
			'size'=>60,
			'maxlength'=>255,
			'class'=>'margin-5px border-2px',
		];
		
		$form->DisplayFormField($type_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
		
				// Start Field
				// -------------------------------------------------------
		
		$left_div_args = [
			'class'=>'horizontal-left',
		];
		
		$divider->displaystart($left_div_args);
	
				// Display Field
				// -------------------------------------------------------
		
		$type_args = [
			'type'=>'text',
			'name'=>'ItemKey[]',
			'class'=>'margin-top-5px margin-left-5px',
			'size'=>60,
			'maxlength'=>255,
		];
		
		$form->DisplayFormField($type_args);
		
				// Display Field
				// -------------------------------------------------------
		
		$type_args = [
			'type'=>'text',
			'name'=>'ItemValue[]',
			'class'=>'margin-top-5px margin-left-5px',
			'size'=>60,
			'maxlength'=>255,
		];
		
		$form->DisplayFormField($type_args);
		
				// Display 'Add' Button
				// -------------------------------------------------------
		
		$type_args = [
			'type'=>'button',
			'id'=>'add-item-button',
			'class'=>'float-right margin-top-5px margin-right-5px',
			'value'=>'Add Item',
		];
		
		$form->DisplayFormField($type_args);
		
				// Clear Float
				// -------------------------------------------------------
		
		$clear_float_divider_start_args = [
			'class'=>'clear-float',
		];
		
		$divider->displaystart($clear_float_divider_start_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
		
				// Start Hidden Field
				// -------------------------------------------------------
		
		$clear_float_divider_start_args = [
			'id'=>'hidden-item-input',
			'class'=>'display-none',
		];
		
		$divider->displaystart($clear_float_divider_start_args);
		
				// Display Hidden Input
				// -------------------------------------------------------
		
		$type_args = [
			'type'=>'text',
			'name'=>'ItemKey-Hidden',
			'class'=>'margin-top-5px margin-left-5px',
			'size'=>60,
			'maxlength'=>255,
		];
		
		$form->DisplayFormField($type_args);
		
		$type_args = [
			'type'=>'text',
			'name'=>'ItemValue-Hidden',
			'class'=>'margin-top-5px margin-left-5px',
			'size'=>60,
			'maxlength'=>255,
		];
		
		$form->DisplayFormField($type_args);
		
				// Display 'Delete' Button
				// -------------------------------------------------------
		
		$type_args = [
			'type'=>'button',
			'id'=>'delete-item-button',
			'class'=>'float-right margin-top-5px margin-right-5px delete-item-button',
			'value'=>'Remove',
		];
		
		$form->DisplayFormField($type_args);
		
				// Clear Float
				// -------------------------------------------------------
		
		$clear_float_divider_start_args = [
			'class'=>'clear-float',
		];
		
		$divider->displaystart($clear_float_divider_start_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
		
				// Finish Hidden Field
				// -------------------------------------------------------
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
		
				// Establish Bottom Hidden Div for JS-Showing
				// -------------------------------------------------------
		
		$clear_float_divider_start_args = [
			'id'=>'item-list',
		];
		
		$divider->displaystart($clear_float_divider_start_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
		
				// Display Form Save Button : Save
			
			// -------------------------------------------------------------
		

		print('<center>');
		
		
		$type_args = [
			'type'=>'submit',
			'name'=>'Create List',
			'class'=>'margin-5px',
			'value'=>'Create List',
		];
		
		$form->DisplayFormField($type_args);
		

		print('</center>');
		
		
			// Hidden Action
			// -----------------------------------------------------
		
		$type_args = [
			'type'=>'hidden',
			'name'=>'action',
			'value'=>'CreateList',
		];
		
		$form->DisplayFormField($type_args);
		
				// Display Form Elements : End
			
			// -------------------------------------------------------------
		
		$divider_end_args = [
		];
		$divider->displayend($divider_end_args);
		
		$end_form_args = [
		];
		$form->EndForm($end_form_args);
		
		$divider_end_args = [
		];
		$divider->displayend($divider_end_args);
	}
	
?>
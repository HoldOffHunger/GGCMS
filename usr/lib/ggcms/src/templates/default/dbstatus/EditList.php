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
	
	$good_header_text = 'System Status : Edit List';
	
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
	
			// Display Parent Record Info
		
		// -------------------------------------------------------------
	
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
				'<nobr>Other List Actions:</nobr>', ' &bull; <a href="dbstatus.php?action=CreateList">Create List</a><br> &bull; <a href="dbstatus.php?action=ViewAllLists">View All Lists</a><br> &bull; <a href="dbstatus.php?action=ViewAllListsAndItems">View All Lists and Items</a>',
			],
		],
	];
	$generic_list->Display($version_list_display_args);
	
	$divider->displayend($divider_end_args);
	
			// Delete Information
		
		// -------------------------------------------------------------
	
	if($this->delete) {
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
					'<nobr>Delete Completed:</nobr>', 'The lookup list has been deleted as requested.  The information removed is displayed below.',
				],
			],
		];
		$generic_list->Display($version_list_display_args);
		
		$divider->displayend($divider_end_args);
	}
	
			// Display List
		
		// -------------------------------------------------------------
		
	$lookup_list = $this->lookup_list;
	
	$divider->displaystart($divider_padding_start_args);
	
	$version_list_display_args = [
		'options'=>[
			'tableheaders'=>0,
			'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
			'rowclass'=>'border-1px horizontal-left',
			'cellclass'=>[
				'border-1px vertical-top',
				'border-1px width-100percent vertical-top',
				'border-1px vertical-top',
			],
		],
		'list'=>[
			[
				'<nobr>' . 'LookupList' . $lookup_list['id'] . ' : ' .'</nobr>',
				$lookup_list['Title'],
			],
		],
	];
	$generic_list->Display($version_list_display_args);
	
	$divider->displayend($divider_end_args);
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$start_form_args = [
		'action'=>0,
		'method'=>'post',
		'files'=>0,
		'formclass'=>'margin-0px',
	];
	
	$form->StartForm($start_form_args);
	
			// Display List Title
		
		// -------------------------------------------------------------
	
	if(!$this->delete) {
		$divider_padding_start_args = [
			'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
		];
		
		$divider->displaystart($divider_padding_start_args);
		
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
			'value'=>$lookup_list['Title'],
		];
		
		$form->DisplayFormField($type_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
			
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
	}
	
			// Display List Items
		
		// -------------------------------------------------------------
	
	$lookup_list_items = $this->lookup_list_items;
	
	if(count($lookup_list_items)) {
		if(!$this->delete) {
			$first_lookup_list_item = array_shift($lookup_list_items);
			
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
				'value'=>$first_lookup_list_item['ItemKey'],
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
				'value'=>$first_lookup_list_item['ItemValue'],
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
		}
		
		foreach($lookup_list_items as $lookup_list_item) {
					// Start Field
					// -------------------------------------------------------
			
			$left_div_args = [
				'class'=>'horizontal-left input-divider',
			];
			
			$divider->displaystart($left_div_args);
		
					// Display Field
					// -------------------------------------------------------
			
			if($this->delete) {
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
							$lookup_list_item['ItemKey'],
							$lookup_list_item['ItemValue'],
						],
					],
				];
				$generic_list->Display($version_list_display_args);
				
				$divider->displayend($divider_end_args);
			} else {
					// Display Field
					// -------------------------------------------------------
					
				$type_args = [
					'type'=>'text',
					'name'=>'ItemKey[]',
					'class'=>'margin-top-5px margin-left-5px',
					'size'=>60,
					'maxlength'=>255,
					'value'=>$lookup_list_item['ItemKey'],
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
					'value'=>$lookup_list_item['ItemValue'],
				];
				
				$form->DisplayFormField($type_args);
				
						// Display 'Add' Button
						// -------------------------------------------------------
				
				$type_args = [
					'type'=>'button',
					'id'=>'delete-item-button',
					'class'=>'float-right margin-top-5px margin-right-5px delete-item-button',
					'value'=>'Remove',
				];
				
				$form->DisplayFormField($type_args);
			}
			
					// Clear Float
					// -------------------------------------------------------
			
			$clear_float_divider_start_args = [
				'class'=>'clear-float',
			];
			
			$divider->displaystart($clear_float_divider_start_args);
			
			$clear_float_divider_end_args = [
			];
			
			$divider->displayend($clear_float_divider_end_args);
			
					// End Display
					// -------------------------------------------------------
			
			$clear_float_divider_end_args = [
			];
			
			$divider->displayend($clear_float_divider_end_args);
		}
	} elseif(!$this->delete) {
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
	}
	
			// Start Hidden Field and Save Buttons
			// -------------------------------------------------------
	
	if(!$this->delete) {
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
			'name'=>'Save List',
			'class'=>'margin-5px',
			'value'=>'Save List',
		];
		
		$form->DisplayFormField($type_args);
		
		$type_args = [
			'type'=>'submit',
			'name'=>'DeleteList',
			'class'=>'margin-5px confirm',
			'value'=>'Delete List',
		];
		
		$form->DisplayFormField($type_args);
		
		print('</center>');
	}
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = [];
	$form->EndForm($end_form_args);
	
?>
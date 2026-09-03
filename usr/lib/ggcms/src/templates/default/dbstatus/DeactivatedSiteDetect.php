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
	
	$divider_end_args = [
	];
	
			// Display Header
		
		// -------------------------------------------------------------
	
	$good_header_text = 'System Status : Archives URL Detect';
	
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
	
	$results_count = count($this->StatusDataArray);
	
	if($results_count) {
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
				'Note: Examined a total number of ' . number_format($this->entry_code_count) . ' entry records.  A total of ' . number_format(count($this->StatusDataArray)) . ' deactivated-site issue records were discovered.  See: facebook, myspace, yahoo, wayback.',
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
	
	
	print('<b>Check DB? :</b>');
	
	$type_args = [
		'type'=>'submit',
		'name'=>'do-detection',
		'class'=>'margin-5px',
		'value'=>'Detect DB Issues',
	];
	
	$form->DisplayFormField($type_args);
	

	print('</center>');
	
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = [
	];
	$form->EndForm($end_form_args);
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
			// Display Results
		
		// -------------------------------------------------------------

	if($this->do_detection)
	{
				// Display Results : Start
			
			// -------------------------------------------------------------
		
		$divider_padding_start_args = [
			'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
		];
		$divider->displaystart($divider_padding_start_args);
		
				// Display Results : Entries
			
			// -------------------------------------------------------------
		
		print('<div style="text-align:left;" class="width-100percent"><ul style="text-align:left;">');
		
		$printable_results = [];
		
		if($results_count > 0) {
			for($i = 0; $i < $results_count; $i++)
			{
				$entry = $this->StatusDataArray[$i];
				$parents = $entry['parents'];
				
			#	print_r($parents);
				
				$link = $entry['badlink'];
				
				$printable_piece = '';
				
				#print('<li>');
				$printable_piece .= '<li>';
				
				#print('<a href="');
				$printable_piece .= '<a href="';
				
				$parents_count = count($parents);
				
				$parent_titles = [];
				$parent_codes = [];
				
				for($j = 0; $j < $parents_count; $j++) {
					$parent = $parents[$j];
					$parent_titles[] = $parent['Title'];
					$parent_codes[] = $parent['Code'];
				}
				
				#print(implode('/', $parent_codes));
				$printable_piece .= implode('/', $parent_codes);
				
				#print('/modify.php?action=Edit" target="_blank">');
				$printable_piece .= '/modify.php?action=Edit" target="_blank">';
				
			#	print(implode(' / ', $parent_titles));
				$printable_piece .= implode(' / ', $parent_titles);
				
			#	print('Link #' . $link['id']);
			#	print(' : ');
			#	print($link['URL']);
			#	print('</a>');
				$printable_piece .= '</a>';
				
			#	print('</li>');
				$printable_piece .= '</li>';
				
				$printable_results[] = $printable_piece;
			}
		} else {
			print('<li>Nothing found.</li>');
		}
		
		sort($printable_results);
		print(implode('', $printable_results));
		
		print('</ul></div>');
	
				// Display Results : End
			
			// -------------------------------------------------------------
		
		$divider_end_args = [
		];
		$divider->displayend($divider_end_args);
	}
	
?>
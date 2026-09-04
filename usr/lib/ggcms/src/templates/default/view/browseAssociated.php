<?php

	print("<script type='text/javascript'>");
	print("
		function inIframe () {
			try {
				return window.self !== window.top;
			} catch (e) {
				return true;
			}
		}
		
		if(!inIframe()) {
			document.location = 'view.php#works';
		}
	");
	print("</script>");
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
		
				// Timeframe
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-date.php');
	$entrydate = new module_entrydate(['that'=>$this]);
	$time_data = $entrydate->getSimpleData();
	$time_frame = $time_data['text'];
	
				// Header_REAL
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_frame]);
	
	ggreq('modules/html/header.php');
	$header = new module_header;
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
	ggreq('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this]);
	
	ggreq('modules/html/entry-list-navigation.php');
	$entrylistnavigation = new module_entrylistnavigation(['that'=>$this]);
	
	if($this->children_count !== 0) {
				// View Browsing Numbers
			
			// -------------------------------------------------------------
			
		if($this->Param('ignore_parent')) {
			print('<center>');
			print('<div class="horizontal-center width-70percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left" title="');
			
			print(' (Last Updated: ');
			$date_epoch_time = strtotime($this->that->associated_record_stats['LastModificationDate']);
			$full_date = date("F d, Y; H:i:s", $date_epoch_time);
			print($full_date);
			print('.)');
			
			print('">');
			
			$valid_item_titles = [
				'quotes'=>true,
				'writings'=>true,
			];
			
			if(!empty($args['creation_type'])) {
				$creation_type_text = $args['creation_type'];
			} elseif($this->Param('item_title') && isset($valid_item_titles[$this->Param('item_title')])) {
				$creation_type_text = $this->Param('item_title');
			} else {
				$creation_type_text = 'documents';
			}
			
			print('<strong>');
			print('<p class="horizontal-left margin-5px font-family-tahoma">');
			
			$valid_stats_prefixes = [
				'This writing has'=>true,
			];
			if($this->Param('stats_prefix') && isset($valid_stats_prefixes[$this->Param('stats_prefix')])) {
				$stats_prefix = $this->Param('stats_prefix') . ' ';
			} else {
				$stats_prefix = 'This person has authored ';
			}
			
			print($stats_prefix . number_format($this->associated_record_stats['AssociatedRecordCount']) . ' ' . $creation_type_text);
			
			if(
				$this->associated_record_stats['AssociatedWordCount'] > 0 ||
				$this->associated_record_stats['AssociatedCharacterCount'] > 0
			) {
				print(', with ' . number_format($this->associated_record_stats['AssociatedWordCount']) . ' words or ' . number_format($this->associated_record_stats['AssociatedCharacterCount']) . ' characters.');
			} else {
				print('.');
			}
			print('</p>');
			print('</strong>');
			
			print('</div>');
			print('</div>');
			print('</center>');
			print('<div class="clear-float"></div>');
		}
		
		$header_secondary_args = [
			'title'=>'Browsing : ' . $this->child_record_start_index . ' to ' . $this->child_record_end_index . ' of ' . $this->children_count ,
			'imagemouseover'=>$this->total_pages . ' Total Pages Available',
			'divmouseover'=>$this->total_children_viewed . ' Items Viewed, ' . $this->total_children_left . ' Remaining to Be Viewed',
			'level'=>3,
			'divclass'=>'width-33percent border-2px background-color-gray13 margin-5px float-left',
			'textclass'=>'font-family-arial padding-0px margin-5px horizontal-center vertical-center',
			'imagedivclass'=>'border-2px margin-5px background-color-gray10',
			'imageclass'=>'border-1px',
			'domainobject'=>$this->domain_object,
			'leftimageenable'=>0,
			'rightimageenable'=>0,
		];
		$header->display($header_secondary_args);
	
				// View Browsing Per-Page Setting
			
			// -------------------------------------------------------------
		
		print('<form class="margin-0px" method="POST">');
		
		$browsing_options = 'Results Per Page : <select id="perpage" name="perpage">';
		
		for($i = 10; $i <= 200; $i += 10) {
			$browsing_options .= '<option value="' . $i . '"';
			
			if($i == $this->perpage && !$this->custom_per_page_selected) {
				$browsing_options .= ' SELECTED="SELECTED"';
			}
			
			$browsing_options .= '>' . $i . '</option>';
		}
		
		$browsing_options .= '<option value="custom"';
		if($this->custom_per_page_selected) {
			$browsing_options .= ' SELECTED="SELECTED"';
		}
		$browsing_options .= '>Custom</option>';
		$browsing_options .= '</select> ';
		$browsing_options .= '<input id="CustomPerPage" name="CustomPerPage" type="text" size="5" value="' . $this->perpage . '"> ';
		$browsing_options .= '<input type="submit" value="Update"> ';
		$browsing_options .= '<input type="hidden" name="page" value="1">';
		
		$header_secondary_args = [
			'title'=>$browsing_options,
			'divmouseover'=>'Adjust results per page here.',
			'level'=>3,
			'divclass'=>'width-33percent border-2px background-color-gray13 margin-5px float-right',
			'textclass'=>'font-family-arial padding-0px margin-5px horizontal-center vertical-center',
			'imagedivclass'=>'border-2px margin-5px background-color-gray10',
			'imageclass'=>'border-1px',
			'domainobject'=>$this->domain_object,
			'leftimageenable'=>0,
			'rightimageenable'=>0,
		];
		$header->display($header_secondary_args);
		
		print('</form>');
		
		print('<div class="clear-float"></div>');
		
				// View Selected Record List Pages
			
			// -------------------------------------------------------------
		
		$entrylistnavigation->Display([
			'skip'=>$this->Param('ignore_parent'),
			'parents'=>$this->Param('parents'),
			'list_author'=>$this->Param('list_author'),
			'ignore_parent'=>$this->Param('ignore_parent'),
			'item_title'=>$this->Param('item_title'),
			'stats_prefix'=>$this->Param('stats_prefix'),
		]);
		
				// View Selected Record List Pages
			
			// -------------------------------------------------------------
			
		print('<div class="horizontal-center width-90percent">');
		$child_display = $entrysort->Sort(['entries'=>$this->children]);
		$entrylist->Display([
			'children'=>$child_display,
			'skip'=>$this->Param('ignore_parent'),
			'parents'=>$this->Param('parents'),
			'list_author'=>$this->Param('list_author'),
			'ignore_parent'=>$this->Param('ignore_parent'),
			'item_title'=>$this->Param('item_title'),
			'stats_prefix'=>$this->Param('stats_prefix'),
		]);
		print('</div>');
			
				// View Selected Record List Pages
			
			// -------------------------------------------------------------
		
		print('<br>');
		
		$entrylistnavigation->Display([
			'skip'=>$this->Param('ignore_parent'),
			'parents'=>$this->Param('parents'),
			'list_author'=>$this->Param('list_author'),
			'ignore_parent'=>$this->Param('ignore_parent'),
			'item_title'=>$this->Param('item_title'),
			'stats_prefix'=>$this->Param('stats_prefix'),
		]);
	} else {
		print('<div class="horizontal-center width-90percent">');
		print('<h3 style="font-family:tahoma;margin:0px;">');
		print('Nothing available yet!');
		print('</h3>');
		print('</div>');
	}
	
			// Display Debug
		
		// -------------------------------------------------------------
	
	#ggreq('modules/html/debug.php');
	#$debug = new module_debug(['that'=>$this]);
	#$debug->DisplayBasicRecords([]);
	
?>
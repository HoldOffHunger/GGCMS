<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
	ggreq('modules/html/text.php');
	$text = new module_text;
	
	ggreq('modules/html/form.php');
	$form = new module_form;
	
	ggreq('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/html/table.php');
	$table = new module_table;
	
	ggreq('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
	ggreq('modules/html/header.php');
	$header = new module_header;
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
			// Mouseover Values
		
		// -------------------------------------------------------------
	
	$div_mouseover = '';
	
	if($this->entry['quote'] && $this->entry['quote'][0]) {
		$random_quote = $this->entry['quote'][array_rand($this->entry['quote'], 1)];
		
		$div_mouseover = '&quot;' . str_replace('"', '\'', $random_quote['Quote']) . '&quot; -- ' . str_replace('"', '\'', $random_quote['Source']);
	}
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text,
		'sub_text'=>$sub_text,
		'sub_title'=>$sub_title,
	]);
	
	$entryheader->Display();
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_one_third_start_args = [
		'class'=>'width-33percent float-left',
	];
	
	$divider_case_start_args = [
		'class'=>'width-100percent height-400px overflow-auto',
	];
	
	$divider_frame_start_args = [
		'class'=>'width-100percent border-2px',
	];
	
	$divider_padding_start_args = [
		'class'=>'margin-5px padding-5px',
	];
	
	$divider_end_args = [
	];
	
			// Admin Controls and Top Bar
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/view-top-bar.php');
	$viewtopbar = new module_viewtopbar(['that'=>$this]);
	$viewtopbar->Display(['file'=>__FILE__]);
	
			// View Selected Record List
		
		// -------------------------------------------------------------
	
	if($this->entry['ChildNounPlural']) {
		$child_noun_plural = $this->entry['ChildNounPlural'];
	} else {
		$child_noun_plural = 'Works';
	}
	
	$header_secondary_args = [
		'title'=>'Total ' . $this->entry['ChildAdjective'] . ' ' . $child_noun_plural . ' : ' . $this->children_count ,
		'divmouseover'=>$div_mouseover,
		'level'=>3,
		'divclass'=>'width-33percent border-2px background-color-gray13 margin-5px',
		'textclass'=>'padding-0px margin-5px horizontal-center vertical-center font-family-arial',
		'imagedivclass'=>'border-2px margin-5px background-color-gray10',
		'imageclass'=>'border-1px',
		'domainobject'=>$this->domain_object,
		'leftimageenable'=>0,
		'rightimageenable'=>0,
	];
	$header->display($header_secondary_args);
			
			// Introduction
		
		// -------------------------------------------------------------
	
	print('<center>');
	print('<div class="horizontal-center width-70percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	
	print('<strong>');
	print(str_replace('<p>', '<p class="horizontal-left margin-5px font-family-tahoma">', $this->entry['textbody'][0]['Text']));
	
	print('<p class="horizontal-left margin-5px font-family-tahoma" title="');
	
	print(' (Last Updated: ');
	$date_epoch_time = strtotime($this->child_record_stats['LastModificationDate']);
	$full_date = date("F d, Y; H:i:s", $date_epoch_time);
	print($full_date);
	print('.)');
	
	print('">');
	print('This archive contains ' . number_format($this->child_record_stats['ChildRecordCount']) . ' texts, with ' . number_format($this->child_record_stats['ChildWordCount']) . ' words or ' . number_format($this->child_record_stats['ChildCharacterCount']) . ' characters.');
	
	print('</strong>');
	print('</p>');
	
	print('</div>');
	print('</div>');
	print('</center>');
							
	$clear_float_divider_start_args = [
		'class'=>'clear-float',
	];
	
	$divider->displaystart($clear_float_divider_start_args);
	
	$clear_float_divider_end_args = [
	];
	
	$divider->displayend($clear_float_divider_end_args);
			
			// New Entries
	
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-new.php');
	$index_new = new module_indexnew([
		'that'=>$this,
		'entrysort'=>$entrysort,
	]);
	$index_new->Display();
			
			// Random Entries
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-random.php');
	$index_random = new module_indexrandom([
		'that'=>$this,
		'entrysort'=>$entrysort,
	]);
	$index_random->Display();
	
			// Images, Tags, Quotes, Descriptions, Textbodies, Event Dates and Likes
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-sections.php');
	$index_sections = new module_indexsections(['that'=>$this]);
	$index_sections->DisplayImages(['header'=>'I Never Forget a ' . $this->entry['ChildNoun'] . ' Book']);
	$index_sections->DisplayTags(['header'=>'Tagged']);
	$index_sections->DisplayQuotes(['header'=>'I\'ve Heard It Said...']);
	$index_sections->DisplayDescriptions(['header'=>'Let Me Tell You About a Book I Know']);
	$index_sections->DisplayTexts(['header'=>'Texts']);
	$index_sections->DisplayEventDates(['header'=>'Fragments of Our Past']);
	$index_sections->DisplayLikes(['header'=>'Well-Liked Books']);
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/index-share.php');
	$index_share = new module_indexshare(['that'=>$this]);
	$index_share->Display();
			
			// Debug
		
		// -------------------------------------------------------------
	
	/*
	print("<PRE>RECORD LIST:");
	print_r($this->record_list);
	print("\n\nMASTER RECORD:\n\n");
	print_r($this->master_record);
	print("\n\nPARENT:\n\n");
	print_r($this->parent);
	print("\n\nENTRY:\n\n");
	print_r($this->entry);
	print("\n\nCHILDREN:\n\n");
	print_r($this->children);
	print("</PRE>");
	*/
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>
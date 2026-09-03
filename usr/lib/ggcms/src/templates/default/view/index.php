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
	
	classreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
	classreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
			// Mouseover Values
		
		// -------------------------------------------------------------
	
	$div_mouseover = '';
	$image_mouseover = '';
	
	if($this->entry['quote'] && $this->entry['quote'][0]) {
		$random_quote = $this->entry['quote'][array_rand($this->entry['quote'], 1)];
		
		$div_mouseover = '&quot;' . str_replace('"', '\'', $random_quote['Quote']) . '&quot; -- ' . str_replace('"', '\'', $random_quote['Source']);
	}
	
	if($this->entry['description'] && $this->entry['description'][0])
	{
		$description = $this->entry['description'][0];
		
		$image_mouseover = str_replace('"', '\'', $description['Description']);
	}
	
			// Display Header
		
		// -------------------------------------------------------------
	
	classreq('modules/html/entry-header.php');
	classreq('modules/html/entry-index-header.php');
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
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	if($this->authentication_object->user_session['UserAdmin.id']) {
		classreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center width-95percent margin-top-5px">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
		
		classreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs(['that'=>$this]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		classreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();
		
				// End Top Bar
			
			// -------------------------------------------------------------
		
		print('</div>');
	
			// Finish Breadcrumb Trails
		
		// -------------------------------------------------------------
							
	$clear_float_divider_start_args = [
		'class'=>'clear-float',
	];
	
	$divider->displaystart($clear_float_divider_start_args);
	
	$clear_float_divider_end_args = [
	];
	
	$divider->displayend($clear_float_divider_end_args);
	
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
	
	classreq('modules/html/index-new.php');
	$index_new = new module_indexnew([
		'that'=>$this,
		'entrysort'=>$entrysort,
	]);
	$index_new->Display();
			
			// Random Entries
		
		// -------------------------------------------------------------
	
	classreq('modules/html/index-random.php');
	$index_random = new module_indexrandom([
		'that'=>$this,
		'entrysort'=>$entrysort,
	]);
	$index_random->Display();
	
			// Images
		
		// -------------------------------------------------------------
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('I Never Forget a ' . $this->entry['ChildNoun'] . ' Book');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->images_random as $image)
	{
		print('<div class="border-2px background-color-gray15 margin-5px float-left" title="');
		print($image['Entry.Title']);
		if($image['Entry.Subtitle'])
		{
			print(' : ');
			print($image['Entry.Subtitle']);
		}
		print('">');
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="height-100px width-100px background-color-gray0 horizontal-center">');
		print('<div class="horizontal-center vertical-specialcenter">');
	#	print_r($image['Entry.Code']);
		print('<a href="' . $image['Entry.Code'] . '/view.php"');
		print(' target="_blank"');
		print('>');
		print('<img width="');
		print(ceil($image['IconPixelWidth'] / 2));
		print('" height="');
		print(ceil($image['IconPixelHeight'] / 2));
		print('" src="');
		print($this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
		print('/image/');
		print(implode('/', str_split($image['FileDirectory'])));
		print('/');
		print($image['IconFileName']);
		print('">');
		print('</a>');
		print('</div>');
		print('</div>');
		print('</div>');
		print('</div>');
	}
		$clear_float_divider_start_args = [
			'class'=>'clear-float',
		];
		
		$divider->displaystart($clear_float_divider_start_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
	
	print('</div>');
	print('</div>');
			
			// Tags
		
		// -------------------------------------------------------------
	
	if(count($this->tags_random)) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Tagged');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->tags_random as $tag)
	{
		print('<div class="border-2px background-color-gray13 margin-left-5px margin-top-5px margin-bottom-5px float-left">');
		print('<span class="horizontal-left margin-5px font-family-arial">');
		print('<a href="view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
		print($tag['Tag']);
		
		print(' (');
		print(number_format($this->tag_counts['children'][$tag['Tag']]));
		print(')');
		
		print('</a>');
		print('</span>');
		print('</div>');
	}
	
	$clear_float_divider_start_args = [
		'class'=>'clear-float',
	];
	
	$divider->displaystart($clear_float_divider_start_args);
	
	$clear_float_divider_end_args = [
	];
	
	$divider->displayend($clear_float_divider_end_args);
	
	print('</div>');
	print('</div>');
	}
			
			// Quotes
		
		// -------------------------------------------------------------
	
	if(count($this->quotes_random)) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('I\'ve Heard It Said...');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->quotes_random as $quote)
	{
		print('<div class="border-2px background-color-gray15 margin-5px horizontal-left" title="');
		print($quote['Entry.Title']);
		if($quote['Entry.Subtitle'])
		{
			print(' : ');
			print($quote['Entry.Subtitle']);
		}
		print('">');
		print('<blockquote class="margin-top-0px"><p class="horizontal-left margin-5px font-family-arial"><em>"<quote>');
		print('<a href="' . $quote['Entry.Code'] . '/view.php">');
		print($quote['Quote']);
		print('</a>');
		print('</quote>"</em></p></blockquote>');
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
			
			// Descriptions
		
		// -------------------------------------------------------------
	
	if(count($this->descriptions_random)) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Let Me Tell You About a Book I Know');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->descriptions_random as $description)
	{
		print('<div class="border-2px background-color-gray15 margin-5px horizontal-left" title="');
		print($description['Entry.Title']);
		if($description['Entry.Subtitle'])
		{
			print(' : ');
			print($description['Entry.Subtitle']);
		}
		print('">');
		print('<blockquote class="margin-top-0px"><p class="horizontal-left margin-5px font-family-arial"><strong>');
		print('<a href="' . $description['Entry.Code'] . '/view.php">');
		print($description['Description']);
		print('</a>');
		print('</strong></p></blockquote>');
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
			
			// Textbodies
		
		// -------------------------------------------------------------
	
	if(count($this->textbodies_random)) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Texts');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->textbodies_random as $textbody) {
		print('<div class="border-2px background-color-gray15 margin-5px horizontal-left" title="');
		print($textbody['Entry.Title']);
		if($textbody['Entry.Subtitle'])
		{
			print(' : ');
			print($textbody['Entry.Subtitle']);
		}
		print('">');
		print('<blockquote class="margin-top-0px"><p class="horizontal-left margin-5px font-family-arial">');
		print('<a href="' . $textbody['Entry.Code'] . '/view.php">');
		$good_text = strip_tags(substr($textbody['Text'], 0, 500));
		$good_text = str_replace('  ', ' ', $good_text);
		$good_text = str_replace('  ', ' ', $good_text);
		$good_text = str_replace('  ', ' ', $good_text);
		print($good_text);
		if(strlen($textbody['Text']) > 500)
		{
			print('...');
		}
		print('</a>');
		print('</p></blockquote>');
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
			
			// Event Dates
		
		// -------------------------------------------------------------
	
	if($this->eventdates_random) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Fragments of Our Past');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->eventdates_random as $eventdate)
	{
		print('<div class="margin-5px horizontal-left font-family-arial">');
		
		$date_time_pieces = explode(' ', $eventdate['EventDateTime']);
		$event_date = $date_time_pieces[0];
		$event_time = $date_time_pieces[1];
		
		print('<div class="float-left border-2px margin-5px background-color-gray13">');
		print('<div class="margin-5px">');
		
		print('<strong>');
		
		if($event_date != '0000-00-00')
		{
			$date_epoch_time = strtotime($event_date);
			print('<a href="' . $eventdate['Entry.Code'] . '/view.php">');
			print(date("F d, Y", $date_epoch_time));
			print('</a>');
		}
		
		print(' ');
		
		if($event_time != '00:00:00')
		{
			print($event_time);
		}
		
		print(' : </strong>');
		print('</div>');
		print('</div>');
		
		print('<a href="' . $eventdate['Entry.Code'] . '/view.php">');
		print($eventdate['Title']);
		print(' of ');
		
		$display_title = $eventdate['Entry.Title'];
		
		if($eventdate['Entry.Subtitle'])
		{
			$display_title .= ' : ';
			$display_title .= $eventdate['Entry.Subtitle'];
		}

		print($display_title);
		
		print('.');
		print('</a>');
			
					// Finish Floated Box
				
				// -------------------------------------------------------------
									
			$clear_float_divider_start_args = [
				'class'=>'clear-float',
			];
			
			$divider->displaystart($clear_float_divider_start_args);
			
			$clear_float_divider_end_args = [
			];
			
			$divider->displayend($clear_float_divider_end_args);
		
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
			
			// Likes
		
		// -------------------------------------------------------------
	
	if(count($this->likes_random)) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Well-Liked Books');
	print('</strong>');
	print('</h2>');
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
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->likes_random as $like)
	{
		print('<div class="margin-5px horizontal-left font-family-arial">');
		
		print('<div class="float-left border-2px margin-5px background-color-gray13">');
		print('<div class="margin-5px">');
		
		print('<strong>');
		print('<a href="' . $like['Entry.Code'] . '/view.php">');
		
		print(number_format($like['counts']['likes']));
		
		print(' Likes');
		print('</a>');
		print('</strong>');
		print('</div>');
		print('</div>');
		
		print('<a href="' . $like['Entry.Code'] . '/view.php">');
		
		$display_title = $like['Entry.Title'];
		
		if($like['Entry.Subtitle'])
		{
			$display_title .= ' : ' . $like['Entry.Subtitle'];
		}

		print($display_title);
		
		print('</a>');
		
				// Finish Floated Box
			
			// -------------------------------------------------------------
								
		$clear_float_divider_start_args = [
			'class'=>'clear-float',
		];
		
		$divider->displaystart($clear_float_divider_start_args);
		
		$clear_float_divider_end_args = [
		];
		
		$divider->displayend($clear_float_divider_end_args);
		
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
	
			// Share Package
		
		// -------------------------------------------------------------
	
	classreq('modules/html/socialmediasharelinks.php');
	$social_media_share_links_args = [
		'globals'=>$this->handler->globals,
		'textonly'=>$this->mobile_friendly,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
		'socialmedia'=>$this->social_media,
		'sharewithtext'=>'Share With',
		'socialmediasharelinkargs'=>[
			'url'=>$this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/anarchism.php?action=index',
			'title'=>$this->header_title_text,
			'desc'=>$image_mouseover,
			'provider'=>$this->domain_object->primary_domain_lowercased,
		],
	];
	$social_media_share_links = new module_socialmediasharelinks($social_media_share_links_args);
		
				// Display Social Networking Options
			
			// -------------------------------------------------------------
	
	$social_media_share_links->display();
		
				// Finish Textbody Header
			
			// -------------------------------------------------------------
	
	$clear_float_divider_start_args = [
		'class'=>'clear-float',
	];
	
	$divider->displaystart($clear_float_divider_start_args);
	
	$clear_float_divider_end_args = [
	];
	
	$divider->displayend($clear_float_divider_end_args);
			
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
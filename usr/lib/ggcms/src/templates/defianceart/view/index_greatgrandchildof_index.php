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
	
				// Header_REAL
			
			// -------------------------------------------------------------
	
	$date_epoch_time = strtotime($this->child_record_stats['LastModificationDate']);
	$full_date = date("F d, Y; H:i:s", $date_epoch_time);
	
	$sub_text =
		str_replace('<p>', '<p class="horizontal-left margin-5px font-family-tahoma">', $this->entry['textbody'][0]['Text']) . 
		'<p class="horizontal-left margin-5px font-family-tahoma">' .
		'This archive contains ' . number_format($this->child_record_stats['ChildRecordCount']) . ' texts, with ' . number_format($this->child_record_stats['ChildWordCount']) . ' words or ' . number_format($this->child_record_stats['ChildCharacterCount']) . ' characters.' .
		'</p>';
	$sub_title = 'Last Updated: ' . $full_date . '.';
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text,
		'sub_text'=>$sub_text,
		'sub2_text'=>'Total ' . $this->entry['ChildAdjective'] . ' ' . $this->entry['ChildNounPlural'] . ' : ' . $this->children_count,
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
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}
	
			// Start Top Bar
		
		// -------------------------------------------------------------
	
	print('<div class="horizontal-center width-95percent margin-top-5px">');
	
			// Breadcrumbs Info
		
		// -------------------------------------------------------------
		
	ggreq('modules/html/breadcrumbs.php');
	$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'action'=>'index']);
	$breadcrumbs->Display();
	
			// Login Info
		
		// -------------------------------------------------------------
		
	ggreq('modules/html/auth.php');
	$auth = new module_auth(['that'=>$this]);
	$auth->Display();
	
			// End Top Bar
		
		// -------------------------------------------------------------
	
	print('</div>');
	
			// Finish Breadcrumb Trails
		
		// -------------------------------------------------------------
							
	print('<div class="clear-float"></div>');
	
			// View Selected Record List
		
		// -------------------------------------------------------------
	
	$header_secondary_args = [
		'title'=>'Total ' . $this->entry['ChildAdjective'] . ' ' . $this->entry['ChildNounPlural'] . ' : ' . $this->children_count,
	//	'image'=>$this->primary_host_record['PrimaryImageLeft'],
	//	'rightimage'=>$this->primary_host_record['PrimaryImageRight'],
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
#	$header->display($header_secondary_args);
							
#	print('<div class="clear-float"></div>');
			
			// New Entries
		
		// -------------------------------------------------------------
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Newest Additions');
	print('</strong>');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
							
	print('<div class="clear-float"></div>');
	
	print('<div class="horizontal-center width-90percent">');
	
	foreach($this->children as $child) {
		unset($display_image);
		unset($url);
		
		if($child['image']) {
			$child_images = $child['image'];
			$child_image_count = count($child_images);
			if($child_image_count) {
				shuffle($child_images);
				$child_image = $child_images[0];
				$display_image = $child_image;
			}
		}
		
		if(!$display_image && $child['link']) {
			$url = $child['link'][1]['URL'];
			$display_image = [
				'IconPixelWidth'=>200,
				'IconPixelHeight'=>200,
			];
		}
		
		if(!$display_image) {
			$display_image = [
				'IconFileName'=>$this->primary_host_record['PrimaryImageLeft'],
				'IconPixelWidth'=>200,
				'IconPixelHeight'=>200,
			];
		}
		
		if(!$url) {
			$url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) .
			'/image/' .
			implode('/', str_split($display_image['FileDirectory'])) .
			'/' .
			$display_image['IconFileName'];
		}
		
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="background-color-gray0" style="height:200px;width:200px;">');
		print('<div class="vertical-specialcenter">');
		print('<a href="' . $child['Code'] . '/view.php">');
		print('<img');
		#max-width="200"');
#		print(ceil($display_image['IconPixelWidth'] / 2));
	#	print('" max-height="200"');
#		print(ceil($display_image['IconPixelHeight'] / 2));
		print(' style="max-width:200px;max-height:200px;"');
		print(' src="');
		print($url);
		print('">');
		print('</a>');
		print('</div>');
		print('</div>');
		print('</div>');
		print('</div>');
	}
	print('<div class="clear-float"></div>');
	
	print('<div class="horizontal-center width-50percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	print('<h3 class="margin-5px">');
	print('<a href="view.php?action=browse">');
	print('<span class="font-family-tahoma">');
	print('Browse the Complete Selection of ' . $this->children_count . ' ' . $this->entry['ChildAdjective'] . ' ' . $this->entry['ChildNounPlural']);
	print('</span>');
	print('</a>');
	print('</h3>');
	print('</div>');
	print('</div>');
	
	print('</div>');
			
			// Random Entries
		
		// -------------------------------------------------------------
	
	if(count($this->children_random) > 0) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('Blasts from the Past');
	print('</strong>');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
							
	print('<div class="clear-float"></div>');
	
	print('<div class="horizontal-center width-90percent">');
	
	foreach($this->children_random as $child)
	{
		print('<div class="horizontal-center background-color-gray14 border-2px margin-top-5px float-left" style="margin-right:5px;">');
		
		unset($display_image);
		unset($url);
		
		if($child['image']) {
			$child_images = $child['image'];
			$child_image_count = count($child_images);
			if($child_image_count) {
				shuffle($child_images);
				$child_image = $child_images[0];
				$display_image = $child_image;
			}
		}
		
		if(!$display_image && $child['link']) {
			$url = $child['link'][1]['URL'];
			$display_image = [
				'IconPixelWidth'=>200,
				'IconPixelHeight'=>200,
			];
		}
		
		if(!$display_image) {
			$display_image = [
				'IconFileName'=>$this->primary_host_record['PrimaryImageLeft'],
				'IconPixelWidth'=>200,
				'IconPixelHeight'=>200,
			];
		}
		
		if(!$url) {
			$url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) .
			'/image/' .
			implode('/', str_split($display_image['FileDirectory'])) .
			'/' .
			$display_image['IconFileName'];
		}
		
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="background-color-gray0" style="height:200px;width:200px;">');
		print('<div class="vertical-specialcenter">');
		print('<a href="' . $child['Code'] . '/view.php">');
		print('<img');
		#max-width="200"');
#		print(ceil($display_image['IconPixelWidth'] / 2));
	#	print('" max-height="200"');
#		print(ceil($display_image['IconPixelHeight'] / 2));
		print(' style="max-width:200px;max-height:200px;"');
		print(' src="');
		print($url);
		print('">');
		print('</a>');
		print('</div>');
		print('</div>');
		print('</div>');
		print('</div>');
		
		
		print('</div>');
	}
	print('</div>');
	print('<div class="clear-float"></div>');
	}
			
			// Images
		
		// -------------------------------------------------------------
	
	if(count($this->images_random) > 0) {
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
	print('<strong>');
	print('I Never Forget a Good Book');
	print('</strong>');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
							
	print('<div class="clear-float"></div>');
	
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
	
	print('<div class="clear-float"></div>');
	
	print('</div>');
	print('</div>');
	}
			
			// Tags
		
		// -------------------------------------------------------------
	
	if(count($this->tags_random) > 0) {
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
							
	print('<div class="clear-float"></div>');
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->tags_random as $tag)
	{
		print('<div class="border-2px background-color-gray13 margin-left-5px margin-top-5px margin-bottom-5px float-left">');
		print('<span class="horizontal-left margin-5px font-family-arial">');
		print('<a href="/view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
		print($tag['Tag']);
		
		print(' (');
		print(number_format($this->tag_counts[$tag['Tag']]));
		print(')');
		
		print('</a>');
		print('</span>');
		print('</div>');
	}
	
	print('<div class="clear-float"></div>');
	
	print('</div>');
	print('</div>');
	}
			
			// Quotes
		
		// -------------------------------------------------------------
	
	if(count($this->quotes_random) > 0) {
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
							
	print('<div class="clear-float"></div>');
	
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
	
	if(count($this->descriptions_random) > 0) {
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
							
	print('<div class="clear-float"></div>');
	
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
	
	if(count($this->textbodies_random) > 0) {
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
							
	print('<div class="clear-float"></div>');
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->textbodies_random as $textbody)
	{
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
		$text_display = $this->cleanser_object->FormatListOutput([
			'text'=>substr($textbody['Text'], 0, 500),
		]);
		print($text_display);
		print('</a>');
		print('</p></blockquote>');
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
			
			// Event Dates
		
		// -------------------------------------------------------------
	
	if(count($this->eventdates_random) > 0) {
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
							
	print('<div class="clear-float"></div>');
	
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
									
			print('<div class="clear-float"></div>');
		
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
			
			// Likes
		
		// -------------------------------------------------------------
	
	if(count($this->likes_random) > 0) {
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
							
	print('<div class="clear-float"></div>');
	
	print('<div class="horizontal-center width-90percent">');
	print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');
	
	foreach($this->likes_random as $like) {
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
		
		if($like['Entry.Subtitle']) {
			$display_title .= ' : ' . $like['Entry.Subtitle'];
		}

		print($display_title);
		
		print('</a>');
		
				// Finish Floated Box
			
			// -------------------------------------------------------------
								
		print('<div class="clear-float"></div>');
		
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	}
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/socialmediasharelinks.php');
	$social_media_share_links_args = [
		'globals'=>$this->globals,
		'textonly'=>$this->mobile_friendly,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
		'socialmedia'=>$this->social_media,
		'sharewithtext'=>'Share With',
		'socialmediasharelinkargs'=>[
			'url'=>$this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/feminism/view.php?action=index',
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
	
	print('<div class="clear-float"></div>');
			
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
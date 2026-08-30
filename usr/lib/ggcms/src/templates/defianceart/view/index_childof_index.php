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
	$breadcrumbs = new module_breadcrumbs(['that'=>$this]);
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
	
	foreach($this->children as $child)
	{
		print('<div class="horizontal-center background-color-gray14 border-2px margin-top-5px" style="width:30%;float:left;margin-right:5px;">');
		
		unset($display_image);
		
		if($child['image'])
		{
			$child_images = $child['image'];
			$child_image_count = count($child_images);
			if($child_image_count)
			{
				shuffle($child_images);
				$child_image = $child_images[0];
				$display_image = $child_image;
			}
		}
		if(!$display_image) {
			if($this->master_record && $this->master_record['image'] && $this->master_record['image'][0]) {
				$display_image = $this->master_record['image'][0];
			} else {
				$display_image = [
					'IconFileName'=>$this->primary_host_record['PrimaryImageLeft'],
					'IconPixelWidth'=>200,
					'IconPixelHeight'=>200,
				];
			}
		}
		
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="border-2px background-color-gray15 margin-5px float-left">');
		print('<div class="height-100px width-100px background-color-gray0">');
		print('<div class="vertical-specialcenter">');
		print('<a href="' . $child['Code'] . '/view.php?action=index">');
		print('<img width="');
		print(ceil($display_image['IconPixelWidth'] / 2));
		print('" height="');
		print(ceil($display_image['IconPixelHeight'] / 2));
		print('" src="');
		print($this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
		print('/image/');
		print(implode('/', str_split($display_image['FileDirectory'])));
		print('/');
		print($display_image['IconFileName']);
		print('">');
		print('</a>');
		print('</div>');
		print('</div>');
		print('</div>');
		print('</div>');
		
		$child_title = '<a href="' . $child['Code'] . '/view.php?action=index">';
		
		$creation_date = explode(' ', $child['OriginalCreationDate'])[0];
		
		$child_title .= date("M d, Y", strtotime($creation_date));
		$child_title .= '</a>';
		
		$div_mouseover = '';
		
		if($child['textbody'])
		{
			$text_bodies = $child['textbody'];
			
			$text_body_count = count($text_bodies);
			if($text_body_count)
			{
				$first_textbody = $text_bodies[0];
				
			#	$div_mouseover .= number_format($first_textbody['WordCount']) . ' Words / ' . number_format($first_textbody['CharacterCount']) . ' Characters';
			}
		}
		else
		{
			$grand_children = $child['children'];
			
			if($grand_children && is_array($grand_children))
			{
				$grand_children_count = count($grand_children);
				
				if($grand_children_count)
				{
					$grand_child_display = $entrysort->Sort(['entries'=>$grand_children]);
					
					unset($grand_child);
					foreach($grand_child_display as $single_grand_child)
					{
						if(!$grand_child)
						{
							$full_grand_child = $single_grand_child;
							$grand_child = $single_grand_child['textbody'][0];
						}
					}
					
					$div_mouseover .= $full_grand_child['Title'] . ' : ' . number_format($grand_child['WordCount']) . ' Words / ' . number_format($grand_child['CharacterCount']) . ' Characters';
				}
			}
		}
		
		$header_secondary_args = [
			'title'=>$child_title,
			'divmouseover'=>$div_mouseover,
			'level'=>3,
			'divclass'=>'border-2px background-color-gray15 margin-5px float-left',
			'textclass'=>'padding-0px margin-5px horizontal-left font-family-tahoma',
			'imagedivclass'=>'border-2px margin-5px background-color-gray10',
			'imageclass'=>'border-1px',
			'domainobject'=>$this->domain_object,
			'leftimageenable'=>0,
			'rightimageenable'=>0,
		];
		$header->display($header_secondary_args);
		
		print('<p class="horizontal-left margin-5px font-family-arial">');
		
		$time_frame = '';
		
		if($child['eventdate'])
		{
			$child_event_count = count($child['eventdate']);
			for($i = 0; $i < $child_event_count; $i++)
			{
				$child_event = $child['eventdate'][$i];
					
				if($child_event['Title'] == 'Publication')
				{
					$publication_event = $child_event;
				}
				
				if($publication_event)
				{
					$i = $child_event_count;
				}
			}
			
			if($publication_event)
			{
				if($publication_event['EventDateTime'] != '0000-00-00 00:00:00')
				{
					$event_date_pieces = explode('-', $publication_event['EventDateTime']);
					$year = $event_date_pieces[0];
					$time_frame .= $year;
				}
				else
				{
					$time_frame .= '?';
				}
				
				unset($publication_event);
			}
		}
			print('<div class="border-2px background-color-gray15 margin-5px float-left" style="font-family:arial; ">');
		
		if($time_frame)
		{
			print($time_frame);
		}
		
		
		if($child['Title'] || $child['Subtitle'] || $child['association'][0]['entry']['Title'])
		{
			if($time_frame)
			{
				print(' ~ ');
			}
			
			print('<strong>');
			print('<a href="' . $child['Code'] . '/view.php?action=index">');
			
			if($child['Title'])
			{
				print($child['Title']);
			}
			if($child['Title'] && $child['Subtitle'])
			{
				print(' : ');
			}
			if($child['Subtitle'])
			{
				print($child['Subtitle']);
			}
			print('</a>');
			
			if($child['association'][0]['entry']['Title'])
			{
				print(', by ');
				print('<a href="../people/' . $child['association'][0]['entry']['Code'] . '/view.php">');
				print($child['association'][0]['entry']['Title']);
				print('</a> ');
			}
			print('</strong>');
			print('</div>');
		}
		
		if($child['description'])
		{
			$description = $child['description'][0];
			
			if($description && $description['Description'])
			{
				print(' &mdash; ');
				print('<em>');
				if($time_frame || $child['Subtitle'])
				{
					print(' : ');
				}
				
				print($description['Description']);
				print(' ');
				print('</em>');
				
				if($description['Source'])
				{
					$source = $description['Source'];
					
					if(strlen($source) > 50)
					{
						$source = substr($source, 0, 50) . '...';
					}
					
					print(' (From : ' . $source . '.)');
				}
			}
		}
		
		print('</p>');
		
				// Finish Float
			
			// -------------------------------------------------------------
		
		print('<div class="clear-float"></div>');
		
				// Tags
			
			// -------------------------------------------------------------
		
		if($child['tag'])
		{
			$tag_count = count($child['tag']);
			
			if($tag_count)
			{
				$tags = $child['tag'];
				$max_limit = $tag_count;
				if($max_limit > 10)
				{
					$max_limit = 10;
				}
				shuffle($tags);
				
				for($i = 0; $i < $max_limit; $i++)
				{
					$tag = $tags[$i];
					print('<div class="border-2px background-color-gray15 margin-left-5px margin-bottom-5px float-left">');
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
				
						// Finish Float
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
			}
		}
		
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
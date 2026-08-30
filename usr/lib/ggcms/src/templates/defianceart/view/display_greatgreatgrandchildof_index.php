<?php
	
			// Format-Universal Formatting
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');

	ggreq('modules/html/entry-comments.php');
	$entry_comments = new module_entrycomments(['that'=>$this]);

#	ggreq('modules/html/entry-image.php');
#	$entry_image = new module_entryimage(['that'=>$this]);
	
	ggreq('modules/html/entry-quote.php');
	$entry_quotes = new module_entryquotes(['that'=>$this]);
	
	ggreq('modules/html/entry-share.php');
	$entry_share = new module_entryshare(['that'=>$this]);
		
				// Magic Date Setting from Parent
			
			// -------------------------------------------------------------
	
	$valid_date = FALSE;
	
	for($i = 0; $i < $eventdate_count; $i++) {
		$eventdate_item = $this->entry['eventdate'][$i];
		if((int)$eventdate_item['id'] > 0) {
			$valid_date = TRUE;
			$i = 0;
		}
	}
	
	if(!$valid_date && $this->parent['eventdate']) {
		$parent_eventdate_count = count($this->parent['eventdate']);
		
		if($parent_eventdate_count > 0) {
			for($i = 0; $i < $parent_eventdate_count; $i++) {
				$parent_eventdate_item = $this->parent['eventdate'][$i];
				
				if((int)$parent_eventdate_item['id'] > 0) {
					array_unshift($this->entry['eventdate'], $this->parent['eventdate'][$i]);
					$eventdate_count++;
				}
			}
		}
	}
		
				// Child Record Counts
			
			// -------------------------------------------------------------
		
	$image_count = $this->counts['image'];
	$tag_count = $this->counts['tag'];
	$description_count = $this->counts['description'];
	$quote_count = $this->counts['quote'];
	$textbody_count = $this->counts['textbody'];
	$association_count = $this->counts['association'];
	$eventdate_count = $this->counts['eventdate'];
	$link_count = $this->counts['link'];
	$definition_count = $this->counts['definition'];
	$children_count = $this->counts['children'];
	
	$younger_sibling_count = $this->counts['younger_sibling'];
	$older_sibling_count = $this->counts['older_sibling'];
	
				// Basic Requirements
			
			// -------------------------------------------------------------

	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
		
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

				// Other Stuff
			
			// -------------------------------------------------------------
			
	if($association_count < 1) {
		if($this->parent['association'] && $this->parent['association'][0] && (int)$this->parent['association'][0]['id'] > 0) {
			$this->entry['association'] = $this->parent['association'];
			$this->SetAssociationRecords();
			$association_count = count($this->entry['association']);
		}
	}
	
				// Timeframe
			
			// -------------------------------------------------------------
		
	if($this->entry['eventdate'])
	{
		$entry_event_count = count($this->entry['eventdate']);
		for($i = 0; $i < $entry_event_count; $i++)
		{
			$entry_event = $this->entry['eventdate'][$i];
			
			if($entry_event['Title'] == 'Publication')
			{
				$publication_event = $entry_event;
			}
			
			if($publication_event)
			{
				$i = $entry_event_count;
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
		}
	}
		
				// Simple Formats
			
			// -------------------------------------------------------------
			
	if(	($this->script_format_lower == 'pdf') ||
		($this->script_format_lower == 'rtf') ||
		($this->script_format_lower == 'epub') ||
		($this->script_format_lower == 'daisy') ||
		($this->script_format_lower == 'sgml') ||
		($this->script_format_lower == 'tex') ||
		($this->script_format_lower == 'brf') ||
		($this->script_format_lower == 'html' && $this->Param('printerfriendly')) ||
		($this->script_format_lower == 'html' && $this->Param('invertedcolors'))
	)
	{	
		$html_document = '';
		$html_document .= '<h1>';
		$html_document .= $this->entry['Title'];
		$html_document .= '</h1>';
		
		$html_document .= "\n";
		
		if($this->entry['Subtitle'])
		{
			$html_document .= '<h2>';
			$html_document .= $this->entry['Subtitle'];
			$html_document .= '</h2>';
			$html_document .= "\n";
		}
		
		if($time_frame)
		{
			$html_document .= '<p>';
			$html_document .= $time_frame;
			$html_document .= '</p>';
			$html_document .= "\n";
		}
		
		if($this->entry['association'] && $association_count)
		{
			$html_document .= '<p><b>People :</b></p>';
			$html_document .= "\n";

			$associations = $this->entry['association'];
			
			for($i = 0; $i < $association_count; $i++)
			{
				$association = $associations[$i];
				$child = $association['entry'];
				
				$html_document .= '<p>';
				$html_document .= $association['SubType'];
				$html_document .= ' : ';
				$html_document .= $child['Title'];
				$html_document .= '</p>';
			}
			$html_document .= "\n";
		}
		
		if($this->entry['description'] && $description_count)
		{
			$description = $this->entry['description'][0];
			$html_document .= '<p><b>Description :</b> ';
			$html_document .= $description['Description'];
			
			if($description['Source'])
			{
				$html_document .= ' (From : ';
				$html_document .= $description['Source'];
				$html_document .= ')';
			}
			
			$html_document .= '</p>';
			$html_document .= "\n";
		}
		
		if($this->entry['tag'] && $tag_count)
		{
			$html_document .= '<p><b>Tags :</b> ';
			
			$tags = $this->entry['tag'];
			$display_tags = [];
			
			$tag_max = $tag_count;
			if($tag_max > 10) {
				$tag_max = 10;
			}
			
			for($i = 0; $i < $tag_max; $i++)
			{
				$tag = $tags[$i];
				
				$display_tags[] = $tag['Tag'];
			}
			
			$html_document .= implode(', ', $display_tags);
			
			$html_document .= '.</p>';
			$html_document .= "\n";
		}
		
		if($this->entry['quote'] && $quote_count)
		{
			$html_document .= '<p><b>Quotes :</b></p>';
			$html_document .= "\n";
			
			$quotes = $this->entry['quote'];
			
			for($i = 0; $i < $quote_count; $i++)
			{
				$quote = $quotes[$i];
				
				$html_document .= '<blockquote><i>"';
				$html_document .= str_replace('"', '\'', $quote['Quote']);
				$html_document .= '"</i>';
				
				if($quote['Source'])
				{
					$html_document .= ' (From : ';
					$html_document .= $quote['Source'];
					$html_document .= '.)';
				}
				
				$html_document .= '</blockquote>';
				$html_document .= "\n";
			}
		}
		
		if($this->entry['textbody'] && $textbody_count)
		{
			$html_document .= '<p><b>Text :</b></p>';
			$html_document .= "\n";
			
			$textbodies = $this->entry['textbody'];
			
			for($i = 0; $i < $textbody_count; $i++)
			{
				$textbody = $textbodies[$i];
				
				$text_formatted = $textbody['Text'];
				$text_formatted = preg_replace("/<img[^>]+\>/i", " ", $text_formatted); 
				
				$html_document .= $text_formatted;
				$html_document .= "\n";
				
				if($textbody['Source'])
				{
					$html_document .= '<p>From : ';
					$html_document .= $textbody['Source'];
					$html_document .= '.</p>';
					$html_document .= "\n";
				}
			}
		}
		
		if($this->entry['eventdate'] && $eventdate_count)
		{
			$event_dates = $this->entry['eventdate'];
			
			$html_document .= '<p><b>Chronology :</b></p>';
			$html_document .= "\n";
			
			for($i = 0; $i < $eventdate_count; $i++)
			{
				$event_date = $event_dates[$i];
				
				$date_epoch_time = strtotime($event_date['EventDateTime']);
				
				$html_document .= '<blockquote><b>';
				
				$html_document .= date("F d, Y", $date_epoch_time);
				$html_document .= ' :</b> ';
				
				$html_document .= $this->entry['Title'];
				$html_document .= ' -- ';
				$html_document .= $event_date['Title'];
				
				$html_document .= '.';
				$html_document .= '</blockquote>';
				
				$html_document .= "\n";
			}
		}
		
		if($this->entry['link'] && $link_count)
		{
			$links = $this->entry['link'];
			
			$html_document .= '<p><b>Links :</b></p>';
			$html_document .= "\n";
			
			
			for($i = 0; $i < $link_count; $i++)
			{
				$link = $links[$i];
				
				$html_document .= '<blockquote> &bull; <b>';
				$html_document .= $link['Title'];
				$html_document .= '</b>';
				$html_document .= '<br>';
				$html_document .= '<a href="';
				$html_document .= $link['URL'];
				$html_document .= '">';
				$html_document .= $link['URL'];
				$html_document .= '</a>';
				$html_document .= '</blockquote>';
				$html_document .= "\n";
			}
		}
		
		$html_document .= '<p>';
		
		if($this->script_format_lower == 'pdf')
		{
			$html_document .= 'PDF';
		}
		elseif($this->script_format_lower == 'html')
		{
			$html_document .= 'HTML';
		}
		
		$html_document .= ' file generated from : ';
		$html_document .= '</p>';
		$html_document .= "\n";
		
		$html_document .= '<blockquote><b>';
		$html_document .= $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/';
		$html_document .= '</b></blockquote>';
	}
	
			// TXT Format
		
		// -------------------------------------------------------------

	if($this->script_format_lower == 'txt')
	{
		$standard_indent = '     ';
		$standard_header_underscore = '----------------------------------';
		$text_document = $this->entry['Title'];
		
		if($this->entry['Subtitle'])
		{
			$text_document .= ' : ';
			$text_document .= "\n";
			$text_document .= $this->entry['Subtitle'];
		}
		
		$text_document .= "\n";
		$text_document .= $standard_header_underscore . $standard_header_underscore;
		
		$text_document .= "\n\n";
		
		if($time_frame)
		{
			$text_document .= $time_frame;
			$text_document .= "\n\n";
		}
		
		if($this->entry['association'] && $association_count)
		{
			$text_document .= 'People :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";

			$associations = $this->entry['association'];
			
			for($i = 0; $i < $association_count; $i++)
			{
				$association = $associations[$i];
				$child = $association['entry'];
				
				$text_document .= $association['SubType'];
				$text_document .= ' : ';
				$text_document .= $child['Title'];
				$text_document .= "\n\n";
			}
		}
		
		if($this->entry['description'] && $description_count)
		{
			$description = $this->entry['description'][0];
			$text_document .= "Description :";
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$text_document .= $description['Description'];
			$text_document .= "\n\n";
			
			if($description['Source'])
			{
				$text_document .= $standard_indent;
				$text_document .= 'From : ';
				$text_document .= $description['Source'];
				$text_document .= "\n\n";
			}
		}
		
		if($this->entry['tag'] && $tag_count)
		{
			$text_document .= 'Tags :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$tags = $this->entry['tag'];
			$tags_to_display = [];
			
			$tag_max = $tag_count;
			if($tag_max > 10) {
				$tag_max = 10;
			}
			
			for($i = 0; $i < $tag_max; $i++)
			{
				$tag = $tags[$i];
				$tags_to_display[] = $tag['Tag'];
			}
			
			$text_document .= implode(', ', $tags_to_display);
			
			$text_document .= '.';
		}
		
		if($this->entry['quote'] && $quote_count)
		{
			$text_document .= 'Quotes :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$quotes = $this->entry['quote'];
			
			for($i = 0; $i < $quote_count; $i++)
			{
				$quote = $quotes[$i];
				
				$text_document .= '"';
				$text_document .= str_replace('"', '\'', $quote['Quote']);
				$text_document .= '"';
				$text_document .= "\n\n";
				
				if($quote['Source'])
				{
					$text_document .= $standard_indent;
					$text_document .= 'From : ' . $quote['Source'];
					$text_document .= "\n\n";
				}
			}
		}
		
		if($this->entry['textbody'] && $textbody_count)
		{
			$text_document .= 'Text :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$text_body = $this->entry['textbody'][0];
			
			$text_document .= html_entity_decode(strip_tags($text_body['Text']));
			$text_document .= "\n\n";
			
			if($text_body['Source'])
			{
				$text_document .= $standard_indent;
				$text_document .= 'From : ' . $text_body['Source'];
				$text_document .= "\n\n";
			}
		}
		
		if($this->entry['eventdate'] && $eventdate_count)
		{
			$event_dates = $this->entry['eventdate'];
			
			$text_document .= 'Events :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			
			for($i = 0; $i < $eventdate_count; $i++)
			{
				$text_document .= "\n\n";
				$text_document .= $standard_indent;
				$text_document .= $this->entry['Title'];
				$text_document .= ' -- ';
				$event_date = $event_dates[$i];
				$text_document .= $event_date['Title'];
				$text_document .= ' : ';
				$date_epoch_time = strtotime($event_date['EventDateTime']);
				$text_document .= date("F d, Y", $date_epoch_time);
			}
			
			$text_document .= "\n\n";
		}
		
		if($this->entry['link'] && $link_count)
		{
			$links = $this->entry['link'];
			
			$text_document .= 'Links :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			
			for($i = 0; $i < $link_count; $i++)
			{
				$link = $links[$i];
				
				$text_document .= "\n\n";
				$text_document .= $standard_indent;
				$text_document .= $link['Title'];
				$text_document .= ' --';
				$text_document .= "\n";
				$text_document .= $link['URL'];
			}
			
			$text_document .= "\n\n";
		}
		
		$text_document .= 'About This Textfile :';
		$text_document .= "\n";
		$text_document .= $standard_header_underscore;
		$text_document .= "\n\n";
		
		$text_document .= $standard_indent;
		$text_document .= 'Text file generated from : ';
		$text_document .= "\n";
		$text_document .= $this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/';
		
		if($this->Param('wrapped'))
		{
			$text_document = wordwrap($text_document, 75, "\n", FALSE);
		}
		
		print($text_document);
	}
	
			// PDF Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'pdf')
	{
		$this->source_content = $html_document;
	}
	
			// RTF Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'rtf')
	{
		$this->source_content = $html_document;
	}
	
			// EPub Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'epub')
	{
		$this->source_content = $html_document;
	}
	
			// DAISY Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'daisy')
	{
		$this->source_content = $html_document;
	}
	
			// SGML Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'sgml')
	{
		$this->source_content = $html_document;
	}
	
			// XML Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'xml')
	{
		$this->source_content = $this->SetRecordToUseForMetadata();
	}
	
			// TEX Format
		
		// -------------------------------------------------------------
			
	if ($this->script_format_lower == 'tex')
	{
		$this->source_content = $html_document;
	}
	
	if ($this->script_format_lower == 'brf')
	{
		$this->source_content = $html_document;
	}
	
			// Printer-Friendly HTML Format
		
		// -------------------------------------------------------------

	if($this->script_format_lower == 'html' && $this->Param('printerfriendly'))
	{
		print('<div class="font-family-arial">');
		
		print($html_document);
		
		print('</div>');
	}
	
			// Inverted-Colors HTML Format
		
		// -------------------------------------------------------------

	elseif($this->script_format_lower == 'html' && $this->Param('invertedcolors'))
	{
		print('<div class="font-family-arial">');
		
		print($html_document);
		
		print('</div>');
	}
	
			// HTML Format
		
		// -------------------------------------------------------------
			
	elseif ($this->script_format_lower == 'html')
	{
				// Standard Requires
			
			// -------------------------------------------------------------
	
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
			'globals'=>$this->globals,
			'languageobject'=>$this->language_object,
			'divider'=>$divider,
			'domainobject'=>$this->domain_object,
		];
		$navigation = new module_navigation($navigation_args);
		
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
			'sharewithtext'=>$this->share_with_text,
			'socialmediasharelinkargs'=>[
				'url'=>$this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/?id=' . $this->entry['assignment'][0]['id'],
				'title'=>$this->header_title_text,
				'desc'=>$instructions_content_text,
				'provider'=>$this->domain_object->primary_domain_lowercased,
			],
		];
		$social_media_share_links = new module_socialmediasharelinks($social_media_share_links_args);
		
				// Display Header
			
			// -------------------------------------------------------------
		
		$entryheader->Display();
		
				// Admin Controls
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		
		if($this->canUserAccess()) {
			$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
		}
		
		$entry_controls->showNotPublishedNote(['that'=>$this]);
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center width-100percent margin-top-5px">');
		
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
		
				// Like/Dislike Buttons
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/entry-likes.php');
		$entry_likes = new module_entrylikes(['that'=>$this]);
		$entry_likes->Display();
		
				// Comments Link-Box
			
			// -------------------------------------------------------------
		
		$entry_comments->DisplayLinkBox();
		
				// Image Link-Box
			
			// -------------------------------------------------------------
		
#		$entry_image->DisplayLinkBox();
		
				// Quote Link-Box
			
			// -------------------------------------------------------------
		
		$entry_quotes->DisplayLinkBox();
		
				// Permalink
			
			// -------------------------------------------------------------
		
		$entry_share->DisplayPermalink();
		
				// Share Links
			
			// -------------------------------------------------------------
		
		$entry_share->DisplaySmall();
		
				// End Top Bar
			
			// -------------------------------------------------------------
		
		print('</div>');
		
				// Finish Breadcrumb Trails
			
			// -------------------------------------------------------------
		
		print('<div class="clear-float"></div>');
		
				// Display Associations
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-association.php');
		$entry_association = new module_entryassociation(['that'=>$this, 'header'=>'']);
		$entry_association->Display(['max'=>1]);
		
				// Display Description
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-description.php');
		$entry_description = new module_entrydescription(['that'=>$this, 'header'=>'']);
		$entry_description->Display();
		
				// Display Quotes
			
			// -------------------------------------------------------------
			
		$entry_quotes->Display(['max'=>1, 'header'=>'']);
		
		print('<center>');
		if($this->entry['image'] && $this->entry['image'][0]) {
			$entry_location = $this->entry['image'][0]['FileDirectory'];
			
			$image = '/image/';
			$image .= implode('/', str_split($entry_location)) . '/';
			$image .= $this->entry['image'][0]['FileName'];
			
			print('<img src="' . $image . '" style="max-width:1000px;">');
		} elseif($this->entry['link']) {
			$page_url = $this->entry['link'][0]['URL'];
			$image_url = $this->entry['link'][1]['URL'];
			$image_url = str_replace('_m.', '_b.', $image_url);
			print('<a data-flickr-embed="true" data-header="true" data-footer="true" href="' . $page_url . '"><img src="' . $image_url . '" style="max-width:800px;max-height:1200px;"></a><script async src="//embedr.flickr.com/assets/client-code.js" charset="utf-8"></script>');
		}
		print('</center>');
		
				// Display Textbody
			
			// -------------------------------------------------------------
		
	#	ggreq('modules/html/entry-textbody.php');
	#	$entry_textbody = new module_entrytextbody(['that'=>$this, 'subheader'=>$this->entry['Title']]);
	#	$entry_textbody->Display();
		
	#	if(($this->entry['textbody'] && strlen($this->entry['textbody'][0]['Text']) > 0) || ($this->counts['children'] > 0)) {
	#		$entry_association->Display([]);
	#	}
		
				// Display Event Dates
			
			// -------------------------------------------------------------
		
	#	$entrydate->DisplayEventDatesHistory();
		
				// Display Image Gallery
			
			// -------------------------------------------------------------
		
	#	$entry_image->Display(['header'=>'Image Gallery of ' . $this->entry['Title']]);
		
				// Display Links
			
			// -------------------------------------------------------------
		
	#	ggreq('modules/html/entry-link.php');
	#	$entry_link = new module_entrylink(['that'=>$this]);
	#	$entry_link->Display([]);
		
				// Comments Header
			
			// -------------------------------------------------------------
			
		$entry_comments->Display();
			
				// View Selected Record Tags
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-tag.php');
		$entry_tag = new module_entrytag(['that'=>$this]);
		$entry_tag->Display([]);
		
						// Display Navigation
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-navigation.php');
		$entry_navigation = new module_entrynavigation(['that'=>$this]);
		$entry_navigation->DisplaySiblingNavigation([]);
		
				// DEBUG
			
			// -------------------------------------------------------------
			
	#	ggreq('modules/html/entry-debug.php');
	#	$entry_debug = new module_entrydebug(['that'=>$this]);
	#	$entry_debug->Debug([]);
		
				// Display Final Ending Navigation
			
			// -------------------------------------------------------------
		
		$bottom_navigation_args = [
			'thispage'=>'',
		];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
		
		//print("<script type='text/javascript'>
		//		$(window).load( function() {
		//			responsiveVoice.speak(\"$message\", \"UK English Female\");
		//		});
		//</script>");
	}
?>
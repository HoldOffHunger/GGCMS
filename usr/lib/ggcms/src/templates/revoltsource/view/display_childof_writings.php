<?php
		
				// Basics
			
			// -------------------------------------------------------------
	
	ggreq('modules/spacing.php');
	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
	ggreq('modules/html/entry-image.php');
	$entry_image = new module_entryimage(['that'=>$this]);
	
	ggreq('modules/html/entry-quote.php');
	$entry_quotes = new module_entryquotes(['that'=>$this]);
		
				// Timeframe
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-date.php');
	$entrydate = new module_entrydate(['that'=>$this]);
	$time_data = $entrydate->getSimpleData();
	
	ggreq('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this, 'entrydate'=>$entrydate]);
	
	ggreq('modules/html/entry-associated.php');
	$entry_associated = new module_entryassociated(['that'=>$this, 'header'=>'Works', 'entrysort'=>$entrysort, 'entrylist'=>$entrylist, 'iframe'=>'always']);
		
				// Header_REAL
			
			// -------------------------------------------------------------
			
	ggreq('modules/html/entry-header.php');
	$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_data['text']]);
		
				// Comments
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/entry-comments.php');
	$entry_comments = new module_entrycomments(['that'=>$this]);
		
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
		
		$html_document .= $entrydate->getSimpleDisplay_HTML();
		
		if($this->entry['description'] && $this->counts['description'])
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
		
		if($this->entry['tag'] && $this->counts['tag'])
		{
			$html_document .= '<p><b>Tags :</b> ';
			
			$tags = $this->entry['tag'];
			$display_tags = [];
			
			$tag_max = $this->counts['tag'];
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
		
		if($this->entry['quote'] && $this->counts['quote'])
		{
			$html_document .= '<p><b>Quotes :</b></p>';
			$html_document .= "\n";
			
			$quotes = $this->entry['quote'];
			
			for($i = 0; $i < $this->counts['quote']; $i++)
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
		
		if($this->entry['textbody'] && $this->counts['textbody'])
		{
			$html_document .= '<p><b>Biography :</b></p>';
			$html_document .= "\n";
			
			$textbodies = $this->entry['textbody'];
			
			for($i = 0; $i < $this->counts['textbody']; $i++)
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
		
		if($this->entry['associated'] && $this->counts['associated'])
		{
			$html_document .= '<p><b>Works :</b></p>';
			$html_document .= "\n";
			
			$associateds = $this->entry['associated'];
			
			for($i = 0; $i < $this->counts['associated']; $i++)
			{
				$associated = $associateds[$i];
				
				$full_datetime = $associated['entry']['eventdate'][0]['EventDateTime'];
				$datetime_pieces = explode(' ', $full_datetime);
				$date = $datetime_pieces[0];
				$date_epoch_time = strtotime($date);
				
				$html_document .= '<p>' . $associated['SubType'] . ' of ' . $associated['entry']['Title'] . ' (' . date("F d, Y", $date_epoch_time) . ')</p>';
			}
		}
		
		if($this->entry['eventdate'] && $this->counts['eventdate'])
		{
			$event_dates = $this->entry['eventdate'];
			
			$html_document .= '<p><b>Chronology :</b></p>';
			$html_document .= "\n";
			
			for($i = 0; $i < $this->counts['eventdate']; $i++)
			{
				$event_date = $event_dates[$i];
				
				$date_epoch_time = strtotime($event_date['EventDateTime']);
				
				$html_document .= '<blockquote><b>';
				
				$html_document .= date("F d, Y", $date_epoch_time);
				$html_document .= ' :</b> ';
				
				$html_document .= $this->entry['Title'];
				$html_document .= '\'s ';
				$html_document .= $event_date['Title'];
				
				$html_document .= '.';
				$html_document .= '</blockquote>';
				
				$html_document .= "\n";
			}
		}
		
		if($this->entry['link'] && $this->counts['link'])
		{
			$links = $this->entry['link'];
			
			$html_document .= '<p><b>Links :</b></p>';
			$html_document .= "\n";
			
			for($i = 0; $i < $this->counts['link']; $i++)
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
		
		$text_document .= $entrydate->getSimpleDisplay_TXT();
		
		if($this->entry['description'] && $this->counts['description'])
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
		
		if($this->entry['tag'] && $this->counts['tag'])
		{
			$text_document .= 'Tags :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$tags = $this->entry['tag'];
			$tags_to_display = [];
			
			$tag_max = $this->counts['tag'];
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
			$text_document .= "\n\n";
		}
		
		if($this->entry['quote'] && $this->counts['quote'])
		{
			$text_document .= 'Quotes :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$quotes = $this->entry['quote'];
			
			for($i = 0; $i < $this->counts['quote']; $i++)
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
		
		if($this->entry['textbody'] && $this->counts['textbody'])
		{
			$text_document .= 'Biography :';
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
		
		if($this->entry['associated'] && $this->counts['associated'])
		{
			$text_document .= 'Works :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$associateds = $this->entry['associated'];
			
			for($i = 0; $i < $this->counts['associated']; $i++)
			{
				$associated = $associateds[$i];
				
				$full_datetime = $associated['entry']['eventdate'][0]['EventDateTime'];
				$datetime_pieces = explode(' ', $full_datetime);
				$date = $datetime_pieces[0];
				$date_epoch_time = strtotime($date);
				
				$text_document .= '' . $associated['SubType'] . ' of ' . $associated['entry']['Title'] . ' (' . date("F d, Y", $date_epoch_time) . ')' . "\n\n";
			}
		}
		
		if($this->entry['eventdate'] && $this->counts['eventdate'])
		{
			$event_dates = $this->entry['eventdate'];
			
			$text_document .= 'Events :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			
			for($i = 0; $i < $this->counts['eventdate']; $i++)
			{
				$text_document .= "\n\n";
				$text_document .= $standard_indent;
				$text_document .= $this->entry['Title'];
				$text_document .= '\'s ';
				$event_date = $event_dates[$i];
				$text_document .= $event_date['Title'];
				$text_document .= ' : ';
				$date_epoch_time = strtotime($event_date['EventDateTime']);
				$text_document .= date("F d, Y", $date_epoch_time);
			}
			
			$text_document .= "\n\n";
		}
		
		if($this->entry['link'] && $this->counts['link'])
		{
			$links = $this->entry['link'];
			
			$text_document .= 'Links :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			
			for($i = 0; $i < $this->counts['link']; $i++)
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
		

	
			// HTML Format
		
		// -------------------------------------------------------------
		
	} elseif ($this->script_format_lower == 'html') {
				// Standard Requires
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/navigation.php');
		$navigation_args = [
			'globals'=>$this->handler->globals,
			'languageobject'=>$this->language_object,
			'domainobject'=>$this->domain_object,
		];
		$navigation = new module_navigation($navigation_args);
		
				// Share Package_REAL
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-share.php');
		$entry_share = new module_entryshare(['that'=>$this]);
		
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
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
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
		
		$entry_image->DisplayLinkBox();
		
				// Quote Link-Box
			
			// -------------------------------------------------------------
		
		$entry_quotes->DisplayLinkBox();
		
				// Works Link-Box
			
			// -------------------------------------------------------------
		
		$entry_associated->DisplayLinkBox();
		
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
		
				// View Navigation
			
			// -------------------------------------------------------------
		
	#	ggreq('modules/html/entry-navigation.php');
	#	$entry_navigation = new module_entrynavigation(['that'=>$this]);
		
	#	$entry_navigation->Display();
		
				// View Images
			
			// -------------------------------------------------------------
		
	#	$entry_image->Display();
		
				// View Times
			
			// -------------------------------------------------------------
		
	#	$entrydate->Display();
		
				// Finish Date and Images
			
			// -------------------------------------------------------------
		
		print('<div class="clear-float"></div>');
		
				// Display Associations
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-association.php');
		$entry_association = new module_entryassociation(['that'=>$this, 'header'=>'']);
		$entry_association->Display([]);
		
				// Display Description
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-description.php');
		$entry_description = new module_entrydescription(['that'=>$this, 'header'=>'']);
		$entry_description->Display();
		
				// Display Quotes
			
			// -------------------------------------------------------------
		
		# more quotes at the end! (link)
		
		$entry_quotes->Display(['max'=>1, 'header'=>'']);
		
				// Display Textbody
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-textbody.php');
		$entry_textbody = new module_entrytextbody(['that'=>$this, 'header'=>'', 'subheader'=>'About ' . $this->entry['Title']]);
		$entry_textbody->Display();
		
				// Display Quotes
			
			// -------------------------------------------------------------
		
		$entry_associated->Display([
			'header'=>'Quotes from ' . $this->entry['Title'] . ', by ' . $this->entry['association'][0]['entry']['Title'],
			'ignore_parent'=>'writings',
			'hide_stats'=>true,
			'anchor'=>'author-quotes',
			'parents'=>2,
			'stats_prefix'=>'This writing has ',
			'item_title'=>'quotes',
			'list_author'=>true,
		]);
		
				// Display Image Gallery
			
			// -------------------------------------------------------------
		
		$entry_image->Display(['header'=>'Image Gallery of ' . $this->entry['Title']]);
		
				// Display All Quotes
			
			// -------------------------------------------------------------
		
		$entry_quotes->Display(['header'=>'Quotes by ' . $this->entry['Title']]);
		
				// Display Definitions
			
			// -------------------------------------------------------------
		
	#	ggreq('modules/html/entry-definition.php');
	#	$entry_definition = new module_entrydefinition(['that'=>$this]);
	#	$entry_definition->Display();
		
				// Display Event Dates
			
			// -------------------------------------------------------------
		
		$entrydate->DisplayEventDatesHistory();
		
				// Display Share Links
			
			// -------------------------------------------------------------
			
	#	$entry_share->Display();
		
				// Display Links
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/entry-link.php');
		$entry_link = new module_entrylink(['that'=>$this]);
		$entry_link->Display([]);
		
				
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
		
		$navigation->DisplayBottomNavigation(['thispage'=>'']);
	}
?>
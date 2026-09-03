<?php
	
			// Format-Universal Formatting
		
		// -------------------------------------------------------------

	ggreqonce('modules/spacing.php');

	ggreqonce('modules/html/entry-comments.php');
	$entry_comments = new module_entrycomments(['that'=>$this]);

	ggreqonce('modules/html/entry-image.php');
	$entry_image = new module_entryimage(['that'=>$this]);
	
	ggreqonce('modules/html/entry-quote.php');
	$entry_quotes = new module_entryquotes(['that'=>$this]);
	
	ggreqonce('modules/html/entry-share.php');
	$entry_share = new module_entryshare(['that'=>$this]);
		
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
	
	ggreqonce('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
		
				// Timeframe
			
			// -------------------------------------------------------------
	
	ggreqonce('modules/html/entry-date.php');
	$entrydate = new module_entrydate(['that'=>$this]);
	$time_data = $entrydate->getSimpleData();
	$time_frame = $time_data['text'];
	
	ggreqonce('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this, 'entrydate'=>$entrydate]);
	
				// Header_REAL
			
			// -------------------------------------------------------------
	
	ggreqonce('modules/html/entry-header.php');
	$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_frame]);
	
				// Simple Formats
			
			// -------------------------------------------------------------
			
	if(	($this->script_format_lower == 'pdf') ||
		($this->script_format_lower == 'rtf') ||
		($this->script_format_lower == 'epub') ||
		($this->script_format_lower == 'daisy') ||
		($this->script_format_lower == 'sgml') ||
		($this->script_format_lower == 'tex') ||
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
		
		if($this->children && $children_count) {
			$this->SetFullChildRecords();
			$children_count = count($this->children);
			
			if($children_count) {
				$html_document .= '<p><b>Sections (TOC) :</b></p>';
				$html_document .= "\n";
				
				$child_display = $entrysort->Sort(['entries'=>$this->children]);
				
				foreach($child_display as $child) {
					$html_document .= '    <p>&bull; ' . $child['Title'];
					
					if($child['Subtitle']) {
						$html_document .= ' : ' . $child['Subtitle'];
					}
					
					if($child['textbody']) {
						$html_document .= '<br>';
						$html_document .= "\n";
						$html_document .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						$html_document .= number_format($child['textbody'][0]['WordCount']) . ' Words; ';
						$html_document .= number_format($child['textbody'][0]['CharacterCount']) . ' Characters';
					}
					
					$html_document .= "</p>";
				}
				
				$html_document .= '<p><b>Sections (Content) :</b></p>';
				$html_document .= "\n";
				
				foreach($child_display as $child) {
					$html_document .= '<p>&bull; ' . $child['Title'] . "";
					
					if($child['Subtitle'])
					{
						$html_document .= ' : ' . $child['Subtitle'];
					}
					
					$html_document .= '</p>';
					
					$html_document .= $this->formatImageText([
						'text'=>$child['textbody'][0]['Text'],
						'images'=>$child['image'],
					]);
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
		
		$text_document .= $entrydate->getSimpleDisplay_TXT();
		
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
			$text_document .= "\n\n";
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
		
		if($this->entry['textbody'] && $textbody_count) {
			$text_document .= 'Text :';
			$text_document .= "\n";
			$text_document .= $standard_header_underscore;
			$text_document .= "\n\n";
			
			$text_body = $this->entry['textbody'][0];
			
			$text_document .= html_entity_decode(strip_tags($text_body['Text']));
			$text_document .= "\n\n";
			
			if($text_body['Source']) {
				$text_document .= $standard_indent;
				$text_document .= 'From : ' . $text_body['Source'];
				$text_document .= "\n\n";
			}
		}
		
		if($this->children && $children_count) {
			$this->SetFullChildRecords();
			$children_count = count($this->children);
			
			if($children_count) {
				$text_document .= 'Sections (TOC) :';
				$text_document .= "\n";
				$text_document .= $standard_header_underscore;
				$text_document .= "\n\n";
				
				$child_display = $entrysort->Sort(['entries'=>$this->children]);
				
				foreach($child_display as $child) {
					$text_document .= '    * ' . $child['Title'];
					
					if($child['Subtitle']) {
						$text_document .= ' : ' . $child['Subtitle'];
					}
					
					if($child['textbody']) {
						$text_document .= "\n";
						$text_document .= '        ';
						$text_document .= number_format($child['textbody'][0]['WordCount']) . ' Words; ';
						$text_document .= number_format($child['textbody'][0]['CharacterCount']) . ' Characters';
					}
					
					$text_document .= "\n\n";
				}
				
				$text_document .= 'Sections (Content) :';
				$text_document .= "\n";
				$text_document .= $standard_header_underscore;
				$text_document .= "\n\n";
				
				foreach($child_display as $child)
				{
					$text_document .= '* ' . $child['Title'];
					
					if($child['Subtitle'])
					{
						$text_document .= ' : ' . $child['Subtitle'];
					}
					
					$text_document .= "\n\n";
					
					$text_document .= strip_tags($child['textbody'][0]['Text']);
					
					$text_document .= "\n\n";
				}
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
	
		ggreqonce('modules/html/text.php');
		$text = new module_text;
		
		ggreqonce('modules/html/form.php');
		$form = new module_form;
		
		ggreqonce('modules/html/divider.php');
		$divider = new module_divider;
		
		ggreqonce('modules/html/table.php');
		$table = new module_table;
		
		ggreqonce('modules/html/list/generic.php');
		$generic_list = new module_genericlist;
		
		ggreqonce('modules/html/header.php');
		$header = new module_header;
		
		ggreqonce('modules/html/navigation.php');
		$navigation_args = [
			'globals'=>$this->handler->globals,
			'languageobject'=>$this->language_object,
			'divider'=>$divider,
			'domainobject'=>$this->domain_object,
		];
		$navigation = new module_navigation($navigation_args);
		
				// Display Header
			
			// -------------------------------------------------------------
		
		$entryheader->Display();
		
				// Admin Controls
			
			// -------------------------------------------------------------
		
		if($this->authentication_object->user_session['UserAdmin.id']) {
			ggreqonce('modules/html/entry-controls.php');
			$entry_controls = new module_entrycontrols;
			$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
		}
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center width-100percent margin-top-5px">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		ggreqonce('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'subpage'=>'News Feed Documentation']);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreqonce('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();
		
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
		
		ggreqonce('modules/html/entry-association.php');
		$entry_association = new module_entryassociation(['that'=>$this, 'header'=>'']);
		$entry_association->Display([]);
		
				// Display Description
			
			// -------------------------------------------------------------
		
#		ggreqonce('modules/html/entry-description.php');
#		$entry_description = new module_entrydescription(['that'=>$this, 'header'=>'']);
#		$entry_description->Display();
		
				// Display Quotes
			
			// -------------------------------------------------------------
			
		$entry_quotes->Display(['max'=>1, 'header'=>'']);
		
		
				// Display Textbody
			
			// -------------------------------------------------------------
		
		$html = '';
		
		$html .= '<h3>Intro to the News Feed</h3>';
		
		$html .= '<p>Welcome to the newsfeed for RevoltLib!</p>';
		
		$html .= '<p>Information for the newest additions to ';
		
		if($this->master_record['id'] !== $this->entry['id']) {
			$html .= $this->master_record['Title'] . ' ' . $this->master_record['ChildNoun'] . ' for ';
		}
		
		$html .= '<a href="' . $this->formatted_news_feed['entryurl'] . '">';
		$html .= $this->entry['Title'];
		$html .= '</a>';
		$html .= ' : ';
		$html .= $this->entry['description'][0]['Description'] . '</p>';
		
		$html .= '<p>Join the newsfeed with this URL for RSS:</p>';
		
		$html .= '<blockquote><code>' . $this->formatted_news_feed['url'] . '</code></blockquote>';
		
		$html .= '<p>Join the newsfeed with this URL for Atom:</p>';
		
		$html .= '<blockquote><code>' . $this->formatted_news_feed['atomlink'] . '</code></blockquote>';
		
		$html .= '<h3>Alternate Formats</h3>';
		
		$html .= '<p>We also support alternate formats for your newsfeed needs:</p>';
		
		$html .= '<blockquote>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 0.91';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=0.91</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 0.92';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=0.92</code>';
		
		$html .= '</blockquote>';
		
		$html .= '<h3>Set Custom List Amounts</h3>';
		
		$html .= '<p>You can also customize the amount of items you want the feed to return.  The max is 1,000.  This also works with the alternate versions....</p>';
		
		$html .= '<blockquote>';
		
		$html .= '<strong>';
		$html .= '5 Max Items';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?items=5</code>';
		$html .= '<br>';
		$html .= '<code>' . $this->formatted_news_feed['atomlink'] . '?items=5</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= '55 Max Items';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?items=55</code>';
		$html .= '<br>';
		$html .= '<code>' . $this->formatted_news_feed['atomlink'] . '?items=55</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= '555 Max Items';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?items=555</code>';
		$html .= '<br>';
		$html .= '<code>' . $this->formatted_news_feed['atomlink'] . '?items=555</code>';
		
		$html .= '</blockquote>';
		
		$html .= '<p>The alternate newsfeeds with limits would be in the following format:</p>';
		
		$html .= '<blockquote>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 0.91, 100 Items';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=0.91&items=100</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 0.92, 200 Items';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=0.92&items=200</code>';
		
		$html .= '</blockquote>';
		
		
		
		$html .= '<h3>Developer-Mode</h3>';
		
		$html .= '<p>If you want a developer-view mode of the RSS feed, you can do that with a "humanreadable" parameter:</p>';
		
		$html .= '<blockquote>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 0.91, Human-Readable';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=0.91&humanreadable=1</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 0.92, Human-Readable';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=0.92&humanreadable=1</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= 'RSS Feed, Version 2.00, Human-Readable';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['url'] . '?version=2.00&humanreadable=1</code>';
		$html .= '<br><br>';
		
		$html .= '<strong>';
		$html .= 'Atom Feed, Version 1.00, Human-Readable';
		$html .= '</strong>';
		$html .= '<br>';
		
		$html .= '<code>' . $this->formatted_news_feed['atomlink'] . '?humanreadable=1</code>';
		
		$html .= '</blockquote>';
		
		$html .= '<h3>Master List of News Feeds</h3>';
		
		$html .= '<p>If you are interested in more feeds, check out the ones below:</p>';
		
		$html .= '<blockquote>';
		
		for($i = 0; $i < count($this->formatted_news_feeds); $i++) {
			$formatted_news_feed = $this->formatted_news_feeds[$i];
			
			$html .= '<b>';
			$html .= '<a href="' . $formatted_news_feed['entryurl'] . '">';
			$html .= implode(' ' . $this->arrowRightSVG() . ' ', $formatted_news_feed['titles']);
			$html .= '</a>';
			$html .= ' &mdash; ';
			
			$html .= '</b>';
			
			$html .= '<a href="' . $formatted_news_feed['docsurl'] . '">';
			$html .= '(click here for NewsFeed Documentation)';
			$html .= '</a>';
			
			$html .= '<br>';
			
			$html .= 'RSS Feed URL: <code>' . $formatted_news_feed['url'] . '</code>';
			$html .= '<br>';
			
			$html .= 'Atom Feed URL: <code>' . $formatted_news_feed['atomlink'] . '</code>';
			$html .= '<br>';
			
			if(($i + 1) < count($this->formatted_news_feeds)) {
				$html .= '<br>';
			}
		}
		$html .= '</blockquote>';
		
	#	print("<PRE>");
	#	print_r($this->formatted_news_feed);
	#	print_r($this->entry);
	#	print_r($this->formatted_news_feeds);
	#	print("</PRE>");
		
		ggreqonce('modules/html/entry-textbody.php');
		$entry_textbody = new module_entrytextbody([
			'that'=>$this,
			'subheader'=>'About the News Feed',
			'noalts'=>TRUE,
		]);
		
		$entry_textbody->DisplayCustomContent([
			'textbody'=>[
				'Text'=>$html,
			],
		]);
		
				// DEBUG
			
			// -------------------------------------------------------------
			
	#	ggreqonce('modules/html/entry-debug.php');
	#	$entry_debug = new module_entrydebug(['that'=>$this]);
	#	$entry_debug->Debug([]);
		
				// Display Final Ending Navigation
			
			// -------------------------------------------------------------
		
		$bottom_navigation_args = [
			'thispage'=>'Feeds',
		];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
	}
?>
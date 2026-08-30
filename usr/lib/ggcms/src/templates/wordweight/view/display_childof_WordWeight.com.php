<?php
	ggreq('modules/spacing.php');
	
	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);
	
			// Format-Universal Formatting
		
		// -------------------------------------------------------------
		
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
	
			// Standard Requires
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/socialmediasharelinks.php');
	$social_media_share_links_args = [
		'globals'=>$this->handler->globals,
		'textonly'=>$this->mobile_friendly,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
		'socialmedia'=>$this->social_media,
		'sharewithtext'=>$this->share_with_text,
		'socialmediasharelinkargs'=>[
			'url'=>$this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/' . $this->word . '/',
			'title'=>$this->header_title_text,
			'desc'=>$instructions_content_text,
			'provider'=>$this->domain_object->primary_domain_lowercased,
		],
	];
	$social_media_share_links = new module_socialmediasharelinks($social_media_share_links_args);
	
			// Display Header
		
		// -------------------------------------------------------------
		
	ggreq('modules/html/entry-header.php');
	$entryheader = new module_entryheader(['that'=>$this, 'time_frame'=>$time_frame]);
	
	$entryheader->Display();
	
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
	$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>$this->word]);
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
	
			// View Navigation
		
		// -------------------------------------------------------------
	
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	
	print('<ul type="1" class="margin-5px font-family-arial">');
	
	print('<li><a href="#definitions">Definitions of ' . $this->word . '</a></li>');
	
	print('<li><a href="#search">Search</a></li>');
	
	print('<li><a href="#random">Random Words</a></li>');
	
	print('<li><a href="#similar">Similar Sites</a></li>');
	
	print('<li><a href="#share">Share</a></li>');
	
	print('</ul>');
	
	print('</div>');
	
			// Finish Date and Images
		
		// -------------------------------------------------------------
							
	print('<div class="clear-float"></div>');
	
			// Display Share Links
		
		// -------------------------------------------------------------
?>


<div style="width:80%;border:1px solid black;margin:auto;">
<b>
<a target="_blank" href="https://www.amazon.com/b?_encoding=UTF8&tag=autonomoushol-20&linkCode=ur2&linkId=c27292bca406306f004ecdf93d388b1f&camp=1789&creative=9325&node=8975347011">Find Books on Learning, Teaching, and Education!</a>

<div class="clear-float"></div>
</div>

<?PHP		
				// Share Links Header
			
			// -------------------------------------------------------------
			
	print('<a name="definitions"></a>');
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial">');
	print('Definitions of ');
	print($this->word);
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
	
	print('<div class="clear-float"></div>');
	
			// See the Definition
			// -----------------------------------------------
	
	print('<div class="horizontal-center width-90percent">');
	
	$definitions_count = count($this->definitions);
	for($i = 0; $i < $definitions_count; $i++) {
		$definition = $this->definitions[$i];
		print('<div class="border-2px background-color-gray15 margin-5px horizontal-left">');
		
		print('<div class="span-header-3"><p style="margin:5px;padding:5px;border:black 2px solid;background-color:#FFFFFF;" class="header-3 padding-0px margin-5px horizontal-left font-family-tahoma"><span>');
		
		if($definition['Pronunciation']) {
			print('<strong>Pronunciation : </strong>' . $definition['Pronunciation']);
			print('<br>');
		}
		
		if($definition['PartOfSpeech']) {
			print('<strong>Part of Speech : </strong>' . $definition['PartOfSpeech']);
			print('<br>');
		}
		
		if($definition['Etymology']) {
			print('<strong>Etymology : </strong>' . $definition['Etymology']);
			print('<br>');
		}
		
		if($definition['Definition']) {
			print('<strong>Definition : </strong>' . str_replace("\n", "<BR>\n", $definition['Definition']));
			print('<br>');
		}
		
		if($definition['DictionaryTitle']) {
			print('<strong>Source : </strong>' .  $definition['DictionaryTitle']);
		}
		
		print('</span></p></div>');
		print('</div>');
	}
	
	print('</div>');

?>



<div style="width:80%;border:1px solid black;margin:auto;">
<b>
<a href="https://amzn.to/4bVKLkP"><img src="/image/dictionary.jpg" width="100" style="float:left;> <span style="font-size:150%;">Merriam-Webster's Everyday Language Reference Set: Includes: The Merriam-Webster Dictionary, The Merriam-Webster Thesaurus, and The Merriam-Webster Vocabulary Builder</span><br><br></b>

An attractive, affordable boxed reference set featuring best-selling references to help build vocabulary and improve language skills. The boxed set includes:<br><br>

&bull; The Merriam-Webster Dictionary ― over 75,000 definitions for the words you need today<br>
&bull; The Merriam-Webster Thesaurus ― over 150,000 word choices, plus usage guidance<br>
&bull; Merriam-Webster’s Vocabulary Builder ― learn 3,200 words with quizzes and root words―perfect for test prep!


</a>
</b>
<div class="clear-float"></div>
</div>

<?PHP
	
			// Display Share Links
		
		// -------------------------------------------------------------
			
				// Share Links Header
			
			// -------------------------------------------------------------
			
	print('<a name="search"></a>');
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial">');
	print('Search');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
		
				// Finish Share Links Header
			
			// -------------------------------------------------------------
	
	print('<div class="clear-float"></div>');
	
	print('<center>');
	
	

	print('<form class="margin-0px" method="post" action="http://www.wordweight.com/">');
	
	print('<div class="border-2px background-color-gray15 margin-5px horizontal-left width-50percent">');
	print('<div style="margin:5px;padding:5px;border:black 2px solid;background-color:#FFFFFF;" class="header-3 padding-0px margin-5px horizontal-left font-family-tahoma">');
	
	print('<center>');
	
	if($this->search_term) {
		print("Sorry, no results for " . $this->search_term . ".  Please try again!");
		print('<BR><BR>');
	}
	
	print('Search : <input type="text" name="search" size="60">');
	
	print('<br><br>');
	
	print('<input type="submit" value="Lookup Definition">');
	print('</center>');
	
	print('</div>');
	print('</div>');
	
	print('</form>');
	
	
	print('</center>');
	
			// Display Random Words
		
		// -------------------------------------------------------------
			
				// Share Links Header
			
			// -------------------------------------------------------------
			
	print('<a name="random"></a>');
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial">');
	print('Random Words');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
		
				// Finish Share Links Header
			
			// -------------------------------------------------------------
								
	print('<div class="clear-float"></div>');

	print('<center>');
	print('<div class="border-2px background-color-gray15 margin-5px horizontal-left width-70percent">');
	print('<div style="margin:5px;padding:5px;border:black 2px solid;background-color:#FFFFFF;" class="header-3 padding-0px margin-5px horizontal-left font-family-tahoma">');
	
	print('<center>');
	print('<div class="border-2px background-color-gray13 margin-5px" style="display: inline-block;">');
	print('<div class="margin-5px">');
	print('<center><h3 class="margin-0px">Some Random Definitions!</h3></center>');
	print('</div>');
	print('</div>');
	print('</center>');
	
	print('<br>');
	
	$random_definitions = $this->dictionary->LookUpRandomWords([]);
	
	foreach ($random_definitions as $random_word => $random_definition) {
		print('<div id="header_backgroundimageurl" class="border-2px background-color-gray13 margin-5px" style="display: inline-block;">');
		print('<div class="margin-5px">');
		print('<a href="/' . urlencode(ucwords($random_word)) . '/">');
		print(ucwords($random_word));
		print('</a>');
		print('</div>');
		print('</div>');
	}
	
	print('</div>');
	print('</div>');
	print('</center>');
	
			// Display Similar Sites
		
		// -------------------------------------------------------------
			
				// Satellites Header
			
			// -------------------------------------------------------------
			
	print('<a name="similar"></a>');
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial">');
	print('Similar Sites');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
	print('<div class="clear-float"></div>');
			
				// Show Satellites
			
			// -------------------------------------------------------------
	
	ggreq('modules/html/similarsites-satellites.php');
	
	$similar_site_args = [
		'site'=>$this->domain_object->primary_domain_lowercased,
		'language'=>$this->language_object,
	];
	$similar_sites = new module_similarsites_satellites($similar_site_args);
	
	$similar_sites->display();
	
			// Display Share Links
		
		// -------------------------------------------------------------
			
				// Share Links Header
			
			// -------------------------------------------------------------
			
	print('<a name="share"></a>');
	
	print('<center>');
	print('<div class="horizontal-center width-95percent">');
	print('<div class="border-2px background-color-gray15 margin-5px float-left">');
	print('<h2 class="horizontal-left margin-5px font-family-arial">');
	print('Share');
	print('</h2>');
	print('</div>');
	print('</div>');
	print('</center>');
		
				// Finish Share Links Header
			
			// -------------------------------------------------------------
								
	print('<div class="clear-float"></div>');
		
				// Start Display Share Options
			
			// -------------------------------------------------------------
	
	print('<center>');
	print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
	print('<div class="border-2px background-color-gray15 margin-5px horizontal-left font-family-arial">');
	print('<div class="margin-5px horizontal-left font-family-arial">');
	
				// Display "Share" Text
			
			// -------------------------------------------------------------
	
	print('<div class="float-left border-2px margin-5px background-color-gray13">');
	print('<div class="margin-5px">');
	print('<strong>Permalink for Sharing :</strong>');
	print('</div>');
	print('</div>');
		
				// Finish "Share" Text
			
			// -------------------------------------------------------------
								
	print('<div class="clear-float"></div>');
		
				// Display Permalink
			
			// -------------------------------------------------------------
	
	print('<center>');
	print('<div class="margin-5px horizontal-center width-90percent">');
	print('<div class="margin-5px border-2px background-color-gray13 float-left">');
	print('<div class="margin-5px horizontal-left font-family-arial float-left">');
	print('<input class="select-input-contents" type="text" size="100" value="');
	print($this->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]));
	print('/');
	print($this->word);
	print('/');
	print('">');
	print('</div>');
	
	print('<div class="clear-float"></div>');
	
	print('</div>');
	
	print('<div class="clear-float"></div>');
	print('</div>');
	print('</center>');
	
				// Display Social Networking Options
			
			// -------------------------------------------------------------
	
	$social_media_share_links->display();
	
				// End Display Share Options
			
			// -------------------------------------------------------------
			
	print('</div>');
	print('</div>');
	print('</div>');
	print('</center>');
	
			// DEBUG
		
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
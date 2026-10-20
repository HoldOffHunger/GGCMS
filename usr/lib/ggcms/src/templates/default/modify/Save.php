<?php
	print('<div style="font-family:arial, tahoma;">');
		
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
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>(!empty($this->saveattemptresults) ? 'Saved!' : 'Save result') . ' &mdash; ' . $this->entry['Title'],
		'sub_text'=>'',
	]);
	
	$entryindexheader->Display();
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center margin-top-5px" style="width:100%;">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs([
			'that'=>$this,
			'subpage'=>!empty($this->saveattemptresults) ? 'Saved Entry' : 'Save Result',
		]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this, 'noredirect'=>TRUE]);
		$auth->Display();
		
		print('<div class="clear-float"></div>');
	
			// Display Parent Record Info
		
		// -------------------------------------------------------------
	
	print('<center><div style="width:70%;">');
	print('<table style="width:100%;" class="border-2px background-color-gray13 margin-top-5px">');

	print('<tbody><tr class="border-1px horizontal-left">');

	print('<td class="border-1px vertical-top">');

	print('<nobr>Adding Entry To:</nobr>');

	print('</td>');

	print('<td class="border-1px width-100percent vertical-top">');

	print($this->GetHyperlinkedEntryView([
		'entry'=>$this->parent,
		'entrylist'=>$this->record_list,
	]));

	print('</td>');

	print('</tr>');

	print('<tr class="border-1px horizontal-left">');

	print('<td class="border-1px vertical-top">');

	print('<nbr>Status:');

	print('</nbr></td>');

	print('<td class="border-1px width-100percent vertical-top">');
	
	print($this->save_status);
	
	print('</td>');

	print('</tr>');

	print('</tbody></table>');
	print('</div></center>');
	
			// Display Errors
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-errors.php');
	$errors = new module_modifyerrors(['that'=>$this, 'unpadded'=>TRUE]);
	$errors->Display();
	
			// Display Form Elements : Start
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = array(
		'class'=>'horizontal-center width-80percent margin-top-5px border-2px',
	);
	
	$divider->displaystart($divider_padding_start_args);
	
			// Display Form Save Results
		
		// -------------------------------------------------------------
	
	if($this->saveattemptresults)
	{
		ggreq('modules/html/modify-results.php');
		$results = new module_modifyresults(['that'=>$this]);
		
		$results->Display();
	} else {
	
			// Re-Display Form
		
		// -------------------------------------------------------------
		
		$start_form_args = [
			'action'=>0,
			'method'=>'post',
			'files'=>1,
			'formclass'=>'margin-0px',
		];
		
		$form->StartForm($start_form_args);
		
				// Display Form Elements : Standard Elements
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/modify-fields.php');
		$fields = new module_modifyfields([
			'that'=>$this,
			'plainlabels'=>TRUE,
			'noassociationtypes'=>TRUE,
			'fields'=>[
				'Title'=>[
					'noamericanize'=>TRUE,
				],
				'Subtitle'=>[
					'noamericanize'=>TRUE,
				],
				'List Title'=>[
					'noamericanize'=>TRUE,
					'nolisttitlecase'=>TRUE,
				],
				'List Title Sort Key'=>[],
				'Code'=>[],
				'Child Adjective'=>[],
				'Child Noun'=>[],
				'Child Noun Plural'=>[],
				'Grand Child Adjective'=>[],
				'Grand Child Noun'=>[],
				'Grand Child Noun Plural'=>[],
				'Entry Translation'=>[
					'subtitlerows'=>['value', 'blank', 'hidden'],
					'sortkeylabels'=>['blank'=>'List Title Sort Key: '],
				],
				'Availability Dates'=>[],
				'Description'=>[
					'hiddenlanguagelabel'=>'Language : ',
				],
				'Quote'=>[
					'hiddenlanguagelabel'=>'Language : ',
					'plainblank'=>TRUE,
				],
				'Text Body'=>[
					'nooptions'=>TRUE,
					'textmaxlength'=>TRUE,
				],
				'Image'=>[
					'nothumbnails'=>TRUE,
					'noindexing'=>TRUE,
				],
				'Image Translation'=>[],
				'Tag'=>[
					'notagclass'=>TRUE,
					'nolanguagedefault'=>TRUE,
					'hiddenlanguagename'=>'tag_Language[]',
				],
				'Link'=>[
					'urlonly'=>TRUE,
					'languagefromurl'=>TRUE,
					'hiddentitlename'=>'link_Title-Hidden[]',
				],
				'Event Date'=>[
					'bydate'=>TRUE,
					'dividerbreaks'=>TRUE,
					'approximatefromlast'=>TRUE,
				],
				'Association'=>[
					'followtags'=>TRUE,
					'rawidrows'=>['hidden'],
					'noblanktypes'=>TRUE,
				],
				'Publish'=>[
					'alwayschecked'=>TRUE,
				],
				'Save'=>[
					'save'=>TRUE,
				],
			],
		]);
		
		$fields->Display();
	}
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$end_form_args = [
	];
	$form->EndForm($end_form_args);
	
	$divider_end_args = [
	];
	$divider->displayend($divider_end_args);
	
	$bottom_navigation_args = [
		'thispage'=>'Save',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
	print('</div>');
	
?>
<?php
	print('<div style="font-family:arial, tahoma;">');
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
	ggreq('modules/html/text.php');
	
	ggreq('modules/html/form.php');
	$form = new module_form;
	
	ggreq('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/html/table.php');
	
	ggreq('modules/html/list/generic.php');
	
	ggreq('modules/html/header.php');
	
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
	
	ggreq('modules/html/modify-save-status.php');
	$save_status = new module_modifysavestatus(['that'=>$this, 'adding'=>TRUE]);
	$save_status->Display();
	
			// Display Errors
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-errors.php');
	$errors = new module_modifyerrors(['that'=>$this, 'unpadded'=>TRUE]);
	$errors->Display();
	
			// Display Form Elements
		
		// -------------------------------------------------------------
	
	$divider->displaystart(['class'=>'horizontal-center width-80percent margin-top-5px border-2px']);
	
			// what was written, when it was; the form again, when it was not
	if($this->saveattemptresults) {
		ggreq('modules/html/modify-results.php');
		$results = new module_modifyresults(['that'=>$this]);
		$results->Display();
	} else {
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
		
		$form->StartForm([
			'action'=>0,
			'method'=>'post',
			'files'=>1,
			'formclass'=>'margin-0px',
		]);
		
		$fields->Display();
	}
	
			// Display Form Elements : End
		
		// -------------------------------------------------------------
	
	$form->EndForm([]);
	$divider->displayend([]);
	
	$navigation->DisplayBottomNavigation(['thispage'=>'Save']);
	
	print('</div>');
	
?>
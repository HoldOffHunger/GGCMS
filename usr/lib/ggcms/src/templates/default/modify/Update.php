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
		'main_text'=>(!empty($this->saveattemptresults) ? 'Updated!' : 'Update result') . ' &mdash; ' . $this->entry['Title'],
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
			'subpage'=>!empty($this->saveattemptresults) ? 'Updated Entry' : 'Update Result',
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
	$save_status = new module_modifysavestatus(['that'=>$this]);
	$save_status->Display();
	
			// Display Master Record Instructions
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/modify-master-record.php');
	$master_record = new module_modifymasterrecord(['that'=>$this, 'unpadded'=>TRUE]);
	$master_record->Display();
	
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
		$results = new module_modifyresults([
			'that'=>$this,
			'associationtypes'=>TRUE,
			'imagetranslations'=>TRUE,
			'eventdescriptions'=>TRUE,
		]);
		$results->Display();
	} else {
		ggreq('modules/html/modify-fields.php');
		$fields = new module_modifyfields([
			'that'=>$this,
			'plainlabels'=>TRUE,
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
				'Entry Translation'=>[
					'plaintitlelabel'=>TRUE,
					'subtitlerows'=>['value', 'hidden'],
					'sortkeylabels'=>['blank'=>'List Title Sort Key: ', 'hidden'=>'List Title Sort Key: '],
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
					'balancedblank'=>TRUE,
				],
				'Image'=>[
					'nothumbnails'=>TRUE,
					'noindexing'=>TRUE,
					'plaintitlelabel'=>TRUE,
				],
				'Tag'=>[
					'notagclass'=>TRUE,
				],
				'Link'=>[
					'urlonly'=>TRUE,
					'nolanguage'=>TRUE,
					'plaintitlelabel'=>TRUE,
					'hiddentitlename'=>'link_Title[]',
				],
				'Event Date'=>[
					'bydate'=>TRUE,
					'dividerbreaks'=>TRUE,
					'approximatefromlast'=>TRUE,
					'plaintitlelabel'=>TRUE,
				],
				'Association'=>[
					'followtags'=>TRUE,
					'rawidrows'=>['value', 'blank', 'hidden'],
					'noblanktypes'=>TRUE,
				],
				'Publish'=>[
					'unclosedchecked'=>TRUE,
				],
				'Save'=>[
					'nolabel'=>TRUE,
					'alwaysdelete'=>TRUE,
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
	
	$navigation->DisplayBottomNavigation(['thispage'=>'Update']);
	
	print('</div>');
	
?>
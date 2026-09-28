<?php
	if($this->Param('headless')) {
		print("<script type='text/javascript'>");
		print("
			function inIframe () {
				try {
					return window.self !== window.top;
				} catch (e) {
					return true;
				}
			}

			if(!inIframe()) {
				document.location = 'view.php#children';
			}
		");
		print("</script>");
	}

			// Standard Requires

		// -------------------------------------------------------------

	ggreq('modules/spacing.php');

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

	ggreq('modules/html/navigation.php');
	$navigation = new module_navigation([
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	]);

	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);

	ggreq('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this]);

	ggreq('modules/html/entry-list-navigation.php');
	$entrylistnavigation = new module_entrylistnavigation(['that'=>$this]);

	ggreq('modules/html/browse-bar.php');
	$browse_bar = new module_browsebar(['that'=>$this]);

	$breadcrumbs_title = 'Browsing';

	if($this->entry['ChildAdjective']) {
		$breadcrumbs_title .= ' ' . $this->entry['ChildAdjective'];
	}

	if($this->entry['ChildNounPlural']) {
		$breadcrumbs_title .= ' ' . $this->entry['ChildNounPlural'];
	}

	$this->header_title_text .= ' &mdash; ' . $breadcrumbs_title;

			// Display Header

		// -------------------------------------------------------------

	if(!$this->Param('headless')) {
		$entryheader->Display();
	}

			// Admin Controls

		// -------------------------------------------------------------

	if(!$this->Param('headless') && $this->authentication_object->user_session['UserAdmin.id']) {
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}

			// Breadcrumbs

		// -------------------------------------------------------------

	if(!$this->Param('headless')) {
		print('<div class="page-tools">');

		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>$breadcrumbs_title]);
		$breadcrumbs->Display();

		print('</div>');
	}

			// The Entries, a Page at a Time

		// -------------------------------------------------------------

	$browse_bar->Display();

	$entrylistnavigation->Display([]);

	print('<div class="block page-wide">');
	$entrylist->Display(['children'=>$entrysort->Sort(['entries'=>$this->children])]);
	print('</div>');

	$entrylistnavigation->Display([]);

			// Display Final Ending Navigation

		// -------------------------------------------------------------

	if(!$this->Param('headless')) {
		$navigation->DisplayBottomNavigation(['thispage'=>'']);
	}

?>

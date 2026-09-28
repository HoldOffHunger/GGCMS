<?php

			// Standard Requires

		// -------------------------------------------------------------

	ggreq('modules/spacing.php');

	ggreq('modules/html/navigation.php');
	$navigation = new module_navigation([
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	]);

	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);

	ggreq('modules/html/entry-date.php');
	$entrydate = new module_entrydate(['that'=>$this]);

	ggreq('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this, 'entrydate'=>$entrydate]);

	ggreq('modules/html/entry-list-navigation.php');
	$entrylistnavigation = new module_entrylistnavigation(['that'=>$this]);

			// Header: the tag, and how many carry it

		// -------------------------------------------------------------

	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/tag-header.php');
	$tag_header = new module_tagheader(['that'=>$this]);
	$tag_header->Display();

			// Admin Controls

		// -------------------------------------------------------------

	if($this->authentication_object->user_session['UserAdmin.id']) {
		ggreq('modules/html/entry-controls.php');
		$entry_controls = new module_entrycontrols;
		$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);
	}

			// Breadcrumbs

		// -------------------------------------------------------------

	print('<div class="page-tools">');

	ggreq('modules/html/breadcrumbs.php');
	$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>'Tagged &ldquo;' . $this->tag_cleansed . '&rdquo;']);
	$breadcrumbs->Display();

	print('</div>');

			// The Word in the Dictionaries

		// -------------------------------------------------------------

	$tag_header->DisplayDefinitions();

			// The Entries, a Page at a Time

		// -------------------------------------------------------------

	ggreq('modules/html/browse-bar.php');
	$browse_bar = new module_browsebar(['that'=>$this]);
	$browse_bar->Display();

	$entrylistnavigation->Display([]);

	print('<div class="block page-wide">');
	$entrylist->Display(['children'=>$entrysort->Sort(['entries'=>$this->children])]);
	print('</div>');

	$entrylistnavigation->Display([]);

			// Display Final Ending Navigation

		// -------------------------------------------------------------

	$navigation->DisplayBottomNavigation(['thispage'=>'']);

?>

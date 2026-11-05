<?php

		/*
			The 3rd Parties panel's links, as module_hideable lists them.
			Required by module_mastercpanel::Menu(), so $this is the panel.
		*/

			// Column #2, 3rd Party : List Args
		
		// -------------------------------------------------------------

	return [
		[
			'text'=>'Primary Host Management',
			'link'=>' ',
			'mouseover'=>'Primary Host Management',
			[
				'text'=>'Clone Primary Host Database',
				'link'=>'dbstatus.php?action=ClonePrimaryHostDatabase',
				'mouseover'=>'Clone Primary Host Database',
			],
			[
				'text'=>'Update Primary Host Information',
				'link'=>$this->that->domain_object->GetPrimaryDomain(['www'=>1, 'lowercase'=>1]) . '/modify.php?action=Edit',
				'mouseover'=>'Update Primary Host Information',
			],
			[
				'text'=>'View All Primary Hosts',
				'link'=>'dbstatus.php?action=ViewAllPrimaryHosts',
				'mouseover'=>'Clone Primary Host Database',
			],
			[
				'text'=>'View Primary Host Status',
				'link'=>'dbstatus.php?action=ViewPrimaryHostStatus',
				'mouseover'=>'View Primary Host Status',
			],
			[
				'text'=>'View Primary Host SEO Status',
				'link'=>'dbstatus.php?action=ViewPrimaryHostSEOStatus',
				'mouseover'=>'View All Primary Host SEO Status',
			],
		],
		[
			'text'=>'Vendor Management',
			'link'=>' ',
			'mouseover'=>'Vendor Management',
			[
				'text'=>'View All Vendors',
				'link'=>'dbstatus.php?action=ViewAllVendors',
				'mouseover'=>'View All Vendors',
			],
			[
				'text'=>'Generate Search Engine Sitemap-Submission Links',
				'link'=>'dbstatus.php?action=GenerateSearchEngineSitemapSubmissionLinks',
				'mouseover'=>'Generate Search Engine Sitemap-Submission Links',
			],
		],
	];

?>

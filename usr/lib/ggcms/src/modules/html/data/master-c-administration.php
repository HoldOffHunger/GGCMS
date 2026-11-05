<?php

		/*
			The Admin panel's links, as module_hideable lists them.
			Required by module_mastercpanel::Menu(), so $this is the panel.
		*/

			// Column #2, Administration : List Args
		
		// -------------------------------------------------------------

	return [
		[
			'text'=>'Administration',
			'link'=>' ',
			'mouseover'=>'Administration',
			[
				'text'=>'View All Domains',
				'link'=>'admin.php?action=ViewAllDomains',
				'mouseover'=>'View All Domains',
			],
		],
		[
			'text'=>'War Room',
			'link'=>' ',
			'mouseover'=>'No Fighting!',
			[
				'text'=>'This Host',
				'link'=>' ',
				'mouseover'=>'No Fighting!',
				[
					'text'=>'This Host',
					'link'=>'warroom.php?host=' . $this->that->domain_object->host,
					'mouseover'=>'View War Room for This Host',
				],
				[
					'text'=>'This Host - 100 Most Recent',
					'link'=>'warroom.php?limit=100&host=' . $this->that->domain_object->host,
					'mouseover'=>'View War Room for This Host',
				],
				[
					'text'=>'This Host - 1,000 Most Recent',
					'link'=>'warroom.php?limit=1000&host=' . $this->that->domain_object->host,
					'mouseover'=>'View War Room for This Host',
				],
			],
			[
				'text'=>'All Hosts',
				'link'=>' ',
				'mouseover'=>'No Fighting!',
				[
					'text'=>'All Hosts',
					'link'=>'warroom.php',
					'mouseover'=>'View War Room for All Hosts',
				],
				[
					'text'=>'All Hosts - 100 Most Recent',
					'link'=>'warroom.php?limit=100',
					'mouseover'=>'View War Room for All Hosts',
				],
				[
					'text'=>'All Hosts - 1,000 Most Recent',
					'link'=>'warroom.php?limit=1000',
					'mouseover'=>'View War Room for All Hosts',
				],
			],
		],
	];

?>

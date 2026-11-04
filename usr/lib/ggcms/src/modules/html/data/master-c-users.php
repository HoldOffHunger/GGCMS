<?php

		/*
			The Users panel's links, as module_hideable lists them.
			Required by module_mastercpanel::Menu(), so $this is the panel.
		*/

			// Column #2, Users : List Args
		
		// -------------------------------------------------------------

	return [
		[
			'text'=>'User Management',
			'link'=>' ',
			'mouseover'=>'User Management',
			[
				'text'=>'View All Users',
				'link'=>'userstatus.php?action=ViewAllUsers',
				'mouseover'=>'View All Users',
			],
		],
		[
			'text'=>'Comments',
			'link'=>' ',
			'mouseover'=>'Comment System',
			[
				'text'=>'Review Pending Comments',
				'link'=>'userstatus.php?action=ReviewComments',
				'mouseover'=>'Review Pending Comments',
			],
			[
				'text'=>'Review Rejected Comments',
				'link'=>'userstatus.php?action=ReviewRejectedComments',
				'mouseover'=>'Review Rejected Comments',
			],
			[
				'text'=>'Review Accepted Comments',
				'link'=>'userstatus.php?action=ReviewAcceptedComments',
				'mouseover'=>'Review Accepted Comments',
			],
		],
		[
			'text'=>'Suggestions',
			'link'=>' ',
			'mouseover'=>'Suggestion System',
			[
				'text'=>'Review Pending Suggestions',
				'link'=>'userstatus.php?action=ReviewSuggestions',
				'mouseover'=>'Review Pending Suggestions',
			],
			[
				'text'=>'Review Rejected Suggestions',
				'link'=>'userstatus.php?action=ReviewRejectedSuggestions',
				'mouseover'=>'Review Rejected Suggestions',
			],
			[
				'text'=>'Review Accepted Suggestions',
				'link'=>'userstatus.php?action=ReviewAcceptedSuggestions',
				'mouseover'=>'Review Accepted Suggestions',
			],
		],
		[
			'text'=>'Site Statistics',
			'link'=>' ',
			'mouseover'=>'Site Statistics',
			[
				'text'=>'Page Statistics',
				'link'=>'userstatus.php?action=PageStatistics',
				'mouseover'=>'Page Statistics',
			],
		],
	];

?>

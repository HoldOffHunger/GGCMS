<?php

		/*
			The Entries panel's links, as module_hideable lists them.
			Required by module_mastercpanel::Menu(), so $this is the panel.
		*/

			// Column #1, Tools : List Args
		
		// -------------------------------------------------------------

	return [
		[
			'text'=>'Modify Entries',
			'link'=>' ',
			'mouseover'=>'Modify Entries',
			[
				'text'=>'Add Entry',
				'link'=>'modify.php?action=Add',
				'mouseover'=>'Add an Entry',
			],
			[
				'text'=>'Import Entries',
				'link'=>'import.php',
				'mouseover'=>'Import Entries',
			],
		],
		[
			'text'=>'Select Entries',
			'link'=>' ',
			'mouseover'=>'Select Entries',
			[
				'text'=>'Select Entry by id',
				'link'=>'view.php?action=Select&by=id',
				'mouseover'=>'Select Entry by Code',
			],
			[
				'text'=>'Select Entry by Title',
				'link'=>'view.php?action=Select&by=Title',
				'mouseover'=>'Select Entry by Code',
			],
			[
				'text'=>'Select Entry by Code',
				'link'=>'view.php?action=Select&by=Code',
				'mouseover'=>'Select Entry by Code',
			],
			[
				'text'=>'Select Entry by Description',
				'link'=>'view.php?action=Select&by=Description',
				'mouseover'=>'Select Entry by Description',
			],
			[
				'text'=>'Select Entry by Quote',
				'link'=>'view.php?action=Select&by=Quote',
				'mouseover'=>'Select Entry by Quote',
			],
			[
				'text'=>'Select Entry by Link',
				'link'=>'view.php?action=Select&by=Link',
				'mouseover'=>'Select Entry by Link',
			],
			[
				'text'=>'Select Entry by TextBody',
				'link'=>'view.php?action=Select&by=TextBody',
				'mouseover'=>'Select Entry by Text Body',
			],
			[
				'text'=>'Select Entry by Tag',
				'link'=>'view.php?action=Select&by=Tag',
				'mouseover'=>'Select Entry by Tag',
			],
			[
				'text'=>'Select Entry by AvailabilityStart',
				'link'=>'view.php?action=Select&by=AvailabilityStart',
				'mouseover'=>'Select Entry by Availability Start',
			],
			[
				'text'=>'Select Entry by AvailabilityEnd',
				'link'=>'view.php?action=Select&by=AvailabilityEnd',
				'mouseover'=>'Select Entry by Availability End',
			],
			[
				'text'=>'Select Entry by Level',
				'link'=>'view.php?action=Select&by=Level',
				'mouseover'=>'Select Entry by Level',
			],
		],
		[
			'text'=>'Cleanup Entries',
			'link'=>' ',
			'mouseover'=>'Cleanup Entries',
			[
				'text'=>'Get Cleanup DB MySQL',
				'link'=>'dbstatus.php?action=GetCleanupDBMySQL',
				'mouseover'=>'Add an Entry',
			],
		],
		[
			'text'=>'Detect Entry Issues',
			'link'=>' ',
			'mouseover'=>'Detect Entry Issues',
			[
				'text'=>'Entry Issues',
				'link'=>' ',
				'mouseover'=>'Detect Entry Issues',
				[
					'text'=>'Entries Missing List Titles',
					'link'=>'dbstatus.php?action=EntriesMissingListTitles',
					'mouseover'=>'Check Entries for Missing List Titles',
				],
				[
					'text'=>'Entries Missing List Titles Sort Keys',
					'link'=>'dbstatus.php?action=EntriesMissingListTitleSortKeys',
					'mouseover'=>'Check Entries for Missing List Title Sort Keys',
				],
				[
					'text'=>'Entries Missing Child Adjectives',
					'link'=>'dbstatus.php?action=EntriesMissingChildAdjectives',
					'mouseover'=>'Check Entries for Missing Child Adjectives',
				],
				[
					'text'=>'Entries Missing Child Nouns',
					'link'=>'dbstatus.php?action=EntriesMissingChildNouns',
					'mouseover'=>'Check Entries for Missing Child Nouns',
				],
				[
					'text'=>'Entries Missing Child Nouns Plural',
					'link'=>'dbstatus.php?action=EntriesMissingChildNounsPlura',
					'mouseover'=>'Check Entries for Missing Child Nouns Plura',
				],
			],
			[
				'text'=>'Text Issues',
				'link'=>' ',
				'mouseover'=>'Detect Text Issues',
				[
					'text'=>'Detect Bad Text',
					'link'=>'dbstatus.php?action=DetectBadText',
					'mouseover'=>'Detect Bad Text',
				],
			],
		],
		[
			'text'=>'Detect Entry Errors',
			'link'=>' ',
			'mouseover'=>'Detect Entry Errors',
			[
				'text'=>'Entries',
				'link'=>' ',
				'mouseover'=>'Detect Entry Errors',
				[
					'text'=>'Entry Symmetry Check',
					'link'=>'dbstatus.php?action=EntrySymmetryCheck',
					'mouseover'=>'Check Entry Symmetry',
				],
				[
					'text'=>'Entry Code Length Check',
					'link'=>'dbstatus.php?action=DetectEntryCodeLength',
					'mouseover'=>'Check Entry Code Length',
				],
			],
			[
				'text'=>'TextBodies',
				'link'=>' ',
				'mouseover'=>'Detect TextBody Errors',
				[
					'text'=>'Detect Missing TextBodies',
					'link'=>'dbstatus.php?action=DetectMissingTextBodies',
					'mouseover'=>'Detect Missing TextBodies',
				],
			],
			[
				'text'=>'Descriptions',
				'link'=>' ',
				'mouseover'=>'Detect Description Errors',
				[
					'text'=>'Detect Missing Descriptions',
					'link'=>'dbstatus.php?action=DetectMissingDescriptions',
					'mouseover'=>'Detect Missing Descriptions',
				],
			],
			[
				'text'=>'Quotes',
				'link'=>' ',
				'mouseover'=>'Detect Quote Errors',
				[
					'text'=>'Detect Missing Quotes',
					'link'=>'dbstatus.php?action=DetectMissingQuotes',
					'mouseover'=>'Detect Missing Quotes',
				],
			],
			[
				'text'=>'Images',
				'link'=>' ',
				'mouseover'=>'Detect Image Errors',
				[
					'text'=>'Detect Blank Image Fields',
					'link'=>'dbstatus.php?action=DetectBlankImageFields',
					'mouseover'=>'Detect Blank Image Fields',
				],
				[
					'text'=>'Detect Missing Image Files',
					'link'=>'dbstatus.php?action=DetectMissingImageFiles',
					'mouseover'=>'Detect Missing Image Files',
				],
				[
					'text'=>'Detect Missing Images',
					'link'=>'dbstatus.php?action=DetectMissingImages',
					'mouseover'=>'Detect Missing Images',
				],
			],
			[
				'text'=>'Dates',
				'link'=>' ',
				'mouseover'=>'Detect Date Errors',
				[
					'text'=>'Detect Missing Dates',
					'link'=>'dbstatus.php?action=DetectMissingDates',
					'mouseover'=>'Detect Missing Dates',
				],
			],
			[
				'text'=>'Tags',
				'link'=>' ',
				'mouseover'=>'Detect Tag Errors',
				[
					'text'=>'Detect Missing Tags',
					'link'=>'dbstatus.php?action=DetectMissingTags',
					'mouseover'=>'Detect Missing Tags',
				],
			],
			[
				'text'=>'Associations',
				'link'=>' ',
				'mouseover'=>'Detect Association Errors',
				[
					'text'=>'Detect Missing Associations',
					'link'=>'dbstatus.php?action=DetectMissingAssociations',
					'mouseover'=>'Detect Missing Associations',
				],
			],
			[
				'text'=>'Links',
				'link'=>' ',
				'mouseover'=>'Detect Link Errors',
				[
					'text'=>'Detect White Space Chars in URL\'s',
					'link'=>'dbstatus.php?action=DetectWhiteSpaceURLs',
					'mouseover'=>'Check White Space Link Errors',
				],
				[
					'text'=>'Detect "*" Chars in Archive URL\'s',
					'link'=>'dbstatus.php?action=ArchiveURLDetect',
					'mouseover'=>'Check Archive Link Errors',
				],
				[
					'text'=>'Detect !"*" Chars in Archives URL\'s',
					'link'=>'dbstatus.php?action=ArchivesURLDetect',
					'mouseover'=>'Check Archives Link Errors',
				],
				[
					'text'=>'Detect " ~ " Sequences in URL\'s',
					'link'=>'dbstatus.php?action=CustomDelimitedDBSDetect',
					'mouseover'=>'Check Sequence Link Errors',
				],
				[
					'text'=>'Detect Deactivated Site Links in URL\'s',
					'link'=>'dbstatus.php?action=DeactivatedSiteDetect',
					'mouseover'=>'Detect Deactivated Site Links (myspace, yahoo, etc.)',
				],
				[
					'text'=>'Detect Copy/Paste Issues in URL\'s',
					'link'=>'dbstatus.php?action=CopyPasteIssuesURLsDetect',
					'mouseover'=>'Detect Copy/Paste Issues',
				],
				[
					'text'=>'Detect Missing Links',
					'link'=>'dbstatus.php?action=DetectMissingLinks',
					'mouseover'=>'Detect Missing Links',
				],
			],
		],
		[
			'text'=>'Fix Entry Errors',
			'link'=>' ',
			'mouseover'=>'Fix Entry Errors',
			[
				'text'=>'Entries',
				'link'=>' ',
				'mouseover'=>'Fix Entry Errors',
				[
					'text'=>'Detect and Fix Blank List Titles',
					'link'=>'dbstatus.php?action=DetectAndFixBlankListTitles',
					'mouseover'=>'Fix Entry Errors',
				],
				[
					'text'=>'Detect and Fix British Spellings',
					'link'=>'dbstatus.php?action=DetectAndFixBritishSpellings',
					'mouseover'=>'Fix British Spellings',
				],
			],
			[
				'text'=>'Images',
				'link'=>' ',
				'mouseover'=>'Fix Image Errors',
				[
					'text'=>'Fix Blank Image Fields',
					'link'=>'dbstatus.php?action=FixBlankImageFields',
					'mouseover'=>'Fix Blank Image Fields',
				],
				[
					'text'=>'Fix Missing Images',
					'link'=>'dbstatus.php?action=FixMissingImages',
					'mouseover'=>'Fix Missing Images',
				],
			],
		],
		[
			'text'=>'Modify Lists',
			'link'=>' ',
			'mouseover'=>'Modify Lists',
			[
				'text'=>'Create List',
				'link'=>'dbstatus.php?action=CreateList',
				'mouseover'=>'Create List',
			],
			[
				'text'=>'Import List',
				'link'=>'dbstatus.php?action=ImportList',
				'mouseover'=>'Import List',
			],
			[
				'text'=>'View All Lists',
				'link'=>'dbstatus.php?action=ViewAllLists',
				'mouseover'=>'View All List Names',
			],
			[
				'text'=>'View All Lists with Items',
				'link'=>'dbstatus.php?action=ViewAllListsAndItems',
				'mouseover'=>'View All Lists and Items',
			],
		],
	];

?>

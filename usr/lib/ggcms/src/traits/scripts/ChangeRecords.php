<?php

	/*
		How an entry and its child records are changed: a conveyor belt,
		one for every field of every table.

		A request's values go on at one end and come off at the other as
		rows in the database, and every stage between does one job to every
		table's records in turn:

			Read      ChangeRecordsRead       take each field off the request
			Cleanse   ChangeRecordsCleanse    cut each record to its table's fields
			Validate  ChangeRecordsValidate   check every value; stop on any failure
			Prepare   ChangeRecordsPrepare    make each value what it will be stored as
			Backup    ChangeRecordsBackup     file every old value in RecordChange
			Save      ChangeRecordsSave       write each table, the entry first
			Format    ChangeRecordsFormat     make the saved records fit to show
			Remove    ChangeRecordsRemove     delete what the request no longer carries

		Each stage keeps what it was handed before it changes anything --
		$this->entry_unset, $this->entry_original, $this->textbody_unprepared
		and the rest -- so when a value comes off the belt wrong, every stage
		it passed through can still be asked what it looked like there.

		The belt is run by ChangeRecordsVerbs: SaveEntry(), UpdateEntry() and
		DeleteEntryWithChildRecords().  ChangeRecordsVocabulary names every
		table's fields the way a request carries them.

		It was all modify.php until November 2026 -- among the first scripts
		GGCMS had, because a CMS is for changing records, and the one path
		into the database that cleanses, validates, encodes a date before
		the year 1000 and backs up what it replaces.  Anything that wrote
		entries without it got none of that; EarthFluent's courses were made
		by a macro and have no change history at all.  So it is here now,
		and two scripts ride the same belt: modify.php, for the one entry a
		browser posts, and importer.php, for a file of them.  Both read
		through Param(), so to the belt a row of an import and a form posted
		from the Edit page are the same thing.

		A class using this trait beside SimpleORM must choose this trait's
		updateFileName() and startsWith(), which modify.php always had:

			use SimpleORM, ChangeRecords {
				ChangeRecords::updateFileName insteadof SimpleORM;
				ChangeRecords::startsWith insteadof SimpleORM;
			}
	*/

	ggreq('traits/scripts/ChangeRecords/ChangeRecordsVerbs.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsVocabulary.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsRead.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsCleanse.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsValidate.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsPrepare.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsBackup.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsSave.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsFormat.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsRemove.php');
	ggreq('traits/scripts/ChangeRecords/ChangeRecordsPageCache.php');

	trait ChangeRecords {
		use ChangeRecordsVerbs;
		use ChangeRecordsVocabulary;
		use ChangeRecordsRead;
		use ChangeRecordsCleanse;
		use ChangeRecordsValidate;
		use ChangeRecordsPrepare;
		use ChangeRecordsBackup;
		use ChangeRecordsSave;
		use ChangeRecordsFormat;
		use ChangeRecordsRemove;
		use ChangeRecordsPageCache;
		
			//  What no stage assigns by name, but the scripts and templates read.
		
		public $last_unpublished_add;
		public $last_unpublished_edit;
		public $association_types;
		public $association_config_to_use;
		public $entry_modification_stop_before_save;
	}

?>

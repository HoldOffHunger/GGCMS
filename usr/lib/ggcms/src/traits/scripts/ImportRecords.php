<?php

	/*
		An importer of records: a file of rows, each put through the same
		belt modify.php puts a posted form through -- see ChangeRecords.php.

		Its own stages:

			Headers  ImportRecordsHeaders  which column is which field of which table
			Plan     ImportRecordsPlan     rows gathered into the entries they belong to
			Belt     ImportRecordsBelt     each entry ridden through ChangeRecords
			Images   ImportRecordsImages   a zip's files handed to the belt as uploads
			Run      ImportRecordsRun      the whole of it, and the report of every row

		Designed for Confex, before GGCMS, and first written in Perl.  The
		manual a person reads is importer.php's page; this is the one a
		programmer reads.
	*/

	ggreq('classes/Import/ImportFile.php');
	ggreq('traits/scripts/ImportRecords/ImportRecordsHeaders.php');
	ggreq('traits/scripts/ImportRecords/ImportRecordsPlan.php');
	ggreq('traits/scripts/ImportRecords/ImportRecordsBelt.php');
	ggreq('traits/scripts/ImportRecords/ImportRecordsImages.php');
	ggreq('traits/scripts/ImportRecords/ImportRecordsRun.php');

	trait ImportRecords {
		use ImportRecordsHeaders;
		use ImportRecordsPlan;
		use ImportRecordsBelt;
		use ImportRecordsImages;
		use ImportRecordsRun;
	}

?>

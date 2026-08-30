<?php
	use PHPUnit\Framework\TestCase;

	require('/var/www/ggcms_install_directories.php');
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/MySQL/DBAccess/GetRecordDescription_QueryDB.php');
	
	class GetRecordDescription_QueryDBTest extends TestCase {
		use GetRecordDescription_QueryDBTrait;
	}
?>
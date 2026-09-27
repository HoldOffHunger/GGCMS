<?php
	use PHPUnit\Framework\TestCase;

	require('/var/www/ggcms_install_directories.php');
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/MySQL/DBFileCache/ArrayInsert.php');
	
	require(GGCMS_DIR . 'classes/Database/DBFileCache.php');
	class insertElementIntoSortedArray extends TestCase {
		function __construct() {
			parent::__construct();
			
			$this->db_file_cache = new DBFileCache(['handler'=>NULL,]);
		}
		
		use ArrayInsert;
	}
?>
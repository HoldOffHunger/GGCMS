<?php
	use PHPUnit\Framework\TestCase;

	require('/var/www/ggcms_install_directories.php');
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/MySQL/DBFileCache/ArrayContains.php');
	
	require(GGCMS_DIR . 'classes/Database/DBFileCache.php');
	class findElementInSortedArray extends TestCase {
		public $db_file_cache;
		
		function __construct() {
			parent::__construct();
			
			$this->db_file_cache = new DBFileCache(['handler'=>NULL,]);
		}
		
		use ArrayContains;
	}
?>
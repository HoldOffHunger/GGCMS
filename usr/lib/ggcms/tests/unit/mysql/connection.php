<?php
	use PHPUnit\Framework\TestCase;
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/MySQL/Connection.php');
	
	class ConnectionTest extends TestCase {
		use ConnectionTrait;
	}
?>
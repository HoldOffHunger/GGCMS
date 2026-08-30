<?php
	use PHPUnit\Framework\TestCase;
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/Demo/String.php');
	
	class StringTest extends TestCase {
		use StringTrait;
	}
?>
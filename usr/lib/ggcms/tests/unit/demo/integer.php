<?php
	use PHPUnit\Framework\TestCase;
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/Demo/Integer.php');
	
	class IntegerTest extends TestCase {
		use IntegerTrait;
	}
?>
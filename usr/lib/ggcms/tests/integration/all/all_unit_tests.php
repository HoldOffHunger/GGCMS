<?php
	use PHPUnit\Framework\TestCase;
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/MySQL/Connection.php');
	
	testreq('traits/Demo/Integer.php');
	testreq('traits/Demo/String.php');
	testreq('traits/Demo/Truth.php');
	
	class TrueTest extends TestCase {
		use ConnectionTrait;
		use IntegerTrait;
		use StringTrait;
		use TruthTrait;
	}
?>
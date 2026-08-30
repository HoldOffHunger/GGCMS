<?php
	use PHPUnit\Framework\TestCase;
	
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/Demo/Truth.php');
	
	class TrueTest extends TestCase {
		use TruthTrait;
	}
?>
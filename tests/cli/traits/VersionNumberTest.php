<?php

	require_once(GGCMS_CLI_DIR . 'traits/VersionNumber.php');

	class VersionNumberTestSubject {
		use VersionNumber;
	}

	class VersionNumberTest extends GGCMSTestCase {
		public function testValidateVersionNumber() {
			$subject = new VersionNumberTestSubject();

			foreach(['8', '8.4', '8.4.19', '1.00'] as $version) {
				$this->assertTrue($subject->validateVersionNumber(['string'=>$version]), $version);
			}

			foreach(['', '8.', '8..4', '8.4-dev', 'v8'] as $version) {
				$this->assertFalse($subject->validateVersionNumber(['string'=>$version]), $version);
			}
		}
	}

?>

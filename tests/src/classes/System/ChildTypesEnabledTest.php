<?php

		/*
			Deploy lays the private repository's etc/ggcms/ over this one's,
			so its clonefrom/child_types/enabled.php is the class every site's
			override extends.  On 10 November 2026 AcademicReference_enabled()
			was added here and not there, and every site but EarthFluent, whose
			override had its own, 500'd on every uncached page for two
			minutes: the ORM asks each switch by name.  Every switch this
			default defines, the private one must too.
		*/

	class ChildTypesEnabledTest extends GGCMSTestCase {
		public function switches($file) {
			preg_match_all('/public function (\w+_enabled)\(\)/', file_get_contents($file), $matches);

			return $matches[1];
		}

		public function testPrivateClonefromHasEverySwitch() {
			$private = getenv('GGCMS_CONFIG_REPO') . '/etc/ggcms/clonefrom/child_types/enabled.php';

			if(!getenv('GGCMS_CONFIG_REPO') || !is_file($private)) {
				$this->markTestSkipped('No private repository with its own clonefrom child_types to compare.');
			}

			$public = $this->switches(GGCMS_DIR . '../../../../etc/ggcms/clonefrom/child_types/enabled.php');

			$this->assertNotEmpty($public);
			$this->assertSame([], array_values(array_diff($public, $this->switches($private))));
		}
	}

?>

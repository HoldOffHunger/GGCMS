<?php

	class BCETest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/System/BCE.php']);
		}

			/*
				systemstatus.php's master-variables panel.  The property name
				needs braces; without them PHP 7 and later read the property
				named by the whole array, and every value came back NULL.
			*/

		public function testGetMasterVariablesForObject() {
			$object = new stdClass();

			foreach((new BCE())->GetMasterVariableNames() as $name) {
				$object->{$name['AttributeName']} = 'value of ' . $name['AttributeName'];
			}

			$master_variables = (new BCE())->GetMasterVariablesForObject(['object'=>$object]);

			$this->assertSame('value of desired_script', $master_variables['Desired Script']);
			$this->assertSame('value of script_format_lower', $master_variables['Script Format Lower']);
			$this->assertCount(11, $master_variables);
		}

		public function testGetMasterVariableNames() {
			$names = (new BCE())->GetMasterVariableNames();

			$this->assertCount(11, $names);
			$this->assertSame(['FullName'=>'Desired Script', 'AttributeName'=>'desired_script'], $names[0]);
		}
	}

?>

<?php
	trait IntegerTrait {
		public function testIntegers() {
			$condition = TRUE;
			
			$this->assertEquals(2, 2, 'Integer 2 = 2.');
			$this->assertIsInteger(1);
			
			return TRUE;
		}
	}
?>
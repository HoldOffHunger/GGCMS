<?php
	trait StringTrait {
		public function testStrings() {
			$condition = TRUE;
			
			$string1 = 'True is equal to True';
			
			$this->assertEquals($string1, $string1, 'String 1 is equal to string 1');
			$this->assertIsString($string1);
			
			return TRUE;
		}
	}
?>
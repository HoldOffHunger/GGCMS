<?php
	trait TruthTrait {
		public function testTruth() {
			$condition = TRUE;
			
			$this->assertTrue($condition, 'True is True');
			$this->assertEquals($condition, TRUE, 'True is equal to True');
			
			return TRUE;
		}
	}
?>
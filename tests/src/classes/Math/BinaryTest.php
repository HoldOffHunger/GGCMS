<?php

	class BinaryTest extends GGCMSTestCase {
		public function testIncrementBinaryValue() {
			$binary = new Binary();

			foreach(['000'=>'001', '001'=>'010', '011'=>'100', '0111'=>'1000', '1010'=>'1011'] as $value => $expected) {
				$this->assertSame($expected, $binary->IncrementBinaryValue(['binary'=>(string)$value]), (string)$value);
			}

			$this->assertFalse($binary->IncrementBinaryValue(['binary'=>'111']), 'all ones cannot be incremented within its width');
		}

		public function testDecrementBinaryValue() {
			$binary = new Binary();

			foreach(['001'=>'000', '010'=>'001', '100'=>'011', '1000'=>'0111'] as $value => $expected) {
				$this->assertSame($expected, $binary->DecrementBinaryValue(['binary'=>(string)$value]), (string)$value);
			}

			$this->assertFalse($binary->DecrementBinaryValue(['binary'=>'000']), 'all zeroes cannot be decremented');
		}

		public function testBitKeyValues() {
			$binary = new Binary();

			$keyed = $binary->BitKeyValues(['values'=>['a', 'b', 'c', 'd'], 'baseobject'=>new Base()]);

			$this->assertSame(2, $keyed['bitlength']);
			$this->assertSame(['00'=>'a', '01'=>'b', '10'=>'c', '11'=>'d'], $keyed['keyedvalues']);
			$this->assertSame(['a'=>'00', 'b'=>'01', 'c'=>'10', 'd'=>'11'], $keyed['valuekeys']);
		}

		public function testGetBitLength() {
			$binary = new Binary();
			$base = new Base();

			foreach([2=>1, 8=>3, 16=>4, 32=>5, 64=>6] as $count => $expected) {
				$this->assertSame($expected, $binary->GetBitLength(['bitoptions'=>array_fill(0, $count, 'x'), 'baseobject'=>$base]), $count . ' options');
			}

			$this->assertFalse($binary->GetBitLength(['bitoptions'=>array_fill(0, 10, 'x'), 'baseobject'=>$base]), 'ten options is no whole number of bits');
		}
	}

?>

<?php

	class BaseTest extends GGCMSTestCase {
		public function testConvertBase() {
			$base = new Base();

			$cases = [
				['Hexadecimal', 'Binary', 'f0', '11110000'],
				['Binary', 'Hexadecimal', '11110000', 'f0'],
				['Hexadecimal', 'EightBit', 'fff', '7777'],
				['Hexadecimal', 'Base32', 'fffff', 'vvvv'],
				['Binary', 'Base64', '111111000000', '=0'],
			];

			foreach($cases as [$from, $to, $value, $expected]) {
				$this->assertSame($expected, $base->ConvertBase(['startingbase'=>$from, 'endingbase'=>$to, 'value'=>$value]), $from . ' ' . $value . ' to ' . $to);
			}

			$this->assertFalse($base->ConvertBase(['startingbase'=>'Decimal', 'endingbase'=>'Binary', 'value'=>'10']), 'an unknown base is refused');
		}

			/*
				modify.php and master-c.php file every uploaded image under the
				first four Base32 characters of its SHA-512.  Those four must
				never change, or every stored image moves out from under its
				record.  sha512('') is used because it is fixed forever.
			*/

		public function testConvertBaseImageDirectory() {
			$base = new Base();

			$full_image_hash = $base->ConvertBase(['startingbase'=>'Hexadecimal', 'endingbase'=>'Base32', 'value'=>hash('sha512', '')]);

			$this->assertSame('pu1u', substr($full_image_hash, 0, 4));
		}

			/*
				A value whose bits do not divide evenly into the ending base
				must keep its last bits.  SHA-512 is 512 bits, which is 102
				Base32 characters and two bits over.
			*/

		public function testConvertBaseKeepsTrailingBits() {
			$base = new Base();

			$this->assertSame('vs', $base->ConvertBase(['startingbase'=>'Hexadecimal', 'endingbase'=>'Base32', 'value'=>'ff']), '11111111 is 11111 then 111, padded to 11100');

			$full_image_hash = $base->ConvertBase(['startingbase'=>'Hexadecimal', 'endingbase'=>'Base32', 'value'=>hash('sha512', '')]);

			$this->assertSame(103, strlen($full_image_hash));
		}

		public function testIsBase2Number() {
			$base = new Base();

			foreach([2=>1, 4=>2, 8=>3, 16=>4, 32=>5, 64=>6, 1024=>10] as $number => $expected) {
				$this->assertSame($expected, $base->IsBase2Number(['number'=>$number]), (string)$number);
			}

			foreach([1, 3, 10, 62, 0] as $number) {
				$this->assertFalse($base->IsBase2Number(['number'=>$number]), (string)$number);
			}
		}

		public function testGetAlphabets() {
			$base = new Base();

			$alphabets = $base->GetAlphabets(['startingbase'=>'Hexadecimal', 'endingbase'=>'Base32']);

			$this->assertSame(4, $alphabets['startingalphabetbitlength']);
			$this->assertSame(5, $alphabets['endingalphabetbitlength']);
			$this->assertSame('1111', $alphabets['startingalphabetvaluekeys']['f']);
			$this->assertSame('v', $alphabets['endingalphabet']['11111']);
			$this->assertSame('11111', $alphabets['endingalphabetvaluekeys']['v']);

			$this->assertFalse($base->GetAlphabets(['startingbase'=>'Hexadecimal', 'endingbase'=>'Nope']));
		}

		public function testBitKeyAlphabet() {
			$base = new Base();
			$base->binary_object = new Binary();

			$keyed = $base->BitKeyAlphabet(['alphabet'=>$base->BinaryAlphabet()]);

			$this->assertSame(['0'=>'0', '1'=>'1'], $keyed['keyedvalues']);
		}

		public function testBase64Alphabet() {
			$this->assertAlphabet(['alphabet'=>(new Base())->Base64Alphabet(), 'size'=>64]);
		}

		public function testBase32Alphabet() {
			$this->assertAlphabet(['alphabet'=>(new Base())->Base32Alphabet(), 'size'=>32]);
		}

		public function testHexadecimalAlphabet() {
			$this->assertAlphabet(['alphabet'=>(new Base())->HexadecimalAlphabet(), 'size'=>16]);
		}

		public function testEightBitAlphabet() {
			$this->assertAlphabet(['alphabet'=>(new Base())->EightBitAlphabet(), 'size'=>8]);
		}

		public function testBinaryAlphabet() {
			$this->assertAlphabet(['alphabet'=>(new Base())->BinaryAlphabet(), 'size'=>2]);
		}

		public function assertAlphabet($args) {
			$this->assertCount($args['size'], $args['alphabet']);
			$this->assertCount($args['size'], array_unique($args['alphabet']), 'no letter appears twice');
		}
	}

?>

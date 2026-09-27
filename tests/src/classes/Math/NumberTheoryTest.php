<?php

	class NumberTheoryTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Math/NumberTheory.php']);
		}

		public function testFindGreatestCommonDivisor() {
			$number_theory = new NumberTheory();

			$cases = [
				[12, 18, 6],
				[18, 12, 6],
				[17, 5, 1],
				[48, 180, 12],
				[7, 7, 7],
				[0, 9, 9],
				[9, 0, 9],
				[1071, 462, 21],
				[3120, 17, 1],
				[0, 0, 0],
				[6.0, 4, 2],		// a float used to look odd, whatever its value
				[-4, 6, 2],			// a negative used to recurse until PHP died
				[-12, -18, 6],
			];

			foreach($cases as [$first, $second, $expected]) {
				$this->assertSame($expected, $number_theory->FindGreatestCommonDivisor(['firstnumber'=>$first, 'secondnumber'=>$second]), 'gcd(' . $first . ', ' . $second . ')');
			}
		}

		public function testIsThisNumberPrime() {
			$number_theory = new NumberTheory();

			foreach([2, 3, 5, 7, 11, 13, 97, 7919] as $prime) {
				$this->assertTrue($number_theory->IsThisNumberPrime(['allegedprime'=>$prime]), (string)$prime);
			}

			foreach([4, 9, 15, 25, 49, 91, 7917] as $composite) {
				$this->assertFalse($number_theory->IsThisNumberPrime(['allegedprime'=>$composite]), (string)$composite);
			}

			foreach([1, 0, -7] as $not_prime) {
				$this->assertFalse($number_theory->IsThisNumberPrime(['allegedprime'=>$not_prime]), 'nothing under 2 is prime: ' . $not_prime);
			}
		}

		public function testFindPrimeNumbers() {
			$number_theory = new NumberTheory();

			$this->assertSame([2, 3, 5, 7, 11], $number_theory->FindPrimeNumbers(['maxprime'=>100, 'primeorder'=>'', 'listlimit'=>0]), 'ascending and five by default');
			$this->assertSame([97, 89, 83], $number_theory->FindPrimeNumbers(['maxprime'=>100, 'primeorder'=>'Descending', 'listlimit'=>3]));
			$this->assertFalse($number_theory->FindPrimeNumbers(['maxprime'=>'many', 'primeorder'=>'', 'listlimit'=>0]));
			$this->assertFalse($number_theory->FindPrimeNumbers(['maxprime'=>100, 'primeorder'=>'Sideways', 'listlimit'=>0]));
		}

		public function testFindPrimeNumbersAscending() {
			$this->assertSame([2, 3, 5, 7], (new NumberTheory())->FindPrimeNumbersAscending(['maxprime'=>10, 'listlimit'=>10]), 'the maximum is exclusive');
			$this->assertSame([2, 3], (new NumberTheory())->FindPrimeNumbersAscending(['maxprime'=>30, 'listlimit'=>'2']), 'a limit given as a string');
		}

		public function testFindPrimeNumbersDescending() {
			$this->assertSame([7, 5, 3, 2], (new NumberTheory())->FindPrimeNumbersDescending(['maxprime'=>8, 'listlimit'=>10]));
			$this->assertSame([7, 5], (new NumberTheory())->FindPrimeNumbersDescending(['maxprime'=>8, 'listlimit'=>'2']), 'a limit given as a string');
		}

		public function testCalculatePhi() {
			$this->assertSame(3120, (new NumberTheory())->CalculatePhi(['firstnumber'=>61, 'secondnumber'=>53]));
		}

		public function testFindRelativelyPrimeNumbers() {
			$number_theory = new NumberTheory();

			$this->assertSame([3, 7, 9], $number_theory->FindRelativelyPrimeNumbers(['order'=>'Ascending', 'number'=>10, 'amounttofind'=>3]));
			$this->assertSame([9, 7], $number_theory->FindRelativelyPrimeNumbers(['order'=>'Descending', 'number'=>10, 'amounttofind'=>2]));
			$this->assertSame([3, 7], $number_theory->FindRelativelyPrimeNumbers(['order'=>'Ascending', 'number'=>10, 'amounttofind'=>'2']), 'an amount given as a string');
			$this->assertFalse($number_theory->FindRelativelyPrimeNumbers(['order'=>'Sideways', 'number'=>10, 'amounttofind'=>2]));

			foreach($number_theory->FindRelativelyPrimeNumbers(['order'=>'Random', 'number'=>3120, 'amounttofind'=>5]) as $coprime) {
				$this->assertSame(1, $number_theory->FindGreatestCommonDivisor(['firstnumber'=>3120, 'secondnumber'=>$coprime]), 'random pick ' . $coprime);
			}
		}

		public function testExtendedEuclideanAlgorithmProducts() {
			$this->assertEquals([3120, 17, 9, 8], (new NumberTheory())->ExtendedEuclideanAlgorithmProducts(['firstnumber'=>17, 'secondnumber'=>3120]), 'the order of the arguments does not matter');
		}

		public function testExtendedEuclideanAlgorithmProduct() {
			$this->assertEquals(['multiplyer'=>183, 'addition'=>9], (new NumberTheory())->ExtendedEuclideanAlgorithmProduct(['firstnumber'=>3120, 'secondnumber'=>17]));
		}

			/*
				The differences equation for 3120 and 17 ends as two products
				one apart -- the identity RSA takes its private exponent from.
			*/

		public function testExtendedEuclideanAlgorithmDifferences() {
			$number_theory = new NumberTheory();

			$products = $number_theory->ExtendedEuclideanAlgorithmProducts(['firstnumber'=>3120, 'secondnumber'=>17]);
			$equation = $number_theory->ExtendedEuclideanAlgorithmDifferences(['sum'=>1, 'products'=>$products]);

			$first = $equation['firstquantity']['value'];
			$second = $equation['secondquantity']['value'];

			$this->assertEquals(1, abs(($first['multiplyer'] * $first['multiple']) - ($second['multiplyer'] * $second['multiple'])), 'the two sides differ by one');
		}

		public function testDiocletianEquationEuclideanAlgorithmDifference() {
			$this->assertEquals(['multiplicand'=>183], (new NumberTheory())->DiocletianEquationEuclideanAlgorithmDifference(['product'=>3120, 'additive'=>17]));
		}
	}

?>

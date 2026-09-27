<?php

	class RandomTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Math/Random.php']);
		}

		public function testGetRandomString() {
			$random = new Random();

			$string = $random->GetRandomString(['stringlength'=>40, 'usenumbers'=>TRUE, 'uselowercaseletters'=>FALSE, 'useuppercaseletters'=>FALSE]);

			$this->assertMatchesRegularExpression('/\A[0-9]{40}\z/', $string);

			$string = $random->GetRandomString(['stringlength'=>0, 'usenumbers'=>FALSE, 'uselowercaseletters'=>TRUE, 'useuppercaseletters'=>TRUE]);

			$this->assertMatchesRegularExpression('/\A[a-zA-Z]\z/', $string, 'no length means one character');
		}

		public function testGetRandomString_OptionsArray() {
			$random = new Random();

			$this->assertCount(62, $random->GetRandomString_OptionsArray(['usenumbers'=>TRUE, 'uselowercaseletters'=>TRUE, 'useuppercaseletters'=>TRUE]));
			$this->assertSame([], $random->GetRandomString_OptionsArray(['usenumbers'=>FALSE, 'uselowercaseletters'=>FALSE, 'useuppercaseletters'=>FALSE]));
		}

		public function testGetNumbersArray() {
			$this->assertSame('0123456789', implode('', (new Random())->GetNumbersArray()));
		}

		public function testGetLowerCaseLettersArray() {
			$this->assertSame('abcdefghijklmnopqrstuvwxyz', implode('', (new Random())->GetLowerCaseLettersArray()));
		}

		public function testGetUpperCaseLettersArray() {
			$this->assertSame('ABCDEFGHIJKLMNOPQRSTUVWXYZ', implode('', (new Random())->GetUpperCaseLettersArray()));
		}
	}

?>

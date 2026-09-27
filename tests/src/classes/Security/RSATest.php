<?php

	/*
		Ben's RSA, built on NumberTheory's extended Euclid and Stein's GCD.
		The textbook pair is p = 61, q = 53, e = 17, whose private key is
		2753.  Encryption and decryption need bcmath; where it is missing
		those two tests say so and skip.
	*/

	class RSATest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Math/NumberTheory.php']);
			$this->requireEngine(['file'=>'classes/Security/RSA.php']);
		}

		public function requireBCMath() {
			if(!extension_loaded('bcmath')) {
				$this->markTestSkipped('bcmath is not loaded');
			}
		}

		public function testRSASetup() {
			$rsa = new RSA();

			$this->assertEquals(['firstpublicnumber'=>3233, 'secondpublicnumber'=>17, 'privatekey'=>2753, 'rsastage'=>2], $rsa->RSASetup(['firstprime'=>61, 'secondprime'=>53, 'secondpublicnumber'=>17, 'validateprimenumbers'=>TRUE]));
			$this->assertSame(1, $rsa->RSASetup(['firstprime'=>61, 'secondprime'=>53, 'secondpublicnumber'=>0, 'validateprimenumbers'=>TRUE, 'publicnumberstofind'=>3, 'publicnumbersorder'=>''])['rsastage'], 'no public number yet: stage one offers some');
			$this->assertSame(['rsastage'=>0], $rsa->RSASetup(['firstprime'=>60, 'secondprime'=>53, 'secondpublicnumber'=>17, 'validateprimenumbers'=>TRUE]), '60 is refused');
		}

		public function testRSASetupStep1() {
			$setup = (new RSA())->RSASetupStep1(['firstprime'=>61, 'secondprime'=>53, 'publicnumberstofind'=>3, 'publicnumbersorder'=>'']);

			$this->assertSame(3233, $setup['firstpublicnumber']);
			$this->assertSame([7, 11, 17], $setup['availablesecondpublicnumbers'], 'the first three numbers coprime to phi = 3120');
		}

		public function testRSASetupStep2() {
			$rsa = new RSA();

			foreach([[61, 53, 17], [3, 7, 11], [11, 13, 7], [17, 19, 5], [101, 113, 3]] as [$p, $q, $e]) {
				$private_key = $rsa->RSASetupStep2(['firstprime'=>$p, 'secondprime'=>$q, 'secondpublicnumber'=>$e])['privatekey'];

				$this->assertEquals(1, ($e * $private_key) % (($p - 1) * ($q - 1)), 'e * d = 1 mod phi for p=' . $p . ' q=' . $q . ' e=' . $e);
			}
		}

		public function testRSAEncryption() {
			$this->requireBCMath();

			$this->assertEquals(2790, (new RSA())->RSAEncryption(['message'=>65, 'firstpublickey'=>3233, 'secondpublickey'=>17])['encryptedmessage']);
		}

		public function testRSADecryption() {
			$this->requireBCMath();

			$this->assertEquals(65, (new RSA())->RSADecryption(['message'=>2790, 'firstpublickey'=>3233, 'privatekey'=>2753])['decryptedmessage']);
		}

		public function testRSAPowModCombo() {
			$this->requireBCMath();

			$this->assertEquals(2790, (new RSA())->RSAPowModCombo(['message'=>65, 'pow'=>17, 'mod'=>3233])['message_pow_modded']);
		}

		public function testRSASetupValidatePrimes() {
			$rsa = new RSA();

			$this->assertTrue($rsa->RSASetupValidatePrimes(['firstprime'=>61, 'secondprime'=>53]));
			$this->assertFalse($rsa->RSASetupValidatePrimes(['firstprime'=>60, 'secondprime'=>53]));
			$this->assertFalse($rsa->RSASetupValidatePrimes(['firstprime'=>61, 'secondprime'=>51]));
		}
	}

?>

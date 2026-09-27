<?php

	/*
		Authentication's constructor wants a request handler, and nothing
		tested here reads one, so the object is built without it.
	*/

	class AuthenticationTest extends GGCMSTestCase {
		public function testGenerateCookieToken() {
			$authentication = $this->newWithoutConstructor(['class'=>'Authentication']);

			$tokens = [];

			for($i = 0; $i < 500; $i++) {
				$token = $authentication->GenerateCookieToken(['useraccount'=>['Username'=>'someone']]);

				$this->assertMatchesRegularExpression('/\A[0-9a-zA-Z]{60}\z/', $token);

				$tokens[$token] = TRUE;
			}

			$this->assertCount(500, $tokens, 'no token repeats');
		}

			/*
				Random.php was loaded with plain require, so a request that
				made a second token, or had loaded Random already, died
				declaring the class twice.
			*/

		public function testGenerateCookieToken_Random() {
			$this->requireEngine(['file'=>'classes/Math/Random.php']);

			$authentication = $this->newWithoutConstructor(['class'=>'Authentication']);

			$first = $authentication->GenerateCookieToken_Random();
			$second = $authentication->GenerateCookieToken_Random();

			$this->assertSame(60, strlen($first));
			$this->assertNotSame($first, $second);
		}
	}

?>

<?php

	/*
		SetCookie() itself is not tested: under the CLI, headers_list() is
		always empty, so what setcookie() sent cannot be read back.
	*/

	class CookieTest extends GGCMSTestCase {
		public function newCookie() {
			$_COOKIE = ['AuthenticationToken'=>'abc', 'language'=>'ja'];

			$handler = new stdClass();
			$handler->time = new stdClass();
			$handler->time->time = 1000000000;

			return new Cookie(['handler'=>$handler]);
		}

		public function testGetCookie() {
			$this->assertSame('ja', $this->newCookie()->GetCookie(['cookie'=>'language']));
			$this->assertNull($this->newCookie()->GetCookie(['cookie'=>'absent']));
		}

		public function testPermanentCookieExpirationTime() {
			$this->assertSame(1000000000 + 315360000, $this->newCookie()->PermanentCookieExpirationTime(), 'ten years of 365 days');
		}

		public function testTemporaryCookieExpirationTime() {
			$this->assertSame(1000000000 + 14400, $this->newCookie()->TemporaryCookieExpirationTime(), 'four hours');
		}

		public function testDeleteCookieExpirationTime() {
			$this->assertLessThan(0, $this->newCookie()->DeleteCookieExpirationTime(), 'in the past, so the browser drops it');
		}

		public function testCookiePath() {
			$this->assertSame('/', $this->newCookie()->CookiePath());
		}

		public function testCookieHTTPOnlyOption() {
			$this->assertFalse($this->newCookie()->CookieHTTPOnlyOption(), 'the default; the login token asks for TRUE itself');
		}
	}

?>

<?php

	class Cookie {
		public function __construct($args) {
			$this->handler = $args['handler'];
			$this->cookie = $_COOKIE;
			
			return TRUE;
		}
		
		public function SetCookie($args) {
			$cookie_key = $args['key'];
			$cookie_value = $args['value'];
			$secure = $args['secure'] ?? FALSE;
			$permanent = $args['permanent'] ?? FALSE;
			$http_only = $args['httponly'] ?? $this->CookieHTTPOnlyOption();
			
			if(isset($cookie_value)) {
				if(!$secure && !$permanent) {
					$cookie_time = $this->TemporaryCookieExpirationTime();
				} else {
					$cookie_time = $this->PermanentCookieExpirationTime();
				}
			} else {
				$cookie_time = $this->DeleteCookieExpirationTime();
			}
			
			return setcookie(
				$cookie_key,
				$cookie_value ?? '',
				$cookie_time,
				$this->CookiePath(),
				$this->handler->domain->primary_domain_lowercased,
				$secure,
				$http_only
			);
		}
		
			// GetCookie()
			// Tests: CookieTest::testGetCookie()
			// Test file: tests/src/classes/Networking/CookieTest.php
		public function GetCookie($args) {
			$cookie_key = $args['cookie'];
			return $this->cookie[$cookie_key] ?? NULL;
		}
		
			// PermanentCookieExpirationTime()
			// Tests: CookieTest::testPermanentCookieExpirationTime()
			// Test file: tests/src/classes/Networking/CookieTest.php
		public function PermanentCookieExpirationTime() {
			return $this->handler->time->time + (10 * 365 * 24 * 60 * 60);
		}
		
			// TemporaryCookieExpirationTime()
			// Tests: CookieTest::testTemporaryCookieExpirationTime()
			// Test file: tests/src/classes/Networking/CookieTest.php
		public function TemporaryCookieExpirationTime() {
			return $this->handler->time->time + (4* 60 * 60);
		}
		
			// DeleteCookieExpirationTime()
			// Tests: CookieTest::testDeleteCookieExpirationTime()
			// Test file: tests/src/classes/Networking/CookieTest.php
		public function DeleteCookieExpirationTime() {
			return (-1) * ($this->handler->time->time + (10 * 365 * 24 * 60 * 60));
		}
		
			// CookiePath()
			// Tests: CookieTest::testCookiePath()
			// Test file: tests/src/classes/Networking/CookieTest.php
		public function CookiePath() {
			return '/';
		}
		
			// CookieHTTPOnlyOption()
			// Tests: CookieTest::testCookieHTTPOnlyOption()
			// Test file: tests/src/classes/Networking/CookieTest.php
		public function CookieHTTPOnlyOption() {
			return FALSE;
		}
	}

?>
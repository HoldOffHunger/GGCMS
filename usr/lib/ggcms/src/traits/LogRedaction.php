<?php

		/*
			ISE and ISI records used to store print_r($_SERVER), $_POST, $_GET
			and, for errors, the whole handler.  So a login that failed stored
			its plaintext password, every cookie went in with the server array,
			and every error on every site carried the global password seed.
			The error tables had become a second credential store.

			Everything bound for those tables passes through here.  Values
			under a sensitive-looking key are replaced at any depth and in any
			letter case, the server array is reduced to the fields that help
			diagnose a request, and sensitive query parameters are masked
			wherever a URL is kept.
		*/

	trait LogRedaction {
		public function RedactedMarker() {
			return '[REDACTED]';
		}

		public function SensitiveKeyFragments() {
			return [
				'password',
				'passwd',
				'pwd',
				'passphrase',
				'token',
				'secret',
				'seed',
				'salt',
				'authorization',
				'cookie',
				'session',
				'credential',
				'apikey',
				'api_key',
				'privatekey',
				'private_key',
				'csrf',
			];
		}

		public function LoggableServerFields() {
			return [
				'HTTP_HOST',
				'SERVER_NAME',
				'SERVER_PROTOCOL',
				'HTTPS',
				'REQUEST_METHOD',
				'REQUEST_URI',
				'REDIRECT_URL',
				'QUERY_STRING',
				'SCRIPT_FILENAME',
				'HTTP_USER_AGENT',
				'HTTP_REFERER',
				'HTTP_ACCEPT',
				'HTTP_ACCEPT_LANGUAGE',
				'REMOTE_ADDR',
				'REQUEST_TIME',
			];
		}

			// IsSensitiveKey()
			// Tests: LogRedactionTest::testIsSensitiveKey()
			// Test file: tests/src/traits/LogRedactionTest.php
		public function IsSensitiveKey($args) {
			$key = strtolower((string)$args['key']);

			foreach($this->SensitiveKeyFragments() as $fragment) {
				if(strpos($key, $fragment) !== FALSE) {
					return TRUE;
				}
			}

			return FALSE;
		}

			// RedactValues()
			// Tests: LogRedactionTest::testRedactValues()
			// Test file: tests/src/traits/LogRedactionTest.php
		public function RedactValues($args) {
			$values = $args['values'];

			if(!is_array($values)) {
				return $this->RedactURL(['url'=>$values]);
			}

			$redacted_values = [];

			foreach($values as $key => $value) {
				if($this->IsSensitiveKey(['key'=>$key])) {
					$redacted_values[$key] = $this->RedactedMarker();
				} elseif(is_array($value)) {
					$redacted_values[$key] = $this->RedactValues(['values'=>$value]);
				} elseif(is_scalar($value) || $value === NULL) {
					$redacted_values[$key] = $this->RedactURL(['url'=>$value]);
				} else {
					$redacted_values[$key] = '[' . gettype($value) . ']';
				}
			}

			return $redacted_values;
		}

			// RedactURL()
			// Tests: LogRedactionTest::testRedactURL()
			// Test file: tests/src/traits/LogRedactionTest.php
			/*
				Masks the value of any key=value pair whose key looks sensitive,
				so "/login.php?password=x&next=/" keeps "next" and loses "x".
				Anything that is not a string passes through untouched.
			*/

		public function RedactURL($args) {
			$url = $args['url'];

			if(!is_string($url) || strpos($url, '=') === FALSE) {
				return $url;
			}

			$fragments = implode('|', array_map('preg_quote', $this->SensitiveKeyFragments()));

			return preg_replace(
				'/(^|[?&;])([^=&;#]*(?:' . $fragments . ')[^=&;#]*=)[^&;#]*/i',
				'$1$2' . $this->RedactedMarker(),
				$url
			);
		}

			// LoggableServerVariables()
			// Tests: LogRedactionTest::testLoggableServerVariables()
			// Test file: tests/src/traits/LogRedactionTest.php
		public function LoggableServerVariables() {
			$server_variables = [];

			foreach($this->LoggableServerFields() as $field) {
				if(isset($_SERVER[$field])) {
					$server_variables[$field] = $_SERVER[$field];
				}
			}

			return $this->RedactValues(['values'=>$server_variables]);
		}

			// LoggableRequest()
			// Tests: LogRedactionTest::testLoggableRequest()
			// Test file: tests/src/traits/LogRedactionTest.php
		public function LoggableRequest() {
			return [
				'server'=>print_r($this->LoggableServerVariables(), TRUE),
				'post'=>print_r($this->RedactValues(['values'=>$_POST]), TRUE),
				'get'=>print_r($this->RedactValues(['values'=>$_GET]), TRUE),
				'url'=>$this->RedactURL(['url'=>$_SERVER['REQUEST_URI'] ?? '']),
			];
		}
	}

?>

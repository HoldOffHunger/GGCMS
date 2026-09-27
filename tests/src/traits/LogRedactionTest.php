<?php

	class LogRedactionTestSubject {
		use LogRedaction;
	}

	class LogRedactionTest extends GGCMSTestCase {
		public function testIsSensitiveKey() {
			$subject = new LogRedactionTestSubject();

			foreach(['password', 'NewPassword', 'AuthenticationToken', 'HTTP_COOKIE', 'api_key', 'csrf_token'] as $key) {
				$this->assertTrue($subject->IsSensitiveKey(['key'=>$key]), $key);
			}

			foreach(['username', 'next', 'id', 'REQUEST_URI', 0] as $key) {
				$this->assertFalse($subject->IsSensitiveKey(['key'=>$key]), (string)$key);
			}
		}

		public function testRedactValues() {
			$subject = new LogRedactionTestSubject();

			$redacted = $subject->RedactValues(['values'=>[
				'username'=>'ben',
				'Password'=>'hunter2',
				'nested'=>['deeper'=>['secret'=>'x', 'kept'=>'y']],
				'count'=>3,
				'nothing'=>NULL,
				'object'=>new stdClass(),
				'next'=>'/login.php?token=abc&page=2',
			]]);

			$this->assertSame('ben', $redacted['username']);
			$this->assertSame('[REDACTED]', $redacted['Password'], 'key match is case-insensitive');
			$this->assertSame('[REDACTED]', $redacted['nested']['deeper']['secret'], 'redaction reaches any depth');
			$this->assertSame('y', $redacted['nested']['deeper']['kept']);
			$this->assertSame(3, $redacted['count']);
			$this->assertNull($redacted['nothing']);
			$this->assertSame('[object]', $redacted['object'], 'an object is named, never dumped');
			$this->assertSame('/login.php?token=[REDACTED]&page=2', $redacted['next'], 'a URL held as a value is masked too');

			$this->assertSame('/a?password=[REDACTED]', $subject->RedactValues(['values'=>'/a?password=x']), 'a bare string is treated as a URL');
		}

		public function testRedactURL() {
			$subject = new LogRedactionTestSubject();

			$cases = [
				'/login.php?password=x&next=/'=>'/login.php?password=[REDACTED]&next=/',
				'/a?PassWord=x;session_id=y#frag'=>'/a?PassWord=[REDACTED];session_id=[REDACTED]#frag',
				'/view.php?id=4&page=2'=>'/view.php?id=4&page=2',
				'/no/query/at/all'=>'/no/query/at/all',
				'password=bare'=>'password=[REDACTED]',
			];

			foreach($cases as $url => $expected) {
				$this->assertSame($expected, $subject->RedactURL(['url'=>$url]), $url);
			}

			$this->assertSame(42, $subject->RedactURL(['url'=>42]), 'a non-string passes through');
		}

		public function testLoggableServerVariables() {
			$subject = new LogRedactionTestSubject();

			$_SERVER = [
				'HTTP_HOST'=>'example.com',
				'REQUEST_URI'=>'/login.php?password=x',
				'HTTP_COOKIE'=>'AuthenticationToken=abc',
				'PHP_AUTH_PW'=>'hunter2',
			];

			$expected = [
				'HTTP_HOST'=>'example.com',
				'REQUEST_URI'=>'/login.php?password=[REDACTED]',
			];

			$this->assertSame($expected, $subject->LoggableServerVariables(), 'only listed fields survive, and URLs among them are masked');
		}

		public function testLoggableRequest() {
			$subject = new LogRedactionTestSubject();

			$_SERVER = ['REQUEST_URI'=>'/a?token=t'];
			$_POST = ['password'=>'hunter2', 'title'=>'Hello'];
			$_GET = ['secret'=>'s'];

			$loggable = $subject->LoggableRequest();

			$this->assertSame('/a?token=[REDACTED]', $loggable['url']);
			$this->assertStringContainsString('Hello', $loggable['post']);
			$this->assertStringNotContainsString('hunter2', $loggable['post'], 'a posted password never reaches the log');
			$this->assertStringNotContainsString('[secret] => s', $loggable['get']);
		}
	}

?>

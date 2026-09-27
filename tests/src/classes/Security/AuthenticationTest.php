<?php

	/*
		Authentication's constructor wants a request handler.  These tests
		give it a small one: a database that answers the session lookup with
		whatever row the test chooses, and a script that declares whatever
		security the test asks for.
	*/

	class AuthenticationTestDatabase {
		public $session_row;
		public $queries = [];
		public $updates = [];

		public function GetRecords($args) {
			$this->queries[] = $args;
			return $this->session_row ? [$this->session_row] : [];
		}

		public function UpdateRecord($args) {
			$this->updates[] = $args;
			return [];
		}
	}

	class AuthenticationTestCookie {
		public $token;

		public function GetCookie($args) {
			return $this->token;
		}
	}

	class AuthenticationTestScript {
		public $secure;
		public $requires_login;
		public $admin_only;

		public function IsSecure() {
			return $this->secure;
		}

		public function RequiresLogin() {
			return $this->requires_login;
		}

		public function AdminOnly() {
			return $this->admin_only;
		}
	}

	class AuthenticationTest extends GGCMSTestCase {
		public function newAuthentication($args) {
			$database = new AuthenticationTestDatabase();
			$database->session_row = $args['sessionrow'] ?? NULL;

			$cookie = new AuthenticationTestCookie();
			$cookie->token = $args['token'] ?? NULL;

			$handler = (object)['cookie_token'=>NULL, 'cookie'=>$cookie, 'db_access'=>$database];

			return new Authentication(['handler'=>$handler]);
		}

		public function newScript($args) {
			$script = new AuthenticationTestScript();
			$script->secure = $args['secure'];
			$script->requires_login = $args['requireslogin'];
			$script->admin_only = $args['adminonly'];

			return (object)['script'=>$script];
		}

			/*
				Every door against every visitor.  Before 31 August an
				admin-only script let in anyone signed in: the requirement
				and the user's status were tested in one condition, and a
				non-admin fell to the else that grants access.
			*/

		public function testAuthenticate() {
			$reader = ['User.id'=>7, 'User.Username'=>'reader', 'User.EmailAddress'=>'reader@example.test', 'UserAdmin.id'=>NULL];
			$admin = ['User.id'=>1, 'User.Username'=>'admin', 'User.EmailAddress'=>'admin@example.test', 'UserAdmin.id'=>1];

			$open = ['secure'=>FALSE, 'requireslogin'=>FALSE, 'adminonly'=>FALSE];
			$secure = ['secure'=>TRUE, 'requireslogin'=>FALSE, 'adminonly'=>FALSE];
			$members = ['secure'=>TRUE, 'requireslogin'=>TRUE, 'adminonly'=>FALSE];
			$admins = ['secure'=>TRUE, 'requireslogin'=>TRUE, 'adminonly'=>TRUE];

			$cases = [
				['open page, anyone',				$open,		NULL,		'off',	1, 0, NULL],
				['secure page over http',			$secure,	NULL,		'off',	0, 1, 'ssl'],
				['secure page over https',			$secure,	NULL,		'on',	1, 0, NULL],
				['members page, nobody signed in',	$members,	NULL,		'on',	0, 1, 'login'],
				['members page, reader',			$members,	$reader,	'on',	1, 0, NULL],
				['admin page, nobody signed in',	$admins,	NULL,		'on',	0, 1, 'login'],
				['admin page, reader',				$admins,	$reader,	'on',	0, 1, 'login'],
				['admin page, admin',				$admins,	$admin,		'on',	1, 0, NULL],
			];

			foreach($cases as [$label, $security, $session_row, $https, $granted, $redirect, $redirect_type]) {
				$_SERVER['HTTPS'] = $https;

				$authentication = $this->newAuthentication(['sessionrow'=>$session_row, 'token'=>$session_row ? 'a-token' : NULL]);
				$authentication->Authenticate(['script'=>$this->newScript($security)]);

				$this->assertSame($granted, $authentication->access_granted, $label . ': access');
				$this->assertSame($redirect, $authentication->redirect, $label . ': redirect');
				$this->assertSame($redirect_type, $authentication->redirect_type, $label . ': redirect type');
			}
		}

		public function testCheckAuthenticationForCurrentObject_IsAdmin() {
			$authentication = $this->newAuthentication([]);

			$this->assertFalse($authentication->CheckAuthenticationForCurrentObject_IsAdmin(), 'nobody signed in');

			$authentication->user_session = ['UserAdmin.id'=>NULL];
			$this->assertFalse($authentication->CheckAuthenticationForCurrentObject_IsAdmin(), 'a reader');

			$authentication->user_session = ['UserAdmin.id'=>1];
			$this->assertTrue($authentication->CheckAuthenticationForCurrentObject_IsAdmin(), 'an admin');
		}

			/*
				A session lapses 160 hours after it was last used.  Until 26
				September the query compared LastAccess with LastAccess plus
				160 hours, which is true of every row, so no session ended.
			*/

		public function testCheckCurrentAuthentication() {
			$authentication = $this->newAuthentication(['sessionrow'=>['User.id'=>7, 'User.Username'=>'reader', 'User.EmailAddress'=>'reader@example.test'], 'token'=>'a-token']);

			$this->assertTrue($authentication->CheckCurrentAuthentication());
			$this->assertSame(['id'=>7, 'Username'=>'reader', 'EmailAddress'=>'reader@example.test'], $authentication->user_account);

			$query = $authentication->handler->db_access->queries[0];

			$this->assertSame('a-token', $query['definition']['CookieToken']);
			$this->assertSame(['>', 'DATE_SUB(NOW(), INTERVAL 160 HOUR)'], $query['definition']['RAW']['LastAccess'], 'the session must have been used in the last 160 hours');

			$nobody = $this->newAuthentication([]);

			$this->assertSame(0, $nobody->CheckCurrentAuthentication(), 'no cookie');
			$this->assertSame([], $nobody->handler->db_access->queries, 'and no query made');
		}

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

<?php

	/*
		Authentication's constructor wants a request handler.  These tests
		give it a small one: a database that answers the session lookup with
		whatever row the test chooses, and a script that declares whatever
		security the test asks for.
	*/

	class AuthenticationTestDatabase {
		public $session_row;
		public $users = [];				# username => User row, Password as the HEX() the ORM selects
		public $attempts = [];			# LoginAttempt rows
		public $missing_types = [];		# tables to answer as absent
		public $queries = [];
		public $updates = [];
		public $deletes = [];

		public function GetRecords($args) {
			$this->queries[] = $args;
			$definition = $args['definition'];

			if(in_array($args['type'], $this->missing_types, TRUE)) {
				return ['line'=>1, 'error'=>'Table does not exist'];
			}

			if($args['type'] === 'LoginAttempt') {
				$rows = array_values(array_filter($this->attempts, fn($row) => strcasecmp($row['Username'], $definition['Username']) === 0));
				return array_slice($rows, 0, $args['limit']);
			}

			if($args['type'] === 'User') {
				return isset($this->users[$definition['Username']]) ? [$this->users[$definition['Username']]] : [];
			}

			return $this->session_row ? [$this->session_row] : [];
		}

		public function UpdateRecord($args) {
			$this->updates[] = $args;

			if($args['type'] === 'User') {
				foreach($this->users as $username => $row) {
					if($row['id'] === $args['where']['id']) {
						if(isset($args['update']['PasswordHash'])) {
							$this->users[$username]['PasswordHash'] = $args['update']['PasswordHash'];
						}
						if(($args['update']['RAW']['Password'][1] ?? NULL) === 'DEFAULT(Password)') {
							$this->users[$username]['Password'] = '30' . str_repeat('00', 31);
						}
					}
				}
			}

			return [];
		}

		public function CreateRecord($args) {
			if($args['type'] === 'LoginAttempt') {
				$this->attempts[] = $args['definition'];
			}

			return ['id'=>8] + $args['definition'];
		}

		public function DeleteRecords($args) {
			$this->deletes[] = $args;

			if($args['type'] === 'LoginAttempt' && $args['where'] === 'Username = ? AND IPAddress = ?') {
				[$username, $address] = $args['wherevalues'];
				$this->attempts = array_values(array_filter($this->attempts, fn($row) => !($row['Username'] === $username && $row['IPAddress'] === $address)));
			}

			return [];
		}
	}

	class AuthenticationTestCookie {
		public $token;
		public $set = [];

		public function GetCookie($args) {
			return $this->token;
		}

		public function SetCookie($args) {
			$this->set[] = $args;
			return TRUE;
		}
	}

	class AuthenticationTestIssueLogging {
		public $logs = [];

		public function createLog($args) {
			$this->logs[] = $args;
			return TRUE;
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

			$database->missing_types = $args['missingtypes'] ?? [];

				// Accounts still on unsalted SHA-256, as every account was
			foreach($args['accounts'] ?? [] as $username => $password) {
				$database->users[$username] = ['id'=>count($database->users) + 7, 'Username'=>$username, 'Password'=>strtoupper(hash('sha256', $password)), 'PasswordHash'=>'', 'EmailAddress'=>'reader@example.test', 'UserAdmin.id'=>NULL];
			}

			$handler = (object)['cookie_token'=>NULL, 'cookie'=>$cookie, 'db_access'=>$database, 'issue_logging'=>new AuthenticationTestIssueLogging()];

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

		public function login($args) {
			$_SERVER['HTTP_X_FORWARDED_FOR'] = $args['address'];

			return $args['authentication']->Login(['username'=>'reader', 'password'=>$args['password']])['status'];
		}

		public function issueTypes($args) {
			return array_column($args['authentication']->handler->issue_logging->logs, 'issuetype');
		}

		public function testLogin() {
			$this->requireEngine(['file'=>'classes/Math/Random.php']);
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

			$authentication = $this->newAuthentication(['accounts'=>['reader'=>'right horse']]);
			$database = $authentication->handler->db_access;

			$this->assertSame('Failure', $this->login(['authentication'=>$authentication, 'address'=>'198.51.100.7', 'password'=>'wrong']));
			$this->assertSame([['Username'=>'reader', 'IPAddress'=>'198.51.100.7']], $database->attempts, 'a failure is kept, with the address and never the password');

			$this->assertSame('Success', $this->login(['authentication'=>$authentication, 'address'=>'198.51.100.7', 'password'=>'right horse']));
			$this->assertSame([], $database->attempts, "success clears that address's failures for the account");
			$this->assertSame(60, strlen($authentication->handler->cookie->set[0]['value']), 'and signs in');
			$this->assertSame([], $this->issueTypes(['authentication'=>$authentication]));

			$this->assertArrayNotHasKey('Password', $authentication->user_account[0], 'no password material is passed on');
			$this->assertArrayNotHasKey('PasswordHash', $authentication->user_account[0]);

			$row = $database->users['reader'];
			$this->assertTrue(password_verify('right horse', $row['PasswordHash']), 'the SHA-256 account was upgraded by signing in');
			$this->assertSame('30' . str_repeat('00', 31), $row['Password'], 'and its old hash cleared');

			$this->assertSame('Success', $this->login(['authentication'=>$authentication, 'address'=>'198.51.100.7', 'password'=>'right horse']), 'and signs in again on the new hash');
			$this->assertSame('Failure', $this->login(['authentication'=>$authentication, 'address'=>'198.51.100.7', 'password'=>'wrong']));
		}

			/*
				Google sign-in made its accounts with no username and gave
				every one the same password, SHA-256 of a seed printed in the
				public repository.  A blank username never reaches the
				lookup, whatever the password.
			*/

		public function testLoginRefusesBlankUsername() {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

			$authentication = $this->newAuthentication(['accounts'=>['' =>'the-published-seed']]);
			$database = $authentication->handler->db_access;

			foreach(['', '   '] as $username) {
				$result = $authentication->Login(['username'=>$username, 'password'=>'the-published-seed']);

				$this->assertSame('Failure', $result['status'], 'username "' . $username . '"');
			}

			$this->assertSame([], $database->queries, 'nothing was even looked up');
			$this->assertSame('Failure', $authentication->Login(['username'=>['reader'], 'password'=>'x'])['status'], 'an array is not a username');
		}

		public function testLogin_VerifyPassword() {
			$authentication = $this->newAuthentication([]);

			$legacy = ['id'=>7, 'Password'=>strtoupper(hash('sha256', 'right horse')), 'PasswordHash'=>''];
			$modern = ['id'=>7, 'Password'=>'30' . str_repeat('00', 31), 'PasswordHash'=>password_hash('right horse', PASSWORD_DEFAULT)];

			$cases = [
				['SHA-256, right',			$legacy,	'right horse',	TRUE],
				['SHA-256, wrong',			$legacy,	'wrong horse',	FALSE],
				['bcrypt, right',			$modern,	'right horse',	TRUE],
				['bcrypt, wrong',			$modern,	'wrong horse',	FALSE],
				['bcrypt ignores the old column',	['Password'=>$legacy['Password']] + $modern,	'right horse',	TRUE],
				['cleared, no hash: nothing matches',	['id'=>7, 'Password'=>'30' . str_repeat('00', 31), 'PasswordHash'=>''],	'',	FALSE],
				['no account',				NULL,		'right horse',	FALSE],
			];

			foreach($cases as [$label, $account, $password, $expected]) {
				$this->assertSame($expected, $authentication->Login_VerifyPassword(['useraccount'=>$account, 'password'=>$password]), $label);
			}

			$this->assertSame(['algo'=>'2y', 'algoName'=>'bcrypt', 'options'=>['cost'=>12]], password_get_info($authentication->Login_DecoyHash()), 'the decoy costs what a real check costs');
		}

		public function testLogin_UpgradePassword() {
			$authentication = $this->newAuthentication(['accounts'=>['reader'=>'right horse']]);
			$database = $authentication->handler->db_access;

			$this->assertTrue($authentication->Login_UpgradePassword(['useraccount'=>$database->users['reader'], 'password'=>'right horse']));
			$this->assertSame(['id'=>7], $database->updates[0]['where']);
			$this->assertSame(['=', 'DEFAULT(Password)'], $database->updates[0]['update']['RAW']['Password']);

			$this->assertFalse($authentication->Login_UpgradePassword(['useraccount'=>$database->users['reader'], 'password'=>'right horse']), 'a current bcrypt hash is left alone');
			$this->assertCount(1, $database->updates);
		}

			/*
				Three from one address blocks that address; ten from anywhere
				locks the account.  A refused attempt never reaches the
				password, so the right one is refused too.  One issue at each
				limit, not one per refusal.
			*/

		public function testLoginLimits() {
			$this->requireEngine(['file'=>'classes/Math/Random.php']);
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

			$authentication = $this->newAuthentication(['accounts'=>['reader'=>'right horse']]);
			$database = $authentication->handler->db_access;

			foreach([1, 2, 3] as $attempt) {
				$this->assertSame('Failure', $this->login(['authentication'=>$authentication, 'address'=>'198.51.100.7', 'password'=>'wrong ' . $attempt]));
			}

			$this->assertSame(['Login Address Blocked'], $this->issueTypes(['authentication'=>$authentication]), 'at the third');

			$user_queries = count(array_filter($database->queries, fn($query) => $query['type'] === 'User'));
			$this->assertSame('Failure', $this->login(['authentication'=>$authentication, 'address'=>'198.51.100.7', 'password'=>'right horse']), 'the blocked address, with the right password');
			$this->assertSame($user_queries, count(array_filter($database->queries, fn($query) => $query['type'] === 'User')), 'and the password was never checked');
			$this->assertSame(3, count($database->attempts), 'nor the refusal counted');

			$this->assertSame('Success', $this->login(['authentication'=>$authentication, 'address'=>'203.0.113.9', 'password'=>'right horse']), 'another address still may');

			$locked = $this->newAuthentication(['accounts'=>['reader'=>'right horse']]);

			for($attempt = 1; $attempt <= 10; $attempt++) {
				$this->assertSame('Failure', $this->login(['authentication'=>$locked, 'address'=>'192.0.2.' . $attempt, 'password'=>'wrong']));
			}

			$this->assertSame(['Login Account Locked'], $this->issueTypes(['authentication'=>$locked]), 'at the tenth');
			$this->assertSame('Failure', $this->login(['authentication'=>$locked, 'address'=>'203.0.113.9', 'password'=>'right horse']), 'locked from a new address, right password and all');
			$this->assertSame(['Login Account Locked'], $this->issueTypes(['authentication'=>$locked]), 'and no second issue for it');

			$query = $database->queries[0];
			$this->assertSame(['>', 'DATE_SUB(NOW(), INTERVAL 24 HOUR)'], $query['definition']['RAW']['OriginalCreationDate'], 'only the last day counts');

			$unlimited = $this->newAuthentication(['accounts'=>['reader'=>'right horse'], 'missingtypes'=>['LoginAttempt']]);

			$this->assertSame('Success', $this->login(['authentication'=>$unlimited, 'address'=>'198.51.100.7', 'password'=>'right horse']), 'no table yet: login still works');
			$this->assertSame(['Login Limits Unavailable'], $this->issueTypes(['authentication'=>$unlimited]), 'and says it went unlimited');
		}

		public function testLoginAttempt_ClientAddress() {
			$authentication = $this->newAuthentication([]);
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

			$cases = [
				['nginx appended the visitor',			'203.0.113.9',					NULL,			'203.0.113.9'],
				['earlier entries are the client\'s',	'10.0.0.1, 203.0.113.9',		NULL,			'203.0.113.9'],
				['nothing usable forwarded',			'not an address',				NULL,			'127.0.0.1'],
				['one IPv6 address, one way',			'2001:DB8:0:0:0:0:0:1',			NULL,			'2001:db8::1'],
				['CF-Connecting-IP is not believed',	'203.0.113.9',					'192.0.2.1',	'203.0.113.9'],
			];

			foreach($cases as [$label, $forwarded, $cloudflare, $expected]) {
				$_SERVER['HTTP_X_FORWARDED_FOR'] = $forwarded;
				$_SERVER['HTTP_CF_CONNECTING_IP'] = $cloudflare;

				$this->assertSame($expected, $authentication->LoginAttempt_ClientAddress(), $label);
			}
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

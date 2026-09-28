<?php

	class Authentication {
		public $handler;
		public $script;
		public $redirect_type;
		public $random;
		
		public $access_granted;
		public $redirect;
		public $protocol;
		public $user_account;
		public $user_session;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$this->access_granted = 0;
			$this->redirect = 0;
			$this->protocol = '';
		}
		
			// Authenticate()
			// Tests: AuthenticationTest::testAuthenticate()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
		public function Authenticate($args) {
			$this->script = $args['script'];
			
			if($this->script->script) {
				$this->DetectHTTPS();
				if($this->protocol === 'ssl') {
					$this->CheckCurrentAuthentication();
				}
				if($this->script->script->IsSecure()) {
					$this->DetectHTTPS();
					if($this->protocol === 'ssl') {
						if($this->script->script->RequiresLogin()) {
							if ($this->user_session) {
								if($this->script->script->AdminOnly()) {
									if($this->CheckAuthenticationForCurrentObject_IsAdmin()) {
										$this->access_granted = 1;
									} else {
										$this->redirect = 1;
										$this->redirect_type = 'login';
										$this->access_granted = 0;
									}
								} else {
									$this->access_granted = 1;
								}
								//IF (USER IS AUTHENTICATED FOR OBJECT)
								//{
								//}
								//else
								//{
									//$this->access_granted = 0;
								//}
							} else {
							//	REDIRECT TO LOGIN SCREEN
								$this->redirect = 1;
								$this->redirect_type = 'login';
								$this->access_granted = 0;
							}
						} else {
							$this->access_granted = 1;
						}
					} else {
						$this->redirect = 1;
						$this->redirect_type = 'ssl';
						$this->access_granted = 0;
					}
				} else {
					$this->access_granted = 1;
				}
			} else {
				$this->access_granted = 1;
			}
		}
		
		public function ReAuthenticate() {
			if(!$this->user_session) {
				$this->CheckCurrentAuthentication();
			}
			
			if($this->user_session) {
				$last_login = strtotime($this->user_session['LastAccess']);
				$now = $this->handler->time->time;
				
				if(($now - (60 * 15)) >= $last_login) {
					return $this->RefreshAuthentication();
				}
			}
			
			return TRUE;
		}
		
		public function RefreshAuthentication() {
			return $this->Login_Successful([
				'useraccount'=>[$this->user_account],
				'refresh'=>1,
			]);
		}
		
		public function CheckAuthenticationForCurrentObject() {
			if($this->CheckAuthenticationForCurrentObject_IsAdmin() || $this->CheckAuthenticationForCurrentObject_IsOwner()) {
				return TRUE;
			}
			
			return FALSE;
		}
		
			// CheckAuthenticationForCurrentObject_IsAdmin()
			// Tests: AuthenticationTest::testCheckAuthenticationForCurrentObject_IsAdmin()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
		public function CheckAuthenticationForCurrentObject_IsAdmin() {
			if($this->user_session && $this->user_session['UserAdmin.id']) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function CheckAuthenticationForCurrentObject_IsOwner() {
			return FALSE;
		}
		
			// CheckCurrentAuthentication()
			// Tests: AuthenticationTest::testCheckCurrentAuthentication()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
		public function CheckCurrentAuthentication() {
			$this->user_session = NULL;
			$this->user_account = NULL;
			$this->access_granted = 0;
			$authentication_token = $this->handler->cookie_token;
			
			if(!$authentication_token) {
				$authentication_token = $this->handler->cookie->GetCookie(['cookie'=>'AuthenticationToken']);
			}
			
			if(is_string($authentication_token) && $authentication_token) {
				$user_session_record_args = [
					'type'=>'UserSession',
					'definition'=>[
						'CookieToken'=>$authentication_token,
						'RAW'=>[
							'LastAccess'=>[
								'>',
								'DATE_SUB(NOW(), INTERVAL 160 HOUR)',
							],
						],
					],
					'limit'=>1,
					'joins'=>[
						'LEFT JOIN'=>[
							'User'=>'User.id = UserSession.Userid',
							'UserAdmin'=>'UserAdmin.Userid = UserSession.Userid',
						],
					],
				];
				
				$user_session = $this->handler->db_access->GetRecords($user_session_record_args);
					
					/*
						This runs on every HTTPS request carrying the cookie, not
						only on login, so a failed lookup must not become a 500
						in front of a reader.  They see the page logged out, and
						the failure is kept as an issue rather than lost.
					*/
				
				if(!empty($user_session['line'])) {
					if(isset($this->handler->issue_logging)) {
						$this->handler->issue_logging->createLog([
							'issuetype'=>'Session Lookup Failed',
							'description'=>'Unable to verify authentication session; the request continued logged out.',
						]);
					}
					
					return 0;
				}
				if(!empty($user_session[0]['User.id'])) {
					$this->user_session = $user_session[0];
					$this->user_account = [];
					$this->user_account['id'] = $this->user_session['User.id'];
					$this->user_account['Username'] = $this->user_session['User.Username'];
					$this->user_account['EmailAddress'] = $this->user_session['User.EmailAddress'];
					
					return TRUE;
				}
			}
			
			return 0;
		}
		
			// Login()
			// Tests: AuthenticationTest::testLogin(), AuthenticationTest::testLoginLimits()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
			/*
				Refused before the password is looked at, so a guess made
				while an address or an account is held back can never
				succeed.  See LoginAttempt_IsRefused() for the limits.
			*/
		public function Login($args) {
			$username = $args['username'];
			$password = $args['password'];
			
			$address = $this->LoginAttempt_ClientAddress();
			
			$recent_failures = $this->LoginAttempt_RecentFailures(['username'=>$username]);
			
			$is_refused_args = [
				'failures'=>$recent_failures,
				'address'=>$address,
			];
			
			if($this->LoginAttempt_IsRefused($is_refused_args)) {
				return $this->Login_Failure([
					'username'=>$username,
					'password'=>$password,
					'hashed_password'=>'',
				]);
			}
			
			$hashed_password = hash('sha256', $password);
			
			$user_record_args = [
				'type'=>'User',
				'definition'=>[
					'Username'=>$username,
					'RAW'=>[
						'Password'=>[
							'=',
							'UNHEX(\'' . $hashed_password . '\')',
						],
					],
				],
				'limit'=>1,
				'joins'=>[
					'LEFT JOIN'=>[
						'UserAdmin'=>'UserAdmin.Userid = User.id',
					],
				],
			];
			
			$user_account = $this->handler->db_access->GetRecords($user_record_args);
			if(!empty($user_account['line'])) {
				throw new RuntimeException('Unable to verify login credentials.');
			}
			
			if($user_account) {
				$this->LoginAttempt_Clear(['username'=>$username, 'address'=>$address]);
				
				$this->user_account = $user_account;
				$login_successful_args = [
					'useraccount'=>$user_account,
				];
				return $this->Login_Successful($login_successful_args);
			} else {
				$record_failure_args = [
					'username'=>$username,
					'address'=>$address,
					'failures'=>$recent_failures,
				];
				
				$this->LoginAttempt_RecordFailure($record_failure_args);
				
				$login_failure_args = [
					'username'=>$username,
					'password'=>$password,
					'hashed_password'=>$hashed_password,
				];
				return $this->Login_Failure($login_failure_args);
			}
		}
		
		public function Logout() {
			$user_session = $this->user_session;
			
			$cookie_result = $this->Logout_ResetCookie();
			$logout_result = $this->Logout_ResetDatabase();
			if(!empty($logout_result['line'])) {
				throw new RuntimeException('Unable to clear authentication session.');
			}
			
			$this->user_session = NULL;
			$this->user_account = NULL;
			if($cookie_result === FALSE) {
				throw new RuntimeException('Unable to clear authentication cookie.');
			}
			
			return $user_session;
		}
		
		public function Logout_ResetDatabase() {
			$authentication_token = $this->handler->cookie->GetCookie(['cookie'=>'AuthenticationToken']);
			
			if(!is_string($authentication_token) || $authentication_token === '') {
				return [];
			}
			
			$user_session_update_args = [
				'type'=>'UserSession',
				'update'=>[
					'CookieToken'=>'',
					'LastAccess'=>'00:00:00 0000-00-00',
				],
				'where'=>[
					'CookieToken'=>$authentication_token,
				],
			];
			
			return $this->handler->db_access->UpdateRecord($user_session_update_args);
		}
		
		public function Logout_ResetCookie() {
			$set_authentication_cookie_args = [
				'secure'=>TRUE,
				'httponly'=>TRUE,		# no script reads it, so no script injected into a page can take it
				'key'=>'AuthenticationToken',
				'value'=>null,
			];
			
			return $this->handler->cookie->SetCookie($set_authentication_cookie_args);
		}
		
			// GenerateCookieToken()
			// Tests: AuthenticationTest::testGenerateCookieToken()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
			/*
				The token was a bcrypt hash of the username, the time and two
				salts, read by ConvertBase() as hexadecimal.  Most of a bcrypt
				string is not hexadecimal, and ConvertBase() drops what is
				not, so a token kept only its hex-looking characters: eleven
				on average, four at the fewest, and always beginning 8i, from
				the 2, 1 and 2 in "$2y$12$".  A token that short can be
				guessed.  It is now sixty characters drawn by random_int(),
				about 357 bits; the salts, public in this repository anyway,
				went with it.
			*/
		public function GenerateCookieToken($args) {
			return $this->GenerateCookieToken_Random();
		}
		
			// GenerateCookieToken_Random()
			// Tests: AuthenticationTest::testGenerateCookieToken_Random()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
		public function GenerateCookieToken_Random() {
			$random_class_location = GGCMS_DIR . 'classes/Math/Random.php';
			require_once($random_class_location);		# a second token in one request would declare Random twice
			$this->random = new Random();
			$random_string_args = [
				'stringlength'=>60,
				'usenumbers'=>1,
				'useuppercaseletters'=>1,
				'uselowercaseletters'=>1,
			];
			$cookie_token = $this->random->GetRandomString($random_string_args);
			return $cookie_token;
		}
		
		public function Login_Successful($args) {
			$user_account = $args['useraccount'];
			$user_session = $this->user_session;
			$user_session_returnable = NULL;
			$first_user_account = $user_account[0];
			
			$userid = $first_user_account['id'];
			
			if(!$userid) {
				$userid = $user_session['Userid'];
			}
				
			$cookie_token_args = [
				'useraccount'=>$first_user_account,
			];
			$cookie_token = $this->GenerateCookieToken($cookie_token_args);
			
			if(!$this->AllowMultipleDeviceLogin() || !empty($args['refresh'])) {
				$user_session_where_args = [
					'type'=>'UserSession',
					'definition'=>[
						'Userid'=>$userid,
					],
					'limit'=>1,
				];
				
				if(!empty($args['refresh'])) {
					$user_session_where_args['definition']['CookieToken'] = $user_session['CookieToken'];
				}
				
				$user_session = $this->handler->db_access->GetRecords($user_session_where_args);
				if(!empty($user_session['line'])) {
					throw new RuntimeException('Unable to load authentication session.');
				}
				
				if($user_session) {
					$first_user_session = $user_session[0];
					$new_user_session = $first_user_session;
					$new_user_session['CookieToken'] = $cookie_token;
					$new_user_session['LastAccess'] = 'NOW()';
					
					$user_session_update_args = [
						'type'=>'UserSession',
						'update'=>[
							'CookieToken'=>$cookie_token,
							'RAW'=>[
								'LastAccess'=>[
									'=',
									'NOW()',
								],
							],
						],
						'where'=>[
							'id'=>$new_user_session['id'],
						],
					];
					
					$user_session_update_results = $this->handler->db_access->UpdateRecord($user_session_update_args);
					if(!empty($user_session_update_results['line'])) {
						throw new RuntimeException('Unable to save authentication session.');
					}
					
					$user_session_returnable = $new_user_session;
					$user_session_returnable = $user_session_update_results[0];
				}
			}
			
			if(!$user_session_returnable) {
				$user_session_insert_args = [
					'type'=>'UserSession',
					'definition'=>[
						'Userid'=>$first_user_account['id'],
						'CookieToken'=>$cookie_token,
						'RAW'=>[
							'LastAccess'=>[
								'=',
								'NOW()',
							],
						],
					],
				];
				
				$user_session_creation_results = $this->handler->db_access->CreateRecord($user_session_insert_args);
				if(!empty($user_session_creation_results['line'])) {
					throw new RuntimeException('Unable to save authentication session.');
				}
				
				$user_session_returnable = $user_session_creation_results;
			}
			
			$set_authentication_cookie_args = [
				'secure'=>TRUE,
				'httponly'=>TRUE,		# no script reads it, so no script injected into a page can take it
				'key'=>'AuthenticationToken',
				'value'=>$cookie_token,
			];
			
			if($this->handler->cookie->SetCookie($set_authentication_cookie_args) === FALSE) {
				throw new RuntimeException('Unable to set authentication cookie.');
			}
			$this->handler->cookie_token = $cookie_token;
			
			return [
				'status'=>'Success',
				'useraccount'=>$first_user_account,
				'usersession'=>$user_session_returnable,
			];
		}
		
		public function Login_Failure($args) {
			$username = $args['username'];
			$password = $args['password'];
			$hashed_password = $args['hashed_password'];
			
			return [
				'status'=>'Failure',
				'useraccount'=>[],
			];
		}
		
			// Login Limits
			// -----------------------------------------------------------------
			
			/*
				Three wrong passwords for an account from one address in a day,
				and that address may not try that account again until the first
				of them is a day old.  Ten for an account from anywhere in a
				day, and it is locked the same way.  Both lapse on their own:
				a lock only an administrator could lift would let anyone lock
				an administrator out for good by failing ten times a day.
			*/
		
		public function LoginAttempt_AddressLimit() {
			return 3;
		}
		
		public function LoginAttempt_AccountLimit() {
			return 10;
		}
		
		public function LoginAttempt_WindowHours() {
			return 24;
		}
			
			// LoginAttempt_IsRefused()
			// Tests: AuthenticationTest::testLoginLimits()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
		public function LoginAttempt_IsRefused($args) {
			$failures = $args['failures'];
			$address = $args['address'];
			
			if(!is_array($failures)) {
				return FALSE;
			}
			
			if(count($failures) >= $this->LoginAttempt_AccountLimit()) {
				return TRUE;
			}
			
			$address_failures = 0;
			foreach($failures as $failure) {
				if($failure['IPAddress'] === $address) {
					$address_failures++;
				}
			}
			
			return $address_failures >= $this->LoginAttempt_AddressLimit();
		}
			
			/*
				Every failure for the account in the window, up to the
				account limit, since more than that refuses anyway.  NULL when
				the lookup fails -- a site whose LoginAttempt table has not
				been made yet -- and then login goes on unlimited, with an
				issue to say so, rather than locking every account out.
			*/
		
		public function LoginAttempt_RecentFailures($args) {
			$failures = $this->handler->db_access->GetRecords([
				'type'=>'LoginAttempt',
				'definition'=>[
					'Username'=>$args['username'],
					'RAW'=>[
						'OriginalCreationDate'=>[
							'>',
							'DATE_SUB(NOW(), INTERVAL ' . (int)$this->LoginAttempt_WindowHours() . ' HOUR)',
						],
					],
				],
				'limit'=>$this->LoginAttempt_AccountLimit(),
			]);
			
			if(!empty($failures['line'])) {
				$this->LoginAttempt_LogIssue([
					'issuetype'=>'Login Limits Unavailable',
					'description'=>'Could not read LoginAttempt, so this login was not limited.  Has the table been made on this site?',
				]);
				
				return NULL;
			}
			
			return $failures ?: [];
		}
			
			/*
				An issue once, at the attempt that crosses a limit, rather
				than one for every refusal after it.  The username and the
				address, never the password.
			*/
		
		public function LoginAttempt_RecordFailure($args) {
			$username = $args['username'];
			$address = $args['address'];
			$failures = $args['failures'];
			
			if(!is_array($failures)) {
				return FALSE;
			}
			
			$this->handler->db_access->DeleteRecords([
				'type'=>'LoginAttempt',
				'where'=>'OriginalCreationDate < DATE_SUB(NOW(), INTERVAL ' . (int)$this->LoginAttempt_WindowHours() . ' HOUR)',
				'sqlbindstring'=>'',
				'wherevalues'=>[],
			]);
			
			$this->handler->db_access->CreateRecord([
				'type'=>'LoginAttempt',
				'definition'=>[
					'Username'=>$username,
					'IPAddress'=>$address,
				],
			]);
			
			$failures[] = ['IPAddress'=>$address];
			
			$address_failures = 0;
			foreach($failures as $failure) {
				if($failure['IPAddress'] === $address) {
					$address_failures++;
				}
			}
			
			if(count($failures) === $this->LoginAttempt_AccountLimit()) {
				$this->LoginAttempt_LogIssue([
					'issuetype'=>'Login Account Locked',
					'description'=>'Account "' . $username . '" failed ' . count($failures) . ' logins in ' . $this->LoginAttempt_WindowHours() . ' hours, the last from ' . $address . '; it is locked until the first of them is ' . $this->LoginAttempt_WindowHours() . ' hours old.',
				]);
			} elseif($address_failures === $this->LoginAttempt_AddressLimit()) {
				$this->LoginAttempt_LogIssue([
					'issuetype'=>'Login Address Blocked',
					'description'=>'Address ' . $address . ' failed ' . $address_failures . ' logins to account "' . $username . '" in ' . $this->LoginAttempt_WindowHours() . ' hours; it may not try that account again until the first of them is ' . $this->LoginAttempt_WindowHours() . ' hours old.',
				]);
			}
			
			return TRUE;
		}
		
		public function LoginAttempt_Clear($args) {
			return $this->handler->db_access->DeleteRecords([
				'type'=>'LoginAttempt',
				'where'=>'Username = ? AND IPAddress = ?',
				'sqlbindstring'=>'ss',
				'wherevalues'=>[$args['username'], $args['address']],
			]);
		}
			
			/*
				The last address in X-Forwarded-For is the one nginx appended
				for the connection it accepted -- the visitor, or with
				Cloudflare in front, the visitor nginx's real_ip restored from
				Cloudflare's own ranges.  Earlier entries are whatever the
				client claimed.  CF-Connecting-IP is not read: on the sites
				nginx answers directly, a client can send it.  In a canonical
				form, so one address is never counted as two.
			*/
			
			// LoginAttempt_ClientAddress()
			// Tests: AuthenticationTest::testLoginAttempt_ClientAddress()
			// Test file: tests/src/classes/Security/AuthenticationTest.php
		public function LoginAttempt_ClientAddress() {
			$forwarded = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
			$address = trim(end($forwarded));
			
			if(!filter_var($address, FILTER_VALIDATE_IP)) {
				$address = (string)$_SERVER['REMOTE_ADDR'];
			}
			
			$packed = filter_var($address, FILTER_VALIDATE_IP) ? inet_pton($address) : FALSE;
			
			return $packed === FALSE ? $address : inet_ntop($packed);
		}
		
		public function LoginAttempt_LogIssue($args) {
			if(isset($this->handler->issue_logging)) {
				return $this->handler->issue_logging->createLog($args);
			}
			
			return FALSE;
		}
		
		public function AllowMultipleDeviceLogin() {
			return TRUE;
		}
		
		public function RedirectToNewURL($args) {
			$redirect_object = $args['redirect'];
			unset($args['redirect']);
			
			switch($this->redirect_type) {
				case 'ssl':
					return $redirect_object->RedirectToSecuredConnection($args);
					break;
					
				case 'login':
					return $redirect_object->RedirectToLogin($args);
					break;
					
				case 'invalidaccess':
					return $redirect_object->RedirectToInvalidAccess($args);
					break;
			}
			
			return FALSE;
		}
		
		public function DetectHTTPS() {
			if($_SERVER['HTTPS'] == 'on') {
				$this->protocol = 'ssl';
			} else {
				$this->protocol = 'unencrypted';
			}
		}
	}

?>
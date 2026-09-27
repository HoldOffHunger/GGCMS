<?php

	class Authentication {
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
			
			return $this;
		}
		
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
		
		public function CheckAuthenticationForCurrentObject_IsAdmin() {
			if($this->user_session && $this->user_session['UserAdmin.id']) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function CheckAuthenticationForCurrentObject_IsOwner() {
			return FALSE;
		}
		
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
		
		public function Login($args) {
			$username = $args['username'];
			$password = $args['password'];
			
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
				$this->user_account = $user_account;
				$login_successful_args = [
					'useraccount'=>$user_account,
				];
				return $this->Login_Successful($login_successful_args);
			} else {
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
		
		public function GenerateCookieToken($args) {
			return $this->GenerateCookieToken_Secure($args);
		}
		
		public function GenerateCookieToken_Secure($args) {
			$user_account = $args['useraccount'];
			
			$hash_values = [];
			$hash_values[] = $user_account['Username'];
			$hash_values[] = $this->AuthenticationToken_Salt1();
			$hash_values[] = $this->handler->time->time;
			$hash_values[] = $this->AuthenticationToken_Salt2();
			
			$hash_plaintext = implode('',$hash_values);
			$hash_token = password_hash($hash_plaintext, CRYPT_BLOWFISH);
			
			$base_object = new Base();
			$this->base_object = $base_object;
			
			$convert_base_args = [
				'value'=>$hash_token,
				'startingbase'=>'Hexadecimal',
				'endingbase'=>'Base64',
			];
			
			$hash_token_base64 = $base_object->ConvertBase($convert_base_args);
			
			return $hash_token_base64;
		}
		
		public function AuthenticationToken_Salt1() {
				// FIXME: Move to Globals
			return 'Ehtdetohda80d2)*08';
		}
		
		public function AuthenticationToken_Salt2() {
				// FIXME: Move to Globals
			return ';d79d(:Ddaddhr]LS-';
		}
		
		public function GenerateCookieToken_Random() {
			$random_class_location = GGCMS_DIR . 'classes/Math/Random.php';
			require($random_class_location);
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
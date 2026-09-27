<?php

			// https://console.developers.google.com/apis/credentials?project=revoltlib
			
			// https://www.google.com/webmasters/tools/home

	class Google {
		public $handler;
		public $client_id;
		public $client_secret;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			
#			print("BT:!");
#			$this->handler->globals->SetAPIData();
#			print_r($this->handler->globals);
			
			if(!is_array($this->handler->globals->apidata) || (count($this->handler->globals->apidata) < 1)) {
				return;
			}
			
			$this->client_id = $this->handler->globals->apidata['google']['client_id'];
			$this->client_secret = $this->handler->globals->apidata['google']['client_secret'];
			
			depreq('Google/vendor/autoload.php');
		}
		
		public function AuthenticateOrDisauthenticateWithGoogle($args) {
			$google_token_id = $args['token'];
			$logout = $args['logout'];
			
			$results = [];

				/*
					A site with no Google client configured cannot verify a token,
					and the library is only loaded when one is -- see the
					constructor.  Only holdoffhunger and yallhearingthis have one.
					Everywhere else a google_token_id went straight to
					new Google_Client and died: on 12 September 2026 one client
					POSTing it to revoltlib produced 242 fatals and as many 500s.
					Logout needs no client, so it is left alone.
				*/

			if(!$this->client_id) {
				$google_token_id = '';
			}
			
			if($google_token_id) {
				$client = new Google_Client([
					'client_id'=>$this->client_id,
				]);
				$payload = $client->verifyIdToken($google_token_id);
				
				if($payload) {
					$email_address = $payload['email'];
					
					$user_record_args = [
						'type'=>'User',
						'definition'=>[
							'EmailAddress'=>$email_address,
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
						throw new RuntimeException('Unable to look up Google account.');
					}
					
					if(!empty($user_account[0]['id'])) {
						$this->handler->authentication->user_account = $user_account;
						$this->handler->authentication->Login_Successful(['useraccount'=>$user_account]);
						$results['newuser'] = 0;
					} else {
						$hashed_password = hash('sha256', $this->handler->globals->passwordseed);
						$user_record_args = [
							'type'=>'User',
							'definition'=>[
								'EmailAddress'=>$email_address,
								'RAW'=>[
									'Password'=>[
										'=',
										'UNHEX(\'' . $hashed_password . '\')',
									],
								],
							],
						];
						
						$user_creation_results = $this->handler->db_access->CreateRecord($user_record_args);
						if(!empty($user_creation_results['line']) || empty($user_creation_results['id'])) {
							throw new RuntimeException('Unable to create Google account.');
						}
						
						$this->handler->authentication->user_account = [$user_creation_results];
						$this->handler->authentication->Login_Successful(['useraccount'=>[$user_creation_results]]);
						$results['newuser'] = 1;
					}
					
					$this->handler->authentication->CheckCurrentAuthentication();
					$results['action'] = 'login';
					
					$this->handleLoginCookie();
				}
			}
			
			if($logout) {
				$this->Logout();
				$results['action'] = 'logout';
				
				$this->handleLogoutCookie();
			}
			
			return $results;
		}
		
		public function Logout() {
			return $this->handler->authentication->Logout();
		}
		
		public function handleLoginCookie() {
			return $this->handler->cookie->SetCookie([
				'key'=>'loggedin',
				'value'=>TRUE,
				'permanent'=>TRUE,
			]);
		}
		
		public function handleLogoutCookie() {
			return $this->handler->cookie->SetCookie([
				'key'=>'loggedin',
				'value'=>FALSE,
				'permanent'=>TRUE,
			]);
		}
	}

?>
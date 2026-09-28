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
			
			$this->client_id = $this->handler->globals->apidata['google']['client_id'] ?? '';
			$this->client_secret = $this->handler->globals->apidata['google']['client_secret'] ?? '';	# unused: VerifyIdToken() needs no secret
		}
		
		public function AuthenticateOrDisauthenticateWithGoogle($args) {
			$google_token_id = $args['token'];
			$logout = $args['logout'];
			
			$results = [];

				/*
					A site with no Google client configured cannot verify a token.
					Only holdoffhunger and yallhearingthis have one.  Everywhere
					else a google_token_id used to go straight to Google's client
					library and die: on 12 September 2026 one client POSTing it to
					revoltlib produced 242 fatals and as many 500s.  Logout needs
					no client, so it is left alone.
				*/

			if(!$this->client_id) {
				$google_token_id = '';
			}

			if($google_token_id) {
				$payload = $this->VerifyIdToken(['token'=>$google_token_id]);
				
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
							/*
								No password.  Every account made here used to get
								SHA-256 of the password seed, one fixed string
								for every site, so any of them could be signed
								into with it and a blank username.  A Google
								account signs in through Google alone.
							*/
						
						$user_record_args = [
							'type'=>'User',
							'definition'=>[
								'EmailAddress'=>$email_address,
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
		
			/*
				A Google sign-in ID token is a JWT signed with RS256 by one of the
				keys Google publishes.  It is ours to trust when the signature
				checks against the key its header names, Google issued it, it was
				issued to this site's client, and it has not expired.  This
				replaces google/apiclient 2.1.1 (2016) and the Guzzle, phpseclib and
				firebase/jwt it carried, whose fallbacks called functions PHP 8
				removed.

				Accounts are matched on email address, so the address must also
				be one Google has verified.  The library never asked.

				Returns the token's claims, or NULL.
			*/

			// VerifyIdToken()
			// Tests: GoogleTest::testVerifyIdToken()
			// Test file: tests/src/classes/API/GoogleTest.php
		public function VerifyIdToken($args) {
			$parts = explode('.', (string)$args['token']);
			if(count($parts) !== 3) {
				return NULL;
			}

			[$encoded_header, $encoded_claims, $encoded_signature] = $parts;

			$header = json_decode($this->Base64URLDecode(['data'=>$encoded_header]), TRUE);
			$claims = json_decode($this->Base64URLDecode(['data'=>$encoded_claims]), TRUE);
			$signature = $this->Base64URLDecode(['data'=>$encoded_signature]);

			if(!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256' || !isset($header['kid'])) {
				return NULL;
			}

			$certificates = $this->GoogleCertificates();
			if(!isset($certificates[$header['kid']])) {
				return NULL;
			}

			$signed = openssl_verify($encoded_header . '.' . $encoded_claims, $signature, $certificates[$header['kid']], OPENSSL_ALGO_SHA256);
			if($signed !== 1) {
				return NULL;
			}

				// A minute either way for clock drift.
			$now = time();
			$leeway = 60;

			if(!in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], TRUE)) {
				return NULL;
			}

			if(($claims['aud'] ?? '') !== $this->client_id) {
				return NULL;
			}

			if(!isset($claims['exp']) || (int)$claims['exp'] < $now - $leeway) {
				return NULL;
			}

			if(isset($claims['iat']) && (int)$claims['iat'] > $now + $leeway) {
				return NULL;
			}

			if(empty($claims['email']) || ($claims['email_verified'] ?? FALSE) !== TRUE) {
				return NULL;
			}

			return $claims;
		}

			/*
				Google's signing certificates, key id to PEM.  Fetched per
				sign-in: sign-ins are rare, and the keys rotate.  An empty list
				on any failure, so no token verifies.
			*/

		public function GoogleCertificates() {
			$curl = curl_init('https://www.googleapis.com/oauth2/v1/certs');
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER=>TRUE,
				CURLOPT_TIMEOUT=>10,
				CURLOPT_CONNECTTIMEOUT=>5,
			]);
			$response = curl_exec($curl);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if($response === FALSE || $status !== 200) {
				return [];
			}

			$certificates = json_decode($response, TRUE);

			return is_array($certificates) ? $certificates : [];
		}

		public function Base64URLDecode($args) {
			$data = strtr($args['data'], '-_', '+/');

			return (string)base64_decode($data . str_repeat('=', (4 - strlen($data) % 4) % 4), TRUE);
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
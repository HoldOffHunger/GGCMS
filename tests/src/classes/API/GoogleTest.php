<?php

	/*
		Google sign-in tokens, signed here with a key made for the test and
		checked against its certificate in place of Google's published ones.
		Nothing goes to the network.
	*/

	class GoogleTest extends GGCMSTestCase {
		public static $key;
		public static $certificate;

		public static function setUpBeforeClass(): void {
			self::$key = openssl_pkey_new(['private_key_bits'=>2048, 'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
			$request = openssl_csr_new(['commonName'=>'ggcms-test'], self::$key);
			openssl_x509_export(openssl_csr_sign($request, NULL, self::$key, 1), self::$certificate);
		}

		public function newGoogle() {
			$this->requireEngine(['file'=>'classes/API/Google.php']);

			$google = new class extends Google {
				public $certificates = [];

				public function __construct() {}

				public function GoogleCertificates() {
					return $this->certificates;
				}
			};

			$google->client_id = 'fixture-client.apps.googleusercontent.com';
			$google->certificates = ['fixture-kid'=>self::$certificate];

			return $google;
		}

		public function token($args) {
			$encode = function($data) { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); };

			$header = $encode(json_encode(($args['header'] ?? []) + ['alg'=>'RS256', 'kid'=>'fixture-kid', 'typ'=>'JWT']));
			$claims = $encode(json_encode(($args['claims'] ?? []) + [
				'iss'=>'https://accounts.google.com',
				'aud'=>'fixture-client.apps.googleusercontent.com',
				'exp'=>time() + 3600,
				'iat'=>time(),
				'email'=>'reader@example.test',
				'email_verified'=>TRUE,
			]));

			openssl_sign($header . '.' . $claims, $signature, $args['key'] ?? self::$key, OPENSSL_ALGO_SHA256);

			return $header . '.' . $claims . '.' . $encode($signature);
		}

		public function testVerifyIdToken() {
			$google = $this->newGoogle();

			$claims = $google->VerifyIdToken(['token'=>$this->token([])]);
			$this->assertSame('reader@example.test', $claims['email']);

			$this->assertNotNull($google->VerifyIdToken(['token'=>$this->token(['claims'=>['iss'=>'accounts.google.com']])]));

			$other_key = openssl_pkey_new(['private_key_bits'=>2048, 'private_key_type'=>OPENSSL_KEYTYPE_RSA]);

			$rejected = [
				'another site\'s client'=>['claims'=>['aud'=>'someone-else.apps.googleusercontent.com']],
				'not Google'=>['claims'=>['iss'=>'https://evil.example']],
				'expired'=>['claims'=>['exp'=>time() - 3600]],
				'issued in the future'=>['claims'=>['iat'=>time() + 3600]],
				'unverified email'=>['claims'=>['email_verified'=>FALSE]],
				'email_verified as a string'=>['claims'=>['email_verified'=>'true']],
				'unknown key'=>['header'=>['kid'=>'unknown-kid']],
				'not RS256'=>['header'=>['alg'=>'HS256']],
				'signed by another key'=>['key'=>$other_key],
			];

			foreach($rejected as $name => $token_args) {
				$this->assertNull($google->VerifyIdToken(['token'=>$this->token($token_args)]), $name);
			}

			$token = $this->token([]);
			[$header, $claims, $signature] = explode('.', $token);
			$forged = rtrim(strtr(base64_encode(json_encode(['iss'=>'https://accounts.google.com', 'aud'=>'fixture-client.apps.googleusercontent.com', 'exp'=>time() + 3600, 'email'=>'admin@example.test', 'email_verified'=>TRUE])), '+/', '-_'), '=');

			$this->assertNull($google->VerifyIdToken(['token'=>$header . '.' . $forged . '.' . $signature]), 'claims swapped under a real signature');
			$this->assertNull($google->VerifyIdToken(['token'=>'not-a-token']));
			$this->assertNull($google->VerifyIdToken(['token'=>'']));

			$google->certificates = [];
			$this->assertNull($google->VerifyIdToken(['token'=>$token]), 'certificates unavailable');
		}
	}

?>

<?php
	use GuzzleHttp\Client;
	trait MissingDatabaseTrait {
		public function testMissingDatabaseTrait() {
			$domain = 'localhost';
			$condition = TRUE;
			
			$client = new Client([
					// Base URI is used with relative requests
				#	'base_uri' => 'https://wordweight.com/',
				#	'base_uri' => 'https://wordweight.com/',
					'base_uri'=> 'http://' . $domain . '/',
					// You can set any number of default request options.
					'timeout'  => 2.0,
				]
			);
			
			$response = $client->request('GET', '/root', ['http_errors' => FALSE]);
			
			$error_message_globals = new AbstractGlobals_type_error_errormessage();
			
		#	$expected_error_message = "<p>1049 : Unknown database '" . $domain . "'</p>";
			$expected_error_message = $error_message_globals->GetServer500ErrorMessage();
			
		#	print($error_message_globals->GetServer500ErrorMessage());
		#	print($error_message_globals->GetServer500ErrorMessage());
		#	print($error_message_globals->GetServer500ErrorMessage());
			
		#	print($expected_error_message);
			
		#	print('|');
		#	print_r($response->getBody()->read(10000));
		#	print('|');
		
			$this->assertEquals($expected_error_message, $response->getBody()->read(10000));
			
			return TRUE;
		}
	}
?>
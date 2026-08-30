<?php
	use GuzzleHttp\Client;
	trait MailToTrait {
		public function testMailToRedirectTrait() {
			$protocol = 'http://';
			$domain = 'localhost';
			
			$condition = TRUE;
			
			$client = new Client([
					'base_uri'=> $protocol . $domain,
					'timeout'  => 2.0,
				]
			);
			
			$response = $client->request('GET', '/mailto:hey@holdoffhunger.com', [
				'http_errors' => FALSE,
				'allow_redirects' => FALSE,
			]);
			
			$location_headers = $response->getHeader('location');
			
		#	print("|");
		#	print_r($location_headers);
		#	print($response->getBody()->read(10000));
		#	print("|");
			
			#$expected_location_header = $protocol . 'www.' . $domain . '/yo/root/';
			
			$expected_location_header = 'mailto:hey@holdoffhunger.com';
			$this->assertEquals($expected_location_header, $location_headers[0]);
			
			return TRUE;
		}
	}

?>
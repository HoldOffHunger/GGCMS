<?php
	use GuzzleHttp\Client;
	trait LinuxRedirectTrait {
		public function testLinuxRedirect() {
			$protocol = 'http://';
			$domain = 'localhost';
			
			$condition = TRUE;
			
			$client = new Client([
					'base_uri'=> $protocol . $domain,
					'timeout'  => 2.0,
				]
			);
			
			$expected_location_header = '/';
			
			$response = $client->request('GET', '/~someguy', [
				'http_errors' => FALSE,
				'allow_redirects' => FALSE,
			]);
			
			$location_headers = $response->getHeader('location');
			
			$this->assertEquals($expected_location_header, $location_headers[0]);
			
			return TRUE;
		}
	}

?>
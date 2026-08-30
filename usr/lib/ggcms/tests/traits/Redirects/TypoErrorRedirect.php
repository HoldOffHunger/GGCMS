<?php
	use GuzzleHttp\Client;
	trait TypoErrorRedirectTrait {
		public function testTypoErrorRedirect() {
			$protocol = 'http://';
			$domain = 'localhost';
			
			$condition = TRUE;
			
			$client = new Client([
					'base_uri'=> $protocol . $domain,
					'timeout'  => 2.0,
				]
			);
			
			$expected_location_header = $protocol . 'www.' . $domain . '/website.php?stopredirect=1';
			
			$response = $client->request('GET', '/website.php)', [
				'http_errors' => FALSE,
				'allow_redirects' => FALSE,
			]);
			
			$location_headers = $response->getHeader('location');
			
			$this->assertEquals($expected_location_header, $location_headers[0]);
			
			return TRUE;
		}
	}

?>
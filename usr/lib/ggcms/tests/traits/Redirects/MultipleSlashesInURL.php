<?php
	use GuzzleHttp\Client;
	trait MultipleSlashesInUrlTrait {
		public function testMultipleSlashesInURLTrait() {
			$protocol = 'http://';
			$domain = 'localhost';
			
			$condition = TRUE;
			
			$client = new Client([
					// Base URI is used with relative requests
				#	'base_uri' => 'https://wordweight.com/',
				#	'base_uri' => 'https://wordweight.com/',
					'base_uri'=> $protocol . $domain,
					// You can set any number of default request options.
					'timeout'  => 2.0,
				]
			);
			
		#	error_reporting(FALSE);
			
			$response = $client->request('GET', '/yo///////////root/', [
				'http_errors' => FALSE,
				'allow_redirects' => FALSE,
			]);
			
			$location_headers = $response->getHeader('location');
			
			$expected_location_header = $protocol . 'www.' . $domain . '/yo/root/';
			
			$this->assertEquals($expected_location_header, $location_headers[0]);
			
			return TRUE;
		}
	}

?>
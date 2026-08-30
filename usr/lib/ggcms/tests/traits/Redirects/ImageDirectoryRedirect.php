<?php
	use GuzzleHttp\Client;
	trait ImageDirectoryRedirectTrait {
		public function testImageDirectoryRedirect() {
			$protocol = 'http://';
			$domain = 'localhost';
			
			$condition = TRUE;
			
			$client = new Client([
					'base_uri'=> $protocol . $domain,
					'timeout'  => 2.0,
				]
			);
			
			$expected_location_header = '/image.php';
			
			$response = $client->request('GET', '/image/uuuuuuu', [
				'http_errors' => FALSE,
				'allow_redirects' => FALSE,
			]);
			
			$location_headers = $response->getHeader('location');
			
			$this->assertEquals($expected_location_header, $location_headers[0]);
			
			return TRUE;
		}
	}

?>
<?php
	use GuzzleHttp\Client;
	trait ServeLocalFilesRedirectTrait {
		public function testSrvLocalFilesRedirect() {
			$protocol = 'http://';
			$domain = 'localhost';
			
			$condition = TRUE;
			
			$client = new Client([
					'base_uri'=> $protocol . $domain,
					'timeout'  => 10.0,
				]
			);
			
			$expected_location_header = '/image.php';
			
			$response = $client->request('GET', '/?server=1&domain=sortwords.com', [
				'http_errors' => FALSE,
				'allow_redirects' => FALSE,
			]);
			// GGCMS_DATA_DIR
			print("||||");
			print_r($response->getBody()->read(10000));
			print("||||");
		#	$location_headers = $response->getHeader('location');
			
		#	$this->assertEquals($expected_location_header, $location_headers[0]);
			
			return TRUE;
		}
	}

?>
<?php

	class CurlTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/Curl.php']);
		}

			/*
				ping.php fetches what an administrator gives it and keeps the
				response under the document root, so where it may go is the
				whole of its safety.  Literal addresses and localhost only:
				no test here waits on the network.
			*/

		public function testPublicDestination() {
			$curl = new Curl();

			$refused = [
				'file:///etc/php/8.5/apache2/conf.d/99-ggcms-database.ini'	=> 'a local file',
				'gopher://93.184.216.34/'									=> 'a protocol other than http and https',
				'http://127.0.0.1/'											=> 'loopback',
				'http://localhost:8443/'									=> 'a name for loopback',
				'http://10.0.0.5/'											=> 'a private network',
				'http://192.168.1.1/'										=> 'a private network',
				'http://169.254.169.254/metadata/v1/'						=> 'the host\'s metadata service',
				'http://[::1]/'												=> 'IPv6 loopback',
				'http://[fd00::1]/'											=> 'IPv6 private',
				'not a url'													=> 'nothing to fetch',
			];

			foreach($refused as $url => $why) {
				$this->assertFalse($curl->PublicDestination(['url'=>$url]), $why . ': ' . $url);
			}

			$destination = $curl->PublicDestination(['url'=>'https://93.184.216.34/page']);

			$this->assertSame(443, $destination['port']);
			$this->assertSame(['93.184.216.34:443:93.184.216.34'], $destination['resolve'], 'pinned, so cURL cannot be answered differently');

			$this->assertSame(['[2606:4700::1111]:8080:[2606:4700::1111]'], $curl->PublicDestination(['url'=>'HTTP://[2606:4700::1111]:8080/'])['resolve'], 'public IPv6, a port, and a scheme in capitals');
		}
	}

?>

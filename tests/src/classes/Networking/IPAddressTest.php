<?php

	class IPAddressTest extends GGCMSTestCase {
		public function newIPAddress() {
			return new IPAddress(['handler'=>NULL]);
		}

		public function testGetIPAddressForDatabase() {
			$_SERVER['REMOTE_ADDR'] = '192.168.1.10';

			$this->assertSame('200200000000000000000000c0a8010a', $this->newIPAddress()->GetIPAddressForDatabase());
		}

		public function testConvertIPAddressForDatabase() {
			$ip_address = $this->newIPAddress();

			$this->assertSame('2002000000000000000000007f000001', $ip_address->ConvertIPAddressForDatabase(['ipaddress'=>'127.0.0.1']));
			$this->assertSame('20010db8000000000000000000000001', $ip_address->ConvertIPAddressForDatabase(['ipaddress'=>'2001:0db8:0000:0000:0000:0000:0000:0001']));
			$this->assertSame('', $ip_address->ConvertIPAddressForDatabase(['ipaddress'=>'']));
		}

		public function testConvertIPv4AddressToIPv6Address() {
			$this->assertSame('20020000000000000000000001020304', $this->newIPAddress()->ConvertIPv4AddressToIPv6Address(['ipaddress'=>'1.2.3.4']));
			$this->assertSame('200200000000000000000000ffffffff', $this->newIPAddress()->ConvertIPv4AddressToIPv6Address(['ipaddress'=>'255.255.255.255']));
		}

		public function testCleanseIPv6ForDatabase() {
			$this->assertSame('fe80abcd', $this->newIPAddress()->CleanseIPv6ForDatabase(['ipaddress'=>'fe80::abcd']));
		}

		public function testIsIPv4Address() {
			$this->assertTrue($this->newIPAddress()->IsIPv4Address(['ipaddress'=>'10.0.0.1']));
			$this->assertFalse($this->newIPAddress()->IsIPv4Address(['ipaddress'=>'fe80::1']));
		}

		public function testIsIPv6Address() {
			$this->assertTrue($this->newIPAddress()->IsIPv6Address(['ipaddress'=>'fe80::1']));
			$this->assertFalse($this->newIPAddress()->IsIPv6Address(['ipaddress'=>'10.0.0.1']));
		}
	}

?>

<?php

	class ReverseDNSNotationTestSubject {
		use ReverseDNSNotation;

		public $domain;
		public $reversed_domain;
	}

	class ReverseDNSNotationTest extends GGCMSTestCase {
		public function testReverseDomainName() {
			$subject = new ReverseDNSNotationTestSubject();

			$cases = [
				'holdoffhunger.com'=>'com.holdoffhunger',
				'news.example.co.uk'=>'uk.co.example.news',
				'localhost'=>'localhost',
			];

			foreach($cases as $domain => $expected) {
				$this->assertSame($expected, $subject->ReverseDomainName(['domain'=>$domain]), $domain);
			}

			$twice = $subject->ReverseDomainName(['domain'=>$subject->ReverseDomainName(['domain'=>'news.example.co.uk'])]);

			$this->assertSame('news.example.co.uk', $twice, 'reversing twice is the identity');
		}

		public function testReverseThisDomainName() {
			$subject = new ReverseDNSNotationTestSubject();
			$subject->domain = 'revoltlib.com';

			$this->assertSame('com.revoltlib', $subject->ReverseThisDomainName());
			$this->assertSame('com.revoltlib', $subject->reversed_domain, 'the result is also kept on the object');
		}
	}

?>

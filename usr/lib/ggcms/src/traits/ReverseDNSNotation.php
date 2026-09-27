<?php

	trait ReverseDNSNotation {
		public $reversed_domain;
		
			// ReverseDomainName()
			// Tests: ReverseDNSNotationTest::testReverseDomainName()
			// Test file: tests/src/traits/ReverseDNSNotationTest.php
		public function ReverseDomainName($args) {
			$domain = $args['domain'];
			
			$domain_pieces = explode('.', $domain);
			
			$reversed_domain_pieces = array_reverse($domain_pieces);
			
			$reversed_domain = implode('.', $reversed_domain_pieces);
			
			return $reversed_domain;
		}
		
			// ReverseThisDomainName()
			// Tests: ReverseDNSNotationTest::testReverseThisDomainName()
			// Test file: tests/src/traits/ReverseDNSNotationTest.php
		public function ReverseThisDomainName() {
			return $this->reversed_domain = $this->ReverseDomainName([
				'domain'=>$this->domain,
			]);
		}
	}

?>
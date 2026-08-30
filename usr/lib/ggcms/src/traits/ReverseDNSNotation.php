<?php

	trait ReverseDNSNotation {
		public function ReverseDomainName($args) {
			$domain = $args['domain'];
			
			$domain_pieces = explode('.', $domain);
			
			$reversed_domain_pieces = array_reverse($domain_pieces);
			
			$reversed_domain = implode('.', $reversed_domain_pieces);
			
			return $reversed_domain;
		}
		
		public function ReverseThisDomainName() {
			return $this->reversed_domain = $this->ReverseDomainName([
				'domain'=>$this->domain,
			]);
		}
	}

?>
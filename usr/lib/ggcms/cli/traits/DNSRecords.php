<?php

	trait DNSRecords {
		public function getDNSRecordValue() {
			return '_______your.server.address________';
		}
		
		public function getStandardTTL() {
			return 3600;
		}
		
		public function getDNSRecords() {
			$standard_ttl = $this->getStandardTTL();
			$default_server = $this->getDNSRecordValue();
			
			$dns_records = [
				[
					'Record Type'=>'A',
					'Hostname'=>$this->domain,
					'Value'=>$default_server,
					'TTL'=>$standard_ttl,
					'Type'=>'~',
				],
				[
					'Record Type'=>'A',
					'Hostname'=>'*.' . $this->domain,
					'Value'=>$default_server,
					'TTL'=>$standard_ttl,
					'Type'=>'~',
				],
				[
					'Record Type'=>'AAAA',
					'Hostname'=>$this->domain,
					'Value'=>$default_server,
					'TTL'=>$standard_ttl,
					'Type'=>'~',
				],
				[
					'Record Type'=>'AAAA',
					'Hostname'=>'*.' . $this->domain,
					'Value'=>$default_server,
					'TTL'=>$standard_ttl,
					'Type'=>'~',
				],
				[
					'Record Type'=>'CAA',
					'Hostname'=>$this->domain,
					'Value'=>'authorization:letsencrypt.org',
					'TTL'=>$standard_ttl,
					'Type'=>'WILD',
				],
				[
					'Record Type'=>'CAA',
					'Hostname'=>$this->domain,
					'Value'=>'authorization:letsencrypt.org',
					'TTL'=>$standard_ttl,
					'Type'=>'ISSUE',
				],
				[
					'Record Type'=>'CAA',
					'Hostname'=>$this->domain,
					'Value'=>'authorization:mailto:' . $this->globals->AdminEmailAddress(),
					'TTL'=>$standard_ttl,
					'Type'=>'IODEF',
				],
			];
			
			return $dns_records;
		}
	}

?>
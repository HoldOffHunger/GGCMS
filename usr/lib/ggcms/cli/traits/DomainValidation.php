<?php

	trait DomainValidation {
		public function validateDomain($args) {
			$domain_parts = $args['domain_parts'];
			
			if(count($domain_parts) < 2 || strlen($domain_parts[0]) === 0 || strlen($domain_parts[1]) === 0) {
				return FALSE;
			}
			
			return TRUE;
		}
	}

?>
<?php

	trait Apache {
		public function defaultWebServerUser() {
			return 'www-data';
		}
		
		public function defaultWebServerUserMode() {
			return '0744';
		}
	}
 
?>
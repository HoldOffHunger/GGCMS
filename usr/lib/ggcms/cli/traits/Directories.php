<?php

	trait Directories {
		public function domainDirectories() {
			$domain = $this->domain;
			
			return array_merge(
				$this->webServingDirectories(),
				$this->statDirectories()
			);
		}
		
		public function webServingDirectories() {
			$domain = $this->domain;
			
			return [
				'/srv/ggcms/' . $domain . '/',
				'/srv/ggcms/' . $domain . '/www/',
				'/srv/ggcms/' . $domain . '/www/image/',
			];
		}
		
		public function statDirectories() {
			$domain = $this->domain;
			
			return [
				'/var/log/ggcms/' . $domain . '/',
				'/var/log/ggcms/' . $domain . '/stats/',
			];
		}
	}

?>
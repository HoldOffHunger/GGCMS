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

			/*
				Where the document formats -- RTF, TeX, SGML, PDF and the
				rest -- cache what they render: data/<format>/<site>/, a
				folder for the site in every format's folder.  Without its
				own, a site's first request for one of them is a 500, the
				format's fopen() returning false; AutonomyBooks' return
				policy and privacy pages were, on 5 November 2026.

				The formats are whatever folders data/ holds, as the old
				DBAdmin::CloneDataToNewDatabase() read them, so a format
				added later needs nothing here.  Not in domainDirectories(),
				which the retirer archives: this is a cache, and is
				discarded instead.
			*/

		public function documentCacheBase() {
			return GGCMS_DIR . 'data/';
		}

		public function documentCacheDirectories() {
			$base = $this->documentCacheBase();

			if(!is_dir($base)) {
				return [];
			}

			$directories = [];

			foreach(scandir($base) as $format) {
				if($format === '.' || $format === '..' || !is_dir($base . $format)) {
					continue;
				}

				$directories[] = $base . $format . '/' . $this->host . '/';
			}

			return $directories;
		}
	}

?>
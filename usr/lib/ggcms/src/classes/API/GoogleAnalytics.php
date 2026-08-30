<?php

			// https://analytics.google.com/analytics/web/

	/* fix eventually; cannot take handler directly; need to pass handler to script
	
			$base_code = $this->GoogleAnalyticsBaseCode();
			switch($this->handler['domain']->host) {
	
	*/

	class GoogleAnalytics {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$base_code = $this->GoogleAnalyticsBaseCode();
			
			switch($this->handler->domain->host) {
				default:
					$this->configgtag = FALSE;
					break;
				
				case 'copyleftlicense':
					$this->configgtag = $base_code . '4';
					break;
					
				case 'defianceart':
					$this->configgtag = $base_code . '14';
					break;
					
				case 'earthfluent':
					$this->configgtag = $base_code . '1';
					break;
				
				case 'listkeywords':
					$this->configgtag = $base_code . '5';
					break;
				
				case 'masereelgroup':
					$this->configgtag = $base_code . '15';
					break;
				
				case 'pronouncethat':
					$this->configgtag = $base_code . '6';
					break;
				
				case 'removeblanklines':
					$this->configgtag = $base_code . '7';
					break;
				
				case 'removeduplicatelines':
					$this->configgtag = $base_code . '8';
					break;
				
				case 'removespacing':
					$this->configgtag = $base_code . '9';
					break;
				
				case 'revoltlib':
					$this->configgtag = $base_code . '2';
					break;
					
				case 'revoltlink':
					$this->configgtag = $base_code . '3';
					break;
				
				case 'sortwords':
					$this->configgtag = $base_code . '10';
					break;
				
				case 'wordweight':
					$this->configgtag = $base_code . '11';
					break;
				
				case 'revoltsource':
					$this->configgtag = $base_code . '13';
					break;
			}
		}
		
		public function GoogleAnalyticsBaseCode() {
			return 'UA-106871476-';
		}
		
		public function DisplayHeaderBlock() {
			if($this->configgtag) {
				$this->DisplayHeaderBlock_header();
				$this->DisplayHeaderBlock_importTagManager();
				
				$this->DisplayHeaderBlock_startScript();
				
				$this->DisplayHeaderBlock_bodyScript();
				
				$this->DisplayHeaderBlock_endScript();
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function DisplayHeaderBlock_bodyScript() {
			print("\t\t" . 'window.dataLayer = window.dataLayer || [];' . "\n");
			print("\t\t" . 'function gtag(){dataLayer.push(arguments)};' . "\n");
			print("\t\t" . 'gtag(\'js\', new Date());' . "\n");
			print("\t\t" . 'gtag(\'config\', \'' . $this->configgtag . '\');' . "\n");
		}
		
		public function DisplayHeaderBlock_header() {
			print("\t\t" . '<!-- Global Site Tag (gtag.js) - Google Analytics -->' . "\n\n");
			
			return TRUE;
		}
		
		public function DisplayHeaderBlock_importTagManager() {
			print("\t" . '<script async src="https://www.googletagmanager.com/gtag/js?id=');
			print($this->configgtag);	// FIXME: Add HTMLtags or something?
			print('"></script>' . "\n\n");
			
			return TRUE;
		}
		
		public function DisplayHeaderBlock_startScript() {
			print("\t" . '<script type="text/javascript">' . "\n\n");
			
			return TRUE;
		}
		
		public function DisplayHeaderBlock_endScript() {
			print("\n\t" . '</script>' . "\n\n");
			
			return TRUE;
		}
	}

?>
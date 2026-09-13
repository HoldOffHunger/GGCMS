<?php

	class Domain {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$this->SetPrimaryDomain();
			
			if($_SERVER['HTTPS'] === 'on') {
				$this->protocol = 'https';
			} else {
				$this->protocol = 'http';
			}
			
			return $this;
		}
		
		public function SetPrimaryDomain() {
			if(array_key_exists('HTTP_X_FORWARDED_SERVER', $_SERVER)) {
				$server_name = $_SERVER['HTTP_X_FORWARDED_SERVER'];
			} elseif($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost' && $_GET['domain']) {
				$server_name = $_GET['domain'];
			} else {
				$server_name = $_SERVER['SERVER_NAME'];
			}
			
			$server_name_pieces = explode('.', $server_name);
			$server_name_pieces_count = count($server_name_pieces);
			for($i = 0; $i < $server_name_pieces_count; $i++) {
				$server_name_piece = $server_name_pieces[$i];
				if($server_name_piece !== 'www') {
					$server_base_name = $server_name_piece;
					$i = $server_name_pieces_count;
				}
			}
			
			$this->host = $server_base_name;
			
			$primary_domain = $server_name;
			
			if(preg_match("#^www\.#i", $primary_domain)) {
				$primary_domain = preg_replace("#^www\.#i", "", $primary_domain);
			}
			
			$this->primary_domain = $primary_domain;
			$this->primary_domain_lowercased = $primary_domain;
			
			return TRUE;
		}
		
		public function GetPrimaryDomain($args) {
			$primary_domain = '';
			
				/*
					The scheme follows the connection.  'secure' still forces
					https; 'insecure' no longer forces http.

					Every caller passing 'insecure' was building a public link --
					canonical, share URL, source attribution, image URLs in the
					RDF and OPDS exports -- and a link to a site answering on
					https should say https.  On 13 September 2026 revoltlib's and
					masereelgroup's canonical links pointed at http:// addresses
					that only redirect.  The 274 callers still passing it are
					harmless now and can drop it at leisure.
				*/

			if($args['secure']) {
				$primary_domain .= 'https://';
			} else {
				$primary_domain .= $this->HTTPProtocol();
			}
			
			if($args['www']) {
			#	$primary_domain .= 'www.';
			}
			
			if($args['domain']) {
				$primary_domain .= $args['domain'];
			} elseif($args['lowercased'] || $args['lowercase']) {
				$primary_domain .= $this->primary_domain_lowercased;
			} else {
				$primary_domain .= $this->primary_domain;
			}
			
			return $primary_domain;
		}
		
		public function GetHTTPSConnection() {
			return $_SERVER['HTTPS'];
		}

			/*
				"https://" when this request arrived over a secure connection,
				"http://" otherwise.  The one place the scheme is decided;
				GetPrimaryDomain and the format classes' HTTPProtocol() ask here.
				Some servers set HTTPS to "off" rather than leaving it unset.
			*/

		public function HTTPProtocol() {
			$https = $this->GetHTTPSConnection();

			if($https && strtolower((string) $https) !== 'off') {
				return 'https://';
			}

			return 'http://';
		}
		
		public function GetDomainFromURL($args) {
			$url = $args['url'];
			
			$url_pieces = explode('//', $url);
			$url_pieces_count = count($url_pieces);
			
			if($url_pieces_count > 1) {
				$protocol = $url_pieces[0];
				$url = $url_pieces[1];
				$url_pieces = explode('/', $url);
				$domain_net = $url_pieces[0];
				$domain_pieces = explode('.', $domain_net);
				$domain_pieces_count = count($domain_pieces);
				
				if($domain_pieces_count > 1) {
					$top_level_domain = $domain_pieces[$domain_pieces_count - 1];
					$domain = $domain_pieces[$domain_pieces_count - 2] . '.' . $top_level_domain;
					
					return [
						'domain'=>$domain,
						'topleveldomain'=>$top_level_domain,
					];
				}
			} else {
				return [
					'domain'=>$url,
					'topleveldomain'=>'',
				];
			}
			
			return '';
		}
		
		public function ValidateReferringWebsite() {
			if($this->ShouldValidateReferringWebsite()) {
				$referral_domain = $this->GetDomainFromURL(['url'=>$_SERVER['HTTP_REFERER']]);
				
				if(!$this->IsReferringWebsiteSelf(['referraldomain'=>$referral_domain])) {
					if(!$this->ValidateExternalReferralSite(['referraldomain'=>$referral_domain])) {
						return FALSE;
					}
				}
			}
			
			return TRUE;
		}
		
		public function ValidateExternalReferralSite($args) {
			$referral_domain = $args['referraldomain']['domain'];
			
			ggreq('classes/Networking/InvalidReferralDomains.php');
			
			$invalid_referrals = new InvalidReferralDomains();
			$invalid_referrals_hash = $invalid_referrals->GetInvalidReferralDomainsHash();
			
			if($invalid_referrals_hash[$referral_domain]) {
				return FALSE;
			}
			
			$referral_tld = $args['referraldomain']['topleveldomain'];
			
			$invalid_referrals_tlds_hash = $invalid_referrals->GetInvalidReferralTopLevelDomainsHash();
			
			if($invalid_referrals_tlds_hash[$referral_tld]) {
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function ShouldValidateReferringWebsite() {
			return $_SERVER['HTTP_REFERER'];
		}
		
		public function IsReferringWebsiteSelf($args) {
			$referral_domain = $args['referraldomain']['domain'];
			
			if(preg_match("/^" . $this->host . "\./", $referral_domain)) {
				return TRUE;
			}
			
			return FALSE;
		}
	}

?>
<?php

	class Domain {
		public $handler;
		public $protocol;
		public $host;
		public $primary_domain;
		public $primary_domain_lowercased;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$this->SetPrimaryDomain();
			
			if($_SERVER['HTTPS'] === 'on') {
				$this->protocol = 'https';
			} else {
				$this->protocol = 'http';
			}
		}
		
			// SetPrimaryDomain()
			// Tests: DomainTest::testSetPrimaryDomain()
			// Test file: tests/src/classes/Networking/DomainTest.php
			/*
				X-Forwarded-Server used to come first, and it is a request
				header: nginx passes it through untouched, and nothing in front
				of GGCMS sets it.  Whatever it named became the site -- its
				configuration was require()d and its database opened -- so one
				header could render revoltlib.com from earthfluent's database
				into revoltlib's page cache, or, reversed into a path, require
				any .php file on the host.  It is not read at all now.
				
				?domain= on localhost stays, for development, and only as a
				plain host name.
			*/
		
		public function SetPrimaryDomain() {
			if($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost' && $this->IsHostName(['name'=>$_GET['domain']])) {
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
			
			// IsHostName()
			// Tests: DomainTest::testIsHostName()
			// Test file: tests/src/classes/Networking/DomainTest.php
			/*
				Letters, digits, hyphens and single dots, as a DNS name is.  The
				domain becomes a configuration path and a database name, so
				nothing else may.
			*/
		
		public function IsHostName($args) {
			$name = (string) $args['name'];
			
			if((strlen($name) === 0) || (strlen($name) > 253)) {
				return FALSE;
			}
			
			return (preg_match('/\A[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\z/i', $name) === 1);
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
		
			// GetDomainFromURL()
			// Tests: DomainTest::testGetDomainFromURL()
			// Test file: tests/src/classes/Networking/DomainTest.php
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
					
					/*
						A host with no dot in it -- http://localhost/,
						http://intranet/ -- is a domain with no top level.  This
						used to return an empty string, and both callers index
						the result, so any visitor sending such a Referer got a
						TypeError and a 500 from Handler::ValidateReferrals().
					*/
				
				return [
					'domain'=>$domain_net,
					'topleveldomain'=>'',
				];
			} else {
				return [
					'domain'=>$url,
					'topleveldomain'=>'',
				];
			}
		}
		
			// ValidateReferringWebsite()
			// Tests: DomainTest::testValidateReferringWebsite()
			// Test file: tests/src/classes/Networking/DomainTest.php
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
		
			// ValidateExternalReferralSite()
			// Tests: DomainTest::testValidateExternalReferralSite()
			// Test file: tests/src/classes/Networking/DomainTest.php
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
		
			// ShouldValidateReferringWebsite()
			// Tests: DomainTest::testShouldValidateReferringWebsite()
			// Test file: tests/src/classes/Networking/DomainTest.php
		public function ShouldValidateReferringWebsite() {
			return $_SERVER['HTTP_REFERER'];
		}
		
			// IsReferringWebsiteSelf()
			// Tests: DomainTest::testIsReferringWebsiteSelf()
			// Test file: tests/src/classes/Networking/DomainTest.php
			/*
				This was the site's name followed by a dot, so revoltlib.xyz
				counted as revoltlib.com and skipped the blocklist -- a lookalike
				a referral spammer can register for pennies.  The whole domain,
				cut to its last two labels as the referrer's is, must match.
			*/
		
		public function IsReferringWebsiteSelf($args) {
			$referral_domain = (string)$args['referraldomain']['domain'];
			$own_domain = (string)$this->GetDomainFromURL(['url'=>'//' . $this->primary_domain])['domain'];
			
			return $own_domain !== '' && strcasecmp($referral_domain, $own_domain) === 0;
		}
	}

?>
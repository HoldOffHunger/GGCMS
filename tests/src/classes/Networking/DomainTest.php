<?php

	/*
		Domain's constructor reads the request, so these build one without
		it and set the one property -- host -- the referral checks read.
	*/

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\Attributes\RunInSeparateProcess;

	class DomainTest extends GGCMSTestCase {
		public function newDomain() {
			$domain = $this->newWithoutConstructor(['class'=>'Domain']);
			$domain->host = 'example';
			$domain->primary_domain = 'example.com';
			$domain->primary_domain_lowercased = 'example.com';

			return $domain;
		}

			/*
				X-Forwarded-Server named the site until September 2026, and it
				is a request header.  One request could render revoltlib.com
				from earthfluent's database, or require any .php on the host.
			*/

		public function testSetPrimaryDomain() {
			$cases = [
				[['HTTP_HOST'=>'revoltlib.com', 'SERVER_NAME'=>'revoltlib.com', 'HTTP_X_FORWARDED_SERVER'=>'earthfluent.com'], [], 'revoltlib.com', 'revoltlib'],
				[['HTTP_HOST'=>'www.revoltlib.com', 'SERVER_NAME'=>'www.revoltlib.com'], [], 'revoltlib.com', 'revoltlib'],
				[['HTTP_HOST'=>'localhost', 'SERVER_NAME'=>'localhost'], ['domain'=>'earthfluent.com'], 'earthfluent.com', 'earthfluent'],
				[['HTTP_HOST'=>'localhost', 'SERVER_NAME'=>'localhost'], ['domain'=>'php.x/../../etc/passwd'], 'localhost', 'localhost'],
				[['HTTP_HOST'=>'revoltlib.com', 'SERVER_NAME'=>'revoltlib.com'], ['domain'=>'earthfluent.com'], 'revoltlib.com', 'revoltlib'],
			];

			foreach($cases as [$server, $get, $expected_domain, $expected_host]) {
				$_SERVER = $server;
				$_GET = $get;

				$domain = $this->newDomain();
				$domain->SetPrimaryDomain();

				$this->assertSame($expected_domain, $domain->primary_domain, json_encode([$server, $get]));
				$this->assertSame($expected_host, $domain->host, 'the database label follows the domain');
			}
		}

		public function testIsHostName() {
			$domain = $this->newDomain();

			foreach(['revoltlib.com', 'news.example.co.uk', 'localhost', 'a-b.c9.io', 'EarthFluent.com'] as $name) {
				$this->assertTrue($domain->IsHostName(['name'=>$name]), $name);
			}

			foreach(['', '.', '..', 'a..b', '-a.com', 'a-.com', 'a.com.', '/etc/passwd', 'php.x/../../x', 'a b.com', "a\0.com", 'a_b.com', str_repeat('a.', 127) . 'aa', NULL] as $name) {
				$this->assertFalse($domain->IsHostName(['name'=>$name]), json_encode($name));
			}
		}

		public function testGetDomainFromURL() {
			$domain = $this->newDomain();

			$cases = [
				'https://www.example.com/a/b'=>['domain'=>'example.com', 'topleveldomain'=>'com'],
				'http://news.example.co.uk/'=>['domain'=>'co.uk', 'topleveldomain'=>'uk'],
				'example.com'=>['domain'=>'example.com', 'topleveldomain'=>''],
				'http://localhost/'=>['domain'=>'localhost', 'topleveldomain'=>''],
				'http://intranet'=>['domain'=>'intranet', 'topleveldomain'=>''],
				'http://'=>['domain'=>'', 'topleveldomain'=>''],
			];

			foreach($cases as $url => $expected) {
				$this->assertSame($expected, $domain->GetDomainFromURL(['url'=>$url]), $url);
			}
		}

			/*
				Every uncached request passes through this, from
				Handler::ValidateReferrals().  A Referer naming a host with no
				dot in it -- http://localhost/, http://intranet/ -- used to
				come back from GetDomainFromURL() as an empty string, and the
				next line indexed it: a TypeError, and a 500, for any visitor
				who sent that header.
			*/

		public static function referers() {
			return [
				'single label'=>['http://localhost/'],
				'single label, no path'=>['http://intranet'],
				'no host at all'=>['http://'],
				'this site'=>['https://www.example.com/x'],
				'another site'=>['https://search.example.org/?q=a'],
				'none'=>[NULL],
				'Ben\'s own sortwords'=>['https://sortwords.com/'],
				'Ben\'s own listkeywords'=>['https://www.listkeywords.com/tools'],
			];
		}

			/*
				"Error 403 - You done been smote."  Referral spam forges the
				Referer precisely so that it is seen, so refusing it by that
				Referer works on exactly the senders who forge one.  Each of
				these was let in by nothing and refused by the list.
			*/

		public static function smitten() {
			return [
				'a listed spam domain'=>['https://1-best-seo.com/'],
				'another, with a path'=>['http://worldwide-seo-services.com/offer?id=4'],
				'a listed spam domain, www'=>['https://www.makeinternetnoise.com/'],
				'the whole .xyz top-level domain'=>['https://anything-at-all.xyz/'],
				'the whole .pw top-level domain'=>['http://spam.example.pw/'],
				'a lookalike of this site, on a listed TLD'=>['https://example.xyz/'],
			];
		}

		#[DataProvider('smitten')]
		#[RunInSeparateProcess]
		public function testValidateReferringWebsite_Smitten($referer) {
			$_SERVER['HTTP_REFERER'] = $referer;

			$this->assertFalse($this->newDomain()->ValidateReferringWebsite());
		}

			/*
				ValidateExternalReferralSite() loads its list with ggreq(), which
				may happen once per process -- once per request, as on the web --
				so each referer runs in a process of its own.
			*/

		#[DataProvider('referers')]
		#[RunInSeparateProcess]
		public function testValidateReferringWebsite($referer) {
			if($referer === NULL) {
				unset($_SERVER['HTTP_REFERER']);
			} else {
				$_SERVER['HTTP_REFERER'] = $referer;
			}

			$this->assertTrue($this->newDomain()->ValidateReferringWebsite());
		}

		#[RunInSeparateProcess]
		public function testValidateExternalReferralSite() {
			$this->assertTrue($this->newDomain()->ValidateExternalReferralSite(['referraldomain'=>['domain'=>'wikipedia.org', 'topleveldomain'=>'org']]));
		}

		public function testShouldValidateReferringWebsite() {
			$_SERVER['HTTP_REFERER'] = 'https://example.org/';

			$this->assertSame('https://example.org/', $this->newDomain()->ShouldValidateReferringWebsite());
		}

		public function testIsReferringWebsiteSelf() {
			$domain = $this->newDomain();

			$this->assertTrue($domain->IsReferringWebsiteSelf(['referraldomain'=>['domain'=>'example.com']]));
			$this->assertFalse($domain->IsReferringWebsiteSelf(['referraldomain'=>['domain'=>'notexample.com']]));
			$this->assertFalse($domain->IsReferringWebsiteSelf(['referraldomain'=>['domain'=>'localhost']]));
			$this->assertFalse($domain->IsReferringWebsiteSelf(['referraldomain'=>['domain'=>'example.xyz']]), 'the same name on another TLD is not this site');
			$this->assertFalse($domain->IsReferringWebsiteSelf(['referraldomain'=>['domain'=>'example.pw']]));
			$this->assertTrue($domain->IsReferringWebsiteSelf(['referraldomain'=>['domain'=>'EXAMPLE.com']]), 'in any case');
		}
	}

?>

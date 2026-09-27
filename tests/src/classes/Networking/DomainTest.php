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

			return $domain;
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
			];
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
		}
	}

?>

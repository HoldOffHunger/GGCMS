<?php

	/*
		What ClientSideIncludes decides to put in a page's head.
	*/

	class ClientSideIncludesTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Format/HTML/ClientSideIncludes.php']);
		}

		public function newIncludes($args) {
			return new ClientSideIncludes([
				'desiredaction'=>'display',
				'scriptfile'=>$args['scriptfile'],
				'domainobject'=>NULL,
				'language'=>NULL,
				'googleapi'=>(object)['client_id'=>$args['clientid']],
				'globals'=>NULL,
			]);
		}

			/*
				Google's sign-in script goes only where someone signs in or
				out, and only on a site with a client.  Everywhere else it
				would tell Google about every reader's visit.
			*/

		public function testLoadsGoogleSignIn() {
			$client = 'fixture-client.apps.googleusercontent.com';

			$this->assertTrue($this->newIncludes(['scriptfile'=>'login', 'clientid'=>$client])->LoadsGoogleSignIn());
			$this->assertTrue($this->newIncludes(['scriptfile'=>'logout', 'clientid'=>$client])->LoadsGoogleSignIn());

			foreach(['view', 'modify', 'suggest', 'user-panel'] as $script) {
				$this->assertFalse($this->newIncludes(['scriptfile'=>$script, 'clientid'=>$client])->LoadsGoogleSignIn(), $script);
			}

			$this->assertFalse($this->newIncludes(['scriptfile'=>'login', 'clientid'=>''])->LoadsGoogleSignIn(), 'a site with no client');
			$this->assertFalse($this->newIncludes(['scriptfile'=>'login', 'clientid'=>NULL])->LoadsGoogleSignIn(), 'a site with no client');
		}

			/*
				A site's own build, else the default, else nothing -- and with
				nothing, the page links style.php's stylesheet as before.
			*/

		public function testBuiltStylesheet() {
			$includes = function($manifest, $host) {
				$object = new class([
					'desiredaction'=>'display',
					'scriptfile'=>'view',
					'domainobject'=>(object)['host'=>$host],
					'language'=>NULL,
					'googleapi'=>NULL,
					'globals'=>NULL,
				]) extends ClientSideIncludes {
					public $manifest = [];

					public function BuiltStylesheetManifest() {
						return $this->manifest;
					}
				};

				$object->manifest = $manifest;

				return $object;
			};

			$manifest = ['default'=>'default.aaaaaaaaaa.css', 'somesite'=>'somesite.bbbbbbbbbb.css'];

			$this->assertSame('/css/build/somesite.bbbbbbbbbb.css', $includes($manifest, 'somesite')->BuiltStylesheet());
			$this->assertSame('/css/build/default.aaaaaaaaaa.css', $includes($manifest, 'othersite')->BuiltStylesheet());
			$this->assertFalse($includes([], 'somesite')->BuiltStylesheet(), 'no build yet');
		}

			/*
				House ads only on view pages of a site that takes part and
				has said both what it is and what shape it is.  A public
				install's globals know none of this and get no script.
			*/

		public function testHouseAdsEnabled() {
			$includes = function($script, $globals) {
				return new ClientSideIncludes([
					'desiredaction'=>'display',
					'scriptfile'=>$script,
					'domainobject'=>NULL,
					'language'=>NULL,
					'googleapi'=>NULL,
					'globals'=>$globals,
				]);
			};

			$site = function($takes_part, $classification, $shape) {
				return new class($takes_part, $classification, $shape) {
					public function __construct(public $takes_part, public $classification, public $shape) {}
					public function HouseAds() { return $this->takes_part; }
					public function SiteClassification() { return $this->classification; }
					public function SiteShape() { return $this->shape; }
				};
			};

			$this->assertTrue($includes('view', $site(TRUE, 'revolutionary', 'multipage'))->HouseAdsEnabled());
			$this->assertTrue($includes('view', $site(TRUE, 'commercial', 'onepage'))->HouseAdsEnabled());

			$this->assertFalse($includes('modify', $site(TRUE, 'commercial', 'onepage'))->HouseAdsEnabled(), 'not a view page');
			$this->assertFalse($includes('view', $site(FALSE, 'revolutionary', 'multipage'))->HouseAdsEnabled(), 'opted out');
			$this->assertFalse($includes('view', $site(TRUE, 'unstated', 'multipage'))->HouseAdsEnabled(), 'unclassified');
			$this->assertFalse($includes('view', $site(TRUE, 'commercial', 'unstated'))->HouseAdsEnabled(), 'no shape');
			$this->assertFalse($includes('view', new stdClass)->HouseAdsEnabled(), 'a public install');
			$this->assertFalse($includes('view', NULL)->HouseAdsEnabled(), 'no globals');
		}

			/*
				A signed-in reader's page says so and lists what they closed;
				anyone else's carries nothing, so cached pages stay alike.
			*/

		public function testHouseAdsReaderAttributes() {
			$includes = function($userid, $rows) {
				$db_access = new class($rows) {
					public function __construct(public $rows) {}
					public function GetRecords($args) { return $this->rows; }
				};

				return new ClientSideIncludes([
					'desiredaction'=>'display',
					'scriptfile'=>'view',
					'domainobject'=>NULL,
					'language'=>NULL,
					'googleapi'=>NULL,
					'globals'=>NULL,
					'handler'=>(object)[
						'authentication'=>(object)['user_session'=>$userid ? ['User.id'=>$userid] : NULL],
						'db_access'=>$db_access,
					],
				]);
			};

			$this->assertSame(' data-reader="1" data-hidden="revoltlib sortwords"',
				$includes(7, [['AdCode'=>'revoltlib'], ['AdCode'=>'sortwords'], ['AdCode'=>'"><script>']])->HouseAdsReaderAttributes());
			$this->assertSame(' data-reader="1" data-hidden=""', $includes(7, [])->HouseAdsReaderAttributes());
			$this->assertSame('', $includes(0, [['AdCode'=>'revoltlib']])->HouseAdsReaderAttributes(), 'signed out');
			$this->assertSame('', $this->newIncludes(['scriptfile'=>'view', 'clientid'=>NULL])->HouseAdsReaderAttributes(), 'no handler');
		}
	}

?>

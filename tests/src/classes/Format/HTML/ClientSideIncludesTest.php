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
	}

?>

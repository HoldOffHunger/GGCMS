<?php

		// The handler, for the content stage: a logged-out request that security wants sent elsewhere
	class HandlerContentTestHandler extends Handler {
		public function CheckSecurity() {
			return TRUE;
		}

		public function Construct_UpgradeDBAccess() {
			return TRUE;
		}
	}

	class HandlerContentTestAuthentication {
		public $redirect = 'login';
		public $sent = [];

		public function RedirectToNewURL($args) {
			$this->sent[] = $args;

			return TRUE;
		}
	}

	class HandlerContentTestDatabase {
		public function DBEnd() {
			return TRUE;
		}
	}

		// A format that can send a visitor somewhere, as HTML can, and one that cannot, as JSON cannot
	class HandlerContentTestHTMLFormat {
		public $redirect_object;
	}

	class HandlerContentTestJSONFormat {
	}

	class HandlerContentTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/Handler/HandlerContent.php']);
		}

		public function content($args) {
			$handler = $this->newWithoutConstructor(['class'=>'HandlerContentTestHandler']);
			$handler->access = 0;
			$handler->db_access = new HandlerContentTestDatabase();
			$handler->authentication = new HandlerContentTestAuthentication();
			$handler->script = $args['format'];

			return new HandlerContent(['handler'=>$handler]);
		}

			/*
				A logged-out request for modify.json called RedirectToLogin()
				on nothing and answered 500, in every format but HTML, until 11
				November 2026.  Now the content stage declines, FALSE, and the
				request chain sends /modify.json on to /modify.php, where the
				HTML page handles the login.
			*/

		public function testALoggedOutRequestInAFormatThatCannotRedirectIsRefused() {
			$content = $this->content(['format'=>new HandlerContentTestJSONFormat()]);

			$this->assertFalse($content->HandleRequest_Content_Format());
			$this->assertSame([], $content->handler->authentication->sent, 'no redirect was attempted');
		}

		public function testALoggedOutRequestInHTMLIsStillSentToLogIn() {
			$format = new HandlerContentTestHTMLFormat();
			$format->redirect_object = new stdClass();

			$content = $this->content(['format'=>$format]);

			$this->assertTrue($content->HandleRequest_Content_Format());
			$this->assertCount(1, $content->handler->authentication->sent);
			$this->assertSame($format->redirect_object, $content->handler->authentication->sent[0]['redirect']);
		}
	}

?>

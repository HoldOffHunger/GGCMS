<?php

	/*
		HandlerRedirects is Handler's redirect-and-repair stage, moved out of
		Handler.php on 28 September 2026.  It keeps no state of its own, so
		each test builds a Handler without its constructor -- as HandlerTest
		does -- and hands it over.  These tests moved with their functions.
	*/

	class HandlerRedirectsTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/Handler/HandlerRedirects.php']);
		}

		public function newRedirects() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };

			return $handler->redirects = new HandlerRedirects(['handler'=>$handler]);
		}

		public function testRequestPath() {
			$cases = [
				'/a/b/'=>'/a/b/',
				'/view.pdf?mobilefriendly=1?mobilefriendly=1?action=browse'=>'/view.pdf?mobilefriendly=1&mobilefriendly=1&action=browse',
				'https://example.com/a/b?x=1'=>'/a/b?x=1',
				'https://example.com'=>'/',
				'a/b'=>'/a/b',
				''=>'/',
			];

			foreach($cases as $uri => $expected) {
				$_SERVER['REQUEST_URI'] = (string)$uri;

				$this->assertSame($expected, $this->newRedirects()->RequestPath(), 'uri ' . var_export((string)$uri, TRUE));
			}
		}

		public function testBadEndingCharacters() {
			$bad_endings = $this->newRedirects()->badEndingCharacters();

			foreach($bad_endings[2] as $ending => $bad) {
				$this->assertSame(2, mb_strlen($ending), var_export($ending, TRUE) . ' is filed under two characters');
			}

			foreach($bad_endings[1] as $ending => $bad) {
				$this->assertSame(1, mb_strlen($ending), var_export($ending, TRUE) . ' is filed under one character');
			}
		}

			/*
				A link pasted out of prose -- "see /some/page)." -- keeps its
				path and loses only the punctuation after it.
			*/

		public function testCleanseURL() {
			$redirects = $this->newRedirects();

			$cases = [
				'/some/page'=>'/some/page',
				'/some/page.'=>'/some/page',
				'/some/page)'=>'/some/page',
				'/some/page.)'=>'/some/page',
				'/some/page).'=>'/some/page',
				'/some/page"'=>'/some/page',
				'/some/page\'.'=>'/some/page',
				'/some/page).).'=>'/some/page',
				'/some/page]}'=>'/some/page',
			];

			foreach($cases as $url => $expected) {
				$this->assertSame($expected, $redirects->cleanseURL(['url'=>$url]), 'url ' . var_export($url, TRUE));
			}

			$this->assertSame('/p' . str_repeat('.', 1), $redirects->cleanseURL(['url'=>'/p' . str_repeat('.', 12)]), 'recursion stops at its depth limit');
		}

			/*
				nginx gives every dotless path a trailing slash before the
				engine sees it, so "/people)" pasted out of prose reached this
				as "/people)/", whose last character is a slash, and it 404ed.
				It looks past one trailing slash now, and keeps it.
			*/

		public function testHandleBadLinkRedirect() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };
			$handler->domain = (object)['primary_domain_lowercased'=>'example.com'];

			$redirects = new class(['handler'=>$handler]) extends HandlerRedirects {
				public function handleRedirect() { return TRUE; }
			};

			$_SERVER['HTTPS'] = 'on';
			$_GET = [];

			$cases = [
				'/people)'=>'https://example.com/people?stopredirect=1',
				'/people).'=>'https://example.com/people?stopredirect=1',
				'/people)/'=>'https://example.com/people/?stopredirect=1',
				'/a/b/people"/'=>'https://example.com/a/b/people/?stopredirect=1',
				'/people.)/'=>'https://example.com/people/?stopredirect=1',
			];

			foreach($cases as $uri => $expected) {
				$_SERVER['REQUEST_URI'] = $uri;
				$handler->redirect_url = NULL;

				$this->assertTrue($redirects->handleBadLinkRedirect(), $uri . ' is repaired');
				$this->assertSame($expected, $handler->redirect_url, $uri);
			}

			foreach(['/people/', '/people', '/', '//', '/people/view.php'] as $fine) {
				$_SERVER['REQUEST_URI'] = $fine;

				$this->assertFalse($redirects->handleBadLinkRedirect(), $fine . ' is left alone');
			}

			$_SERVER['REQUEST_URI'] = '/people)/';
			$_GET = ['stopredirect'=>'1'];

			$this->assertFalse($redirects->handleBadLinkRedirect(), 'never twice');
		}

		public function testRedirectsToSelf() {
			$_SERVER['HTTPS'] = 'on';
			$_SERVER['HTTP_HOST'] = 'example.com';
			$_SERVER['REQUEST_URI'] = '/a/b/?x=1';

			$redirects = $this->newRedirects();

			$this->assertTrue($redirects->RedirectsToSelf(['url'=>'https://example.com/a/b/?x=1']));
			$this->assertTrue($redirects->RedirectsToSelf(['url'=>'/a/b/?x=1']), 'a relative target is on this host and scheme');
			$this->assertTrue($redirects->RedirectsToSelf(['url'=>'https://EXAMPLE.com/a/b/?x=1']), 'hosts compare case-insensitively');
			$this->assertFalse($redirects->RedirectsToSelf(['url'=>'http://example.com/a/b/?x=1']), 'a scheme change is an upgrade, not a loop');
			$this->assertFalse($redirects->RedirectsToSelf(['url'=>'https://other.example/a/b/?x=1']));
			$this->assertFalse($redirects->RedirectsToSelf(['url'=>'https://example.com/a/b/']), 'a different query');
			$this->assertFalse($redirects->RedirectsToSelf(['url'=>'https://example.com/a/b']), 'a different path');
			$this->assertFalse($redirects->RedirectsToSelf(['url'=>'']));
		}
	}

?>

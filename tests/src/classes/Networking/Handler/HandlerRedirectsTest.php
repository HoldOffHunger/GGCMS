<?php

	/*
		HandlerRedirects is Handler's redirect-and-repair stage, moved out of
		Handler.php on 28 September 2026.  It keeps no state of its own, so
		each test builds a Handler without its constructor -- as HandlerTest
		does -- and hands it over.  These tests moved with their functions.
	*/

	require_once(GGCMS_DIR . 'classes/Networking/Handler/HandlerRedirects.php');

		// Records what a repair would have been, instead of rebuilding the request
	class HandlerRedirectsTestRepairs extends HandlerRedirects {
		public $repaired;

		public function RepairRequest($args) {
			$this->repaired = $args;

			return TRUE;
		}
	}

		// A query that answers one value for every parameter
	class HandlerRedirectsTestQuery {
		public $value;

		public function __construct($value) {
			$this->value = $value;
		}

		public function Parameter($args) {
			return $this->value;
		}
	}

		// Records what a permalink asked handleRedirect for, and repairs or sends as told
	class HandlerRedirectsTestPermalinks extends HandlerRedirects {
		public $sent = [];
		public $repair = TRUE;
		public $from_cache = TRUE;

		public function handleRedirect() {
			$this->sent[] = $this->handler->redirect_url;

			if($this->repair) {
				$this->handler->repair_count = 1;
				$this->handler->redirect_url = '';

				return FALSE;
			}

			return TRUE;
		}

		public function ServePermalinkFromCache() {
			return $this->from_cache;
		}
	}

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

			// The whole path is asked for before any bare code: a bare code is shared, a path is not
		public function testReservedCodeCandidates() {
			$redirects = $this->newRedirects();

			$this->assertSame(
				['dutch/nouns-part-1/', 'dutch/nouns-part-1', 'nouns-part-1'],
				$redirects->ReservedCodeCandidates(['request_uri'=>'/dutch/nouns-part-1/'])
			);

			$this->assertSame(
				['dutch/nouns-part-1/view.php', 'dutch/nouns-part-1', 'view', 'nouns-part-1'],
				$redirects->ReservedCodeCandidates(['request_uri'=>'/dutch/nouns-part-1/view.php'])
			);

			$candidates = $redirects->ReservedCodeCandidates(['request_uri'=>'/dutch/nouns-part-1/dog/']);
			$this->assertSame('dutch/nouns-part-1/dog', $candidates[1], 'a word under a moved lesson is found by its own old path');
			$this->assertLessThan(array_search('dog', $candidates, TRUE), array_search('dutch/nouns-part-1/dog', $candidates, TRUE));

			$this->assertSame(['old-code'], $redirects->ReservedCodeCandidates(['request_uri'=>'/old-code']));
			$this->assertSame([], $redirects->ReservedCodeCandidates(['request_uri'=>'/']));
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
			$handler->entry_path_handler = new class { public function EntryPathNamesEntries() { return FALSE; } };

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

			/*
				926 of revoltlink's entry codes end in a bracket, and stripping
				it as copy-paste debris turned every one of those pages into a
				404.  A path that already names entries is left as it came; a
				query string's end is still the query's to lose.
			*/

		public function testHandleBadLinkRedirectLeavesNamedEntries() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };
			$handler->domain = (object)['primary_domain_lowercased'=>'example.com'];
			$handler->entry_path_handler = new class { public function EntryPathNamesEntries() { return TRUE; } };

			$redirects = new class(['handler'=>$handler]) extends HandlerRedirects {
				public function handleRedirect() { return TRUE; }
			};

			$_SERVER['HTTPS'] = 'on';
			$_GET = [];

			foreach(['/groups/action-against-poisoning-(aap)/', '/groups/action-against-poisoning-(aap)', '/food/veggin\'/'] as $entry) {
				$_SERVER['REQUEST_URI'] = $entry;
				$handler->redirect_url = NULL;

				$this->assertFalse($redirects->handleBadLinkRedirect(), $entry . ' names an entry and is left alone');
				$this->assertNull($handler->redirect_url, $entry);
			}

			$_SERVER['REQUEST_URI'] = '/groups/action-against-poisoning-(aap)/?page=2)';
			$handler->redirect_url = NULL;

			$this->assertTrue($redirects->handleBadLinkRedirect(), 'a bracket on the query is still debris');
			$this->assertSame('https://example.com/groups/action-against-poisoning-(aap)/?page=2&stopredirect=1', $handler->redirect_url);
		}

			/*
				A repair is answered in place, so it never goes back through
				nginx, which gives every dotless path its trailing slash.  The
				engine reads /people as a script named people, so /people). was
				repaired to /people and 404ed while /people) -- slashed by
				nginx first -- was served.  The repaired path is slashed as
				nginx would have slashed it.
			*/

		public function testRepairInsteadOfRedirect() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };
			$handler->globals = new stdClass();

			$redirects = new HandlerRedirectsTestRepairs(['handler'=>$handler]);

			$_SERVER['REQUEST_METHOD'] = 'GET';
			$_SERVER['HTTPS'] = 'on';
			$_SERVER['HTTP_HOST'] = 'example.com';

			$cases = [
				'https://example.com/people?stopredirect=1'=>['/people/', 'stopredirect=1'],
				'/people'=>['/people/', NULL],
				'/a/b/c'=>['/a/b/c/', NULL],
				'/people/'=>['/people/', NULL],
				'/people/view.php?action=index'=>['/people/view.php', 'action=index'],
				'/robots.txt'=>['/robots.txt', NULL],
				'/'=>['/', NULL],
				'/a/b/c/d/e/f/g'=>['/a/b/c/d/e/f/g', NULL],
			];

			foreach($cases as $target => [$path, $query]) {
				$this->assertTrue($redirects->RepairInsteadOfRedirect(['url'=>$target]), $target);
				$this->assertSame(['path'=>$path, 'query'=>$query], $redirects->repaired, $target);
			}
		}

		public function testPermalinkId() {
			$redirects = $this->newRedirects();

			$cases = [
				['123', 123],
				[45460, 45460],
				['0', 0],
				['', 0],
				[NULL, 0],
				['abc', 0],
				['12abc', 0],
				['-5', 0],
				[' 5', 0],
				['1.5', 0],
				[['1'], 0],
			];

			foreach($cases as [$value, $expected]) {
				$redirects->handler->query = new HandlerRedirectsTestQuery($value);

				$this->assertSame($expected, $redirects->PermalinkId(), 'id ' . var_export($value, TRUE));
			}
		}

			/*
				A permalink is repaired to its entry and, once repaired, offered
				to the page cache.  Until 8 November 2026 its address was worked
				out and dropped, and the front page answered instead.
			*/

		public function testHandlePermalinkRedirect() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };

			$redirects = new HandlerRedirectsTestPermalinks(['handler'=>$handler]);

			$_GET = ['id'=>'45460'];
			$handler->permalink_url = '';

			$this->assertFalse($redirects->handlePermalinkRedirect(), 'no permalink, nothing to do');
			$this->assertSame([], $redirects->sent);

			$handler->permalink_url = 'https://example.com/english/myth/';
			$handler->repair_count = 0;

			$this->assertTrue($redirects->handlePermalinkRedirect(), 'repaired and served from the cache');
			$this->assertSame(['https://example.com/english/myth/'], $redirects->sent, 'id itself is not carried over');
			$this->assertSame('', $handler->permalink_url, 'acted on once');

			$redirects->sent = [];
			$redirects->from_cache = FALSE;
			$handler->permalink_url = 'https://example.com/english/myth/';
			$handler->repair_count = 0;
			$_GET = ['id'=>'45460', 'action'=>'browse', 'page'=>'2'];

			$this->assertFalse($redirects->handlePermalinkRedirect(), 'repaired but not cached: the chain carries on and renders it');
			$this->assertSame(['https://example.com/english/myth/?page=2'], $redirects->sent, 'other parameters travel, action does not');

			$redirects->sent = [];
			$redirects->repair = FALSE;
			$handler->permalink_url = 'https://example.com/english/myth/?action=Edit';
			$handler->repair_count = 0;
			$_GET = ['id'=>'45460', 'page'=>'2'];

			$this->assertTrue($redirects->handlePermalinkRedirect(), 'sent as a redirect where a site does not repair');
			$this->assertSame(['https://example.com/english/myth/?action=Edit&page=2'], $redirects->sent);
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

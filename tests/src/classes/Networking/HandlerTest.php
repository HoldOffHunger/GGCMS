<?php

	/*
		Handler's constructor opens a database and loads a site, so these
		build one without it and test the methods that need neither.
		Handler::__destruct() closes the database, so it is handed a
		connection that has nothing to close.
	*/

	use PHPUnit\Framework\Attributes\RunInSeparateProcess;

	class HandlerTest extends GGCMSTestCase {
		public function newHandler() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };

			return $handler;
		}

		public function testQueryStringNeedsRepair() {
			$handler = $this->newHandler();

			$this->assertTrue($handler->QueryStringNeedsRepair(['querystring'=>'mobilefriendly=1?action=browse']));
			$this->assertFalse($handler->QueryStringNeedsRepair(['querystring'=>'mobilefriendly=1&action=browse']));
			$this->assertFalse($handler->QueryStringNeedsRepair(['querystring'=>'']));
		}

		public function testRepairQueryString() {
			$this->assertSame('mobilefriendly=1&mobilefriendly=1&action=browse', $this->newHandler()->RepairQueryString(['querystring'=>'mobilefriendly=1?mobilefriendly=1?action=browse']));
		}

		public function testConstruct_RepairQueryString() {
			$handler = $this->newHandler();

			$_SERVER['QUERY_STRING'] = 'mobilefriendly=1?action=browse';
			$_GET = ['mobilefriendly'=>'1?action=browse'];

			$this->assertTrue($handler->Construct_RepairQueryString());
			$this->assertSame(['mobilefriendly'=>'1', 'action'=>'browse'], $_GET);
			$this->assertSame('mobilefriendly=1&action=browse', $_SERVER['QUERY_STRING']);
			$this->assertSame(['mobilefriendly'=>'1?action=browse'], $handler->original_get, 'what arrived is kept');

			$_SERVER['QUERY_STRING'] = 'a=1&b=2';

			$this->assertFalse($handler->Construct_RepairQueryString(), 'a well-formed query is left alone');
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

				$this->assertSame($expected, $this->newHandler()->RequestPath(), 'uri ' . var_export((string)$uri, TRUE));
			}
		}

		public function testBadEndingCharacters() {
			$bad_endings = $this->newHandler()->badEndingCharacters();

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
			$handler = $this->newHandler();

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
				$this->assertSame($expected, $handler->cleanseURL(['url'=>$url]), 'url ' . var_export($url, TRUE));
			}

			$this->assertSame('/p' . str_repeat('.', 1), $handler->cleanseURL(['url'=>'/p' . str_repeat('.', 12)]), 'recursion stops at its depth limit');
		}

			/*
				The site's configuration is require()d from a path built out of
				the domain.  A domain that reverses into ../ must never get
				there; it falls back to defaultglobals.  Runs alone because
				clonefrom.php declares classes.
			*/

		#[RunInSeparateProcess]
		public function testConstruct_Globals() {
			$handler = $this->newHandler();
			$handler->domain = new Domain(['handler'=>$handler]);
			$handler->domain->primary_domain_lowercased = $handler->ReverseDomainName(['domain'=>'../../../GGCMS/usr/lib/ggcms/cli/system/RepoDirectories']);

			$handler->Construct_Globals();

			$this->assertSame('defaultglobals', get_class($handler->globals), 'a path is not a site');
		}

		#[RunInSeparateProcess]
		public function testConstruct_GlobalsLoadsTheSite() {
			$handler = $this->newHandler();
			$handler->domain = new Domain(['handler'=>$handler]);
			$handler->domain->primary_domain_lowercased = 'revoltlib.com';

			$handler->Construct_Globals();

			$this->assertSame('globals', get_class($handler->globals), 'a real site loads its own configuration');
		}

		public function testRedirectsToSelf() {
			$_SERVER['HTTPS'] = 'on';
			$_SERVER['HTTP_HOST'] = 'example.com';
			$_SERVER['REQUEST_URI'] = '/a/b/?x=1';

			$handler = $this->newHandler();

			$this->assertTrue($handler->RedirectsToSelf(['url'=>'https://example.com/a/b/?x=1']));
			$this->assertTrue($handler->RedirectsToSelf(['url'=>'/a/b/?x=1']), 'a relative target is on this host and scheme');
			$this->assertTrue($handler->RedirectsToSelf(['url'=>'https://EXAMPLE.com/a/b/?x=1']), 'hosts compare case-insensitively');
			$this->assertFalse($handler->RedirectsToSelf(['url'=>'http://example.com/a/b/?x=1']), 'a scheme change is an upgrade, not a loop');
			$this->assertFalse($handler->RedirectsToSelf(['url'=>'https://other.example/a/b/?x=1']));
			$this->assertFalse($handler->RedirectsToSelf(['url'=>'https://example.com/a/b/']), 'a different query');
			$this->assertFalse($handler->RedirectsToSelf(['url'=>'https://example.com/a/b']), 'a different path');
			$this->assertFalse($handler->RedirectsToSelf(['url'=>'']));
		}
	}

?>

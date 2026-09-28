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

			/*
				The files beside a site's images went out with no Content-Type,
				which PHP calls text/html, from a name that kept the query
				string.  The site is pointed at this test's scratch directory,
				as data_isfile() reads GGCMS_DATA_DIR/<domain>/www/.
			*/

		public function testSrvLocalFile() {
			$www = $this->emptyScratchDirectory() . '/www/';
			mkdir($www . 'image/j', 0755, TRUE);
			foreach(['favicon.ico', 'safari-pinned-tab.svg', 'word-guess-game-demo.html', 'master-c.php', 'site.webmanifest', 'notes.unheardof', 'image/j/photo.jpg'] as $file) {
				file_put_contents($www . $file, 'x');
			}

			$handler = $this->newHandler();
			$handler->domain = (object)['primary_domain_lowercased'=>'tests/HandlerTest'];

			$icon = $handler->SrvLocalFile(['requesturi'=>'/favicon.ico']);
			$this->assertSame('image/vnd.microsoft.icon', $icon['mimetype']);
			$this->assertContains('X-Content-Type-Options: nosniff', $icon['headers']);
			$this->assertSame($www . 'favicon.ico', $icon['location']);

			$this->assertSame('image/vnd.microsoft.icon', $handler->SrvLocalFile(['requesturi'=>'/favicon.ico?v=2'])['mimetype'], 'the query string is not part of the name');
			$this->assertContains('Content-Security-Policy: sandbox', $handler->SrvLocalFile(['requesturi'=>'/safari-pinned-tab.svg'])['headers'], 'SVG can carry a script, so it is sandboxed');
			$this->assertSame('text/html', $handler->SrvLocalFile(['requesturi'=>'/word-guess-game-demo.html'])['mimetype'], 'the demos are still pages');
			$this->assertSame('application/manifest+json', $handler->SrvLocalFile(['requesturi'=>'/site.webmanifest'])['mimetype']);
			$this->assertSame('application/octet-stream', $handler->SrvLocalFile(['requesturi'=>'/notes.unheardof'])['mimetype'], 'an unknown type is a download, not a page');

			$this->assertFalse($handler->SrvLocalFile(['requesturi'=>'/master-c.php']), 'PHP source is never printed');
			$this->assertFalse($handler->SrvLocalFile(['requesturi'=>'/image/j/photo.jpg']), 'image/ is Image\'s');
			$this->assertFalse($handler->SrvLocalFile(['requesturi'=>'/not-there.ico']));
			$this->assertFalse($handler->SrvLocalFile(['requesturi'=>'/']));
		}
	}

?>

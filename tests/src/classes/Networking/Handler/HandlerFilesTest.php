<?php

	/*
		HandlerFiles is Handler's file stage, moved out of Handler.php on 28
		September 2026: requests answered from disk rather than rendered.  It
		keeps no state, so each test builds a Handler without its constructor
		and hands it over.  testSrvLocalFile() moved here with its function.
	*/

	class HandlerFilesTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/Handler/HandlerFiles.php']);
		}

		public function newFileHandler() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };

			return $handler->file_handler = new HandlerFiles(['handler'=>$handler]);
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

			$files = $this->newFileHandler();
			$files->handler->domain = (object)['primary_domain_lowercased'=>'tests/HandlerFilesTest'];

			$icon = $files->SrvLocalFile(['requesturi'=>'/favicon.ico']);
			$this->assertSame('image/vnd.microsoft.icon', $icon['mimetype']);
			$this->assertContains('X-Content-Type-Options: nosniff', $icon['headers']);
			$this->assertSame($www . 'favicon.ico', $icon['location']);

			$this->assertSame('image/vnd.microsoft.icon', $files->SrvLocalFile(['requesturi'=>'/favicon.ico?v=2'])['mimetype'], 'the query string is not part of the name');
			$this->assertContains('Content-Security-Policy: sandbox', $files->SrvLocalFile(['requesturi'=>'/safari-pinned-tab.svg'])['headers'], 'SVG can carry a script, so it is sandboxed');
			$this->assertSame('text/html', $files->SrvLocalFile(['requesturi'=>'/word-guess-game-demo.html'])['mimetype'], 'the demos are still pages');
			$this->assertSame('application/manifest+json', $files->SrvLocalFile(['requesturi'=>'/site.webmanifest'])['mimetype']);
			$this->assertSame('application/octet-stream', $files->SrvLocalFile(['requesturi'=>'/notes.unheardof'])['mimetype'], 'an unknown type is a download, not a page');

			$this->assertFalse($files->SrvLocalFile(['requesturi'=>'/master-c.php']), 'PHP source is never printed');
			$this->assertFalse($files->SrvLocalFile(['requesturi'=>'/image/j/photo.jpg']), 'image/ is Image\'s');
			$this->assertFalse($files->SrvLocalFile(['requesturi'=>'/not-there.ico']));
			$this->assertFalse($files->SrvLocalFile(['requesturi'=>'/']));
		}

		public function testIsScriptImage() {
			$files = $this->newFileHandler();

			foreach(['/image/a/b/photo.jpg'=>TRUE, '/x/y/icon.WEBP'=>TRUE, '/people/'=>FALSE, '/view.php'=>FALSE, '/notes.txt'=>FALSE] as $uri => $expected) {
				$_SERVER['REQUEST_URI'] = $uri;

				$this->assertSame($expected, $files->isScriptImage(), $uri);
			}
		}
	}

?>

<?php

	/*
		Everything here runs against a scratch cache root under
		GGCMS_WORK_DIR, set through GGCMS_PAGE_CACHE_ROOT -- the same override
		the warmer uses -- so nothing is ever written to the real volume.
	*/

	class PageCacheTest extends GGCMSTestCase {
		private $cache_root;
		private $saved_cache_root;

		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Cache/PageCache.php']);
			$this->emptyScratchDirectory();

			$this->saved_cache_root = getenv('GGCMS_PAGE_CACHE_ROOT');
			$this->cache_root = $this->scratchDirectory() . '/pages';

			putenv('GGCMS_PAGE_CACHE_ROOT=' . $this->cache_root);

			$_SERVER['HTTP_HOST'] = 'example.com';
			$_SERVER['REQUEST_URI'] = '/some/entry/';
			$_SERVER['REQUEST_METHOD'] = 'GET';
			$_SERVER['QUERY_STRING'] = '';
			$_COOKIE = [];
		}

		protected function tearDown(): void {
			putenv('GGCMS_PAGE_CACHE_ROOT' . ($this->saved_cache_root === FALSE ? '' : '=' . $this->saved_cache_root));

			parent::tearDown();
		}

		public function newPageCache($args) {
			$handler = new stdClass();
			$handler->script_format = $args['format'] ?? 'HTML';
			$handler->script_name = $args['script'] ?? 'view.php';
			$handler->error_404 = NULL;
			$handler->script = NULL;

			return new PageCache(['handler'=>$handler]);
		}

		public function htmlPage() {
			return '<html><body>' . str_repeat('A page worth caching. ', 200) . '</body></html>';
		}

		public function testCacheRootLocation() {
			$this->assertSame($this->cache_root, $this->newPageCache([])->CacheRootLocation(), 'the CLI override is honoured');

			putenv('GGCMS_PAGE_CACHE_ROOT=' . $this->cache_root . '/');

			$this->assertSame($this->cache_root, $this->newPageCache([])->CacheRootLocation(), 'a trailing slash is dropped');
		}

		public function testDomainCacheLocation() {
			$this->assertSame($this->cache_root . '/example.com', $this->newPageCache([])->DomainCacheLocation());

			$_SERVER['HTTP_HOST'] = '../etc';

			$this->assertFalse($this->newPageCache([])->DomainCacheLocation());
		}

		public function testSafeHost() {
			$cases = [
				'example.com'=>'example.com',
				'Example.COM:8080'=>'example.com',
				'www.revolt.example.co.uk'=>'www.revolt.example.co.uk',
				''=>FALSE,
				'..'=>FALSE,
				'a..b.com'=>FALSE,
				'-example.com'=>FALSE,
				'example.com.'=>FALSE,
				'exa mple.com'=>FALSE,
				'example.com/../x'=>FALSE,
				str_repeat('a', 254)=>FALSE,
			];

			foreach($cases as $host => $expected) {
				$_SERVER['HTTP_HOST'] = (string)$host;

				$this->assertSame($expected, $this->newPageCache([])->SafeHost(), 'host ' . var_export((string)$host, TRUE));
			}
		}

		public function testSafePath() {
			$cases = [
				'/'=>'/',
				'/some/entry/'=>'/some/entry/',
				'/some/entry/view.php?page=2'=>'/some/entry/view.php',
				'/css/view/display.css'=>'/css/view/display.css',
				'/a_b-c.d,e~f/'=>'/a_b-c.d,e~f/',
				''=>FALSE,
				'relative/path'=>FALSE,
				'/../etc/passwd'=>FALSE,
				'/a/%2e%2e/b'=>FALSE,
				'/a//b'=>FALSE,
				"/a\0b"=>FALSE,
				'/a b'=>FALSE,
				'/caf' . "\xc3\xa9"=>FALSE,
				'/' . str_repeat('a', 512)=>FALSE,
				str_repeat('/a', 25)=>FALSE,
				str_repeat('/a', 24)=>str_repeat('/a', 24),
			];

			foreach($cases as $uri => $expected) {
				$_SERVER['REQUEST_URI'] = (string)$uri;

				$this->assertSame($expected, $this->newPageCache([])->SafePath(), 'path ' . var_export((string)$uri, TRUE));
			}
		}

		public function testMaxPathLength() {
			$this->assertSame(512, $this->newPageCache([])->MaxPathLength());
		}

		public function testMaxPathDepth() {
			$this->assertSame(24, $this->newPageCache([])->MaxPathDepth());
		}

		public function testCacheLocation() {
			$domain = $this->cache_root . '/example.com';

			$_SERVER['REQUEST_URI'] = '/some/entry/';
			$this->assertSame($domain . '/some/entry/index.html', $this->newPageCache([])->CacheLocation());

			$_SERVER['REQUEST_URI'] = '/some/entry/view.php';
			$this->assertSame($domain . '/some/entry/view.php.html', $this->newPageCache([])->CacheLocation());

			$_SERVER['REQUEST_URI'] = '/css/view/display.css';
			$this->assertSame($domain . '/css/view/display.css.css', $this->newPageCache(['format'=>'CSS', 'script'=>'display.css'])->CacheLocation(), 'the suffix mirrors the format');

			$_SERVER['REQUEST_URI'] = '/../x';
			$this->assertFalse($this->newPageCache([])->CacheLocation());
		}

		public function testIsCacheable() {
			$this->assertTrue($this->newPageCache([])->IsCacheable(['output'=>$this->htmlPage()]));

			$_SERVER['REQUEST_METHOD'] = 'POST';

			$this->assertFalse($this->newPageCache([])->IsCacheable(['output'=>$this->htmlPage()]), 'any one failing condition refuses');
		}

		public function testIsCacheable_Method() {
			$this->assertTrue($this->newPageCache([])->IsCacheable_Method());

			foreach(['POST', 'HEAD', 'PUT'] as $method) {
				$_SERVER['REQUEST_METHOD'] = $method;

				$this->assertFalse($this->newPageCache([])->IsCacheable_Method(), $method);
			}
		}

		public function testIsCacheable_NoQueryString() {
			$this->assertTrue($this->newPageCache([])->IsCacheable_NoQueryString());

			$_SERVER['QUERY_STRING'] = 'page=2';

			$this->assertFalse($this->newPageCache([])->IsCacheable_NoQueryString());
		}

		public function testIsCacheable_Anonymous() {
			$this->assertTrue($this->newPageCache([])->IsCacheable_Anonymous());

			foreach(['loggedin', 'AuthenticationToken', 'language'] as $cookie) {
				$_COOKIE = [$cookie=>''];

				$this->assertFalse($this->newPageCache([])->IsCacheable_Anonymous(), 'even an empty ' . $cookie . ' cookie refuses');
			}
		}

		public function testCacheableScripts() {
			$this->assertSame(['view.php'], $this->newPageCache([])->CacheableScripts());
		}

		public function testCacheableFormats() {
			$this->assertSame(['HTML'=>'html', 'CSS'=>'css', 'BRF'=>'brf'], $this->newPageCache([])->CacheableFormats());
		}

		public function testBrowserCacheHeader() {
			$this->assertSame('Cache-Control: public, max-age=14400', $this->newPageCache([])->BrowserCacheHeader());
		}

		public function testCacheSuffix() {
			$this->assertSame('brf', $this->newPageCache(['format'=>'BRF'])->CacheSuffix());
		}

		public function testIsCacheable_Script() {
			$this->assertTrue($this->newPageCache([])->IsCacheable_Script());
			$this->assertFalse($this->newPageCache(['script'=>'modify.php'])->IsCacheable_Script(), 'only view.php for HTML');
			$this->assertTrue($this->newPageCache(['format'=>'CSS', 'script'=>'display.css'])->IsCacheable_Script(), 'a stylesheet is never judged by its script name');
			$this->assertFalse($this->newPageCache(['format'=>'PDF'])->IsCacheable_Script(), 'an unlisted format');

			$page_cache = $this->newPageCache([]);
			$page_cache->handler->error_404 = TRUE;

			$this->assertFalse($page_cache->IsCacheable_Script(), 'a 404');

			$page_cache = $this->newPageCache([]);
			$page_cache->handler->script = new stdClass();
			$page_cache->handler->script->script = new class { public function isSecure() { return TRUE; } };

			$this->assertFalse($page_cache->IsCacheable_Script(), 'a secure script never touches the disk');
		}

		public function testIsCacheable_Response() {
			$this->assertTrue($this->newPageCache([])->IsCacheable_Response(), 'under the CLI an untouched response code counts as 200');
		}

		public function testMinimumCacheableLength() {
			$this->assertSame(2048, $this->newPageCache([])->MinimumCacheableLength());
			$this->assertSame(64, $this->newPageCache(['format'=>'CSS'])->MinimumCacheableLength());
		}

		public function testIsCacheable_Output() {
			$this->assertTrue($this->newPageCache([])->IsCacheable_Output(['output'=>$this->htmlPage()]));
			$this->assertFalse($this->newPageCache([])->IsCacheable_Output(['output'=>'<html></html>']), 'too short to be a real page');
			$this->assertFalse($this->newPageCache([])->IsCacheable_Output(['output'=>str_repeat('x', 4096)]), 'HTML that never closes');

			$css = str_repeat('body { color: black; } ', 10);

			$this->assertTrue($this->newPageCache(['format'=>'CSS'])->IsCacheable_Output(['output'=>$css]));
			$this->assertFalse($this->newPageCache(['format'=>'CSS'])->IsCacheable_Output(['output'=>"\n<br />\n<b>Warning</b>: " . $css]), 'a stylesheet that starts with a PHP error');
		}

		public function testWriteCache() {
			$page_cache = $this->newPageCache([]);

			$this->assertTrue($page_cache->WriteCache(['output'=>$this->htmlPage()]));

			$location = $page_cache->CacheLocation();

			$this->assertSame($this->htmlPage(), file_get_contents($location));
			$this->assertSame($this->htmlPage(), gzdecode(file_get_contents($location . '.gz')), 'the compressed twin holds the same page');
			$this->assertSame([], glob(dirname($location) . '/*.tmp'), 'no temporary file is left behind');
			$this->assertFalse($page_cache->WriteCache(['output'=>'short']), 'an uncacheable page is not written');
		}

		public function testCompressedLocation() {
			$this->assertSame('/x/index.html.gz', $this->newPageCache([])->CompressedLocation(['location'=>'/x/index.html']));
		}

		public function testWriteCompressed() {
			$location = $this->scratchDirectory() . '/compressed.html';

			$this->assertTrue($this->newPageCache([])->WriteCompressed(['location'=>$location, 'output'=>'hello']));
			$this->assertSame('hello', gzdecode(file_get_contents($location . '.gz')));
		}

		public function testFlushPage() {
			$page_cache = $this->newPageCache([]);
			$page_cache->WriteCache(['output'=>$this->htmlPage()]);
			$location = $page_cache->CacheLocation();

			$_SERVER['QUERY_STRING'] = 'action=like';
			$_SERVER['REQUEST_URI'] = '/some/entry/?action=like';

			$this->assertTrue($page_cache->FlushPage([]), 'a like on the page flushes the page itself');
			$this->assertFileDoesNotExist($location);
			$this->assertFileDoesNotExist($location . '.gz', 'the .gz never outlives its page');
			$this->assertTrue($page_cache->FlushPage([]), 'flushing what is not cached is success');
		}

		public function testFlushDomain() {
			$page_cache = $this->newPageCache([]);
			$page_cache->WriteCache(['output'=>$this->htmlPage()]);

			$this->assertTrue($page_cache->FlushDomain([]));
			$this->assertDirectoryDoesNotExist($this->cache_root . '/example.com');
			$this->assertCount(2, $page_cache->RemovedFiles(), 'the page and its .gz');
			$this->assertFalse($page_cache->FlushDomain(['domain'=>'..']), 'never above the cache root');
			$this->assertFalse($page_cache->FlushDomain(['domain'=>'']), 'never the cache root itself');
		}

		public function testFlushAll() {
			foreach(['one.example', 'two.example'] as $host) {
				$_SERVER['HTTP_HOST'] = $host;
				$this->newPageCache([])->WriteCache(['output'=>$this->htmlPage()]);
			}

			$this->assertTrue($this->newPageCache([])->FlushAll());
			$this->assertSame(['.', '..'], scandir($this->cache_root), 'the root survives, empty');
		}

		public function testRemovedFiles() {
			$this->assertSame([], $this->newPageCache([])->RemovedFiles());
		}

		public function testRemoveDirectory() {
			$outside = $this->scratchDirectory() . '/outside';
			@mkdir($outside);
			touch($outside . '/keep.txt');

			$this->assertFalse($this->newPageCache([])->RemoveDirectory(['location'=>$outside]), 'refuses anything outside the cache root');
			$this->assertFileExists($outside . '/keep.txt');

			@mkdir($this->cache_root . '/linked', 0755, TRUE);
			@symlink($outside, $this->cache_root . '/linked/escape');

			$this->assertTrue($this->newPageCache([])->RemoveDirectory(['location'=>$this->cache_root . '/linked']));
			$this->assertFileExists($outside . '/keep.txt', 'a symlink inside the tree is unlinked, never followed');
		}
	}

?>

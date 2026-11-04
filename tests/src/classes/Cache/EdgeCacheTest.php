<?php

	/*
		Only the pure parts.  Nothing here talks to Cloudflare: with no token
		set, every method that would must return FALSE without trying.
	*/

	class EdgeCacheTest extends GGCMSTestCase {
		private $saved_token;

		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Cache/EdgeCache.php']);

			$this->saved_token = getenv('GGCMS_CLOUDFLARE_TOKEN');
			putenv('GGCMS_CLOUDFLARE_TOKEN');
		}

		protected function tearDown(): void {
			putenv('GGCMS_CLOUDFLARE_TOKEN' . ($this->saved_token === FALSE ? '' : '=' . $this->saved_token));

			parent::tearDown();
		}

		public function testToken() {
			$edge_cache = new EdgeCache([]);

			if(get_cfg_var('ggcms.cloudflare_token') === FALSE) {
				$this->assertFalse($edge_cache->Token());

				putenv('GGCMS_CLOUDFLARE_TOKEN=not a token');
				$this->assertFalse($edge_cache->Token());
			}

			putenv('GGCMS_CLOUDFLARE_TOKEN=abcdefghijklmnopqrstuvwxyz_0123456789-AB');
			$this->assertSame('abcdefghijklmnopqrstuvwxyz_0123456789-AB', $edge_cache->Token());
		}

		public function testZoneName() {
			$edge_cache = new EdgeCache([]);

			$this->assertSame('revoltlib.com', $edge_cache->ZoneName(['domain'=>'revoltlib.com']));
			$this->assertSame('revoltlib.com', $edge_cache->ZoneName(['domain'=>'www.revoltlib.com']));
			$this->assertSame('revoltlib.com', $edge_cache->ZoneName(['domain'=>' WWW.RevoltLib.com ']));

			$this->assertFalse($edge_cache->ZoneName(['domain'=>'']));
			$this->assertFalse($edge_cache->ZoneName(['domain'=>'localhost']));
			$this->assertFalse($edge_cache->ZoneName(['domain'=>'revoltlib.com/../x']));
			$this->assertFalse($edge_cache->ZoneName(['domain'=>FALSE]));
		}

		public function testPageURLs() {
			$edge_cache = new EdgeCache([]);

			$this->assertSame(
				['https://revoltlib.com/people/', 'https://www.revoltlib.com/people/'],
				$edge_cache->PageURLs(['domain'=>'www.revoltlib.com', 'path'=>'/people/'])
			);

			$this->assertFalse($edge_cache->PageURLs(['domain'=>'revoltlib.com', 'path'=>FALSE]));
			$this->assertFalse($edge_cache->PageURLs(['domain'=>'revoltlib.com', 'path'=>'people/']));
			$this->assertFalse($edge_cache->PageURLs(['domain'=>'nope', 'path'=>'/people/']));
		}

		public function testNoTokenMeansNoRequest() {
			if(get_cfg_var('ggcms.cloudflare_token') !== FALSE) {
				$this->markTestSkipped('php.ini names a Cloudflare token here.');
			}

			$edge_cache = new EdgeCache([]);

			$this->assertFalse($edge_cache->PurgeDomain(['domain'=>'revoltlib.com']));
			$this->assertFalse($edge_cache->PurgePage(['domain'=>'revoltlib.com', 'path'=>'/people/']));
			$this->assertFalse($edge_cache->ZoneNames());
		}
	}

?>

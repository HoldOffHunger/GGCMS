<?php

	/*
		PageCacheWarmer's path filters.  Enumerating the paths needs a site's
		database; deciding which of them to keep does not.
	*/

	class PageCacheWarmerTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireCLI(['file'=>'classes/Cache/PageCacheWarmer.php']);
		}

		public function newWarmer($args) {
			return new PageCacheWarmer(['argv'=>array_merge(['warm_page_cache.php'], $args['arguments'])]);
		}

			/*
				On RevoltLib a book is two codes deep, under its section; the
				section's own document would be every book in it at once.
			*/

		public function testIsAtDepth() {
			$warmer = $this->newWarmer(['arguments'=>['--depth=2']]);

			$this->assertTrue($warmer->IsAtDepth('/anarchism/mutual-aid/'));
			$this->assertFalse($warmer->IsAtDepth('/anarchism/'));
			$this->assertFalse($warmer->IsAtDepth('/anarchism/mutual-aid/chapter-1/'));
		}

		public function testKeepsItsOwnFile() {
			$this->assertTrue($this->newWarmer(['arguments'=>['--format=pdf']])->KeepsItsOwnFile());
			$this->assertFalse($this->newWarmer(['arguments'=>['--format=brf']])->KeepsItsOwnFile());
		}
	}

?>

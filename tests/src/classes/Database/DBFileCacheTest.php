<?php

	require_once(GGCMS_DIR . 'classes/Database/DBFileCache.php');

		/*
			The row cache, rooted in scratch rather than on the volume, for a
			handler that knows only its domain.
		*/

	class DBFileCacheTestSubject extends DBFileCache {
		public $scratch_root;

		public function DBFileCacheLocation() {
			return $this->scratch_root;
		}
	}

	class DBFileCacheTestHandler {
		use ReverseDNSNotation;

		public $domain;
	}

	class DBFileCacheTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->emptyScratchDirectory();
		}

		public function newCache() {
			$handler = new DBFileCacheTestHandler();
			$handler->domain = new stdClass();
			$handler->domain->primary_domain_lowercased = 'example.com';

			$cache = new DBFileCacheTestSubject(['handler'=>$handler]);
			$cache->scratch_root = $this->scratchDirectory() . '/' . $this->name();

			@mkdir($cache->scratch_root . '/dictionaries', 0755, TRUE);

			return $cache;
		}

		public function testDBFileCacheLocation() {
			$this->assertSame('/mnt/nyc01/ggcms_cache/mysql_db_file_cache', (new DBFileCache(['handler'=>NULL]))->DBFileCacheLocation());
		}

		public function testDomainFileCacheLocation() {
			$cache = $this->newCache();

			$this->assertSame($cache->scratch_root . '/com.example', $cache->DomainFileCacheLocation(), 'reverse-DNS, like everything keyed by domain');
		}

		public function testCacheLocation() {
			$cache = $this->newCache();

			$this->assertSame($cache->scratch_root . '/com.example/Entry', $cache->CacheLocation(['type'=>'Entry', 'subtype'=>'']));
			$this->assertSame($cache->scratch_root . '/com.example/Entry/Tag', $cache->CacheLocation(['type'=>'Entry', 'subtype'=>'Tag']));
			$this->assertSame($cache->scratch_root . '/dictionaries', $cache->CacheLocation(['type'=>'', 'subtype'=>'', 'directory'=>'dictionaries']));
		}

		public function testWriteCache() {
			$cache = $this->newCache();

			$cache->WriteCache(['directory'=>'dictionaries', 'field'=>'Term', 'arguments'=>['APPLE', 'PEAR'], 'data'=>[['id'=>1, 'Term'=>'APPLE'], ['id'=>2, 'Term'=>'APPLE'], ['id'=>3, 'Term'=>'PEAR']]]);

			$this->assertSame([['id'=>1, 'Term'=>'APPLE'], ['id'=>2, 'Term'=>'APPLE']], json_decode(file_get_contents($cache->scratch_root . '/dictionaries/APPLE'), TRUE), 'one file per value of the field');
			$this->assertFileExists($cache->scratch_root . '/dictionaries/PEAR');
		}

		public function testWriteCache_Item() {
			$cache = $this->newCache();
			$directory = $cache->scratch_root . '/dictionaries/';

			$this->assertTrue($cache->WriteCache_Item(['full_directory'=>$directory, 'argument'=>'FIG', 'argument_data'=>[['id'=>7]]]));
			$this->assertSame('[{"id":7}]', file_get_contents($directory . 'FIG'));

			$cache->WriteCache_Item(['full_directory'=>$directory, 'argument'=>'FIG', 'argument_data'=>[['id'=>8]]]);
			$this->assertSame('[{"id":7}]', file_get_contents($directory . 'FIG'), 'writes are create-only');

			$cache->WriteCache_Item(['full_directory'=>$directory, 'argument'=>'../ESCAPED', 'argument_data'=>[['id'=>9]]]);
			$this->assertFileDoesNotExist($cache->scratch_root . '/ESCAPED', 'never outside its directory');
		}

		public function testWriteCache_Blank() {
			$cache = $this->newCache();
			$blanks_file = $cache->scratch_root . '/Tag_blanks.txt';

			$this->assertFalse($cache->WriteCache_Blank(['blanks'=>FALSE, 'new_blanks'=>[5=>TRUE], 'blanks_file'=>$blanks_file]), 'no blanks file means the type keeps none');

			file_put_contents($blanks_file, "3\n9");
			$cache->WriteCache_Blank(['blanks'=>['3', '9'], 'new_blanks'=>[5=>TRUE, 12=>TRUE, 9=>TRUE], 'blanks_file'=>$blanks_file]);

			$this->assertSame("3\n5\n9\n12", file_get_contents($blanks_file), 'merged in order, and 9 once');
		}

		public function testReadCache() {
			$cache = $this->newCache();

			$cache->WriteCache(['directory'=>'dictionaries', 'field'=>'Term', 'arguments'=>['APPLE'], 'data'=>[['id'=>1, 'Term'=>'APPLE']]]);

			$this->assertSame([['id'=>1, 'Term'=>'APPLE']], $cache->ReadCache(['directory'=>'dictionaries', 'arguments'=>['APPLE', 'APPLE']]));
			$this->assertFalse($cache->ReadCache(['directory'=>'dictionaries', 'arguments'=>['APPLE', 'KIWI']]), 'one missing argument is a miss for all');
		}

			/*
				view.php looks ?search= up in this cache.  ?search=../../secret
				read the JSON two directories above it until September 2026.
			*/

		public function testReadCache_Item() {
			$cache = $this->newCache();

			file_put_contents(dirname($cache->scratch_root) . '/SECRET', '[{"id":1,"Term":"LEAKED"}]');

			$this->assertFalse($cache->ReadCache(['directory'=>'dictionaries', 'arguments'=>[strtoupper('../../secret')]]));
			$this->assertSame([], $cache->ReadCache_Item(['full_directory'=>$cache->scratch_root . '/dictionaries/', 'argument'=>'39', 'blanks'=>['38', '39']]), 'a known blank is an empty hit');
		}

		public function testDeleteCache() {
			$cache = $this->newCache();

			$cache->WriteCache(['directory'=>'dictionaries', 'field'=>'Term', 'arguments'=>['APPLE'], 'data'=>[['id'=>1, 'Term'=>'APPLE']]]);
			file_put_contents($cache->scratch_root . '/KEEP', 'x');

			$this->assertSame(1, $cache->DeleteCache(['directory'=>'dictionaries', 'arguments'=>['APPLE', '../KEEP']]));
			$this->assertFileExists($cache->scratch_root . '/KEEP', 'a delete never leaves its directory either');
		}

		public function testDeleteCache_PathSegment() {
			$cache = $this->newCache();
			$directory = $cache->CacheLocation(['type'=>'ggcms_RecordTree', 'subtype'=>'']);
			@mkdir($directory, 0755, TRUE);

			foreach(['anarchism%2Fgod-and-the-state', 'anarchism', 'people%2Fgandhi'] as $key) {
				file_put_contents($directory . '/' . $key, '[]');
			}

			$this->assertSame(2, $cache->DeleteCache_PathSegment(['type'=>'ggcms_RecordTree', 'subtype'=>'', 'segment'=>'anarchism']));
			$this->assertFileExists($directory . '/people%2Fgandhi');
		}

		public function testRemoveBomUtf8() {
			$cache = $this->newCache();

			$this->assertSame("1\n2", $cache->removeBomUtf8("\xEF\xBB\xBF1\n2"));
			$this->assertSame('plain', $cache->removeBomUtf8('plain'));
		}

		public function testReadCache_blanks() {
			$cache = $this->newCache();
			$directory = $cache->scratch_root . '/Tag';

			$this->assertFalse($cache->ReadCache_blanks(['full_directory'=>$directory]));

			file_put_contents($directory . '_blanks.txt', "\xEF\xBB\xBF" . "3\n9");

			$this->assertFalse($cache->ReadCache_blanks(['full_directory'=>$directory]), 'the answer is kept for the life of the request');
			$this->assertSame(['3', '9'], $this->newCache()->ReadCache_blanks(['full_directory'=>$directory]), 'a fresh request reads it, without its BOM');
		}

		public function testFileNameMax() {
			$this->assertSame(128, $this->newCache()->FileNameMax());
		}

		public function testIsCacheFileName() {
			$cache = $this->newCache();

			foreach(['APPLE', '39', 'anarchism%2Fgod-and-the-state', 'É'] as $name) {
				$this->assertTrue($cache->IsCacheFileName(['filename'=>$name]), $name);
			}

			foreach(['', '.', '..', '../X', 'A/B', "A\0B", str_repeat('a', 128)] as $name) {
				$this->assertFalse($cache->IsCacheFileName(['filename'=>$name]), json_encode($name));
			}
		}

		public function testFindElementInSortedArray() {
			$cache = $this->newCache();

			mt_srand(20260927);

			for($trial = 0; $trial < 500; $trial++) {
				$array = $this->randomSortedArray();
				$element = mt_rand(0, 60);

				$this->assertSame(in_array($element, $array, TRUE), $cache->findElementInSortedArray(['array'=>$array, 'element'=>$element]), json_encode([$array, $element]));
			}
		}

			/*
				One insert in four used to land in the wrong place.
			*/

		public function testInsertElementIntoSortedArray() {
			$cache = $this->newCache();

			mt_srand(20260927);

			for($trial = 0; $trial < 500; $trial++) {
				$array = $this->randomSortedArray();
				$element = mt_rand(0, 60);

				$this->assertSame($this->referenceInsert(['array'=>$array, 'element'=>$element]), $cache->insertElementIntoSortedArray(['array'=>$array, 'element'=>$element]), json_encode([$array, $element]));
			}

			$this->assertSame(['38', '39', '40'], $cache->insertElementIntoSortedArray(['array'=>['38', '39', '40'], 'element'=>39]), 'a blank read back as a string is the same id');
		}

		public function testInsertArrayIntoSortedArray() {
			$this->assertSame([1, 2, 3, 5, 8, 13], $this->newCache()->insertArrayIntoSortedArray(['arr1'=>[2, 5, 13], 'arr2'=>[8, 1, 3, 5]]));
		}

		public function randomSortedArray() {
			$array = [];

			for($i = mt_rand(0, 40); $i > 0; $i--) {
				$array[] = mt_rand(0, 60);
			}

			$array = array_values(array_unique($array));
			sort($array);

			return $array;
		}

		public function referenceInsert($args) {
			$array = $args['array'];
			$array[] = $args['element'];
			$array = array_values(array_unique($array));
			sort($array);

			return $array;
		}
	}

?>

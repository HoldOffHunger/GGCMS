<?php

	require_once(GGCMS_DIR . 'classes/Database/DBAccess.php');

		/*
			A prepared statement that runs, fails, or throws as told.
		*/

	class DBAccessTestStatement {
		public $outcome;

		public function __construct($outcome) {
			$this->outcome = $outcome;
		}

		public function execute() {
			if($this->outcome instanceof Throwable) {
				throw $this->outcome;
			}

			return $this->outcome;
		}
	}

		/*
			A row cache that records what it was told to delete.
		*/

	class DBAccessTestFileCache {
		public $deleted = [];
		public $deleted_all = [];

		public function DeleteCache($args) {
			$this->deleted[$args['type']][] = $args['arguments'];
			return TRUE;
		}

		public function DeleteCache_All($args) {
			$this->deleted_all[$args['type']] = ($this->deleted_all[$args['type']] ?? 0) + 1;
			return TRUE;
		}

		public function DeleteCache_Subtypes($args) {
			return [];
		}

		public function DeleteCache_PathSegment($args) {
			return TRUE;
		}
	}

	class DBAccessTest extends GGCMSTestCase {
		public function testExecuteStatement() {
			$db_access = $this->newWithoutConstructor(['class'=>'DBAccess']);

			$unholdable = new mysqli_sql_exception('Conversion from collation utf8mb4_0900_ai_ci into utf8mb3_general_ci impossible for parameter', 3988);

			$this->assertTrue($db_access->ExecuteStatement(['statement'=>new DBAccessTestStatement(TRUE), 'query'=>'SELECT 1']));
			$this->assertFalse($db_access->ExecuteStatement(['statement'=>new DBAccessTestStatement(FALSE), 'query'=>'SELECT 1']));

			$this->assertNull($db_access->ExecuteStatement(['statement'=>new DBAccessTestStatement($unholdable), 'query'=>"\n\tselect id FROM User WHERE Username = ?"]), 'a lookup for what no row can hold matches nothing');
			$this->assertFalse($db_access->ExecuteStatement(['statement'=>new DBAccessTestStatement($unholdable), 'query'=>'INSERT INTO Comment (Comment) VALUES (?)']), 'a write that cannot be stored is a failed write');

			$this->expectException(mysqli_sql_exception::class);
			$db_access->ExecuteStatement(['statement'=>new DBAccessTestStatement(new mysqli_sql_exception('Column cannot be null', 1048)), 'query'=>'SELECT 1']);
		}

			// An import flushes after every entry; each flush must be of what came since the last
		public function testFlushRowCacheNowForgetsWhatItFlushed() {
			$db_access = $this->newWithoutConstructor(['class'=>'DBAccess']);
			$db_access->db_file_cache = new DBAccessTestFileCache();
			$db_access->row_cache_dirty = TRUE;		# already registered, so no shutdown flush is queued by the test

			$db_access->MarkRowCacheDirty(['type'=>'Assignment', 'update'=>['id'=>1, 'Parentid'=>7, 'Childid'=>12], 'where'=>['id'=>1]]);
			$db_access->FlushRowCacheNow();

			$this->assertContains(12, $db_access->db_file_cache->deleted['ggcms_ChildRecordCount'][0]);
			$this->assertSame(1, $db_access->db_file_cache->deleted_all['ggcms_RecordTree']);

			$db_access->db_file_cache->deleted = [];
			$db_access->MarkRowCacheDirty(['type'=>'Assignment', 'update'=>['Ordering'=>2], 'where'=>['id'=>5]]);
			$db_access->FlushRowCacheNow();

			$this->assertArrayNotHasKey('ggcms_ChildRecordCount', $db_access->db_file_cache->deleted, 'nothing named an entry since the last flush');
			$this->assertSame(1, $db_access->db_file_cache->deleted_all['ggcms_RecordTree'], 'the trees are not cleared again for a write that moved nothing');
			$this->assertTrue($db_access->row_cache_dirty, 'the shutdown flush stays registered');
		}

			// A moved entry's old path must stop resolving from the row cache
		public function testMarkRowCacheDirtyClearsTreesOnAMove() {
			$fresh = function() {
				$db_access = $this->newWithoutConstructor(['class'=>'DBAccess']);
				$db_access->db_file_cache = new stdClass();
				$db_access->row_cache_dirty = TRUE;		# already registered, so no shutdown flush is queued by the test

				return $db_access;
			};

			$moved = $fresh();
			$moved->MarkRowCacheDirty(['type'=>'Assignment', 'update'=>['id'=>60207, 'Parentid'=>75870, 'Childid'=>60207], 'where'=>['id'=>60207]]);
			$this->assertTrue($moved->row_cache_dirty_all_trees, 'a repointed assignment clears every cached tree');
			$this->assertArrayHasKey(75870, $moved->row_cache_dirty_entry_ids);

			$made = $fresh();
			$made->MarkRowCacheDirty(['type'=>'Assignment', 'definition'=>['Parentid'=>2, 'Childid'=>75870]]);
			$this->assertNotTrue($made->row_cache_dirty_all_trees, 'a new assignment adds a path and takes none away');

			$ordered = $fresh();
			$ordered->MarkRowCacheDirty(['type'=>'Assignment', 'update'=>['Ordering'=>3], 'where'=>['id'=>60207]]);
			$this->assertNotTrue($ordered->row_cache_dirty_all_trees, 'an assignment written without a parent moves nothing');
		}
	}

?>

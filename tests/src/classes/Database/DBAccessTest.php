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
	}

?>

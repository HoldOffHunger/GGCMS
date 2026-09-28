<?php

	/*
		Only the two select builders are live: DBAccess calls them for
		every GetRecords().  EscapeMySQLQuery() and its helpers have no
		caller -- values go through bound parameters -- and are not tested.
	*/

	class EscapeMySQLTest extends GGCMSTestCase {
		public function recordDescription() {
			return [
				'id'=>['TypeBase'=>'int'],
				'Title'=>['TypeBase'=>'varchar'],
			];
		}

		public function testGetRecordFullSelectStatement() {
			$select = (new EscapeMySQL(['handler'=>NULL]))->GetRecordFullSelectStatement(['recordtype'=>'Entry', 'recorddescription'=>$this->recordDescription()]);

			$this->assertSame('Entry.id as id, Entry.Title as Title', $select);
		}

		public function testGetRecordFullTableSelectStatement() {
			$select = (new EscapeMySQL(['handler'=>NULL]))->GetRecordFullTableSelectStatement(['recordtype'=>'User', 'recorddescription'=>$this->recordDescription()]);

			$this->assertSame('User.id as \'User.id\', User.Title as \'User.Title\'', $select, 'a joined table\'s columns keep their table name');
		}

			/*
				Uncalled today, but it is an escaper: a value containing NOW
				or DATE anywhere used to go into the SQL untouched, so
				"...' OR 1=1 -- NOW" would have.  Only a date expression of
				the shape the engine writes passes through now.
			*/

		public function testEscapeMySQLQuery_EscapeDateTime() {
			$escape = new EscapeMySQL(['handler'=>(object)['cleanser'=>$this->newWithoutConstructor(['class'=>'HandleInput'])]]);

			foreach(['NOW()', 'now()', 'DATE_SUB(NOW(), INTERVAL 160 HOUR)', 'DATE_ADD(NOW(), INTERVAL 1 DAY)'] as $expression) {
				$this->assertSame($expression, $escape->EscapeMySQLQuery_EscapeDateTime(['query'=>$expression]), $expression . ' passes through');
			}

			$this->assertSame("'2026-09-27 20:03:08'", $escape->EscapeMySQLQuery_EscapeDateTime(['query'=>'2026-09-27 20:03:08']));
			$this->assertSame("'2020-01-01 00:00:00'", $escape->EscapeMySQLQuery_EscapeDateTime(['query'=>"2020-01-01' OR 1=1 -- NOW"]), 'NOW inside anything else does not');
			$this->assertSame("'0000-00-00 00:00:00'", $escape->EscapeMySQLQuery_EscapeDateTime(['query'=>'DATE_SUB(NOW(), INTERVAL (SELECT 1) HOUR)']), 'nor a DATE_SUB with anything but a number in it');
		}
	}

?>

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
	}

?>

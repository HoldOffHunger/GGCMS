<?php

	class RecordSelectTest extends GGCMSTestCase {
		public function recordDescription() {
			return [
				'id'=>['TypeBase'=>'int'],
				'Title'=>['TypeBase'=>'varchar'],
			];
		}

		public function testGetRecordFullSelectStatement() {
			$select = (new RecordSelect())->GetRecordFullSelectStatement(['recordtype'=>'Entry', 'recorddescription'=>$this->recordDescription()]);

			$this->assertSame('Entry.id as id, Entry.Title as Title', $select);
		}

		public function testGetRecordFullTableSelectStatement() {
			$select = (new RecordSelect())->GetRecordFullTableSelectStatement(['recordtype'=>'User', 'recorddescription'=>$this->recordDescription()]);

			$this->assertSame('User.id as \'User.id\', User.Title as \'User.Title\'', $select, 'a joined table\'s columns keep their table name');
		}
	}

?>

<?php

	require_once(GGCMS_DIR . 'classes/Database/ORM.php');

	class ORMTest extends GGCMSTestCase {
		public function testCodeCanExist() {
			$orm = $this->newWithoutConstructor(['class'=>'ORM']);

			foreach(['people', 'emma-goldman', 'café', 'дерево', '中文', 7] as $code) {
				$this->assertTrue($orm->CodeCanExist(['code'=>$code]), (string)$code);
			}

			foreach(["foo\xC0\xAE", "foo\xFF", "foo\xE2\x82", "people\u{1F600}"] as $code) {
				$this->assertFalse($orm->CodeCanExist(['code'=>$code]), bin2hex($code));
			}
		}

			/*
				The walk is refused before any query: this ORM has no handler,
				so reaching the database at all would be an error.  On the live
				sites the query was reached, and MySQL's refusal was a 500.
			*/

		public function testGetRecordTree() {
			$orm = $this->newWithoutConstructor(['class'=>'ORM']);

			$this->assertSame([], $orm->GetRecordTree(['codelist'=>['people', "foo\xC0\xAE"]]));
			$this->assertSame([], $orm->GetRecordTree(['codelist'=>["people\u{1F600}"]]));
		}
	}

?>

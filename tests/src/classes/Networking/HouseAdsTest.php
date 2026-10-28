<?php

	/*
		The house ads a signed-in reader has closed for good.
	*/

	class HouseAdsTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/HouseAds.php']);
		}

			/*
				A handler with a reader (or none) and a database that keeps
				UserHiddenHouseAd rows in memory.
			*/

		public function newHouseAds($args) {
			$db_access = new HouseAdsTestDatabase();

			$handler = (object)[
				'authentication'=>(object)['user_session'=>$args['userid'] ? ['User.id'=>$args['userid']] : NULL],
				'db_access'=>$db_access,
			];

			return new HouseAds(['handler'=>$handler]);
		}

		public function testValidCode() {
			$house_ads = $this->newHouseAds(['userid'=>0]);

			foreach(['revoltlib', 'autonomybooks-spain', 'a'] as $code) {
				$this->assertTrue($house_ads->ValidCode(['code'=>$code]), $code);
			}

			foreach(['', 'RevoltLib', 'revoltlib.webp', '../x', 'a b', str_repeat('a', 65), NULL, ['revoltlib']] as $code) {
				$this->assertFalse($house_ads->ValidCode(['code'=>$code]), var_export($code, TRUE));
			}
		}

			/*
				Closing an ad keeps it, once, for that reader only; a signed-out
				reader and a bad code keep nothing.
			*/

		public function testHide() {
			$house_ads = $this->newHouseAds(['userid'=>7]);

			$this->assertSame([], $house_ads->HiddenCodes());

			$this->assertTrue($house_ads->Hide(['code'=>'revoltlib']));
			$this->assertTrue($house_ads->Hide(['code'=>'revoltlib']), 'twice is already done');
			$this->assertTrue($house_ads->Hide(['code'=>'autonomybooks-spain']));
			$this->assertFalse($house_ads->Hide(['code'=>'Not A Code']));

			$this->assertSame(['revoltlib', 'autonomybooks-spain'], $house_ads->HiddenCodes());
			$this->assertCount(2, $house_ads->handler->db_access->rows);

			$someone_else = $this->newHouseAds(['userid'=>8]);
			$someone_else->handler->db_access = $house_ads->handler->db_access;
			$this->assertSame([], $someone_else->HiddenCodes(), 'another reader');

			$signed_out = $this->newHouseAds(['userid'=>0]);
			$this->assertFalse($signed_out->Hide(['code'=>'revoltlib']));
			$this->assertSame([], $signed_out->HiddenCodes());
			$this->assertSame([], $signed_out->handler->db_access->rows);
		}

		public function testHiddenCodesSurvivesAFailedLookup() {
			$house_ads = $this->newHouseAds(['userid'=>7]);
			$house_ads->handler->db_access->fail = TRUE;

			$this->assertSame([], $house_ads->HiddenCodes());
		}
	}

		/*
			UserHiddenHouseAd rows kept in memory, answering the two DBAccess
			calls HouseAds makes.
		*/

	class HouseAdsTestDatabase {
		public $rows = [];
		public $fail = FALSE;

		public function GetRecords($args) {
			if($this->fail) {
				return ['line'=>1];
			}

			return array_values(array_filter($this->rows, function($row) use ($args) {
				return $row['Userid'] === $args['definition']['Userid'];
			}));
		}

		public function CreateRecord($args) {
			$row = $args['definition'] + ['id'=>count($this->rows) + 1];
			$this->rows[] = $row;

			return $row;
		}
	}

?>

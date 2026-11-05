<?php

		// The table descriptions, as DBAccess::GetRecordDescription() answers for a hardcoded table
	class ModifyPriceTestDBAccess {
		public function GetRecordDescription($args) {
			$function = 'HardcodedTable_' . $args['type'];

			return (new ReflectionClass('HardcodedTableDescriptions'))->newInstanceWithoutConstructor()->$function();
		}
	}

		// A book's price, the one child record no site had before AutonomyBooks, 5 Nov 2026.  Its own process,
		// because modify.php brings the whole script layer and the HTML basicscript with it
	#[PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
	class ModifyPriceTest extends GGCMSTestCase {
			/*
				Loaded here, in the test's own process, not at the top of the
				file: a script's class extends the format's basicscript, which a
				real request loads for the one format it serves, and the rest of
				the suite must not see the HTML one.
			*/
		
		protected function setUp(): void {
			parent::setUp();
			
			$this->requireEngine(['file'=>'scripts/Format/base_format.php']);
			$this->requireEngine(['file'=>'scripts/Format/HTML/basicscript.php']);
			$this->requireEngine(['file'=>'scripts/modify.php']);
			$this->requireEngine(['file'=>'classes/Database/HardcodedTableDescriptions.php']);
		}
		
		public function subject($prices) {
			$modify = $this->newWithoutConstructor(['class'=>'modify']);
			$modify->handler = (object)['db_access'=>new ModifyPriceTestDBAccess];
			$modify->price = $prices;
			$modify->errors = [];

			return $modify;
		}

		public function testPriceCurrencies() {
			$this->assertSame(['USD', 'EUR', 'GBP', 'CAD', 'AUD'], $this->subject([])->PriceCurrencies());
		}

			/*
				The enum is written twice, once in the schema and once in the
				description the form and validation read, and the two have to
				agree or a currency the form offers is one MySQL refuses.
			*/

		public function testPriceCurrencyEnumMatchesSchema() {
			$schema = file_get_contents(GGCMS_CLI_DIR . 'sql/clonefrom.sql');

			$this->assertSame(1, preg_match("/CREATE TABLE `Price` \(.*?`Currency` (enum\([^)]*\))/s", $schema, $matches));

			$description = $this->newWithoutConstructor(['class'=>'HardcodedTableDescriptions'])->HardcodedTable_Price();

			$this->assertSame($matches[1], $description['Currency']['Type']);
		}

		public function testValidateRecordForSaving_Price() {
			foreach(['749.99', '5', '12.5', '0.00', '99999999.99'] as $amount) {
				$this->assertTrue($this->subject([['PriceValue'=>$amount, 'Currency'=>'USD']])->ValidateRecordForSaving_Price(), $amount);
			}

			foreach(['$5', '1,200.00', '12.345', '-3', 'ten', '100000000'] as $amount) {
				$modify = $this->subject([['PriceValue'=>$amount, 'Currency'=>'USD']]);

				$this->assertFalse($modify->ValidateRecordForSaving_Price(), $amount);
				$this->assertCount(1, $modify->errors, $amount);
			}

			$this->assertFalse($this->subject([['PriceValue'=>'5.00', 'Currency'=>'XYZ']])->ValidateRecordForSaving_Price());
			$this->assertFalse($this->subject([['PriceValue'=>'5.00', 'Currency'=>'']])->ValidateRecordForSaving_Price());

				// The blank row every form posts, with its currency already chosen
			$this->assertTrue($this->subject([['PriceValue'=>'', 'Currency'=>'USD']])->ValidateRecordForSaving_Price());
		}

		public function testPrepareRecordForSaving_Price() {
			$modify = $this->subject([
				['PriceValue'=>' 749.99 ', 'Currency'=>'USD'],
				['PriceValue'=>'', 'Currency'=>'GBP'],
				['PriceValue'=>'12.00', 'Currency'=>'EUR'],
			]);

			$this->assertTrue($modify->PrepareRecordForSaving_Price());

				// Still three rows, in place, because ids are matched to them by position
			$this->assertSame([
				['PriceValue'=>'749.99', 'Currency'=>'USD'],
				['PriceValue'=>'', 'Currency'=>''],
				['PriceValue'=>'12.00', 'Currency'=>'EUR'],
			], $modify->price);
		}
	}

?>

<?php

	/*
		Which of clonefrom's tables a site lacks, and so which the push
		would create.  The database is replaced by a list of table names.
	*/

	class ClonefromPushTest extends GGCMSTestCase {
		public function newPush($args) {
			$this->requireCLI(['file'=>'classes/Database/ClonefromPush.php']);

				// its one read replaced, so nothing reaches MySQL
			$push = new class(['argv'=>$args['argv'] ?? []]) extends ClonefromPush {
				public $tables = [];

				public function getTableNames($args) {
					return $this->tables[$args['database']] ?? [];
				}
			};

			$push->tables = $args['tables'];
			$push->setArguments();

			return $push;
		}

		public function testMissingTables() {
			$tables = [
				'clonefrom'=>['Entry'=>TRUE, 'LikeDislike'=>TRUE, 'UserHiddenHouseAd'=>TRUE, 'EntryCodeReservation'=>TRUE],
				'revoltlib'=>['Entry'=>TRUE, 'LikeDislike'=>TRUE, 'RevoltLibOwnTable'=>TRUE],
				'sortwords'=>['Entry'=>TRUE, 'LikeDislike'=>TRUE, 'UserHiddenHouseAd'=>TRUE, 'EntryCodeReservation'=>TRUE],
			];

			$push = $this->newPush(['tables'=>$tables]);
			$reference = $tables['clonefrom'];

			$this->assertSame(['EntryCodeReservation', 'UserHiddenHouseAd'], $push->missingTables(['database'=>'revoltlib', 'reference'=>$reference]));
			$this->assertSame([], $push->missingTables(['database'=>'sortwords', 'reference'=>$reference]), 'has them all');

			$one_table = $this->newPush(['tables'=>$tables, 'argv'=>['clonefrom_push.php', '--table=UserHiddenHouseAd']]);
			$this->assertSame(['UserHiddenHouseAd'], $one_table->missingTables(['database'=>'revoltlib', 'reference'=>$reference]), '--table');
		}

			/*
				A site has its configuration; an archived copy beside it, and
				the shared dictionaries, do not.
			*/

		public function testIsSiteDatabase() {
			$push = $this->newPush(['tables'=>[]]);

			$this->assertTrue($push->isSiteDatabase(['database'=>'revoltlib']));
			$this->assertTrue($push->isSiteDatabase(['database'=>'sortwords']));

			foreach(['revoltlib_live', 'revoltlib_jan2022', 'masereelgroup_old', 'alldictionaries', 'clonefrom', '', '../etc'] as $database) {
				$this->assertFalse($push->isSiteDatabase(['database'=>$database]), $database);
			}
		}

		public function testArgumentsAreIdentifiers() {
			$push = $this->newPush(['tables'=>[], 'argv'=>['clonefrom_push.php', '--database=revoltlib`; DROP', '--table=User.Hidden', '--apply']]);

			$this->assertSame('revoltlibDROP', $push->arguments['database']);
			$this->assertSame('UserHidden', $push->arguments['table']);
			$this->assertTrue($push->arguments['apply']);

			$this->assertFalse($this->newPush(['tables'=>[], 'argv'=>['clonefrom_push.php']])->arguments['apply'], 'dry run by default');
		}
	}

?>

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

			$column = $this->newPush(['tables'=>[], 'argv'=>['clonefrom_push.php', '--column=TextBody.SpellCheck`Immune']]);
			$this->assertSame('TextBody', $column->arguments['table']);
			$this->assertSame('SpellCheckImmune', $column->arguments['column']);
			$this->assertSame('', $this->newPush(['tables'=>[], 'argv'=>['clonefrom_push.php', '--column=SpellCheckImmune']])->arguments['column'], 'a column needs its table');
		}

			/*
				The ADD COLUMN that recreates clonefrom's column, from its
				information_schema.COLUMNS row.
			*/

		public function testColumnDefinition() {
			$push = $this->newPush(['tables'=>[]]);

			$row = function($overrides) {
				return $overrides + ['COLUMN_NAME'=>'SpellCheckImmune', 'COLUMN_TYPE'=>'tinyint(1)', 'IS_NULLABLE'=>'NO', 'COLUMN_DEFAULT'=>'0', 'EXTRA'=>'', 'CHARACTER_SET_NAME'=>NULL, 'COLLATION_NAME'=>NULL];
			};

			$this->assertSame("`SpellCheckImmune` tinyint(1) NOT NULL DEFAULT '0'", $push->columnDefinition(['column'=>$row([])]));
			$this->assertSame("`Source` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT ''", $push->columnDefinition(['column'=>$row(['COLUMN_NAME'=>'Source', 'COLUMN_TYPE'=>'varchar(512)', 'COLUMN_DEFAULT'=>'', 'CHARACTER_SET_NAME'=>'utf8mb3', 'COLLATION_NAME'=>'utf8mb3_general_ci'])]), 'an empty default is still a default');
			$this->assertSame('`Text` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL', $push->columnDefinition(['column'=>$row(['COLUMN_NAME'=>'Text', 'COLUMN_TYPE'=>'mediumtext', 'COLUMN_DEFAULT'=>NULL, 'CHARACTER_SET_NAME'=>'utf8mb3', 'COLLATION_NAME'=>'utf8mb3_general_ci'])]), 'no default');
			$this->assertSame('`Seen` datetime NULL DEFAULT CURRENT_TIMESTAMP', $push->columnDefinition(['column'=>$row(['COLUMN_NAME'=>'Seen', 'COLUMN_TYPE'=>'datetime', 'IS_NULLABLE'=>'YES', 'COLUMN_DEFAULT'=>'CURRENT_TIMESTAMP', 'EXTRA'=>'DEFAULT_GENERATED'])]), 'a computed default is bare');
			$this->assertSame("`Note` varchar(9) NOT NULL DEFAULT 'it\\'s'", $push->columnDefinition(['column'=>$row(['COLUMN_NAME'=>'Note', 'COLUMN_TYPE'=>'varchar(9)', 'COLUMN_DEFAULT'=>"it's"])]), 'a quote in a default');

			$this->assertNull($push->columnDefinition(['column'=>$row(['COLUMN_NAME'=>'id', 'COLUMN_TYPE'=>'int', 'COLUMN_DEFAULT'=>NULL, 'EXTRA'=>'auto_increment'])]), 'auto_increment');
			$this->assertNull($push->columnDefinition(['column'=>$row(['EXTRA'=>'VIRTUAL GENERATED'])]), 'generated');
		}

			/*
				After its neighbour in clonefrom when the site has it, FIRST
				when it leads, and at the end otherwise.
			*/

		public function testColumnPosition() {
			$push = $this->newPush(['tables'=>[]]);

			$reference = ['id'=>[], 'Text'=>[], 'CharacterCount'=>[], 'SpellCheckImmune'=>[], 'Entryid'=>[]];

			$this->assertSame('AFTER `CharacterCount`', $push->columnPosition(['reference'=>$reference, 'present'=>['id'=>[], 'Text'=>[], 'CharacterCount'=>[], 'Entryid'=>[]], 'column'=>'SpellCheckImmune']));
			$this->assertSame('', $push->columnPosition(['reference'=>$reference, 'present'=>['id'=>[], 'Text'=>[], 'Entryid'=>[]], 'column'=>'SpellCheckImmune']), 'its neighbour is missing too');
			$this->assertSame('FIRST', $push->columnPosition(['reference'=>$reference, 'present'=>['Text'=>[]], 'column'=>'id']));
		}
	}

?>

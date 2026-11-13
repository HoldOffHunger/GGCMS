<?php

	/*
		Naming a new child table in every file that has to name it.  The
		whole-plan tests work on copies of the real files, in this class's
		scratch directory, so the tool is held to the files as they are
		today and never writes to them.
	*/

	class ChildTableAdderTest extends GGCMSTestCase {
		public function newAdder($args = []) {
			$this->requireCLI(['file'=>'classes/Database/ChildTableAdder.php']);

			$adder = new ChildTableAdder([
				'argv'=>$args['argv'] ?? ['add_child_table.php'],
				'publicroot'=>$args['publicroot'] ?? '/nowhere/public',
				'privateroot'=>$args['privateroot'] ?? '/nowhere/private',
			]);

			$adder->setArguments();

			return $adder;
		}

			//  Both repositories' files, copied to scratch: every file the tool names, as it stands in the checkouts.

		public function copies() {
			$scratch = $this->emptyScratchDirectory();
			$roots = ['public'=>dirname(GGCMS_DIR, 4), 'private'=>rtrim(str_replace('\\', '/', (string)getenv('GGCMS_CONFIG_REPO')), '/')];

			if(!is_file($roots['private'] . '/etc/ggcms/clonefrom/child_types/enabled.php')) {
				$this->markTestSkipped('No private repository to copy the configuration\'s half from.');
			}

			$adder = $this->newAdder(['publicroot'=>$roots['public'], 'privateroot'=>$roots['private'], 'argv'=>['add_child_table.php', '--table=ScratchNote']]);
			$plan = $adder->plan(['table'=>'ScratchNote', 'columns'=>$adder->parseColumns(['columns'=>'Note:varchar(255)'])]);
			$this->assertSame([], $adder->problems, 'the real files are shaped as the tool expects');

			foreach(array_keys($plan) as $path) {
				foreach($roots as $name => $root) {
					if(strpos($path, $root . '/') === 0) {
						$copy = $scratch . '/' . $name . substr($path, strlen($root));

						if(!is_dir(dirname($copy))) {
							mkdir(dirname($copy), 0755, TRUE);
						}

						copy($path, $copy);
					}
				}
			}

			return ['public'=>$scratch . '/public', 'private'=>$scratch . '/private', 'files'=>count($plan)];
		}

		public function testParseColumns() {
			$adder = $this->newAdder();

			$columns = $adder->parseColumns(['columns'=>'Language:varchar(32), Position:int, BuiltFromEntryid:int, Done:tinyint(1):key, Body:text']);
			$this->assertSame([], $adder->problems);
			$this->assertSame(['BuiltFromEntryid', 'Language', 'Position', 'Done', 'Body'], array_column($columns, 'name'), 'a foreign key goes first, whatever its place in the list');
			$this->assertSame([TRUE, FALSE, FALSE, TRUE, FALSE], array_column($columns, 'key'), 'a foreign key is indexed unasked');
			$this->assertSame(['', '32', '', '1', ''], array_column($columns, 'attribute'));
			$this->assertSame([TRUE, FALSE, TRUE, TRUE, FALSE], array_column($columns, 'number'));

			foreach(['language:varchar(32)', 'Entryid:int', 'OriginalCreationDate:int', 'Price:decimal(10,2)', 'Note', 'ParentEntryid:varchar(32)', 'Note:int,Note:int', ''] as $refused) {
				$adder = $this->newAdder();
				$adder->parseColumns(['columns'=>$refused]);
				$this->assertNotEmpty($adder->problems, '"' . $refused . '" is refused');
			}

			$this->assertTrue($adder->validName(['name'=>'WordPiece']));
			$this->assertFalse($adder->validName(['name'=>'word_piece']));
			$this->assertFalse($adder->validName(['name'=>'Word`; DROP']));
		}

		public function testPrimaryColumn() {
			$adder = $this->newAdder();
			$columns = $adder->parseColumns(['columns'=>'Language:varchar(32),Position:int,Piece:varchar(255)']);
			$this->assertSame('Piece', $adder->primaryColumn(['columns'=>$columns]), 'the first varchar that is not Language');

			$given = $this->newAdder(['argv'=>['add_child_table.php', '--primary=Position']]);
			$this->assertSame('Position', $given->primaryColumn(['columns'=>$columns]));

			$wrong = $this->newAdder(['argv'=>['add_child_table.php', '--primary=Colour']]);
			$wrong->primaryColumn(['columns'=>$columns]);
			$this->assertNotEmpty($wrong->problems);
		}

		public function testSpokenName() {
			$adder = $this->newAdder();

			$this->assertSame('Word Piece', $adder->spokenName(['table'=>'WordPiece']));
			$this->assertSame('A word piece\'s', $adder->spokenName(['table'=>'WordPiece', 'possessive'=>TRUE]));
			$this->assertSame('An academic reference\'s', $adder->spokenName(['table'=>'AcademicReference', 'possessive'=>TRUE]));
			$this->assertSame('API Quota', $adder->spokenName(['table'=>'APIQuota']));
		}

		public function testCreateTable() {
			$adder = $this->newAdder();
			$sql = $adder->createTable(['table'=>'WordForm', 'columns'=>$adder->parseColumns(['columns'=>'Language:varchar(32),BuiltFromEntryid:int,Body:text'])]);

			$this->assertSame(implode("\n", [
				'CREATE TABLE `WordForm` (',
				'  `id` int NOT NULL AUTO_INCREMENT,',
				'  `Entryid` int NOT NULL DEFAULT \'0\',',
				'  `BuiltFromEntryid` int NOT NULL DEFAULT \'0\',',
				'  `Language` varchar(32) NOT NULL DEFAULT \'\',',
				'  `Body` text NOT NULL,',
				'  `OriginalCreationDate` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',',
				'  `LastModificationDate` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',',
				'  PRIMARY KEY (`id`),',
				'  KEY `Entryid` (`Entryid`),',
				'  KEY `BuiltFromEntryid` (`BuiltFromEntryid`)',
				') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;',
			]), $sql);
		}

		public function testAddToLists() {
			$adder = $this->newAdder();
			$text = "\t\t'Price',\r\n\t\t'Publication',\r\n\t\t'Quote',\n\t'publication'=>['publication_'],\n\t'Publication'=>FALSE,\n\t'Publication'=>'publication',\n";

			$this->assertSame(
				"\t\t'Price',\r\n\t\t'Publication',\r\n\t\t'WordPiece',\r\n\t\t'Quote',\n\t'publication'=>['publication_'],\n\t'wordpiece'=>['wordpiece_'],\n\t'Publication'=>FALSE,\n\t'WordPiece'=>FALSE,\n\t'Publication'=>'publication',\n\t'WordPiece'=>'wordpiece',\n",
				$adder->addToLists(['text'=>$text, 'table'=>'WordPiece']),
				'each form it is named in, with the indent and the line ending of the line it follows'
			);

			$this->assertNull($adder->addToLists(['text'=>"\t\t'Price',\n\t\t'Quote',\n", 'table'=>'WordPiece']), 'a list that never names Publication is not one it knows');
		}

		public function testAddSwitch() {
			$adder = $this->newAdder();
			$switch = function($name) { return "\t\tpublic function " . $name . "_enabled() {\n\t\t\treturn TRUE;\n\t\t}\n"; };
			$text = "<?php\n\n\tclass Switches {\n" . $switch('Assignment') . "\t\t\n" . $switch('Quote') . "\t}\n\n?>";

			$middle = $adder->addSwitch(['text'=>$text, 'table'=>'Price']);
			$this->assertStringContainsString($switch('Assignment') . "\t\t\n\t\tpublic function Price_enabled() {\n\t\t\treturn FALSE;\n\t\t}\n\t\t\n" . $switch('Quote'), $middle, 'in its alphabetical place, and off');

			$last = $adder->addSwitch(['text'=>$text, 'table'=>'WordPiece']);
			$this->assertStringContainsString($switch('Quote') . "\t\t\n\t\tpublic function WordPiece_enabled() {\n\t\t\treturn FALSE;\n\t\t}\n\t}\n", $last);

			$this->assertNull($adder->addSwitch(['text'=>"<?php\n\n?>", 'table'=>'WordPiece']));
		}

		public function testAddSchema() {
			$adder = $this->newAdder();
			$table = function($name) { return "--\n-- Table structure for table `" . $name . "`\n--\n\nCREATE TABLE `" . $name . "` (x);\nUNLOCK TABLES;\n"; };
			$text = "-- dump\n\n" . $table('Entry') . "\n" . $table('Tag') . "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
			$columns = $adder->parseColumns(['columns'=>'Note:varchar(255)']);

			$middle = $adder->addSchema(['text'=>$text, 'table'=>'Price', 'columns'=>$columns]);
			$this->assertLessThan(strpos($middle, 'table `Tag`'), strpos($middle, 'table `Price`'));
			$this->assertGreaterThan(strpos($middle, 'table `Entry`'), strpos($middle, 'table `Price`'));
			$this->assertStringContainsString("ALTER TABLE `Price` ENABLE KEYS */;\nUNLOCK TABLES;\n\n--\n-- Table structure for table `Tag`", $middle);

			$last = $adder->addSchema(['text'=>$text, 'table'=>'WordPiece', 'columns'=>$columns]);
			$this->assertStringContainsString("ALTER TABLE `WordPiece` ENABLE KEYS */;\nUNLOCK TABLES;\n/*!40103 SET TIME_ZONE", $last, 'after the last table, before the dump puts the session back');

			$this->assertNull($adder->addSchema(['text'=>'not a dump', 'table'=>'WordPiece', 'columns'=>$columns]));
		}

		public function testAddDescription() {
			$adder = $this->newAdder();
			$text = "<?php\n\tclass Descriptions {\n\t\tpublic function HardcodedTable_Entry() {\r\n\t\t\treturn [];\r\n\t\t}\r\n\t\t\r\n\t\tpublic function HardcodedTable_Tag() {\n\t\t\treturn [];\n\t\t}\n\t}\n\n?>";
			$columns = $adder->parseColumns(['columns'=>'Position:int,Piece:varchar(255):key']);

			$middle = $adder->addDescription(['text'=>$text, 'table'=>'Price', 'columns'=>$columns]);
			$this->assertStringContainsString("\t\t\r\n\t\tpublic function HardcodedTable_Price() {\r\n\t\t\treturn [\r\n\t\t\t\t'id' => [\r\n", $middle, 'beside Windows lines it is written in Windows lines');
			$this->assertStringContainsString("\t\t\t\t'Piece' => [\r\n\t\t\t\t\t'Type' => 'varchar(255)',\r\n\t\t\t\t\t'TypeBase' => 'varchar',\r\n\t\t\t\t\t'TypeAttribute' => 255,\r\n\t\t\t\t\t'Null' => 'NO',\r\n\t\t\t\t\t'Key' => 'MUL',\r\n\t\t\t\t\t'Default' => '',\r\n", $middle);
			$this->assertStringContainsString("\t\t\t\t'Position' => [\r\n\t\t\t\t\t'Type' => 'int',\r\n\t\t\t\t\t'TypeBase' => 'int',\r\n\t\t\t\t\t'TypeAttribute' => '',\r\n\t\t\t\t\t'Null' => 'NO',\r\n\t\t\t\t\t'Key' => '',\r\n\t\t\t\t\t'Default' => 0,\r\n", $middle);

			$last = $adder->addDescription(['text'=>$text, 'table'=>'WordPiece', 'columns'=>$columns]);
			$this->assertStringContainsString("\t\t}\n\t\t\n\t\tpublic function HardcodedTable_WordPiece() {\n", $last, 'and beside Unix lines, in Unix lines');
			$this->assertStringEndsWith("\t\t\t];\n\t\t}\n\t}\n\n?>", $last);

			token_get_all($middle, TOKEN_PARSE);
			token_get_all($last, TOKEN_PARSE);
		}

		public function testAddStep() {
			$adder = $this->newAdder();
			$text = "\t\t\tif(!\$this->SaveRecordFromQuery_Publication()) {\n\t\t\t\t\$save_results = FALSE;\n\t\t\t\t\$this->errors[] = ['There was a problem with saving the Publication.'];\n\t\t\t}\n";

			$this->assertSame(
				$text . "\t\t\t\n" . "\t\t\tif(!\$this->SaveRecordFromQuery_WordPiece()) {\n\t\t\t\t\$save_results = FALSE;\n\t\t\t\t\$this->errors[] = ['There was a problem with saving the Word Piece.'];\n\t\t\t}\n",
				$adder->addStep(['text'=>$text, 'prefix'=>'SaveRecordFromQuery', 'table'=>'WordPiece'])
			);

			$this->assertNull($adder->addStep(['text'=>$text, 'prefix'=>'SaveRecordFromQuery', 'table'=>'WordPiece', 'count'=>2]), 'a stage with fewer runs of steps than expected has changed shape');
			$this->assertNull($adder->addStep(['text'=>$text, 'prefix'=>'ValidateRecordForSaving', 'table'=>'WordPiece']));
		}

		public function testPlanNamesTheTableEverywhere() {
			$copies = $this->copies();
			$arguments = ['add_child_table.php', '--table=ScratchNote', '--columns=Language:varchar(32),Note:varchar(255),NotedEntryid:int'];

			$before = [];

			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($copies['public']), FilesystemIterator::SKIP_DOTS)) as $file) {
				$before[$file->getPathname()] = file_get_contents($file->getPathname());
			}

			$this->assertCount($copies['files'], $before);

				// A preview writes nothing
			$preview = $this->newAdder(['argv'=>$arguments, 'publicroot'=>$copies['public'], 'privateroot'=>$copies['private']]);
			ob_start();
			$this->assertTrue($preview->add());
			$said = ob_get_clean();
			$this->assertStringContainsString($copies['files'] . ' file(s) would change.', $said);
			$this->assertStringContainsString('clonefrom_push.php --table=ScratchNote --apply', $said);

			foreach($before as $path => $text) {
				$this->assertSame($text, file_get_contents($path), 'a preview left ' . basename($path) . ' alone');
			}

				// With --apply every file names it, still parses, and keeps its own line endings
			$apply = $this->newAdder(['argv'=>array_merge($arguments, ['--apply']), 'publicroot'=>$copies['public'], 'privateroot'=>$copies['private']]);
			ob_start();
			$this->assertTrue($apply->add());
			ob_end_clean();

			foreach($before as $path => $text) {
				$after = file_get_contents($path);
				$this->assertMatchesRegularExpression('/ScratchNote|scratchnote/', $after, basename($path) . ' names it');

				if(substr($path, -4) === '.php') {
					token_get_all($after, TOKEN_PARSE);
				}

				if(substr_count($text, "\r\n") === substr_count($text, "\n")) {
					$this->assertSame(substr_count($after, "\r\n"), substr_count($after, "\n"), basename($path) . ' is still in Windows line endings throughout');
				} else if(!substr_count($text, "\r")) {
					$this->assertSame(0, substr_count($after, "\r"), basename($path) . ' is still in Unix line endings throughout');
				}
			}

			$vocabulary = file_get_contents($copies['public'] . '/usr/lib/ggcms/src/traits/scripts/ChangeRecords/ChangeRecordsVocabulary.php');
			$this->assertStringContainsString("'scratchnote_NotedEntryid',\n\t\t\t\t\t\t'scratchnote_Language',\n\t\t\t\t\t\t'scratchnote_Note',", str_replace("\r\n", "\n", $vocabulary), 'the foreign key first');
			$this->assertStringContainsString("'ScratchNote'=>['Note'],", file_get_contents($copies['public'] . '/usr/lib/ggcms/src/traits/scripts/ChangeRecords/ChangeRecordsBackup.php'));

			foreach(['public', 'private'] as $root) {
				$this->assertStringContainsString("public function ScratchNote_enabled() {", file_get_contents($copies[$root] . '/etc/ggcms/clonefrom/child_types/enabled.php'), 'off, in both repositories');
			}

				// A second run refuses, and changes nothing
			$applied = [];

			foreach(array_keys($before) as $path) {
				$applied[$path] = file_get_contents($path);
			}

			$again = $this->newAdder(['argv'=>array_merge($arguments, ['--apply']), 'publicroot'=>$copies['public'], 'privateroot'=>$copies['private']]);
			ob_start();
			$this->assertFalse($again->add());
			$this->assertStringContainsString('already names ScratchNote', ob_get_clean());

			foreach($applied as $path => $text) {
				$this->assertSame($text, file_get_contents($path));
			}

				// One file out of shape stops all of it
			$this->emptyScratchDirectory();
			$copies = $this->copies();
			$broken = $copies['public'] . '/usr/lib/ggcms/src/traits/scripts/ChangeRecords/ChangeRecordsSave.php';
			file_put_contents($broken, str_replace('SaveRecordFromQuery_Publication', 'SaveRecordFromQuery_Publications', file_get_contents($broken)));

			$stopped = $this->newAdder(['argv'=>array_merge($arguments, ['--apply']), 'publicroot'=>$copies['public'], 'privateroot'=>$copies['private']]);
			ob_start();
			$this->assertFalse($stopped->add());
			$said = ob_get_clean();
			$this->assertStringContainsString('ChangeRecordsSave.php is not shaped as expected', $said);
			$this->assertStringNotContainsString('ScratchNote', file_get_contents($copies['public'] . '/etc/ggcms/clonefrom.php'), 'and nothing else was written');
		}
	}

?>

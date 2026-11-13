<?php

	/*
		Adds a new child table to the code: every file that has to name it.

		A child table hangs off an entry -- Tag, Price, Publication -- and the
		engine names each one in a great many places: the reference schema,
		its hardcoded description, the ORM's lists, the cache tools' lists,
		the child-type switches, and eight steps of modify.php's save
		pipeline.  WordPiece and WordForm went in by hand on 13 November
		2026 and came to twenty-one files across the two repositories, nearly
		all of it by rote.  This does the rote part.

			add_child_table.php --table=WordPiece --columns="Language:varchar(32),Position:int,Piece:varchar(255),Role:varchar(32),Answers:varchar(255)"
			add_child_table.php --table=WordForm --columns="BuiltFromEntryid:int:key,Language:varchar(32),BaseWord:varchar(255)" --apply

		Nothing is written without --apply.  Run bare, it says what it would
		do to each file.  It is all or nothing: if any one file is not shaped
		as expected, it stops, names the file, and writes none of them.

		The new type is copied from Publication, the plainest type there is:
		every field a string, trimmed, held to its column's length, nothing
		else done to it.  Wherever Publication is named, the new type is
		named after it.

		Every table has the same spine (Docs/Database.md): id, Entryid, any
		other column ending Entryid, the data columns in the order given,
		then OriginalCreationDate and LastModificationDate.  Only the data
		columns are given.

		It works on two checkouts and no database: this one, and the private
		configuration repository named by GGCMS_CONFIG_REPO, whose
		etc/ggcms/clonefrom/ is laid over this one's on deploy.  A child type
		switched on in one and missing from the other fatals every site.

		What it does not do:

		  the database    it prints the CREATE TABLE to run on clonefrom, and
		                  the clonefrom_push.php command that follows
		  the Edit form   modify.php saves the type, but shows no fields for
		                  it; until a form module is written, the importer is
		                  the only writer
		  any one site    the type is off everywhere; a site turns it on in
		                  its own etc/ggcms/com.<site>/child_types/enabled.php
	*/

	class ChildTableAdder {
		public $argv;
		public $public_root;
		public $private_root;
		public $arguments;
		public $problems = [];

			// The type every new type is copied from, and named after
		public const ANCHOR = 'Publication';

		public function __construct($args) {
			$this->argv = $args['argv'];
			$this->public_root = rtrim(str_replace('\\', '/', (string)$args['publicroot']), '/');
			$this->private_root = rtrim(str_replace('\\', '/', (string)$args['privateroot']), '/');
		}

			// Entry Point
			// -----------------------------------------------

		public function add() {
			$this->setArguments();

			if($this->arguments['help'] || !strlen($this->arguments['table'])) {
				return $this->printUsage();
			}

			$columns = $this->parseColumns(['columns'=>$this->arguments['columns']]);

			if(!$this->validName(['name'=>$this->arguments['table']])) {
				$this->problems[] = 'A table\'s name is letters only, starting with a capital: WordPiece, not ' . $this->arguments['table'] . '.';
			}

			if($this->problems) {
				return $this->printProblems();
			}

			$plan = $this->plan(['table'=>$this->arguments['table'], 'columns'=>$columns]);

			if($this->problems) {
				return $this->printProblems();
			}

			foreach($plan as $path => $edit) {
				if($this->arguments['apply']) {
					file_put_contents($path, $edit['text']);
				}

				print(($this->arguments['apply'] ? 'changed       ' : 'would change  ') . $this->shortPath(['path'=>$path]) . '  (' . $edit['what'] . ')' . "\n");
			}

			print("\n" . count($plan) . ' file(s) ' . ($this->arguments['apply'] ? 'changed.' : 'would change.  Nothing was written; add --apply.') . "\n");
			print($this->nextSteps(['table'=>$this->arguments['table'], 'columns'=>$columns]));

			return TRUE;
		}

			// Arguments
			// -----------------------------------------------

		public function setArguments() {
			$arguments = [
				'table'=>'',
				'columns'=>'',
				'primary'=>'',
				'apply'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--apply') {
					$arguments['apply'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 8) === '--table=') {
					$arguments['table'] = trim(substr($argument, 8));
				} else if(substr($argument, 0, 10) === '--columns=') {
					$arguments['columns'] = trim(substr($argument, 10));
				} else if(substr($argument, 0, 10) === '--primary=') {
					$arguments['primary'] = trim(substr($argument, 10));
				}
			}

			return $this->arguments = $arguments;
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Add Child Table' . "\n\n");
			print('  add_child_table.php --table=NAME --columns="Name:type[:key],..." [--primary=COLUMN] [--apply]' . "\n\n");
			print('  Names a new child table in every file of both repositories that has to name it,' . "\n");
			print('  copying how ' . self::ANCHOR . ' is named.  Give only the data columns: id, Entryid and the' . "\n");
			print('  two dates are added.  Types: int, tinyint(1), varchar(N), text.  A column ending' . "\n");
			print('  Entryid is a foreign key: it goes straight after Entryid and is indexed.' . "\n\n");
			print('  --table     the new table, e.g. WordPiece' . "\n");
			print('  --columns   its data columns, in order, e.g. "Language:varchar(32),Position:int,Piece:varchar(255)"' . "\n");
			print('  --primary   the column that says what a record is, for the change log; the first varchar' . "\n");
			print('              that is not Language unless given' . "\n");
			print('  --apply     do it; without this, only say what would be done' . "\n\n");
			print('  Needs GGCMS_CONFIG_REPO, the private configuration checkout.  Touches no database:' . "\n");
			print('  it prints the SQL for clonefrom and the clonefrom_push.php command to run after.' . "\n\n");

			return TRUE;
		}

		public function printProblems() {
			foreach($this->problems as $problem) {
				print('STOP: ' . $problem . "\n");
			}

			print("\n" . 'Nothing was written.' . "\n");

			return FALSE;
		}

		public function shortPath($args) {
			$path = $args['path'];

			if(strpos($path, $this->private_root . '/') === 0) {
				return '[config] ' . substr($path, strlen($this->private_root) + 1);
			}

			return substr($path, strlen($this->public_root) + 1);
		}

			// Names and Columns
			// -----------------------------------------------

			// validName()
			// Tests: ChildTableAdderTest::testParseColumns()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function validName($args) {
			return (bool)preg_match('/^[A-Z][A-Za-z]*$/', (string)$args['name']);
		}

			/*
				"Language:varchar(32),BuiltFromEntryid:int:key" as the spine
				orders it: foreign keys first, then the rest as given.  Each
				column is name, type, base, attribute, key and whether it holds
				a number.
			*/

			// parseColumns()
			// Tests: ChildTableAdderTest::testParseColumns()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function parseColumns($args) {
			$foreign = [];
			$data = [];
			$seen = [];

			foreach(array_filter(array_map('trim', explode(',', (string)$args['columns'])), 'strlen') as $given) {
				$pieces = array_map('trim', explode(':', $given));
				$name = $pieces[0];
				$type = isset($pieces[1]) ? strtolower($pieces[1]) : '';

				if(!$this->validName(['name'=>$name])) {
					$this->problems[] = 'A column\'s name is letters only, starting with a capital: "' . $name . '" is not.';
					continue;
				}

				if(in_array($name, ['Entryid', 'OriginalCreationDate', 'LastModificationDate'], TRUE) || isset($seen[$name])) {
					$this->problems[] = $name . ' is ' . (isset($seen[$name]) ? 'given twice.' : 'part of every table already; give only the data columns.');
					continue;
				}

				$seen[$name] = TRUE;

				if(!preg_match('/^(int|tinyint\(1\)|text|varchar\(([1-9][0-9]{0,4})\))$/', $type, $match)) {
					$this->problems[] = $name . '\'s type, "' . (isset($pieces[1]) ? $pieces[1] : '') . '", is not one of int, tinyint(1), varchar(N) or text.  Add anything else by hand.';
					continue;
				}

				$is_foreign = substr($name, -7) === 'Entryid';

				if($is_foreign && $type !== 'int') {
					$this->problems[] = $name . ' ends in Entryid, so it holds an entry\'s id and must be an int.';
					continue;
				}

				$column = [
					'name'=>$name,
					'type'=>$type,
					'base'=>preg_replace('/\(.*$/', '', $type),
					'attribute'=>($type === 'tinyint(1)') ? '1' : (isset($match[2]) ? $match[2] : ''),
					'key'=>$is_foreign || (isset($pieces[2]) && strtolower($pieces[2]) === 'key'),
					'number'=>in_array($type, ['int', 'tinyint(1)'], TRUE),
				];

				if($is_foreign) {
					$foreign[] = $column;
				} else {
					$data[] = $column;
				}
			}

			if(!$foreign && !$data && !$this->problems) {
				$this->problems[] = 'A table needs at least one data column: --columns="Name:type,..."';
			}

			return array_merge($foreign, $data);
		}

			// primaryColumn()
			// Tests: ChildTableAdderTest::testPrimaryColumn()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function primaryColumn($args) {
			$names = array_column($args['columns'], 'name');

			if(strlen($this->arguments['primary'])) {
				if(!in_array($this->arguments['primary'], $names, TRUE)) {
					$this->problems[] = '--primary=' . $this->arguments['primary'] . ' is not one of the columns given.';
				}

				return $this->arguments['primary'];
			}

			foreach($args['columns'] as $column) {
				if($column['base'] === 'varchar' && $column['name'] !== 'Language') {
					return $column['name'];
				}
			}

			return $names[0];
		}

			//  "WordPiece" as a person says it: "Word Piece", and "A word piece's" for a complaint about one.

			// spokenName()
			// Tests: ChildTableAdderTest::testSpokenName()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function spokenName($args) {
			$words = trim(preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $args['table']));

			if(empty($args['possessive'])) {
				return $words;
			}

			return (preg_match('/^[AEIOU]/', $words) ? 'An ' : 'A ') . strtolower($words) . '\'s';
		}

			// The Plan
			// -----------------------------------------------

			/*
				Every file's new text, keyed by its path, or problems.  A file
				is read once and every edit to it laid on the same text, so a
				file named in two lists is written once.
			*/

			// plan()
			// Tests: ChildTableAdderTest::testPlanNamesTheTableEverywhere()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function plan($args) {
			$table = $args['table'];
			$columns = $args['columns'];
			$lower = strtolower($table);
			$fields = array_column($columns, 'name');
			$primary = $this->primaryColumn(['columns'=>$columns]);

			$engine = $this->public_root . '/usr/lib/ggcms/';
			$traits = $engine . 'src/traits/scripts/ChangeRecords/';
			$plan = [];

			$lists = [
				$this->public_root . '/etc/ggcms/clonefrom.php',
				$this->private_root . '/etc/ggcms/clonefrom.php',
				$engine . 'cli/classes/Database/DBCacheBlanksChecker.php',
				$engine . 'cli/classes/Database/DBCacheBlanksClearer.php',
				$engine . 'cli/classes/Database/DBCacheBlanksFileClearer.php',
				$engine . 'cli/classes/Database/DBCacheClearer.php',
				$engine . 'cli/classes/Database/DBCacheEnabler.php',
				$engine . 'cli/classes/Database/SchemaChecker.php',
				$engine . 'cli/classes/Entries/EntryModifier.php',
				$engine . 'src/classes/Database/ORM.php',
				$engine . 'src/traits/scripts/SimpleORM.php',
			];

			foreach($lists as $path) {
				$this->lay(['plan'=>&$plan, 'path'=>$path, 'table'=>$table, 'what'=>'its lists of child tables', 'edit'=>function($text) use ($table) {
					return $this->addToLists(['text'=>$text, 'table'=>$table]);
				}]);
			}

			foreach([$this->public_root, $this->private_root] as $root) {
				$this->lay(['plan'=>&$plan, 'path'=>$root . '/etc/ggcms/clonefrom/child_types/enabled.php', 'table'=>$table, 'what'=>'its switch, off', 'edit'=>function($text) use ($table) {
					return $this->addSwitch(['text'=>$text, 'table'=>$table]);
				}]);
			}

			$this->lay(['plan'=>&$plan, 'path'=>$engine . 'cli/sql/clonefrom.sql', 'table'=>$table, 'what'=>'CREATE TABLE', 'edit'=>function($text) use ($table, $columns) {
				return $this->addSchema(['text'=>$text, 'table'=>$table, 'columns'=>$columns]);
			}]);

			$this->lay(['plan'=>&$plan, 'path'=>$engine . 'src/classes/Database/HardcodedTableDescriptions.php', 'table'=>$table, 'what'=>'its hardcoded description', 'edit'=>function($text) use ($table, $columns) {
				return $this->addDescription(['text'=>$text, 'table'=>$table, 'columns'=>$columns]);
			}]);

			$steps = [
				'ChangeRecordsVocabulary.php'=>function($text) use ($table, $lower, $fields) {
					$block = "\t\t\t\t'" . $lower . "'=>[\n\t\t\t\t\t'formal'=>'" . $table . "',\n\t\t\t\t\t'objectdefinition'=>[\n";

					foreach($fields as $field) {
						$block .= "\t\t\t\t\t\t'" . $lower . '_' . $field . "',\n";
					}

					return $this->addAfterBlock(['text'=>$text, 'pattern'=>'/\t\t\t\t\'publication\'=>\[\n.*?\n\t\t\t\t\],\n/s', 'block'=>$block . "\t\t\t\t\t],\n\t\t\t\t],\n", 'gap'=>'']);
				},
				'ChangeRecordsRead.php'=>function($text) use ($table, $lower) {
					$text = $this->addAfterLine(['text'=>$text, 'line'=>"\t\tpublic \$publication_unset;", 'new'=>"\t\tpublic \$" . $lower . '_unset;']);
					$text = $this->addStep(['text'=>$text, 'prefix'=>'SetRecordFromQuery', 'table'=>$table]);
					$block = "\t\tpublic function SetRecordFromQuery_" . $table . "() {\n\t\t\t\$this->" . $lower . '_unset = $this->' . $lower . ";\n"
						. "\t\t\t\$set_record_from_query_args = \$this->RecordQueryDefinition(['variablename'=>'" . $lower . "']);\n"
						. "\t\t\treturn \$this->SetRecordFromQuery(\$set_record_from_query_args);\n\t\t}\n";

					return $this->addAfterFunction(['text'=>$text, 'function'=>'SetRecordFromQuery_' . self::ANCHOR, 'block'=>$block]);
				},
				'ChangeRecordsCleanse.php'=>function($text) use ($table, $lower, $fields) {
					$text = $this->addStep(['text'=>$text, 'prefix'=>'CleanseRecordFromQuery', 'table'=>$table]);
					$block = "\t\tpublic function CleanseRecordFromQuery_" . $table . "() {\n\t\t\t\$cleanse_record_from_query_args = [\n"
						. "\t\t\t\t'variablename'=>'" . $lower . "',\n\t\t\t\t'datastructure'=>[\n";

					foreach($fields as $field) {
						$block .= "\t\t\t\t\t'" . $field . "',\n";
					}

					$block .= "\t\t\t\t],\n\t\t\t];\n\t\t\treturn \$this->CleanseRecordFromQuery(\$cleanse_record_from_query_args);\n\t\t}\n";

					return $this->addAfterFunction(['text'=>$text, 'function'=>'CleanseRecordFromQuery_' . self::ANCHOR, 'block'=>$block]);
				},
				'ChangeRecordsFormat.php'=>function($text) use ($table, $lower, $fields) {
					$text = $this->addStep(['text'=>$text, 'prefix'=>'FormatSavedRecordFromQuery', 'table'=>$table]);
					$block = "\t\tpublic function FormatSavedRecordFromQuery_" . $table . "() {\n\t\t\t\$format_args = [\n\t\t\t\t'recordtype'=>'" . $lower . "',\n"
						. "\t\t\t\t'recordfields'=>['" . implode("', '", $fields) . "'],\n\t\t\t];\n\t\t\t\n"
						. "\t\t\treturn \$this->FormatSavedRecordFromQuery_Base(\$format_args);\n\t\t}\n";

					return $this->addAfterFunction(['text'=>$text, 'function'=>'FormatSavedRecordFromQuery_' . self::ANCHOR, 'block'=>$block]);
				},
				'ChangeRecordsPrepare.php'=>function($text) use ($table, $lower) {
					$text = $this->addAfterLine(['text'=>$text, 'line'=>"\t\tpublic \$publication;", 'new'=>"\t\tpublic \$" . $lower . ';']);
					$text = $this->addStep(['text'=>$text, 'prefix'=>'PrepareRecordForSaving', 'table'=>$table]);
					$block = "\t\tpublic function PrepareRecordForSaving_" . $table . "() {\n\t\t\t\$records = [];\n\t\t\t\n"
						. "\t\t\tforeach ((array)\$this->" . $lower . " as \$record) {\n\t\t\t\tforeach ((array)\$record as \$field => \$value) {\n"
						. "\t\t\t\t\t\$record[\$field] = trim((string)\$value);\n\t\t\t\t}\n\t\t\t\t\n\t\t\t\t\$records[] = \$record;\n\t\t\t}\n\t\t\t\n"
						. "\t\t\t\$this->" . $lower . " = \$records;\n\t\t\t\n\t\t\treturn TRUE;\n\t\t}\n";

					return $this->addAfterFunction(['text'=>$text, 'function'=>'PrepareRecordForSaving_' . self::ANCHOR, 'block'=>$block]);
				},
				'ChangeRecordsSave.php'=>function($text) use ($table, $lower) {
					$text = $this->addStep(['text'=>$text, 'prefix'=>'SaveRecordFromQuery', 'table'=>$table, 'count'=>2]);
					$block = "\t\tpublic function SaveRecordFromQuery_" . $table . "() {\n\t\t\t\$save_record_from_query_args = [\n"
						. "\t\t\t\t'objectname'=>'" . $lower . "',\n\t\t\t\t'objecttype'=>'" . $table . "',\n\t\t\t];\n\t\t\t\n"
						. "\t\t\treturn \$this->SaveRecordFromQuery_Base(\$save_record_from_query_args);\n\t\t}\n";

					return $this->addAfterFunction(['text'=>$text, 'function'=>'SaveRecordFromQuery_' . self::ANCHOR, 'block'=>$block]);
				},
				'ChangeRecordsValidate.php'=>function($text) use ($table, $lower) {
					$text = $this->addStep(['text'=>$text, 'prefix'=>'ValidateRecordForSaving', 'table'=>$table]);
					$label = str_replace('\'', '\\\'', $this->spokenName(['table'=>$table, 'possessive'=>TRUE]));
					$block = "\t\tpublic function ValidateRecordForSaving_" . $table . "() {\n"
						. "\t\t\treturn \$this->ValidateRecordLengths(['type'=>'" . $table . "', 'records'=>\$this->" . $lower . ", 'label'=>'" . $label . "']);\n\t\t}\n";

					return $this->addAfterFunction(['text'=>$text, 'function'=>'ValidateRecordForSaving_' . self::ANCHOR, 'block'=>$block]);
				},
				'ChangeRecordsBackup.php'=>function($text) use ($table, $primary) {
					return $this->addAfterLine(['text'=>$text, 'line'=>"\t\t\t\t'Publication'=>['Publisher'],", 'new'=>"\t\t\t\t'" . $table . "'=>['" . $primary . "'],"]);
				},
			];

			foreach($steps as $file => $edit) {
				$this->lay(['plan'=>&$plan, 'path'=>$traits . $file, 'table'=>$table, 'what'=>'a step of modify.php\'s save pipeline', 'edit'=>$edit]);
			}

			return $plan;
		}

			/*
				One file's edit, laid onto the plan.  The file is worked on
				with plain line feeds and written back with its own endings;
				one that mixes endings keeps each line's own.  An edit that
				cannot find its place returns NULL, and that is a problem
				naming the file.
			*/

		public function lay($args) {
			$path = $args['path'];
			$table = $args['table'];

			if(!isset($args['plan'][$path])) {
				if(!is_file($path)) {
					$this->problems[] = $this->shortPath(['path'=>$path]) . ' is not there.';

					return FALSE;
				}

				$raw = file_get_contents($path);
				$args['plan'][$path] = ['raw'=>$raw, 'text'=>$raw, 'what'=>$args['what']];

				if(preg_match('/[\'`_]' . $table . '[\'`(]/', $raw)) {
					$this->problems[] = $this->shortPath(['path'=>$path]) . ' already names ' . $table . '.';
					unset($args['plan'][$path]);

					return FALSE;
				}
			} else if(strpos($args['plan'][$path]['what'], $args['what']) === FALSE) {
				$args['plan'][$path]['what'] .= ', ' . $args['what'];
			}

			$raw = $args['plan'][$path]['text'];
			$windows = substr_count($raw, "\r\n");
			$uniform = $windows && $windows === substr_count($raw, "\n");

			$edited = $args['edit']($uniform ? str_replace("\r\n", "\n", $raw) : $raw);

			if($edited === NULL) {
				$this->problems[] = $this->shortPath(['path'=>$path]) . ' is not shaped as expected, so ' . $args['what'] . ' could not be added.  Look at how ' . self::ANCHOR . ' is named there.';

				return FALSE;
			}

			$args['plan'][$path]['text'] = $uniform ? str_replace("\n", "\r\n", $edited) : $edited;

			return TRUE;
		}

			// The Edits
			// -----------------------------------------------

			/*
				Wherever a list names Publication, the new table follows it in
				the same form: as a key, a bare name, a lower-case name, a
				switch that is off, or a form prefix.
			*/

			// addToLists()
			// Tests: ChildTableAdderTest::testAddToLists()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function addToLists($args) {
			$table = $args['table'];
			$lower = strtolower($table);

			$forms = [
				"'Publication'=>'publication',"=>"'" . $table . "'=>'" . $lower . "',",
				"'Publication',"=>"'" . $table . "',",
				"'publication',"=>"'" . $lower . "',",
				"'Publication'=>FALSE,"=>"'" . $table . "'=>FALSE,",
				"'publication'=>['publication_'],"=>"'" . $lower . "'=>['" . $lower . "_'],",
			];

			$lines = explode("\n", $args['text']);
			$out = [];
			$added = 0;

			foreach($lines as $line) {
				$out[] = $line;
				$bare = rtrim($line, "\r");
				$trimmed = trim($bare);

				if(isset($forms[$trimmed])) {
					$out[] = substr($bare, 0, strlen($bare) - strlen(ltrim($bare))) . $forms[$trimmed] . (substr($line, -1) === "\r" ? "\r" : '');
					$added++;
				}
			}

			return $added ? implode("\n", $out) : NULL;
		}

			//  A switch in the class every site's switches extend, off, in the alphabetical place the file keeps.

			// addSwitch()
			// Tests: ChildTableAdderTest::testAddSwitch()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function addSwitch($args) {
			$table = $args['table'];
			$lines = explode("\n", $args['text']);
			$at = NULL;
			$last = NULL;

			foreach($lines as $index => $line) {
				if(preg_match('/^\t\tpublic function ([A-Za-z]+)_enabled\(\) \{$/', $line, $match)) {
					$last = $index;

					if($at === NULL && strcmp($match[1], $table) > 0) {
						$at = $index;
					}
				}
			}

			if($last === NULL) {
				return NULL;
			}

			$block = ["\t\tpublic function " . $table . '_enabled() {', "\t\t\treturn FALSE;", "\t\t}", "\t\t"];

			if($at === NULL) {
					// after the last switch: its three lines, then a blank one before ours
				$block = ["\t\t", $block[0], $block[1], $block[2]];
				$at = $last + 3;
			}

			array_splice($lines, $at, 0, $block);

			return implode("\n", $lines);
		}

			// createTable()
			// Tests: ChildTableAdderTest::testCreateTable()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function createTable($args) {
			$lines = ['CREATE TABLE `' . $args['table'] . '` (', '  `id` int NOT NULL AUTO_INCREMENT,', '  `Entryid` int NOT NULL DEFAULT \'0\','];
			$keys = ['Entryid'];

			foreach($args['columns'] as $column) {
				$default = ($column['base'] === 'text') ? '' : ' DEFAULT \'' . ($column['number'] ? '0' : '') . '\'';
				$lines[] = '  `' . $column['name'] . '` ' . $column['type'] . ' NOT NULL' . $default . ',';

				if($column['key']) {
					$keys[] = $column['name'];
				}
			}

			$lines[] = '  `OriginalCreationDate` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',';
			$lines[] = '  `LastModificationDate` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',';
			$lines[] = '  PRIMARY KEY (`id`),';

			foreach($keys as $index => $key) {
				$lines[] = '  KEY `' . $key . '` (`' . $key . '`)' . ($index < count($keys) - 1 ? ',' : '');
			}

			$lines[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;';

			return implode("\n", $lines);
		}

			//  The table's block in the reference dump, in the dump's own form and its alphabetical place.

			// addSchema()
			// Tests: ChildTableAdderTest::testAddSchema()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function addSchema($args) {
			$table = $args['table'];

			$block = "--\n-- Table structure for table `" . $table . "`\n--\n\nDROP TABLE IF EXISTS `" . $table . "`;\n"
				. "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n/*!50503 SET character_set_client = utf8mb4 */;\n"
				. $this->createTable($args) . "\n/*!40101 SET character_set_client = @saved_cs_client */;\n\n"
				. "--\n-- Dumping data for table `" . $table . "`\n--\n\nLOCK TABLES `" . $table . "` WRITE;\n"
				. "/*!40000 ALTER TABLE `" . $table . "` DISABLE KEYS */;\n/*!40000 ALTER TABLE `" . $table . "` ENABLE KEYS */;\nUNLOCK TABLES;\n";

			if(!preg_match_all('/^--\n-- Table structure for table `([A-Za-z]+)`\n/m', $args['text'], $matches, PREG_OFFSET_CAPTURE)) {
				return NULL;
			}

			foreach($matches[1] as $index => $found) {
				if(strcmp($found[0], $table) > 0) {
					$at = $matches[0][$index][1];

					return substr($args['text'], 0, $at) . $block . "\n" . substr($args['text'], $at);
				}
			}

				// after the last table: the dump closes by putting the session's settings back
			$at = strpos($args['text'], "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;");

			if($at === FALSE || substr($args['text'], $at - 15, 15) !== "UNLOCK TABLES;\n") {
				return NULL;
			}

			return substr($args['text'], 0, $at) . "\n" . $block . substr($args['text'], $at);
		}

			/*
				HardcodedTable_<Name>(): what DESCRIBE would say, for a site
				whose cache of it is cold.  The file mixes line endings, so
				each new line takes the ending of the line it goes in beside.
			*/

			// addDescription()
			// Tests: ChildTableAdderTest::testAddDescription()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function addDescription($args) {
			$table = $args['table'];

			$described = [
				['name'=>'id', 'type'=>'int', 'base'=>'int', 'attribute'=>'', 'keyed'=>'PRI', 'default'=>"''", 'extra'=>'auto_increment'],
				['name'=>'Entryid', 'type'=>'int', 'base'=>'int', 'attribute'=>'', 'keyed'=>'MUL', 'default'=>'0', 'extra'=>''],
			];

			foreach($args['columns'] as $column) {
				$described[] = [
					'name'=>$column['name'], 'type'=>$column['type'], 'base'=>$column['base'], 'attribute'=>$column['attribute'],
					'keyed'=>$column['key'] ? 'MUL' : '', 'default'=>$column['number'] ? '0' : "''", 'extra'=>'',
				];
			}

			foreach(['OriginalCreationDate', 'LastModificationDate'] as $date) {
				$described[] = ['name'=>$date, 'type'=>'datetime', 'base'=>'datetime', 'attribute'=>'', 'keyed'=>'', 'default'=>"'0000-00-00 00:00:00'", 'extra'=>''];
			}

			$block = ["\t\tpublic function HardcodedTable_" . $table . '() {', "\t\t\treturn ["];

			foreach($described as $index => $column) {
				$block[] = "\t\t\t\t'" . $column['name'] . "' => [";
				$block[] = "\t\t\t\t\t'Type' => '" . $column['type'] . "',";
				$block[] = "\t\t\t\t\t'TypeBase' => '" . $column['base'] . "',";
				$block[] = "\t\t\t\t\t'TypeAttribute' => " . (strlen($column['attribute']) ? $column['attribute'] : "''") . ',';
				$block[] = "\t\t\t\t\t'Null' => 'NO',";
				$block[] = "\t\t\t\t\t'Key' => '" . $column['keyed'] . "',";
				$block[] = "\t\t\t\t\t'Default' => " . $column['default'] . ',';
				$block[] = "\t\t\t\t\t'Extra' => '" . $column['extra'] . "',";
				$block[] = "\t\t\t\t],";

				if($index < count($described) - 1) {
					$block[] = "\t\t\t\t";
				}
			}

			$block[] = "\t\t\t];";
			$block[] = "\t\t}";

			$lines = explode("\n", $args['text']);
			$at = NULL;
			$close = NULL;

			foreach($lines as $index => $line) {
				$bare = rtrim($line, "\r");

				if($at === NULL && preg_match('/^\t\tpublic function HardcodedTable_([A-Za-z]+)\(\) \{$/', $bare, $match) && strcmp($match[1], $table) > 0) {
					$at = $index;
				}

				if($bare === "\t}") {
					$close = $index;
				}
			}

			if($close === NULL) {
				return NULL;
			}

			if($at === NULL) {
					// after the last table: a blank line, then ours, before the class closes
				array_unshift($block, "\t\t");
				$at = $close;
			} else {
				$block[] = "\t\t";
			}

			$ending = (substr($lines[max(0, $at - 1)], -1) === "\r") ? "\r" : '';

			array_splice($lines, $at, 0, array_map(function($line) use ($ending) { return $line . $ending; }, $block));

			return implode("\n", $lines);
		}

			//  One more of the "if(!$this-><Prefix>_Publication()) { ... }" steps in a stage's run of them.

			// addStep()
			// Tests: ChildTableAdderTest::testAddStep()
			// Test file: tests/cli/classes/Database/ChildTableAdderTest.php
		public function addStep($args) {
			if($args['text'] === NULL) {
				return NULL;
			}

			$table = $args['table'];
			$pattern = '/(\t+)if\(!\$this->' . $args['prefix'] . '_' . self::ANCHOR . '\(\)\) \{\n.*?\n\1\}\n/s';

			if(preg_match_all($pattern, $args['text']) !== (isset($args['count']) ? $args['count'] : 1)) {
				return NULL;
			}

			return preg_replace_callback($pattern, function($match) use ($table) {
				$step = str_replace('_' . self::ANCHOR . '()', '_' . $table . '()', $match[0]);
				$step = str_replace('the ' . self::ANCHOR . '.', 'the ' . $this->spokenName(['table'=>$table]) . '.', $step);

				return $match[0] . $match[1] . "\n" . $step;
			}, $args['text']);
		}

		public function addAfterLine($args) {
			if($args['text'] === NULL || substr_count($args['text'], $args['line'] . "\n") !== 1) {
				return NULL;
			}

			return str_replace($args['line'] . "\n", $args['line'] . "\n" . $args['new'] . "\n", $args['text']);
		}

		public function addAfterBlock($args) {
			if($args['text'] === NULL || preg_match_all($args['pattern'], $args['text'], $matches) !== 1) {
				return NULL;
			}

			return str_replace($matches[0][0], $matches[0][0] . $args['gap'] . $args['block'], $args['text']);
		}

			//  A new function after Publication's, with the blank line the files keep between functions.

		public function addAfterFunction($args) {
			return $this->addAfterBlock([
				'text'=>$args['text'],
				'pattern'=>'/\t\tpublic function ' . $args['function'] . '\(\) \{\n.*?\n\t\t\}\n/s',
				'block'=>$args['block'],
				'gap'=>"\t\t\n",
			]);
		}

			// What Is Left to a Person
			// -----------------------------------------------

		public function nextSteps($args) {
			$table = $args['table'];

			return "\n" . 'Left to do by hand:' . "\n\n"
				. '  1. Make the table in clonefrom, on each host:' . "\n\n"
				. preg_replace('/^/m', '       ', $this->createTable($args)) . "\n\n"
				. '  2. Give it to every site:  clonefrom_push.php --table=' . $table . ' --apply' . "\n"
				. '  3. Turn it on for a site:  ' . $table . '_enabled() returning TRUE in the private repository\'s' . "\n"
				. '     etc/ggcms/com.<site>/child_types/enabled.php.  Do steps 1 and 2 on a host before that' . "\n"
				. '     reaches it: a site with the switch on reads the table at once.' . "\n"
				. '  4. modify.php now saves a ' . $table . ' but shows no fields for one.  Until it has a form' . "\n"
				. '     module, the importer is the only writer: ' . $table . '_<Column> headers.' . "\n"
				. '  5. Run the tests and the lint, and commit both repositories.' . "\n\n";
		}
	}

?>

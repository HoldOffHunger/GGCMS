<?php

		/*
			The belt, ChangeRecords, as modify.php and importer.php both wear it.
			Its own process, as ModifyPriceTest is: the scripts bring the HTML
			basicscript with them, and the rest of the suite must not see it.
		*/

		// Param() answered from a list, as a Query built from a post answers it
	class ChangeRecordsTestQuery {
		public $params;

		public function __construct($args) {
			$this->params = $args['params'];
		}

		public function Parameter($args) {
			return isset($this->params[$args['parameter']]) ? $this->params[$args['parameter']] : NULL;
		}
	}

		// The database, for the Backup stage: every RecordChange it is asked to write, and an answer for the rest
	class ChangeRecordsTestDatabase {
		public $written = [];
		public $fail = FALSE;
		public $chosen = [];

		public function CreateRecord($args) {
			if($this->fail) {
				return ['line'=>1, 'error'=>'refused'];
			}

			$this->written[] = $args['definition'];

			return $args['definition'] + ['id'=>count($this->written)];
		}

		public function GetStandardEntryRecordTypes() {
			return ['Tag'=>'tag', 'TextBody'=>'textbody'];
		}

		public function GetChosenAssociations($args) {
			return $this->chosen;
		}
	}

	#[PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
	class ChangeRecordsTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'scripts/Format/base_format.php']);
			$this->requireEngine(['file'=>'scripts/Format/HTML/basicscript.php']);
		}
		
			//  One script a process, as one script a request: both require the same traits.
		
		public function load($script) {
			$this->requireEngine(['file'=>'scripts/' . $script . '.php']);
		}

			// Answers Param() from a list, as a Query built from a post would
		public function subject($class, $params) {
			$subject = $this->newWithoutConstructor(['class'=>$class]);
			$subject->query_object = new ChangeRecordsTestQuery(['params'=>$params]);

			return $subject;
		}

			// Every stage, on both scripts, from the one set of traits
		public function testModifyRidesTheBelt() {
			$this->load('modify');
			$this->assertRidesTheBelt(['modify']);
		}
		
		public function testTheImporterRidesTheSameBelt() {
			$this->load('importer');
			$this->assertRidesTheBelt(['importer', 'importerrow']);
		}
		
		public function assertRidesTheBelt($classes) {
			$stages = [
				'SaveEntry', 'UpdateEntry', 'DeleteEntryWithChildRecords',
				'SetRecordFromQueryForAll', 'CleanseRecordFromQueryForAll', 'ValidateForSavingForAll',
				'PrepareRecordForSavingForAll', 'BackupOldRecord', 'DetectChangedRecords',
				'SaveRecordFromQueryForAll', 'SaveRecordFromQueryForUpdate', 'FormatSavedRecordFromQueryForAll',
				'DeleteChildRecordsForUpdate', 'FlushPageCacheAndReport', 'RecordsAsQuery', 'FormatDates',
			];

			foreach($classes as $class) {
				foreach($stages as $stage) {
					$method = new ReflectionMethod($class, $stage);
					$this->assertStringContainsString('/traits/scripts/ChangeRecords/', str_replace('\\', '/', $method->getFileName()), $class . '::' . $stage);
				}
			}
		}

			// modify.php's own updateFileName(), with its guard for an empty name, not SimpleORM's
		public function testTheBeltsUpdateFileNameWinsInModify() {
			$this->load('modify');
			$this->assertSame('', $this->newWithoutConstructor(['class'=>'modify'])->updateFileName(['filename'=>'']));
		}
		
		public function testTheBeltsUpdateFileNameWinsInTheImporter() {
			$this->load('importer');
			$this->assertSame('', $this->newWithoutConstructor(['class'=>'importerrow'])->updateFileName(['filename'=>'']));
		}

			// The Read stage takes its fields from the one table
		public function testEveryChildTypeIsInTheVocabulary() {
			$this->load('modify');
			$this->requireEngine(['file'=>'classes/Database/ORM.php']);
			
			$formal = array_column($this->newWithoutConstructor(['class'=>'modify'])->RecordQueryDefinitions(), 'formal');
			$orm = (new ReflectionClass('ORM'))->newInstanceWithoutConstructor()->GetChildRecordTypes();

			$this->assertSame([], array_values(array_diff(array_diff($orm, ['EntryPermission']), $formal)), 'every child record type the ORM knows is read from a request');
		}

			/*
				An entry's records posted back as RecordsAsQuery() writes them
				come off the Read stage as they went on.  That round trip is
				what lets the importer change one field and leave the rest.
			*/
		public function testRecordsComeBackAsTheyWent() {
			$this->load('importer');
			
			$records = [
				'tag'=>[['id'=>1, 'Tag'=>'verse', 'Language'=>'en'], ['id'=>2, 'Tag'=>'vers', 'Language'=>'fr']],
				'eventdate'=>[['id'=>3, 'EventDate'=>'-0500-03-01', 'EventTime'=>'12:00:00', 'Title'=>'Founded', 'Description'=>'', 'Language'=>'']],
				'link'=>[['id'=>4, 'Title'=>'Scans', 'URL'=>'https://archive.org/', 'Language'=>'']],
				'entrytranslation'=>[['id'=>5, 'Title'=>'coreano', 'Subtitle'=>'', 'ListTitle'=>'', 'ListTitleSortKey'=>'', 'Language'=>'es']],
			];

			$writer = $this->newWithoutConstructor(['class'=>'importerrow']);
			$post = $writer->RecordsAsQuery(['entry'=>['Title'=>'Korean', 'Code'=>'korean', 'Publish'=>'1'], 'records'=>$records]);

			$this->assertSame('Korean', $post['Title']);
			$this->assertSame(['verse', 'vers'], $post['Tag']);
			$this->assertSame(['en', 'fr'], $post['tag_Language']);

			$reader = $this->subject('importerrow', $post);

			foreach(['tag'=>'SetRecordFromQuery_Tag', 'eventdate'=>'SetRecordFromQuery_EventDate', 'link'=>'SetRecordFromQuery_Link', 'entrytranslation'=>'SetRecordFromQuery_EntryTranslation'] as $variable_name => $read) {
				$reader->$read();

				foreach($records[$variable_name] as $index => $record) {
					unset($record['id']);

					foreach($record as $field => $value) {
						$this->assertSame($value, $reader->$variable_name[$index][$field], $variable_name . ' ' . $index . ' ' . $field);
					}
				}
			}
		}

			// A text body's switches go one per text body, Spell Check Immune as it was stored and every cleaning off
		public function testTextBodySwitchesArePositional() {
			$this->load('importer');
			
			$post = $this->newWithoutConstructor(['class'=>'importerrow'])->RecordsAsQuery(['entry'=>[], 'records'=>['textbody'=>[
				['Text'=>'<p>One</p>', 'Source'=>'', 'Language'=>'en', 'SpellCheckImmune'=>'0'],
				['Text'=>'<p>Two</p>', 'Source'=>'', 'Language'=>'en', 'SpellCheckImmune'=>'1'],
			]]]);

			$this->assertSame(['', '1'], $post['textbody_SpellCheckImmune']);
			$this->assertSame(['', ''], $post['textbody_StripURLs']);
			$this->assertSame(['', ''], $post['textbody_AmericanizeVocabulary']);
		}

			// The importer is an administrator's page
		public function testTheImporterIsAdminOnly() {
			$this->load('importer');
			
			$importer = $this->newWithoutConstructor(['class'=>'importer']);

			$this->assertTrue($importer->IsSecure());
			$this->assertTrue($importer->RequiresLogin());
			$this->assertTrue($importer->AdminOnly());
		}

			// The Backup stage
			// -----------------------------------------------

		public function backup() {
			$this->load('importer');

			$database = new ChangeRecordsTestDatabase();

			$subject = $this->newWithoutConstructor(['class'=>'importerrow']);
			$subject->db_access_object = $database;
			$subject->orm = $database;
			$subject->authentication_object = (object)['user_session'=>['id'=>7]];
			$subject->errors = [];
			$subject->textbody = [];

			return $subject;
		}

			/*
				One tag deleted and another changed, in one update: the deleted
				one filed whole, the changed one by its field.  Until 11 November
				2026 neither was filed -- the deletion was kept under its id,
				40314, the change appended after it at 40315, and the loop
				counted 0 and 1.
			*/
		public function testADeletedRecordIsFiledWholeBesideAChangedOne() {
			$subject = $this->backup();

			$old_tags = [
				['id'=>'40314', 'Tag'=>'verse', 'Language'=>'en', 'Entryid'=>'50'],
				['id'=>'40320', 'Tag'=>'rhyme', 'Language'=>'en', 'Entryid'=>'50'],
			];

			$subject->entry = ['id'=>'50', 'Title'=>'Poetry', 'Code'=>'poetry'];
			$subject->entry_unset = ['id'=>'50', 'Title'=>'Poetry', 'Code'=>'poetry', 'tag'=>$old_tags];
			$subject->tag = [['id'=>'40320', 'Tag'=>'metre', 'Language'=>'en']];

			$this->assertTrue($subject->BackupOldRecord());

			$written = $subject->db_access_object->written;
			$this->assertCount(2, $written);

			$this->assertSame('*', $written[0]['RecordField']);
			$this->assertSame(40314, $written[0]['Recordid']);
			$this->assertSame('Tag', $written[0]['RecordType']);
			$this->assertSame(['id'=>'40314', 'Tag'=>'verse', 'Language'=>'en', 'Entryid'=>'50'], json_decode($written[0]['OldValue'], TRUE), 'every column, enough to put it back');

			$this->assertSame('Tag', $written[1]['RecordField']);
			$this->assertSame('40320', $written[1]['Recordid']);
			$this->assertSame('rhyme', $written[1]['OldValue']);
			$this->assertSame(7, $written[1]['Userid']);
		}

			// A removed record usually comes back as a blank row wearing its id, which Remove then deletes: still a deletion
		public function testARecordLeftBlankIsFiledWhole() {
			$subject = $this->backup();

			$subject->entry = ['id'=>'50418', 'Title'=>'Punjabi', 'Code'=>'punjabi'];
			$subject->entry_unset = ['id'=>'50418', 'Title'=>'Punjabi', 'Code'=>'punjabi', 'entrytranslation'=>[['id'=>'40312', 'Title'=>'panyabí', 'Language'=>'es']]];
			$subject->entrytranslation = [['id'=>'40312', 'Title'=>'', 'Language'=>'']];

			$subject->orm = new class extends ChangeRecordsTestDatabase {
				public function GetStandardEntryRecordTypes() {
					return ['EntryTranslation'=>'entrytranslation'];
				}
			};
			$subject->db_access_object = $subject->orm;

			$this->assertTrue($subject->BackupOldRecord());

			$written = $subject->db_access_object->written;
			$this->assertCount(1, $written);
			$this->assertSame('*', $written[0]['RecordField']);
			$this->assertSame(['id'=>'40312', 'Title'=>'panyabí', 'Language'=>'es'], json_decode($written[0]['OldValue'], TRUE));
		}

		public function testAFailedBackupStopsTheUpdate() {
			$subject = $this->backup();
			$subject->db_access_object->fail = TRUE;

			$subject->entry = ['id'=>'50', 'Title'=>'Prose', 'Code'=>'poetry'];
			$subject->entry_unset = ['id'=>'50', 'Title'=>'Poetry', 'Code'=>'poetry', 'tag'=>[]];
			$subject->tag = [];

			$this->assertFalse($subject->BackupOldRecord());
			$this->assertCount(1, $subject->errors);
		}

			// A deleted entry: the entry, its child records, its assignment and the associations to it, before anything goes
		public function testDeletingAnEntryFilesEverythingFirst() {
			$subject = $this->backup();
			$subject->db_access_object->chosen = [['id'=>'9', 'Entryid'=>'12', 'ChosenEntryid'=>'50', 'Type'=>'author']];

			$subject->entry = ['id'=>'50', 'Title'=>'Poetry', 'Code'=>'poetry', 'tag'=>[['id'=>'1']], 'description'=>NULL];
			$subject->parent = ['id'=>'4', 'Title'=>'Books'];
			$subject->tag = [['id'=>'1', 'Tag'=>'verse'], ['id'=>'2', 'Tag'=>'rhyme']];
			$subject->textbody = [['id'=>'3', 'Text'=>'<p>Dans le jardin, l\'été.</p>'], ['Text'=>'']];

			$this->assertTrue($subject->BackupDeletedEntry());

			$filed = array_map(function($row) { return $row['RecordType'] . ' ' . $row['Recordid']; }, $subject->db_access_object->written);
			$this->assertSame(['Entry 50', 'Tag 1', 'Tag 2', 'TextBody 3', 'Assignment 0', 'Association 9'], $filed, 'the blank text body that was never saved is not filed');

			foreach($subject->db_access_object->written as $row) {
				$this->assertSame('*', $row['RecordField']);
				$this->assertSame('50', $row['Entryid']);
			}

			$this->assertSame(['id'=>'50', 'Title'=>'Poetry', 'Code'=>'poetry'], json_decode($subject->db_access_object->written[0]['OldValue'], TRUE), 'the entry\'s own columns, not the child lists hung on it');
			$this->assertSame(['Parentid'=>'4', 'Childid'=>'50'], json_decode($subject->db_access_object->written[4]['OldValue'], TRUE));
			$this->assertStringContainsString('l\'été', json_decode($subject->db_access_object->written[3]['OldValue'], TRUE)['Text']);
		}

		public function testADeleteThatCannotBeBackedUpIsNotMade() {
			$subject = $this->backup();
			$subject->db_access_object->fail = TRUE;

			$subject->entry = ['id'=>'50', 'Title'=>'Poetry'];
			$subject->tag = [];

			$this->assertFalse($subject->BackupDeletedEntry());
		}

			// Text-body switches
			// -----------------------------------------------

		public function switches($args) {
			$this->load('modify');
			$this->requireEngine(['file'=>'modules/spacing.php']);
			$this->requireEngine(['file'=>'modules/html/modify-text-body.php']);

			$module = $this->newWithoutConstructor(['class'=>'module_modifytextbody']);
			$module->form = new module_form;
			$module->adding = FALSE;

			ob_start();
			$module->DisplaySwitches($args);

			return ob_get_clean();
		}

			/*
				Every row, the hidden one Add Text copies included, posts every
				switch -- a select always posts, where a clear checkbox posted
				nothing and moved the next text's value onto this one.
			*/
		public function testEveryTextBodyPostsEverySwitch() {
			$row = $this->switches(['record'=>['Text'=>'<p>Two</p>', 'SpellCheckImmune'=>'1'], 'mode'=>'row']);

			foreach(['textbody_StripURLs', 'textbody_AmericanizeVocabulary', 'textbody_HTMLFormatting', 'textbody_SpellCheckImmune'] as $name) {
				$this->assertStringContainsString('<select  name="' . $name . '[]"', $row, $name);
			}

			$this->assertStringNotContainsString('checkbox', $row);
			$this->assertMatchesRegularExpression('/name="textbody_SpellCheckImmune\[\]".*?<option value="1" selected="SELECTED">Yes/s', $row, 'immune, as stored');
			$this->assertMatchesRegularExpression('/name="textbody_StripURLs\[\]".*?<option value="" selected="SELECTED">No/s', $row, 'an existing text has every cleaning off');

			$hidden = $this->switches(['record'=>NULL, 'mode'=>'hidden']);
			$this->assertStringContainsString('name="textbody_SpellCheckImmune-Hidden"', $hidden, 'the row Add Text copies has them too, renamed to [] when copied');
		}
	}

?>

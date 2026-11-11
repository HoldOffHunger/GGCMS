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
	}

?>

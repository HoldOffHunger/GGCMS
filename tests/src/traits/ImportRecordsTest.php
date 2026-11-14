<?php

	ggreq('traits/scripts/ChangeRecords/ChangeRecordsVocabulary.php');
	foreach(['Headers', 'Plan', 'Belt', 'Images', 'Run'] as $stage) {
		ggreq('traits/scripts/ImportRecords/ImportRecords' . $stage . '.php');		# not the whole of ImportRecords.php: it requires ImportFile, which ImportFileTest has
	}

		// The importer's understanding of a file, without a database: what each column is, and which rows are one entry
	class ImportRecordsTestSubject {
		use ChangeRecordsVocabulary;
		use ImportRecordsHeaders;
		use ImportRecordsPlan;
		use ImportRecordsBelt;
		use ImportRecordsImages;
		use ImportRecordsRun;

		public $owners = [];

		public function lookup() {
			return function($args) {
				return isset($this->owners[$args['type'] . ':' . $args['id']]) ? $this->owners[$args['type'] . ':' . $args['id']] : 0;
			};
		}
	}

	class ImportRecordsTest extends GGCMSTestCase {
		public function subject() {
			return new ImportRecordsTestSubject();
		}

		public function rows($subject, $table, $headers, $cells_list) {
			$header = $subject->ParseImportHeaders(['headers'=>$headers, 'table'=>$table]);
			$this->assertSame([], $header['unknown']);

			$rows = [];

			foreach($cells_list as $index => $cells) {
				$rows[] = $subject->ReadImportRow(['cells'=>$cells, 'columns'=>$header['columns'], 'line'=>$index + 2]);
			}

			return $rows;
		}

		public function plan($table, $headers, $cells_list, $owners = []) {
			$subject = $this->subject();
			$subject->owners = $owners;

			return $subject->PlanImport(['rows'=>$this->rows($subject, $table, $headers, $cells_list), 'table'=>$table, 'lookup'=>$subject->lookup()]);
		}

			// Headers
			// -----------------------------------------------

		public function testHeadersNameTheBeltsOwnFields() {
			$subject = $this->subject();

			$this->assertSame(['kind'=>'entryfield', 'parameter'=>'Title'], $subject->ParseImportHeader(['header'=>'Entry_Title', 'table'=>'Entry']));
			$this->assertSame(['kind'=>'entryfield', 'parameter'=>'Title'], $subject->ParseImportHeader(['header'=>'entry_title', 'table'=>'Entry']), 'case does not matter');
			$this->assertSame('tag_Language', $subject->ParseImportHeader(['header'=>'Tag_Language', 'table'=>'Entry'])['parameter']);
			$this->assertSame('Tag', $subject->ParseImportHeader(['header'=>'Tag_Tag', 'table'=>'Entry'])['parameter']);
			$this->assertSame('Text', $subject->ParseImportHeader(['header'=>'TextBody_Text', 'table'=>'Entry'])['parameter']);
			$this->assertSame('URL', $subject->ParseImportHeader(['header'=>'Link_URL', 'table'=>'Entry'])['parameter']);
			$this->assertSame('entrytranslation_Title', $subject->ParseImportHeader(['header'=>'EntryTranslation_Title', 'table'=>'Entry'])['parameter']);
			$this->assertSame('image_FileName', $subject->ParseImportHeader(['header'=>'Image_FileName', 'table'=>'Entry'])['parameter']);
		}

		public function testTheImportersOwnColumns() {
			$subject = $this->subject();

			$kinds = [
				'Entry_id'=>'entryid',
				'Entry_Identifier'=>'identifier',
				'Entry_ParentIdentifier'=>'parentidentifier',
				'Entry_ParentPath'=>'parentpath',
				'Assignment_Parentid'=>'parentid',
				'Delete'=>'delete',
				'Tag_id'=>'childid',
				'Tag_Entryid'=>'entryid',
				'Option_title-smart-title-case'=>'option',
				'TextBody_SpellCheckImmune'=>'childoption',
			];

			foreach($kinds as $header => $kind) {
				$this->assertSame($kind, $subject->ParseImportHeader(['header'=>$header, 'table'=>'Entry'])['kind'], $header);
			}

			$guard = $subject->ParseImportHeader(['header'=>'Current_EntryTranslation_Title', 'table'=>'Entry']);
			$this->assertSame('childfield', $guard['kind']);
			$this->assertTrue($guard['guard']);
		}

			// A Tag import's id is a Tag's id, and Tag its Tag
		public function testABareHeaderBelongsToTheImportsTable() {
			$subject = $this->subject();

			$this->assertSame(['kind'=>'childid', 'table'=>'Tag', 'variablename'=>'tag'], $subject->ParseImportHeader(['header'=>'id', 'table'=>'Tag']));
			$this->assertSame('tag_Language', $subject->ParseImportHeader(['header'=>'Language', 'table'=>'Tag'])['parameter']);
			$this->assertSame('entryid', $subject->ParseImportHeader(['header'=>'id', 'table'=>'Entry'])['kind']);
		}

		public function testAnUnknownHeaderIsNamed() {
			$header = $this->subject()->ParseImportHeaders(['headers'=>['Entry_Title', 'Entry_Titel', 'Tag_Colour', 'Option_make-it-good', 'Current_Delete'], 'table'=>'Entry']);

			$this->assertSame(['Entry_Titel', 'Tag_Colour', 'Option_make-it-good', 'Current_Delete'], $header['unknown']);
		}

			// The second Tag_Tag goes with the second Tag_Language
		public function testRepeatedColumnsAreRecordsInOrder() {
			$subject = $this->subject();
			$rows = $this->rows($subject, 'Entry', ['Entry_Title', 'Tag_Tag', 'Tag_Language', 'Tag_Tag', 'Tag_Language'], [['Poetry', 'verse', 'en', 'vers', 'fr']]);

			$tags = $rows[0]['children']['tag'];
			$this->assertSame(['Tag'=>'verse', 'tag_Language'=>'en'], $tags[0]['fields']);
			$this->assertSame(['Tag'=>'vers', 'tag_Language'=>'fr'], $tags[1]['fields']);
		}

		public function testTheFieldListForTheHeaderBuilder() {
			$subject = $this->subject();

			$entry = array_keys($subject->ImportTableFields(['table'=>'Entry']));
			$this->assertSame('Entry_id', $entry[0]);
			$this->assertContains('Entry_Title', $entry);
			$this->assertContains('Entry_Identifier', $entry);
			$this->assertSame('Delete', $entry[count($entry) - 1]);

			$tag = array_keys($subject->ImportTableFields(['table'=>'tag']));
			$this->assertSame(['Tag_id', 'Entry_id', 'Entry_Identifier', 'Tag_Tag', 'Tag_Language', 'Delete'], $tag);

			$this->assertSame([], $subject->ImportTableFields(['table'=>'Nonsense']));

				// Every field the header builder offers, the parser takes
			foreach(array_keys($subject->ImportTables()) as $table) {
				foreach(array_keys($subject->ImportTableFields(['table'=>$table])) as $field) {
					$this->assertNotNull($subject->ParseImportHeader(['header'=>$field, 'table'=>$table]), $table . ': ' . $field);
				}
			}
		}

			// Gathering rows into entries
			// -----------------------------------------------

			// Two rows titled alike are one entry with two tags; an identifier makes them two
		public function testRowsTitledAlikeAreOneEntryUnlessIdentified() {
			$plan = $this->plan('Entry', ['Entry_Title', 'Tag_Tag'], [['Poetry', 'verse'], ['Poetry', 'rhyme']]);
			$this->assertCount(1, $plan['groups']);
			$this->assertSame([0, 1], reset($plan['groups'])['rows']);
			$this->assertSame('create', reset($plan['groups'])['action']);

			$plan = $this->plan('Entry', ['Entry_Identifier', 'Entry_Title', 'Tag_Tag'], [['a', 'Poetry', 'verse'], ['b', 'Poetry', 'rhyme'], ['a', '', 'metre']]);
			$this->assertCount(2, $plan['groups']);
			$this->assertSame([0, 2], $plan['groups']['identifier:a']['rows']);
		}

		public function testTheSameTitleUnderDifferentParentsIsTwoEntries() {
			$plan = $this->plan('Entry', ['Entry_ParentPath', 'Entry_Title'], [['/books/', 'Index'], ['/films/', 'Index']]);

			$this->assertCount(2, $plan['groups']);
		}

		public function testAnEntryIdIsAnUpdate() {
			$plan = $this->plan('Entry', ['Entry_id', 'Entry_Title'], [['12', 'New Title']]);

			$this->assertSame('update', $plan['groups']['id:12']['action']);
			$this->assertSame(['Title'=>'New Title'], $plan['groups']['id:12']['entryfields']);
		}

			// EntryTranslation 40314 belongs to Entry 50420, and the row needs no Entry_id to say so
		public function testAChildRecordsIdNamesItsEntry() {
			$plan = $this->plan('EntryTranslation', ['id', 'Title'], [['40314', 'javanés']], ['EntryTranslation:40314'=>50420]);

			$this->assertSame('update', $plan['groups']['id:50420']['action']);

			$plan = $this->plan('EntryTranslation', ['id', 'Title'], [['999', 'x']]);
			$this->assertSame(['There is no EntryTranslation with id 999.'], $plan['rows'][0]['errors']);

			$plan = $this->plan('Entry', ['Entry_id', 'EntryTranslation_id'], [['7', '40314']], ['EntryTranslation:40314'=>50420]);
			$this->assertSame(['EntryTranslation 40314 belongs to Entry 50420, not Entry 7.'], $plan['rows'][0]['errors']);
		}

		public function testDelete() {
			$plan = $this->plan('Entry', ['Entry_id', 'Delete'], [['12', 'Y'], ['13', 'yes'], ['14', 'no']]);

			$this->assertSame('delete', $plan['groups']['id:12']['action']);
			$this->assertSame('delete', $plan['groups']['id:13']['action']);
			$this->assertSame('update', $plan['groups']['id:14']['action'], 'only Y and YES delete');

			$plan = $this->plan('Entry', ['Entry_Title', 'Delete'], [['Poetry', 'Y']]);
			$this->assertSame(['Delete needs the Entry_id of the entry to delete.'], $plan['rows'][0]['errors']);

			$plan = $this->plan('Entry', ['Entry_id', 'Entry_Title', 'Delete'], [['12', 'Poetry', 'Y']]);
			$this->assertSame(['A row that deletes an entry may name nothing else about it.'], $plan['rows'][0]['errors']);

			$plan = $this->plan('Tag', ['id', 'Delete'], [['5', 'Y']], ['Tag:5'=>12]);
			$this->assertSame('update', $plan['groups']['id:12']['action'], 'deleting a tag is an update of its entry');
			$this->assertSame(['what'=>'child', 'variablename'=>'tag', 'id'=>'5'], $plan['rows'][0]['deletes']);
		}

			// A delete beside other changes to the same entry: the other rows say why they were not done
		public function testNothingElseIsDoneToAnEntryBeingDeleted() {
			$plan = $this->plan('Entry', ['Entry_id', 'Tag_Tag', 'Delete'], [['12', 'verse', ''], ['12', '', 'Y']]);

			$this->assertSame('delete', $plan['groups']['id:12']['action']);
			$this->assertSame(['Another row deletes this entry, so nothing else may be done to it.'], $plan['rows'][0]['errors']);
			$this->assertSame([], $plan['rows'][1]['errors']);
		}

		public function testRowsOfOneEntryMayNotDisagree() {
			$plan = $this->plan('Entry', ['Entry_Identifier', 'Entry_Title'], [['a', 'Poetry'], ['a', 'Prose']]);
			$this->assertSame(['Entry_Title is "Prose" here and "Poetry" on line 2, for the same entry.'], $plan['rows'][1]['errors']);

			$plan = $this->plan('Entry', ['Entry_Identifier', 'Entry_ParentPath', 'Entry_Title'], [['a', '/books/', 'Poetry'], ['a', '/films/', '']]);
			$this->assertSame(['This row files the entry under a different parent from line 2.'], $plan['rows'][1]['errors']);
		}

			// An existing entry's row may name a parent, which moves it; two rows of it may not name two
		public function testAnExistingEntryMayBeMoved() {
			$plan = $this->plan('Entry', ['Entry_id', 'Entry_ParentPath'], [['12', '/films/']]);
			$this->assertSame([], $plan['rows'][0]['errors']);
			$this->assertSame('update', $plan['groups']['id:12']['action']);
			$this->assertSame('path:films', $plan['groups']['id:12']['parent']);

			$plan = $this->plan('Entry', ['Entry_id', 'Assignment_Parentid'], [['12', '7'], ['12', '8']]);
			$this->assertSame(['This row files the entry under a different parent from line 2.'], $plan['rows'][1]['errors']);

			$plan = $this->plan('Entry', ['Entry_id', 'Entry_Title'], [['12', 'Films']]);
			$this->assertNull($plan['groups']['id:12']['parent'], 'no parent named, no move');

				// Moved under an entry this file makes: it waits for that entry
			$plan = $this->plan('Entry', ['Entry_id', 'Entry_Identifier', 'Entry_ParentIdentifier', 'Entry_Title'], [['12', '', 'nouns', ''], ['', 'nouns', '', 'Nouns']]);
			$ordered = $this->subject()->OrderImportGroups(['groups'=>$plan['groups']]);
			$this->assertSame(['identifier:nouns', 'id:12'], $ordered['order']);
		}

		public function testImportMoveDetail() {
			$subject = $this->subject();

			$rows = [3=>['outcome'=>'Unchanged', 'details'=>['Entry 12 already says all of this.'], 'entryid'=>12]];
			$args = ['rows'=>$rows, 'group'=>['entryid'=>12], 'current'=>['path'=>['books', 'index']], 'move'=>['targetid'=>7, 'where'=>'/films/', 'to'=>'/films/index/'], 'apply'=>TRUE];

			$moved = $subject->ImportMoveDetail($args);
			$this->assertSame('Transferred old record', $moved[3]['outcome']);
			$this->assertSame(['Transferred Entry 12 from /books/index/ to /films/index/, with everything beneath it.'], $moved[3]['details']);

			$args['apply'] = FALSE;
			$this->assertSame(['Would transfer Entry 12 from /books/index/ to /films/index/, with everything beneath it.'], $subject->ImportMoveDetail($args)[3]['details']);

				// A row that also changed a field is still counted as the transfer
			$args['rows'][3] = ['outcome'=>'Updated old record', 'details'=>['Changed Entry 12 Title from "Index" to "Contents".'], 'entryid'=>12];
			$both = $subject->ImportMoveDetail($args);
			$this->assertSame('Transferred old record', $both[3]['outcome']);
			$this->assertCount(2, $both[3]['details']);
			$args['rows'] = $rows;

			$args['move'] = ['same'=>TRUE, 'where'=>'/films/'];
			$same = $subject->ImportMoveDetail($args);
			$this->assertSame('Unchanged', $same[3]['outcome']);
			$this->assertSame(['Entry 12 is already filed under /films/.', 'Entry 12 already says all of this.'], $same[3]['details']);

				// A parent this file makes: still a transfer, and no longer "nothing to change"
			$args['move'] = ['unchecked'=>'nouns'];
			$unchecked = $subject->ImportMoveDetail($args);
			$this->assertSame('Transferred old record', $unchecked[3]['outcome']);
			$this->assertSame(['Would transfer Entry 12 to beneath its parent, nouns, which is new, so the transfer was not checked.'], $unchecked[3]['details']);

			$args['move'] = NULL;
			$this->assertSame($rows, $subject->ImportMoveDetail($args), 'no parent named, nothing said');
		}

		public function testARowThatNamesNoEntry() {
			$plan = $this->plan('Entry', ['Tag_Tag'], [['verse']]);

			$this->assertStringStartsWith('Nothing says which entry this row is', $plan['rows'][0]['errors'][0]);
		}

		public function testIdsMustBeNumbers() {
			$plan = $this->plan('Entry', ['Entry_id', 'Tag_id'], [['abc', ''], ['', 'x1']]);

			$this->assertSame(['Entry_id must be a number, not "abc".'], $plan['rows'][0]['errors']);
			$this->assertSame(['Tag_id must be a number, not "x1".'], $plan['rows'][1]['errors']);
		}

			// Order: a child waits for the parent this file makes, however late it comes
		public function testAChildWaitsForItsParent() {
			$subject = $this->subject();
			$plan = $this->plan('Entry', ['Entry_Identifier', 'Entry_ParentIdentifier', 'Entry_Title'], [
				['w1', 'lesson', 'Hello'],
				['lesson', '', 'Lesson One'],
				['w2', 'lesson', 'Goodbye'],
				['orphan', 'nobody', 'Lost'],
			]);

			$ordered = $subject->OrderImportGroups(['groups'=>$plan['groups']]);

			$this->assertSame('identifier:lesson', $ordered['order'][0]);
			$this->assertSame(['identifier:lesson', 'identifier:orphan', 'identifier:w1', 'identifier:w2'], $this->sorted($ordered['order']));
			$this->assertLessThan(array_search('identifier:w1', $ordered['order']), array_search('identifier:lesson', $ordered['order']));
			$this->assertSame('No row of this file has the Entry_Identifier nobody to file it under.', $ordered['groups']['identifier:orphan']['unplaceable']);
		}

		public function testAParentLoopIsCaught() {
			$subject = $this->subject();
			$plan = $this->plan('Entry', ['Entry_Identifier', 'Entry_ParentIdentifier', 'Entry_Title'], [['a', 'b', 'A'], ['b', 'a', 'B']]);

			$ordered = $subject->OrderImportGroups(['groups'=>$plan['groups']]);

			$this->assertCount(2, $ordered['order']);
			$this->assertArrayHasKey('unplaceable', $ordered['groups']['identifier:a']);
		}

		public function sorted($list) {
			sort($list);

			return $list;
		}

			// Reporting
			// -----------------------------------------------

		public function testTheReportSaysWhatEachRowDid() {
			$report = [
				'apply'=>TRUE,
				'table'=>'Entry',
				'format'=>'tsv',
				'images'=>0,
				'fileerrors'=>[],
				'pagecache'=>'',
				'counts'=>['New record'=>1, 'Error'=>1],
				'rows'=>[
					['line'=>2, 'outcome'=>'New record', 'details'=>['Made Entry 5 at /books/poetry/.']],
					['line'=>3, 'outcome'=>'Error', 'details'=>['Entry_id must be a number, not "abc".']],
				],
			];

			$subject = $this->subject();
			$text = $subject->ImportReportText(['report'=>$report]);

			$this->assertStringContainsString('Line 2      New record', $text);
			$this->assertStringContainsString('Made Entry 5 at /books/poetry/.', $text);
			$this->assertStringContainsString('Entry_id must be a number', $text);
			$this->assertTrue($subject->ImportReportHasErrors(['report'=>$report]));

			$errors_only = $subject->ImportReportText(['report'=>$report, 'only'=>['Error']]);
			$this->assertStringNotContainsString('Made Entry 5', $errors_only);
			$this->assertStringContainsString('Entry_id must be a number', $errors_only);
		}

		public function testPreviewWording() {
			$subject = $this->subject();

			$this->assertSame('Would change Tag 5 Tag from "a" to "b".', $subject->ImportPreviewWording(['detail'=>'Changed Tag 5 Tag from "a" to "b".']));
			$this->assertSame('Would delete Tag 5 from Entry 2.', $subject->ImportPreviewWording(['detail'=>'Deleted Tag 5 from Entry 2.']));
			$this->assertSame('Entry 2 already says all of this.', $subject->ImportPreviewWording(['detail'=>'Entry 2 already says all of this.']));
		}

		public function testDuplicatesAreFoundByEveryFieldGiven() {
			$subject = $this->subject();
			$existing = [['id'=>1, 'Tag'=>'verse', 'Language'=>'en']];

			$this->assertTrue($subject->ImportRecordAlreadyThere(['records'=>$existing, 'variablename'=>'tag', 'record'=>['fields'=>['Tag'=>'verse']]]));
			$this->assertTrue($subject->ImportRecordAlreadyThere(['records'=>$existing, 'variablename'=>'tag', 'record'=>['fields'=>['Tag'=>'verse', 'tag_Language'=>'en']]]));
			$this->assertFalse($subject->ImportRecordAlreadyThere(['records'=>$existing, 'variablename'=>'tag', 'record'=>['fields'=>['Tag'=>'verse', 'tag_Language'=>'fr']]]));
		}

			// New records go on the end of the post, every field of the type filled so the positions line up
		public function testNewRecordsAreAppendedInStep() {
			$subject = $this->subject();
			$post = $subject->RecordsAsQuery(['entry'=>['Title'=>'Poetry'], 'records'=>['tag'=>[['id'=>1, 'Tag'=>'verse', 'Language'=>'en']]]]);

			$post = $subject->ImportAppendRecords(['post'=>$post, 'new'=>TRUE, 'records'=>['tag'=>[['fields'=>['Tag'=>'rhyme']]]]]);

			$this->assertSame(['verse', 'rhyme'], $post['Tag']);
			$this->assertSame(['en', ''], $post['tag_Language']);
		}

		public function testImportPageCacheReport() {
			$subject = $this->subject();

				// Nothing written, nothing to say: a preview, or an import whose every row was skipped
			$this->assertSame('', $subject->ImportPageCacheReport(['apply'=>FALSE, 'wrote'=>FALSE, 'keepcache'=>FALSE]));
			$this->assertSame('', $subject->ImportPageCacheReport(['apply'=>TRUE, 'wrote'=>FALSE, 'keepcache'=>TRUE]));

				// Asked to keep the cache, it flushes nothing and says what is now owed
			$kept = $subject->ImportPageCacheReport(['apply'=>TRUE, 'wrote'=>TRUE, 'keepcache'=>TRUE]);
			$this->assertStringContainsString('left as it was', $kept);
			$this->assertStringContainsString('warm_page_cache.php --url=', $kept);
		}

		public function testImportResolvedValue() {
			$subject = $this->subject();
			$subject->import_registry = ['lesson-1'=>['id'=>76006, 'path'=>['spanish', 'patterns', 'i-speak']], 'unmade'=>['id'=>0, 'path'=>NULL]];

				// An entry the file made a row ago, named by its identifier where its id belongs
			$this->assertSame('76006', $subject->ImportResolvedValue(['parameter'=>'wordform_BuiltFromEntryid', 'value'=>'identifier:lesson-1']));

				// A preview made nothing, and a name the file never gave is nothing too
			$this->assertSame('0', $subject->ImportResolvedValue(['parameter'=>'wordform_BuiltFromEntryid', 'value'=>'identifier:unmade']));
			$this->assertSame('0', $subject->ImportResolvedValue(['parameter'=>'wordform_BuiltFromEntryid', 'value'=>'identifier:nobody']));

				// An id is an id, and only a field that holds an entry's id is read this way
			$this->assertSame('12', $subject->ImportResolvedValue(['parameter'=>'wordform_BuiltFromEntryid', 'value'=>'12']));
			$this->assertSame('identifier:lesson-1', $subject->ImportResolvedValue(['parameter'=>'wordform_BaseWord', 'value'=>'identifier:lesson-1']));
		}

		public function testTheLessonBoardsTablesAreImported() {
			$subject = $this->subject();

			$piece = $subject->ParseImportHeader(['header'=>'WordPiece_Piece', 'table'=>'Entry']);
			$this->assertSame(['childfield', 'WordPiece', 'wordpiece_Piece'], [$piece['kind'], $piece['table'], $piece['parameter']]);

			$built = $subject->ParseImportHeader(['header'=>'WordForm_BuiltFromEntryid', 'table'=>'Entry']);
			$this->assertSame(['childfield', 'WordForm', 'wordform_BuiltFromEntryid'], [$built['kind'], $built['table'], $built['parameter']]);

			$this->assertNull($subject->ParseImportHeader(['header'=>'WordPiece_Colour', 'table'=>'Entry']));
		}
	}

?>

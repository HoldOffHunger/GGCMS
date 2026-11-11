<?php

		// The table descriptions, as DBAccess::GetRecordDescription() answers for a hardcoded table
	class ModifyAcademicReferenceTestDBAccess {
		public function GetRecordDescription($args) {
			$function = 'HardcodedTable_' . $args['type'];

			return (new ReflectionClass('HardcodedTableDescriptions'))->newInstanceWithoutConstructor()->$function();
		}
	}

		// The books that back a word, and their publication details: EarthFluent's citations, 10 Nov 2026.  Its own
		// process, because modify.php brings the whole script layer and the HTML basicscript with it
	#[PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
	class ModifyAcademicReferenceTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'scripts/Format/base_format.php']);
			$this->requireEngine(['file'=>'scripts/Format/HTML/basicscript.php']);
			$this->requireEngine(['file'=>'scripts/modify.php']);
			$this->requireEngine(['file'=>'classes/Database/HardcodedTableDescriptions.php']);
		}

		public function subject($references, $publications = []) {
			$modify = $this->newWithoutConstructor(['class'=>'modify']);
			$modify->handler = (object)['db_access'=>new ModifyAcademicReferenceTestDBAccess];
			$modify->academicreference = $references;
			$modify->publication = $publications;
			$modify->errors = [];

			return $modify;
		}

		public function reference($fields = []) {
			return $fields + [
				'ReferenceEntryid'=>'75901', 'Headword'=>'turkey', 'VolumeNumber'=>'', 'VolumeTitle'=>'', 'ChapterNumber'=>'',
				'ChapterTitle'=>'', 'Page'=>'343', 'Verdict'=>'supports', 'Note'=>'',
			];
		}

		public function testAcademicReferenceVerdicts() {
			$this->assertSame(['supports', 'conflicts', 'mentions'], $this->subject([])->AcademicReferenceVerdicts());
		}

			// Written twice, in the schema and in the description the form reads, and the two must agree

		public function testAcademicReferenceVerdictEnumMatchesSchema() {
			$schema = file_get_contents(GGCMS_CLI_DIR . 'sql/clonefrom.sql');

			$this->assertSame(1, preg_match("/CREATE TABLE `AcademicReference` \(.*?`Verdict` (enum\([^)]*\))/s", $schema, $matches));

			$description = $this->newWithoutConstructor(['class'=>'HardcodedTableDescriptions'])->HardcodedTable_AcademicReference();

			$this->assertSame($matches[1], $description['Verdict']['Type']);
		}

			// Every column the descriptions name is in the schema, so a new site gets the table the engine expects

		public function testDescriptionsMatchSchemaColumns() {
			$schema = file_get_contents(GGCMS_CLI_DIR . 'sql/clonefrom.sql');
			$descriptions = $this->newWithoutConstructor(['class'=>'HardcodedTableDescriptions']);

			foreach(['AcademicReference', 'Publication'] as $table) {
				$this->assertSame(1, preg_match("/CREATE TABLE `" . $table . "` \((.*?)\) ENGINE/s", $schema, $matches), $table);
				preg_match_all('/^\s+`(\w+)`/m', $matches[1], $columns);

				$function = 'HardcodedTable_' . $table;
				$this->assertSame($columns[1], array_keys($descriptions->$function()), $table);
			}
		}

		public function testValidateRecordForSaving_AcademicReference() {
			$this->assertTrue($this->subject([$this->reference()])->ValidateRecordForSaving_AcademicReference());

				// The blank row every form posts: no book, its verdict already chosen
			$this->assertTrue($this->subject([$this->reference(['ReferenceEntryid'=>''])])->ValidateRecordForSaving_AcademicReference());

			foreach([['ReferenceEntryid'=>'oxford'], ['ReferenceEntryid'=>'-4'], ['Verdict'=>'agrees'], ['Verdict'=>''], ['Page'=>str_repeat('9', 33)]] as $bad) {
				$modify = $this->subject([$this->reference($bad)]);

				$this->assertFalse($modify->ValidateRecordForSaving_AcademicReference(), json_encode($bad));
				$this->assertCount(1, $modify->errors, json_encode($bad));
			}
		}

		public function testValidateRecordForSaving_Publication() {
			$this->assertTrue($this->subject([], [['Authors'=>'María Moliner', 'ISBN'=>'9780613094306']])->ValidateRecordForSaving_Publication());
			$this->assertFalse($this->subject([], [['ISBN'=>str_repeat('9', 33)]])->ValidateRecordForSaving_Publication());
		}

			// An Update leaves alone a type its form never showed: EarthFluent's translations, deleted on 10 Nov 2026

		public function testDeleteChildRecordsForUpdate_OnForm() {
			$modify = $this->newWithoutConstructor(['class'=>'modify']);
			$modify->handler = (object)['globals'=>new class {
				public function ShowModifiableFields() {
					return ['EntryTranslation'=>FALSE, 'Tag'=>TRUE, 'AvailabilityStart'=>FALSE];
				}
			}];

			$this->assertFalse($modify->DeleteChildRecordsForUpdate_OnForm(['type'=>'EntryTranslation']));
			$this->assertTrue($modify->DeleteChildRecordsForUpdate_OnForm(['type'=>'Tag']));
			$this->assertFalse($modify->DeleteChildRecordsForUpdate_OnForm(['type'=>'AvailabilityDateRange']));

				// A type the list never names is taken as shown, as it always was
			$this->assertTrue($modify->DeleteChildRecordsForUpdate_OnForm(['type'=>'Image']));
		}

		public function testPrepareRecordForSaving_AcademicReference() {
			$modify = $this->subject([
				$this->reference(['Page'=>' 343 ']),
				$this->reference(['ReferenceEntryid'=>' ', 'Verdict'=>'supports']),
			]);

			$this->assertTrue($modify->PrepareRecordForSaving_AcademicReference());

				// Two rows still, in place, because ids are matched to them by position; the bookless one blanked
			$this->assertCount(2, $modify->academicreference);
			$this->assertSame('343', $modify->academicreference[0]['Page']);
			$this->assertSame('supports', $modify->academicreference[0]['Verdict']);
			$this->assertSame('', $modify->academicreference[1]['ReferenceEntryid']);
			$this->assertSame('', $modify->academicreference[1]['Verdict']);
		}
	}

?>

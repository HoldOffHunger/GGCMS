<?php

		// A word's citation line, as its page prints it, 10 Nov 2026
	class EntryAcademicReferencesTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'modules/spacing.php']);
			$this->requireEngine(['file'=>'modules/html/entry-academic-references.php']);
		}

		public function subject($entry) {
			$module = $this->newWithoutConstructor(['class'=>'module_entryacademicreferences']);
			$module->that = (object)[
				'entry'=>$entry,
				'handler'=>(object)['abstractglobals'=>(object)['child_types'=>new class {
					public function AcademicReference_enabled() { return TRUE; }
					public function Publication_enabled() { return TRUE; }
				}]],
			];

			return $module;
		}

		public function testCitation() {
			$book = ['id'=>75901, 'Title'=>'Diccionario de uso del español', 'Subtitle'=>'', 'Assignmentid'=>75902, 'publication'=>[
				'Authors'=>'María Moliner', 'Editors'=>'', 'Translators'=>'', 'Publisher'=>'Gredos', 'Edition'=>'', 'Year'=>'', 'ISBN'=>'',
			]];
			$reference = ['Headword'=>'pavo', 'VolumeNumber'=>'2', 'VolumeTitle'=>'H–Z', 'ChapterNumber'=>'', 'ChapterTitle'=>'', 'Page'=>'643', 'Note'=>''];

			$this->assertSame(
				'<cite><a href="/?id=75902">Diccionario de uso del español</a></cite>, Volume 2: H–Z, page 643, entry &ldquo;pavo&rdquo;. By María Moliner. Gredos.',
				$this->subject([])->Citation(['book'=>$book, 'reference'=>$reference])
			);
		}

			// Everything filled in, and the commas only where both sides have something

		public function testCitationInFull() {
			$book = ['id'=>1, 'Title'=>'A Grammar', 'Subtitle'=>'Of Spanish', 'Assignmentid'=>2, 'publication'=>[
				'Authors'=>'J. Butt', 'Editors'=>'C. Benjamin', 'Translators'=>'A. Moreira', 'Publisher'=>'Routledge', 'Edition'=>'Sixth edition', 'Year'=>'2019', 'ISBN'=>'9781138',
			]];
			$reference = ['Headword'=>'ser', 'VolumeNumber'=>'1', 'VolumeTitle'=>'', 'ChapterNumber'=>'3', 'ChapterTitle'=>'The verb', 'Page'=>'40', 'Note'=>''];

			$this->assertSame(
				'<cite><a href="/?id=2">A Grammar: Of Spanish</a></cite>, Volume 1, Chapter 3: The verb, page 40, entry &ldquo;ser&rdquo;. By J. Butt. Edited by C. Benjamin. Translated by A. Moreira. Routledge, Sixth edition, 2019. ISBN 9781138.',
				$this->subject([])->Citation(['book'=>$book, 'reference'=>$reference])
			);

				// A chapter with only a title, and nothing else but the book
			$reference = ['Headword'=>'', 'VolumeNumber'=>'', 'VolumeTitle'=>'', 'ChapterNumber'=>'', 'ChapterTitle'=>'Animals', 'Page'=>'', 'Note'=>''];
			$book['publication'] = NULL;

			$this->assertSame('<cite><a href="/?id=2">A Grammar: Of Spanish</a></cite>, Chapter Animals.', $this->subject([])->Citation(['book'=>$book, 'reference'=>$reference]));
		}

			// Escaped: a citation is typed by hand on modify.php

		public function testCitationEscapes() {
			$book = ['id'=>1, 'Title'=>'<b>Bold</b> & Co', 'Subtitle'=>'', 'Assignmentid'=>0, 'publication'=>NULL];
			$reference = ['Headword'=>'"x"', 'VolumeNumber'=>'', 'VolumeTitle'=>'', 'ChapterNumber'=>'', 'ChapterTitle'=>'', 'Page'=>'', 'Note'=>''];

			$citation = $this->subject([])->Citation(['book'=>$book, 'reference'=>$reference]);

			$this->assertStringNotContainsString('<b>', $citation);
			$this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt; &amp; Co', $citation);
		}

			// A book that disagrees is kept for review, not shown to a reader as proof

		public function testShownReferences() {
			$entry = ['academicreference'=>[
				['ReferenceEntryid'=>'1', 'Verdict'=>'supports'],
				['ReferenceEntryid'=>'2', 'Verdict'=>'conflicts'],
				['ReferenceEntryid'=>'3', 'Verdict'=>'mentions'],
				['ReferenceEntryid'=>'', 'Verdict'=>''],
			]];

			$this->assertSame(['1', '3'], array_column($this->subject($entry)->ShownReferences(), 'ReferenceEntryid'));
		}
	}

?>

<?php

	/*
		module_entryformats builds every document format but the reading
		page.  These are its PDF-only pieces: the cover and the chapter
		anchors PDFPageFrame reads.
	*/

	class EntryFormatsTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'modules/spacing.php']);
			$this->requireEngine(['file'=>'modules/html/entry-formats.php']);
		}

		public function newFormats($args) {
			$that = (object)[
				'script_format_lower'=>$args['format'],
				'object_list'=>['anarchism', 'mutual-aid'],
				'entry'=>['Title'=>'Mutual Aid', 'Subtitle'=>'', 'association'=>[]],
				'domain_object'=>new class {
					public $primary_domain = 'RevoltLib.com';

					public function GetPrimaryDomain($args) {
						return 'https://' . $this->primary_domain;
					}
				},
			];

			$formats = $this->newWithoutConstructor(['class'=>'module_entryformats']);
			$formats->that = $that;

			return $formats;
		}

			// An entity in a stored title is decoded before it is encoded, so the header shows the apostrophe

		public function testGetDocument_PDFAnchor() {
			$anchor = $this->newFormats(['format'=>'pdf'])->getDocument_PDFAnchor(['title'=>'Ain&#x27;t No Gods: Part 1']);

			$this->assertSame('<a name="ggcms-chapter:Ain%27t%20No%20Gods%3A%20Part%201"></a>' . "\n", $anchor);
			$this->assertStringNotContainsString(' ', trim(substr($anchor, 3)), 'one attribute, no spaces to split on');
		}

		public function testGetDocumentURL() {
			$this->assertSame('https://RevoltLib.com/anarchism/mutual-aid/', $this->newFormats(['format'=>'pdf'])->getDocumentURL());
		}

		public function testGetDocument_PDFChapterMarker() {
			$formats = $this->newFormats(['format'=>'pdf']);
			$chapter = ['Title'=>'Among Animals', 'Subtitle'=>'', 'textbody'=>[['WordCount'=>4200]]];
			$quote = ['Title'=>'A quote', 'Subtitle'=>'', 'textbody'=>[['WordCount'=>40]]];

			$this->assertStringContainsString('ggcms-chapter:', $formats->getDocument_PDFChapterMarker(['child'=>$chapter]));
			$this->assertSame('', $formats->getDocument_PDFChapterMarker(['child'=>$quote]), 'a short child runs on');
			$this->assertSame('', $this->newFormats(['format'=>'epub'])->getDocument_PDFChapterMarker(['child'=>$chapter]), 'PDF only');
		}

		public function testGetDocument_PDFCover() {
			$this->assertSame('', $this->newFormats(['format'=>'rtf'])->getDocument_PDFCover());

			$cover = $this->newFormats(['format'=>'pdf'])->getDocument_PDFCover();

			$this->assertStringContainsString('<h2>RevoltLib.com</h2>', $cover);
			$this->assertStringContainsString('<a href="https://RevoltLib.com/anarchism/mutual-aid/">RevoltLib.com/anarchism/mutual-aid</a>', $cover);
			$this->assertStringEndsWith('<a name="ggcms-chapter:Mutual%20Aid"></a>' . "\n", $cover);
		}
	}

?>

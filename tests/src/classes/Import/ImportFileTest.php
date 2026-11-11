<?php

	ggreq('classes/Import/ImportFile.php');

		// The importer's reader: every format into one header and its rows, and a zip's images kept by name
	class ImportFileTest extends GGCMSTestCase {
		public function read($content, $name = '') {
			$file = new ImportFile();
			$file->Read(['content'=>$content, 'name'=>$name]);

			return $file;
		}

		public function zip($files) {
			$path = tempnam(sys_get_temp_dir(), 'ggcms-test-zip-');
			$zip = new ZipArchive();
			$zip->open($path, ZipArchive::OVERWRITE);

			foreach($files as $name => $content) {
				$zip->addFromString($name, $content);
			}

			$zip->close();
			$content = file_get_contents($path);
			unlink($path);

			return $content;
		}

		public function testTabDelimited() {
			$file = $this->read("Entry_Title\tTag_Tag\n Poetry \tverse\n\n\nPoetry\trhyme\n", 'records.tsv');

			$this->assertSame([], $file->errors);
			$this->assertSame('tsv', $file->format);
			$this->assertSame(['Entry_Title', 'Tag_Tag'], $file->headers);
			$this->assertSame([['Poetry', 'verse'], ['Poetry', 'rhyme']], $file->rows);
			$this->assertSame([2, 5], $file->lines, 'blank rows are dropped, and the lines kept true');
		}

		public function testCommaSeparatedWithAQuotedLineBreak() {
			$file = $this->read("\xEF\xBB\xBFEntry_Title,TextBody_Text\n\"Arc Lamps\",\"<p>One,</p>\n<p>two</p>\"\nGas,plain\n", 'records.csv');

			$this->assertSame(['Entry_Title', 'TextBody_Text'], $file->headers, 'the byte order mark is not part of the first header');
			$this->assertSame(['Arc Lamps', "<p>One,</p>\n<p>two</p>"], $file->rows[0]);
			$this->assertSame([2, 4], $file->lines);
		}

		public function testShortAndLongRowsAreTheHeadersShape() {
			$file = $this->read("a\tb\tc\n1\n1\t2\t3\t4\n");

			$this->assertSame([['1', '', ''], ['1', '2', '3']], $file->rows);
		}

		public function testFormatIsSniffedWithoutAName() {
			$this->assertSame('tsv', $this->read("Entry_Title\tTag_Tag\nx\ty\n")->format);
			$this->assertSame('csv', $this->read("Entry_Title,Tag_Tag\nx,y\n")->format);
			$this->assertSame('json', $this->read('[{"Entry_Title":"x"}]')->format);
			$this->assertSame('jsonl', $this->read('{"Entry_Title":"x"}')->format);
		}

			// A list value is a column named more than once: two tags
		public function testJSONObjects() {
			$file = $this->read('[{"Entry_Title": "Poetry", "Tag_Tag": ["verse", "rhyme"]}, {"Entry_Title": "Prose", "Entry_Publish": true}]', 'records.json');

			$this->assertSame(['Entry_Title', 'Tag_Tag', 'Tag_Tag', 'Entry_Publish'], $file->headers);
			$this->assertSame([['Poetry', 'verse', 'rhyme', ''], ['Prose', '', '', '1']], $file->rows);
		}

		public function testJSONListsWithTheHeaderFirst() {
			$file = $this->read('[["Entry_id", "Delete"], [12, "Y"]]', 'records.json');

			$this->assertSame(['Entry_id', 'Delete'], $file->headers);
			$this->assertSame([['12', 'Y']], $file->rows);
		}

		public function testJSONLines() {
			$file = $this->read("{\"Entry_Title\": \"Один\"}\n\n{\"Entry_Title\": \"二\"}\n", 'records.jsonl');

			$this->assertSame([['Один'], ['二']], $file->rows);
			$this->assertSame([1, 3], $file->lines);
		}

		public function testBrokenJSONIsAnError() {
			$file = $this->read('[{"Entry_Title": ', 'records.json');

			$this->assertCount(1, $file->errors);
			$this->assertSame([], $file->rows);
		}

			// Shared strings, an inline string, a number, and a cell left out
		public function testXLSX() {
			$sheet = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
				. '<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="inlineStr"><is><t>Entry_Publish</t></is></c></row>'
				. '<row r="2"><c r="A2" t="s"><v>2</v></c><c r="C2"><v>1</v></c></row>'
				. '</sheetData></worksheet>';
			$strings = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Entry_Title</t></si><si><t>Tag_Tag</t></si><si><r><t>Arc </t></r><r><t>Lamps</t></r></si></sst>';

			$file = $this->read($this->zip(['xl/workbook.xml'=>'<workbook/>', 'xl/sharedStrings.xml'=>$strings, 'xl/worksheets/sheet1.xml'=>$sheet]), 'records.xlsx');

			$this->assertSame([], $file->errors);
			$this->assertSame(['Entry_Title', 'Tag_Tag', 'Entry_Publish'], $file->headers);
			$this->assertSame([['Arc Lamps', '', '1']], $file->rows);
		}

		public function testXLSXColumnIndex() {
			$file = new ImportFile();

			$this->assertSame(0, $file->XLSXColumnIndex(['reference'=>'A1']));
			$this->assertSame(25, $file->XLSXColumnIndex(['reference'=>'Z9']));
			$this->assertSame(27, $file->XLSXColumnIndex(['reference'=>'AB12']));
		}

			// One data file, the rest images, found by name with the folders and the case ignored
		public function testZipOfRecordsAndImages() {
			$file = $this->read($this->zip([
				'records.tsv'=>"Image_Title\tImage_FileName\nLamp\tLamp.JPG\n",
				'pictures/lamp.jpg'=>'not really a jpeg',
				'__MACOSX/pictures/._lamp.jpg'=>'resource fork',
			]), 'bundle.zip');

			$this->assertSame([], $file->errors);
			$this->assertSame([['Lamp', 'Lamp.JPG']], $file->rows);
			$this->assertSame(1, count($file->images));

			$image = $file->Image(['filename'=>'LAMP.jpg']);
			$this->assertSame('lamp.jpg', $image['name']);
			$this->assertSame('not really a jpeg', file_get_contents($image['path']));
			$this->assertStringNotContainsString('..', $image['path']);

			$this->assertNull($file->Image(['filename'=>'gas.jpg']));

			$file->RemoveImages();
			$this->assertFileDoesNotExist($image['path']);
		}

		public function testZipWithTwoDataFilesOrTwoLikeNamedImagesIsRefused() {
			$this->assertCount(1, $this->read($this->zip(['a.tsv'=>"x\n1\n", 'b.csv'=>"x\n1\n"]), 'bundle.zip')->errors);

			$twins = $this->read($this->zip(['a.tsv'=>"x\n1\n", 'one/fig.png'=>'1', 'two/FIG.png'=>'2']), 'bundle.zip');
			$this->assertCount(1, $twins->errors);
			$twins->RemoveImages();
		}

		public function testAHeaderOfNothingIsAnError() {
			$this->assertCount(1, $this->read("\t\t\n1\t2\n")->errors);
			$this->assertCount(1, $this->read('')->errors);
		}
	}

?>

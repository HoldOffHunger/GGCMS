<?php

	class HandleInputTest extends GGCMSTestCase {
		public function newHandleInput() {
			return new HandleInput(['handler'=>NULL]);
		}

		public function testFormatTitleOuput() {
			$handle_input = $this->newHandleInput();

			$this->assertSame('Short title', $handle_input->FormatTitleOuput(['text'=>'Short title']));
			$this->assertSame(str_repeat('a', 50), $handle_input->FormatTitleOuput(['text'=>str_repeat('a', 50)]), 'exactly fifty is left alone');
			$this->assertSame(str_repeat('a', 45) . '...', $handle_input->FormatTitleOuput(['text'=>str_repeat('a', 45) . '     ' . str_repeat('b', 10)]), 'cut at fifty, trailing space trimmed, then ellipsis');
		}

		public function testFormatListOutput() {
			$handle_input = $this->newHandleInput();

			$text = "<p>First line.</p>\n<p>See Image::42 and [12] ----- more</p>";

			$this->assertSame('First line. See and more...', $handle_input->FormatListOutput(['text'=>$text]));
			$this->assertSame('Ends properly.', $handle_input->FormatListOutput(['text'=>'Ends properly.']));
			$this->assertSame('', $handle_input->FormatListOutput(['text'=>'']), 'nothing in, nothing out');
		}

		public function testValidToFormatListOutput() {
			$this->assertTrue($this->newHandleInput()->ValidToFormatListOutput(['text'=>'x']));
			$this->assertFalse($this->newHandleInput()->ValidToFormatListOutput(['text'=>'']));
		}

		public function testStripBCMLCode() {
			$this->assertSame('a   b   c', $this->newHandleInput()->StripBCMLCode(['text'=>'a FullImage::12 b Image::7 c']));
		}

		public function testStripCitationNumbers() {
			$this->assertSame('claim. another. (not a citation)', $this->newHandleInput()->StripCitationNumbers(['text'=>'claim.[1] another.( 2 3) (not a citation)']));
		}

		public function testStripCommonDashes() {
			$this->assertSame('a - b -- c --- d   e   f', $this->newHandleInput()->StripCommonDashes(['text'=>'a - b -- c --- d ---- e __ f']));
		}

		public function testSwapHTMLWithSpaces() {
			$this->assertSame('a b c d e f', $this->newHandleInput()->SwapHTMLWithSpaces(['text'=>"a<br>b<br />c<p>d\te&nbsp;f"]));
		}

		public function testGetReplaceableHTMLSpacing() {
			$spacing = $this->newHandleInput()->GetReplaceableHTMLSpacing();

			$this->assertCount(12, $spacing);
			$this->assertContains('<hr />', $spacing);
		}

		public function testSwapMultipleSpacesWithSingleSpaces() {
			$this->assertSame(' a b c ', $this->newHandleInput()->SwapMultipleSpacesWithSingleSpaces(['text'=>"  a \n\t b   c  "]));
		}

		public function testTrimText() {
			$this->assertSame('a b', $this->newHandleInput()->TrimText(['text'=>"  a b\n"]));
		}

		public function testAppendTruncatingPeriods() {
			$handle_input = $this->newHandleInput();

			foreach(['Done.', 'Done!', 'Done?'] as $text) {
				$this->assertSame($text, $handle_input->AppendTruncatingPeriods(['text'=>$text]));
			}

			$this->assertSame('Cut off...', $handle_input->AppendTruncatingPeriods(['text'=>'Cut off']));
		}

		public function testCleanseInput_Integer() {
			$this->assertSame(['cleansedinput'=>42], $this->newHandleInput()->CleanseInput_Integer(['input'=>'42abc']));
			$this->assertSame(['cleansedinput'=>0], $this->newHandleInput()->CleanseInput_Integer(['input'=>'abc']));
		}

		public function testCleanseInput_Filename() {
			$this->assertSame(['cleansedinput'=>'....etcpasswd'], $this->newHandleInput()->CleanseInput_Filename(['input'=>'../../etc/passwd']));
		}
	}

?>

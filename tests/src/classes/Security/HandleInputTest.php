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

				/*
					Until September 2026 every byte above 7F was deleted here, so
					this first excerpt read "dj vu." and Japanese read "...".
				*/

			$this->assertSame('Россия — déjà vu.', $handle_input->FormatListOutput(['text'=>'<p>Россия — déjà vu.</p>']));
			$this->assertSame('日本語のテキスト。', $handle_input->FormatListOutput(['text'=>'日本語のテキスト。']));
			$this->assertSame('Ćwiczenie ćma жизнь.', $handle_input->FormatListOutput(['text'=>'Ćwiczenie ćma жизнь.']));
		}

		public function testStripCitationMarks() {
			$this->assertSame('footnote and  and  and  gone. Ćwiczenie жизнь', $this->newHandleInput()->StripCitationMarks(['text'=>'footnote† and ‡ and ¶ and * gone. Ćwiczenie жизнь']));
		}

		public function testStripUncommonDashes() {
			$handle_input = $this->newHandleInput();

			$this->assertSame('a b c', $handle_input->StripUncommonDashes(['text'=>'a————b––––c']));
			$this->assertSame('a — b ——— c', $handle_input->StripUncommonDashes(['text'=>'a — b ——— c']), 'fewer than four are left alone');
		}

		public function testHandlePossibleUTF8Corruption() {
			$handle_input = $this->newHandleInput();

			$this->assertSame('Россия — déjà 日本', $handle_input->HandlePossibleUTF8Corruption(['text'=>'Россия — déjà 日本']));
			$this->assertSame('bad  bytes  end', $handle_input->HandlePossibleUTF8Corruption(['text'=>"bad \xFF\xFE bytes \xC3 end"]), 'invalid sequences go');
			$this->assertSame('ab', $handle_input->HandlePossibleUTF8Corruption(['text'=>"a\x07\u{0085}b"]), 'control characters, C1 included, go');
			$this->assertSame('surrogate', $handle_input->HandlePossibleUTF8Corruption(['text'=>"surrogate\xED\xA0\x80"]), 'an encoded surrogate is not UTF-8');
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
			$this->assertSame('終わり。', $handle_input->AppendTruncatingPeriods(['text'=>'終わり。']), 'a full-width full stop ends a sentence');
			$this->assertSame('déjà...', $handle_input->AppendTruncatingPeriods(['text'=>'déjà']));
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

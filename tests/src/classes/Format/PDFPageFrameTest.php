<?php

	/*
		PDFPageFrame draws the running header and footer on every page of a
		generated PDF, and starts each chapter on a page of its own.  The
		documents here are made in memory; nothing is written to disk.
	*/

	class PDFPageFrameTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			require_once(GGCMS_DEP_DIR . 'php-html-to-pdf/php-html-to-pdf.php');
			$this->requireEngine(['file'=>'classes/Format/PDFPageFrame.php']);
		}

		public function newFrame() {
			return new PDFPageFrame([
				'Title'=>'Mutual Aid',
				'documenturl'=>'https://RevoltLib.com/anarchism/mutual-aid/',
			]);
		}

		public function chapterAnchor($args) {
			return '<a name="ggcms-chapter:' . rawurlencode($args['title']) . '"></a>';
		}

			/*
				A chapter starts a page and names the pages after it; two
				anchors with nothing between them share one page rather than
				leaving one blank.
			*/

		public function testOpenTag_Chapter() {
			$pdf = $this->newFrame();

			$pdf->WriteHTML([
				'html'=>'<p>Cover</p>' . $this->chapterAnchor(['title'=>'Front matter']) . $this->chapterAnchor(['title'=>'Chapter 1: Among Animals']) . '<p>Text.</p>',
				'language'=>'en',
			]);

			$this->assertSame(2, $pdf->PageNo(), 'the cover, then one page for both anchors');
			$this->assertSame('Chapter 1: Among Animals', $pdf->running_title, 'decoded, spaces and colon intact');
		}

		public function testHeader_RunningTitle() {
			$pdf = $this->newFrame();
			$pdf->WriteHTML(['html'=>'<p>Cover</p>', 'language'=>'en']);
			$pdf->SetFont($pdf->base_font, '', 9);

			$pdf->running_title = 'Chapter 2';
			$this->assertSame('Chapter 2', $pdf->Header_RunningTitle());

			$pdf->running_title = str_repeat('A very long chapter title ', 20);
			$title = $pdf->Header_RunningTitle();

			$this->assertStringEndsWith('...', $title);
			$this->assertLessThanOrEqual($pdf->w - 30, $pdf->GetStringWidth($title), 'fits the header line');
		}

		public function testFooter_Address() {
			$this->assertSame('RevoltLib.com/anarchism/mutual-aid', $this->newFrame()->Footer_Address());
		}
	}

?>

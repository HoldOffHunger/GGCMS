<?php

		/*
			The frame around every page of a generated PDF: the chapter's title
			across the top, and the site's address for the document with the page
			number across the bottom.  A PDF passed around on its own -- uploaded
			to the Internet Archive, printed, emailed -- still says where it came
			from on every page, and the reader still knows which chapter they are
			in.

			HTMLtoPDF, in dep/, draws the document itself and is left as it is;
			tFPDF calls Header() and Footer() on every page and leaves them
			empty for a class like this one to fill.

			The document marks where a chapter begins with an empty anchor,
			which HTMLtoPDF already passes through and otherwise ignores:

				<a name="ggcms-chapter:Mutual%20Aid%20Among%20Animals"></a>

			The title is rawurlencode()d because a tag's attributes are split
			on spaces before they are read.  A chapter starts a new page and
			becomes the running title from that page on.  See
			module_entryformats::getDocument_PDFChapterMarker().
		*/

	class PDFPageFrame extends HTMLtoPDF {
		public $document_url;
		public $document_title;
		public $running_title;
		public $page_top;

			// Constructor
			// -----------------------------------------------

		public function __construct($args) {
			$this->document_url = (string)($args['documenturl'] ?? '');
			$this->document_title = (string)($args['Title'] ?? '');
			$this->running_title = $this->document_title;
			$this->page_top = 0;

			parent::__construct($args);

			return $this;
		}

			// Chapters
			// -----------------------------------------------

		public function ChapterMarkerPrefix() {
			return 'ggcms-chapter:';
		}

		public function ContentTop() {
			return 16;
		}

		public function OpenTag($tag, $attr) {
			$name = (string)($attr['NAME'] ?? '');
			$prefix = $this->ChapterMarkerPrefix();

			if($tag === 'A' && strpos($name, $prefix) === 0) {
				return $this->OpenTag_Chapter([
					'title'=>rawurldecode(substr($name, strlen($prefix))),
				]);
			}

			return parent::OpenTag($tag, $attr);
		}

		public function OpenTag_Chapter($args) {
			$this->running_title = $args['title'];

				// Two chapters with nothing between them share one page, rather than leaving one blank

			if($this->GetY() > $this->page_top) {
				$this->AddPage();
			}

			return TRUE;
		}

			// Header
			// -----------------------------------------------

		public function Header() {
				// The cover carries the title itself

			if($this->PageNo() < 2) {
				$this->page_top = $this->GetY();

				return TRUE;
			}

			$this->SetFont($this->base_font, '', 9);
			$this->SetTextColor(110);
			$this->SetXY(10, 6);
			$this->Cell($this->w - 20, 5, $this->Header_RunningTitle(), 0, 0, 'C');

			$this->SetDrawColor(180);
			$this->Line(10, 12, $this->w - 10, 12);

			$this->SetY($this->ContentTop());
			$this->page_top = $this->GetY();

			return TRUE;
		}

		public function Header_RunningTitle() {
			$title = $this->running_title;
			$width = $this->w - 30;

			if($this->GetStringWidth($title) <= $width) {
				return $title;
			}

			while(mb_strlen($title) > 1 && $this->GetStringWidth($title . '...') > $width) {
				$title = mb_substr($title, 0, -1);
			}

			return rtrim($title) . '...';
		}

			// Footer
			// -----------------------------------------------

		public function Footer() {
			$this->SetFont($this->base_font, '', 9);
			$this->SetTextColor(110);

			$this->SetXY(10, -14);
			$this->Cell($this->w - 20, 5, $this->Footer_Address(), 0, 0, 'L', FALSE, $this->document_url);

			if($this->PageNo() > 1) {
				$this->SetXY(10, -14);
				$this->Cell($this->w - 20, 5, (string)$this->PageNo(), 0, 0, 'R');
			}

			return TRUE;
		}

			// The address as a reader would type it: no scheme, no trailing slash

		public function Footer_Address() {
			$address = preg_replace('#^https?://#i', '', $this->document_url);

			return rtrim($address, '/');
		}
	}

?>

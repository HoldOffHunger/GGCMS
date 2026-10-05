<?php

		/*
			An entry in every format but the reading page: the printer-friendly
			and inverted-colour pages, plain text, and the HTML that PDF, EPUB,
			RTF, DAISY, SGML, LaTeX and Braille are each converted from.  JSON,
			CSV, RDF and OPDS the engine writes from the record by itself, and
			XML needs only the record handed over.

			Each older template carried its own copy of this, several hundred
			lines long -- display_quotes.php, display_grandchildof_essays.php --
			and the templates written for the reading page carried none, so
			every format but the web page came back empty, or as the web page.
			A template's formats are now

				if($this->script_format_lower == 'html' && !$this->Param('printerfriendly') && !$this->Param('invertedcolors')) {
					ggreq('modules/html/reading-page.php');
					$reading_page = new module_readingpage(['that'=>$this]);
					$reading_page->Display(['file'=>__FILE__]);
				} else {
					ggreq('modules/html/entry-formats.php');
					$formats = new module_entryformats(['that'=>$this]);
					$formats->Display();
				}

			A document carries the entry's children after the entry itself --
			a theme's PDF is its quotes, a section's is its links -- so a site
			of small entries keeps its documents to the entries that list
			something, and an entry with nothing under it sends a request for
			one on to its parent's.  See OffersAlternateFormats() in view.php.

			Switches:
			  body    a text the template makes itself rather than reads from
			          the record -- AnarchistCode's code of conduct -- set where
			          the record's text goes, as the reading page takes it
		*/

	class module_entryformats extends module_spacing {
		public $that;
		public $body;
		public $entrysort;
		public $entrydate;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->body = $args['body'] ?? NULL;

			require_once(GGCMS_DIR . 'modules/html/entry-sort.php');
			require_once(GGCMS_DIR . 'modules/html/entry-date.php');

			$this->entrysort = new module_entrysort(['that'=>$this->that]);
			$this->entrydate = new module_entrydate(['that'=>$this->that]);
		}

		public function Display() {
			$that = $this->that;
			$format = $that->script_format_lower;

			if($format === 'html') {
				print('<div class="font-family-arial">');
				print($this->getDocument_HTML());
				print('</div>');

				return TRUE;
			}

			if($format === 'xml') {
				$that->source_content = $that->SetRecordToUseForMetadata();

				return TRUE;
			}

			if(!in_array($format, $this->getDocumentFormats())) {
				return TRUE;
			}

			if(method_exists($that, 'OffersAlternateFormats') && !$that->OffersAlternateFormats()) {
				return $this->RedirectToParent();
			}

			if($format === 'txt') {
				$text = $this->getDocument_TXT();

				if($that->Param('wrapped')) {
					$text = wordwrap($text, 75, "\n", FALSE);
				}

				print($text);

				return TRUE;
			}

			$that->source_content = $this->getDocument_HTML();

			return TRUE;
		}

			/*
				The formats made from the document rather than from the record.
				Six of them are converted once and kept on disk, under
				src/data/<format>/<site>/.
			*/

		public function getDocumentFormats() {
			return ['pdf', 'epub', 'rtf', 'daisy', 'sgml', 'tex', 'brf', 'txt'];
		}

			/*
				To the same format of the entry above, whose document holds this
				one: /oppression/1/view.pdf goes to /oppression/view.pdf, keeping
				?wrapped=1 and ?mode=dotted.  The engine's own script redirect,
				as browseByTag() uses -- PDF and the converted formats answer an
				empty document by ending the request with it, but text and
				Braille print whatever they are given, so it is sent from here.
			*/

		public function RedirectToParent() {
			$that = $this->that;

			$codes = array_slice($that->object_list, 0, -1);
			$base = '/' . ($codes ? implode('/', $codes) . '/' : '');

			$query = [];

			foreach(['wrapped', 'mode'] as $parameter) {
				if($that->Param($parameter)) {
					$query[] = $parameter . '=' . urlencode($that->Param($parameter));
				}
			}

			$that->redirect_script = 'view';
			$that->redirect_base = $base . 'view.' . $that->handler->script_extension;
			$that->redirect_query = implode('&', $query);

			return $that->handler->redirects->handleScriptRedirect();
		}

			/*
				The children with their whole text, every one of them, in the
				order the site lists them.  An index page shows only its newest
				and a few at random, but its document is the whole collection.
			*/

		public function getChildren() {
			$that = $this->that;

			if(!$that->children_count && !$that->counts['children']) {
				return [];
			}

			$children = $that->orm->GetRecordChildren([
				'entry'=>$that->entry,
				'startindex'=>0,
				'endindex'=>0,
				'orderby'=>'ListTitleSortKey,ListTitle,Title',
				'where'=>[],
				'publish'=>TRUE,
				'noassignment'=>FALSE,
				'extraselect'=>'',
				'alltext'=>TRUE,
			]);

			if(!$children) {
				return [];
			}

			return $this->entrysort->Sort(['entries'=>$children]);
		}

		public function getGeneratedFrom() {
			return $this->that->domain_object->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>0, 'www'=>1]) . '/';
		}

				// The document, as HTML
				// -------------------------------------------------------------

		public function getDocument_HTML() {
			$that = $this->that;
			$entry = $that->entry;
			$counts = $that->counts;

			$document = '<h1>' . $entry['Title'] . '</h1>' . "\n";

			if($entry['Subtitle']) {
				$document .= '<h2>' . $entry['Subtitle'] . '</h2>' . "\n";
			}

			$document .= $this->entrydate->getSimpleDisplay_HTML();

			if($entry['association'] && $counts['association']) {
				$document .= '<p><b>People :</b></p>' . "\n";

				foreach($entry['association'] as $association) {
					$document .= '<p>' . $association['SubType'] . ' : ' . $association['entry']['Title'] . '</p>';
				}

				$document .= "\n";
			}

			if($entry['description'] && $counts['description']) {
				$description = $entry['description'][0];

				$document .= '<p><b>Description :</b> ' . $description['Description'];

				if($description['Source']) {
					$document .= ' (From : ' . $description['Source'] . ')';
				}

				$document .= '</p>' . "\n";
			}

			if($entry['tag'] && $counts['tag']) {
				$tags = array_column(array_slice($entry['tag'], 0, 10), 'Tag');

				$document .= '<p><b>Tags :</b> ' . implode(', ', $tags) . '.</p>' . "\n";
			}

			if($entry['quote'] && $counts['quote']) {
				$document .= '<p><b>Quotes :</b></p>' . "\n";

				foreach($entry['quote'] as $quote) {
					$document .= '<blockquote><i>"' . str_replace('"', '\'', $quote['Quote']) . '"</i>';

					if($quote['Source']) {
						$document .= ' (From : ' . $quote['Source'] . '.)';
					}

					$document .= '</blockquote>' . "\n";
				}
			}

			if($entry['textbody'] && $counts['textbody']) {
				$document .= '<p><b>Text :</b></p>' . "\n";

				foreach($entry['textbody'] as $textbody) {
					$document .= preg_replace("/<img[^>]+\>/i", " ", $textbody['Text']) . "\n";

					if($textbody['Source']) {
						$document .= '<p>From : ' . $textbody['Source'] . '.</p>' . "\n";
					}
				}
			}

			if($this->body !== NULL) {
				$document .= $this->body . "\n";
			}

			$children = $this->getChildren();

			if($children) {
				$document .= '<p><b>Sections (TOC) :</b></p>' . "\n";

				foreach($children as $child) {
					$document .= '    <p>&bull; ' . $this->getChildTitle(['child'=>$child]);

					if(!empty($child['textbody'])) {
						$document .= '<br>' . "\n" . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						$document .= number_format($child['textbody'][0]['WordCount']) . ' Words; ';
						$document .= number_format($child['textbody'][0]['CharacterCount']) . ' Characters';
					}

					$document .= '</p>';
				}

				$document .= '<p><b>Sections (Content) :</b></p>' . "\n";

				foreach($children as $child) {
					$document .= '<p>&bull; ' . $this->getChildTitle(['child'=>$child]) . '</p>';

					if(!empty($child['description'])) {
						$document .= '<p>' . $child['description'][0]['Description'] . '</p>';
					}

					foreach(($child['link'] ?? []) as $link) {
						$document .= '<p><a href="' . $link['URL'] . '">' . $link['URL'] . '</a></p>';
					}

					if(!empty($child['textbody'])) {
						$document .= $that->formatImageText([
							'text'=>$child['textbody'][0]['Text'],
							'images'=>$child['image'] ?? [],
						]);
					}
				}
			}

			if($entry['eventdate'] && $counts['eventdate']) {
				$document .= '<p><b>Chronology :</b></p>' . "\n";

				foreach($entry['eventdate'] as $event_date) {
					$document .= '<blockquote><b>' . date("F d, Y", strtotime($event_date['EventDateTime'])) . ' :</b> ';
					$document .= $entry['Title'] . ' -- ' . $event_date['Title'] . '.';
					$document .= '</blockquote>' . "\n";
				}
			}

			if($entry['link'] && $counts['link']) {
				$document .= '<p><b>Links :</b></p>' . "\n";

				foreach($entry['link'] as $link) {
					$document .= '<blockquote> &bull; <b>' . $link['Title'] . '</b><br>';
					$document .= '<a href="' . $link['URL'] . '">' . $link['URL'] . '</a>';
					$document .= '</blockquote>' . "\n";
				}
			}

			$document .= '<p>' . strtoupper($that->script_format_lower) . ' file generated from : </p>' . "\n";
			$document .= '<blockquote><b>' . $this->getGeneratedFrom() . '</b></blockquote>';

			return $document;
		}

				// The document, as plain text
				// -------------------------------------------------------------

		public function getDocument_TXT() {
			$that = $this->that;
			$entry = $that->entry;
			$counts = $that->counts;

			$indent = '     ';
			$underline = '----------------------------------';

			$document = $entry['Title'];

			if($entry['Subtitle']) {
				$document .= ' : ' . "\n" . $entry['Subtitle'];
			}

			$document .= "\n" . $underline . $underline . "\n\n";

			$document .= $this->entrydate->getSimpleDisplay_TXT();

			if($entry['association'] && $counts['association']) {
				$document .= $this->getHeading_TXT(['title'=>'People']);

				foreach($entry['association'] as $association) {
					$document .= $association['SubType'] . ' : ' . $association['entry']['Title'] . "\n\n";
				}
			}

			if($entry['description'] && $counts['description']) {
				$description = $entry['description'][0];

				$document .= $this->getHeading_TXT(['title'=>'Description']);
				$document .= $description['Description'] . "\n\n";

				if($description['Source']) {
					$document .= $indent . 'From : ' . $description['Source'] . "\n\n";
				}
			}

			if($entry['tag'] && $counts['tag']) {
				$tags = array_column(array_slice($entry['tag'], 0, 10), 'Tag');

				$document .= $this->getHeading_TXT(['title'=>'Tags']);
				$document .= implode(', ', $tags) . '.' . "\n\n";
			}

			if($entry['quote'] && $counts['quote']) {
				$document .= $this->getHeading_TXT(['title'=>'Quotes']);

				foreach($entry['quote'] as $quote) {
					$document .= '"' . str_replace('"', '\'', $quote['Quote']) . '"' . "\n\n";

					if($quote['Source']) {
						$document .= $indent . 'From : ' . $quote['Source'] . "\n\n";
					}
				}
			}

			if($entry['textbody'] && $counts['textbody']) {
				$textbody = $entry['textbody'][0];

				$document .= $this->getHeading_TXT(['title'=>'Text']);
				$document .= html_entity_decode(strip_tags($textbody['Text'])) . "\n\n";

				if($textbody['Source']) {
					$document .= $indent . 'From : ' . $textbody['Source'] . "\n\n";
				}
			}

			if($this->body !== NULL) {
				$document .= html_entity_decode(strip_tags($this->body)) . "\n\n";
			}

			$children = $this->getChildren();

			if($children) {
				$document .= $this->getHeading_TXT(['title'=>'Sections (TOC)']);

				foreach($children as $child) {
					$document .= '    * ' . $this->getChildTitle(['child'=>$child]);

					if(!empty($child['textbody'])) {
						$document .= "\n" . '        ';
						$document .= number_format($child['textbody'][0]['WordCount']) . ' Words; ';
						$document .= number_format($child['textbody'][0]['CharacterCount']) . ' Characters';
					}

					$document .= "\n\n";
				}

				$document .= $this->getHeading_TXT(['title'=>'Sections (Content)']);

				foreach($children as $child) {
					$document .= '* ' . $this->getChildTitle(['child'=>$child]) . "\n\n";

					if(!empty($child['description'])) {
						$document .= html_entity_decode(strip_tags($child['description'][0]['Description'])) . "\n\n";
					}

					foreach(($child['link'] ?? []) as $link) {
						$document .= $link['URL'] . "\n\n";
					}

					if(!empty($child['textbody'])) {
						$document .= html_entity_decode(strip_tags($child['textbody'][0]['Text'])) . "\n\n";
					}
				}
			}

			if($entry['eventdate'] && $counts['eventdate']) {
				$document .= 'Events :' . "\n" . $underline;

				foreach($entry['eventdate'] as $event_date) {
					$document .= "\n\n" . $indent . $entry['Title'] . ' -- ' . $event_date['Title'] . ' : ';
					$document .= date("F d, Y", strtotime($event_date['EventDateTime']));
				}

				$document .= "\n\n";
			}

			if($entry['link'] && $counts['link']) {
				$document .= 'Links :' . "\n" . $underline;

				foreach($entry['link'] as $link) {
					$document .= "\n\n" . $indent . $link['Title'] . ' --' . "\n" . $link['URL'];
				}

				$document .= "\n\n";
			}

			$document .= $this->getHeading_TXT(['title'=>'About This Textfile']);
			$document .= $indent . 'Text file generated from : ' . "\n" . $this->getGeneratedFrom();

			return $document;
		}

		public function getHeading_TXT($args) {
			return $args['title'] . ' :' . "\n" . '----------------------------------' . "\n\n";
		}

		public function getChildTitle($args) {
			$child = $args['child'];

			if($child['Subtitle']) {
				return $child['Title'] . ' : ' . $child['Subtitle'];
			}

			return $child['Title'];
		}
	}

?>

<?php

	require_once(GGCMS_DIR . 'modules/html/entry-child.php');

		/*
			A front page's sections: first a bar of them all, each a small
			picture over its name, then each in turn as a card -- picture,
			title and subtitle, description and a quote -- with, for the
			sections that list their entries, those entries under it, as
			module_entrychild cards.

			Switches:
			  order       the sections' codes ranked, lowest first; a code
			              not ranked keeps its place among the others
			  indexcodes  the codes of the sections whose links open their
			              index -- view.php?action=index -- and whose
			              entries are listed under their card
		*/

	class module_indexcollections extends module_spacing {
		public $that;
		public $order;
		public $index_codes;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->order = $args['order'] ?? [];
			$this->index_codes = $args['indexcodes'] ?? [];
		}

		public function Display() {
			$children = $this->SortedChildren();

			$this->DisplaySectionBar(['children'=>$children]);

			foreach($children as $child) {
				$this->DisplaySectionCard(['child'=>$child]);
				$this->DisplayEntries(['child'=>$child]);
			}

			return TRUE;
		}

		public function SortedChildren() {
			$children = (array) $this->that->children;
			$order = $this->order;

			usort($children, function($a, $b) use ($order) {
				return ($order[$a['Code']] ?? NULL) <=> ($order[$b['Code']] ?? NULL);
			});

			return $children;
		}

		public function IsIndex($args) {
			return in_array($args['child']['Code'], $this->index_codes, TRUE);
		}

		public function ChildURL($args) {
				// The template once sent some sections to their quotes:
				//	if(array_key_exists($child['Code'], $title_hash)) {
				//		$sub_dir = '/quotes';
				//	}

			return $args['child']['Code'] . '/view.php' . ($this->IsIndex($args) ? '?action=index' : '');
		}

		public function IconURL($args) {
			$image = $args['child']['image'][0];

			return $this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $image['IconFileName'];
		}

			// The bar of sections
			// -------------------------------------------------------------

		public function DisplaySectionBar($args) {
			if(count($args['children']) === 0) {
				return FALSE;
			}

			print('<center>');
			print('<div class="horizontal-center width-80percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left width-100percent">');
			print('<center>');

			$this->DisplaySectionBarHeading();

			print('<div class="horizontal-center width-95percent font-family-arial">');

			foreach($args['children'] as $child) {
				$this->DisplaySectionBarItem(['child'=>$child]);
			}

			print('</div>');
			print('</center>');
			print('</div>');
			print('</div>');
			print('</center>');

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function DisplaySectionBarHeading() {
			print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
			print('<strong>');
			print('<div class="border-2px" style="display:inline;margin:20px;">');
			print('<div style="display:inline;margin:20px;">');
			print('Sections :');
			print('</div>');
			print('</div>');
			print('</strong>');
			print('</h2>');

			return TRUE;
		}

		public function DisplaySectionBarItem($args) {
			$child = $args['child'];

			print('<div class="border-2px background-color-gray13 margin-5px float-left">');
			print('<div class="margin-5px">');

			print('<a href="' . $this->ChildURL($args) . '">');
			print('<img height="50" width="50" class="border-2px margin-5px" src="' . $this->IconURL($args) . '"><br>');
			print('<h3 style="margin:5px;">');
			print(str_replace(' ', '<br>', $child['Title']));
			print('</h3>');
			print('</a>');

			print('<div class="clear-float"></div>');

			print('</div>');
			print('</div>');

			return TRUE;
		}

			// Each section's card
			// -------------------------------------------------------------

		public function DisplaySectionCard($args) {
			$child = $args['child'];
			$url = $this->ChildURL($args);

			print('<center>');
			print('<div class="horizontal-center width-95percent">');
			print('<div class="border-2px vertical-center background-color-gray15 margin-5px float-left width-100percent">');

			$this->DisplaySectionCardImage($args);

			print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
			print('<strong>');
			print('<a href="' . $url . '">' . $child['Title'] . ' : ' . $child['Subtitle'] . '</a>');
			print('</strong>');
			print('</h2>');

			$this->DisplaySectionCardText($args);

			print('</div>');
			print('</div>');
			print('</center>');

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function DisplaySectionCardImage($args) {
			print('<div class="border-2px vertical-center background-color-gray15 margin-5px float-left">');
			print('<div class="border-2px vertical-center background-color-gray15 margin-5px float-left">');
			print('<div class="background-color-gray0 vertical-center" style="height:200px;width:200px;top: 50%;display: table-cell;">');
			print('<a href="' . $this->ChildURL($args) . '">');
			print('<img src="' . $this->IconURL($args) . '">');
			print('</a>');
			print('</div>');
			print('</div>');
			print('</div>');

			return TRUE;
		}

		public function DisplaySectionCardText($args) {
			$child = $args['child'];

			if($child['description'][0]['Description']) {
				print('<p class="horizontal-left font-family-arial margin-5px">');
				print('<b>' . $child['description'][0]['Description'] . '</b><br>');
				print('</p>');
			}

			if($child['quote'][0]) {
				$this->DisplaySectionQuote($args);
			}

			return TRUE;
		}

		public function DisplaySectionQuote($args) {
			$quotes = $args['child']['quote'];
			$random_quote = $quotes[array_rand($quotes, 1)];

			print('<center>');
			print('<div class="horizontal-center ">');
			print('<blockquote class="horizontal-left font-family-arial"><em>"');
			print(str_replace('"', '\'', $random_quote['Quote']));
			print('"');

			if($random_quote['Source']) {
				print(' -- ');
				print($random_quote['Source']);
			}

			print('</em></blockquote>');
			print('</div>');
			print('</center>');

			return TRUE;
		}

			// The entries under a section's card
			// -------------------------------------------------------------

		public function DisplayEntries($args) {
			$child = $args['child'];
			$grandchildren = $child['children'];

			if(!$grandchildren || !$this->IsIndex($args) || !count($grandchildren)) {
				return FALSE;
			}

			print('<div class="entry-list">');

			foreach($grandchildren as $grandchild) {
				$entry_child = new module_entrychild(['that'=>$this->that, 'child'=>$grandchild] + $this->EntrySwitches($args));
				$entry_child->Display();
			}

			print('</div>');

			return TRUE;
		}

			/*
				An entry's card as the template's own loop drew it: the
				author after the title, the years of a person's life and
				the subtitle under it, three quotes or the start of the text
				-- or of the first chapter's -- and up to ten counted tags.
			*/

		public function EntrySwitches($args) {
			return [
				'linkprefix'=>$args['child']['Code'] . '/',
				'imagedirectory'=>TRUE,
				'titlestyle'=>'author',
				'detailline'=>'subtitle',
				'lifespan'=>TRUE,
				'grandchildren'=>TRUE,
				'grandchildexcerpt'=>'plain',
				'rootlinks'=>TRUE,
				'tagcounts'=>TRUE,
				'plainfloat'=>TRUE,
			];
		}
	}

?>

<?php

		/*
			A front page's sections and a taste of each.

			Display() prints the sections as collection cards -- picture,
			name, subtitle, the first few entries in it -- then
			DisplayStacks() prints the first entry of each section as a
			catalogue card with a passage from it, "from the stacks".  A
			template that wants only one calls it on its own.

			Links are relative, as they always were: this is printed on the
			page whose children these are.
		*/

	class module_entrychildrengrandchildren extends module_spacing {
		public $that;
		public $header;
		public $entrysort;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->entrysort = $args['entrysort'];
		}

		public function Display() {
			$this->DisplayCollections();
			$this->DisplayStacks();

			return TRUE;
		}

		public function DisplayCollections() {
			if(!$this->that->children || !count($this->that->children)) {
				return FALSE;
			}

			print('<section class="block collections-block" aria-labelledby="collections-title">');
			print('<h2 class="block-title" id="collections-title">' . ($this->header ? $this->header : 'The collections') . '</h2>');
			print('<div class="collections">');

			foreach($this->that->children as $child) {
				$this->DisplayCollection(['child'=>$child]);
			}

			print('</div>');
			print('</section>');

			return TRUE;
		}

		public function DisplayCollection($args) {
			$child = $args['child'];
			$url = $child['Code'] . '/' . $this->Page(['entry'=>$this->that->entry]);
			$image = $this->Display_Image_GetRandomImage(['entry'=>$child]);

			print('<article class="coll">');

			if($image) {
				print('<a class="coll-image" href="' . $url . '" tabindex="-1" aria-hidden="true">');
				print('<img alt="" loading="lazy" src="' . $this->ImageURL(['image'=>$image]) . '">');
				print('</a>');
			}

			print('<div class="coll-body">');
			print('<p class="kicker">Collection</p>');
			print('<h3 class="coll-title"><a href="' . $url . '">' . $child['Title'] . '</a></h3>');

			if($child['Subtitle']) {
				print('<p class="coll-subtitle">' . $child['Subtitle'] . '</p>');
			}

			$grandchildren = $child['children'];

			if($grandchildren && count($grandchildren)) {
				print('<ul class="coll-list">');

				foreach(array_slice($grandchildren, 0, 3) as $grandchild) {
					print('<li><a href="' . $this->GrandChildURL(['child'=>$child, 'grandchild'=>$grandchild]) . '">' . $grandchild['Title'] . '</a>');

					$author = $this->Author(['entry'=>$grandchild]);

					if($author) {
						print(' <span class="coll-author">' . $author['Title'] . '</span>');
					}

					print('</li>');
				}

				print('</ul>');
			}

			print('<a class="coll-more" href="' . $url . '">Browse ' . $child['Title'] . ' &rarr;</a>');
			print('</div>');
			print('</article>');

			return TRUE;
		}

			/*
				One entry from each section, on a catalogue card, with a
				passage from it: a quote where it has one, else its opening.
			*/

		public function DisplayStacks() {
			$picks = [];

			foreach((array) $this->that->children as $child) {
				if($child['children'] && count($child['children'])) {
					$picks[] = ['child'=>$child, 'grandchild'=>$child['children'][0]];
				}
			}

			if(!$picks) {
				return FALSE;
			}

			print('<section class="block stacks-block" aria-labelledby="stacks-title">');
			print('<div class="block-head">');
			print('<h2 class="block-title" id="stacks-title">From the stacks</h2>');
			print('<p class="block-note">A few texts taken down from the shelves.</p>');
			print('</div>');
			print('<div class="stacks">');

			foreach($picks as $pick) {
				$this->DisplayStackCard($pick);
			}

			print('</div>');
			print('</section>');

			return TRUE;
		}

		public function DisplayStackCard($args) {
			$child = $args['child'];
			$grandchild = $args['grandchild'];
			$url = $this->GrandChildURL(['child'=>$child, 'grandchild'=>$grandchild]);
			$passage = $this->Passage(['entry'=>$grandchild]);

			print('<article class="idx stack-card">');
			print('<div class="idx-head"><span class="kicker">' . $child['Title'] . '</span>');

			if($passage['source']) {
				print('<span class="stack-source">' . $passage['source'] . '</span>');
			}

			print('</div>');
			print('<h3 class="stack-title"><a href="' . $url . '">' . $grandchild['Title'] . '</a></h3>');

			$author = $this->Author(['entry'=>$grandchild]);

			if($author) {
				print('<p class="stack-author">' . $author['Title'] . '</p>');
			}

			if($passage['text']) {
				print('<blockquote class="stack-passage">' . $passage['text'] . '</blockquote>');
			}

			print('</article>');

			return TRUE;
		}

			// Data
			// -------------------------------------------------

		public function Page($args) {
			$page = 'view.php';

			if($args['entry']['ChildAction']) {
				$page .= '?action=' . $args['entry']['ChildAction'];
			}

			return $page;
		}

		public function GrandChildURL($args) {
			return $args['child']['Code'] . '/' . $args['grandchild']['Code'] . '/' . $this->Page(['entry'=>$args['child']]);
		}

		public function Author($args) {
			$entry = $args['entry'];

			if($entry['association'] && count($entry['association']) && $entry['association'][0]['entry'] && $entry['association'][0]['entry']['id']) {
				return $entry['association'][0]['entry'];
			}

			return NULL;
		}

		public function Passage($args) {
			$entry = $args['entry'];

			if($entry['quote'] && count($entry['quote'])) {
				$quote = $entry['quote'][array_rand($entry['quote'])];

				return [
					'text'=>'&ldquo;' . str_replace('"', '\'', $quote['Quote']) . '&rdquo;',
					'source'=>$this->ShortSource(['source'=>$quote['Source']]),
				];
			}

			if($entry['textbody'] && count($entry['textbody'])) {
				return [
					'text'=>$this->that->cleanser_object->FormatListOutput(['text'=>$entry['textbody'][0]['FirstThousandCharacters']]),
					'source'=>$this->ShortSource(['source'=>$entry['textbody'][0]['Source']]),
				];
			}

			if($entry['description'] && count($entry['description'])) {
				return [
					'text'=>$entry['description'][0]['Description'],
					'source'=>'',
				];
			}

			return ['text'=>'', 'source'=>''];
		}

		public function ShortSource($args) {
			$source = trim(strip_tags((string) $args['source']));

			if(strlen($source) > 40) {
				$source = substr($source, 0, 40) . '...';
			}

			return $source;
		}

		public function Display_Image_GetRandomImage($args) {
			$images = $args['entry']['image'];

			if(!$images || !count($images)) {
				return NULL;
			}

			shuffle($images);

			return $images[0];
		}

		public function ImageURL($args) {
			$image = $args['image'];

			return '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $image['FileName'];
		}
	}

?>

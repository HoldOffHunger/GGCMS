<?php

	class module_entrynavigation extends module_spacing {
		public $that;
		
		public function __construct($args) {
			$this->that = $args['that'];
		}
		
		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

			/*
				Where to go when the text ends: the next entry as a card that
				asks to be opened, the previous one beside it, then every entry
				nearby.  This is the page's best chance of a second page read.
			*/

		public function DisplaySiblingNavigation($args) {
			if(!$this->that->counts['younger_sibling'] && !$this->that->counts['older_sibling']) {
				return TRUE;
			}

			print('<section class="block keep-reading" id="siblings">');
			print('<h2 class="block-title">Keep reading</h2>');

			$this->DisplayNextPrevious();

			print('<div class="nearby-inline">');
			print('<h3 class="block-subtitle">Nearby in ' . $this->that->parent['Title'] . '</h3>');
			$this->DisplayNearby();
			print('</div>');

			print('</section>');

			return TRUE;
		}

		public function DisplayNextPrevious() {
				/*
					Both lists run in reading order: the next entry is the first
					of the older siblings, the previous one the LAST of the
					younger.  The old navigation took the first of the younger,
					which is the farthest back, and called it the last entry.
				*/

			$next = $this->that->counts['older_sibling'] ? $this->that->older_siblings[0] : NULL;
			$previous = $this->that->counts['younger_sibling'] ? $this->that->younger_siblings[$this->that->counts['younger_sibling'] - 1] : NULL;

			if($next) {
				$this->DisplaySiblingCard(['sibling'=>$next, 'label'=>'Next in ' . $this->that->parent['Title'], 'class'=>'next-card']);
			}

			if($previous) {
				$this->DisplaySiblingCard(['sibling'=>$previous, 'label'=>'Previous', 'class'=>'previous-card']);
			}

			return TRUE;
		}

		public function DisplaySiblingCard($args) {
			$sibling = $args['sibling'];
			$url = $this->that->EntrySiblingURL(['code'=>$sibling['Code']]);
			$image = $this->SiblingImage(['sibling'=>$sibling]);

			print('<a class="' . $args['class'] . '" href="' . $url . '">');

			if($image) {
				print('<img class="sibling-card-image" alt="" loading="lazy" src="' . $image . '">');
			}

			print('<span class="sibling-card-label">' . $args['label'] . '</span>');
			print('<strong class="sibling-card-title">' . $sibling['Title'] . '</strong>');

			$author = $this->SiblingAuthor(['sibling'=>$sibling]);

			if($author) {
				print('<span class="sibling-card-author">' . $author['Title'] . '</span>');
			}

			$description = $this->SiblingDescription(['sibling'=>$sibling]);

			if($description) {
				print('<span class="sibling-card-description">' . $description . '</span>');
			}

			print('</a>');

			return TRUE;
		}

			/*
				Every sibling the page was given, with this entry marked where
				it falls: the ones before it, itself, the ones after.
			*/

		public function DisplayNearby() {
			print('<ol class="nearby">');

			for($i = 0; $i < $this->that->counts['younger_sibling']; $i++) {
				$this->DisplayNearbyItem(['sibling'=>$this->that->younger_siblings[$i]]);
			}

			print('<li><a aria-current="page" href="#top">' . $this->that->entry['ListTitle'] . '</a></li>');

			for($i = 0; $i < $this->that->counts['older_sibling']; $i++) {
				$this->DisplayNearbyItem(['sibling'=>$this->that->older_siblings[$i]]);
			}

			print('</ol>');

			return TRUE;
		}

		public function DisplayNearbyItem($args) {
			$sibling = $args['sibling'];

			print('<li>');
			print('<a href="' . $this->that->EntrySiblingURL(['code'=>$sibling['Code']]) . '">');
			print($sibling['ListTitle']);
			print('</a>');

			$author = $this->SiblingAuthor(['sibling'=>$sibling]);

			if($author) {
				print(' <span class="nearby-author">' . $author['Title'] . '</span>');
			}

			print('</li>');

			return TRUE;
		}

		public function SiblingImage($args) {
			$sibling = $args['sibling'];

			if($sibling['image'] && $sibling['image'][0]) {
				$image = $sibling['image'][0];

				return '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $image['IconFileName'];
			}

			if($sibling['link'] && $sibling['link'][1] && $sibling['link'][1]['Title'] === 'Image') {
				return $sibling['link'][1]['URL'];
			}

			return '';
		}

		public function SiblingAuthor($args) {
			$sibling = $args['sibling'];

			if($sibling['association'][0] && $sibling['association'][0]['entry']['id']) {
				return $sibling['association'][0]['entry'];
			}

			return NULL;
		}

		public function SiblingDescription($args) {
			$sibling = $args['sibling'];

			if($sibling['description'] && count($sibling['description']) && $sibling['description'][0]['Description']) {
				return $sibling['description'][0]['Description'];
			}

			return '';
		}

		public function DisplayTOC() {
			print('<nav class="toc" aria-label="On this page">');
			print('<ol>');

			if($this->that->entry['description'] && $this->that->counts['description'] !== 0) {
				print('<li><a href="#description">Description</a></li>');
			}

			if($this->that->entry['quote'] && $this->that->counts['quote'] !== 0) {
				print('<li><a href="#quote">Quotes</a></li>');
			}

			if($this->that->entry['textbody'] && $this->that->counts['textbody'] !== 0) {
				print('<li><a href="#textbody">Biography</a></li>');
			}

			if($this->that->entry['associated'] && $this->that->counts['associated'] !== 0) {
				print('<li><a href="#associated">Works</a></li>');
			}

			if($this->that->entry['image'] && $this->that->counts['image'] !== 0) {
				print('<li><a href="#image">Images</a></li>');
			}

			if(($this->that->entry['definition'] && $this->that->counts['definition'] !== 0) || $this->that->authentication_object->user_session['UserAdmin.id']) {
				print('<li><a href="#glossary">Glossary</a></li>');
			}

			if($this->that->entry['eventdate'] && $this->that->counts['eventdate'] !== 0) {
				print('<li><a href="#eventdate">Chronology</a></li>');
			}

			if($this->that->entry['link'] && $this->that->counts['link'] !== 0) {
				print('<li><a href="#link">Links</a></li>');
			}

			print('<li><a href="#comments">Comments</a></li>');

			if($this->that->entry['tag'] && $this->that->counts['tag'] !== 0) {
				print('<li><a href="#tag">Tags</a></li>');
			}

			if($this->that->counts['younger_sibling'] !== 0 || $this->that->counts['older_sibling'] !== 0) {
				print('<li><a href="#siblings">Navigation</a></li>');
			}

			print('</ol>');
			print('</nav>');

			return TRUE;
		}
	}

?>
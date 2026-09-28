<?php

	class module_entryimage extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

		public function DisplayLinkBox() {
			if($this->that->counts['image'] === 0) {
				return FALSE;
			}

			print('<a class="action" href="#image">');
			print('Images');
			print('<span class="action-count">' . number_format($this->that->counts['image']) . '</span>');
			print('</a>');

			return TRUE;
		}

		public function Display($args) {
			if($this->that->counts['image'] === 0 || !$this->that->entry['image']) {
				return FALSE;
			}

			$header = $args['header'];

			if(!$header) {
				$header = 'Image Gallery';
			}

			print('<section class="block gallery" id="image">');
			print('<h2 class="block-title">' . $header . '</h2>');

			$this->DisplayFloat();

			print('</section>');

			return TRUE;
		}

			/*
				The pictures as a grid of thumbnails, each opening the full
				image.  Named for the float it used to be, since templates call
				it on its own.
			*/

		public function DisplayFloat() {
			if(!$this->canDisplay()) {
				return FALSE;
			}

			$images = $this->that->entry['image'];

			print('<ul class="gallery-grid">');

			for($i = 0; $i < $this->that->counts['image']; $i++) {
				$image = $images[$i];
				$directory = implode('/', str_split($image['FileDirectory']));

				print('<li>');
				print('<a href="/image/' . $directory . '/' . $image['FileName'] . '" target="_blank"');

				if($image['Title']) {
					print(' title="' . htmlentities($image['Title']) . '"');
				}

				print('>');
				print('<img alt="' . htmlentities((string) $image['Title']) . '" loading="lazy" width="' . ceil($image['IconPixelWidth']) . '" height="' . ceil($image['IconPixelHeight']) . '" src="/image/' . $directory . '/' . $image['IconFileName'] . '">');
				print('</a>');
				print('</li>');
			}

			print('</ul>');

			return TRUE;
		}

		public function canDisplay() {
			if(!$this->that->entry['image'] || $this->that->counts['image'] === 0) {
				return FALSE;
			}

			if($this->that->mobile_friendly) {
				$this->NoDisplayForMobile();
				return FALSE;
			}

			return TRUE;
		}

		public function NoDisplayForMobile() {
			print('<p class="note"><a href="view.php">Images are on the full edition of this page.</a></p>');

			return TRUE;
		}
	}

?>

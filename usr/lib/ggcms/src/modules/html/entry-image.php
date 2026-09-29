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

			/*
				The pictures at the head of a page whose entries are pictures
				first -- reading-page.php's pictures_first.  Each is shown
				whole, from its full file, unless that file is a raw scan too
				large to send with the page (MasereelGroup holds three at
				eleven thousand pixels and more); those show their icon and
				link to the scan, saying how big it is.
			*/

		public function DisplayLead() {
			if(!$this->canDisplay()) {
				return FALSE;
			}

			$largest_inline_width = 2400;

			foreach($this->that->entry['image'] as $image) {
				if(!$image || empty($image['id'])) {
					continue;
				}

				$directory = '/image/' . implode('/', str_split($image['FileDirectory'])) . '/';
				$whole = ((int) $image['PixelWidth'] > 0 && (int) $image['PixelWidth'] <= $largest_inline_width);
				$alt = htmlentities((string) ($image['Title'] ?: $this->that->entry['Title']));

				print('<figure class="lead-picture' . ($whole ? '' : ' is-scan') . '">');
				print('<a href="' . $directory . $image['FileName'] . '" target="_blank">');

				if($whole) {
					print('<img alt="' . $alt . '" width="' . (int) $image['PixelWidth'] . '" height="' . (int) $image['PixelHeight'] . '" src="' . $directory . $image['FileName'] . '">');
				}
				else {
					print('<img alt="' . $alt . '" width="' . (int) $image['IconPixelWidth'] . '" height="' . (int) $image['IconPixelHeight'] . '" src="' . $directory . $image['IconFileName'] . '">');
				}

				print('</a>');

				if(!$whole || $image['Title'] || $image['Description']) {
					print('<figcaption>');

					if($image['Title']) {
						print('<span class="lead-picture-title">' . $image['Title'] . '</span> ');
					}

					if($image['Description']) {
						print('<span class="lead-picture-note">' . $image['Description'] . '</span> ');
					}

					if(!$whole) {
						print('<a href="' . $directory . $image['FileName'] . '" target="_blank">The full scan, ' . number_format((int) $image['PixelWidth']) . ' &times; ' . number_format((int) $image['PixelHeight']) . ' pixels</a>');
					}

					print('</figcaption>');
				}

				print('</figure>');
			}

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

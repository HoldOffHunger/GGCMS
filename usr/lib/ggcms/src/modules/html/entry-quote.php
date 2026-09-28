<?php

	class module_entryquotes extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

		public function DisplayLinkBox() {
			if($this->that->counts['quote'] === 0) {
				return FALSE;
			}

			print('<a class="action" href="#quote">');
			print('Quotes');
			print('<span class="action-count">' . number_format($this->that->counts['quote']) . '</span>');
			print('</a>');

			return TRUE;
		}

			/*
				One quote is a pull quote under the title; several are a
				section of their own, with a heading.
			*/

		public function Display($args) {
			if(array_key_exists('max', $args)) {
				$max = $args['max'];

				if($max > $this->that->counts['quote']) {
					$max = $this->that->counts['quote'];
				}
			} else {
				$max = $this->that->counts['quote'];
			}

			if(!$this->that->entry['quote'] || $this->that->counts['quote'] === 0 || !$max) {
				return TRUE;
			}

			$several = ($max !== 1 && $args['header']);

			print('<section class="block quotes' . ($several ? '' : ' quotes-single') . '" id="quote">');

			if($several) {
				print('<h2 class="block-title">' . $args['header'] . '</h2>');
			}

			for($i = 0; $i < $max; $i++) {
				$quote = $this->that->entry['quote'][$i];

				print('<figure class="pull-quote">');
				print('<blockquote><p>');
				print(str_replace('"', '\'', $quote['Quote']));
				print('</p></blockquote>');

				if($quote['Source']) {
					print('<figcaption>');
					print($this->that->HyperlinkizeText(['text'=>$quote['Source']]));
					print('</figcaption>');
				}

				print('</figure>');
			}

			print('</section>');

			return TRUE;
		}

	}

?>

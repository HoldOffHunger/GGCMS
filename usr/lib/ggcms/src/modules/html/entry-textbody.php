<?php

		/*
			The text of an entry: its reading tools, then the text at a
			comfortable measure, then where it came from.

			.text-to-play-as-audio is text-audio.js's: it reads aloud whatever
			that element holds, so it holds the text and nothing else.  A
			template that puts the reading tools elsewhere -- in a sidebar --
			passes 'noalts'=>TRUE and prints them itself.
		*/

	class module_entrytextbody extends module_spacing {
		public $that;
		public $header;
		public $subheader;
		public $noalts;
		public $alts;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->subheader = $args['subheader'];
			$this->noalts = $args['noalts'];

			if(!$this->noalts) {
				require_once(GGCMS_DIR . 'modules/html/alternateformats.php');
				$alts = new module_alternateformats(['that'=>$this->that]);
				$this->alts = $alts;
			}
		}

		public function Display() {
			if($this->that->entry['textbody'] && $this->that->counts['textbody']) {
				return $this->DisplayContent();
			}

			return FALSE;
		}

		public function DisplayCustomContent($args) {
			print('<section class="block text" id="textbody">');
			$this->DisplayHeader();
			$this->Display_TextBlock($args);
			print('</section>');

			return TRUE;
		}

		public function DisplayContent() {
			print('<section class="block text" id="textbody">');
			$this->DisplayHeader();
			$this->DisplayTextBody();
			print('</section>');

			return TRUE;
		}

		public function DisplayTextBody() {
			for($i = 0; $i < $this->that->counts['textbody']; $i++) {
				$textbody = $this->that->entry['textbody'][$i];
				$this->Display_TextBlock(['textbody'=>$textbody]);
			}

			return TRUE;
		}

		public function DisplayHeader() {
			if($this->header) {
				print('<h2 class="block-title">' . $this->header . '</h2>');
			}

			return TRUE;
		}

		public function Display_TextBlock($args) {
			$textbody = $args['textbody'];

			if(!$this->noalts) {
				$this->alts->Display();
			}

			if($this->subheader) {
				print('<p class="text-subheader">' . $this->subheader . '</p>');
			}

			$text = $textbody['Text'];

			if($args['quote_mode']) {
				print('<figure class="pull-quote pull-quote-large"><blockquote class="text-to-play-as-audio">');
				print($this->that->HyperlinkizeText(['text'=>$text]));
				print('</blockquote></figure>');
			} else {
				print('<div class="prose text-to-play-as-audio">');
				print($this->that->HyperlinkizeText(['text'=>$text]));
				print('</div>');
			}

			$this->Display_Source($args);

			return TRUE;
		}

		public function Display_Source($args) {
			$textbody = $args['textbody'];
			$source = '';

			if($textbody && $textbody['Source']) {
				$source = $textbody['Source'];
			} elseif($args['source']) {
				$source = $args['source'];
			}

			if(!$source) {
				return FALSE;
			}

			$source_text = $args['source_text'] ? $args['source_text'] : 'From ';

			print('<p class="source">');
			print($source_text);
			print($this->that->HyperlinkizeText(['text'=>$source]));
			print('</p>');

			return TRUE;
		}
	}

?>

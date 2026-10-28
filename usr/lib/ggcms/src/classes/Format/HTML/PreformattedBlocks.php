<?php

	/*
		Keeps a text's <pre> blocks out of modify.php's tidying.

		Saving a text body runs it through DOMDocument and then collapses
		every run of whitespace to one space, and a <pre> block is the one
		place where the whitespace is the content.  The Affero GPL's "how to
		apply" block, saved on 28 October 2026, came back as three lines
		instead of fifteen, and its <year> and <name of author> placeholders
		came back as <year></year> and <name></name>.  24 of CopyleftLicense's
		licenses keep part of their text that way.

		SetAside() swaps each block for a token in an empty <pre>, which the
		tidying passes through unchanged, and Restore() puts the block back
		exactly as it was saved.  An empty block is left in place, as the
		tidying has always removed those.
	*/

	class PreformattedBlocks {
		public $blocks = [];

			// SetAside()
			// Tests: PreformattedBlocksTest::testRoundTrip()
			// Test file: tests/src/classes/Format/HTML/PreformattedBlocksTest.php
		public function SetAside($args) {
			$text = $args['text'];

			$this->blocks = [];

			return preg_replace_callback('/<pre\b[^>]*>(.*?)<\/pre>/is', function($match) {
				if(!strlen(trim($match[1]))) {
					return $match[0];
				}

				$token = $this->Token(['index'=>count($this->blocks)]);
				$this->blocks[$token] = $match[0];

				return '<pre>' . $token . '</pre>';
			}, $text);
		}

			// Restore()
			// Tests: PreformattedBlocksTest::testRoundTrip()
			// Test file: tests/src/classes/Format/HTML/PreformattedBlocksTest.php
		public function Restore($args) {
			$text = $args['text'];

			foreach($this->blocks as $token => $block) {
				$text = str_replace('<pre>' . $token . '</pre>', $block, $text);
			}

			return $text;
		}

			/*
				Letters and digits only, so neither DOMDocument nor the
				whitespace swaps have anything in it to change.
			*/

		public function Token($args) {
			return 'GGCMSPREFORMATTEDBLOCK' . $args['index'] . 'END';
		}
	}

?>

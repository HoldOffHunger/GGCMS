<?php

	/*
		modify.php's text tidying, with a <pre> block set aside: the block
		comes back byte for byte, and everything around it is tidied as
		before.
	*/

	class PreformattedBlocksTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Format/HTML/PreformattedBlocks.php']);
		}

			// The Affero GPL's "how to apply" block, as CopyleftLicense keeps it
		const AFFERO = "<pre>    <one line to give the program's name and a brief idea of what it does.>\n    Copyright (C) <year>  <name of author>\n\n    This program is free software.</pre>";

			// What modify.php does to a text body between the two calls, cut down to the parts a <pre> block met
		public function tidy($text) {
			$dom = new DOMDocument;
			@$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
			$text = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(['<html>', '</html>', '<body>', '</body>'], ['', '', '', ''], $dom->saveHTML()));

			return preg_replace(['/<pre><\/pre>/', '/(?<!>)(?<!<)[\s]+(?!<)(?!>)/', "/<\/p>[\s]*<p>/", '/[ ]+/'], ['', ' ', "</p>\n\n<p>", ' '], $text);
		}

		public function testRoundTrip() {
			$text = "<p>How to apply\nthese terms.</p>\n" . self::AFFERO . "\n<p>Second\nparagraph.</p>" . "<pre class=\"x\">two\n\nblocks</pre>";

			$blocks = new PreformattedBlocks;
			$tidied = $blocks->Restore(['text'=>$this->tidy($blocks->SetAside(['text'=>$text]))]);

			$this->assertStringContainsString(self::AFFERO, $tidied, 'the block exactly as saved, placeholders and all');
			$this->assertStringContainsString("<pre class=\"x\">two\n\nblocks</pre>", $tidied, 'a second block, with its attribute');
			$this->assertStringContainsString('<p>How to apply these terms.</p>', $tidied, 'the paragraphs still tidied');
			$this->assertCount(2, $blocks->blocks);

			$this->assertStringNotContainsString('<year></year>', $this->tidy($text), 'without it, the placeholders become tags');
		}

		public function testEmptyBlockIsLeftToTheTidying() {
			$blocks = new PreformattedBlocks;

			$this->assertSame('<p>a</p><pre> </pre>', $blocks->SetAside(['text'=>'<p>a</p><pre> </pre>']));
			$this->assertSame([], $blocks->blocks);
		}
	}

?>

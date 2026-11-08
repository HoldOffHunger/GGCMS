<?php

	/*
		SpellcheckFix puts one reviewed spellcheck fix into a text body's
		markup, and only where the reviewed sentence is still there.
	*/

	class SpellcheckFixTest extends GGCMSTestCase {
		public function fixer() {
			$this->requireCLI(['file'=>'classes/Entries/SpellcheckFix.php']);

			return new SpellcheckFix([]);
		}

		public function testFixesTheWordInItsSentence() {
			$result = $this->fixer()->Apply([
				'text'=>'<p>He is an activist, a software engineeer, a wobbly.</p>',
				'word'=>'engineeer',
				'fix'=>'engineer',
				'before'=>'heisanactivistasoftware',
				'after'=>'awobbly',
			]);

			$this->assertSame('<p>He is an activist, a software engineer, a wobbly.</p>', $result['text']);
		}

		public function testKeepsTheCapitalOfASentenceStart() {
			$result = $this->fixer()->Apply([
				'text'=>'<p>Goverment IT schemes always collapse.</p>',
				'word'=>'Goverment',
				'fix'=>'government',
				'before'=>'',
				'after'=>'itschemesalways',
			]);

			$this->assertSame('<p>Government IT schemes always collapse.</p>', $result['text']);
		}

		public function testAReviewersCapitalWins() {
			$this->assertSame('Goes', $this->fixer()->Cased(['original'=>'Ges', 'fix'=>'Goes']));
			$this->assertSame('TARIFF', $this->fixer()->Cased(['original'=>'TARRIFF', 'fix'=>'tariff']));
		}

		public function testSkipsAnEditedSentence() {
			$result = $this->fixer()->Apply([
				'text'=>'<p>He is now a software engineeer by trade.</p>',
				'word'=>'engineeer',
				'fix'=>'engineer',
				'before'=>'heisanactivistasoftware',
				'after'=>'awobbly',
			]);

			$this->assertNull($result['text']);
			$this->assertSame('sentence has changed', $result['reason']);
		}

		public function testNeverFindsTheWordInsideAnother() {
			$result = $this->fixer()->Apply([
				'text'=>'<p>The wild geese.</p>',
				'word'=>'wil',
				'fix'=>'will',
				'before'=>'the',
				'after'=>'dgeese',
			]);

			$this->assertNull($result['text']);
		}

		public function testNeverRewritesInsideATag() {
			$result = $this->fixer()->Apply([
				'text'=>'<a href="/teh/page">read teh page</a>',
				'word'=>'teh',
				'fix'=>'the',
				'before'=>'read',
				'after'=>'page',
			]);

			$this->assertSame('<a href="/teh/page">read the page</a>', $result['text']);
		}

		public function testMatchesAcrossEntitiesAndTags() {
			$result = $this->fixer()->Apply([
				'text'=>'<p>Caf&eacute; <em>society</em>, wih Marx and Bakunin.</p>',
				'word'=>'wih',
				'fix'=>'with',
				'before'=>'cafsociety',
				'after'=>'marxandbakunin',
			]);

			$this->assertSame('<p>Caf&eacute; <em>society</em>, with Marx and Bakunin.</p>', $result['text']);
		}

		public function testRefusesTwoMatchingOccurrences() {
			$result = $this->fixer()->Apply([
				'text'=>'<p>teh cat and teh cat</p>',
				'word'=>'teh',
				'fix'=>'the',
				'before'=>'',
				'after'=>'cat',
			]);

			$this->assertNull($result['text']);
			$this->assertSame('2 occurrences match', $result['reason']);
		}
	}

?>

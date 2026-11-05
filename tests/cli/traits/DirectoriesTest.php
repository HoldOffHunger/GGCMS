<?php

	require_once(GGCMS_CLI_DIR . 'traits/Directories.php');

		// The trait with the document cache pointed at a scratch tree rather than the engine's data/
	class DirectoriesTestSubject {
		use Directories;

		public $domain;
		public $host;
		public $base;

		public function documentCacheBase() {
			return $this->base;
		}
	}

		// AutonomyBooks' document formats were 500s, its site folder never made in any of them, 5 Nov 2026
	class DirectoriesTest extends GGCMSTestCase {
		public function testDocumentCacheDirectories() {
			$base = $this->emptyScratchDirectory() . '/data/';

			foreach(['pdf', 'rtf', 'tex'] as $format) {
				mkdir($base . $format, 0755, TRUE);
			}

				// A file beside the format folders is not a format
			file_put_contents($base . 'README', '');

			$subject = new DirectoriesTestSubject();
			$subject->domain = 'autonomybooks.com';
			$subject->host = 'autonomybooks';
			$subject->base = $base;

			$this->assertSame([
				$base . 'pdf/autonomybooks/',
				$base . 'rtf/autonomybooks/',
				$base . 'tex/autonomybooks/',
			], $subject->documentCacheDirectories());

				// The archive list the retirer keeps is untouched by it
			$this->assertNotContains($base . 'rtf/autonomybooks/', $subject->domainDirectories());

			$subject->base = $base . 'absent/';

			$this->assertSame([], $subject->documentCacheDirectories());
		}
	}

?>

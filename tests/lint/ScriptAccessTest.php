<?php

	/*
		A script that requires a login is open to anyone who signs in with
		Google, unless it also says AdminOnly() -- which is FALSE by default.
		transfer.php said only RequiresLogin(), and any sign-in could move
		any entry on any site; formmaker.php and languageutils.php, both
		tools for the author, were open the same way.  Found 28 September
		2026.

		So every script that requires a login must either be admin-only or
		be named here, as a page meant for everyone who signs in.  A new
		script that is neither fails this test until someone decides.
	*/

	class ScriptAccessTest extends GGCMSTestCase {
		public function forEveryoneSignedIn() {
			return [
				'user-panel.php'=>'a reader\'s own submissions, queried by their own id',
				'chapterify.php'=>'splits a text into chapters in the browser; each chapter is a form that posts to modify.php, so it can do nothing modify would not',
				'modify.php'=>'reader submissions by design: they save unpublished, and Publish, Code and the rest are checked with isUserAdmin() field by field',
			];
		}

		public function answer($args) {
			if(!preg_match('/function ' . $args['function'] . '\(\)\s*\{\s*return\s+(TRUE|FALSE)\s*;/i', $args['source'], $match)) {
				return NULL;
			}

			return strtoupper($match[1]) === 'TRUE';
		}

		public function testEveryLoginScriptIsAdminOnlyOrNamed() {
			$open = [];
			$checked = 0;

			foreach(glob(GGCMS_DIR . 'scripts/*.php') as $file) {
				$source = file_get_contents($file);
				$name = basename($file);

				if(!$this->answer(['source'=>$source, 'function'=>'RequiresLogin'])) {
					continue;
				}

				$checked++;

				if($this->answer(['source'=>$source, 'function'=>'AdminOnly'])) {
					continue;
				}

				if(!isset($this->forEveryoneSignedIn()[$name])) {
					$open[] = $name;
				}
			}

			$this->assertGreaterThan(5, $checked, 'the login scripts were found');
			$this->assertSame([], $open, 'requires a login, is not admin-only, and is not named as a page for every sign-in');
		}

		public function testTheNamedPagesStillExist() {
			foreach($this->forEveryoneSignedIn() as $name => $why) {
				$this->assertFileExists(GGCMS_DIR . 'scripts/' . $name, $why);
			}
		}
	}

?>

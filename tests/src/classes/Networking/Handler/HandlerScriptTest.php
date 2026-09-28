<?php

	/*
		HandlerScript is Handler's script stage, moved out of Handler.php on
		28 September 2026: which script, file, class, format and extension
		answer a request.  It runs on every request and had no test.  It
		keeps no state, so each test builds a Handler without its
		constructor and hands it over.
	*/

	class HandlerScriptTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/Handler/HandlerScript.php']);
		}

		public function newScriptHandler() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };
			$handler->cleanser = new HandleInput(['handler'=>NULL]);

			return $handler->script_handler = new HandlerScript(['handler'=>$handler]);
		}

			/*
				The format and its lower-case name are two switches over the
				same extensions, kept side by side by hand.  Whatever either
				knows, the lower-case one must be the other in lower case.
			*/

		public function testConstruct_ScriptFormat() {
			$script = $this->newScriptHandler();
			$handler = $script->handler;

			$source = file_get_contents(GGCMS_DIR . 'classes/Networking/Handler/HandlerScript.php');
			preg_match_all("/^\t\t\t\tcase '([a-z0-9]*)':/m", $source, $known);
			$extensions = array_unique($known[1]);

			$this->assertGreaterThan(40, count($extensions), 'the extensions were read out of the source');

			foreach($extensions as $extension) {
				$handler->script_extension = $extension;
				$script->Construct_ScriptFormat();

				$this->assertSame(strtolower($handler->script_format), $handler->script_format_lower, '.' . $extension);
			}

			foreach(['php'=>'HTML', ''=>'HTML', 'css'=>'CSS', 'rss'=>'RSS', 'epub'=>'EPub', 'pdf'=>'PDF', 'unheardof'=>''] as $extension => $format) {
				$handler->script_extension = $extension;
				$script->Construct_ScriptFormat();

				$this->assertSame($format, $handler->script_format, '.' . $extension);
			}
		}

		public function testConstruct_ScriptName() {
			$script = $this->newScriptHandler();
			$handler = $script->handler;

			$handler->desired_script = 'browse.php';
			$script->Construct_ScriptName();
			$this->assertSame('browse.php', $handler->script_name);

			$handler->desired_script = '';
			$script->Construct_ScriptName();
			$this->assertSame('view.php', $handler->script_name, 'no script means view.php');
		}

		public function testConstruct_ScriptFileAndExtension() {
			$script = $this->newScriptHandler();
			$handler = $script->handler;

			foreach(['view.php'=>['view', 'php'], 'user-panel.php'=>['user-panel', 'php'], 'news.rss'=>['news', 'rss'], 'view.tar.gz'=>['view.tar', 'gz']] as $name => [$file, $extension]) {
				$handler->script_name = $name;
				$script->Construct_ScriptFileAndExtension();

				$this->assertSame($file, $handler->script_file, $name);
				$this->assertSame($extension, $handler->script_extension, $name);
			}

			$handler->script_file = 'user-panel';
			$script->Construct_ScriptClassname();
			$this->assertSame('userpanel', $handler->script_classname, 'a class name has no hyphens');
		}

		public function testConstruct_ScriptLocation() {
			$script = $this->newScriptHandler();
			$handler = $script->handler;

			$handler->script_format = 'CSS';
			$handler->script_file = 'display';
			$script->Construct_ScriptLocation();
			$this->assertSame(GGCMS_DIR . 'scripts/style.php', $handler->script_location, 'every stylesheet is style.php');

			$handler->script_format = 'HTML';
			$handler->script_file = 'browse';
			$script->Construct_ScriptLocation();
			$this->assertSame(GGCMS_DIR . 'scripts/browse.php', $handler->script_location);
		}
	}

?>

<?php

	/*
		The base of every GGCMS test class.

		One test method per engine function, named test<FunctionName>, so
		that the function's own header can name the place it is tested.  A
		method may make any number of assertions.

		The request superglobals are saved before every test and restored
		after, so a test may set whatever request it needs without leaking
		it into the next one.
	*/

	use PHPUnit\Framework\TestCase;

	abstract class GGCMSTestCase extends TestCase {
		private $saved_request;

		protected function setUp(): void {
			$this->saved_request = [$_SERVER, $_GET, $_POST, $_COOKIE];
		}

		protected function tearDown(): void {
			[$_SERVER, $_GET, $_POST, $_COOKIE] = $this->saved_request;
		}

			/*
				The standard libraries are loaded once by the bootstrap.
				Anything else is loaded here, once, however many tests ask.
			*/

		public function requireEngine($args) {
			return require_once(GGCMS_DIR . $args['file']);
		}

		public function requireCLI($args) {
			return require_once(GGCMS_CLI_DIR . $args['file']);
		}

			/*
				Handler's constructor opens a database and reads a site's
				configuration.  Most of its methods need neither, so this builds
				one without running the constructor, and the test sets whatever
				properties the method under test reads.
			*/

		public function newWithoutConstructor($args) {
			$reflection = new ReflectionClass($args['class']);

			return $reflection->newInstanceWithoutConstructor();
		}

		public function scratchDirectory() {
			$directory = GGCMS_DATA_DIR . 'tests/' . get_class($this);

			if(!is_dir($directory)) {
				mkdir($directory, 0755, TRUE);
			}

			return $directory;
		}
	}

?>

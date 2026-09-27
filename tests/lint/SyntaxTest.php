<?php

	/*
		php -l over every PHP file in both repositories.

		error_reporting(0) means a file that does not parse can sit in a
		template for weeks and show only as a page that is quietly blank.
		This finds it without a request.  Not in the default run -- it
		starts several thousand processes -- so ask for it:

			vendor/bin/phpunit --testsuite lint
	*/

	class SyntaxTest extends GGCMSTestCase {
		public function testEngineParses() {
			$this->assertSame([], $this->unparseableFiles(['root'=>dirname(__DIR__, 2)]));
		}

		public function testConfigurationParses() {
			$this->assertSame([], $this->unparseableFiles(['root'=>dirname(GGCMS_CONFIG_DIR, 2)]));
		}

		public function phpFiles($args) {
			$root = $args['root'];
			$files = [];

			$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
				function($file) { return !in_array($file->getFilename(), ['.git', 'vendor'], TRUE); }
			));

			foreach($iterator as $file) {
				if($file->getExtension() === 'php') {
					$files[] = $file->getPathname();
				}
			}

			return $files;
		}

			/*
				Eight at a time.  Returns "path: message" for every file that
				does not parse, so a failure names each one.
			*/

		public function unparseableFiles($args) {
			$pending = $this->phpFiles($args);
			$running = [];
			$unparseable = [];

			while(count($pending) || count($running)) {
				while(count($running) < 8 && count($pending)) {
					$file = array_shift($pending);
					$process = proc_open([PHP_BINARY, '-l', $file], [1=>['pipe', 'w'], 2=>['pipe', 'w']], $pipes);
					$running[] = ['file'=>$file, 'process'=>$process, 'pipes'=>$pipes];
				}

				$running = $this->collectFinished(['running'=>$running, 'unparseable'=>&$unparseable]);
			}

			return $unparseable;
		}

		public function collectFinished($args) {
			$still_running = [];

			foreach($args['running'] as $lint) {
				if(proc_get_status($lint['process'])['running']) {
					$still_running[] = $lint;
					continue;
				}

				$output = stream_get_contents($lint['pipes'][1]) . stream_get_contents($lint['pipes'][2]);
				fclose($lint['pipes'][1]);
				fclose($lint['pipes'][2]);

				if(strpos($output, 'No syntax errors detected') === FALSE) {
					$args['unparseable'][] = $lint['file'] . ': ' . trim($output);
				}

				proc_close($lint['process']);
			}

			if(count($still_running) === count($args['running'])) {
				usleep(2000);
			}

			return $still_running;
		}
	}

?>

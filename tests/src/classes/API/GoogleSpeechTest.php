<?php

	/*
		Only the parts that never talk to Google: the key, the language
		codes, and the daily allowance, which counts in a folder of the
		test's own.
	*/

	class GoogleSpeechTest extends GGCMSTestCase {
		private $saved_key;
		private $log_directory;

		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/API/GoogleSpeech.php']);

			$this->saved_key = getenv('GGCMS_GOOGLE_SPEECH_KEY');
			putenv('GGCMS_GOOGLE_SPEECH_KEY');

			$this->log_directory = sys_get_temp_dir() . '/ggcms-speech-test-' . getmypid() . '-' . mt_rand() . '/';
		}

		protected function tearDown(): void {
			putenv('GGCMS_GOOGLE_SPEECH_KEY' . ($this->saved_key === FALSE ? '' : '=' . $this->saved_key));

			foreach(glob($this->log_directory . 'example.com/speech/*') ?: [] as $file) {
				unlink($file);
			}

			@rmdir($this->log_directory . 'example.com/speech');
			@rmdir($this->log_directory . 'example.com');
			@rmdir($this->log_directory);

			parent::tearDown();
		}

		private function speech() {
			return new GoogleSpeech([
				'domain'=>'example.com',
				'logdirectory'=>$this->log_directory,
			]);
		}

		public function testKey() {
			$speech = $this->speech();

			if(get_cfg_var('ggcms.google_speech_key') === FALSE) {
				$this->assertFalse($speech->Key());

				putenv('GGCMS_GOOGLE_SPEECH_KEY=not a key');
				$this->assertFalse($speech->Key());
			}

			putenv('GGCMS_GOOGLE_SPEECH_KEY=AIzaSyabcdefghijklmnopqrstuvwxyz_0123-AB');
			$this->assertSame('AIzaSyabcdefghijklmnopqrstuvwxyz_0123-AB', $speech->Key());
		}

		public function testDetectLanguageCode() {
			$speech = $this->speech();

			$this->assertSame('fr', $speech->DetectLanguageCode(['language'=>'fr']));
			$this->assertSame('zh', $speech->DetectLanguageCode(['language'=>'zh-CN']));
			$this->assertSame('zh', $speech->DetectLanguageCode(['language'=>'zh-TW']));
			$this->assertSame('pt', $speech->DetectLanguageCode(['language'=>' PT-pt ']));

			$this->assertFalse($speech->DetectLanguageCode(['language'=>'']));
				// undetermined is a code, just not one the list has -- Pronounce() says unknown
			$this->assertSame('und', $speech->DetectLanguageCode(['language'=>'und']));
			$this->assertFalse($speech->DetectLanguageCode(['language'=>'../x']));
		}

		public function testLanguages() {
			$languages = $this->speech()->Languages();

				// English and EarthFluent's thirteen
			$this->assertCount(14, $languages);
			$this->assertSame('pt-BR', $languages['pt']['locale']);

			foreach($languages as $code=>$language) {
				$this->assertMatchesRegularExpression('#\A[a-z]{2}\z#', $code);
				$this->assertMatchesRegularExpression('#\A[a-z]{2,3}-[A-Z]{2}\z#', $language['locale']);
			}
		}

		public function testPronounce_Allowance() {
			$speech = $this->speech();

			$this->assertTrue($speech->Pronounce_Allowance(['characters'=>60, 'dailycharacters'=>100]));
			$this->assertTrue($speech->Pronounce_Allowance(['characters'=>40, 'dailycharacters'=>100]));

				// full: refused, and the refusal costs nothing
			$this->assertFalse($speech->Pronounce_Allowance(['characters'=>1, 'dailycharacters'=>100]));
			$this->assertSame('100', file_get_contents($this->log_directory . 'example.com/speech/' . gmdate('Y-m-d') . '.txt'));

				// a larger allowance has room again
			$this->assertTrue($speech->Pronounce_Allowance(['characters'=>1, 'dailycharacters'=>101]));
		}

		public function testPronounce_NoKey() {
			if(get_cfg_var('ggcms.google_speech_key') !== FALSE) {
				$this->markTestSkipped('A real key is set; this would spend it.');
			}

			$this->assertSame(['error'=>'key'], $this->speech()->Pronounce([
				'text'=>'bonjour',
				'language'=>'fr',
				'dailycharacters'=>100,
			]));
		}
	}

?>

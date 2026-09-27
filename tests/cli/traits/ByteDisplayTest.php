<?php

	require_once(GGCMS_CLI_DIR . 'traits/ByteDisplay.php');

	class ByteDisplayTestSubject {
		use ByteDisplay;
	}

	class ByteDisplayTest extends GGCMSTestCase {
		public function testFormatBytes() {
			$subject = new ByteDisplayTestSubject();

			$cases = [
				[0, NULL, '0 B'],
				[923, NULL, '923 B'],
				[1024, NULL, '1.0 KB'],
				[1536, NULL, '1.5 KB'],
				[1048576, 2, '1.00 MB'],
				[-2048, NULL, '-2.0 KB'],
				[1024 ** 6, NULL, '1,024.0 PB'],
			];

			foreach($cases as [$number, $precision, $expected]) {
				$args = ['number'=>$number];

				if($precision !== NULL) {
					$args['precision'] = $precision;
				}

				$this->assertSame($expected, $subject->formatBytes($args), (string)$number);
			}
		}

		public function testFormatBytesPadded() {
			$this->assertSame('    1.0 KB', (new ByteDisplayTestSubject())->formatBytesPadded(['number'=>1024]));
			$this->assertSame('1.0 KB', (new ByteDisplayTestSubject())->formatBytesPadded(['number'=>1024, 'width'=>3]), 'never truncated to fit');
		}

		public function testFormatSavedPercent() {
			$this->assertSame('25.0%', (new ByteDisplayTestSubject())->formatSavedPercent(['before'=>400, 'after'=>300]));
			$this->assertSame('0.0%', (new ByteDisplayTestSubject())->formatSavedPercent(['before'=>0, 'after'=>0]), 'nothing to shrink is no division by zero');
		}
	}

?>

<?php

	class GGCMSDateFormatTestSubject {
		use GGCMSDateFormat;
	}

	class GGCMSDateFormatTest extends GGCMSTestCase {
		public function testFormatDate() {
			$subject = new GGCMSDateFormatTestSubject();

			$cases = [
				['2026-09-27', FALSE, 'September 27, 2026'],
				['2026-09-27', TRUE, 'Sep. 27, 2026'],
				['1871-03-00', FALSE, 'March, 1871'],
				['1871-03-00', TRUE, 'Mar., 1871'],
				['1917-00-00', FALSE, '1917'],
				['bce400-00-00', FALSE, '400 BCE'],
			];

			foreach($cases as [$date, $short, $expected]) {
				$this->assertSame($expected, $subject->FormatDate(['date'=>$date, 'short-dates'=>$short]), $date . ($short ? ' (short)' : ''));
			}

			$this->assertSame('?', $subject->FormatDate(['date'=>'', 'short-dates'=>FALSE]), 'no date is a question mark');
			$this->assertSame('?', $subject->FormatDate(['date'=>NULL, 'short-dates'=>FALSE]));
		}
	}

?>

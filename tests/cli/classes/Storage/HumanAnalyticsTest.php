<?php

	require_once(GGCMS_CLI_DIR . 'classes/Storage/HumanAnalytics.php');

	class HumanAnalyticsTest extends GGCMSTestCase {
		public function view($args) {
			return array_merge([
				'screen'=>'1920x1080',
				'language'=>'en-US',
				'timezone'=>'America/New_York',
				'event'=>'mousemove',
				'referrer'=>'',
				'agent'=>'Mozilla/5.0(WindowsNT10.0;Win64;x64)AppleWebKit/537.36(KHTML,likeGecko)Chrome/153.0.0.0Safari/537.36',
			], $args);
		}

			/*
				Each farm, as it appears in the beacon logs, and the readers
				nearest to each who must be kept.
			*/

		public function testIsFarm() {
			$analytics = $this->newWithoutConstructor(['class'=>'HumanAnalytics']);

			$linux = 'Mozilla/5.0(X11;Linuxx86_64)AppleWebKit/537.36(KHTML,likeGecko)Chrome/154.0.0.0Safari/537.36';
			$android = 'Mozilla/5.0(Linux;Android10;K)AppleWebKit/537.36(KHTML,likeGecko)Chrome/153.0.0.0MobileSafari/537.36';
			$firefox = 'Mozilla/5.0(X11;Linuxx86_64;rv:142.0)Gecko/20100101Firefox/142.0';
			$edge = 'Mozilla/5.0(WindowsNT10.0;Win64;x64)AppleWebKit/537.36(KHTML,likeGecko)Chrome/153.0.0.0Safari/537.36Edg/153.0.0.0';

			$farm = [
				'the first farm' => ['language'=>'zh-CN', 'timezone'=>'Asia/Shanghai'],
				'Linux Chrome, key first' => ['agent'=>$linux, 'event'=>'keydown'],
				'Windows Chrome, en-US, Asia, from nowhere' => ['timezone'=>'Asia/Singapore'],
				'Windows Chrome, en-US, UTC, from nowhere' => ['timezone'=>'UTC'],
			];

			$readers = [
				'Shanghai on another screen' => ['screen'=>'1366x768', 'language'=>'zh-CN', 'timezone'=>'Asia/Shanghai'],
				'Linux Chrome that moved the mouse first' => ['agent'=>$linux],
				'Linux Chrome, key first, on another screen' => ['agent'=>$linux, 'event'=>'keydown', 'screen'=>'2560x1440'],
				'Linux Firefox, key first' => ['agent'=>$firefox, 'event'=>'keydown'],
				'Android Chrome, key first' => ['agent'=>$android, 'event'=>'keydown'],
				'Windows Chrome in New York, from nowhere' => [],
				'Windows Chrome in Singapore, from Wikipedia' => ['timezone'=>'Asia/Singapore', 'referrer'=>'https://en.wikipedia.org/'],
				'Windows Chrome in Singapore, en-GB' => ['timezone'=>'Asia/Singapore', 'language'=>'en-GB'],
				'Edge in Singapore, from nowhere' => ['timezone'=>'Asia/Singapore', 'agent'=>$edge],
			];

			foreach($farm as $label => $fields) {
				$this->assertTrue($analytics->isFarm(['view'=>$this->view($fields)]), $label);
			}

			foreach($readers as $label => $fields) {
				$this->assertFalse($analytics->isFarm(['view'=>$this->view($fields)]), $label);
			}
		}
	}

?>

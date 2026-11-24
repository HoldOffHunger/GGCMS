<?php

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunInSeparateProcess;

	class RobotsTest extends GGCMSTestCase {
		private function robotsText($args) {
			$this->requireEngine(['file'=>'scripts/Format/base_format.php']);
			$this->requireEngine(['file'=>'scripts/Format/HTML/basicscript.php']);
			$this->requireEngine(['file'=>'scripts/robots.php']);
			$robots = $this->newWithoutConstructor(['class'=>'robots']);
			$robots->handler = (object)[
				'globals'=>$args['globals'],
				'domain'=>new class {
					public function GetPrimaryDomain($args) { return 'https://example.com'; }
				},
				'language'=>new class {
					public function getLanguageCode() { return 'en'; }
				},
				'abstractglobals'=>(object)['site'=>new class {
					public function Copyright() { return 'Fixture'; }
				}],
			];
			$robots->all_domains = [];
			$robots->SetRobotsTXTAttributes();
			return $robots->SetRobotsTXTFile();
		}

		private function canFetch($args) {
			// The generated file uses one User-agent per group. Match its wildcard and
			// end markers, and prefer the longest matching pattern (Allow wins a tie).
			$groups = preg_split('/^User-agent:\s*/m', $args['text']);
			$by_agent = [];
			foreach(array_slice($groups, 1) as $group) {
				[$agent, $rules] = explode("\n", $group, 2);
				$by_agent[trim($agent)] = $rules;
			}
			$rules = $by_agent[$args['agent'] ?? '*'] ?? $by_agent['*'];
			preg_match_all('/^(Allow|Disallow): (\S+)/m', $rules, $matches, PREG_SET_ORDER);
			$score = -1;
			$allowed = TRUE;
			foreach($matches as [, $directive, $pattern]) {
				$anchored = str_ends_with($pattern, '$');
				$path = $anchored ? substr($pattern, 0, -1) : $pattern;
				$regex = str_replace('\\*', '.*', preg_quote($path, '#'));
				if(preg_match('#\A' . $regex . ($anchored ? '\\z' : '') . '#', $args['path'])) {
					$length = strlen(str_replace('*', '', $path));
					if($length > $score || ($length === $score && $directive === 'Allow')) {
						$score = $length;
						$allowed = $directive === 'Allow';
					}
				}
			}
			return $allowed;
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState(FALSE)]
		public function testOnlyCachedQuizShapesAreAllowedOnEnabledSites() {
			$text = $this->robotsText(['globals'=>new class {
				public function AllowCachedQuizCrawling() { return TRUE; }
			}]);
			for($previous = 0; $previous <= 10; $previous++) {
				for($future = 0; $future <= 10; $future++) {
					$query = '?quizmode=1' . ($previous ? '&previousquizzes=' . $previous : '')
						. ($future ? '&futurequizzes=' . $future : '');
					foreach(['/view.php', '/course/topic/lesson/view.php'] as $path) {
						$this->assertTrue($this->canFetch(['text'=>$text, 'path'=>$path . $query]), $path . $query);
					}
				}
			}

			foreach([
				'/lesson/view.php?previousquizzes=2&quizmode=1',
				'/lesson/view.php?quizmode=1&mobilefriendly=1',
				'/lesson/view.php?quizmode=2',
				'/lesson/view.php?quizmode=1&previousquizzes=11',
				'/lesson/view.php?quizmode=1&futurequizzes=0',
				'/lesson/?quizmode=1',
				'/preview.php?quizmode=1',
				'/other.php?example=/view.php?quizmode=1',
			] as $path) {
				$this->assertFalse($this->canFetch(['text'=>$text, 'path'=>$path]), $path);
			}
			$this->assertTrue($this->canFetch(['text'=>$text, 'path'=>'/sitemap.xml']));
			$this->assertFalse($this->canFetch(['text'=>$text, 'path'=>'/view.php?quizmode=1', 'agent'=>'GPTBot']));

			$disabled = $this->robotsText(['globals'=>new stdClass()]);
			$this->assertFalse($this->canFetch(['text'=>$disabled, 'path'=>'/view.php?quizmode=1']));
		}
	}

<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/ByteDisplay.php');
	clireq('traits/CLIAccess.php');

	/*
		Disk and inode headroom, for every volume this host writes to.

		This is the check that would have prevented the 2024 outage, and it
		could not have: it printed disk_free_space('/') and then ran du -s /,
		which walked every mounted volume -- minutes of I/O on a one-core host
		-- and it exited 0 whatever it found.  On a timer it would have logged
		the disk filling, faithfully, to nobody.

		It now reads each path's filesystem with df, which is instant, and
		answers the way the backup checker does: exit 0 when every volume has
		room, 1 when one is getting close, 2 when one is nearly full or out of
		inodes.  Cron mails on 2.

		  check_free_space.php
		  check_free_space.php --path=/ --path=/mnt/nyc01 --warn=15 --fail=8 --quiet

		--warn and --fail are the percent free below which a volume warns or
		fails.  A volume under 1 GB free fails whatever its percentage, since
		a percentage of a small disk is not room.  --quiet prints nothing
		when every volume passes.
	*/

	class FreeSpace {
		use ByteDisplay;
		use CLIAccess;
		
		public $paths;
		public $warn_percent;
		public $fail_percent;
		public $quiet;

		public function checkFreeSpace() {
			$this->readArguments();

			$results = [];

			foreach($this->paths as $path) {
				$results[] = $this->checkPath(['path'=>$path]);
			}

			$exit_code = 0;

			foreach($results as $result) {
				$exit_code = max($exit_code, $result['code']);
			}

			if(!$this->quiet || $exit_code) {
				$this->setHandle();
				$this->bannerMessage();

				$rows = [];

				foreach($results as $result) {
					unset($result['code']);
					$rows[] = $result;
				}

				print(arr2textTable($rows) . PHP_EOL);
			}

			return $exit_code;
		}

		public function readArguments() {
			$this->paths = [];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--path=') === 0) {
					$this->paths[] = substr($argument, 7);
				}
			}

			if(!$this->paths) {
				$this->paths = ['/'];
			}

			$this->warn_percent = (float)$this->argumentValue(['name'=>'warn', 'default'=>15]);
			$this->fail_percent = (float)$this->argumentValue(['name'=>'fail', 'default'=>8]);
			$this->quiet = in_array('--quiet', $this->argv, TRUE);

			return TRUE;
		}

		public function argumentValue($args) {
			$name = $args['name'];
			$default = $args['default'];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--' . $name . '=') === 0) {
					return substr($argument, strlen($name) + 3);
				}
			}

			return $default;
		}

		public function MinimumFreeBytes() {
			return 1024 * 1024 * 1024;
		}

		public function checkPath($args) {
			$path = $args['path'];

			$free_bytes = @disk_free_space($path);
			$total_bytes = @disk_total_space($path);

			if($free_bytes === FALSE || !$total_bytes) {
				return [
					'path'=>$path,
					'free'=>'-',
					'size'=>'-',
					'free %'=>'-',
					'inodes free %'=>'-',
					'status'=>'FAIL -- cannot read this path',
					'code'=>2,
				];
			}

			$free_percent = 100 * $free_bytes / $total_bytes;
			$inode_free_percent = $this->InodeFreePercent(['path'=>$path]);

			$lowest_percent = $inode_free_percent === NULL ? $free_percent : min($free_percent, $inode_free_percent);

			if($lowest_percent < $this->fail_percent || $free_bytes < $this->MinimumFreeBytes()) {
				$status = 'FAIL';
				$code = 2;
			} elseif($lowest_percent < $this->warn_percent) {
				$status = 'WARN';
				$code = 1;
			} else {
				$status = 'OK';
				$code = 0;
			}

			return [
				'path'=>$path,
				'free'=>$this->formatBytes(['number'=>$free_bytes]),
				'size'=>$this->formatBytes(['number'=>$total_bytes]),
				'free %'=>number_format($free_percent, 1),
				'inodes free %'=>$inode_free_percent === NULL ? '-' : number_format($inode_free_percent, 1),
				'status'=>$status,
				'code'=>$code,
			];
		}

			/*
				PHP has no inode call, and a disk with space left but no inodes
				is as full as one without.  df -Pi is POSIX output: the fifth
				field of the second line is the percentage used.
			*/

		public function InodeFreePercent($args) {
			$path = $args['path'];

			$output = shell_exec('df -Pi ' . escapeshellarg($path) . ' 2>/dev/null');
			$lines = explode("\n", trim((string)$output));

			if(count($lines) < 2) {
				return NULL;
			}

			$fields = preg_split('/\s+/', trim($lines[1]));

			if(count($fields) < 5 || !preg_match('/^([0-9]+)%$/', $fields[4], $used)) {
				return NULL;
			}

			return 100 - (float)$used[1];
		}

					// Script-Level Functions

		public function bannerMessageText() {
			return 'GGCMS Free Space';
		}
	}

?>

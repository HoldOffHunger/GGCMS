<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');

	/*
		Read what a 500 actually said.

		server_errors.php lists counts, scripts and URLs, but never the
		message or the trace, so on 26 September finding out why revoltlib's
		news.rss failed took hand-written SQL against the live database.

		Without --id it lists the newest open tickets with their ids and the
		start of each message.  With --id it prints that one ticket whole:
		message, script, URL, dates, count, and the context and stack trace
		stored alongside it.  Request data is left out; it is redacted on the
		way in, and rows from before that were scrubbed.
	*/

	class Error500Detail {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;
		
		public $error_id;
		public $top;

		public function showErrorDetail() {
			$this->setHandle();
			$this->bannerMessage();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			if(!$this->setOptions()) {
				return $this->cancelAction(['message'=>'--id and --top take whole numbers, as in `--id=1560`.']);
			}

			$this->setGlobals();

			if($this->error_id) {
				$this->printError();
			} else {
				$this->listErrors();
			}

			return TRUE;
		}

		public function bannerMessageText() {
			return 'Server Error Detail';
		}

		public function confirmDomainText() {
			return 'Reading 500\'s For: ';
		}

			/*
				Both are validated as digits rather than escaped: they are
				interpolated into SQL and then into a shell command.
			*/

		public function setOptions() {
			$this->error_id = 0;
			$this->top = 10;

			foreach($this->argv as $argument) {
				if(preg_match('/\A--(id|top)=(.*)\z/', $argument, $option)) {
					if(!preg_match('/\A[0-9]{1,10}\z/', $option[2])) {
						return FALSE;
					}

					if($option[1] === 'id') {
						$this->error_id = (int)$option[2];
					} else {
						$this->top = max(1, (int)$option[2]);
					}
				}
			}

			return TRUE;
		}

		public function RunSQL($args) {
			$sql = $args['sql'];
			$vertical = $args['vertical'] ?? FALSE;

			return (string)shell_exec('mysql --raw ' . ($vertical ? '-E ' : '') . '-e ' . escapeshellarg($sql));
		}

		public function listErrors() {
			$sql = 'SELECT id, IncidentCount AS Count, LastModificationDate AS LastSeen, Script, LEFT(URL, 60) AS URL, ';
			$sql .= 'LEFT(REPLACE(REPLACE(ErrorMessage, CHAR(10), \' \'), CHAR(13), \'\'), 90) AS Message ';
			$sql .= 'FROM ' . $this->host . '.InternalServerError WHERE Resolved = 0 ';
			$sql .= 'ORDER BY LastModificationDate DESC LIMIT ' . $this->top . ';';

			$output = $this->RunSQL(['sql'=>$sql]);

			if(strlen($output) === 0) {
				print('No open 500\'s.  Hooray!' . PHP_EOL . PHP_EOL);
				return TRUE;
			}

			print($this->formatTable(['output'=>$output]));
			print('Newest first.  Add --id=N for one ticket\'s full message and trace.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function printError() {
			$sql = 'SELECT id, Script, IncidentCount, Resolved, OriginalCreationDate AS FirstSeen, LastModificationDate AS LastSeen, URL, ';
			$sql .= 'ErrorMessage, EnvironmentVariables AS ContextAndTrace ';
			$sql .= 'FROM ' . $this->host . '.InternalServerError WHERE id = ' . $this->error_id . ';';

			$output = $this->RunSQL(['sql'=>$sql, 'vertical'=>TRUE]);

			if(strlen($output) === 0) {
				print('No 500 with id ' . $this->error_id . ' on ' . $this->domain . '.' . PHP_EOL . PHP_EOL);
				return TRUE;
			}

			print($output . PHP_EOL);

			return TRUE;
		}
	}

?>

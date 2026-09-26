<?php

	depreq('arr2textTable/arr2textTable.php');

	ggreq('traits/LogRedaction.php');

	clireq('traits/DBAccess.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/MySQLDatabases.php');
	clireq('traits/MySQLInternalDatabases.php');
	clireq('traits/MySQLGGCMSInternalDatabases.php');
	clireq('traits/MySQLClustersInternalDatabases.php');

	/*
		Scrub request data out of ISE and ISI rows stored before redaction.

		Until 26 September 2026 every error and issue row kept print_r() of
		$_SERVER, $_POST and $_GET, and every error row kept the whole handler
		-- cookies, any password a failed login carried, and the global
		password seed on every error on every site.  Redaction now happens on
		the way in (traits/LogRedaction.php), but the rows already written
		keep what they were given.

		A print_r() dump cannot be reliably parsed back and redacted, so the
		stored request columns of those rows are replaced outright.  The
		message, script, URL, counts and dates stay: they are what triage
		reads, and none of them is a request dump.  Sensitive query values
		in the URL columns, including the instance tables, are masked with
		the same key list the logger uses.

		A row's request columns are written once, when it is created -- a
		repeat of the same error only bumps its count -- so the cut is on
		OriginalCreationDate.  Dry by default; --apply writes.
	*/

	class ScrubServerErrors {
		use DBAccess;
		use CLIAccess;
		use GlobalsTrait;
		use MySQLDatabases;
		use MySQLInternalDatabases;
		use MySQLGGCMSInternalDatabases;
		use MySQLClustersInternalDatabases;
		use LogRedaction;

		public function scrubServerErrors() {
			$this->setHandle();
			$this->bannerMessage();

			$this->apply = in_array('--apply', $this->argv, TRUE);

			if(!$this->setDate()) {
				return $this->cancelAction(['message'=>'Invalid date.  Please submit one in the form of `2026-09-27`.']);
			}

			$this->setGlobals();
			$this->scrubDatabases();

			return TRUE;
		}

		public function bannerMessageText() {
			return 'Scrub Request Data From Server Errors And Issues';
		}

		public function confirmDomainText() {
			return 'Scrubbing Server Errors For: ';
		}

		public function setDate() {
			print("Scrub rows created before (YYYY-MM-DD): ");

			$arguments = array_values(array_filter(array_slice($this->argv, 1), function($argument) {
				return strpos($argument, '--') !== 0;
			}));

			if(count($arguments) && $arguments[0]) {
				$this->date = trim($arguments[0]);
				print($this->date . PHP_EOL);
			} else {
				$this->date = trim(fgets($this->handle));
			}

				/*
					Validated rather than escaped, as in ResolveErrorsBeforeDate:
					it goes into SQL and then into a shell command.
				*/

			if(!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $this->date)) {
				return FALSE;
			}

			print(PHP_EOL);

			return TRUE;
		}

		public function ScrubbedMarker() {
			return 'Scrubbed ' . date('Y-m-d') . ': stored before request data was redacted.';
		}

			/*
				MySQL 8's REGEXP_REPLACE form of LogRedaction::RedactURL().  The
				fragments are fixed lowercase words, so they need no escaping
				beyond what preg_quote would give them, which is none.
			*/

		public function URLPattern() {
			return '(^|[?&;])([^=&;#]*(' . implode('|', $this->SensitiveKeyFragments()) . ')[^=&;#]*=)[^&;#]*';
		}

		public function RunSQL($args) {
			$sql = $args['sql'];

			return trim((string)shell_exec('mysql -N -e ' . escapeshellarg($sql)));
		}

		public function TableExists($args) {
			$database = $args['database'];
			$table = $args['table'];

			return $this->RunSQL(['sql'=>'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ' . escapeshellarg($database) . ' AND TABLE_NAME = ' . escapeshellarg($table) . ';']) === '1';
		}

		public function scrubDatabases() {
			$databases = $this->getUserMySQLDatabases();

			$date = escapeshellarg($this->date);
			$marker = escapeshellarg($this->ScrubbedMarker());
			$url_pattern = escapeshellarg($this->URLPattern());

			$payload_tables = [
				'InternalServerError'=>['ServerVariable', 'PostVariable', 'GetVariable', 'EnvironmentVariables'],
				'InternalServerIssue'=>['ServerVariable', 'PostVariable', 'GetVariable'],
			];

			$url_tables = [
				'InternalServerError',
				'InternalServerErrorInstance',
				'InternalServerIssue',
				'InternalServerIssueInstance',
			];

			$rows = [];
			$total_rows = 0;
			$total_urls = 0;

			foreach($databases as $database) {
				if(!$this->TableExists(['database'=>$database, 'table'=>'InternalServerError'])) {
					continue;
				}

				$row = ['database'=>$database];

				foreach($payload_tables as $table => $columns) {
					$where = 'OriginalCreationDate < ' . $date . ' AND ' . $columns[0] . ' <> ' . $marker;
					$count = (int)$this->RunSQL(['sql'=>'SELECT COUNT(*) FROM ' . $database . '.' . $table . ' WHERE ' . $where . ';']);

					if($this->apply && $count) {
						$assignments = implode(', ', array_map(function($column) use ($marker) {
							return $column . ' = ' . $marker;
						}, $columns));

						$this->RunSQL(['sql'=>'UPDATE ' . $database . '.' . $table . ' SET ' . $assignments . ', LastModificationDate = LastModificationDate WHERE ' . $where . ';']);
					}

					$row[$table . ' rows'] = $count;
					$total_rows += $count;
				}

				$url_count = 0;

				foreach($url_tables as $table) {
					$where = 'REGEXP_LIKE(URL, ' . $url_pattern . ', \'i\')';
					$count = (int)$this->RunSQL(['sql'=>'SELECT COUNT(*) FROM ' . $database . '.' . $table . ' WHERE ' . $where . ';']);

					if($this->apply && $count) {
						$this->RunSQL(['sql'=>'UPDATE ' . $database . '.' . $table . ' SET URL = REGEXP_REPLACE(URL, ' . $url_pattern . ', \'$1$2' . $this->RedactedMarker() . '\', 1, 0, \'i\'), LastModificationDate = LastModificationDate WHERE ' . $where . ';']);
					}

					$url_count += $count;
				}

				$row['URLs masked'] = $url_count;
				$total_urls += $url_count;

				$rows[] = $row;
			}

			print(arr2textTable($rows) . PHP_EOL);

			if($this->apply) {
				print('Scrubbed ' . $total_rows . ' row(s) and masked ' . $total_urls . ' URL(s).' . PHP_EOL);
			} else {
				print('Dry run: ' . $total_rows . ' row(s) and ' . $total_urls . ' URL(s) would change.  Add --apply to write.' . PHP_EOL);
			}

			print('Messages, scripts, counts and dates are left as they are.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}
	}

?>

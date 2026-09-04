<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/DBAccess.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	clireq('traits/MySQLDatabases.php');
	clireq('traits/MySQLInternalDatabases.php');
	clireq('traits/MySQLGGCMSInternalDatabases.php');
	clireq('traits/MySQLClustersInternalDatabases.php');

	/*
		Resolve every unresolved 500 last seen before a given date.

		The queue is only useful if what remains in it is real, and on
		3 September 2026 it was 339 tickets of which almost none described a
		live fault: the child_types fault, the deleted src/data directory, the
		doubled query separators, and 2,471 incidents from two broken deploys
		that evening.  All fixed, all still listed, so triage meant re-deriving
		that by reading dates on every row.

		Resolved rather than deleted.  Resolved = 1 takes a ticket out of the
		live list while keeping first-seen, last-seen and the incident count --
		which is the evidence that says it was stale in the first place.  A
		DELETE throws away the only proof that the decision was right, and the
		instance rows behind these run to millions.

		Takes a date because "before this" is the only judgement being made,
		and it belongs to whoever runs the tool rather than to the tool.
	*/

	class ResolveErrorsBeforeDate {
		use DBAccess;
		use CLIAccess;
		use GlobalsTrait;
		use MySQLDatabases;
		use MySQLInternalDatabases;
		use MySQLGGCMSInternalDatabases;
		use MySQLClustersInternalDatabases;

		public function resolveErrorsBeforeDate() {
			$this->setHandle();
			$this->bannerMessage();

			if(!$this->setDate()) {
				return $this->cancelAction(['message'=>'Invalid date.  Please submit one in the form of `2026-09-01`.']);
			}

			$this->setGlobals();
			$this->resolveErrors();

			return TRUE;
		}

		public function setDate() {
			print("Resolve errors last seen before (YYYY-MM-DD): ");

			if(array_key_exists(1, $this->argv) && $this->argv[1]) {
				$this->date = trim($this->argv[1]);
				print($this->date . PHP_EOL);
			} else {
				$this->date = trim(fgets($this->handle));
			}

				/*
					Validated rather than escaped.  This string is interpolated
					into SQL and then into a shell command, and the only shape
					it may ever have is a date.
				*/

			if(!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $this->date)) {
				return FALSE;
			}

			print(PHP_EOL);

			return TRUE;
		}

		public function resolveErrors() {
			$databases = $this->getUserMySQLDatabases();

			$rows = [];
			$total_resolved = 0;
			$total_remaining = 0;

			foreach($databases as $database) {
				$count_sql = 'SELECT COUNT(*) FROM ' . $database . '.InternalServerError WHERE Resolved = 0;';
				$before = (int)trim(shell_exec('mysql -N -e ' . escapeshellarg($count_sql)));

				$sql = 'UPDATE ' . $database . '.InternalServerError SET Resolved = 1 WHERE Resolved = 0 AND LastModificationDate < ' . escapeshellarg($this->date) . ';';

				shell_exec('mysql -e ' . escapeshellarg($sql));

				$count_sql = 'SELECT COUNT(*) FROM ' . $database . '.InternalServerError WHERE Resolved = 0;';
				$after = (int)trim(shell_exec('mysql -N -e ' . escapeshellarg($count_sql)));

				$resolved = $before - $after;

				$total_resolved += $resolved;
				$total_remaining += $after;

				$rows[] = [$database, $before, $resolved, $after];
			}

			print(arr2textTable($rows, ['database', 'was open', 'resolved', 'still open']) . PHP_EOL);

			print('Resolved ' . $total_resolved . ' ticket(s).  ' . $total_remaining . ' still open.' . PHP_EOL);
			print('Nothing was deleted; first-seen, last-seen and incident counts are intact.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}
	}

?>

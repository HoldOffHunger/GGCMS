<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');

	/*
		Lists the unresolved issue queue for one domain.

		The error side has had a lister since the beginning -- List500 for the
		fatals and List404 for the not-founds -- and the issue side had only
		IssueCounts, which answers "how many" across every domain and nothing
		else.  Triage needs the other question: which ones, on this site, of
		this kind.

		Filtered by type, because a full queue is not readable.  On 4 September
		2026 revoltlib held 2,589 Missing Braille Glyph tickets, 88 not-founds
		and one bad permalink; earthfluent held 379 and every one of them was a
		404 from a scanner.  Handing either list back whole answers nothing and
		buries the one line that matters.

		The type is checked against the types the site has actually recorded,
		rather than escaped.  That is not only safer -- the value reaches the
		database through a shell, so there are two sets of quoting rules to get
		wrong and only one chance -- it is also more useful, because a typo
		gets a list of what was meant instead of an empty table.
	*/

	class ListIssues {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;
		use GlobalsTrait;

		public function listIssues() {
			$this->setHandle();
			$this->bannerMessage();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setGlobals();
			$this->setMySQLArgs();

			$this->readOptions();

			if($this->wants_type_list) {
				return $this->listTypes();
			}

			if(!$this->resolveType()) {
				return TRUE;
			}

			$this->getAndListIssues();

			return TRUE;
		}

			// Options
			// -------------------------------------------------

		/*
			argv[1] is the domain, which setDomain has already taken.  Anything
			after it that begins with two dashes is ours.
		*/

		public function readOptions() {
			$this->requested_type = '';
			$this->wants_type_list = FALSE;
			$this->answer_type = 10;

			foreach(array_slice((array) $this->argv, 2) as $argument) {
				if($argument === '--types') {
					$this->wants_type_list = TRUE;

					continue;
				}

				if(substr($argument, 0, 7) === '--type=') {
					$this->requested_type = substr($argument, 7);

					continue;
				}

				if(substr($argument, 0, 8) === '--limit=') {
					$this->answer_type = (int) substr($argument, 8);

					continue;
				}
			}

				/*
					A limit is a number and reaches the database as one.  Cast
					rather than escaped, because there is no value of this that
					is text.
				*/

			if($this->answer_type < 1) {
				$this->answer_type = 10;
			}

			return TRUE;
		}

			// Types
			// -------------------------------------------------

		public function getRecordedTypes() {
			$sql_command = 'SELECT DISTINCT IssueType FROM ' . $this->host . '.InternalServerIssue WHERE Resolved = 0;';

			$output = trim((string) shell_exec('mysql -N -e "' . $sql_command . '"'));

			if(strlen($output) === 0) {
				return [];
			}

			return array_values(array_filter(array_map('trim', explode("\n", $output)), 'strlen'));
		}

		public function listTypes() {
			$types = $this->getRecordedTypes();

			if(!count($types)) {
				print(PHP_EOL . 'No unresolved issues.  Hooray!' . PHP_EOL . PHP_EOL);

				return TRUE;
			}

			print('Unresolved issue types on ' . $this->domain . ':' . PHP_EOL . PHP_EOL);

			foreach($types as $type) {
				print('    ' . $type . PHP_EOL);
			}

			print(PHP_EOL);

			return TRUE;
		}

		/*
			An exact match against what the site has recorded, or nothing.

			Without a type the whole queue comes back, which is the behaviour
			the error listers have and is right for a small queue.  With one
			that does not match, say so and show the alternatives: an empty
			table is indistinguishable from a quiet site, and the difference
			matters.
		*/

		public function resolveType() {
			if(!strlen($this->requested_type)) {
				return TRUE;
			}

			$types = $this->getRecordedTypes();

			foreach($types as $type) {
				if(strcasecmp($type, $this->requested_type) === 0) {
					$this->requested_type = $type;

					return TRUE;
				}
			}

			print(PHP_EOL . 'No unresolved issues of type `' . $this->requested_type . '` on ' . $this->domain . '.' . PHP_EOL . PHP_EOL);

			if(count($types)) {
				print('Recorded types are:' . PHP_EOL . PHP_EOL);

				foreach($types as $type) {
					print('    ' . $type . PHP_EOL);
				}

				print(PHP_EOL);
			}

			return FALSE;
		}

			// The list
			// -------------------------------------------------

		public function getAndListIssues() {
			$where = 'WHERE Resolved = 0';

			if(strlen($this->requested_type)) {
				$where .= " AND IssueType = '" . $this->requested_type . "'";
			}

			$sql_command = 'SELECT IncidentCount as Count, IssueType as Type, LastModificationDate as LastSeen, URL FROM '
				. $this->host . '.InternalServerIssue '
				. $where
				. ' ORDER BY IncidentCount DESC LIMIT ' . (int) $this->answer_type . ';';

			print('Getting issues for ' . $this->domain . '.' . PHP_EOL . PHP_EOL);

			$output = shell_exec('mysql -e "' . $sql_command . '"');

			if(strlen((string) $output) === 0) {
				print(PHP_EOL . 'No issues.  Hooray!' . PHP_EOL . PHP_EOL);
			} else {
				print($this->formatTable(['output'=>$output]));
			}

			print('Issues successfully retrieved for ' . $this->domain . '.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

			// Dialogue
			// -------------------------------------------------

		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall installation beginning.',
			]);
		}

		public function bannerMessageText() {
			return 'List Issues';
		}

		public function confirmDomainText() {
			return 'Getting Issues For: ';
		}
	}

?>

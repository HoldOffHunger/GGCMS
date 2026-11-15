<?php

		/*
			Each require is made only when its name is not yet declared, as
			ClonefromPush does, because the tests load the engine first and a
			second require of a trait or function is fatal.  For the same
			reason this keeps its own link and runQuery() rather than using
			the CLI's DBAccess trait, whose name the engine's DBAccess class
			already holds.
		*/

	if(!function_exists('arr2textTable')) {
		depreq('arr2textTable/arr2textTable.php');
	}

	if(!trait_exists('ReverseDNSNotation')) {
		ggreq('traits/ReverseDNSNotation.php');
	}

	if(!trait_exists('CLIAccess')) {
		clireq('traits/CLIAccess.php');
	}

	if(!trait_exists('ChildTypeSwitches')) {
		clireq('traits/ChildTypeSwitches.php');
	}

	/*
		How much of each site its child records actually cover, against
		what child_types/enabled.php switches on.

		check_schema.php --check=enabled already says when a table's row count
		and its switch disagree.  It cannot say that a table is in use but thin
		-- RevoltLib had 14,062 Quotes and still 14,355 texts without one -- and
		that is the state an AI Worker exists for.  So this counts entries, not
		rows: of the entries that could hold one, how many do.

		Each child table gets one of five states:

		  used       switched on, and on most of the entries that could have one
		  underused  switched on and in use, but missing from many entries:
		             a worker could fill it
		  unused     switched on, never used: switch it off, or start using it
		  off        switched off and empty, which agrees
		  unusual    switched off with rows in it.  Not wrong -- a type can be
		             shut off at once for a security concern -- but worth a look

		--fields does the same for columns, because a table can be on every
		entry with one of its columns blank on all of them: Image.Description,
		Entry.Subtitle.  --gaps is the question "who needs workers?": only the
		thin tables and columns a model could fill, most missing first.
	*/

	class ChildRecordCoverage {
		use CLIAccess;
		use ReverseDNSNotation;
		use ChildTypeSwitches;

		public $arguments;
		public $db_link;

			// Entry Point
			// -----------------------------------------------

		public function reportCoverage() {
			$this->db_link = ggcms_mysqli();
			$this->setArguments();

			if($this->arguments['help']) {
				return $this->printUsage();
			}

			$databases = $this->getDatabases();

			if(!$databases) {
				print('No databases matched.  Try --database=NAME, or omit it for all.' . "\n");

				return FALSE;
			}

			$rows = [];

			foreach($databases as $database) {
				$rows = array_merge($rows, $this->coverDatabase(['database'=>$database]));
			}

			return $this->report(['rows'=>$rows, 'databases'=>$databases]);
		}

			// Arguments
			// -----------------------------------------------

			/*
				Narrow by default, as every diagnostic here is: only the
				states that want a person -- underused, unused and unusual --
				and only tables, not columns.  --all asks for the rest.
			*/

		public function setArguments() {
			$arguments = [
				'database'=>[],
				'table'=>[],
				'state'=>[],
				'of'=>'leaves',
				'threshold'=>90,
				'fields'=>FALSE,
				'gaps'=>FALSE,
				'summary'=>FALSE,
				'json'=>FALSE,
				'all'=>FALSE,
				'help'=>FALSE,
			];

			foreach($this->argv as $argument) {
				if($argument === '--all') {
					$arguments['all'] = TRUE;
				} else if($argument === '--fields') {
					$arguments['fields'] = TRUE;
				} else if($argument === '--gaps') {
					$arguments['gaps'] = TRUE;
					$arguments['fields'] = TRUE;
				} else if($argument === '--summary') {
					$arguments['summary'] = TRUE;
				} else if($argument === '--json') {
					$arguments['json'] = TRUE;
				} else if($argument === '--help' || $argument === '-h') {
					$arguments['help'] = TRUE;
				} else if(substr($argument, 0, 11) === '--database=') {
					$arguments['database'] = $this->listArgument(['value'=>substr($argument, 11)]);
				} else if(substr($argument, 0, 8) === '--table=') {
					$arguments['table'] = $this->listArgument(['value'=>substr($argument, 8)]);
				} else if(substr($argument, 0, 8) === '--state=') {
					$arguments['state'] = $this->listArgument(['value'=>substr($argument, 8)]);
				} else if(substr($argument, 0, 5) === '--of=') {
					$arguments['of'] = substr($argument, 5);
				} else if(substr($argument, 0, 12) === '--threshold=') {
					$arguments['threshold'] = max(1, min(100, (int) substr($argument, 12)));
				}
			}

			if(!isset($this->candidateKinds()[$arguments['of']])) {
				print('--of must be one of: ' . implode(', ', array_keys($this->candidateKinds())) . '.  Using leaves.' . "\n");
				$arguments['of'] = 'leaves';
			}

			return $this->arguments = $arguments;
		}

		public function listArgument($args) {
			$value = $args['value'];

			return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
		}

		public function printUsage() {
			print("\n");
			print('GGCMS - Child Record Coverage' . "\n\n");
			print('  show_child_record_coverage.php [--database=A,B] [--table=Quote,Tag] [--state=underused,unusual]' . "\n");
			print('                                 [--of=leaves|published|text|all] [--threshold=90]' . "\n");
			print('                                 [--fields] [--gaps] [--summary] [--json] [--all]' . "\n\n");
			print('  States   used, underused, unused, off, unusual (switched off but holding rows)' . "\n");
			print('           and for columns: filled, sparse, empty, by design (blank where it should be)' . "\n");
			print('  --of     which entries could hold a child record:' . "\n");
			print('             leaves     published entries with no children of their own (the default)' . "\n");
			print('             published  every published entry' . "\n");
			print('             text       published entries with a TextBody' . "\n");
			print('             all        every entry' . "\n");
			print('  --threshold  the coverage, in percent, at which a table or column counts as used (default 90)' . "\n");
			print('  --fields     columns too: filled, sparse or empty' . "\n");
			print('  --gaps       only what a worker could fill, most missing first' . "\n");
			print('  --summary    one line per site: its entries, and ChildRecordStats\' totals' . "\n");
			print('  --json       the same rows as JSON, for the Foreman' . "\n\n");
			print('  Prints underused, unused and unusual tables unless told otherwise.  --all prints every state.' . "\n\n");

			return TRUE;
		}

			// Databases
			// -----------------------------------------------

		public function getDatabases() {
			$rows = $this->runQuery(['query'=>'SHOW DATABASES']);

			$skip = $this->skipDatabases();
			$wanted = array_flip($this->arguments['database']);
			$databases = [];

			foreach($rows as $row) {
				$database = array_values($row)[0];

				if(isset($skip[$database])) {
					continue;
				}

				if($wanted && !isset($wanted[$database])) {
					continue;
				}

				if(!$this->hasTable(['database'=>$database, 'table'=>'Entry'])) {
					continue;
				}

				$databases[] = $database;
			}

			return $databases;
		}

			/*
				clonefrom as well as MySQL's own: it is the empty reference
				schema, and every one of its tables would report as unused.
			*/

		public function skipDatabases() {
			return [
				'information_schema'=>TRUE,
				'performance_schema'=>TRUE,
				'mysql'=>TRUE,
				'sys'=>TRUE,
				'defaultdb'=>TRUE,
				'clonefrom'=>TRUE,
			];
		}

		public function hasTable($args) {
			$database = $args['database'];
			$table = $args['table'];

			$rows = $this->runQuery([
				'query'=>'SELECT COUNT(*) AS TableCount FROM information_schema.tables WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$database]) . '" AND TABLE_NAME = "' . $this->escape(['value'=>$table]) . '"',
			]);

			return (int) $rows[0]['TableCount'] > 0;
		}

			// One Site
			// -----------------------------------------------

		public function coverDatabase($args) {
			$database = $args['database'];

			$types = $this->childTypes(['database'=>$database]);
			$flags = $this->getEnabledFlags(['database'=>$database, 'tables'=>$types]);

			if($flags === FALSE) {
				return [[
					'Database'=>$database,
					'Subject'=>'child_types/enabled.php',
					'State'=>'unknown',
					'Note'=>'no config found -- cannot compare',
				]];
			}

			$candidates = $this->countCandidates(['database'=>$database]);
			$rows = [];

			if($this->arguments['summary']) {
				$rows[] = $this->summarise(['database'=>$database, 'candidates'=>$candidates]);

				return $rows;
			}

			foreach($types as $table) {
				if($this->arguments['table'] && !in_array($table, $this->arguments['table'], TRUE)) {
					continue;
				}

				$table_row = $this->coverTable([
					'database'=>$database,
					'table'=>$table,
					'enabled'=>$flags[$table],
					'candidates'=>$candidates,
				]);

				if($this->wantsRow(['row'=>$table_row])) {
					$rows[] = $table_row;
				}

				if($this->arguments['fields'] && $table_row['Rows']) {
					foreach($this->coverFields(['database'=>$database, 'table'=>$table, 'enabled'=>$flags[$table]]) as $field_row) {
						if($this->wantsRow(['row'=>$field_row])) {
							$rows[] = $field_row;
						}
					}
				}
			}

			if($this->arguments['fields'] && (!$this->arguments['table'] || in_array('Entry', $this->arguments['table'], TRUE))) {
				foreach($this->coverEntryFields(['database'=>$database, 'candidates'=>$candidates]) as $field_row) {
					if($this->wantsRow(['row'=>$field_row])) {
						$rows[] = $field_row;
					}
				}
			}

			return $rows;
		}

			/*
				A child type is a table with an Entryid column that clonefrom's
				enabled.php has a switch for.  Both lists are read, not kept here,
				so a table added with add_child_table.php is covered without
				anyone remembering this tool exists.  Form, LookupList and the
				error tables have no switch, and are not children an entry page
				loads.
			*/

		public function childTypes($args) {
			$database = $args['database'];

			$rows = $this->runQuery([
				'query'=>'SELECT TABLE_NAME FROM information_schema.columns WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$database]) . '" AND COLUMN_NAME = "Entryid"',
			]);

			$with_entryid = [];

			foreach($rows as $row) {
				$with_entryid[$row['TABLE_NAME']] = TRUE;
			}

			$types = [];

			foreach($this->switchedTypes() as $type) {
				if(isset($with_entryid[$type])) {
					$types[] = $type;
				}
			}

			return $types;
		}

			// Which Entries Could Hold One
			// -----------------------------------------------

			/*
				The denominator is the whole question.  An index page with no
				Quote is not a gap, and a chapter with none is.  Leaves -- the
				published entries nothing is assigned under -- are the default
				because they are the pages a reader finishes on on every site:
				RevoltLib's texts and authors, EarthFluent's words.  A Tag on a
				book rather than its chapters is still counted as a row, and
				shows as a low coverage rather than as nothing.
			*/

		public function candidateKinds() {
			return [
				'leaves'=>TRUE,
				'published'=>TRUE,
				'text'=>TRUE,
				'all'=>TRUE,
			];
		}

		public function candidateQuery($args) {
			$database = $this->escape(['value'=>$args['database']]);

			$of = $this->arguments['of'];

			if($of === 'all') {
				return 'SELECT id FROM `' . $database . '`.`Entry`';
			}

			$query = 'SELECT CandidateEntry.id FROM `' . $database . '`.`Entry` CandidateEntry WHERE CandidateEntry.Publish = 1';

			if($of === 'leaves') {
				$query .= ' AND NOT EXISTS (SELECT 1 FROM `' . $database . '`.`Assignment` CandidateAssignment WHERE CandidateAssignment.Parentid = CandidateEntry.id)';
			} else if($of === 'text') {
				$query .= ' AND EXISTS (SELECT 1 FROM `' . $database . '`.`TextBody` CandidateText WHERE CandidateText.Entryid = CandidateEntry.id)';
			}

			return $query;
		}

		public function countCandidates($args) {
			$database = $args['database'];

			$rows = $this->runQuery([
				'query'=>'SELECT COUNT(*) AS CandidateCount FROM (' . $this->candidateQuery(['database'=>$database]) . ') Candidates',
			]);

			return (int) $rows[0]['CandidateCount'];
		}

			// Tables
			// -----------------------------------------------

		public function coverTable($args) {
			$database = $args['database'];
			$table = $args['table'];
			$enabled = $args['enabled'];
			$candidates = $args['candidates'];

			$escaped_database = $this->escape(['value'=>$database]);
			$escaped_table = $this->escape(['value'=>$table]);

			$counts = $this->runQuery([
				'query'=>'SELECT ' .
					'(SELECT COUNT(*) FROM `' . $escaped_database . '`.`' . $escaped_table . '`) AS RowTotal, ' .
					'(SELECT COUNT(DISTINCT Child.Entryid) FROM `' . $escaped_database . '`.`' . $escaped_table . '` Child JOIN (' . $this->candidateQuery(['database'=>$database]) . ') Covered ON Covered.id = Child.Entryid) AS CoveredTotal',
			]);

			$rows = (int) $counts[0]['RowTotal'];
			$covered = (int) $counts[0]['CoveredTotal'];

			$state = $this->tableState([
				'table'=>$table,
				'enabled'=>$enabled,
				'rows'=>$rows,
				'covered'=>$covered,
				'candidates'=>$candidates,
			]);

			return [
				'Database'=>$database,
				'Subject'=>$table,
				'State'=>$state,
				'Switch'=>$enabled ? 'on' : 'off',
				'Rows'=>$rows,
				'Entries'=>$covered . ' of ' . $candidates,
				'Coverage'=>$this->percent(['part'=>$covered, 'whole'=>$candidates]),
				'Missing'=>max(0, $candidates - $covered),
				'Note'=>$this->tableNote(['state'=>$state, 'table'=>$table]),
			];
		}

		public function tableState($args) {
			$enabled = $args['enabled'];
			$rows = $args['rows'];
			$covered = $args['covered'];
			$candidates = $args['candidates'];

			if(!$enabled) {
				return $rows ? 'unusual' : 'off';
			}

			if(!$rows) {
				return 'unused';
			}

			if(isset($this->sparseByNature()[$args['table']])) {
				return 'used';
			}

			if(!$candidates || ($covered * 100) >= ($candidates * $this->arguments['threshold'])) {
				return 'used';
			}

			return 'underused';
		}

		public function tableNote($args) {
			$state = $args['state'];
			$table = $args['table'];

			if($state === 'underused') {
				if($table === 'ChildRecordStats' || $table === 'AssociatedRecordStats') {
					return 'the engine\'s own: stale?';
				}

				return $this->isFillable(['table'=>$table]) ? 'a worker could fill it' : 'thin, but not a worker\'s to fill';
			}

			if($state === 'unused') {
				return 'on, never used: switch off, or use it';
			}

			if($state === 'unusual') {
				return 'off, yet holds rows: on purpose?';
			}

			return '';
		}

			/*
				What no model should be asked to write.  Bookkeeping the engine
				keeps for itself, what readers write, and facts a model cannot
				know from the text.  Everything else -- descriptions, quotes,
				tags, image descriptions, dates, references -- is a worker's
				job, checked by a person before it is shipped.
			*/

		public function notFillable() {
			return [
				'EntryPermission'=>'bookkeeping',
				'EntryCodeReservation'=>'bookkeeping',
				'RecordChange'=>'bookkeeping',
				'ChildRecordStats'=>'bookkeeping',
				'AssociatedRecordStats'=>'bookkeeping',
				'Comment'=>'written by readers',
				'LikeDislike'=>'written by readers',
				'Suggestion'=>'written by readers',
				'Price'=>'a fact no text holds',
				'AvailabilityDateRange'=>'a fact no text holds',
			];
		}

			/*
				Tables no site means to have on every entry, so any rows at all
				is use: a reader comments on a few pages, and a code is reserved
				only when one moves.  A thin ChildRecordStats is not among them
				-- the engine keeps one per entry, and a short one is stale.
			*/

		public function sparseByNature() {
			return [
				'Comment'=>TRUE,
				'LikeDislike'=>TRUE,
				'Suggestion'=>TRUE,
				'EntryCodeReservation'=>TRUE,
				'RecordChange'=>TRUE,
			];
		}

		public function isFillable($args) {
			$table = $args['table'];

			return !isset($this->notFillable()[$table]);
		}

			// Fields
			// -----------------------------------------------

			/*
				Text columns only, and not those that mean "the default" when
				blank or that the engine writes itself: a blank Language is the
				site's own language, and file names are written by the upload.
				A number column cannot be told apart from a deliberate zero.
			*/

		public function skippedFields() {
			return [
				'Language'=>TRUE,
				'FileName'=>TRUE,
				'FileDirectory'=>TRUE,
				'IconFileName'=>TRUE,
				'StandardFileName'=>TRUE,
				'Code'=>TRUE,
				'ListTitleSortKey'=>TRUE,
				'IPAddress'=>TRUE,
				'Password'=>TRUE,
				'PasswordHash'=>TRUE,
				'Type'=>TRUE,
				'SubType'=>TRUE,
			];
		}

			/*
				Columns a model has no business filling even when they are
				thin: a ListTitle is only for when the list should differ from
				the Title, and a parent's ChildNoun only matters on parents.
			*/

		public function notFillableFields() {
			return [
				'Entry.ListTitle'=>'falls back to Title',
				'Entry.ChildAdjective'=>'only on parents',
				'Entry.ChildNoun'=>'only on parents',
				'Entry.ChildNounPlural'=>'only on parents',
				'Entry.ChildAction'=>'only on parents',
				'Entry.GrandChildAdjective'=>'only on parents',
				'Entry.GrandChildNoun'=>'only on parents',
				'Entry.GrandChildNounPlural'=>'only on parents',
				'Entry.GrandChildAction'=>'only on parents',
			];
		}

		public function textColumns($args) {
			$database = $args['database'];
			$table = $args['table'];

			$rows = $this->runQuery([
				'query'=>'SELECT COLUMN_NAME FROM information_schema.columns WHERE TABLE_SCHEMA = "' . $this->escape(['value'=>$database]) . '" AND TABLE_NAME = "' . $this->escape(['value'=>$table]) . '" AND DATA_TYPE IN ("char", "varchar", "tinytext", "text", "mediumtext", "longtext") ORDER BY ORDINAL_POSITION',
			]);

			$skipped = $this->skippedFields();
			$columns = [];

			foreach($rows as $row) {
				if(!isset($skipped[$row['COLUMN_NAME']])) {
					$columns[] = $row['COLUMN_NAME'];
				}
			}

			return $columns;
		}

			// A child table's columns, over all of its rows: an Image on a book is as much in want of a description as one on a chapter.

		public function coverFields($args) {
			$database = $args['database'];
			$table = $args['table'];
			$enabled = $args['enabled'];

			$columns = $this->textColumns(['database'=>$database, 'table'=>$table]);

			if(!$columns) {
				return [];
			}

			$escaped_database = $this->escape(['value'=>$database]);
			$escaped_table = $this->escape(['value'=>$table]);

			$counts = $this->runQuery([
				'query'=>'SELECT COUNT(*) AS RowTotal, ' . $this->filledSums(['columns'=>$columns, 'alias'=>'Child']) . ' FROM `' . $escaped_database . '`.`' . $escaped_table . '` Child',
			]);

			return $this->fieldRows([
				'database'=>$database,
				'table'=>$table,
				'columns'=>$columns,
				'counts'=>$counts[0],
				'fillable'=>$enabled && $this->isFillable(['table'=>$table]),
			]);
		}

			// Entry's own columns, over the same entries the tables are counted against.

		public function coverEntryFields($args) {
			$database = $args['database'];

			$columns = $this->textColumns(['database'=>$database, 'table'=>'Entry']);
			$columns = array_values(array_diff($columns, ['Title']));

			if(!$columns) {
				return [];
			}

			$escaped_database = $this->escape(['value'=>$database]);

			$counts = $this->runQuery([
				'query'=>'SELECT COUNT(*) AS RowTotal, ' . $this->filledSums(['columns'=>$columns, 'alias'=>'Child']) . ' FROM `' . $escaped_database . '`.`Entry` Child JOIN (' . $this->candidateQuery(['database'=>$database]) . ') Covered ON Covered.id = Child.id',
			]);

			return $this->fieldRows([
				'database'=>$database,
				'table'=>'Entry',
				'columns'=>$columns,
				'counts'=>$counts[0],
				'fillable'=>TRUE,
			]);
		}

		public function filledSums($args) {
			$columns = $args['columns'];
			$alias = $args['alias'];

			$sums = [];

			foreach($columns as $column) {
				$escaped = $this->escape(['value'=>$column]);
				$sums[] = 'SUM(TRIM(' . $alias . '.`' . $escaped . '`) <> "") AS `Filled' . $escaped . '`';
			}

			return implode(', ', $sums);
		}

		public function fieldRows($args) {
			$database = $args['database'];
			$table = $args['table'];
			$columns = $args['columns'];
			$counts = $args['counts'];
			$fillable = $args['fillable'];

			$total = (int) $counts['RowTotal'];
			$not_fillable = $this->notFillableFields();
			$rows = [];

			foreach($columns as $column) {
				$filled = (int) $counts['Filled' . $this->escape(['value'=>$column])];
				$subject = $table . '.' . $column;

				$state = $this->fieldState(['filled'=>$filled, 'total'=>$total]);
				$note = '';

				if(isset($not_fillable[$subject])) {
					$note = $not_fillable[$subject];

					if($state !== 'filled') {
						$state = 'by design';
					}
				} else if($state !== 'filled' && $fillable) {
					$note = 'a worker could fill it';
				}

				$rows[] = [
					'Database'=>$database,
					'Subject'=>$subject,
					'State'=>$state,
					'Switch'=>'',
					'Rows'=>$total,
					'Entries'=>$filled . ' of ' . $total,
					'Coverage'=>$this->percent(['part'=>$filled, 'whole'=>$total]),
					'Missing'=>max(0, $total - $filled),
					'Note'=>$note,
				];
			}

			return $rows;
		}

		public function fieldState($args) {
			$filled = $args['filled'];
			$total = $args['total'];

			if(!$filled) {
				return 'empty';
			}

			if(($filled * 100) >= ($total * $this->arguments['threshold'])) {
				return 'filled';
			}

			return 'sparse';
		}

			// Choosing What To Print
			// -----------------------------------------------

		public function wantsRow($args) {
			$row = $args['row'];

			$state = $row['State'];

			if($this->arguments['gaps']) {
				return in_array($state, ['underused', 'sparse', 'empty'], TRUE) && $row['Note'] === 'a worker could fill it';
			}

			if($this->arguments['state']) {
				return in_array($state, $this->arguments['state'], TRUE);
			}

			if($this->arguments['all']) {
				return TRUE;
			}

			return !in_array($state, ['used', 'off', 'filled', 'by design'], TRUE);
		}

			// Summary
			// -----------------------------------------------

			/*
				ChildRecordStats exists to say how much a site holds -- it was
				built to be boasted about -- so its totals are the summary.
				Entries it has no row for are counted too: a stale stats table
				undercounts, and that is worth knowing before boasting.
			*/

		public function summarise($args) {
			$database = $args['database'];
			$candidates = $args['candidates'];

			$escaped_database = $this->escape(['value'=>$database]);

			$entries = $this->runQuery([
				'query'=>'SELECT COUNT(*) AS EntryTotal, COALESCE(SUM(Publish = 1), 0) AS PublishedTotal FROM `' . $escaped_database . '`.`Entry`',
			]);

			$stats = ['RecordTotal'=>0, 'WordTotal'=>0, 'StatsTotal'=>0];

			if($this->hasTable(['database'=>$database, 'table'=>'ChildRecordStats'])) {
				$stats = $this->runQuery([
					'query'=>'SELECT COALESCE(SUM(ChildRecordCount), 0) AS RecordTotal, COALESCE(SUM(ChildWordCount), 0) AS WordTotal, COUNT(*) AS StatsTotal FROM `' . $escaped_database . '`.`ChildRecordStats`',
				])[0];
			}

			return [
				'Database'=>$database,
				'Override'=>$this->hasOverride(['database'=>$database]) ? 'yes' : 'no -- clonefrom defaults',
				'Entries'=>(int) $entries[0]['EntryTotal'],
				'Published'=>(int) $entries[0]['PublishedTotal'],
				'Candidates (' . $this->arguments['of'] . ')'=>$candidates,
				'Child records'=>number_format((int) $stats['RecordTotal']),
				'Child words'=>number_format((int) $stats['WordTotal']),
				'Entries without stats'=>max(0, (int) $entries[0]['EntryTotal'] - (int) $stats['StatsTotal']),
			];
		}

			// Output
			// -----------------------------------------------

		public function report($args) {
			$rows = $args['rows'];
			$databases = $args['databases'];

			if($this->arguments['gaps']) {
				usort($rows, function($first, $second) {
					return $second['Missing'] <=> $first['Missing'];
				});
			}

			if($this->arguments['json']) {
				print(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

				return TRUE;
			}

			if(!$rows) {
				print(count($databases) . ' databases checked against ' . $this->arguments['of'] . ', nothing to report.' . "\n");

				return TRUE;
			}

			print(arr2textTable($rows));
			print("\n" . count($rows) . ' rows across ' . count($databases) . ' databases, counted against ' . $this->arguments['of'] . '.' . "\n");

			return TRUE;
		}

		public function percent($args) {
			$part = $args['part'];
			$whole = $args['whole'];

			if(!$whole) {
				return '-';
			}

			return number_format(($part * 100) / $whole, 1) . '%';
		}

			// Every query here is built from identifiers and numbers this tool chose, escaped below; no value from outside reaches one.

		public function runQuery($args) {
			$result = $this->db_link->query($args['query']);

			return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
		}

			/*
				Identifiers cannot be bound as parameters, so they are escaped
				and wrapped rather than prepared, as SchemaChecker does.
			*/

		public function escape($args) {
			$value = $args['value'];

			return preg_replace('/[^A-Za-z0-9_]/', '', $value);
		}
	}

?>

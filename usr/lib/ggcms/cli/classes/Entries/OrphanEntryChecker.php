<?php

	clireq('traits/DBAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/CLIAccess.php');

	class OrphanEntryChecker {
		use DBAccess;
		use DomainValidation;
		use CLIAccess;

		public function checkOrphanEntries() {
			if(in_array('--help', $this->argv, TRUE) || in_array('-h', $this->argv, TRUE)) {
				print("Usage: check_orphan_entries.php DOMAIN [--summary]\n");
				print("  --summary  Print counts without listing entry IDs, titles and codes.\n");
				print("Reports entries without assignments, entries with missing parents, and child rows whose Entryid has no match.\n");
				return TRUE;
			}

			$this->setHandle();
			$this->bannerMessage();
			if(!$this->setDomain()) {
				return $this->cancelAction([
					'message'=>'Invalid domain.  Please submit a FQDN in the form of example.com.',
				]);
			}
			$this->setMySQLArgs();

			$summary = in_array('--summary', $this->argv, TRUE);
			$this->reportEntries([
				'label'=>'Entries without an assignment',
				'condition'=>'NOT EXISTS (SELECT 1 FROM `Assignment` AS a WHERE a.Childid = e.id)',
				'summary'=>$summary,
			]);
			$this->reportEntries([
				'label'=>'Entries with a missing parent',
				'condition'=>'EXISTS (
					SELECT 1 FROM `Assignment` AS a
					LEFT JOIN `Entry` AS p ON p.id = a.Parentid
					WHERE a.Childid = e.id AND a.Parentid <> 0 AND p.id IS NULL
				)',
				'summary'=>$summary,
			]);

			print("Child rows with no matching Entryid:\n");
			$total = 0;
			foreach($this->entryTables() as $table) {
				$identifier = '`' . str_replace('`', '``', $table) . '`';
				$rows = $this->runQuery([
					'query'=>'SELECT COUNT(*) AS OrphanCount FROM ' . $identifier . ' AS c
						WHERE c.Entryid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `Entry` AS e WHERE e.id = c.Entryid)',
				]);
				$count = (int) $rows[0]['OrphanCount'];
				print('  ' . $table . ': ' . $count . "\n");
				$total += $count;
			}
			print('Total child rows: ' . $total . "\n");
			return TRUE;
		}

		protected function entryTables() {
			$rows = $this->runQuery([
				'query'=>"SELECT c.TABLE_NAME FROM information_schema.COLUMNS AS c
					JOIN information_schema.TABLES AS t
					ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
					WHERE c.TABLE_SCHEMA = DATABASE() AND c.COLUMN_NAME = 'Entryid'
					AND t.TABLE_TYPE = 'BASE TABLE' ORDER BY c.TABLE_NAME",
			]);
			return array_column($rows, 'TABLE_NAME');
		}

		protected function reportEntries($args) {
			$where = ' FROM `Entry` AS e WHERE ' . $args['condition'];
			if($args['summary']) {
				$rows = $this->runQuery(['query'=>'SELECT COUNT(*) AS OrphanCount' . $where]);
				print($args['label'] . ': ' . $rows[0]['OrphanCount'] . "\n");
				return;
			}

			$rows = $this->runQuery([
				'query'=>'SELECT e.id AS EntryID, e.Title, e.Code' . $where . ' ORDER BY e.id',
			]);
			print($args['label'] . ': ' . count($rows) . "\n");
			foreach($rows as $row) {
				print('  ' . $row['EntryID'] . "\t" . str_replace(["\r", "\n", "\t"], ' ', $row['Title'] ?? '')
					. "\t" . str_replace(["\r", "\n", "\t"], ' ', $row['Code'] ?? '') . "\n");
			}
		}

		public function bannerMessageText() {
			return 'Orphan Entry Checker';
		}

		public function confirmDomainText() {
			return 'Checking orphan entries for: ';
		}
	}

?>

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
				print('Usage: check_orphan_entries.php DOMAIN [--summary]' . "\n");
				print('  --summary  Print only the total number of orphan entries.' . "\n");

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

			$where = ' FROM `Entry` AS e WHERE NOT EXISTS (
				SELECT 1 FROM `Assignment` AS a WHERE a.childid = e.id
			)';

			if(in_array('--summary', $this->argv, TRUE)) {
				$rows = $this->runQuery([
					'query'=>'SELECT COUNT(*) AS OrphanCount' . $where,
				]);

				print('Orphan entries: ' . $rows[0]['OrphanCount'] . "\n");

				return TRUE;
			}

			$rows = $this->runQuery([
				'query'=>'SELECT e.id AS EntryID' . $where . ' ORDER BY e.id',
			]);

			foreach($rows as $row) {
				print('Entry ID: ' . $row['EntryID'] . "\n");
			}

			print('Orphan entries: ' . count($rows) . "\n");

			return TRUE;
		}

		public function bannerMessageText() {
			return 'Orphan Entry Checker';
		}

		public function confirmDomainText() {
			return 'Checking orphan entries for: ';
		}
	}

?>

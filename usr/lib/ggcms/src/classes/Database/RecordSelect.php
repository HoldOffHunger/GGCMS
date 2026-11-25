<?php

	class RecordSelect {
			// GetRecordFullSelectStatement()
			// Tests: RecordSelectTest::testGetRecordFullSelectStatement()
			// Test file: tests/src/classes/Database/RecordSelectTest.php
		public function GetRecordFullSelectStatement($args) {
			$record_type = $args['recordtype'];
			$record_description = $args['recorddescription'];

			$record_select_all_terms = [];

			foreach ($record_description as $field => $description) {
				switch($description['TypeBase']) {
					case 'binary':
						$record_select_all_terms[] = 'HEX(' . $record_type . '.' . $field . ') as ' . $field;
						break;

					default:
						$record_select_all_terms[] = $record_type . '.' . $field . ' as ' . $field;
				}
			}

			$full_select = implode(', ', $record_select_all_terms);

			return $full_select;
		}

			// GetRecordFullTableSelectStatement()
			// Tests: RecordSelectTest::testGetRecordFullTableSelectStatement()
			// Test file: tests/src/classes/Database/RecordSelectTest.php
		public function GetRecordFullTableSelectStatement($args) {
			$record_type = $args['recordtype'];
			$record_description = $args['recorddescription'];

			$record_select_all_terms = [];

			foreach ($record_description as $field => $description) {
				switch($description['TypeBase']) {
					case 'binary':
						$record_select_all_terms[] = 'HEX(' . $record_type . '.' . $field . ') as \'.' . $record_type . '.' . $field . '\'';
						break;

					default:
						$record_select_all_terms[] = $record_type . '.' . $field . ' as \'' . $record_type . '.' . $field . '\'';
				}
			}

			$full_select = implode(', ', $record_select_all_terms);

			return $full_select;
		}
	}

?>

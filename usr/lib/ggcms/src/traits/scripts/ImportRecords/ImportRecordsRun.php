<?php

	/*
		An import from end to end, and its report.

		Read the file; understand its header, refusing it whole if any
		column is a name nobody knows; read every row; gather the rows into
		entries; put each entry on the belt; and say, for every row, exactly
		what it did:

			New record          a new entry, or a new child record on one
			Updated old record  an existing record changed; its old value is in RecordChange
			Deleted old record  an existing record deleted
			Transferred old record  an existing entry moved beneath another parent, and everything under it
			Unchanged           the record already says what the row says
			Skipped             not done, for a reason that is not a fault -- a twin, a stale Current_
			Error               not done, because something is wrong; the reasons follow
			Not attempted       past the limit

		A preview is the same run with the belt stopped at Prepare: every
		row checked as it would be saved, nothing written.
	*/

	trait ImportRecordsRun {
		public $import_report;
		public $import_file;

			/*
				The script has its ORM and master record by now -- importer.php's
				display() sets them, for the page and for this alike -- and every
				path below is reckoned from that master record.
			*/
		
		public function RunImport($args) {
			$apply = !empty($args['apply']);
			$limit = isset($args['limit']) ? (int)$args['limit'] : 0;

			$report = [
				'apply'=>$apply,
				'table'=>'',
				'format'=>'',
				'name'=>isset($args['name']) ? (string)$args['name'] : '',
				'fileerrors'=>[],
				'unknown'=>[],
				'rows'=>[],
				'counts'=>[],
				'images'=>0,
				'pagecache'=>'',
			];

			$file = new ImportFile();
			$this->import_file = $file;

			$file->Read(['content'=>(string)$args['content'], 'name'=>$report['name']]);

			if(!count($file->errors) && isset($args['imagezip']) && strlen((string)$args['imagezip'])) {
				$file->ReadImageZip(['content'=>$args['imagezip']]);
			}

			$report['format'] = $file->format;
			$report['images'] = count($file->images);

			$table = $this->ImportTableName(['table'=>isset($args['table']) && strlen($args['table']) ? $args['table'] : 'Entry']);

			if(!$table) {
				$file->errors[] = 'There is no table called ' . $args['table'] . ' to import into.';
			}

			$report['table'] = $table;

			if(count($file->errors)) {
				$report['fileerrors'] = $file->errors;
				$file->RemoveImages();

				return $this->import_report = $report;
			}

			$header = $this->ParseImportHeaders(['headers'=>$file->headers, 'table'=>$table]);

			if(count($header['unknown'])) {
				$report['unknown'] = $header['unknown'];
				$report['fileerrors'][] = 'Nothing was imported, because the header names column(s) this importer does not know: ' . implode(', ', $header['unknown']) . '.  A misspelt column would otherwise be skipped without a word.';
				$file->RemoveImages();

				return $this->import_report = $report;
			}

			$rows = [];

			foreach($file->rows as $index => $cells) {
				$rows[] = $this->ReadImportRow(['cells'=>$cells, 'columns'=>$header['columns'], 'line'=>$file->lines[$index]]);
			}

			$plan = $this->PlanImport([
				'rows'=>$rows,
				'table'=>$table,
				'lookup'=>function($lookup_args) {
					return $this->orm->GetChildRecordEntryid($lookup_args);
				},
			]);

			$results = $this->RunImportGroups(['plan'=>$plan, 'apply'=>$apply, 'limit'=>$limit]);

			foreach($plan['rows'] as $row_index => $row) {
				if(count($row['errors'])) {
					$result = ['outcome'=>'Error', 'details'=>$row['errors']];
				} elseif(isset($results[$row_index])) {
					$result = $results[$row_index];
				} else {
					$result = ['outcome'=>'Not attempted', 'details'=>['No entry was run for this row.']];
				}

				$result['line'] = $row['line'];
				$report['rows'][] = $result;

				if(!isset($report['counts'][$result['outcome']])) {
					$report['counts'][$result['outcome']] = 0;
				}

				$report['counts'][$result['outcome']]++;
			}

			$report['pagecache'] = html_entity_decode(strip_tags(str_replace('<br>', "\n", (string)$this->import_page_cache_report)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

			$file->RemoveImages();

			return $this->import_report = $report;
		}

			/*
				The report as plain text, for a shell and for anybody reading
				one: a summary, then a line per row with its details beneath.
				$args['only'] keeps the outcomes named -- Error alone is the
				useful question of a big preview.
			*/

		public function ImportReportText($args) {
			$report = $args['report'];
			$only = isset($args['only']) ? $args['only'] : [];

			$text = '';
			$text .= ($report['apply'] ? 'Import' : 'Preview -- nothing was written') . PHP_EOL;
			$text .= 'Table    : ' . $report['table'] . PHP_EOL;
			$text .= 'Format   : ' . $report['format'] . ($report['images'] ? ', with ' . $report['images'] . ' image(s)' : '') . PHP_EOL;

			foreach($report['fileerrors'] as $error) {
				$text .= 'Refused  : ' . $error . PHP_EOL;
			}

			if(count($report['counts'])) {
				$text .= 'Rows     : ' . count($report['rows']) . PHP_EOL;

				foreach($report['counts'] as $outcome => $count) {
					$text .= sprintf('  %-22s %d', $outcome, $count) . PHP_EOL;
				}
			}

			$text .= PHP_EOL;

			foreach($report['rows'] as $row) {
				if(count($only) && !in_array($row['outcome'], $only, TRUE)) {
					continue;
				}

				$text .= sprintf('Line %-6s %s', $row['line'], $row['outcome']) . PHP_EOL;

				foreach($row['details'] as $detail) {
					$text .= '             ' . $detail . PHP_EOL;
				}
			}

			if(strlen(trim($report['pagecache']))) {
				$text .= PHP_EOL . trim($report['pagecache']) . PHP_EOL;
			}

			return $text;
		}

		public function ImportReportHasErrors($args) {
			return count($args['report']['fileerrors']) || !empty($args['report']['counts']['Error']);
		}
	}

?>

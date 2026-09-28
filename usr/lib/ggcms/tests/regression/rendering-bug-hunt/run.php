<?php
	// Each fixture runs separately because several define minimal base classes.
	$cases = [
		['cleanup', ['keep', 'success'], 1],
		['cleanup', ['keep', 'failure'], 1],
		['cleanup', ['empty', 'success'], 1],
		['cleanup', ['empty', 'failure'], 1],
		['db-execute', [], 4],
		['update-query-errors', [], 4],
		['create-query-errors', [], 5],
		['entry-write-errors', [], 4],
		['parent-write-stop', [], 2],
		['child-write-errors', [], 12],
		['image-write-errors', [], 2],
		['image-rename-write', [], 5],
		['image-upload-invalid', [], 3],
		['image-upload-move', [], 11],
		['resize-errors', [], 9],
		['link-length', [], 8],
		['event-validation', [], 19],
		['empty-writer', [], 2],
		['date-preparation', [], 4],
		['date-pipeline', [], 5],
		['delete-binding', [], 4],
		['child-binding', [], 4],
		['404-render', [], 4],
		['handler-404', [], 4],
		['delete-results', [], 4],
		['feed-dates', [], 8],
		['feed-images', [], 6],
		['format-failure', [], 15],
		['html-dispatch', [], 4],
		['opds-images', [], 5],
		['opds-setup', [], 1],
		['xml-text', [], 6],
		['json-text', [], 4],
		['csv-values', [], 8],
		['csv-quoting', [], 7],
		['user-export-vote', [], 3],
		['user-export-text', [], 5],
		['user-export-labels', [], 5],
		['user-export-charset', [], 2],
		['entry-sort-default', [], 4],
		['entry-sort-shapes', [], 5],
		['user-export-sorting', [], 4],
		['user-lookup', [], 6],
		['request-exceptions', [], 5],
		['user-query-errors', [], 12],
		['export-query-dispatch', [], 3],
		['error-backup-output', [], 3],
		['error-shutdown', [], 1],
		['error-anonymous', [], 5],
		['error-display-text', [], 8],
		['error-token-type', [], 4],
		['error-orphan', [], 2],
		['log-redaction', [], 10],
		['session-expiry-query', [], 2],
		['session-account', [], 2],
		['session-refresh', [], 2],
		['session-create', [], 3],
		['session-write-errors', [], 4],
		['session-lookup-error', [], 2],
		['login-results', [], 3],
		['view-votes', [], 9],
		['warroom-escaping', [], 4],
		['transfer-entry', [], 7],
		['modify-delete', [], 8],
		['modify-reader-copy', [], 8],
		['session-auth-error', [], 2],
		['session-token-type', [], 6],
		['session-recheck', [], 5],
		['session-orphan', [], 2],
		['logout-write-error', [], 3],
		['logout-cookie-error', [], 2],
		['logout-token-type', [], 5],
		['logout-display', [], 3],
		['logout-state', [], 3],
		['google-account', [], 6],
		['cookie-expiry', [], 2],
		['cookie-set', [], 5],
		['session-cookie-error', [], 2],
		['cookie-read', [], 4],
		['cookie-constructor', [], 2],
		['cookie-real-cleanser', [], 4],
		['cookie-array', [], 3],
		['opds-entry-id', [], 2],
		['opds-metadata-text', [], 5],
		['opds-optional-metadata', [], 6],
		['opds-links', [], 4],
		['orm-delete', [], 5],
		['rdf-fields', [], 4],
		['rdf-empty', [], 3],
		['rdf-text', [], 6],
		['rdf-url', [], 3],
		['rdf-special', [], 5],
		['rdf-policy-template', [], 2],
		['rdf-associations', [], 14],
		['rdf-user-export', [], 4],
		['rss-selflink', [], 6],
		['atom-links', [], 10],
		['atom-title-text', [], 5],
		['atom-metadata-text', [], 6],
		['atom-summary-text', [], 6],
		['rss-summary-text', [], 12],
		['rss-summary-length', [], 8],
		['rss-summary-fallback', [], 5],
		['rss-title-text', [], 5],
		['rss-metadata-text', [], 10],
		['rss-image-text', [], 6],
		['rss-links', [], 10],
		['atom-summary-fallback', [], 5],
		['update-cleanup', [], 1],
	];
	$failed = 0;
	$total = 0;
	foreach($cases as [$name, $arguments, $expected_count]) {
		$command = array_merge([PHP_BINARY, __DIR__ . '/' . $name . '.php'], $arguments);
		$process = proc_open($command, [0=>['pipe', 'r'], 1=>['pipe', 'w'], 2=>['pipe', 'w']], $pipes);
		if(!is_resource($process)) {
			print('FAIL starting ' . $name . PHP_EOL);
			$failed++;
			continue;
		}
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		$passed = preg_match_all('/^PASS\b/m', $output);
		print($output);
		if($error !== '') {
			print($error);
		}
		if($status !== 0 || $passed !== $expected_count || strpos($output, 'FAIL') !== FALSE || $error !== '') {
			print('FAIL fixture ' . $name . ': exit=' . $status . ', expected=' . $expected_count . ', passed=' . $passed . PHP_EOL);
			$failed++;
		}
		$total += $passed;
	}
	print('Passed cases: ' . $total . '; failed fixture runs: ' . $failed . PHP_EOL);
	exit($failed ? 1 : 0);
?>
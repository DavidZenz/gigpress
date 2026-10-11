<?php
/* Trusted in-process row contract tests, separate from real HTTP upload evidence. */
function gigpress_csv_import_snapshot() {
	global $wpdb;
	$snapshot = array();
	foreach (array('shows' => GIGPRESS_SHOWS, 'artists' => GIGPRESS_ARTISTS, 'venues' => GIGPRESS_VENUES, 'tours' => GIGPRESS_TOURS) as $name => $table) $snapshot[$name] = $wpdb->get_results('SELECT * FROM ' . $table . ' ORDER BY 1', ARRAY_A);
	$snapshot['related_posts'] = $wpdb->get_results('SELECT ID, post_title, post_status, post_content FROM ' . $wpdb->posts . ' ORDER BY ID', ARRAY_A);
	return $snapshot;
}

function gigpress_csv_import_case($case) {
	global $wpdb, $admin, $gpo;
	require_once WP_PLUGIN_DIR . '/gigpress/admin/csv.php';
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/import-records.php';
	$checks = array('selected_case' => in_array($case, array('import-rows', 'import-retry', 'upload-guards'), true));
	if ($case === 'import-rows') {
		$checks['new_validation_contracts'] = function_exists('gigpress_csv_validate_upload') && function_exists('gigpress_csv_parse_upload')
			&& function_exists('gigpress_csv_validate_headers') && function_exists('gigpress_csv_validate_record');
		$checks['strict_header_validation'] = function_exists('gigpress_csv_validate_headers')
			&& gigpress_csv_validate_headers($fixture['headers'])['valid'] === true
			&& gigpress_csv_validate_headers(array_merge($fixture['headers'], array('Extra')))['valid'] === false
			&& gigpress_csv_validate_headers(array_reverse($fixture['headers']))['valid'] === false
			&& gigpress_csv_validate_headers(array_merge(array('Date'), array_slice($fixture['headers'], 1, 2), array('Date'), array_slice($fixture['headers'], 4)))['valid'] === false
			&& gigpress_csv_validate_headers(array_merge(array("\xEF\xBB\xBFDate"), array_slice($fixture['headers'], 1)))['valid'] === true;
		$valid = function_exists('gigpress_csv_validate_record') ? gigpress_csv_validate_record($fixture['base'], 2, false) : array();
		$checks['blank_time_end_date_and_exact_literals'] = ($valid['group'] ?? '') === 'prepared'
			&& ($valid['time'] ?? null) === '00:00:01' && ($valid['end_date'] ?? null) === '2032-05-06'
			&& $fixture['base']['Venue phone'] === '+43-1-555-0100' && $fixture['base']['Ticket phone'] === '+00012';
		$invalid = $fixture['base']; $invalid['Date'] = '2032-02-30';
		$checks['invalid_date_is_field_rejection'] = function_exists('gigpress_csv_validate_record')
			&& (($date = gigpress_csv_validate_record($invalid, 3, false))['group'] ?? '') === 'rejected'
			&& ($date['field'] ?? '') === 'Date' && !empty($date['reason']) && !empty($date['next_step']);
		$literalRow = $fixture['base']; $literalRow['Artist'] = $fixture['literals']['artist']; $literalRow['Ticket phone'] = $fixture['literals']['phone']; $literalRow['Notes'] = $fixture['literals']['notes'];
		$encode = function ($value) { $value = (string) $value; return '"' . str_replace('"', '""', $value) . '"'; };
		$raw = implode(',', array_map($encode, $fixture['headers'])) . "\r\n" . implode(',', array_map(function ($key) use ($encode, $literalRow) { return $encode($literalRow[$key]); }, $fixture['headers'])) . "\r\n";
		$path = tempnam(sys_get_temp_dir(), 'gigpress-csv-parser-');
		file_put_contents($path, "\xEF\xBB\xBF" . $raw);
		$parsed = gigpress_csv_parse_upload($path); @unlink($path);
		$checks['utf8_bom_and_csv_literals_preserved'] = ($parsed['records'][0]['Artist'] ?? null) === $fixture['literals']['artist']
			&& ($parsed['records'][0]['Ticket phone'] ?? null) === '+00012' && ($parsed['records'][0]['Notes'] ?? null) === $fixture['literals']['notes'];
		$checks['empty_header_only_and_invalid_encoding_are_file_errors'] = gigpress_csv_decode($fixture['raw']['empty'])['error'] !== null
			&& gigpress_csv_decode($fixture['raw']['header_only'])['error'] !== null
			&& gigpress_csv_decode($fixture['raw']['invalid_utf8'])['error'] !== null
			&& gigpress_csv_decode($fixture['raw']['utf16'])['error'] !== null
			&& gigpress_csv_decode($raw . "\xEF\xBB\xBF")['error'] !== null;
		$short = array_slice($fixture['base'], 0, -1);
		$shortResult = gigpress_csv_validate_record($short, 4, false);
		$status = $fixture['base']; $status['Status'] = 'unknown';
		$time = $fixture['base']; $time['Time'] = '24:00:00';
		$related = $fixture['base']; $related['Related ID'] = '-1';
		$checks['short_record_status_time_and_related_rejected'] = ($shortResult['group'] ?? '') === 'rejected'
			&& gigpress_csv_validate_record($status, 5, false)['group'] === 'rejected'
			&& gigpress_csv_validate_record($time, 6, false)['field'] === 'Time'
			&& gigpress_csv_validate_record($related, 7, true)['field'] === 'Related ID';
		$checks['record_width_and_utf8_validation'] = is_array($parsed) && count($parsed['records'] ?? array()) === 1;
		foreach (array(GIGPRESS_SHOWS, GIGPRESS_ARTISTS, GIGPRESS_VENUES, GIGPRESS_TOURS) as $table) $wpdb->query('DELETE FROM ' . $table);
		$literalPrepared = gigpress_csv_validate_record($literalRow, 2, false);
		$beforeInvalid = gigpress_csv_import_snapshot();
		$rejectedPrepared = $literalRow; $rejectedPrepared['Date'] = '2032-02-30';
		$rejected = gigpress_csv_validate_record($rejectedPrepared, 3, false);
		$afterInvalid = gigpress_csv_import_snapshot();
		$first = gigpress_csv_persist_record($literalPrepared);
		$afterFirst = gigpress_csv_import_snapshot();
		$again = gigpress_csv_persist_record($literalPrepared);
		$afterAgain = gigpress_csv_import_snapshot();
		$storedShow = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', (int) ($first['show_id'] ?? 0)), ARRAY_A);
		$storedArtist = $storedShow ? $wpdb->get_row($wpdb->prepare('SELECT artist_name FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_id = %d', $storedShow['show_artist_id']), ARRAY_A) : null;
		$accounted = gigpress_csv_import_result(array($first, $again, $rejected));
		$checks['rejected_and_duplicate_snapshots_unchanged'] = ($rejected['group'] ?? '') === 'rejected' && $beforeInvalid === $afterInvalid
			&& ($again['group'] ?? '') === 'duplicate' && $afterFirst === $afterAgain;
		$checks['all_groups_once_and_literal_storage_preserved'] = ($accounted['total'] ?? -1) === 3
			&& ($accounted['counts'] ?? array()) === array('imported' => 1, 'duplicate' => 1, 'rejected' => 1, 'failed' => 0)
			&& ($storedArtist['artist_name'] ?? null) === $fixture['literals']['artist']
			&& ($storedShow['show_tix_phone'] ?? null) === '+00012' && ($storedShow['show_notes'] ?? null) === $fixture['literals']['notes'];
	} elseif ($case === 'import-retry') {
		foreach (array(GIGPRESS_SHOWS, GIGPRESS_ARTISTS, GIGPRESS_VENUES, GIGPRESS_TOURS) as $table) $wpdb->query('DELETE FROM ' . $table);
		$lateRow = $fixture['base']; $lateRow['Artist'] = 'Late Failure Band'; $lateRow['Venue'] = 'Late Failure Hall'; $lateRow['Tour'] = 'Late Failure Tour';
		$latePrepared = gigpress_csv_validate_record($lateRow, 2, false);
		$target = 'INSERT INTO `' . GIGPRESS_SHOWS . '`'; $attempted = false;
		$fail = function ($sql) use (&$attempted, $target) { if (!$attempted && stripos($sql, $target) !== false) { $attempted = true; return 'INTENTIONAL IMPORT FAILURE'; } return $sql; };
		$suppressedErrors = $wpdb->suppress_errors(true);
		add_filter('query', $fail, 9999);
		$failed = gigpress_csv_persist_record($latePrepared);
		remove_filter('query', $fail, 9999);
		$wpdb->suppress_errors($suppressedErrors);
		$afterFailure = gigpress_csv_import_snapshot();
		$retry = gigpress_csv_persist_record($latePrepared);
		$afterRetry = gigpress_csv_import_snapshot();
		$repeat = gigpress_csv_persist_record($latePrepared);
		$afterRepeat = gigpress_csv_import_snapshot();
		$checks['late_write_failure_was_attempted_and_reported'] = $attempted && ($failed['group'] ?? '') === 'failed'
			&& !empty($failed['identity']) && !empty($failed['reason']) && !empty($failed['next_step']);
		$checks['late_retry_reuses_entities_and_duplicate_is_unchanged'] = count($afterFailure['artists']) === 1
			&& count($afterFailure['venues']) === 1 && count($afterFailure['tours']) === 1 && count($afterFailure['shows']) === 0
			&& ($retry['group'] ?? '') === 'imported' && count($afterRetry['artists']) === 1 && count($afterRetry['venues']) === 1
			&& count($afterRetry['tours']) === 1 && count($afterRetry['shows']) === 1 && ($repeat['group'] ?? '') === 'duplicate' && $afterRepeat === $afterRetry;
		$readbackRow = $fixture['base']; $readbackRow['Artist'] = 'Readback Failure Band'; $readbackRow['Venue'] = 'Readback Failure Hall';
		$readbackPrepared = gigpress_csv_validate_record($readbackRow, 3, false); $readbackAttempted = false;
		$readbackTarget = 'SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id';
		$failReadback = function ($sql) use (&$readbackAttempted, $readbackTarget) { if (!$readbackAttempted && stripos($sql, $readbackTarget) !== false) { $readbackAttempted = true; return 'INTENTIONAL READBACK FAILURE'; } return $sql; };
		$suppressedErrors = $wpdb->suppress_errors(true);
		add_filter('query', $failReadback, 9999);
		$readbackFailure = gigpress_csv_persist_record($readbackPrepared);
		remove_filter('query', $failReadback, 9999);
		$wpdb->suppress_errors($suppressedErrors);
		$readbackRetry = gigpress_csv_persist_record($readbackPrepared);
		$checks['readback_failure_is_failed_then_safe_duplicate_retry'] = $readbackAttempted && ($readbackFailure['group'] ?? '') === 'failed'
			&& ($readbackRetry['group'] ?? '') === 'duplicate';
		$expectedCounts = gigpress_csv_import_result(array($failed, $retry, $repeat, $readbackFailure, $readbackRetry));
		$checks['each_attempt_has_one_terminal_outcome'] = ($expectedCounts['total'] ?? 0) === 5
			&& array_sum($expectedCounts['counts'] ?? array()) === 5 && ($expectedCounts['counts']['failed'] ?? 0) === 2;
	} else {
		global $gpo;
		$temp = tempnam(sys_get_temp_dir(), 'gigpress-fabricated-upload-'); file_put_contents($temp, implode(',', $fixture['headers']));
		$before = gigpress_csv_import_snapshot();
		$_FILES = array('gp_import' => array('name' => 'shows.csv', 'tmp_name' => $temp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($temp)));
		$fake = gigpress_csv_validate_upload($_FILES['gp_import'], array('method' => 'POST', 'action' => 'import'));
		$wrongMethod = gigpress_csv_validate_upload($_FILES['gp_import'], array('method' => 'GET', 'action' => 'import'));
		$wrongAction = gigpress_csv_validate_upload($_FILES['gp_import'], array('method' => 'POST', 'action' => 'delete'));
		$after = gigpress_csv_import_snapshot(); @unlink($temp);
		$checks['fabricated_path_wrong_method_and_action_rejected_without_writes'] = empty($fake['valid']) && empty($wrongMethod['valid']) && empty($wrongAction['valid']) && $before === $after;
	}
	$failed = !$checks['selected_case'] || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

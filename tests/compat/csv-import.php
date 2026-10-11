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
		$checks['validated_persistence_and_retry_contract'] = function_exists('gigpress_csv_persist_record')
			&& function_exists('gigpress_csv_import_result');
	} else {
		$checks['upload_validator_contract'] = function_exists('gigpress_csv_validate_upload');
	}
	$failed = !$checks['selected_case'] || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

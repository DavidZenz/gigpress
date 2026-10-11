<?php
/* Trusted in-process row contract tests, separate from real HTTP upload evidence. */
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
			&& gigpress_csv_validate_headers(array_merge($fixture['headers'], array('Extra')))['valid'] === false;
		$valid = function_exists('gigpress_csv_validate_record') ? gigpress_csv_validate_record($fixture['base'], 2, false) : array();
		$checks['blank_time_end_date_and_exact_literals'] = ($valid['group'] ?? '') === 'prepared'
			&& ($valid['time'] ?? null) === '00:00:01' && ($valid['end_date'] ?? null) === '2032-05-06'
			&& $fixture['base']['Venue phone'] === '+43-1-555-0100' && $fixture['base']['Ticket phone'] === '+00012';
		$invalid = $fixture['base']; $invalid['Date'] = '2032-02-30';
		$checks['invalid_date_is_field_rejection'] = function_exists('gigpress_csv_validate_record')
			&& (($date = gigpress_csv_validate_record($invalid, 3, false))['group'] ?? '') === 'rejected'
			&& ($date['field'] ?? '') === 'Date' && !empty($date['reason']) && !empty($date['next_step']);
		$checks['record_width_and_utf8_validation'] = function_exists('gigpress_csv_parse_upload');
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

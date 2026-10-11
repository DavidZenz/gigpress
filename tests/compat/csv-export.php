<?php
/* Export selection and exact-value contracts; fixture expectations stay independent. */
function gigpress_csv_export_case($case) {
	global $wpdb, $admin, $gpo;
	require_once WP_PLUGIN_DIR . '/gigpress/admin/csv.php';
	$checks = array('selected_case' => in_array($case, array('export-filters', 'export-literals'), true));
	if ($case === 'export-filters') {
		$checks['shared_export_contracts_available'] = function_exists('gigpress_csv_export_state') && function_exists('gigpress_csv_export_selection');
		if ($checks['shared_export_contracts_available']) {
			$default = gigpress_csv_export_state(array());
			$invalid = gigpress_csv_export_state(array('artist_id' => '1 OR 1=1', 'tour_id' => '-2', 'scope' => 'all'));
			$checks['all_dates_minus_one_default'] = $default === array('artist_id' => -1, 'tour_id' => -1, 'scope' => '-1');
			$checks['invalid_filters_use_safe_defaults'] = $invalid === $default;
			$checks['all_scope_values_are_registered'] = array_map(function ($scope) { return gigpress_csv_export_state(array('scope' => $scope))['scope']; }, array('-1', 'upcoming', 'past')) === array('-1', 'upcoming', 'past');
		}
	}
	if ($case === 'export-literals') $checks['literal_case_pending'] = false;
	$failed = !$checks['selected_case'] || !$checks || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

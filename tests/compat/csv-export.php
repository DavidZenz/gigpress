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
			$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/import-records.php';
			foreach (array(GIGPRESS_SHOWS, GIGPRESS_ARTISTS, GIGPRESS_VENUES, GIGPRESS_TOURS) as $table) $wpdb->query('DELETE FROM ' . $table);
			$past = $fixture['base']; $past['Date'] = date('Y-m-d', strtotime(GIGPRESS_NOW . ' - 2 days')); $past['Artist'] = 'Export Artist A'; $past['Tour'] = 'Export Tour A';
			$soon = $fixture['base']; $soon['Date'] = date('Y-m-d', strtotime(GIGPRESS_NOW . ' + 2 days')); $soon['Artist'] = 'Export Artist A'; $soon['Tour'] = 'Export Tour A';
			$later = $fixture['base']; $later['Date'] = date('Y-m-d', strtotime(GIGPRESS_NOW . ' + 3 days')); $later['Artist'] = 'Export Artist B'; $later['Tour'] = 'Export Tour B';
			$created = array();
			foreach (array($past, $soon, $later) as $index => $row) $created[] = gigpress_csv_persist_record(gigpress_csv_validate_record($row, $index + 2, false));
			$wpdb->update(GIGPRESS_SHOWS, array('show_status' => 'deleted'), array('show_id' => (int) $created[2]['show_id']));
			$all = gigpress_csv_export_selection($default);
			$upcoming = gigpress_csv_export_selection(gigpress_csv_export_state(array('scope' => 'upcoming')));
			$pastOnly = gigpress_csv_export_selection(gigpress_csv_export_state(array('scope' => 'past')));
			$artistId = (int) $created[1]['artist_id']; $tourId = (int) $created[1]['tour_id'];
			$filtered = gigpress_csv_export_selection(gigpress_csv_export_state(array('artist_id' => (string) $artistId, 'tour_id' => (string) $tourId, 'scope' => 'upcoming')));
			$emptyState = gigpress_csv_export_state(array('artist_id' => '999999', 'tour_id' => (string) $tourId, 'scope' => 'upcoming'));
			$empty = gigpress_csv_export_selection($emptyState);
			$checks['scope_count_and_rows_share_predicate'] = $all['count'] === count($all['rows']) && $all['count'] === 2
				&& $upcoming['count'] === count($upcoming['rows']) && $upcoming['count'] === 1
				&& $pastOnly['count'] === count($pastOnly['rows']) && $pastOnly['count'] === 1
				&& $filtered['count'] === count($filtered['rows']) && $filtered['count'] === 1
				&& $empty['count'] === 0 && $empty['rows'] === array() && $empty['state'] === $emptyState;
			$checks['reset_action_alone_restores_defaults'] = gigpress_csv_export_state(array_merge($emptyState, array('reset_filters' => '1'))) === $default;
			wp_set_current_user($admin->ID);
			$_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'), 'artist_id' => '999999', 'tour_id' => (string) $tourId, 'scope' => 'upcoming'); $_REQUEST = $_POST;
			ob_start(); $emptyResult = gigpress_export(); $emptyOutput = ob_get_clean();
			$checks['empty_export_redirects_without_csv_bytes'] = $emptyResult === false && $emptyOutput === '' && !headers_sent();
			$_POST = array('_wpnonce' => 'invalid'); $_REQUEST = $_POST;
			ob_start(); $deniedResult = gigpress_export(); $deniedOutput = ob_get_clean();
			$checks['invalid_nonce_stops_before_export'] = $deniedResult === false && strpos($deniedOutput, 'not authorized') !== false;
		}
	}
	if ($case === 'export-literals') $checks['literal_case_pending'] = false;
	$failed = !$checks['selected_case'] || !$checks || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

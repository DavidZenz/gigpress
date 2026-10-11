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
	if ($case === 'export-literals') {
		$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/export-literals.php';
		$csv = function_exists('gigpress_csv_export_encode') ? gigpress_csv_export_encode($fixture['rows']) : '';
		$decoded = $csv !== '' ? gigpress_csv_decode($csv) : array();
		$checks['canonical_header_and_literal_round_trip'] = ($decoded['headers'] ?? array()) === gigpress_csv_columns()
			&& count($decoded['records'] ?? array()) === 3
			&& ($decoded['records'][0]['Notes'] ?? null) === $fixture['values']['formula_unicode']
			&& ($decoded['records'][0]['Venue phone'] ?? null) === $fixture['values']['venue_phone']
			&& ($decoded['records'][0]['Ticket phone'] ?? null) === $fixture['values']['ticket_phone'];
		$checks['distinct_optional_time_and_blank_end_date'] = ($decoded['records'][0]['Time'] ?? null) === ''
			&& ($decoded['records'][1]['Time'] ?? null) === '00:00:00'
			&& ($decoded['records'][0]['End date'] ?? null) === '';
		$checks['legacy_status_zero_remains_literal'] = ($decoded['records'][2]['Status'] ?? null) === '0';
		wp_set_current_user($admin->ID); $_POST = array();
		$wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => 'No Match Artist', 'artist_alpha' => 'No Match Artist'));
		$emptyArtist = (int) $wpdb->insert_id;
		$wpdb->insert(GIGPRESS_TOURS, array('tour_name' => 'No Match Tour', 'tour_status' => 'active'));
		$emptyTour = (int) $wpdb->insert_id;
		$_GET = array('artist_id' => (string) $emptyArtist, 'tour_id' => (string) $emptyTour, 'scope' => 'past', 'export_notice' => 'no_matches');
		ob_start(); gigpress_import_export(); $page = ob_get_clean();
		$importPos = strpos($page, 'gp-csv-import-heading'); $exportPos = strpos($page, 'gp-csv-export-heading');
		$checks['stacked_visible_sections_and_exact_controls'] = $importPos !== false && $exportPos !== false && $importPos < $exportPos
			&& strpos($page, 'Import shows') !== false && strpos($page, 'Download CSV') !== false
			&& preg_match('/<input[^>]+name="include_related"[^>]*>/', $page, $checkbox) === 1 && strpos($checkbox[0], 'checked') === false
			&& strpos($page, 'destination site') !== false;
		$checks['native_disclosures_and_bounded_spreadsheet_guidance'] = strpos($page, '<details') !== false && strpos($page, '<summary') !== false
			&& stripos($page, 'Data &gt; From Text/CSV') !== false && strpos($page, 'not universal') !== false;
		$checks['empty_selection_notice_and_artist_retained'] = strpos($page, 'No shows match these export filters') !== false
			&& preg_match('/<option value="' . $emptyArtist . '"[^>]*selected/', $page) === 1;
		$checks['tour_and_scope_retained_with_reset_action'] = preg_match('/<option value="' . $emptyTour . '"[^>]*selected/', $page) === 1
			&& preg_match('/<option value="past"[^>]*selected/', $page) === 1 && strpos($page, 'reset_filters=1') !== false;
		$checks['status_zero_manual_attention_visible'] = strpos($page, 'Status 0') !== false && strpos($page, 'manual attention') !== false;
		$result = array('counts' => array('imported' => 1, 'duplicate' => 1, 'rejected' => 1, 'failed' => 1, 'skipped' => 1), 'total' => 5,
			'records' => array('imported' => array(array('row' => 1, 'identity' => 'Imported — 2032-05-05 — Hall', 'reason' => 'Saved')),
				'duplicate' => array(array('row' => 2, 'identity' => 'A — 2032-05-06 — Hall', 'reason' => 'Already exists', 'next_step' => 'Review the existing show.')),
				'rejected' => array(array('row' => 3, 'identity' => 'B — 2032-05-07 — Club', 'field' => 'Date', 'reason' => 'Invalid date', 'next_step' => 'Use YYYY-MM-DD.')),
				'failed' => array(array('row' => 4, 'identity' => 'C — 2032-05-08 — Room', 'reason' => 'Database unavailable', 'next_step' => 'Retry after checking the database.')),
				'skipped' => array(array('row' => 5, 'identity' => 'D — 2032-05-09 — Hall', 'reason' => 'Matching show already exists', 'next_step' => 'Correct source CSV and retry.'))));
		$rendered = function_exists('gigpress_csv_render_import_result') ? gigpress_csv_render_import_result($result) : '';
		$checks['result_counts_map_skipped_once_to_duplicate'] = strpos($rendered, '1 imported') !== false && strpos($rendered, '2 duplicates') !== false
			&& strpos($rendered, '1 rejected') !== false && strpos($rendered, '1 failed') !== false && strpos($rendered, '1 skipped') === false;
		$checks['row_details_and_recovery_actions'] = strpos($rendered, 'Row 3') !== false && strpos($rendered, 'Date') !== false
			&& strpos($rendered, 'gigpress-shows') !== false && strpos($rendered, 'gp-import-file') !== false && strpos($rendered, 'not overwritten') !== false;
	}
	$failed = !$checks['selected_case'] || !$checks || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

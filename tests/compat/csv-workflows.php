<?php
/* CSV compatibility cases use fixture-owned expectations and fail closed. */

function gigpress_csv_workflows_case($case) {
	$registry = array('tracer-1.4', 'migrated-contracts', 'import-rows', 'import-retry', 'upload-guards', 'export-filters', 'export-literals', 'conversion-confirm', 'conversion-retry', 'all');
	$checks = array('selected_case' => in_array($case, $registry, true));
	if ($case === 'tracer-1.4') {
		global $wpdb, $admin, $gpo;
		require_once WP_PLUGIN_DIR . '/gigpress/lib/parsecsv.lib.php';
		require_once WP_PLUGIN_DIR . '/gigpress/admin/csv.php';
		require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
		$required = array('gigpress_csv_columns', 'gigpress_csv_statuses', 'gigpress_csv_prepare_record', 'gigpress_csv_persist_record', 'gigpress_csv_import_result');
		$checks['shared_csv_contracts'] = !array_filter($required, function ($name) { return !function_exists($name); });
		$expectedColumns = array('Date', 'Time', 'End date', 'Artist', 'Artist URL', 'Venue', 'Address', 'City', 'State', 'Postal code', 'Country', 'Venue phone', 'Venue URL', 'Admittance', 'Price', 'Ticket URL', 'Ticket phone', 'External URL', 'Notes', 'Tour', 'Status', 'Related ID', 'Related URL');
		$checks['exact_23_column_order'] = function_exists('gigpress_csv_columns') && gigpress_csv_columns() === $expectedColumns;
		$checks['fixture_manifest_available'] = function_exists('gigpress_upgrade_preservation_snapshot') && function_exists('gigpress_upgrade_preservation_manifest_matches');
		$source = $checks['fixture_manifest_available'] ? gigpress_upgrade_preservation_snapshot() : array();
		$showRows = array_values(array_filter($source['shows'] ?? array(), function ($row) { return (int) ($row['show_id'] ?? 0) === 109; }));
		$checks['migrated_source_identity'] = count($showRows) === 1 && ($showRows[0]['show_status'] ?? '') === 'active';
		$export = '';
		if ($checks['migrated_source_identity'] && function_exists('gigpress_export')) {
			wp_set_current_user($admin->ID);
			$_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'), 'scope' => '-1', 'artist_id' => '-1', 'tour_id' => '-1');
			$_REQUEST = $_POST;
			ob_start(); gigpress_export(); $export = ob_get_clean();
		}
		$parsed = new parseCSV();
		$parsed->parse($export);
		$exported = is_array($parsed->data) && isset($parsed->data[0]) ? $parsed->data[0] : array();
		$checks['export_headers_and_one_live_row'] = array_keys($exported) === $expectedColumns && count($parsed->data) === 1 && ($exported['Date'] ?? '') === '2031-04-05';
		$checks['literal_phone_round_trip'] = ($exported['Ticket phone'] ?? null) === '+43-1-555-0109' && ($exported['Venue phone'] ?? null) === '+43-1-555-0100';
		$blankOptional = $exported; $blankOptional['Time'] = ''; $blankOptional['End date'] = '';
		$blankPrepared = function_exists('gigpress_csv_prepare_record') ? gigpress_csv_prepare_record($blankOptional, 2, false) : array();
		$midnight = $exported; $midnight['Time'] = '00:00:00'; $midnightPrepared = function_exists('gigpress_csv_prepare_record') ? gigpress_csv_prepare_record($midnight, 2, false) : array();
		$relatedDenied = false;
		if ($checks['migrated_source_identity'] && !empty($exported['Related ID'])) {
			wp_set_current_user(0);
			$relatedDenied = gigpress_csv_prepare_record($exported, 2, true);
			wp_set_current_user($admin->ID);
		}
		$checks['optional_time_midnight_and_related_opt_in'] = ($blankPrepared['time'] ?? null) === '00:00:01'
			&& ($blankPrepared['end_date'] ?? null) === ($exported['Date'] ?? null) && ($blankPrepared['multi'] ?? null) === 0
			&& ($midnightPrepared['time'] ?? null) === '00:00:00' && (int) ($relatedDenied['related'] ?? 0) === 0;
		$checks['export_preserves_source_snapshot'] = $checks['migrated_source_identity'] && gigpress_upgrade_preservation_snapshot() === $source;
		require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
		$beforeGuards = gigpress_upgrade_preservation_snapshot();
		wp_set_current_user(0); $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array('gpaction' => 'import', '_wpnonce' => 'valid-looking'); $_REQUEST = $_POST;
		ob_start(); gigpress_import(); $unauthorizedOutput = ob_get_clean();
		wp_set_current_user($admin->ID); $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = array('gpaction' => 'import', '_wpnonce' => wp_create_nonce('gigpress-action')); $_REQUEST = $_POST;
		ob_start(); gigpress_import(); $methodOutput = ob_get_clean();
		$_SERVER['REQUEST_METHOD'] = 'POST'; $_POST['_wpnonce'] = 'invalid'; $_REQUEST = $_POST;
		ob_start(); gigpress_import(); $nonceOutput = ob_get_clean();
		$checks['import_capability_method_nonce_guards'] = strpos($unauthorizedOutput, 'not authorized') !== false
			&& strpos($methodOutput, 'not authorized') !== false && strpos($nonceOutput, 'expired') !== false
			&& gigpress_upgrade_preservation_snapshot() === $beforeGuards;
		wp_set_current_user($admin->ID); $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array(); $_REQUEST = array();
		$imported = array(); $retry = array(); $stored = null; $sameSite = false;
		if ($checks['export_headers_and_one_live_row'] && $checks['export_preserves_source_snapshot']) {
			foreach (array(GIGPRESS_SHOWS, GIGPRESS_ARTISTS, GIGPRESS_VENUES, GIGPRESS_TOURS) as $table) $wpdb->query('DELETE FROM ' . $table);
			$prepared = gigpress_csv_prepare_record($exported, 2, true);
			$imported = gigpress_csv_persist_record($prepared);
			$afterFirst = array('shows' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A), 'artists' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_ARTISTS . ' ORDER BY artist_id', ARRAY_A), 'venues' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_VENUES . ' ORDER BY venue_id', ARRAY_A), 'tours' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_TOURS . ' ORDER BY tour_id', ARRAY_A));
			$retry = gigpress_csv_persist_record($prepared);
			$afterRetry = array('shows' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A), 'artists' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_ARTISTS . ' ORDER BY artist_id', ARRAY_A), 'venues' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_VENUES . ' ORDER BY venue_id', ARRAY_A), 'tours' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_TOURS . ' ORDER BY tour_id', ARRAY_A));
			$stored = isset($afterRetry['shows'][0]) ? $afterRetry['shows'][0] : null;
			$sameSite = $afterFirst === $afterRetry;
		}
		$checks['fresh_destination_created_once'] = ($imported['group'] ?? '') === 'imported' && ($retry['group'] ?? '') === 'duplicate' && !empty($stored) && (int) $stored['show_id'] !== 109 && (int) $stored['show_artist_id'] !== 41 && (int) $stored['show_venue_id'] !== 73;
		$checks['duplicate_retry_is_unchanged'] = $sameSite && ($retry['show_id'] ?? 0) === ($imported['show_id'] ?? -1);
		$checks['text_status_and_relationship_readback'] = is_array($stored) && $stored['show_status'] === 'active' && (int) $stored['show_tour_id'] > 0 && (int) $stored['show_related'] === (int) ($exported['Related ID'] ?? 0);
		$checks['result_accounting_has_one_terminal_group'] = function_exists('gigpress_csv_import_result') && gigpress_csv_import_result(array($imported, $retry))['counts'] === array('imported' => 1, 'duplicate' => 1, 'rejected' => 0, 'failed' => 0);
	}
	if ($case === 'migrated-contracts') {
		require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/csv-migrated.php';
		return gigpress_csv_migrated_case($case);
	}
	if (in_array($case, array('import-rows', 'import-retry', 'upload-guards'), true)) {
		require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/csv-import.php';
		return gigpress_csv_import_case($case);
	}
	if (in_array($case, array('export-filters', 'export-literals'), true)) {
		require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/csv-export.php';
		return gigpress_csv_export_case($case);
	}
	if (in_array($case, array('conversion-confirm', 'conversion-retry'), true)) {
		require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/csv-conversion.php';
		return gigpress_csv_conversion_case($case);
	}
	$failed = !$checks['selected_case'] || !$checks || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

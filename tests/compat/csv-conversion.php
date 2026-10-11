<?php
/* Confirmed conversion assertions; every case owns and removes its fixture rows. */

function gigpress_csv_conversion_case($case) {
	global $wpdb, $admin;
	$checks = array('selected_case' => in_array($case, array('conversion-confirm', 'conversion-retry'), true));
	$required = array(
		'gigpress_tour_conversion_preview',
		'gigpress_tour_conversion_intent',
		'gigpress_tour_conversion_resolve_target',
		'gigpress_tour_conversion_confirm',
		'gigpress_tour_conversion_cancel',
	);
	$checks['conversion_contract_available'] = !array_filter($required, function ($name) { return !function_exists($name); });
	if (!$checks['conversion_contract_available']) return gigpress_csv_conversion_result($case, $checks);

	require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/conversion.php';
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/conversion.php';
	foreach (array(GIGPRESS_SHOWS, GIGPRESS_TOURS, GIGPRESS_ARTISTS) as $table) $wpdb->query('DELETE FROM ' . $table);
	$artistIds = array();
	foreach ($fixture['artists'] as $artist) {
		$wpdb->insert(GIGPRESS_ARTISTS, $artist);
		$artistIds[] = (int) $wpdb->insert_id;
	}
	$tourIds = array();
	foreach ($fixture['tours'] as $tour) {
		$wpdb->insert(GIGPRESS_TOURS, $tour);
		$tourIds[] = (int) $wpdb->insert_id;
	}
	$showIds = array();
	foreach (array(0, 1, 3) as $tourIndex) {
		foreach ($fixture['shows'] as $show) {
			$show['show_date'] = '2033-04-05';
			$show['show_time'] = '00:00:01';
			$show['show_expire'] = '2033-04-05';
			$show['show_multi'] = 0;
			$show['show_tour_id'] = $tourIds[$tourIndex];
			$show['show_venue_id'] = 0;
			$wpdb->insert(GIGPRESS_SHOWS, $show);
			$showIds[$tourIndex][] = (int) $wpdb->insert_id;
		}
	}
	wp_set_current_user($admin->ID);
	$before = gigpress_csv_conversion_snapshot();
	$preview = gigpress_tour_conversion_preview(array($tourIds[0], $tourIds[1], $tourIds[2]));
	$checks['complete_preview_includes_names_and_trashed_references'] = count($preview['tours'] ?? array()) === 3
		&& ($preview['tours'][0]['name'] ?? '') === 'Existing Exact Artist'
		&& ($preview['tours'][0]['active_count'] ?? -1) === 1 && ($preview['tours'][0]['trashed_count'] ?? -1) === 1;
	$checks['preview_is_read_only'] = gigpress_csv_conversion_snapshot() === $before;
	$unique = gigpress_tour_conversion_resolve_target('Existing Exact Artist', false);
	$missing = gigpress_tour_conversion_resolve_target('New Target Artist', false);
	$ambiguous = gigpress_tour_conversion_resolve_target('Ambiguous Artist', false);
	$checks['exact_one_reused_and_no_match_creates_once'] = ($unique['status'] ?? '') === 'reused'
		&& (int) ($unique['artist_id'] ?? 0) === $artistIds[0] && ($missing['status'] ?? '') === 'created'
		&& (int) ($missing['artist_id'] ?? 0) > 0
		&& (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'New Target Artist')) === 1;
	$afterCreate = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'New Target Artist'));
	$retryTarget = gigpress_tour_conversion_resolve_target('New Target Artist', false, (int) ($missing['artist_id'] ?? 0));
	$checks['ambiguous_exact_name_is_read_only_and_retry_reuses_target'] = ($ambiguous['status'] ?? '') === 'ambiguous'
		&& (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'Ambiguous Artist')) === 2
		&& $afterCreate === 1 && ($retryTarget['status'] ?? '') === 'reused'
		&& (int) ($retryTarget['artist_id'] ?? 0) === (int) ($missing['artist_id'] ?? 0);
	$intent = gigpress_tour_conversion_intent($preview);
	$checks['intent_is_server_owned_and_bound'] = !empty($intent['token']) && !empty($intent['fingerprint'])
		&& (int) ($intent['owner'] ?? 0) === get_current_user_id() && !empty($intent['expires']);
	$checks['cancel_and_invalid_intents_do_not_write'] = !empty($intent['token'])
		&& gigpress_tour_conversion_cancel($intent['token'], get_current_user_id())
		&& gigpress_csv_conversion_snapshot() === $before
		&& !gigpress_tour_conversion_confirm(str_repeat('a', 40), get_current_user_id());
	if ($case === 'conversion-retry') $checks = array_merge($checks, gigpress_csv_conversion_retry_checks($tourIds, $showIds));
	return gigpress_csv_conversion_result($case, $checks);
}

function gigpress_csv_conversion_snapshot() {
	global $wpdb;
	return array(
		'artists' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_ARTISTS . ' ORDER BY artist_id', ARRAY_A),
		'tours' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_TOURS . ' ORDER BY tour_id', ARRAY_A),
		'shows' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A),
	);
}

function gigpress_csv_conversion_retry_checks($tourIds, $showIds) {
	/* Fault-injection and ownership checks are added with the durable journal GREEN step. */
	return array('retry_fault_matrix' => false, 'intervening_reassignment_preserved' => false,
		'independent_tours_continue' => false, 'readback_and_journal_are_durable' => false,
		'journal_failure_stops_mutation' => false, 'source_retained_on_conflict' => false);
}

function gigpress_csv_conversion_result($case, $checks) {
	$failed = !$checks || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

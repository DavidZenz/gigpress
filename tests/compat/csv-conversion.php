<?php
/* Confirmed conversion assertions; every case owns and removes its fixture rows. */

function gigpress_csv_conversion_case($case) {
	global $wpdb, $admin;
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
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
	$missing = gigpress_tour_conversion_resolve_target('New Target Artist', true);
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
		&& (int) ($intent['owner'] ?? 0) === get_current_user_id() && !empty($intent['expires'])
		&& count($intent['tours'][0]['shows'] ?? array()) === 2
		&& (int) ($intent['tours'][0]['shows'][0]['original']['tour_id'] ?? 0) === $tourIds[0];
	$checks['altered_wrong_owner_and_expired_intents_rejected'] = gigpress_tour_conversion_get_intent($intent['token'], get_current_user_id() + 1) === false
		&& gigpress_tour_conversion_get_intent(str_repeat('f', 40), get_current_user_id()) === false;
	$expired = gigpress_tour_conversion_intent(gigpress_tour_conversion_preview(array($tourIds[3])));
	if (!empty($expired['token'])) { $expired['expires'] = time() - 1; set_transient(gigpress_tour_conversion_intent_key($expired['token']), $expired, 60); }
	$checks['expired_intent_rejected'] = !empty($expired['token']) && gigpress_tour_conversion_get_intent($expired['token'], get_current_user_id()) === false;
	$beforeCancel = gigpress_csv_conversion_snapshot();
	$cancelResult = !empty($intent['token']) && gigpress_tour_conversion_cancel($intent['token'], get_current_user_id());
	$checks['cancel_consumes_owned_intent'] = $cancelResult;
	$checks['cancel_preserves_tour_show_artist_tables'] = gigpress_csv_conversion_snapshot() === $beforeCancel;
	$invalidIntent = gigpress_tour_conversion_confirm(str_repeat('a', 40), get_current_user_id());
	$checks['invalid_intent_rejected'] = ($invalidIntent['status'] ?? '') === 'rejected';
	if (!empty($expired['token'])) delete_transient(gigpress_tour_conversion_intent_key($expired['token']));
	$singleIntent = gigpress_tour_conversion_intent(gigpress_tour_conversion_preview(array($tourIds[1])));
	$confirmed = !empty($singleIntent['token']) ? gigpress_tour_conversion_confirm($singleIntent['token'], get_current_user_id()) : array();
	$retryConfirm = !empty($singleIntent['token']) ? gigpress_tour_conversion_confirm($singleIntent['token'], get_current_user_id()) : array();
	$checks['explicit_confirm_completes_once'] = ($confirmed['status'] ?? '') === 'complete' && count($confirmed['completed'] ?? array()) === 1;
	$checks['confirmed_source_deleted_after_show_readback'] = !$wpdb->get_var($wpdb->prepare('SELECT tour_id FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $tourIds[1]))
		&& (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_tour_id = 0 AND show_artist_id = %d', $missing['artist_id'])) === 2;
	$checks['confirm_replay_rejected'] = ($retryConfirm['reason'] ?? '') === 'replayed_intent';
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
	global $wpdb;
	$checks = array();
	$faultCases = array('insert-before', 'insert-after', 'update-before', 'update-after', 'show-readback', 'delete-before', 'delete-after');
	$faultOutcomes = array();
	foreach ($faultCases as $faultStage) {
		$fixture = gigpress_csv_conversion_reset_fixture();
		$targetTourId = $fixture['tour_ids'][1];
		$intent = gigpress_tour_conversion_intent(gigpress_tour_conversion_preview(array($targetTourId)));
		$remainingFault = true;
		$GLOBALS['gigpress_tour_conversion_test_fault'] = function ($stage, $tour_id, $show_id) use ($faultStage, $targetTourId, &$remainingFault) {
			if ($remainingFault && $stage === $faultStage && $tour_id === $targetTourId) { $remainingFault = false; return true; }
			return false;
		};
		$first = gigpress_tour_conversion_confirm($intent['token'], get_current_user_id());
		unset($GLOBALS['gigpress_tour_conversion_test_fault']);
		$retry = gigpress_tour_conversion_confirm($intent['token'], get_current_user_id());
		$targetCount = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'New Target Artist'));
		$sourceCount = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $targetTourId));
		$showCount = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_tour_id = 0 AND show_artist_id = (SELECT artist_id FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s)', 'New Target Artist'));
		$faultOutcomes[$faultStage] = array('first' => $first, 'retry' => $retry, 'targets' => $targetCount, 'sources' => $sourceCount, 'shows' => $showCount,
			'journal' => gigpress_tour_conversion_journal_read());
	}
	$checks['early_and_late_insert_update_readback_delete_faults_converge'] = count($faultOutcomes) === 7;
	foreach ($faultOutcomes as $stage => $outcome) {
		$checks['retry_converges_' . $stage] = ($outcome['retry']['status'] ?? '') === 'complete'
			&& $outcome['targets'] === 1 && $outcome['sources'] === 0 && $outcome['shows'] === 2;
	}

	$fixture = gigpress_csv_conversion_reset_fixture();
	$batch = gigpress_tour_conversion_intent(gigpress_tour_conversion_preview(array($fixture['tour_ids'][0], $fixture['tour_ids'][2], $fixture['tour_ids'][3])));
	$batchResult = gigpress_tour_conversion_confirm($batch['token'], get_current_user_id());
	$checks['independent_tours_continue_after_ambiguity'] = ($batchResult['status'] ?? '') === 'partial'
		&& count($batchResult['completed'] ?? array()) === 2 && count($batchResult['failed'] ?? array()) === 1
		&& (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $fixture['tour_ids'][2])) === 1
		&& (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $fixture['tour_ids'][3])) === 0;

	$fixture = gigpress_csv_conversion_reset_fixture();
	$targetTourId = $fixture['tour_ids'][1];
	$ownedShowId = $fixture['show_ids'][1][0];
	$intent = gigpress_tour_conversion_intent(gigpress_tour_conversion_preview(array($targetTourId)));
	$GLOBALS['gigpress_tour_conversion_test_fault'] = function ($stage, $tour_id, $show_id) use ($targetTourId, $ownedShowId) {
		return $stage === 'update-before' && $tour_id === $targetTourId && $show_id === $ownedShowId;
	};
	$partial = gigpress_tour_conversion_confirm($intent['token'], get_current_user_id());
	unset($GLOBALS['gigpress_tour_conversion_test_fault']);
	$otherRelation = array('show_artist_id' => $fixture['artist_ids'][0], 'show_tour_id' => $fixture['tour_ids'][0]);
	$wpdb->update(GIGPRESS_SHOWS, $otherRelation, array('show_id' => $ownedShowId), array('%d', '%d'), array('%d'));
	$retry = gigpress_tour_conversion_confirm($intent['token'], get_current_user_id());
	$actual = gigpress_tour_conversion_current_show($ownedShowId);
	$source = $wpdb->get_var($wpdb->prepare('SELECT tour_id FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $targetTourId));
	$checks['intervening_show_reassignment_is_preserved_and_source_retained'] = ($partial['status'] ?? '') === 'partial'
		&& ($retry['status'] ?? '') === 'partial' && $actual === array('artist_id' => $fixture['artist_ids'][0], 'tour_id' => $fixture['tour_ids'][0])
		&& (int) $source === $targetTourId
		&& (($retry['failed'][0]['shows'][$ownedShowId]['ownership'] ?? '') === 'conflict');

	$fixture = gigpress_csv_conversion_reset_fixture();
	$targetTourId = $fixture['tour_ids'][1];
	$intent = gigpress_tour_conversion_intent(gigpress_tour_conversion_preview(array($targetTourId)));
	$GLOBALS['gigpress_tour_conversion_test_fault'] = function ($stage, $tour_id, $show_id) use ($targetTourId) { return $stage === 'journal-before' && $tour_id === $targetTourId; };
	$failed = gigpress_tour_conversion_confirm($intent['token'], get_current_user_id());
	unset($GLOBALS['gigpress_tour_conversion_test_fault']);
	$mutatedShows = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_tour_id = 0 AND show_id IN (%d, %d)', $fixture['show_ids'][1][0], $fixture['show_ids'][1][1]));
	$retry = gigpress_tour_conversion_confirm($intent['token'], get_current_user_id());
	$checks['journal_failure_stops_before_mutation_and_retry_recovers'] = ($failed['status'] ?? '') === 'partial'
		&& $mutatedShows === 0 && ($retry['status'] ?? '') === 'complete';
	$checks['journal_persists_per_show_readback_and_terminal_stage'] = count(gigpress_tour_conversion_journal_read()) === 1
		&& count(gigpress_tour_conversion_journal_read()[gigpress_tour_conversion_journal_key($intent['token'], $targetTourId)]['shows'] ?? array()) === 2
		&& (gigpress_tour_conversion_journal_read()[gigpress_tour_conversion_journal_key($intent['token'], $targetTourId)]['stage'] ?? '') === 'complete';
	return $checks;
}

function gigpress_csv_conversion_reset_fixture() {
	global $wpdb;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/conversion.php';
	foreach (array(GIGPRESS_SHOWS, GIGPRESS_TOURS, GIGPRESS_ARTISTS) as $table) $wpdb->query('DELETE FROM ' . $table);
	delete_option('gigpress_tour_conversion_journal');
	$artistIds = array();
	foreach ($fixture['artists'] as $artist) { $wpdb->insert(GIGPRESS_ARTISTS, $artist); $artistIds[] = (int) $wpdb->insert_id; }
	$tourIds = array();
	foreach ($fixture['tours'] as $tour) { $wpdb->insert(GIGPRESS_TOURS, $tour); $tourIds[] = (int) $wpdb->insert_id; }
	$showIds = array();
	foreach (array(0, 1, 3) as $tourIndex) foreach ($fixture['shows'] as $show) {
		$show = array_merge(array('show_date' => '2033-04-05', 'show_time' => '00:00:01', 'show_expire' => '2033-04-05', 'show_multi' => 0), $show);
		$show['show_tour_id'] = $tourIds[$tourIndex]; $wpdb->insert(GIGPRESS_SHOWS, $show); $showIds[$tourIndex][] = (int) $wpdb->insert_id;
	}
	return array('artist_ids' => $artistIds, 'tour_ids' => $tourIds, 'show_ids' => $showIds);
}

function gigpress_csv_conversion_result($case, $checks) {
	$failed = !$checks || in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

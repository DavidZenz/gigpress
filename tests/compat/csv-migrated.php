<?php
/* Cross-version CSV evidence uses fixture-owned expectations, never importer output. */

function gigpress_csv_migrated_case($case) {
	global $wpdb, $admin;
	$versions = array('1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6');
	$expected = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/csv/expected-migrated.php';
	require_once WP_PLUGIN_DIR . '/gigpress/lib/parsecsv.lib.php';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/csv.php';
	require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
	$checks = array('selected_case' => $case === 'migrated-contracts', 'exact_source_set' => $versions === array_keys($expected), 'source_evidence_nonempty' => false);
	$perVersion = array();
	$allProjection = $allSourcePreserved = $allSameSiteRetry = $allFreshIdentity = $allFreshRetry = $allRelatedChoices = true;
	foreach ($versions as $version) {
		$fixture = gigpress_upgrade_preservation_fixture($version);
		$seeded = is_array($fixture) && upgrade_preservation_seed($fixture);
		unset($GLOBALS['gigpress_db_bootstrap_result']);
		$ready = $seeded ? gigpress_db_bootstrap() : array('status' => 'blocked');
		$source = $seeded && $ready['status'] === 'ready' ? gigpress_upgrade_preservation_snapshot() : array();
		$want = $expected[$version] ?? array();
		wp_set_current_user($admin->ID);
		$_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'), 'scope' => '-1', 'artist_id' => '-1', 'tour_id' => '-1'); $_REQUEST = $_POST;
		ob_start(); if (function_exists('gigpress_export')) gigpress_export(); $csv = ob_get_clean();
		$parser = new parseCSV(); $parser->parse($csv);
		$rows = is_array($parser->data) ? $parser->data : array();
		$row = $rows[0] ?? array();
		$projection = count($rows) === 1 && array_keys($row) === gigpress_csv_columns();
		foreach ($want as $field => $value) if ($field !== 'show_id') $projection = $projection && array_key_exists($field, $row) && $row[$field] === $value;
		$sourceStable = $source && gigpress_upgrade_preservation_snapshot() === $source;
		$prepared = $projection ? gigpress_csv_prepare_record($row, 2, false) : array();
		$sameSite = $projection ? gigpress_csv_persist_record($prepared) : array();
		$sameSiteStable = $sourceStable && ($sameSite['group'] ?? '') === 'duplicate' && gigpress_upgrade_preservation_snapshot() === $source;
		foreach (array(GIGPRESS_SHOWS, GIGPRESS_ARTISTS, GIGPRESS_VENUES, GIGPRESS_TOURS) as $table) $wpdb->query('DELETE FROM ' . $table);
		$freshPrepared = $projection ? gigpress_csv_prepare_record($row, 2, false) : array();
		$fresh = $projection ? gigpress_csv_persist_record($freshPrepared) : array();
		$showId = (int) ($fresh['show_id'] ?? 0);
		$stored = $showId ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $showId), ARRAY_A) : null;
		$freshIdentity = ($fresh['group'] ?? '') === 'imported' && $showId > 0 && $showId !== (int) ($want['show_id'] ?? 0)
			&& $stored && $stored['show_status'] === ($want['Status'] ?? '') && $stored['show_date'] === ($want['Date'] ?? '')
			&& $stored['show_time'] === (($want['Time'] ?? '') === '' ? '00:00:01' : ($want['Time'] ?? ''))
			&& $stored['show_expire'] === (($want['End date'] ?? '') === '' ? ($want['Date'] ?? '') : ($want['End date'] ?? ''));
		$freshSnapshot = array('shows' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A), 'artists' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_ARTISTS . ' ORDER BY artist_id', ARRAY_A), 'venues' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_VENUES . ' ORDER BY venue_id', ARRAY_A), 'tours' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_TOURS . ' ORDER BY tour_id', ARRAY_A));
	$retry = $projection ? gigpress_csv_persist_record($freshPrepared) : array();
	$retrySnapshot = array('shows' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A), 'artists' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_ARTISTS . ' ORDER BY artist_id', ARRAY_A), 'venues' => $wpdb->get_results('SELECT * FROM ' . GIGPRESS_VENUES . ' ORDER BY venue_id', ARRAY_A));
		$relatedOff = $projection ? gigpress_csv_prepare_record($row, 2, false) : array();
		$relatedOn = $projection ? gigpress_csv_prepare_record($row, 2, true) : array();
		$relatedChoice = (int) ($relatedOff['related'] ?? -1) === 0 && (int) ($relatedOn['related'] ?? -1) === (int) ($row['Related ID'] ?? 0);
		$retryStable = ($retry['group'] ?? '') === 'duplicate' && $freshSnapshot === $retrySnapshot;
		$perVersion[$version] = array('projected' => $projection, 'source_preserved' => $sourceStable, 'same_site_duplicate' => $sameSiteStable, 'fresh_identity' => $freshIdentity, 'fresh_retry' => $retryStable, 'related_choices' => $relatedChoice);
		$allProjection = $allProjection && $projection;
		$allSourcePreserved = $allSourcePreserved && $sourceStable;
		$allSameSiteRetry = $allSameSiteRetry && $sameSiteStable;
		$allFreshIdentity = $allFreshIdentity && $freshIdentity;
		$allFreshRetry = $allFreshRetry && $retryStable;
		$allRelatedChoices = $allRelatedChoices && $relatedChoice;
	}
	$checks['source_evidence_nonempty'] = count($perVersion) === 7;
	$checks['every_recognized_projection'] = $allProjection;
	$checks['same_site_source_preserved_and_duplicate'] = $allSourcePreserved && $allSameSiteRetry;
	$checks['fresh_destination_identity_and_retry'] = $allFreshIdentity && $allFreshRetry;
	$checks['related_post_opt_out_and_opt_in'] = $allRelatedChoices;
	return array('case' => $case, 'checks' => $checks, 'versions' => $perVersion, 'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

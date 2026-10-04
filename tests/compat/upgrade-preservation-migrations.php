<?php
/* Matrix cases share the probe's disposable database and failure result shape. */
function gigpress_upgrade_preservation_fixture($version) {
    $path = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/' . $version . '.php';
    return is_readable($path) ? require $path : null;
}

function gigpress_upgrade_preservation_snapshot() {
    global $wpdb;
    $data = array('prefix' => $wpdb->prefix, 'settings' => get_option('gigpress_settings'));
    foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $kind => $id) {
        $data[$kind] = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gigpress_' . $kind . ' ORDER BY ' . $id, ARRAY_A);
    }
    return $data;
}

function gigpress_upgrade_preservation_manifest_matches($fixture, $snapshot) {
    $expected = $fixture['expected'];
    $ids = function ($rows, $key) { return array_map('intval', array_column($rows, $key)); };
    $checks = array(
        'prefix' => $snapshot['prefix'] === $fixture['prefix'],
        'version' => ($snapshot['settings']['db_version'] ?? null) === $expected['version'],
        'show_ids' => $ids($snapshot['shows'], 'show_id') === $expected['show_ids'],
        'artist_ids' => $ids($snapshot['artists'], 'artist_id') === $expected['artist_ids'],
        'venue_ids' => $ids($snapshot['venues'], 'venue_id') === $expected['venue_ids'],
        'tour_ids' => $ids($snapshot['tours'], 'tour_id') === $expected['tour_ids'],
        'artist_alpha' => ($snapshot['artists'][0]['artist_alpha'] ?? null) === $expected['artist_alpha'],
        'venue_city' => ($snapshot['venues'][0]['venue_city'] ?? null) === $expected['venue_city'],
        'venue_state' => ($snapshot['venues'][0]['venue_state'] ?? null) === $expected['venue_state'],
        'journal_removed' => !get_option('gigpress_upgrade_state', false),
    );
    foreach ($expected['settings'] as $key => $value) $checks['setting_' . $key] = array_key_exists($key, $snapshot['settings']) && $snapshot['settings'][$key] === $value;
    return array($checks, !in_array(false, $checks, true));
}

function gigpress_upgrade_preservation_run_versions($versions) {
    global $upgrade_preservation_seed, $upgradeFailurePoint;
    $passes = true; $details = array();
    foreach ($versions as $version) {
        $fixture = gigpress_upgrade_preservation_fixture($version);
        $passes = $passes && is_array($fixture) && upgrade_preservation_seed($fixture);
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $ready = gigpress_db_bootstrap();
        $first = gigpress_upgrade_preservation_snapshot();
        list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $first);
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $repeat = gigpress_db_bootstrap();
        $second = gigpress_upgrade_preservation_snapshot();
        $passes = $passes && $ready['status'] === 'ready' && $repeat['status'] === 'ready' && $matches && $first === $second;
        $details[$version] = array('checks' => $checks, 'repeat' => $first === $second);
    }
    return array('status' => $passes ? 'PASS' : 'FAIL', 'fixtures' => $details);
}

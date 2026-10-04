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
	$data['linked_posts'] = array();
	foreach ($data['shows'] as $show) {
		if ((int) $show['show_related'] > 0) {
			$post = get_post((int) $show['show_related'], ARRAY_A);
			$data['linked_posts'][(int) $show['show_id']] = $post ? array('ID' => (int) $post['ID'], 'post_title' => $post['post_title'], 'post_content' => $post['post_content']) : null;
		}
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
    foreach ((array) ($expected['settings'] ?? array()) as $key => $value) $checks['setting_' . $key] = array_key_exists($key, $snapshot['settings']) && $snapshot['settings'][$key] === $value;
    return array($checks, !in_array(false, $checks, true));
}

function gigpress_upgrade_preservation_retry_after_artist_insert_title_change() {
	global $upgrade_preservation_seed, $upgradeFailurePoint, $wpdb;
	$fixture = gigpress_upgrade_preservation_fixture('1.3');
	if (!is_array($fixture)) return array('passed' => false, 'reason' => 'fixture unavailable');
	unset($fixture['settings']['band']);
	$originalTitle = get_option('blogname');
	update_option('blogname', 'Original retry title');
	$seeded = upgrade_preservation_seed($fixture);
	$upgradeFailurePoint = 'after_artist_insert';
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$blocked = gigpress_db_bootstrap();
	$created = $wpdb->get_row($wpdb->prepare('SELECT artist_id, artist_name FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'Original retry title'), ARRAY_A);
	update_option('blogname', 'Changed retry title');
	$upgradeFailurePoint = null;
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$retry = gigpress_db_bootstrap();
	$artistCount = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . GIGPRESS_ARTISTS);
	$changedCount = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'Changed retry title'));
	$showArtistIds = array_unique(array_map('intval', (array) $wpdb->get_col('SELECT show_artist_id FROM ' . GIGPRESS_SHOWS)));
	update_option('blogname', $originalTitle);
	$passed = $seeded
		&& $blocked['status'] === 'blocked'
		&& is_array($created)
		&& $retry['status'] === 'ready'
		&& $artistCount === 1
		&& $changedCount === 0
		&& $showArtistIds === array((int) $created['artist_id']);
	return array('passed' => $passed, 'blocked' => $blocked, 'retry' => $retry, 'artist_count' => $artistCount, 'changed_count' => $changedCount, 'show_artist_ids' => $showArtistIds);
}

function gigpress_upgrade_preservation_run_versions($versions) {
    global $upgrade_preservation_seed, $upgradeFailurePoint;
    $passes = true; $details = array();
    foreach ($versions as $version) {
        $fixture = gigpress_upgrade_preservation_fixture($version);
        $seeded = is_array($fixture) && upgrade_preservation_seed($fixture);
        $passes = $passes && $seeded;
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $ready = gigpress_db_bootstrap();
        $first = gigpress_upgrade_preservation_snapshot();
        list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $first);
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $repeat = gigpress_db_bootstrap();
        $second = gigpress_upgrade_preservation_snapshot();
		$failurePoints = array('after_schema');
		$stepFailures = array(
			'1.0' => array('after_upgrade_110', 'after_upgrade_120', 'after_upgrade_130', 'after_upgrade_140', 'after_upgrade_160'),
			'1.1' => array('after_upgrade_120', 'after_upgrade_130', 'after_upgrade_140', 'after_upgrade_160'),
			'1.2' => array('after_upgrade_130', 'after_upgrade_140', 'after_upgrade_160'),
			'1.3' => array('before_artist_insert', 'after_artist_insert', 'before_artist_relationship', 'after_artist_relationship', 'before_venue_insert', 'after_venue_insert', 'before_venue_relationship', 'after_venue_relationship', 'after_upgrade_140', 'after_upgrade_160'),
			'1.5' => array('after_upgrade_160'),
		);
		foreach ($stepFailures[$version] as $point) $failurePoints[] = $point;
		$failurePoints[] = 'before_final_marker';
		$retries = true; $retryFailures = array();
		foreach ($failurePoints as $point) {
			$retries = $retries && upgrade_preservation_seed($fixture);
			$upgradeFailurePoint = $point;
			unset($GLOBALS['gigpress_db_bootstrap_result']);
			$blocked = gigpress_db_bootstrap();
			$during = gigpress_upgrade_preservation_snapshot();
			$markerSafe = ($during['settings']['db_version'] ?? null) === $version;
			$journalPresent = is_array(get_option('gigpress_upgrade_state', false));
			$upgradeFailurePoint = null;
			unset($GLOBALS['gigpress_db_bootstrap_result']);
			$retry = gigpress_db_bootstrap();
			$afterRetry = gigpress_upgrade_preservation_snapshot();
			list($retryChecks, $retryMatches) = gigpress_upgrade_preservation_manifest_matches($fixture, $afterRetry);
			$pointPass = $blocked['status'] === 'blocked' && $markerSafe && $journalPresent && $retry['status'] === 'ready' && $retryMatches;
			if (!$pointPass) $retryFailures[$point] = array('blocked' => $blocked, 'marker_safe' => $markerSafe, 'journal_present' => $journalPresent, 'retry' => $retry, 'manifest' => $retryChecks);
			$retries = $retries && $pointPass;
		}
        $passes = $passes && $ready['status'] === 'ready' && $repeat['status'] === 'ready' && $matches && $first === $second && $retries;
        $details[$version] = array('checks' => $checks, 'repeat' => $first === $second, 'retries' => $retries, 'retry_failures' => $retryFailures, 'seeded' => $seeded, 'actual_show_ids' => array_map('intval', array_column($first['shows'], 'show_id')));
    }
	$titleChange = in_array('1.3', $versions, true) ? gigpress_upgrade_preservation_retry_after_artist_insert_title_change() : null;
	$passes = $passes && ($titleChange === null || $titleChange['passed']);
	return array('status' => $passes ? 'PASS' : 'FAIL', 'fixtures' => $details, 'artist_retry_title_change' => $titleChange);
}

function gigpress_upgrade_preservation_run_current() {
	global $upgrade_preservation_seed;
	$fixture = gigpress_upgrade_preservation_fixture('1.6');
	if (!is_array($fixture) || !upgrade_preservation_seed($fixture)) return array('status' => 'FAIL');
	$before = gigpress_upgrade_preservation_snapshot();
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$first = gigpress_db_bootstrap();
	$after = gigpress_upgrade_preservation_snapshot();
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$second = gigpress_db_bootstrap();
	$repeat = gigpress_upgrade_preservation_snapshot();
	list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $after);
	$ok = $first['status'] === 'ready' && $first['code'] === 'current' && $second['code'] === 'current' && $before === $after && $after === $repeat && $matches && !get_option('gigpress_upgrade_state', false);
	return array('status' => $ok ? 'PASS' : 'FAIL', 'checks' => $checks, 'unchanged' => $before === $after, 'repeat' => $after === $repeat, 'journal_absent' => !get_option('gigpress_upgrade_state', false));
}

function gigpress_upgrade_preservation_run_settings_repeat() {
	global $upgrade_preservation_seed;
	$passes = true; $fixtures = array();
	foreach (array('1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6') as $version) {
		$fixture = gigpress_upgrade_preservation_fixture($version);
		$seeded = is_array($fixture) && upgrade_preservation_seed($fixture);
		unset($GLOBALS['gigpress_db_bootstrap_result']);
		$first = gigpress_db_bootstrap();
		$after = gigpress_upgrade_preservation_snapshot();
		unset($GLOBALS['gigpress_db_bootstrap_result']);
		$second = gigpress_db_bootstrap();
		$repeat = gigpress_upgrade_preservation_snapshot();
		list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $after);
		$stable = $after === $repeat;
		$passes = $passes && $seeded && $first['status'] === 'ready' && $second['status'] === 'ready' && $matches && $stable;
		$fixtures[$version] = array('checks' => $checks, 'repeat' => $stable);
	}
	return array('status' => $passes ? 'PASS' : 'FAIL', 'fixtures' => $fixtures);
}

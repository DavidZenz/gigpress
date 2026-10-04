<?php
/* Matrix cases share the probe's disposable database and failure result shape. */
function gigpress_upgrade_preservation_fixture($version) {
    $path = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/' . $version . '.php';
    return is_readable($path) ? require $path : null;
}

function gigpress_upgrade_preservation_schema_columns($columns) {
	$fields = array('Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
	$definitions = array();
	foreach ((array) $columns as $column) {
		$definition = array();
		foreach ($fields as $field) $definition[$field] = array_key_exists($field, $column) ? $column[$field] : null;
		$definitions[$definition['Field']] = $definition;
	}
	ksort($definitions);
	return $definitions;
}

function gigpress_upgrade_preservation_schema_matches($actual, $expected) {
	return gigpress_upgrade_preservation_schema_columns($actual) === gigpress_upgrade_preservation_schema_columns($expected);
}

function gigpress_upgrade_preservation_snapshot() {
    global $wpdb;
    $data = array('prefix' => $wpdb->prefix, 'settings' => get_option('gigpress_settings'));
    foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $kind => $id) {
        $data[$kind] = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gigpress_' . $kind . ' ORDER BY ' . $id, ARRAY_A);
		$data['schema'][$kind] = gigpress_upgrade_preservation_schema_columns($wpdb->get_results('SHOW COLUMNS FROM ' . $wpdb->prefix . 'gigpress_' . $kind, ARRAY_A));
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

function gigpress_upgrade_preservation_expected_value($kind, $field, $value, $row) {
	if ($kind === 'shows' && $field === 'show_expire' && ($row['show_multi'] ?? null) === null) return $row['show_date'];
	if ($kind === 'shows' && $field === 'show_time' && ($value === '' || $value === '00:00:00')) return '00:00:01';
	if (($kind === 'shows' && $field === 'show_status') || ($kind === 'tours' && $field === 'tour_status')) return $value === '' ? 'active' : $value;
	if ($kind === 'venues' && $field === 'venue_city' && preg_match('/,[ ]?([A-Z]{2})$/u', $value)) return preg_replace('/,[ ]?[A-Z]{2}$/u', '', $value);
	if ($kind === 'venues' && $field === 'venue_state' && $value === '' && preg_match('/,[ ]?([A-Z]{2})$/u', $row['venue_city'] ?? '', $matches)) return $matches[1];
	return $value;
}

function gigpress_upgrade_preservation_rows_match($source, $snapshot) {
	$ids = array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id');
	foreach ($ids as $kind => $id) {
		$actual = array();
		foreach ($snapshot[$kind] as $row) $actual[(int) $row[$id]] = $row;
		foreach ($source[$kind] as $row) {
			$key = (int) $row[$id];
			if (!isset($actual[$key])) return false;
			foreach ($row as $field => $value) {
				if ($kind === 'shows' && in_array($field, array('show_artist_id', 'show_venue_id'), true)) continue;
				$expected = gigpress_upgrade_preservation_expected_value($kind, $field, $value, $row);
				if (!array_key_exists($field, $actual[$key]) || (string) $actual[$key][$field] !== (string) $expected) return false;
			}
		}
	}
	return true;
}

function gigpress_upgrade_preservation_relationships_match($source, $snapshot) {
	$artists = array(); $venues = array(); $tours = array(); $defaultArtists = array();
	foreach ($snapshot['artists'] as $row) $artists[(int) $row['artist_id']] = $row;
	foreach ($snapshot['venues'] as $row) $venues[(int) $row['venue_id']] = $row;
	foreach ($snapshot['tours'] as $row) $tours[(int) $row['tour_id']] = $row;
	$shows = array(); foreach ($snapshot['shows'] as $row) $shows[(int) $row['show_id']] = $row;
	foreach ($source['shows'] as $sourceShow) {
		$show = $shows[(int) $sourceShow['show_id']] ?? null;
		if (!$show || !isset($artists[(int) $show['show_artist_id']], $venues[(int) $show['show_venue_id']])) return false;
		if ((int) $sourceShow['show_tour_id'] > 0 && ((int) $show['show_tour_id'] !== (int) $sourceShow['show_tour_id'] || !isset($tours[(int) $show['show_tour_id']]))) return false;
		if ((int) $sourceShow['show_artist_id'] > 0 && (int) $show['show_artist_id'] !== (int) $sourceShow['show_artist_id']) return false;
		if ((int) $sourceShow['show_artist_id'] === 0) $defaultArtists[] = (int) $show['show_artist_id'];
		if ((int) $sourceShow['show_venue_id'] > 0 && (int) $show['show_venue_id'] !== (int) $sourceShow['show_venue_id']) return false;
		if ((int) $sourceShow['show_venue_id'] === 0) {
			$venue = $venues[(int) $show['show_venue_id']];
			if ($venue['venue_name'] !== $sourceShow['show_venue'] || $venue['venue_address'] !== $sourceShow['show_address'] || $venue['venue_city'] !== gigpress_upgrade_preservation_expected_value('venues', 'venue_city', $sourceShow['show_locale'], array('venue_city' => $sourceShow['show_locale'])) || $venue['venue_country'] !== $sourceShow['show_country'] || $venue['venue_phone'] !== $sourceShow['show_venue_phone'] || $venue['venue_url'] !== $sourceShow['show_venue_url']) return false;
		}
	}
	return !$defaultArtists || count(array_unique($defaultArtists)) === 1;
}

function gigpress_upgrade_preservation_manifest_matches($fixture, $snapshot, $source = null) {
    $expected = $fixture['expected'];
    $ids = function ($rows, $key) { return array_map('intval', array_column($rows, $key)); };
    $currentSchema = upgrade_preservation_current_schema();
	$schemaMatches = true;
	foreach ($currentSchema as $kind => $columns) {
		$schemaMatches = $schemaMatches && gigpress_upgrade_preservation_schema_matches($snapshot['schema'][$kind] ?? array(), $columns);
	}
	$wrongDefinition = $snapshot['schema'];
	foreach ($wrongDefinition['shows'] ?? array() as $index => $column) {
		if ($column['Field'] === 'show_tour_id') {
			$wrongDefinition['shows'][$index]['Type'] = 'varchar(255)';
			$wrongDefinition['shows'][$index]['Default'] = '';
			break;
		}
	}
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
		'schema' => $schemaMatches,
		'schema_rejects_wrong_type_default' => !gigpress_upgrade_preservation_schema_matches($wrongDefinition['shows'] ?? array(), $currentSchema['shows']),
	);
	if ($source !== null) {
		$checks['source_values'] = gigpress_upgrade_preservation_rows_match($source, $snapshot);
		$checks['relationships'] = gigpress_upgrade_preservation_relationships_match($source, $snapshot);
		$checks['linked_posts'] = $source['linked_posts'] === $snapshot['linked_posts'];
	}
	foreach ($snapshot['artists'] as $artist) $checks['artist_alpha_' . $artist['artist_id']] = $artist['artist_alpha'] === preg_replace('/^the\s+/ui', '', strtolower($artist['artist_name']));
	foreach ($snapshot['venues'] as $venue) $checks['venue_state_' . $venue['venue_id']] = $venue['venue_state'] === gigpress_upgrade_preservation_expected_value('venues', 'venue_state', $venue['venue_state'], $venue);
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
		$source = $seeded ? gigpress_upgrade_preservation_snapshot() : null;
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $ready = gigpress_db_bootstrap();
        $first = gigpress_upgrade_preservation_snapshot();
        list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $first, $source);
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
			$retrySource = $retries ? gigpress_upgrade_preservation_snapshot() : null;
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
			list($retryChecks, $retryMatches) = gigpress_upgrade_preservation_manifest_matches($fixture, $afterRetry, $retrySource);
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
	list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $after, $before);
	$ok = $first['status'] === 'ready' && $first['code'] === 'current' && $second['code'] === 'current' && $before === $after && $after === $repeat && $matches && !get_option('gigpress_upgrade_state', false);
	return array('status' => $ok ? 'PASS' : 'FAIL', 'checks' => $checks, 'unchanged' => $before === $after, 'repeat' => $after === $repeat, 'journal_absent' => !get_option('gigpress_upgrade_state', false));
}

function gigpress_upgrade_preservation_run_settings_repeat() {
	global $upgrade_preservation_seed;
	$passes = true; $fixtures = array();
	foreach (array('1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6') as $version) {
		$fixture = gigpress_upgrade_preservation_fixture($version);
		$seeded = is_array($fixture) && upgrade_preservation_seed($fixture);
		$source = $seeded ? gigpress_upgrade_preservation_snapshot() : null;
		unset($GLOBALS['gigpress_db_bootstrap_result']);
		$first = gigpress_db_bootstrap();
		$after = gigpress_upgrade_preservation_snapshot();
		unset($GLOBALS['gigpress_db_bootstrap_result']);
		$second = gigpress_db_bootstrap();
		$repeat = gigpress_upgrade_preservation_snapshot();
		list($checks, $matches) = gigpress_upgrade_preservation_manifest_matches($fixture, $after, $source);
		$stable = $after === $repeat;
		$passes = $passes && $seeded && $first['status'] === 'ready' && $second['status'] === 'ready' && $matches && $stable;
		$fixtures[$version] = array('checks' => $checks, 'repeat' => $stable);
	}
	return array('status' => $passes ? 'PASS' : 'FAIL', 'fixtures' => $fixtures);
}

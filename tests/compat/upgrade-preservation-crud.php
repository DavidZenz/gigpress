<?php
/* Mutation coverage for the reconstructed upgraded 1.4 fixture. */

function gigpress_upgrade_preservation_request($handler, $request, $files = array(), &$outcome = null) {
	if ($handler === 'gigpress_delete_show') return gigpress_upgrade_preservation_confirmed_trash($request, $outcome);
	$nonce = wp_create_nonce('gigpress-action');
	$_GET = $request;
	$_POST = $request;
	$_FILES = $files;
	$_REQUEST = array_merge($request, array('_wpnonce' => $nonce));
	$_GET['_wpnonce'] = $nonce;
	$_POST['_wpnonce'] = $nonce;
	ob_start();
	$outcome = call_user_func($handler);
	return ob_get_clean();
}

/* Trash now travels through the same preview and issued confirmation as the UI. */
function gigpress_upgrade_preservation_confirmed_trash($request, &$outcome = null) {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_GET = array();
	$_POST = array_merge($request, array('gpaction' => 'delete', 'trash_stage' => 'preview', '_wpnonce' => wp_create_nonce('gigpress-action')));
	$_REQUEST = $_POST;
	ob_start();
	$preview = gigpress_delete_show();
	$html = ob_get_clean();
	if (($preview['status'] ?? '') !== 'preview') { $outcome = $preview; return $html; }
	preg_match('/name="trash_token" value="([^"]+)"/', $html, $matches);
	preg_match('/name="_wpnonce" value="([^"]+)"/', $html, $nonceMatches);
	$token = html_entity_decode($matches[1] ?? '', ENT_QUOTES, 'UTF-8');
	$_POST = array_merge($request, array('gpaction' => 'delete', 'trash_stage' => 'confirm', 'trash_token' => $token,
		'_wpnonce' => $nonceMatches[1] ?? ''));
	$_REQUEST = $_POST;
	ob_start();
	$outcome = gigpress_delete_show();
	return $html . ob_get_clean();
}

function gigpress_upgrade_preservation_forged_request($handler, $request, $files = array()) {
	$_GET = $request;
	$_POST = $request;
	$_FILES = $files;
	$_REQUEST = $request;
	$level = ob_get_level();
	try {
		ob_start();
		call_user_func($handler);
		ob_end_clean();
		return false;
	} catch (RuntimeException $exception) {
		while (ob_get_level() > $level) ob_end_clean();
		return $exception->getMessage() === 'gigpress-test-invalid-nonce';
	}
}

function gigpress_upgrade_preservation_invalid_nonce_die_handler() {
	throw new RuntimeException('gigpress-test-invalid-nonce');
}

function gigpress_upgrade_preservation_nonce_blocks_request($handler, $request, $files = array()) {
	$filter = function () { return 'gigpress_upgrade_preservation_invalid_nonce_die_handler'; };
	add_filter('wp_die_handler', $filter);
	try {
		return gigpress_upgrade_preservation_forged_request($handler, $request, $files);
	} finally {
		remove_filter('wp_die_handler', $filter);
	}
}

function gigpress_upgrade_preservation_show_request($show, $overrides = array()) {
	$date = explode('-', $show['show_date']);
	$expire = explode('-', $show['show_expire']);
	$time = explode(':', $show['show_time']);
	$request = array(
		'gp_mm' => $date[1], 'gp_dd' => $date[2], 'gp_yy' => $date[0],
		'gp_hh' => $time[2] === '01' ? 'na' : $time[0], 'gp_min' => $time[2] === '01' ? 'na' : $time[1],
		'show_multi' => (int) $show['show_multi'],
		'exp_mm' => $expire[1], 'exp_dd' => $expire[2], 'exp_yy' => $expire[0],
		'show_price' => $show['show_price'], 'show_tix_url' => $show['show_tix_url'],
		'show_tix_phone' => $show['show_tix_phone'], 'show_external_url' => (string) ($show['show_external_url'] ?? ''),
		'show_ages' => $show['show_ages'], 'show_notes' => $show['show_notes'],
		'show_status' => $show['show_status'], 'show_artist_id' => (int) $show['show_artist_id'],
		'show_venue_id' => (int) $show['show_venue_id'], 'show_tour_id' => (int) $show['show_tour_id'],
		'show_related' => (int) $show['show_related'],
	);
	return array_merge($request, $overrides);
}

function gigpress_upgrade_preservation_show_row($id) {
	global $wpdb;
	return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $id), ARRAY_A);
}

function gigpress_upgrade_preservation_mutation_snapshot() {
	global $wpdb;
	$snapshot = array(
		'settings' => get_option('gigpress_settings'),
		'tour_restore_map' => get_option('gigpress_tour_restore_map', false),
	);
	foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $table => $id) {
		$snapshot[$table] = $wpdb->get_results('SELECT * FROM ' . constant('GIGPRESS_' . strtoupper($table)) . ' ORDER BY ' . $id, ARRAY_A);
	}
	return $snapshot;
}

function gigpress_upgrade_preservation_run_mutation_readiness_guards() {
	global $wpdb;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$ok = is_array($fixture) && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';

	// The legacy map action is also a dispatched write path: a valid nonce must
	// keep its established workflow available before blocked-state assertions.
	gigpress_upgrade_preservation_request('gigpress_map_tours_to_artists', array());
	$ok = $ok
		&& !$wpdb->get_var($wpdb->prepare('SELECT tour_id FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', 29))
		&& $wpdb->get_var($wpdb->prepare('SELECT artist_id FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', 'Preservation Tour'));

	$ok = $ok && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	$uploads = wp_upload_dir();
	$uploadName = 'gigpress-blocked-readiness.csv';
	$uploadPath = trailingslashit($uploads['path']) . $uploadName;
	if (file_exists($uploadPath)) unlink($uploadPath);
	$tmpImport = tempnam(sys_get_temp_dir(), 'gigpress-blocked-import-');
	file_put_contents($tmpImport, "Date,Artist,Venue,City,Country\\n2032-05-06,Blocked Band,Blocked Hall,Vienna,AT\\n");
	$importFiles = array('gp_import' => array('name' => $uploadName, 'tmp_name' => $tmpImport, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmpImport), 'type' => 'text/csv'));
	$requests = array(
		'gigpress_add_venue' => array('venue_name' => 'Blocked Venue', 'venue_city' => 'Vienna', 'venue_country' => 'AT'),
		'gigpress_update_venue' => array('venue_id' => 73, 'venue_name' => 'Changed Venue', 'venue_city' => 'Vienna', 'venue_country' => 'AT'),
		'gigpress_add_tour' => array('tour_name' => 'Blocked Tour'),
		'gigpress_update_tour' => array('tour_id' => 29, 'tour_name' => 'Changed Tour'),
		'gigpress_add_artist' => array('artist_name' => 'Blocked Artist', 'artist_url' => ''),
		'gigpress_update_artist' => array('artist_id' => 41, 'artist_name' => 'Changed Artist', 'artist_url' => ''),
		'gigpress_import' => array(),
		'gigpress_empty_trash' => array(),
		'gigpress_map_tours_to_artists' => array(),
	);
	$beforeBlocked = gigpress_upgrade_preservation_mutation_snapshot();
	$GLOBALS['gigpress_db_bootstrap_result'] = array('status' => 'blocked', 'code' => 'unsafe_metadata');
	$blockedNotices = array();
	foreach ($requests as $handler => $request) {
		$files = $handler === 'gigpress_import' ? $importFiles : array();
		$blockedNotices[$handler] = gigpress_upgrade_preservation_request($handler, $request, $files);
	}
	$afterBlocked = gigpress_upgrade_preservation_mutation_snapshot();
	$noticesPresent = !array_filter($blockedNotices, function ($notice) {
		return strpos($notice, 'GigPress data upgrade is paused') === false;
	});
	$noUpload = !file_exists($uploadPath);
	$nonceBlocked = array();
	foreach ($requests as $handler => $request) {
		$files = $handler === 'gigpress_import' ? $importFiles : array();
		$nonceBlocked[$handler] = gigpress_upgrade_preservation_nonce_blocks_request($handler, $request, $files);
	}
	$afterForged = gigpress_upgrade_preservation_mutation_snapshot();
	if (file_exists($tmpImport)) unlink($tmpImport);
	$ok = $ok && $beforeBlocked === $afterBlocked && $afterBlocked === $afterForged
		&& $noticesPresent && $noUpload && !in_array(false, $nonceBlocked, true);

	return array('status' => $ok ? 'PASS' : 'FAIL', 'case' => 'mutation-readiness-guards', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => $ok, 'repeat_matches' => $ok);
}

function gigpress_upgrade_preservation_run_show_lifecycle() {
	global $wpdb;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$ok = is_array($fixture) && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
	$ok = $ok && gigpress_db_in(null) === '' && gigpress_db_out(null) === '';

	$source = gigpress_upgrade_preservation_show_row(109);
	$untouchedTrash = gigpress_upgrade_preservation_show_row(113);
	$addRequest = gigpress_upgrade_preservation_show_request($source, array(
		'gp_yy' => '2032', 'gp_mm' => '05', 'gp_dd' => '06', 'exp_yy' => '2032', 'exp_mm' => '05', 'exp_dd' => '08',
		'show_notes' => 'Created after upgrade', 'show_status' => 'active',
	));
	// Keep the legacy component request; successful identity now comes from the explicit outcome.
	gigpress_upgrade_preservation_request('gigpress_add_show', $addRequest, array(), $addOutcome);
	$createdId = (int) ($addOutcome['show_id'] ?? 0);
	$created = gigpress_upgrade_preservation_show_row($createdId);
	if (!$created) return array('status' => 'FAIL', 'case' => 'show-lifecycle', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => false, 'repeat_matches' => false, 'add_outcome' => $addOutcome);
	$ok = $ok && ($addOutcome['status'] ?? '') === 'saved' && $createdId > 0 && $created && $created['show_notes'] === 'Created after upgrade'
		&& (int) $created['show_artist_id'] === (int) $source['show_artist_id']
		&& (int) $created['show_venue_id'] === (int) $source['show_venue_id']
		&& (int) $created['show_tour_id'] === (int) $source['show_tour_id']
		&& (int) $created['show_related'] === (int) $source['show_related'];

	$editRequest = gigpress_upgrade_preservation_show_request($created, array('show_id' => $createdId, 'show_notes' => 'Edited after upgrade'));
	gigpress_upgrade_preservation_request('gigpress_update_show', $editRequest, array(), $editOutcome);
	$edited = gigpress_upgrade_preservation_show_row($createdId);
	$ok = $ok && ($editOutcome['status'] ?? '') === 'saved' && $edited && (int) $edited['show_id'] === $createdId && $edited['show_notes'] === 'Edited after upgrade';

	$copyRequest = gigpress_upgrade_preservation_show_request($source, array('show_notes' => 'Copied after upgrade'));
	gigpress_upgrade_preservation_request('gigpress_add_show', $copyRequest, array(), $copyOutcome);
	$copyId = (int) ($copyOutcome['show_id'] ?? 0);
	$copy = gigpress_upgrade_preservation_show_row($copyId);
	$sourceAfterCopy = gigpress_upgrade_preservation_show_row(109);
	$ok = $ok && ($copyOutcome['status'] ?? '') === 'saved' && $copyId > 0 && $copyId !== 109 && $copy && $sourceAfterCopy === $source
		&& (int) $copy['show_artist_id'] === (int) $source['show_artist_id']
		&& (int) $copy['show_venue_id'] === (int) $source['show_venue_id']
		&& (int) $copy['show_tour_id'] === (int) $source['show_tour_id']
		&& (int) $copy['show_related'] === (int) $source['show_related'];

	gigpress_upgrade_preservation_request('gigpress_delete_show', array('show_id' => array($createdId, $copyId)));
	$trashedCreated = gigpress_upgrade_preservation_show_row($createdId);
	$trashedCopy = gigpress_upgrade_preservation_show_row($copyId);
	$sourceAfterTrash = gigpress_upgrade_preservation_show_row(109);
	$unselectedTrash = gigpress_upgrade_preservation_show_row(113);
	$ok = $ok && $trashedCreated['show_status'] === 'deleted' && $trashedCopy['show_status'] === 'deleted'
		&& $sourceAfterTrash === $source && $unselectedTrash === $untouchedTrash;

	gigpress_upgrade_preservation_request(function () { gigpress_undo('show'); }, array('show_id' => $createdId . ',' . $copyId));
	$restoredCreated = gigpress_upgrade_preservation_show_row($createdId);
	$restoredCopy = gigpress_upgrade_preservation_show_row($copyId);
	$ok = $ok && $restoredCreated['show_status'] === 'active' && $restoredCopy['show_status'] === 'active'
		&& (int) $restoredCreated['show_id'] === $createdId && (int) $restoredCopy['show_id'] === $copyId;

	$beforeBlocked = $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A);
	$GLOBALS['gigpress_db_bootstrap_result'] = array('status' => 'blocked', 'code' => 'unsafe_metadata');
	gigpress_upgrade_preservation_request('gigpress_add_show', $addRequest);
	gigpress_upgrade_preservation_request('gigpress_update_show', $editRequest);
	gigpress_upgrade_preservation_request('gigpress_delete_show', array('show_id' => array($createdId)));
	gigpress_upgrade_preservation_request(function () { gigpress_undo('show'); }, array('show_id' => $copyId));
	$afterBlocked = $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A);
	$ok = $ok && $beforeBlocked === $afterBlocked;

	$readiness = gigpress_upgrade_preservation_run_mutation_readiness_guards();
	$ok = $ok && $readiness['status'] === 'PASS';
	return array('status' => $ok ? 'PASS' : 'FAIL', 'case' => 'show-lifecycle', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => $ok, 'repeat_matches' => $ok);
}

function gigpress_upgrade_preservation_run_optional_request_fields() {
	global $wpdb, $pluginErrors;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$ok = is_array($fixture) && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
	$warningCount = count($pluginErrors);
	$request = array(
		'gp_mm' => '05', 'gp_dd' => '06', 'gp_yy' => '2032', 'gp_hh' => 'na', 'gp_min' => 'na',
		'show_artist_id' => 41, 'show_venue_id' => 'new', 'venue_name' => 'Sparse Request Hall',
		'venue_city' => 'Vienna', 'venue_country' => 'AT', 'show_tour_id' => 29,
		'show_related' => 0, 'show_status' => 'active',
	);
	gigpress_upgrade_preservation_request('gigpress_add_show', $request, array(), $outcome);
	$show = gigpress_upgrade_preservation_show_row((int) ($outcome['show_id'] ?? 0));
	$venue = $show ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_VENUES . ' WHERE venue_id = %d', $show['show_venue_id']), ARRAY_A) : null;
	$warnings = array_slice($pluginErrors, $warningCount);
	$showOptional = array('show_price', 'show_tix_url', 'show_tix_phone', 'show_external_url', 'show_ages', 'show_notes');
	$venueOptional = array('venue_address', 'venue_state', 'venue_postal_code', 'venue_url', 'venue_phone');
	foreach ($showOptional as $field) $ok = $ok && $show && array_key_exists($field, $show) && $show[$field] === '';
	foreach ($venueOptional as $field) $ok = $ok && $venue && array_key_exists($field, $venue) && $venue[$field] === '';
	$ok = $ok && !$warnings && $show && $venue && $venue['venue_name'] === 'Sparse Request Hall'
		&& $venue['venue_city'] === 'Vienna' && $venue['venue_country'] === 'AT';
	return array('status' => $ok ? 'PASS' : 'FAIL', 'case' => 'optional-request-fields', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => $ok, 'repeat_matches' => $ok, 'warning_count' => count($warnings));
}

function gigpress_upgrade_preservation_run_entity_guards() {
	global $wpdb;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$ok = is_array($fixture) && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/artists.php';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/venues.php';
	$source = gigpress_upgrade_preservation_show_row(109);

	$entities = array();
	foreach (array('active' => 'active', 'trashed' => 'deleted', 'unreferenced' => null) as $kind => $status) {
		$wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => 'Guard ' . $kind, 'artist_alpha' => 'guard ' . $kind, 'artist_url' => '', 'artist_order' => 0));
		$artistId = (int) $wpdb->insert_id;
		$wpdb->insert(GIGPRESS_VENUES, array('venue_name' => 'Guard ' . $kind, 'venue_address' => '', 'venue_city' => 'Vienna', 'venue_state' => '', 'venue_postal_code' => '', 'venue_country' => 'AT', 'venue_url' => '', 'venue_phone' => ''));
		$venueId = (int) $wpdb->insert_id;
		$entities[$kind] = array('artist' => $artistId, 'venue' => $venueId);
		if ($status !== null) {
			$show = $source;
			unset($show['show_id']);
			$show['show_artist_id'] = $artistId;
			$show['show_venue_id'] = $venueId;
			$show['show_status'] = $status;
			$show['show_notes'] = 'Guard ' . $kind;
			$wpdb->insert(GIGPRESS_SHOWS, $show);
		}
	}

	$_GET = $_POST = $_REQUEST = array();
	ob_start(); gigpress_artists(); $artistView = ob_get_clean();
	$_GET = $_POST = $_REQUEST = array();
	ob_start(); gigpress_venues(); $venueView = ob_get_clean();
	$ok = $ok && strpos($artistView, 'gpaction=delete&amp;artist_id=' . $entities['trashed']['artist']) === false
		&& strpos($venueView, 'gpaction=delete&amp;venue_id=' . $entities['trashed']['venue']) === false;

	$beforeShows = $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A);
	foreach (array('active', 'trashed') as $kind) {
		gigpress_upgrade_preservation_request('gigpress_delete_artist', array('artist_id' => $entities[$kind]['artist']));
		gigpress_upgrade_preservation_request('gigpress_delete_venue', array('venue_id' => $entities[$kind]['venue']));
		$ok = $ok && gigpress_upgrade_preservation_show_row((int) $wpdb->get_var($wpdb->prepare('SELECT show_id FROM ' . GIGPRESS_SHOWS . ' WHERE show_notes = %s', 'Guard ' . $kind))) !== null
			&& $wpdb->get_var($wpdb->prepare('SELECT artist_id FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_id = %d', $entities[$kind]['artist']))
			&& $wpdb->get_var($wpdb->prepare('SELECT venue_id FROM ' . GIGPRESS_VENUES . ' WHERE venue_id = %d', $entities[$kind]['venue']));
	}
	gigpress_upgrade_preservation_request('gigpress_delete_artist', array('artist_id' => $entities['unreferenced']['artist']));
	gigpress_upgrade_preservation_request('gigpress_delete_venue', array('venue_id' => $entities['unreferenced']['venue']));
	$afterShows = $wpdb->get_results('SELECT * FROM ' . GIGPRESS_SHOWS . ' ORDER BY show_id', ARRAY_A);
	$ok = $ok && $beforeShows === $afterShows
		&& !$wpdb->get_var($wpdb->prepare('SELECT artist_id FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_id = %d', $entities['unreferenced']['artist']))
		&& !$wpdb->get_var($wpdb->prepare('SELECT venue_id FROM ' . GIGPRESS_VENUES . ' WHERE venue_id = %d', $entities['unreferenced']['venue']));

	return array('status' => $ok ? 'PASS' : 'FAIL', 'case' => 'entity-guards', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => $ok, 'repeat_matches' => $ok);
}

function gigpress_upgrade_preservation_run_tour_undo() {
	global $wpdb, $tourRestoreFailurePoint;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$ok = is_array($fixture) && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
	$source = gigpress_upgrade_preservation_show_row(109);
	$wpdb->insert(GIGPRESS_TOURS, array('tour_name' => 'Second pending tour', 'tour_status' => 'active'));
	$secondTour = (int) $wpdb->insert_id;
	$secondShow = $source;
	unset($secondShow['show_id']);
	$secondShow['show_tour_id'] = $secondTour;
	$secondShow['show_notes'] = 'Second pending show';
	$wpdb->insert(GIGPRESS_SHOWS, $secondShow);
	$secondShowId = (int) $wpdb->insert_id;
	$wpdb->insert(GIGPRESS_TOURS, array('tour_name' => 'Intervening reassignment', 'tour_status' => 'active'));
	$interveningTour = (int) $wpdb->insert_id;

	gigpress_upgrade_preservation_request('gigpress_delete_tour', array('tour_id' => 29));
	$firstMap = get_option('gigpress_tour_restore_map', array());
	$ok = $ok && isset($firstMap[29][109], $firstMap[29][113])
		&& (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === 0
		&& (int) gigpress_upgrade_preservation_show_row(113)['show_tour_id'] === 0;
	gigpress_upgrade_preservation_request('gigpress_delete_tour', array('tour_id' => $secondTour));
	$secondMap = get_option('gigpress_tour_restore_map', array());
	$ok = $ok && isset($secondMap[29][109], $secondMap[$secondTour][$secondShowId]);

	$wpdb->update(GIGPRESS_SHOWS, array('show_tour_id' => $interveningTour), array('show_id' => 109));
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$afterFirstUndo = get_option('gigpress_tour_restore_map', array());
	$ok = $ok && (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === $interveningTour
		&& (int) gigpress_upgrade_preservation_show_row(113)['show_tour_id'] === 29
		&& !isset($afterFirstUndo[29]) && isset($afterFirstUndo[$secondTour][$secondShowId]);
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => $secondTour));
	$ok = $ok && (int) gigpress_upgrade_preservation_show_row($secondShowId)['show_tour_id'] === $secondTour
		&& !get_option('gigpress_tour_restore_map', false);

	$wpdb->update(GIGPRESS_SHOWS, array('show_tour_id' => 29, 'show_tour_restore' => 0), array('show_id' => 109));
	gigpress_upgrade_preservation_request('gigpress_delete_tour', array('tour_id' => 29));
	$tourRestoreFailurePoint = 'before_show_restore';
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$showFailureMap = get_option('gigpress_tour_restore_map', array());
	$ok = $ok && isset($showFailureMap[29][109], $showFailureMap[29][113])
		&& (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === 0;
	$tourRestoreFailurePoint = null;
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$ok = $ok && (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === 29
		&& !get_option('gigpress_tour_restore_map', false);

	gigpress_upgrade_preservation_request('gigpress_delete_tour', array('tour_id' => 29));
	$tourRestoreFailurePoint = 'before_tour_restore';
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$tourFailureMap = get_option('gigpress_tour_restore_map', array());
	$ok = $ok && isset($tourFailureMap[29][109], $tourFailureMap[29][113])
		&& (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === 0;
	$tourRestoreFailurePoint = null;
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$ok = $ok && (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === 29
		&& !get_option('gigpress_tour_restore_map', false);

	gigpress_upgrade_preservation_request('gigpress_delete_tour', array('tour_id' => 29));
	$cycleMap = get_option('gigpress_tour_restore_map', array());
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$ok = $ok && isset($cycleMap[29][113]) && (int) gigpress_upgrade_preservation_show_row(113)['show_tour_id'] === 29
		&& !get_option('gigpress_tour_restore_map', false);

	$wpdb->insert(GIGPRESS_TOURS, array('tour_name' => 'Missing show tour', 'tour_status' => 'active'));
	$missingTour = (int) $wpdb->insert_id;
	$missingShow = $source;
	unset($missingShow['show_id']);
	$missingShow['show_tour_id'] = $missingTour;
	$missingShow['show_notes'] = 'Missing pending show';
	$wpdb->insert(GIGPRESS_SHOWS, $missingShow);
	$missingShowId = (int) $wpdb->insert_id;
	gigpress_upgrade_preservation_request('gigpress_delete_tour', array('tour_id' => $missingTour));
	$missingMap = get_option('gigpress_tour_restore_map', array());
	$wpdb->delete(GIGPRESS_SHOWS, array('show_id' => $missingShowId));
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => $missingTour));
	$ok = $ok && isset($missingMap[$missingTour][$missingShowId]) && !get_option('gigpress_tour_restore_map', false);

	$wpdb->update(GIGPRESS_SHOWS, array('show_tour_id' => 0, 'show_tour_restore' => 1), array('show_id' => 109));
	gigpress_upgrade_preservation_request(function () { gigpress_undo('tour'); }, array('tour_id' => 29));
	$ok = $ok && (int) gigpress_upgrade_preservation_show_row(109)['show_tour_id'] === 0;

	return array('status' => $ok ? 'PASS' : 'FAIL', 'case' => 'tour-undo', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => $ok, 'repeat_matches' => $ok);
}

function gigpress_upgrade_preservation_run_crud($case) {
	if ($case === 'show-lifecycle') return gigpress_upgrade_preservation_run_show_lifecycle();
	if ($case === 'optional-request-fields') return gigpress_upgrade_preservation_run_optional_request_fields();
	if ($case === 'entity-guards') return gigpress_upgrade_preservation_run_entity_guards();
	if ($case === 'tour-undo') return gigpress_upgrade_preservation_run_tour_undo();
	return array('status' => 'FAIL', 'case' => $case, 'fixture' => 'reconstructed-1.4', 'manifest_matches' => false, 'repeat_matches' => false);
}

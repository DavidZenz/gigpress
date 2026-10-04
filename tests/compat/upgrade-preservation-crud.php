<?php
/* Mutation coverage for the reconstructed upgraded 1.4 fixture. */

function gigpress_upgrade_preservation_request($handler, $request) {
	$nonce = wp_create_nonce('gigpress-action');
	$_GET = $request;
	$_POST = $request;
	$_REQUEST = array_merge($request, array('_wpnonce' => $nonce));
	$_GET['_wpnonce'] = $nonce;
	$_POST['_wpnonce'] = $nonce;
	ob_start();
	call_user_func($handler);
	return ob_get_clean();
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
		'show_tix_phone' => $show['show_tix_phone'], 'show_external_url' => $show['show_external_url'],
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

function gigpress_upgrade_preservation_run_show_lifecycle() {
	global $wpdb;
	$fixture = require WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$ok = is_array($fixture) && upgrade_preservation_seed($fixture);
	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$ok = $ok && gigpress_db_bootstrap()['status'] === 'ready';
	require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';

	$source = gigpress_upgrade_preservation_show_row(109);
	$untouchedTrash = gigpress_upgrade_preservation_show_row(113);
	$addRequest = gigpress_upgrade_preservation_show_request($source, array(
		'gp_yy' => '2032', 'gp_mm' => '05', 'gp_dd' => '06', 'exp_yy' => '2032', 'exp_mm' => '05', 'exp_dd' => '08',
		'show_notes' => 'Created after upgrade', 'show_status' => 'active',
	));
	gigpress_upgrade_preservation_request('gigpress_add_show', $addRequest);
	$createdId = (int) $wpdb->insert_id;
	$created = gigpress_upgrade_preservation_show_row($createdId);
	$ok = $ok && $createdId > 0 && $created && $created['show_notes'] === 'Created after upgrade'
		&& (int) $created['show_artist_id'] === (int) $source['show_artist_id']
		&& (int) $created['show_venue_id'] === (int) $source['show_venue_id']
		&& (int) $created['show_tour_id'] === (int) $source['show_tour_id']
		&& (int) $created['show_related'] === (int) $source['show_related'];

	$editRequest = gigpress_upgrade_preservation_show_request($created, array('show_id' => $createdId, 'show_notes' => 'Edited after upgrade'));
	gigpress_upgrade_preservation_request('gigpress_update_show', $editRequest);
	$edited = gigpress_upgrade_preservation_show_row($createdId);
	$ok = $ok && $edited && (int) $edited['show_id'] === $createdId && $edited['show_notes'] === 'Edited after upgrade';

	$copyRequest = gigpress_upgrade_preservation_show_request($source, array('show_notes' => 'Copied after upgrade'));
	gigpress_upgrade_preservation_request('gigpress_add_show', $copyRequest);
	$copyId = (int) $wpdb->insert_id;
	$copy = gigpress_upgrade_preservation_show_row($copyId);
	$sourceAfterCopy = gigpress_upgrade_preservation_show_row(109);
	$ok = $ok && $copyId > 0 && $copyId !== 109 && $copy && $sourceAfterCopy === $source
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

	return array('status' => $ok ? 'PASS' : 'FAIL', 'case' => 'show-lifecycle', 'fixture' => 'reconstructed-1.4', 'manifest_matches' => $ok, 'repeat_matches' => $ok);
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

function gigpress_upgrade_preservation_run_crud($case) {
	if ($case === 'show-lifecycle') return gigpress_upgrade_preservation_run_show_lifecycle();
	if ($case === 'entity-guards') return gigpress_upgrade_preservation_run_entity_guards();
	return array('status' => 'FAIL', 'case' => $case, 'fixture' => 'reconstructed-1.4', 'manifest_matches' => false, 'repeat_matches' => false);
}

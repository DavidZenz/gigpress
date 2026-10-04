<?php
/* Real WordPress requests, independent row snapshots and rendered controls. */
function gigpress_administration_list_request($post = array(), $get = array(), $render = false, $method = 'POST') {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = wp_slash($get);
    $_POST = wp_slash($post);
    $_REQUEST = array_merge($_GET, $_POST);
    $_FILES = array();
    $filter = function () { return 'gigpress_administration_list_die'; };
    add_filter('wp_die_handler', $filter);
    ob_start();
    try { $outcome = $render ? gigpress_admin_shows() : gigpress_delete_show(); }
    catch (RuntimeException $exception) { $outcome = array('status' => 'blocked'); }
    finally { remove_filter('wp_die_handler', $filter); }
    return array('outcome' => $outcome, 'html' => ob_get_clean());
}

function gigpress_administration_list_die() { throw new RuntimeException('list-request-blocked'); }

function gigpress_administration_list_seed($count = 3) {
    global $wpdb;
    $wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => 'List <Band>', 'artist_alpha' => 'list band', 'artist_url' => ''));
    $artist = (int) $wpdb->insert_id;
    $wpdb->insert(GIGPRESS_VENUES, array('venue_name' => 'List <Hall>', 'venue_city' => 'Vienna', 'venue_country' => 'AT'));
    $venue = (int) $wpdb->insert_id;
    $wpdb->insert(GIGPRESS_TOURS, array('tour_name' => 'List Tour', 'tour_status' => 'active'));
    $tour = (int) $wpdb->insert_id;
    $ids = array();
    for ($i = 0; $i < $count; $i++) {
        $wpdb->insert(GIGPRESS_SHOWS, array('show_date' => '2032-05-06', 'show_expire' => '2032-05-06', 'show_time' => '00:00:01',
            'show_artist_id' => $artist, 'show_venue_id' => $venue, 'show_tour_id' => $tour, 'show_status' => 'active', 'show_notes' => 'Distinct list show ' . $i));
        $ids[] = (int) $wpdb->insert_id;
    }
    return array('ids' => $ids, 'state' => array('scope' => 'all', 'artist_id' => $artist, 'venue_id' => $venue, 'tour_id' => $tour, 'sort' => 'desc', 'limit' => 10, 'gp-page' => 1));
}

function gigpress_administration_list_snapshot() {
    global $wpdb;
    $snapshot = array('settings' => get_option('gigpress_settings'));
    foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $table => $id)
        $snapshot[$table] = $wpdb->get_results('SELECT * FROM ' . constant('GIGPRESS_' . strtoupper($table)) . ' ORDER BY ' . $id, ARRAY_A);
    return $snapshot;
}

function gigpress_administration_list_preview($ids, $state, $single = null) {
    $post = array_merge($state, array('gpaction' => 'delete', 'trash_stage' => 'preview', 'show_id' => $ids, '_wpnonce' => wp_create_nonce('gigpress-action')));
    if ($single !== null) $post['trash_single_id'] = $single;
    return gigpress_administration_list_request($post);
}

function gigpress_administration_list_confirmation($preview, $ids, $state, $stage = 'confirm') {
    preg_match('/name="trash_token" value="([^"]+)"/', $preview['html'], $token);
    $token = html_entity_decode($token[1] ?? '', ENT_QUOTES, 'UTF-8');
    return array_merge($state, array('gpaction' => 'delete', 'trash_stage' => $stage, 'trash_token' => $token, 'show_id' => $ids,
        '_wpnonce' => wp_create_nonce('gigpress-trash-' . $stage . '-' . $token)));
}

function gigpress_administration_list_case($case) {
    require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
    require_once WP_PLUGIN_DIR . '/gigpress/admin/shows.php';
    $checks = array();
    if ($case === 'list-single') $checks = gigpress_administration_list_single();
    return array('case' => $case, 'checks' => $checks);
}

function gigpress_administration_list_single() {
    global $wpdb;
    $fixture = gigpress_administration_list_seed();
    list($id, $other) = $fixture['ids'];
    $state = $fixture['state'];
    $baseline = gigpress_administration_list_snapshot();
    $preview = gigpress_administration_list_preview(array($other), $state, $id);
    $checks = array('preview_no_row_changes' => $baseline === gigpress_administration_list_snapshot(),
        'preview_count_one' => strpos($preview['html'], '1 selected show') !== false,
        'preview_identity_id' => strpos($preview['html'], '#' . $id) !== false,
        'preview_identity_date' => strpos($preview['html'], '2032-05-06') !== false,
        'preview_identity_artist_escaped' => strpos($preview['html'], 'List &lt;Band&gt;') !== false,
        'preview_identity_venue_escaped' => strpos($preview['html'], 'List &lt;Hall&gt;') !== false,
        'preview_confirm_cancel' => strpos($preview['html'], '>Confirm</button>') !== false && strpos($preview['html'], '>Cancel</button>') !== false,
        'clicked_row_ignores_checked_bulk' => ($preview['outcome']['ids'] ?? null) === array($id));
    $cancel = gigpress_administration_list_confirmation($preview, array($id), $state, 'cancel');
    $cancelResult = gigpress_administration_list_request($cancel);
    $checks['cancel_no_changes'] = $baseline === gigpress_administration_list_snapshot();
    $checks['cancel_return_state'] = ($cancelResult['outcome']['state'] ?? null) == $state;
    $checks['cancel_feedback'] = strpos($cancelResult['html'], 'No shows were changed') !== false;
    $canceledConfirm = gigpress_administration_list_confirmation($preview, array($id), $state);
    gigpress_administration_list_request($canceledConfirm);
    $checks['cancel_consumes_intent'] = $baseline === gigpress_administration_list_snapshot();
    // Restore the baseline only for the old immediate-delete implementation in RED.
    $wpdb->update(GIGPRESS_SHOWS, array('show_status' => 'active'), array('show_id' => $id));
    $wpdb->update(GIGPRESS_SHOWS, array('show_status' => 'active'), array('show_id' => $other));
    $baseline = gigpress_administration_list_snapshot();
    $preview = gigpress_administration_list_preview(array($id), $state, $id);
    $confirm = gigpress_administration_list_confirmation($preview, array($id), $state);
    foreach (array('direct' => array_merge($confirm, array('trash_token' => '')),
        'tampered_id' => array_merge($confirm, array('show_id' => array($other))),
        'nested_id' => array_merge($confirm, array('show_id' => array(array($id)))),
        'nested_token' => array_merge($confirm, array('trash_token' => array('bad'))),
        'invalid_nonce' => array_merge($confirm, array('_wpnonce' => 'bad'))) as $kind => $request) {
        // Legacy handler's check_admin_referer dies; use its real WP die adapter.
        $filter = function () { return 'gigpress_upgrade_preservation_invalid_nonce_die_handler'; };
        require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-crud.php';
        add_filter('wp_die_handler', $filter);
        $level = ob_get_level();
        try { gigpress_administration_list_request($request); } catch (RuntimeException $exception) { while (ob_get_level() > $level) ob_end_clean(); }
        remove_filter('wp_die_handler', $filter);
        $checks[$kind . '_no_changes'] = $baseline === gigpress_administration_list_snapshot();
    }
    gigpress_administration_list_request(array(), $confirm, false, 'GET');
    $checks['get_cannot_confirm'] = $baseline === gigpress_administration_list_snapshot();
    $owner = get_current_user_id();
    $foreign = wp_create_user('list-foreign', 'disposable-list-password', 'list-foreign@example.invalid');
    $foreignUser = new WP_User($foreign); $foreignUser->set_role('administrator');
    wp_set_current_user($foreign);
    $foreignRequest = array_merge($confirm, array('_wpnonce' => wp_create_nonce('gigpress-trash-confirm-' . $confirm['trash_token'])));
    gigpress_administration_list_request($foreignRequest);
    wp_set_current_user($owner);
    $checks['foreign_owner_no_changes'] = $baseline === gigpress_administration_list_snapshot();
    wp_set_current_user(0);
    $denied = array_merge($confirm, array('_wpnonce' => wp_create_nonce('gigpress-trash-confirm-' . $confirm['trash_token'])));
    gigpress_administration_list_request($denied);
    wp_set_current_user($owner);
    $checks['capability_no_changes'] = $baseline === gigpress_administration_list_snapshot();
    $GLOBALS['gigpress_db_bootstrap_result'] = array('status' => 'blocked', 'code' => 'unsafe_metadata');
    gigpress_administration_list_request($confirm);
    unset($GLOBALS['gigpress_db_bootstrap_result']);
    $checks['readiness_no_changes'] = $baseline === gigpress_administration_list_snapshot();
    // Exercise expiry with the issued server record, rather than waiting 15 minutes.
    $expired = gigpress_administration_list_preview(array($other), $state, $other);
    $expiredPost = gigpress_administration_list_confirmation($expired, array($other), $state);
    $key = 'gigpress_trash_' . $expiredPost['trash_token'];
    $intent = get_transient($key);
    if (is_array($intent)) { $intent['expires'] = time() - 1; set_transient($key, $intent, 900); }
    gigpress_administration_list_request($expiredPost);
    $checks['expired_no_changes'] = $baseline === gigpress_administration_list_snapshot();
    if (function_exists('gigpress_list_state')) {
        $result = gigpress_administration_list_request(array_merge($confirm, array('scope' => 'past', 'sort' => 'asc', 'limit' => 300)));
        $changed = gigpress_administration_list_snapshot();
        $expected = $baseline;
        foreach ($expected['shows'] as &$row) if ((int) $row['show_id'] === $id) $row['show_status'] = 'deleted';
        unset($row);
        $checks['confirm_exact_status_only_snapshot'] = $changed === $expected;
        $checks['confirm_changed_ids_one'] = ($result['outcome']['changed_ids'] ?? null) === array($id);
        $checks['confirm_count_truthful'] = strpos($result['html'], '1 shows moved to trash') !== false;
        $checks['confirmed_return_uses_owned_state'] = ($result['outcome']['state'] ?? null) == $state;
        $checks['undo_only_actual_id'] = strpos(html_entity_decode($result['html'], ENT_QUOTES, 'UTF-8'), 'show_id=' . $id . '&') !== false;
        $replay = gigpress_administration_list_request($confirm);
        $checks['replay_no_changes'] = $changed === gigpress_administration_list_snapshot() && empty($replay['outcome']['changed_ids']);
        $render = gigpress_administration_list_request(array(), $state, true, 'GET');
        $checks['row_trash_button'] = strpos($render['html'], 'name="trash_single_id" value="' . $other . '"') !== false;
        $checks['single_list_form_no_nested_forms'] = substr_count($render['html'], '<form') === 2 && substr_count($render['html'], '</form>') === 2;
    } else $checks['confirmed_protocol_exists'] = false;
    return $checks;
}

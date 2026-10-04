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
    if ($case === 'list-navigation') $checks = gigpress_administration_list_navigation();
    return array('case' => $case, 'checks' => $checks);
}

function gigpress_administration_list_document($html) {
    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument(); $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    return new DOMXPath($dom);
}

function gigpress_administration_list_row_ids($html) {
    $xpath = gigpress_administration_list_document($html); $ids = array();
    foreach ($xpath->query('//input[@name="show_id[]" and @type="checkbox"]') as $node) $ids[] = (int) $node->getAttribute('value');
    return $ids;
}

function gigpress_administration_list_navigation() {
    $fixture = gigpress_administration_list_seed(23);
    $state = array_merge($fixture['state'], array('gp-page' => 2));
    $owner = get_current_user_id();
    update_user_meta($owner, 'gigpress_sort', 'ASC');
    $view = gigpress_administration_list_request(array(), $state, true, 'GET');
    $xpath = gigpress_administration_list_document($view['html']); $checks = array();
    foreach (array('artist_id', 'venue_id', 'tour_id', 'sort', 'limit') as $key) {
        $nodes = $xpath->query('//select[@name="' . $key . '"]/option[@selected]');
        $checks['visible_' . $key] = $nodes->length === 1 && $nodes->item(0)->getAttribute('value') === (string) $state[$key];
        $checks['label_' . $key] = $xpath->query('//label[@for="gp-list-' . $key . '"]')->length === 1;
    }
    $checks['visible_scope'] = $xpath->query('//input[@name="scope" and @value="all"]')->length > 0;
    $checks['sort_not_persisted'] = get_user_meta($owner, 'gigpress_sort', true) === 'ASC';
    $checks['scope_and_size_persisted'] = get_user_meta($owner, 'gigpress_scope', true) === 'all' && (int) get_user_meta($owner, 'gigpress_limit', true) === 10;
    $classes = array('scope' => 0, 'pagination' => 0, 'edit' => 0, 'copy' => 0, 'reset' => 0);
    foreach ($xpath->query('//a[@href]') as $node) {
        $href = $node->getAttribute('href'); parse_str((string) parse_url($href, PHP_URL_QUERY), $args);
        $class = null;
        if (strpos($node->getAttribute('class'), 'page-numbers') !== false) $class = 'pagination';
        elseif (($args['gpaction'] ?? '') === 'edit') $class = 'edit';
        elseif (($args['gpaction'] ?? '') === 'copy') $class = 'copy';
        elseif (($args['reset_filters'] ?? '') === '1') $class = 'reset';
        elseif ($node->parentNode->parentNode->getAttribute('class') === 'subsubsub') $class = 'scope';
        if (!$class) continue;
        $classes[$class]++;
        foreach ($state as $key => $value) {
            if (($class === 'scope' && $key === 'scope') || ($class === 'pagination' && $key === 'gp-page') || ($class === 'reset' && in_array($key, array('artist_id', 'venue_id', 'tour_id', 'gp-page'), true))) continue;
            $checks[$class . '_' . $classes[$class] . '_retains_' . $key] = isset($args[$key]) && (string) $args[$key] === (string) $value;
        }
        if ($class === 'reset') $checks['reset_link_narrow'] = ($args['gp-page'] ?? null) == 1 && ($args['artist_id'] ?? null) == -1 && ($args['venue_id'] ?? null) == -1 && ($args['tour_id'] ?? null) == -1;
    }
    foreach ($classes as $class => $count) $checks['links_exist_' . $class] = $count > 0;
    foreach ($xpath->query('//input[@type="checkbox"]') as $node) {
        $id = $node->getAttribute('id');
        $checks['checkbox_labeled_' . ($id ?: $node->getAttribute('value'))] = $id !== '' && $xpath->query('//label[@for="' . $id . '"]')->length === 1;
    }
    $checks['semantic_column_headings'] = $xpath->query('//thead/tr/th[@scope="col"]')->length === 8;
    $checks['integer_page_size_not_sql_limit'] = $xpath->query('//select[@name="limit"]/option[@value="10" and @selected]')->length === 1;
    $reset = gigpress_administration_list_request(array(), array_merge($state, array('reset_filters' => '1')), true, 'GET');
    $resetState = array_merge($state, array('artist_id' => -1, 'tour_id' => -1, 'venue_id' => -1, 'gp-page' => 1));
    $checks['reset_normalized_state'] = ($reset['outcome']['state'] ?? null) == $resetState;
    $fresh = gigpress_administration_list_request(array(), array(), true, 'GET');
    $checks['fresh_sort_asc_ignores_legacy_meta'] = ($fresh['outcome']['state']['sort'] ?? '') === 'asc';
    foreach (array('artist_id', 'venue_id', 'tour_id') as $key) $checks['fresh_request_only_' . $key] = ($fresh['outcome']['state'][$key] ?? 0) === -1 && get_user_meta($owner, 'gigpress_' . $key, true) === '';
    $second = wp_create_user('list-preferences', 'disposable-preferences-password', 'list-preferences@example.invalid');
    $user = new WP_User($second); $user->set_role('administrator'); wp_set_current_user($second);
    $secondView = gigpress_administration_list_request(array(), array(), true, 'GET');
    $checks['independent_user_defaults'] = ($secondView['outcome']['state']['scope'] ?? '') === 'upcoming' && ($secondView['outcome']['state']['limit'] ?? 0) === 25;
    wp_set_current_user($owner);
    $checks['first_user_preferences_retained'] = get_user_meta($owner, 'gigpress_scope', true) === 'all' && (int) get_user_meta($owner, 'gigpress_limit', true) === 10;
    $asc = array_merge($fixture['state'], array('sort' => 'asc'));
    $all = array();
    for ($page = 1; $page <= 3; $page++) {
        $pageView = gigpress_administration_list_request(array(), array_merge($asc, array('gp-page' => $page)), true, 'GET');
        $all = array_merge($all, gigpress_administration_list_row_ids($pageView['html']));
        $checks['safe_page_metadata_' . $page] = ($pageView['outcome']['pagination']['offset'] ?? -1) === ($page - 1) * 10;
    }
    $checks['equal_keys_distinct_ids_stable_asc'] = $all === $fixture['ids'];
    $desc = array();
    for ($page = 1; $page <= 3; $page++) $desc = array_merge($desc, gigpress_administration_list_row_ids(gigpress_administration_list_request(array(), array_merge($fixture['state'], array('gp-page' => $page)), true, 'GET')['html']));
    $checks['equal_keys_stable_desc'] = $desc === array_reverse($fixture['ids']);
    $outOfRange = gigpress_administration_list_request(array(), array_merge($asc, array('gp-page' => 999)), true, 'GET');
    $checks['clamped_last_page'] = ($outOfRange['outcome']['state']['gp-page'] ?? 0) === 3 && gigpress_administration_list_row_ids($outOfRange['html']) === array_slice($fixture['ids'], 20);
    $zero = gigpress_administration_list_request(array(), array_merge($state, array('artist_id' => 999999)), true, 'GET');
    $checks['zero_matches_text_and_reset'] = strpos($zero['html'], 'No shows match these filters') !== false && strpos($zero['html'], 'Reset filters') !== false;
    $checks['zero_matches_safe_page'] = ($zero['outcome']['state']['gp-page'] ?? 0) === 1 && ($zero['outcome']['pagination']['offset'] ?? -1) === 0;
    $one = gigpress_administration_list_seed(1);
    $oneView = gigpress_administration_list_request(array(), array_merge($one['state'], array('gp-page' => 99)), true, 'GET');
    $checks['single_match_safe_page'] = ($oneView['outcome']['state']['gp-page'] ?? 0) === 1 && gigpress_administration_list_row_ids($oneView['html']) === $one['ids'];
    // The prior renderer lacks domain guards. Keep RED about behavior, avoiding unrelated PHP fixture errors.
    if (isset($view['outcome']['state'])) {
        foreach (array('scope' => array('all'), 'sort' => array('desc'), 'limit' => array(10), 'gp-page' => array(2), 'artist_id' => array(1), 'venue_id' => '1 OR 1=1', 'tour_id' => '-2') as $key => $bad) {
            $invalid = gigpress_administration_list_request(array(), array_merge($state, array($key => $bad)), true, 'GET');
            $checks['malformed_safe_' . $key] = strpos($invalid['html'], 'Invalid list choices') !== false && isset($invalid['outcome']['state']) && !is_array($invalid['outcome']['state'][$key]);
        }
        update_user_meta($owner, 'gigpress_scope', array('all')); update_user_meta($owner, 'gigpress_limit', '0,10');
        $invalidStored = gigpress_administration_list_request(array(), array(), true, 'GET');
        $checks['malformed_saved_preferences_safe'] = $invalidStored['outcome']['state']['scope'] === 'upcoming' && $invalidStored['outcome']['state']['limit'] === 25;
    } else $checks['malformed_domains_covered'] = false;
    return $checks;
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

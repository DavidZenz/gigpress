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
    if ($case === 'list-bulk') $checks = gigpress_administration_list_bulk();
    return array('case' => $case, 'checks' => $checks);
}

function gigpress_administration_list_bulk() {
    global $wpdb;
    $fixture = gigpress_administration_list_seed(8); $state = $fixture['state'];
    list($a, $b, $already, $stale, $failed, $zero, $unselected, $gone) = $fixture['ids'];
    $checks = array(); $before = gigpress_administration_list_snapshot();
    foreach (array('empty' => array(), 'scalar' => $a, 'nested' => array(array($a)), 'negative' => array($a, -1),
        'zero' => array($a, 0), 'text' => array($a, 'bad'), 'float' => array($a, 1.5), 'boolean' => array($a, true),
        'whitespace' => array($a, ' 2'), 'leading_zero' => array($a, '02'), 'oversized' => array($a, '9999999999999999999'),
        'keyed' => array('identity' => $a)) as $kind => $selection) {
        $response = gigpress_administration_list_preview($selection, $state);
        $checks['invalid_selection_no_changes_' . $kind] = $before === gigpress_administration_list_snapshot();
        $checks['invalid_selection_text_' . $kind] = strpos($response['html'], 'Select shows') !== false && ($response['outcome']['status'] ?? '') === 'blocked';
    }
    foreach (array('nested' => array($a), 'negative' => -1, 'zero' => 0, 'float' => 1.5, 'boolean' => true, 'text' => 'bad') as $kind => $single) {
        $response = gigpress_administration_list_preview(array($b), $state, $single);
        $checks['malformed_clicked_row_' . $kind] = $before === gigpress_administration_list_snapshot() && ($response['outcome']['status'] ?? '') === 'blocked';
    }
    $wpdb->update(GIGPRESS_SHOWS, array('show_status' => 'deleted'), array('show_id' => $already));
    $missing = 999999;
    $selection = array($a, $b, $already, $stale, $failed, $zero, $gone, $missing);
    $preview = gigpress_administration_list_preview(array_merge(array($a, $a), array_slice($selection, 1)), $state);
    $checks['explicit_ids_deduplicated'] = ($preview['outcome']['ids'] ?? null) === $selection;
    $checks['selected_count_not_filtered_count'] = strpos($preview['html'], '8 selected show') !== false && strpos($preview['html'], '#' . $unselected . ' ') === false;
    foreach ($selection as $id) $checks['preview_identity_' . $id] = strpos($preview['html'], '#' . $id) !== false;
    $confirm = gigpress_administration_list_confirmation($preview, $selection, $state);
    // A show is edited and another removed between review and confirmation.
    $wpdb->update(GIGPRESS_SHOWS, array('show_notes' => 'Changed after review'), array('show_id' => $stale));
    $wpdb->delete(GIGPRESS_SHOWS, array('show_id' => $gone));
    $baseline = gigpress_administration_list_snapshot();
    $filter = function ($sql) use ($failed, $zero) {
        if (!preg_match('/^UPDATE\s+`?' . preg_quote(GIGPRESS_SHOWS, '/') . '`?\s/i', $sql)) return $sql;
        if (preg_match('/`show_id`\s*=\s*' . $failed . '\b/', $sql)) return 'SELECT * FROM gigpress_intentionally_missing_list_table';
        if (preg_match('/`show_id`\s*=\s*' . $zero . '\b/', $sql)) return str_replace('`show_id` = ' . $zero, '`show_id` = -1', $sql);
        return $sql;
    };
    $suppressed = $wpdb->suppress_errors(true); add_filter('query', $filter);
    try { $result = gigpress_administration_list_request($confirm); }
    finally { remove_filter('query', $filter); $wpdb->suppress_errors($suppressed); }
    $expected = $baseline;
    foreach ($expected['shows'] as &$row) if (in_array((int) $row['show_id'], array($a, $b), true)) $row['show_status'] = 'deleted';
    unset($row);
    $checks['only_two_verified_status_transitions'] = gigpress_administration_list_snapshot() === $expected;
    $checks['truthful_changed_ids'] = ($result['outcome']['changed_ids'] ?? null) === array($a, $b);
    $checks['truthful_count_text'] = strpos($result['html'], '2 shows moved to trash; 6 could not be changed') !== false;
    $rows = $result['outcome']['results'] ?? array();
    $checks['each_unique_selection_once'] = array_column($rows, 'show_id') === $selection;
    $expectedResults = array($a => 'changed', $b => 'changed', $already => 'already_trashed', $stale => 'stale', $failed => 'failed', $zero => 'stale', $gone => 'missing', $missing => 'missing');
    foreach ($rows as $row) {
        $id = $row['show_id'];
        $checks['result_kind_' . $id] = $row['result'] === $expectedResults[$id];
        $checks['result_reason_identity_' . $id] = !empty($row['reason']) && strpos($row['identity'], '#' . $id) !== false && strpos($result['html'], esc_html($row['reason'])) !== false;
        $checks['result_actionable_link_' . $id] = !empty($row['url']) && strpos($result['html'], esc_url($row['url'])) !== false;
        if (!empty($row['url'])) {
            parse_str((string) parse_url($row['url'], PHP_URL_QUERY), $args);
            foreach ($state as $key => $value) $checks['result_' . $id . '_retains_' . $key] = isset($args[$key]) && (string) $args[$key] === (string) $value;
            $checks['missing_uses_list_' . $id] = $row['result'] !== 'missing' || (($args['page'] ?? '') === 'gigpress-shows' && !isset($args['show_id']));
        }
    }
    $links = gigpress_administration_list_document($result['html']); $undo = null;
    foreach ($links->query('//a[@href]') as $node) {
        parse_str((string) parse_url($node->getAttribute('href'), PHP_URL_QUERY), $args);
        if (($args['gpaction'] ?? '') === 'undo') $undo = $args;
    }
    $checks['undo_exact_successes'] = ($undo['show_id'] ?? '') === $a . ',' . $b;
    foreach ($state as $key => $value) $checks['undo_retains_' . $key] = isset($undo[$key]) && (string) $undo[$key] === (string) $value;
    gigpress_administration_list_request($confirm);
    $checks['bulk_replay_no_changes'] = gigpress_administration_list_snapshot() === $expected;
    if ($undo) {
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = $undo; $_POST = array(); $_REQUEST = $undo;
        ob_start(); gigpress_undo('show'); $restoreHtml = ob_get_clean();
        $restored = $baseline;
        $checks['undo_only_successes_snapshot'] = gigpress_administration_list_snapshot() === $restored;
        ob_start(); gigpress_undo('show'); $repeatHtml = ob_get_clean();
        $checks['already_restored_undo_no_changes'] = gigpress_administration_list_snapshot() === $restored;
        $checks['already_restored_undo_truthful_text'] = strpos($repeatHtml, '0 shows restored') !== false;
    }
    // Delete the sole row on page two, keeping all the other list choices.
    $last = gigpress_administration_list_seed(11);
    $lastState = array_merge($last['state'], array('sort' => 'asc', 'gp-page' => 2));
    $lastId = $last['ids'][10];
    $lastPreview = gigpress_administration_list_preview(array($lastId), $lastState);
    $lastPost = gigpress_administration_list_confirmation($lastPreview, array($lastId), $lastState);
    $lastResult = gigpress_administration_list_request($lastPost, $lastState, true);
    $checks['after_last_page_trash_clamped'] = ($lastResult['outcome']['state']['gp-page'] ?? 0) === 1 && ($lastResult['outcome']['pagination']['offset'] ?? -1) === 0;
    $checks['last_page_return_notice_clamped'] = strpos(html_entity_decode($lastResult['html'], ENT_QUOTES, 'UTF-8'), 'gp-page=2&gpaction=undo') === false;
    foreach ($lastState as $key => $value) if ($key !== 'gp-page') $checks['last_page_retains_' . $key] = ($lastResult['outcome']['state'][$key] ?? null) === $value;
    $checks['last_page_unselected_equal_rows_remain'] = gigpress_administration_list_row_ids($lastResult['html']) === array_slice($last['ids'], 0, 10);
    $checks['no_header_selection_ids'] = gigpress_administration_list_document($lastResult['html'])->query('//thead//input[@name] | //tfoot//input[@name]')->length === 0;
    return $checks;
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

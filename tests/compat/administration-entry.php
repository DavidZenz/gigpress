<?php
/* Real WordPress show-entry assertions. Browser interaction is verified separately. */
function gigpress_administration_entry_request($request, $render = false, $get = array()) {
    $_GET = $get;
    $_POST = wp_slash($request);
    $_POST['_wpnonce'] = wp_create_nonce('gigpress-action');
    $_REQUEST = array_merge($_GET, $_POST);
    ob_start();
    $outcome = $render ? gigpress_add() : (($request['gpaction'] ?? 'add') === 'update' ? gigpress_update_show() : gigpress_add_show());
    return array('outcome' => $outcome, 'html' => ob_get_clean());
}

function gigpress_administration_entry_base() {
    global $wpdb;
    $wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => 'Entry Band', 'artist_alpha' => 'entry band', 'artist_url' => ''));
    $artist = (int) $wpdb->insert_id;
    $wpdb->insert(GIGPRESS_VENUES, array('venue_name' => 'Entry Hall', 'venue_city' => 'Vienna', 'venue_country' => 'AT'));
    return array('gpaction' => 'add', 'show_date' => '2032-05-06', 'gp_yy' => '2032', 'gp_mm' => '05', 'gp_dd' => '07',
        'gp_hh' => 'na', 'gp_min' => 'na', 'show_artist_id' => $artist, 'show_venue_id' => (int) $wpdb->insert_id,
        'show_tour_id' => '0', 'show_related' => '0', 'show_status' => 'active', 'show_notes' => 'Native entry');
}

function gigpress_administration_entry_case($case) {
    global $wpdb;
    require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
    require_once WP_PLUGIN_DIR . '/gigpress/admin/new.php';
    $request = gigpress_administration_entry_base();
    $checks = array();
    if ($case === 'entry-create') {
        $first = gigpress_administration_entry_request($request, true);
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_notes = %s ORDER BY show_id DESC LIMIT 1', 'Native entry'), ARRAY_A);
        $id = $row ? (int) $row['show_id'] : 0;
        $checks['native_date_precedes_legacy'] = $row && $row['show_date'] === '2032-05-06';
        $checks['optional_time_sentinel'] = $row && $row['show_time'] === '00:00:01';
        $checks['unchanged_expiration'] = $row && $row['show_expire'] === $row['show_date'] && (int) $row['show_multi'] === 0;
        $checks['native_date_control'] = strpos($first['html'], 'type="date"') !== false && strpos($first['html'], 'name="show_date"') !== false;
        $checks['saved_edit_link'] = $id > 0 && strpos(html_entity_decode($first['html'], ENT_QUOTES, 'UTF-8'), 'gpaction=edit&show_id=' . $id) !== false;
        $checks['list_link'] = strpos($first['html'], 'View list') !== false && strpos($first['html'], 'page=gigpress-shows') !== false;
        $checks['fresh_add_mode'] = strpos($first['html'], 'name="gpaction" value="add"') !== false && strpos($first['html'], 'name="show_id"') === false;
        $second = gigpress_administration_entry_request(array_merge($request, array('show_notes' => 'Another entry')));
        $newId = is_array($second['outcome']) ? ($second['outcome']['show_id'] ?? 0) : 0;
        $checks['explicit_saved_outcome'] = is_array($second['outcome']) && ($second['outcome']['status'] ?? '') === 'saved';
        $checks['add_another_distinct_identity'] = $newId > 0 && $newId !== $id && (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $id)) === 1;
    }
    return array('case' => $case, 'checks' => $checks);
}

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
        'exp_yy' => '2032', 'exp_mm' => '05', 'exp_dd' => '08',
        'artist_name' => '', 'artist_url' => '', 'venue_name' => '', 'venue_address' => '', 'venue_city' => '', 'venue_state' => '',
        'venue_postal_code' => '', 'venue_country' => 'AT', 'venue_url' => '', 'venue_phone' => '', 'tour_name' => '',
        'show_related_title' => 'Show %artist%', 'show_related_date' => 'now', 'show_ages' => 'Not sure',
        'show_tix_url' => '', 'show_tix_phone' => '', 'show_price' => '', 'show_external_url' => '',
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
    } elseif ($case === 'entry-recovery') {
        $hostile = 'Ünicode “quote” \\ punctuation <script>alert("text")</script>';
        $bad = array_merge($request, array('show_date' => '2032-02-31', 'show_multi' => '1', 'show_end_date' => '2032-02-31',
            'show_artist_id' => 'new', 'show_venue_id' => 'new', 'show_tour_id' => 'new', 'show_related' => 'new',
            'artist_name' => $hostile, 'venue_name' => $hostile, 'venue_city' => $hostile, 'tour_name' => $hostile,
            'show_related_title' => $hostile, 'show_related_date' => 'show', 'show_notes' => $hostile));
        $before = gigpress_administration_entry_snapshot();
        $result = gigpress_administration_entry_request($bad, true);
        $checks['invalid_date_no_side_effects'] = $before === gigpress_administration_entry_snapshot();
        $checks['invalid_date_editable'] = preg_match('/type="text"[^>]*name="show_date"[^>]*value="2032-02-31"/', $result['html']) === 1;
        $checks['linked_field_error'] = strpos($result['html'], 'href="#show_date"') !== false && strpos($result['html'], 'id="show_date-error"') !== false;
        $checks['all_new_markers_retained'] = preg_match_all('/value="new" selected=[\'"]selected[\'"]/', $result['html']) === 4;
        $checks['notes_escaped_exactly'] = strpos($result['html'], esc_textarea($hostile) . '</textarea>') !== false && strpos($result['html'], '<script>') === false;
        $checks['radio_and_checked_state_retained'] = preg_match('/value="show" checked=[\'"]checked[\'"]/', $result['html']) === 1 && preg_match('/id="show_multi"[^>]*checked=[\'"]checked[\'"]/', $result['html']) === 1;
        if ($checks['invalid_date_editable']) $checks = array_merge($checks, gigpress_administration_entry_recovery_checks($request, $bad, $hostile));
    } elseif ($case === 'entry-controls') {
        $checks = gigpress_administration_entry_control_checks($request);
    }
    return array('case' => $case, 'checks' => $checks);
}

function gigpress_administration_entry_control_checks($request) {
    global $wpdb;
    $checks = array();
    foreach (array('none' => array('na', 'na', '00:00:01'), 'midnight' => array('00', '00', '00:00:00'), 'uncommon' => array('13', '17', '13:17:00'), 'missing-minute' => array('13', null, '13:00:00')) as $kind => $time) {
        $input = array_merge($request, array('gp_hh' => $time[0], 'gp_min' => $time[1]));
        if ($time[1] === null) unset($input['gp_min']);
        $outcome = gigpress_administration_entry_request($input)['outcome'];
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $outcome['show_id'] ?? 0), ARRAY_A);
        $checks['time_' . $kind] = $outcome['status'] === 'saved' && $row && $row['show_time'] === $time[2];
        if ($kind === 'uncommon') {
            $edit = array_merge($input, array('gpaction' => 'update', 'show_id' => $outcome['show_id']));
            $checks['unchanged_minute_17'] = gigpress_administration_entry_request($edit)['outcome']['status'] === 'saved';
            $html = gigpress_administration_entry_request(array(), true, array('gpaction' => 'edit', 'show_id' => $outcome['show_id']))['html'];
            $checks['minute_17_visible_selected'] = preg_match('/value="17" selected=[\'"]selected[\'"]/', $html) === 1;
        }
    }
    foreach (array('equal' => '2032-05-06', 'earlier' => '2032-05-05') as $kind => $end) {
        $outcome = gigpress_administration_entry_request(array_merge($request, array('show_multi' => '1', 'show_end_date' => $end)))['outcome'];
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $outcome['show_id'] ?? 0), ARRAY_A);
        $checks['end_' . $kind . '_semantics'] = $outcome['status'] === 'saved' && $row && $row['show_expire'] === $end && (int) $row['show_multi'] === 1;
    }
    $settings = get_option('gigpress_settings');
    foreach (array('12' => 0, '24' => 1) as $clock => $alternate) {
        update_option('gigpress_settings', array_merge($settings, array('alternate_clock' => $alternate)));
        $html = gigpress_administration_entry_request(array(), true)['html'];
        $checks['hour_domain_' . $clock] = preg_match_all('/<option value="(?:[01][0-9]|2[0-3])"[^>]*>/', substr($html, strpos($html, '<select name="gp_hh"'), strpos($html, '</select>', strpos($html, '<select name="gp_hh"')) - strpos($html, '<select name="gp_hh"'))) === 24;
        $checks['clock_labels_' . $clock] = $clock === 12 || $clock === '12' ? preg_match('/value="13"[^>]*>1 PM<\/option>/', $html) === 1 && preg_match('/value="00"[^>]*>12 AM<\/option>/', $html) === 1 : preg_match('/value="13"[^>]*>13<\/option>/', $html) === 1;
    }
    update_option('gigpress_settings', $settings);
    $html = gigpress_administration_entry_request(array(), true)['html'];
    $checks['locked_optional_time_label'] = strpos($html, 'Time (optional)') !== false && strpos($html, 'Not specified') !== false;
    $checks['locked_end_date_label'] = strpos($html, 'End date — last day of the event') !== false;
    $checks['existing_cutoff_help'] = strpos($html, 'existing daily cutoff') !== false;
    $start = strpos($html, '<select name="gp_min"');
    $minuteHtml = substr($html, $start, strpos($html, '</select>', $start) - $start);
    $checks['all_minutes_available'] = preg_match_all('/<option value="[0-5][0-9]"/', $minuteHtml) === 60;
    $checks['no_js_minutes_reachable'] = strpos($minuteHtml, 'disabled') === false;
    $checks['no_js_end_and_creation_reachable'] = strpos($html, ' hidden') === false && strpos($html, 'style="display:') === false && strpos($html, 'id="show_end_date"') !== false && strpos($html, 'id="venue_name"') !== false;
    preg_match_all('/<(?:input|select|textarea)\b[^>]*\bid="([^"]+)"[^>]*>/', $html, $controls, PREG_SET_ORDER);
    foreach ($controls as $control) {
        if (strpos($control[0], 'type="hidden"') !== false) continue;
        $checks['label_' . $control[1]] = strpos($html, 'for="' . $control[1] . '"') !== false;
    }
    $rejected = gigpress_administration_entry_request(array_merge($request, array('show_date' => '', 'show_multi' => '1', 'show_end_date' => '', 'gp_hh' => 'bad', 'gp_min' => 'bad')), true)['html'];
    preg_match_all('/href="#([^"]+)"/', $rejected, $links);
    foreach ($links[1] as $target) $checks['summary_target_' . $target] = strpos($rejected, 'id="' . $target . '"') !== false && strpos($rejected, 'id="' . $target . '-error"') !== false;
    preg_match_all('/aria-describedby="([^"]+)"/', $rejected, $descriptions);
    foreach ($descriptions[1] as $ids) foreach (explode(' ', $ids) as $id) $checks['description_target_' . $id] = strpos($rejected, 'id="' . $id . '"') !== false;
    $checks['invalid_fields_identified'] = substr_count($rejected, 'aria-invalid="true"') >= 4;
    $script = file_get_contents(WP_PLUGIN_DIR . '/gigpress/scripts/gigpress-admin.js');
    $checks['no_forced_blur_in_enhancement'] = strpos($script, '.blur(') === false;
    $checks['minute_enablement_enhancement'] = strpos($script, "prop('disabled'") !== false;
    $settings = get_option('gigpress_settings');
    $settings['welcome'] = 'yes';
    update_option('gigpress_settings', $settings);
    $welcome = gigpress_administration_entry_request(array(), true)['html'];
    $checks['welcome_dismissal_link_preserved'] = strpos($welcome, 'gpaction=killwelcome') !== false && strpos($welcome, '_gpwelcome_nonce=') !== false;
    gigpress_administration_entry_request(array(), true, array('gpaction' => 'killwelcome', '_gpwelcome_nonce' => 'invalid'));
    $checks['welcome_invalid_nonce_no_write'] = get_option('gigpress_settings') === $settings;
    $admin = get_current_user_id();
    wp_set_current_user(0);
    gigpress_administration_entry_request(array(), true, array('gpaction' => 'killwelcome', '_gpwelcome_nonce' => wp_create_nonce('gigpress-dismiss-welcome')));
    $checks['welcome_capability_no_write'] = get_option('gigpress_settings') === $settings;
    wp_set_current_user($admin);
    gigpress_administration_entry_request(array(), true, array('gpaction' => 'killwelcome', '_gpwelcome_nonce' => wp_create_nonce('gigpress-dismiss-welcome')));
    $checks['welcome_dismissal_saves_only_welcome'] = get_option('gigpress_settings') === array_merge($settings, array('welcome' => 'no'));
    update_option('gigpress_settings', $settings);
    $created = gigpress_administration_entry_request($request)['outcome'];
    $corrected = gigpress_administration_entry_request(array_merge($request, array(
        'gpaction' => 'update', 'show_id' => $created['show_id'],
        'show_date' => 'not-a-date', 'show_date_picker' => '2032-06-08', 'replace_show_date' => '1',
        'show_multi' => '1', 'show_end_date' => 'not-an-end-date',
        'show_end_date_picker' => '2032-06-09', 'replace_show_end_date' => '1',
        'gp_hh' => '00', 'gp_min' => '17', 'show_notes' => '  Corrected edit  '
    )), true)['html'];
    $saved = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $created['show_id']), ARRAY_A);
    $checks['corrected_update_stored_dates'] = $saved && $saved['show_date'] === '2032-06-08' && $saved['show_expire'] === '2032-06-09';
    $checks['corrected_update_renders_saved_start'] = preg_match('/type="date"[^>]*name="show_date"[^>]*value="2032-06-08"/', $corrected) === 1;
    $checks['corrected_update_renders_saved_end'] = preg_match('/type="date"[^>]*name="show_end_date"[^>]*value="2032-06-09"/', $corrected) === 1;
    $checks['corrected_update_removes_retry_controls'] = strpos($corrected, 'name="replace_show_date"') === false && strpos($corrected, 'name="replace_show_end_date"') === false;
    $checks['corrected_update_retains_edit_identity'] = strpos($corrected, 'name="gpaction" value="update"') !== false && strpos($corrected, 'name="show_id" value="' . $created['show_id'] . '"') !== false;
    $checks['corrected_update_renders_normalized_notes'] = strpos($corrected, '>Corrected edit</textarea>') !== false;
    return $checks;
}

function gigpress_administration_entry_snapshot() {
    global $wpdb;
    $snapshot = array('settings' => get_option('gigpress_settings'));
    foreach (array('shows', 'artists', 'venues', 'tours') as $kind) $snapshot[$kind] = $wpdb->get_results('SELECT * FROM ' . constant('GIGPRESS_' . strtoupper($kind)) . ' ORDER BY 1', ARRAY_A);
    $snapshot['posts'] = $wpdb->get_results('SELECT ID, post_title, post_content, post_status FROM ' . $wpdb->posts . ' ORDER BY ID', ARRAY_A);
    return $snapshot;
}

function gigpress_administration_entry_input_matches($html, $field, $value) {
    return strpos($html, 'name="' . $field . '"') !== false && strpos($html, 'value="' . esc_attr($value) . '"') !== false;
}

function gigpress_administration_entry_recovery_checks($request, $bad, $hostile) {
    global $wpdb;
    $checks = array();
    foreach (array('show_date' => array('', '２０３２-05-06', '2032-5-6', array('2032-05-06')), 'gp_hh' => array('24', array('na')),
        'gp_min' => array('60', array('00')), 'show_artist_id' => array('abc', array('new')), 'show_venue_id' => array('-1', '999999'),
        'show_tour_id' => array('abc', array('new')), 'show_related' => array('bogus', array('new')),
        'show_status' => array('bogus', array('active')), 'show_multi' => array('yes', array('1')), 'gpaction' => array('delete', array('add')),
        'show_related_date' => array('bogus', array('now'))) as $key => $values) {
        foreach ($values as $i => $value) {
            $before = gigpress_administration_entry_snapshot();
            $outcome = gigpress_administration_entry_request(array_merge($request, array($key => $value)))['outcome'];
            $checks['reject_' . $key . '_' . $i] = ($outcome['status'] ?? '') === 'invalid' && $before === gigpress_administration_entry_snapshot();
        }
    }
    $empty = gigpress_administration_entry_request(array('gpaction' => 'add'), true);
    $checks['empty_form_readable_errors'] = substr_count($empty['html'], 'href="#') >= 3 && ($empty['outcome']['status'] ?? '') === 'invalid';
    $legacy = $request;
    unset($legacy['show_date']);
    $legacy['gp_mm'] = '02'; $legacy['gp_dd'] = '31';
    $checks['invalid_legacy_calendar'] = gigpress_administration_entry_request($legacy)['outcome']['status'] === 'invalid';
    $legacy['gp_dd'] = array('06');
    $checks['nested_legacy_calendar'] = gigpress_administration_entry_request($legacy)['outcome']['status'] === 'invalid';
    foreach (array('empty' => '', 'malformed' => 'not a date', 'valid' => '2032-05-09') as $name => $picker) {
        $replacement = gigpress_administration_entry_request(array_merge($request, array('show_date' => 'invalid', 'replace_show_date' => '1', 'show_date_picker' => $picker)))['outcome'];
        $checks['explicit_replacement_' . $name] = ($replacement['status'] === ($name === 'valid' ? 'saved' : 'invalid'));
    }
    $ignored = gigpress_administration_entry_request(array_merge($request, array('show_date' => 'invalid', 'show_date_picker' => '2032-05-09')))['outcome'];
    $checks['unchecked_picker_never_overrides_raw'] = $ignored['status'] === 'invalid';
    $received = array_merge($bad, array('artist_url' => $hostile, 'venue_address' => $hostile, 'venue_state' => $hostile,
        'venue_postal_code' => $hostile, 'venue_url' => $hostile, 'venue_phone' => $hostile, 'show_price' => $hostile,
        'show_tix_url' => $hostile, 'show_tix_phone' => $hostile, 'show_external_url' => $hostile,
        'show_date_picker' => '', 'replace_show_date' => '1'));
    foreach (array('artist_name','artist_url','venue_name','venue_address','venue_city','venue_state','venue_postal_code','venue_url','venue_phone','tour_name','show_related_title','show_price','show_tix_url','show_tix_phone','show_external_url') as $field) {
        $value = $field . ': ' . $hostile;
        $received[$field] = $value;
    }
    $recovered = gigpress_administration_entry_request($received, true);
    foreach ($received as $key => $value) $checks['raw_' . $key] = ($recovered['outcome']['raw_state'][$key] ?? null) === (string) $value;
    foreach (array('artist_name','artist_url','venue_name','venue_address','venue_city','venue_state','venue_postal_code','venue_url','venue_phone','tour_name','show_related_title','show_price','show_tix_url','show_tix_phone','show_external_url') as $field) {
        $value = $received[$field];
        $checks['escaped_' . $field] = gigpress_administration_entry_input_matches($recovered['html'], $field, $value);
        // Another control contains the expected value: only the named control may satisfy the assertion.
        $other = '<input name="another_' . $field . '" value="' . esc_attr($value) . '" />';
        foreach (array('blank' => '', 'corrupt' => 'different value', 'unescaped' => $value) as $kind => $replacement) {
            $fixture = '<input name="' . $field . '" value="' . $replacement . '" />' . $other;
            $checks['escaping_rejects_' . $kind . '_' . $field] = !gigpress_administration_entry_input_matches($fixture, $field, $value);
        }
    }

    $created = gigpress_administration_entry_request($request)['outcome'];
    $id = $created['show_id'];
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $id), ARRAY_A);
    $edit = array_merge($request, array('gpaction' => 'update', 'show_id' => (string) $id));
    $checks['unchanged_update_succeeds'] = gigpress_administration_entry_request($edit)['outcome']['status'] === 'saved';
    $badEdit = array_merge($edit, array('show_date' => 'invalid'));
    $editRecovery = gigpress_administration_entry_request($badEdit, true);
    $checks['edit_identity_retained'] = ($editRecovery['outcome']['show_id'] ?? 0) === $id && strpos($editRecovery['html'], 'name="gpaction" value="update"') !== false && strpos($editRecovery['html'], 'name="show_id" value="' . $id . '"') !== false;
    $missing = gigpress_administration_entry_request(array_merge($edit, array('show_id' => '999999')))['outcome'];
    $checks['absent_edit_target_fails'] = $missing['status'] !== 'saved';
    foreach (array('abc', array('1')) as $i => $value) $checks['invalid_edit_id_' . $i] = gigpress_administration_entry_request(array_merge($edit, array('show_id' => $value)))['outcome']['status'] === 'invalid';
    $copy = gigpress_administration_entry_request($request)['outcome'];
    $checks['copy_source_immutable'] = $copy['show_id'] !== $id && $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $id), ARRAY_A) === $row;
    // A deliberately invalid historical row needs MariaDB's invalid-date fixture mode.
    $sqlMode = $wpdb->get_var('SELECT @@SESSION.sql_mode');
    $wpdb->query("SET SESSION sql_mode = 'ALLOW_INVALID_DATES'");
    $wpdb->query($wpdb->prepare('UPDATE ' . GIGPRESS_SHOWS . ' SET show_date = %s WHERE show_id = %d', '2032-02-31', $id));
    $view = gigpress_administration_entry_request(array(), true, array('gpaction' => 'edit', 'show_id' => $id));
    $checks['impossible_stored_date_editable'] = strpos($view['html'], 'value="2032-02-31"') !== false && preg_match('/type="text"[^>]*name="show_date"/', $view['html']) === 1;
    $wpdb->update(GIGPRESS_SHOWS, array('show_date' => $row['show_date']), array('show_id' => $id));
    $wpdb->query($wpdb->prepare('SET SESSION sql_mode = %s', $sqlMode));
    $before = gigpress_administration_entry_snapshot();
    $adminId = get_current_user_id();
    wp_set_current_user(0);
    $denied = gigpress_administration_entry_request($request)['outcome'];
    wp_set_current_user($adminId);
    $checks['capability_blocks_without_writes'] = $denied['status'] === 'blocked' && $before === gigpress_administration_entry_snapshot();
    require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-crud.php';
    $checks['invalid_nonce_blocks_without_writes'] = gigpress_upgrade_preservation_nonce_blocks_request('gigpress_add_show', $request) && $before === gigpress_administration_entry_snapshot();
    $GLOBALS['gigpress_db_bootstrap_result'] = array('status' => 'blocked', 'code' => 'unsafe_metadata');
    $blocked = gigpress_administration_entry_request($bad, true);
    $checks['readiness_blocks_without_writes'] = $blocked['outcome']['status'] === 'blocked' && $before === gigpress_administration_entry_snapshot() && strpos($blocked['html'], 'site administrator') !== false;
    unset($GLOBALS['gigpress_db_bootstrap_result']);

    $new = array_merge($bad, array('show_date' => '2032-05-06', 'show_end_date' => '2032-05-08', 'artist_name' => 'Retry Artist', 'venue_name' => 'Retry Venue', 'venue_city' => 'Vienna', 'tour_name' => 'Retry Tour', 'show_related_title' => 'Retry Post'));
    foreach (array('artist' => GIGPRESS_ARTISTS, 'venue' => GIGPRESS_VENUES, 'tour' => GIGPRESS_TOURS, 'post' => $wpdb->posts, 'show' => GIGPRESS_SHOWS) as $kind => $table) {
        $prior = gigpress_administration_entry_snapshot();
        $filter = function ($sql) use ($table) { return preg_match('/^INSERT\\s+INTO\\s+`?' . preg_quote($table, '/') . '`?\\s/i', $sql) ? 'SELECT * FROM gigpress_intentionally_missing_failure_table' : $sql; };
        $suppressed = $wpdb->suppress_errors(true);
        add_filter('query', $filter);
        $failed = gigpress_administration_entry_request($new)['outcome'];
        remove_filter('query', $filter);
        $wpdb->suppress_errors($suppressed);
        $checks['failure_' . $kind . '_truthful'] = $failed['status'] === 'failed' && !empty($failed['system_errors']) && !isset($failed['show_id']);
        $checks['failure_' . $kind . '_sticky_unchanged'] = get_option('gigpress_settings') === $prior['settings'];
        $retry = $failed['raw_state'];
        $rejectedAgain = gigpress_administration_entry_request(array_merge($retry, array('show_date' => 'invalid')))['outcome'];
        $checks['created_ids_survive_rejected_retry_' . $kind] = $rejectedAgain['created_ids'] === $failed['created_ids'];
        $beforeRetry = gigpress_administration_entry_snapshot();
        $saved = gigpress_administration_entry_request($retry)['outcome'];
        $afterRetry = gigpress_administration_entry_snapshot();
        $checks['retry_' . $kind . '_saved'] = $saved['status'] === 'saved';
        foreach ($failed['created_ids'] as $entity => $createdId) {
            $plural = array('artist' => 'artists', 'venue' => 'venues', 'tour' => 'tours', 'post' => 'posts')[$entity];
            $checks['retry_' . $kind . '_reuses_' . $entity] = count($beforeRetry[$plural]) === count($afterRetry[$plural]) && (int) ($saved['raw_state'][array('artist' => 'show_artist_id', 'venue' => 'show_venue_id', 'tour' => 'show_tour_id', 'post' => 'show_related')[$entity]] ?? 0) === $createdId;
        }
    }
    return $checks;
}

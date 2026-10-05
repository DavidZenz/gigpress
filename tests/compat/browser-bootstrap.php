<?php
/* Container-only synthetic fixture and real HTTP client. Never loaded by the plugin. */
error_reporting(E_ALL);
ini_set('display_errors', '0');
$errors = array();
set_error_handler(function ($severity, $message, $file, $line) use (&$errors) {
    if ($severity & E_ALL) $errors[] = array('severity' => $severity, 'message' => $message, 'file' => basename($file), 'line' => $line);
    return true;
});
$mode = getenv('COMPAT_BROWSER_MODE') ?: 'seed';
$base = getenv('COMPAT_BROWSER_URL');
if (!preg_match('~\Ahttp://127\.0\.0\.1:[0-9]+\z~', (string) $base)) throw new RuntimeException('Loopback URL required');
$_SERVER['HTTP_HOST'] = parse_url($base, PHP_URL_HOST) . ':' . parse_url($base, PHP_URL_PORT);
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_PORT'] = (string) parse_url($base, PHP_URL_PORT);
if ($mode === 'seed' || $mode === 'public-seed') define('WP_INSTALLING', true);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
add_filter('pre_wp_mail', function () { return true; });

function browser_snapshot() {
    global $wpdb;
    wp_cache_flush();
    $snapshot = array('settings' => get_option('gigpress_settings'));
    foreach (array('SHOWS','ARTISTS','VENUES','TOURS') as $table)
        $snapshot[strtolower($table)] = $wpdb->get_results('SELECT * FROM ' . constant('GIGPRESS_' . $table) . ' ORDER BY 1', ARRAY_A);
    $snapshot['posts'] = $wpdb->get_results('SELECT ID, post_title, post_status, post_content FROM ' . $wpdb->posts . ' ORDER BY ID', ARRAY_A);
    return $snapshot;
}

function public_fixture_http($path) {
    global $base;
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) throw new RuntimeException('Owned HTTP path required');
    $handle = curl_init($base . $path);
    curl_setopt_array($handle, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECT_TO => array('127.0.0.1:' . parse_url($base, PHP_URL_PORT) . ':127.0.0.1:80'),
        CURLOPT_PROXY => '', CURLOPT_HTTPHEADER => array('Cache-Control: no-cache')));
    $body = curl_exec($handle);
    if ($body === false) throw new RuntimeException('Fixture HTTP transport failed: ' . curl_error($handle));
    $result = array('body' => $body, 'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
        'content_type' => (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE));
    unset($handle);
    return $result;
}

function public_fixture_write_override($directory, $template, $source, $location) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) return false;
    $source = str_replace('__LOCATION__', $location, $source);
    return file_put_contents($directory . '/' . $template . '.php', $source, LOCK_EX) !== false;
}

function browser_http($path, $post = null, $jar = 'admin') {
    global $base;
    if (str_starts_with($path, $base)) $path = substr($path, strlen($base));
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) throw new RuntimeException('Owned HTTP path required');
    $cookie = '/tmp/gigpress-browser-' . $jar . '.cookies';
    $handle = curl_init($base . $path);
    curl_setopt_array($handle, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_CONNECT_TO => array('127.0.0.1:' . parse_url($base, PHP_URL_PORT) . ':127.0.0.1:80'),
        CURLOPT_PROXY => '', CURLOPT_REFERER => $base . '/wp-admin/admin.php?page=gigpress-settings'));
    if ($post !== null) curl_setopt_array($handle, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)));
    $html = curl_exec($handle);
    if ($html === false) throw new RuntimeException('Fixture HTTP transport failed: ' . curl_error($handle));
    $result = array('html' => $html, 'status' => curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'url' => curl_getinfo($handle, CURLINFO_EFFECTIVE_URL));
    curl_close($handle);
    return $result;
}

function browser_dom($html) {
    $dom = new DOMDocument(); $prior = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors(); libxml_use_internal_errors($prior);
    return new DOMXPath($dom);
}

function browser_form($html, $query) {
    $xpath = browser_dom($html); $forms = $xpath->query($query);
    if ($forms->length !== 1) throw new RuntimeException('Expected one real form: ' . $query);
    $values = array();
    foreach ($xpath->query('.//input | .//select | .//textarea', $forms->item(0)) as $control) {
        $name = $control->getAttribute('name'); $type = $control->getAttribute('type');
        if (!$name || $control->hasAttribute('disabled') || in_array($type, array('submit','button'), true)) continue;
        if (in_array($type, array('checkbox','radio'), true) && !$control->hasAttribute('checked')) continue;
        $value = $control->nodeName === 'textarea' ? $control->textContent : $control->getAttribute('value');
        if ($control->nodeName === 'select') {
            $options = $xpath->query('./option[@selected]', $control);
            if (!$options->length) $options = $xpath->query('./option', $control);
            if (!$options->length) continue;
            $value = $options->item(0)->getAttribute('value');
        }
        if (preg_match('/\A([^\[]+)\[([^\]]*)\]\z/', $name, $parts)) {
            if ($parts[2] === '') $values[$parts[1]][] = $value;
            else $values[$parts[1]][$parts[2]] = $value;
        } else $values[$name] = $value;
    }
    return $values;
}

function browser_login($user, $jar) {
    browser_http('/wp-login.php', null, $jar);
    $response = browser_http('/wp-login.php', array('log' => $user, 'pwd' => getenv('COMPAT_BROWSER_PASSWORD'),
        'wp-submit' => 'Log In', 'redirect_to' => $GLOBALS['base'] . '/wp-admin/', 'testcookie' => '1'), $jar);
    return $response['status'] === 200 && strpos($response['html'], 'wp-admin-bar-my-account') !== false;
}

function browser_entry() {
    global $wpdb;
    $checks = array('authenticated_admin_login' => browser_login('browser-admin','admin'));
    $page = '/wp-admin/admin.php?page=gigpress/gigpress.php';
    $form = browser_http($page);
    $post = browser_form($form['html'], '//form[.//input[@name="gpaction" and @value="add"]]');
    $checks['real_rendered_nonce'] = !empty($post['_wpnonce']);
    $checks['native_date_control'] = browser_dom($form['html'])->query('//input[@name="show_date" and @type="date"]')->length === 1;
    $seed = get_option('gigpress_browser_fixture');
    $post = array_merge($post, array('show_date' => '2032-05-06', 'gp_hh' => 'na', 'show_artist_id' => $seed['artist'],
        'show_venue_id' => $seed['venue'], 'show_tour_id' => '0', 'show_related' => '0', 'show_notes' => 'HTTP native entry'));
    unset($post['show_multi'], $post['replace_show_date']);
    $before = browser_snapshot();
    $saved = browser_http($page, $post);
    $after = browser_snapshot();
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_notes = %s ORDER BY show_id DESC LIMIT 1', 'HTTP native entry'), ARRAY_A);
    $checks['post_success_and_one_row'] = $saved['status'] === 200 && count($after['shows']) === count($before['shows']) + 1 && strpos($saved['html'], 'Edit saved show') !== false;
    $checks['independent_date_time_expiration'] = $row && $row['show_date'] === '2032-05-06' && $row['show_time'] === '00:00:01' && $row['show_expire'] === '2032-05-06' && (int) $row['show_multi'] === 0;
    $checks['relationships_and_other_records_preserved'] = $row && (int) $row['show_artist_id'] === $seed['artist'] && (int) $row['show_venue_id'] === $seed['venue'] && $before['artists'] === $after['artists'] && $before['venues'] === $after['venues'] && $before['tours'] === $after['tours'] && $before['posts'] === $after['posts'];
    $xpath = browser_dom($saved['html']); $edit = null;
    foreach ($xpath->query('//a[@href]') as $link) if (trim($link->textContent) === 'Edit saved show') $edit = $link->getAttribute('href');
    $checks['issued_saved_edit_identity'] = $row && $edit && strpos($edit, 'show_id=' . $row['show_id']) !== false;
    $edited = $edit ? browser_http($edit) : array('html' => '', 'status' => 0);
    $checks['followed_edit_with_saved_identity'] = $row && $edited['status'] === 200 && browser_dom($edited['html'])->query('//input[@name="show_id" and @value="' . $row['show_id'] . '"]')->length === 1;
    $checks['fresh_add_and_view_list'] = strpos($saved['html'], 'name="gpaction" value="add"') !== false && strpos($saved['html'], 'View list') !== false;
    return $checks;
}

function browser_settings() {
    $checks = array('authenticated_admin_login' => browser_login('browser-admin','admin'));
    $page = '/wp-admin/admin.php?page=gigpress-settings';
    $form = browser_http($page);
    $post = browser_form($form['html'], '//form[@action="options.php"]');
    $before = browser_snapshot(); $baseline = $before['settings'];
    $checks['real_settings_fields'] = !empty($post['_wpnonce']) && ($post['option_page'] ?? '') === 'gigpress' && ($post['action'] ?? '') === 'update';
    $checks['explicit_unchecked_flags'] = isset($post['gigpress_settings']['relatedlink_date']) && $post['gigpress_settings']['relatedlink_date'] === '0';
    $post['gigpress_settings']['artist_label'] = 'HTTP Performers';
    $post['gigpress_settings']['default_date'] = '1900-01-01';
    $post['gigpress_settings']['db_version'] = '0';
    $post['gigpress_settings']['unknown_nested'] = array('forged' => true);
    $expected = $baseline; $expected['artist_label'] = 'HTTP Performers';
    $response = browser_http('/wp-admin/options.php', $post);
    $after = browser_snapshot();
    $checks['actual_options_post_saved'] = $response['status'] === 200 && strpos($response['url'], 'settings-updated=true') !== false && $after['settings'] === $expected;
    $reload = browser_http($page);
    $values = browser_form($reload['html'], '//form[@action="options.php"]');
    $checks['reload_editable_choice'] = $values['gigpress_settings']['artist_label'] === 'HTTP Performers';
    foreach (array('country_view','related_position','output_schema_json','related_category') as $key)
        $checks['unchanged_unknown_choice_' . $key] = $values['gigpress_settings'][$key] === (string) $baseline[$key];
    $checks['protected_unknown_falsey_exact'] = $after['settings'] === $expected;
    foreach (array('shows','artists','venues','tours','posts') as $table) $checks['no_other_write_' . $table] = $before[$table] === $after[$table];
    $invalid = $values; $invalid['_wpnonce'] = 'invalid'; $invalid['gigpress_settings']['artist_label'] = 'Forbidden nonce label';
    $denied = browser_http('/wp-admin/options.php', $invalid);
    $checks['invalid_nonce_denied_no_snapshot_change'] = $denied['status'] === 403 && browser_snapshot() === $after;
    $checks['real_subscriber_login'] = browser_login('browser-subscriber','subscriber');
    $unauthorized = browser_http('/wp-admin/options.php', $values, 'subscriber');
    $checks['unauthorized_post_denied_no_snapshot_change'] = $unauthorized['status'] === 403 && browser_snapshot() === $after;
    $unchecked = $values;
    $flags = array('alternate_clock','display_country','artist_link','target_blank','autocreate_post','category_exclude','relatedlink_date','relatedlink_city','relatedlink_notes','rss_head','display_subscriptions','load_jquery','disable_css','disable_js');
    foreach ($flags as $flag) {
        $unchecked['gigpress_settings'][$flag] = '0';
        if (!empty($expected[$flag])) $expected[$flag] = 0;
    }
    browser_http('/wp-admin/options.php', $unchecked);
    $checks['actual_unchecked_save_preserves_other_values'] = browser_snapshot()['settings'] === $expected;
    return $checks;
}

function browser_guards() {
    global $wpdb;
    $checks = array('authenticated_admin_login' => browser_login('browser-admin','admin'));
    $page = '/wp-admin/admin.php?page=gigpress-shows&scope=all&sort=asc&limit=10';
    $form = browser_http($page);
    $xpath = browser_dom($form['html']); $ids = array();
    foreach ($xpath->query('//input[@name="show_id[]" and @type="checkbox"]') as $node) $ids[] = $node->getAttribute('value');
    $checks['explicit_rendered_selection_available'] = count($ids) === 10 && count(array_unique($ids)) === 10;
    if (count($ids) < 4) return $checks;
    $post = browser_form($form['html'], '//form[.//input[@name="trash_stage"]]');
    $post['show_id'] = array($ids[1]); $post['trash_single_id'] = $ids[0];
    $baseline = browser_snapshot();
    $preview = browser_http($page, $post);
    $confirm = browser_form($preview['html'], '//form[.//input[@name="trash_token"]]');
    $checks['single_preview_no_write_and_exact_clicked_id'] = browser_snapshot() === $baseline && ($confirm['show_id'] ?? null) === array($ids[0]) && strpos($preview['html'], '1 selected show') !== false;
    $checks['issued_intent_and_nonces'] = !empty($confirm['trash_token']) && !empty($confirm['_wpnonce']) && !empty($confirm['trash_cancel_nonce']);
    $cancel = array_merge($confirm, array('trash_stage' => 'cancel'));
    $response = browser_http($page, $cancel);
    $checks['single_cancel_no_write_and_text'] = browser_snapshot() === $baseline && strpos($response['html'], 'Canceled. No shows were changed') !== false;
    $confirm['trash_stage'] = 'confirm'; browser_http($page, $confirm);
    $checks['cancel_consumes_intent'] = browser_snapshot() === $baseline;
    $direct = array_merge($post, array('trash_stage' => 'confirm', 'trash_token' => ''));
    browser_http($page, $direct);
    $checks['direct_bypass_no_write'] = browser_snapshot() === $baseline;
    $preview = browser_http($page, $post);
    $confirm = browser_form($preview['html'], '//form[.//input[@name="trash_token"]]'); $confirm['trash_stage'] = 'confirm';
    $invalid = array_merge($confirm, array('_wpnonce' => 'invalid')); browser_http($page, $invalid);
    $checks['invalid_confirm_nonce_no_write'] = browser_snapshot() === $baseline;
    $tampered = array_merge($confirm, array('show_id' => array($ids[1]))); browser_http($page, $tampered);
    $checks['tampered_selection_no_write'] = browser_snapshot() === $baseline;
    browser_http($page . '&' . http_build_query($confirm));
    $checks['get_confirm_no_write'] = browser_snapshot() === $baseline;
    $checks['subscriber_login'] = browser_login('browser-subscriber','subscriber');
    $unauthorized = browser_http($page, $confirm, 'subscriber');
    $checks['unauthorized_confirm_no_write'] = $unauthorized['status'] === 403 && browser_snapshot() === $baseline;
    $response = browser_http($page, $confirm);
    $expected = $baseline;
    foreach ($expected['shows'] as &$row) if ($row['show_id'] === $ids[0]) $row['show_status'] = 'deleted'; unset($row);
    $checks['single_confirm_status_only_exact_snapshot'] = browser_snapshot() === $expected && strpos($response['html'], '1 shows moved to trash') !== false;
    browser_http($page, $confirm); $checks['single_replay_no_write'] = browser_snapshot() === $expected;
    $post = browser_form(browser_http($page)['html'], '//form[.//input[@name="trash_stage"]]');
    $post['show_id'] = array($ids[1],$ids[2]);
    $preview = browser_http($page, $post);
    $bulk = browser_form($preview['html'], '//form[.//input[@name="trash_token"]]');
    $checks['bulk_preview_exact_count_ids_no_write'] = ($bulk['show_id'] ?? null) === array($ids[1],$ids[2]) && strpos($preview['html'], '2 selected show') !== false && browser_snapshot() === $expected;
    browser_http($page, array_merge($bulk,array('trash_stage'=>'cancel')));
    $checks['bulk_cancel_no_write'] = browser_snapshot() === $expected;
    $preview = browser_http($page, $post); $bulk = browser_form($preview['html'], '//form[.//input[@name="trash_token"]]');
    $bulk['trash_stage'] = 'confirm'; $response = browser_http($page, $bulk);
    foreach ($expected['shows'] as &$row) if (in_array($row['show_id'],array($ids[1],$ids[2]),true)) $row['show_status'] = 'deleted'; unset($row);
    $checks['bulk_confirm_selected_only_status_snapshot'] = browser_snapshot() === $expected && strpos($response['html'], '2 shows moved to trash') !== false;
    // Actual unauthorized/nonce-invalid show submission uses the rendered add form too.
    $entryPage = '/wp-admin/admin.php?page=gigpress/gigpress.php';
    $entry = browser_form(browser_http($entryPage)['html'], '//form[.//input[@name="gpaction" and @value="add"]]');
    $entry['show_notes'] = 'Forbidden HTTP show'; $entry['_wpnonce'] = 'invalid';
    $denied = browser_http($entryPage,$entry);
    // Admin output precedes check_admin_referer here, so core's explicit denial can carry HTTP 200.
    $checks['invalid_show_nonce_no_write'] = in_array($denied['status'], array(200,403), true)
        && strpos($denied['html'], 'link you followed has expired') !== false && browser_snapshot() === $expected;
    $entry = browser_form(browser_http($entryPage)['html'], '//form[.//input[@name="gpaction" and @value="add"]]');
    $denied = browser_http($entryPage,$entry,'subscriber');
    $checks['unauthorized_show_no_write'] = $denied['status'] === 403 && browser_snapshot() === $expected;
    // Use the nonce delivered to the real authenticated administrator page.
    $artist_page = browser_http('/wp-admin/admin.php?page=gigpress-artists');
    $venue_page = browser_http('/wp-admin/admin.php?page=gigpress-venues');
    $checks['entity_pages_render_dependency_guard'] = $artist_page['status'] === 200 && $venue_page['status'] === 200
        && strpos($artist_page['html'], 'gigpress-artist-sort') !== false && strpos($venue_page['html'], 'Browser Hall') !== false;
    preg_match('/var gigpressAdmin = (\{[^\n]+\});/', $artist_page['html'], $nonce_match);
    $config = json_decode($nonce_match[1] ?? '{}', true);
    $checks['artist_order_rendered_action_nonce'] = !empty($config['reorderNonce']);
    $order_before = browser_snapshot();
    $artist_ids = array_column($order_before['artists'], 'artist_id');
    if (count($artist_ids) < 2 || empty($config['reorderNonce'])) return $checks + array('artist_order_http_fixture_available' => false);
    $order = array('action' => 'gigpress_reorder_artists', '_ajax_nonce' => $config['reorderNonce'], 'artist' => array($artist_ids[1], $artist_ids[0], $artist_ids[1]));
    $denied = browser_http('/wp-admin/admin-ajax.php', $order, 'subscriber');
    $checks['artist_order_http_subscriber_no_write'] = $denied['status'] === 400 && browser_snapshot() === $order_before;
    $denied = browser_http('/wp-admin/admin-ajax.php', array_merge($order, array('_ajax_nonce' => 'invalid')));
    $checks['artist_order_http_invalid_nonce_no_write'] = $denied['status'] === 400 && browser_snapshot() === $order_before;
    $denied = browser_http('/wp-admin/admin-ajax.php?' . http_build_query($order));
    $checks['artist_order_http_get_no_write'] = $denied['status'] === 400 && browser_snapshot() === $order_before;
    $response = browser_http('/wp-admin/admin-ajax.php', $order);
    $json = json_decode($response['html'], true);
    $order_expected = $order_before;
    foreach ($order_expected['artists'] as &$row) {
        if ($row['artist_id'] === $artist_ids[1]) $row['artist_order'] = '0';
        if ($row['artist_id'] === $artist_ids[0]) $row['artist_order'] = '1';
    } unset($row);
    $checks['artist_order_http_json_exact_subset_snapshot'] = $response['status'] === 200 && ($json['success'] ?? false) === true && browser_snapshot() === $order_expected;
    return $checks;
}

if ($mode === 'seed') {
    if (is_blog_installed()) throw new RuntimeException('Seed requires a fresh disposable database');
    wp_install('Synthetic GigPress Browser Fixture', 'browser-admin', 'browser-admin@example.test', true, '', getenv('COMPAT_BROWSER_PASSWORD'));
    update_option('home', $base); update_option('siteurl', $base);
    $admin = get_user_by('login', 'browser-admin'); wp_set_current_user($admin->ID);
    $subscriber = wp_create_user('browser-subscriber', getenv('COMPAT_BROWSER_PASSWORD'), 'browser-subscriber@example.test');
    (new WP_User($subscriber))->set_role('subscriber');
    $active = activate_plugin('gigpress/gigpress.php');
    if (is_wp_error($active)) throw new RuntimeException($active->get_error_message());
    require_once WP_PLUGIN_DIR . '/gigpress/admin/db.php';
    $ready = gigpress_db_bootstrap();
    if (($ready['status'] ?? '') !== 'ready') throw new RuntimeException('Fixture database not ready');
    $settings = get_option('gigpress_settings');
    $settings = array_replace($settings, array('welcome' => 'no', 'default_date' => '2032-05-06', 'default_time' => '00:00:01',
        'unknown_nested' => array('false' => false, 'zero' => 0, 'empty' => '', 'null' => null), 'unknown_false' => false,
        'relatedlink_date' => false, 'relatedlink_city' => '', 'rss_limit' => 0, 'country_view' => 'synthetic-region',
        'related_position' => 'synthetic-position', 'output_schema_json' => 'synthetic-schema', 'related_category' => 'retired-synthetic'));
    update_option('gigpress_settings', $settings);
    $wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => 'Browser Band', 'artist_alpha' => 'browser band', 'artist_url' => ''));
    $artist = (int) $wpdb->insert_id;
    foreach (array(71, 72) as $order) $wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => 'Browser order ' . $order, 'artist_alpha' => 'browser order ' . $order, 'artist_url' => '', 'artist_order' => $order));
    $wpdb->insert(GIGPRESS_VENUES, array('venue_name' => 'Browser Hall', 'venue_city' => 'Vienna', 'venue_country' => 'AT'));
    $venue = (int) $wpdb->insert_id;
    $wpdb->insert(GIGPRESS_TOURS, array('tour_name' => 'Browser Tour', 'tour_status' => 'active')); $tour = (int) $wpdb->insert_id;
    $ids = array();
    for ($i = 0; $i < 26; $i++) {
        $wpdb->insert(GIGPRESS_SHOWS, array('show_date' => '2032-05-06', 'show_expire' => '2032-05-06', 'show_time' => '00:00:01',
            'show_artist_id' => $artist, 'show_venue_id' => $venue, 'show_tour_id' => $tour, 'show_status' => 'active', 'show_notes' => 'Synthetic browser show ' . $i));
        $ids[] = (int) $wpdb->insert_id;
    }
    update_option('gigpress_browser_fixture', array('artist' => $artist, 'venue' => $venue, 'tour' => $tour, 'ids' => $ids));
    $mu = ABSPATH . 'wp-content/mu-plugins'; if (!is_dir($mu)) mkdir($mu);
    file_put_contents($mu . '/gigpress-browser-errors.php', '<?php error_reporting(E_ALL); set_error_handler(function($s,$m,$f,$l) { if (($s & E_ALL) && strpos($f,"/gigpress/") !== false) file_put_contents("/tmp/gigpress-browser-errors.log", json_encode(array("severity"=>$s,"message"=>$m,"file"=>basename($f),"line"=>$l))."\n", FILE_APPEND); return false; }); register_shutdown_function(function() { $e=error_get_last(); if ($e && in_array($e["type"],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR),true)) file_put_contents("/tmp/gigpress-browser-errors.log",json_encode($e)."\n",FILE_APPEND); });');
    $checks = array('plugin_active' => is_plugin_active('gigpress/gigpress.php'), 'readiness' => $ready['status'] === 'ready', 'synthetic_rows' => count($ids) === 26, 'exact_wp' => $wp_version === getenv('COMPAT_EXPECTED_WP_VERSION'));
} elseif ($mode === 'public-seed') {
    if (is_blog_installed()) throw new RuntimeException('Public seed requires a fresh disposable database');
    wp_install('Synthetic GigPress Public Fixture', 'public-admin', 'public-admin@example.test', true, '', getenv('COMPAT_BROWSER_PASSWORD'));
    update_option('home', $base); update_option('siteurl', $base);
    $checks = array('fresh_public_site_installed' => is_blog_installed(), 'exact_wp' => $wp_version === getenv('COMPAT_EXPECTED_WP_VERSION'));
} elseif ($mode === 'public-probe-prepare') {
    update_option('home', 'http://127.0.0.1'); update_option('siteurl', 'http://127.0.0.1');
    $checks = array('internal_loopback_canonical_url' => home_url('/') === 'http://127.0.0.1/',
        'siteurl_internal_loopback' => site_url('/') === 'http://127.0.0.1/');
} elseif ($mode === 'public-pages') {
    global $wpdb, $gpo;
    if (!is_plugin_active('gigpress/gigpress.php') || !defined('GIGPRESS_SHOWS')) throw new RuntimeException('Migrated public fixture is not active');
    update_option('home', $base); update_option('siteurl', $base);
    $settings = array_replace((array) get_option('gigpress_settings'), array(
        'buy_tickets_label' => 'Saved Label', 'display_subscriptions' => 1, 'display_country' => 1,
        'relatedlink_notes' => 1, 'disable_css' => 0, 'disable_js' => 0));
    update_option('gigpress_settings', $settings); $gpo = $settings;

    $fixturePath = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/public-publishing/overrides.php';
    $fixture = is_readable($fixturePath) ? require $fixturePath : null;
    if (!is_array($fixture) || !isset($fixture['files'], $fixture['structural'])) throw new RuntimeException('Public override fixture is unavailable');
    $themeRoot = WP_CONTENT_DIR . '/themes';
    $child = $themeRoot . '/gigpress-public-child/gigpress-templates';
    $parent = $themeRoot . '/gigpress-public-parent/gigpress-templates';
    $emptyChild = $themeRoot . '/gigpress-public-empty-child/gigpress-templates';
    $emptyParent = $themeRoot . '/gigpress-public-empty-parent/gigpress-templates';
    $complete = $themeRoot . '/gigpress-public-complete/gigpress-templates';
    $mixedChild = $themeRoot . '/gigpress-public-mixed-child/gigpress-templates';
    $mixedParent = $themeRoot . '/gigpress-public-mixed-parent/gigpress-templates';
    $mixedContent = WP_CONTENT_DIR . '/gigpress-templates';
    foreach (array($complete, $mixedChild, $mixedParent, $mixedContent) as $path) {
        if (is_dir($path) && $path === $mixedContent && glob($path . '/*.php')) throw new RuntimeException('Public fixture wp-content override path is already occupied');
    }
    $writes = array();
    foreach ($fixture['structural'] as $template) $writes[] = public_fixture_write_override($child, $template, $fixture['files'][$template], 'child');
    foreach ($fixture['structural'] as $template) $writes[] = public_fixture_write_override($parent, $template, $fixture['files'][$template], 'parent');
    foreach ($fixture['structural'] as $template) $writes[] = public_fixture_write_override($mixedContent, $template, $fixture['files'][$template], 'wp-content');
    foreach (array($emptyChild, $emptyParent) as $path) {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) $writes[] = false;
    }
    foreach ($fixture['structural'] as $template) {
        $sourceName = $template;
        if ($template === 'shows-list-start') $sourceName = 'shows-list-start-explicit';
        $writes[] = public_fixture_write_override($complete, $template, $fixture['files'][$sourceName], 'complete');
    }
    $writes[] = public_fixture_write_override($mixedChild, 'shows-list-start', $fixture['files']['shows-list-start'], 'child');
    $writes[] = public_fixture_write_override($mixedParent, 'shows-list', $fixture['files']['shows-list'], 'parent');
    $writes[] = public_fixture_write_override($mixedContent, 'shows-list-end', $fixture['files']['shows-list-end'], 'wp-content');
    if (in_array(false, $writes, true)) throw new RuntimeException('Could not create public override fixtures');

    $pages = array(
        'listing' => array('GigPress Public Listing', '[gigpress_shows scope="upcoming" group_artists="yes" artist_order="custom"]'),
        'compact' => array('GigPress Compact Surfaces', "[gigpress_shows scope=\"upcoming\" artist=\"701\"]\n[gigpress_public_compact_fixture]"),
        'child' => array('GigPress Child Theme Override', '[gigpress_public_override mode="child"]'),
        'parent' => array('GigPress Parent Theme Override', '[gigpress_public_override mode="parent"]'),
        'content' => array('GigPress wp-content Override', '[gigpress_public_override mode="content"]'),
        'complete' => array('GigPress Complete Theme Override', '[gigpress_public_override mode="complete"]'),
        'mixed' => array('GigPress Mixed Theme Override', '[gigpress_public_override mode="mixed"]'),
    );
    $urls = array();
    foreach ($pages as $slug => $pageData) {
        $existing = get_page_by_path('gigpress-public-' . $slug, OBJECT, 'page');
        $pageId = wp_insert_post(array('ID' => $existing ? $existing->ID : 0, 'post_type' => 'page', 'post_status' => 'publish',
            'post_title' => $pageData[0], 'post_name' => 'gigpress-public-' . $slug, 'post_content' => $pageData[1]), true);
        if (is_wp_error($pageId)) throw new RuntimeException('Could not create public fixture page');
        $urls[$slug] = get_permalink($pageId);
        $pageIds[$slug] = (int) $pageId;
    }
    update_option('gigpress_public_fixture_pages', $pageIds);
    update_option('show_on_front', 'page');
    update_option('page_on_front', (int) get_page_by_path('gigpress-public-listing', OBJECT, 'page')->ID);
    $mu = WP_CONTENT_DIR . '/mu-plugins';
    if (!is_dir($mu) && !mkdir($mu, 0775, true)) throw new RuntimeException('Could not create fixture mu-plugins directory');
    $muPlugin = <<<'PHP'
<?php
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (($severity & E_ALL) && strpos(str_replace('\\', '/', $file), '/gigpress/') !== false) {
        file_put_contents('/tmp/gigpress-public-errors.log', json_encode(array('severity'=>$severity,'message'=>$message,'file'=>basename($file),'line'=>$line))."\n", FILE_APPEND);
    }
    return false;
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR), true)) file_put_contents('/tmp/gigpress-public-errors.log', json_encode($error)."\n", FILE_APPEND);
});
add_action('init', function () {
    add_shortcode('gigpress_public_override', function ($attributes) {
        $attributes = shortcode_atts(array('mode' => ''), $attributes, 'gigpress_public_override');
        $mode = $attributes['mode'];
        if (!in_array($mode, array('child', 'parent', 'content', 'complete', 'mixed'), true)) return '';
        $themeRoot = WP_CONTENT_DIR . '/themes';
        $child = $themeRoot . '/gigpress-public-empty-child';
        $parent = $themeRoot . '/gigpress-public-empty-parent';
        if ($mode === 'child') $child = $themeRoot . '/gigpress-public-child';
        if ($mode === 'parent') $parent = $themeRoot . '/gigpress-public-parent';
        if ($mode === 'complete') $child = $themeRoot . '/gigpress-public-complete';
        if ($mode === 'mixed') { $child = $themeRoot . '/gigpress-public-mixed-child'; $parent = $themeRoot . '/gigpress-public-mixed-parent'; }
        $childFilter = function ($directory) use ($child) { return $child; };
        $parentFilter = function ($directory) use ($parent) { return $parent; };
        add_filter('stylesheet_directory', $childFilter, 99, 1);
        add_filter('template_directory', $parentFilter, 99, 1);
        $html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
        remove_filter('stylesheet_directory', $childFilter, 99);
        remove_filter('template_directory', $parentFilter, 99);
        return $html;
    });
    add_shortcode('gigpress_public_compact_fixture', function () {
        global $post, $is_excerpt, $wpdb;
        $widgetOutput = '';
        if (class_exists('Gigpress_widget')) {
            ob_start();
            (new Gigpress_widget())->widget(array('before_widget' => '<aside class="gigpress-public-widget">', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'),
                array('title' => 'Compact fixture', 'scope' => 'upcoming', 'limit' => 3, 'group_artists' => 'no', 'show_feeds' => 'no'));
            $widgetOutput = ob_get_clean();
        }
        $related = $wpdb->get_row('SELECT show_related FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = 109');
        $priorPost = $post ?? null; $priorExcerpt = $is_excerpt ?? false;
        $post = $related && $related->show_related ? get_post((int) $related->show_related) : null;
        $is_excerpt = false;
        $relatedOutput = $post ? gigpress_show_related(array('scope' => 'upcoming')) : '';
        $post = $priorPost; $is_excerpt = $priorExcerpt;
        return '<section><h2>Widget</h2>' . $widgetOutput . '</section><section><h2>Related show</h2>' . $relatedOutput . '</section>';
    });
});
PHP;
    if (file_put_contents($mu . '/gigpress-public-fixture.php', $muPlugin, LOCK_EX) === false) throw new RuntimeException('Could not install public fixture helper');
    $checks = array('plugin_active' => is_plugin_active('gigpress/gigpress.php'), 'all_public_pages_created' => count($urls) === 7,
        'migrated_show_available' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = 109') === 1,
        'supplemental_shows_available' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_id IN (801,802,803)') === 3,
        'complete_override_files' => count(glob($complete . '/*.php')) === 3,
        'mixed_override_files' => is_file($mixedChild . '/shows-list-start.php') && is_file($mixedParent . '/shows-list.php') && is_file($mixedContent . '/shows-list-end.php'));
    $themeWarmup = public_fixture_http('/');
    $checks['default_theme_navigation_initialized'] = $themeWarmup['status'] === 200
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_type = 'wp_navigation' AND post_status = 'publish' AND post_name = 'navigation'") > 0;
    if (in_array(false, $checks, true)) throw new RuntimeException('Public fixture page or override checks failed');
    $extra = array('pages' => $urls, 'home' => home_url('/'), 'source_show_ids' => array(109,801,802,803));
} elseif ($mode === 'public-check') {
    $before = browser_snapshot();
    $pageIds = get_option('gigpress_public_fixture_pages');
    if (!is_array($pageIds) || count($pageIds) !== 7) throw new RuntimeException('Public fixture page registry is incomplete');
    $paths = array();
    foreach ($pageIds as $slug => $pageId) $paths[$slug] = wp_make_link_relative(get_permalink((int) $pageId));
    $paths['rss'] = '/?feed=gigpress'; $paths['ical'] = '/?feed=gigpress-ical';
    $responses = array();
    foreach ($paths as $slug => $path) $responses[$slug] = public_fixture_http($path);
    $checks = array(
        'all_required_http_responses' => count($responses) === count($paths) && !array_filter($responses, function ($response) { return $response['status'] !== 200; }),
        'migrated_and_supplemental_details_rendered' => strpos($responses['listing']['body'], 'Archive Hall') !== false && strpos($responses['listing']['body'], 'LongVenue') !== false && strpos($responses['listing']['body'], 'Readable long detail') !== false,
        'compact_widget_and_related_rendered' => strpos($responses['compact']['body'], 'gigpress-public-widget') !== false && strpos($responses['compact']['body'], 'gigpress-related-show') !== false,
        'child_override_resolves_and_remains_owner_controlled' => strpos($responses['child']['body'], 'compat-child-body') !== false && strpos($responses['child']['body'], 'gigpress-layout-bundled') === false,
        'parent_override_resolves_and_remains_owner_controlled' => strpos($responses['parent']['body'], 'compat-parent-body') !== false && strpos($responses['parent']['body'], 'gigpress-layout-bundled') === false,
        'wp_content_override_resolves_and_remains_owner_controlled' => strpos($responses['content']['body'], 'compat-wp-content-body') !== false && strpos($responses['content']['body'], 'gigpress-layout-bundled') === false,
        'complete_override_is_explicitly_adopted' => strpos($responses['complete']['body'], 'compat-complete-body') !== false && strpos($responses['complete']['body'], 'compat-override-start gigpress-layout-bundled') !== false,
        'mixed_override_remains_owner_controlled' => strpos($responses['mixed']['body'], 'compat-child-body') === false && strpos($responses['mixed']['body'], 'compat-parent-body') !== false && strpos($responses['mixed']['body'], 'compat-wp-content-end') !== false && strpos($responses['mixed']['body'], 'gigpress-layout-bundled') === false,
        'anonymous_subscription_and_calendar_endpoints' => strpos($responses['rss']['body'], '<rss ') !== false && strpos($responses['ical']['body'], 'BEGIN:VCALENDAR') !== false,
    );
    $after = browser_snapshot();
    $checks['snapshots_unchanged'] = $before === $after;
    $changedSnapshotKeys = array();
    $changedSnapshotRows = array();
    foreach ($before as $key => $value) {
        if ($value !== ($after[$key] ?? null)) {
            $changedSnapshotKeys[] = $key;
            if ($key === 'posts') {
                $beforeRows = array_column($value, null, 'ID');
                $afterRows = array_column($after[$key] ?? array(), null, 'ID');
                foreach (array_unique(array_merge(array_keys($beforeRows), array_keys($afterRows))) as $postId) {
                    if (!isset($beforeRows[$postId])) {
                        global $wpdb;
                        $postMeta = $wpdb->get_row($wpdb->prepare('SELECT post_type, post_status, post_name FROM ' . $wpdb->posts . ' WHERE ID = %d', $postId), ARRAY_A);
                        $changedSnapshotRows[] = array('id' => (int) $postId, 'change' => 'added', 'classification' => $postMeta);
                    }
                    elseif (!isset($afterRows[$postId])) $changedSnapshotRows[] = array('id' => (int) $postId, 'change' => 'removed');
                    else {
                        $fields = array();
                        foreach ($beforeRows[$postId] as $field => $fieldValue) if ($fieldValue !== $afterRows[$postId][$field]) $fields[] = $field;
                        if ($fields) $changedSnapshotRows[] = array('id' => (int) $postId, 'changed_fields' => $fields);
                    }
                }
            }
        }
    }
    $extra = array('pages' => array_map(function ($path) use ($base) { return $base . $path; }, $paths),
        'http_statuses' => array_map(function ($r) { return $r['status']; }, $responses), 'content_types' => array_map(function ($r) { return $r['content_type']; }, $responses),
        'changed_snapshot_keys' => $changedSnapshotKeys, 'changed_snapshot_rows' => $changedSnapshotRows);
    $httpErrors = is_file('/tmp/gigpress-public-errors.log') ? file('/tmp/gigpress-public-errors.log', FILE_IGNORE_NEW_LINES) : array();
    $checks['no_plugin_http_errors'] = !$httpErrors;
} elseif ($mode === 'smoke') {
    $case = getenv('COMPAT_BROWSER_CASE') ?: 'entry';
    $required = $case === 'all' ? array('entry','settings','guards') : array($case);
    if (array_diff($required,array('entry','settings','guards'))) throw new RuntimeException('Unknown HTTP case');
    $cases = array(); $checks = array();
    foreach ($required as $name) {
        $started = microtime(true); $values = call_user_func('browser_' . $name);
        $passed = count($values) > 0 && !in_array(false,$values,true);
        $cases[] = array('case'=>$name,'status'=>$passed?'PASS':'FAIL','checks'=>$values,'assertion_count'=>count($values),'elapsed_seconds'=>round(microtime(true)-$started,4));
        foreach ($values as $key=>$value) $checks[$name . '.' . $key] = $value;
    }
    $checks['required_http_case_set'] = array_column($cases,'case') === $required && count(array_unique($required)) === count($required);
} elseif ($mode === 'snapshot') {
    echo json_encode(browser_snapshot(), JSON_UNESCAPED_SLASHES) . PHP_EOL; exit;
} else throw new RuntimeException('Unknown browser bootstrap mode');
$errorLog = in_array($mode, array('public-seed', 'public-pages', 'public-check'), true) ? '/tmp/gigpress-public-errors.log' : '/tmp/gigpress-browser-errors.log';
$httpErrors = is_file($errorLog) ? file($errorLog, FILE_IGNORE_NEW_LINES) : array();
$ok = $checks && !in_array(false, $checks, true) && !$errors && !$httpErrors;
echo json_encode(array('status' => $ok ? 'PASS' : 'FAIL', 'wordpress_version' => $wp_version, 'php_version' => PHP_VERSION,
    'checks' => $checks, 'assertion_count' => count($checks), 'cases' => $cases ?? array(), 'errors' => $errors, 'http_errors' => $httpErrors, 'public_fixture' => $extra ?? null), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($ok ? 0 : 1);

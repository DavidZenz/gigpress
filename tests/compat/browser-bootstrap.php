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
if ($mode === 'seed') define('WP_INSTALLING', true);
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
} elseif ($mode === 'smoke') {
    $case = getenv('COMPAT_BROWSER_CASE') ?: 'entry';
    $checks = browser_entry();
    $checks['required_case_entry'] = $case === 'entry';
} elseif ($mode === 'snapshot') {
    echo json_encode(browser_snapshot(), JSON_UNESCAPED_SLASHES) . PHP_EOL; exit;
} else throw new RuntimeException('Unknown browser bootstrap mode');
$httpErrors = is_file('/tmp/gigpress-browser-errors.log') ? file('/tmp/gigpress-browser-errors.log', FILE_IGNORE_NEW_LINES) : array();
$ok = $checks && !in_array(false, $checks, true) && !$errors && !$httpErrors;
echo json_encode(array('status' => $ok ? 'PASS' : 'FAIL', 'wordpress_version' => $wp_version, 'php_version' => PHP_VERSION,
    'checks' => $checks, 'assertion_count' => count($checks), 'errors' => $errors, 'http_errors' => $httpErrors), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($ok ? 0 : 1);

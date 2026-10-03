<?php
/* The probe is executed only inside the disposable WordPress container. */
error_reporting(E_ALL);
ini_set('display_errors', '0');

$pluginErrors = array();
$menuWarnings = array();
$fatal = null;
set_error_handler(function ($severity, $message, $file, $line) use (&$pluginErrors, &$menuWarnings) {
    if ((getenv('COMPAT_PURPOSE') ?: 'activation-menu') === 'diagnose-menu'
        && ($severity & E_WARNING)
        && strpos($message, 'Undefined array key') !== false
        && strpos($file, '/wp-admin/includes/menu.php') !== false) {
        $menuWarnings[] = array('message' => $message, 'line' => $line);
        return true;
    }
    if ($severity & E_ALL) {
        $pluginRoot = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR . '/gigpress/' : '/gigpress/';
        if (strpos(str_replace('\\', '/', $file), $pluginRoot) !== false) {
            $pluginErrors[] = array('severity' => $severity, 'message' => $message, 'file' => $file, 'line' => $line);
        }
    }
    return true;
});
register_shutdown_function(function () use (&$pluginErrors, &$fatal) {
    $last = error_get_last();
    if ($last && in_array($last['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        $fatal = $last;
    }
    if ($fatal) {
        echo json_encode(array('status' => 'FAIL', 'fatal' => $fatal, 'plugin_errors' => $pluginErrors)) . PHP_EOL;
    }
});

$purpose = getenv('COMPAT_PURPOSE') ?: 'activation-menu';

/*
 * The diagnostic PHP 8.2 image cannot complete the WordPress 7.0/7.1 core
 * bootstrap even though its mysqli driver can read the same disposable tables.
 * For the deliberately narrow controlled-fixture downgrade check, retain the
 * actual active_plugins option and provide only the two plugin API calls that
 * the fixture itself uses. Supported-runtime requests still boot WordPress.
 */
if (in_array($purpose, array('fixture-low', 'fixture-recover'), true)) {
    $plugin = 'php-floor-plugin.php';
    $notice = 'PHP 8.3 or newer is required for the controlled compatibility fixture.';
    $fixturePath = '/var/www/html/wp-content/plugins/php-floor-plugin.php';
    $db = mysqli_connect(
        getenv('WORDPRESS_DB_HOST') ?: 'db',
        getenv('WORDPRESS_DB_USER') ?: 'wordpress',
        getenv('WORDPRESS_DB_PASSWORD') ?: '',
        getenv('WORDPRESS_DB_NAME') ?: 'wordpress'
    );
    $activePlugins = array();
    if (!$db) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Could not read the isolated active_plugins option', 'file' => __FILE__, 'line' => __LINE__);
    } else {
        $option = mysqli_query($db, "SELECT option_value FROM wp_options WHERE option_name = 'active_plugins'");
        $row = $option ? mysqli_fetch_assoc($option) : null;
        $activePlugins = $row ? @unserialize($row['option_value']) : false;
        if (!is_array($activePlugins) || !in_array($plugin, $activePlugins, true)) {
            $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture lost active state in the isolated database', 'file' => __FILE__, 'line' => __LINE__);
        }
    }

    $compatHooks = array();
    $compatCanManagePlugins = true;
    function add_action($hook, $callback) {
        global $compatHooks;
        $compatHooks[$hook][] = $callback;
    }
    function current_user_can($capability) {
        global $compatCanManagePlugins;
        return $capability === 'activate_plugins' && $compatCanManagePlugins;
    }
    function compat_fixture_notices() {
        global $compatHooks;
        ob_start();
        foreach (isset($compatHooks['admin_notices']) ? $compatHooks['admin_notices'] : array() as $callback) {
            call_user_func($callback);
        }
        return ob_get_clean();
    }

    if (!is_readable($fixturePath)) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture is unavailable in the container', 'file' => $fixturePath, 'line' => 0);
    } else {
        require $fixturePath;
    }
    $normalSurface = function_exists('php_floor_fixture_normal_surface');
    $first = '';
    $second = '';
    $unauthorized = '';
    $public = '';
    if ($purpose === 'fixture-low') {
        $first = compat_fixture_notices();
        $second = compat_fixture_notices();
        $compatCanManagePlugins = false;
        $unauthorized = compat_fixture_notices();
        $public = compat_fixture_notices();
        $ok = !$pluginErrors
            && !$normalSurface
            && isset($compatHooks['admin_notices'])
            && substr_count($first, $notice) === 1
            && substr_count($second, $notice) === 1
            && strpos($unauthorized, $notice) === false
            && strpos($public, $notice) === false;
    } else {
        $ok = !$pluginErrors
            && $normalSurface
            && isset($compatHooks['init'])
            && in_array('php_floor_fixture_normal_surface', $compatHooks['init'], true)
            && !isset($compatHooks['admin_notices']);
    }
    $fixtureRuntime = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'active_plugins' => $activePlugins,
        'first_notice' => $first,
        'second_notice' => $second,
        'normal_surface' => $normalSurface,
        'runtime_driver' => 'diagnostic-plugin-api-facade',
    );
    if (!$ok) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture runtime-floor contract failed', 'file' => __FILE__, 'line' => __LINE__);
    }
    $result = array(
        'status' => $pluginErrors ? 'FAIL' : 'PASS',
        'wordpress_version' => 'diagnostic-bootstrap-bypassed',
        'php_version' => PHP_VERSION,
        'plugin_active' => in_array($plugin, is_array($activePlugins) ? $activePlugins : array(), true),
        'menu_slugs' => array(),
        'plugin_errors' => $pluginErrors,
        'fatal' => $fatal,
        'purpose' => $purpose,
        'csv_roundtrip' => null,
        'fixture_runtime' => $fixtureRuntime,
    );
    echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['status'] === 'PASS' ? 0 : 1);
}

define('WP_INSTALLING', true);
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_blog_installed()) {
    wp_install('GigPress Compatibility', 'compat-admin', 'compat-admin@example.test', true, '', 'compat-password');
}
$admin = get_user_by('login', 'compat-admin');
wp_set_current_user($admin->ID);
$fixturePurpose = in_array($purpose, array('fixture-activate', 'fixture-low', 'fixture-recover'), true);
$plugin = $fixturePurpose ? 'php-floor-plugin.php' : 'gigpress/gigpress.php';
if ($purpose === 'fixture-activate' || !$fixturePurpose) {
    $activation = activate_plugin($plugin, '', false, false);
    if (is_wp_error($activation)) {
        echo json_encode(array('status' => 'FAIL', 'reason' => $activation->get_error_message(), 'plugin_errors' => $pluginErrors)) . PHP_EOL;
        exit(1);
    }
} elseif (!is_plugin_active($plugin)) {
    $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture lost active state', 'file' => __FILE__, 'line' => __LINE__);
}
if ($purpose === 'diagnose-menu' && (getenv('COMPAT_CONFLICT_MODE') ?: '') === 'exact-key-late-add') {
    $fixtureActivation = activate_plugin('menu-conflict-plugin.php', '', false, false);
    if (is_wp_error($fixtureActivation)) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => $fixtureActivation->get_error_message(), 'file' => __FILE__, 'line' => __LINE__);
    }
}
require ABSPATH . 'wp-admin/menu.php';
global $menu;
$menuSlugs = array();
foreach ((array) $menu as $item) {
    if (isset($item[2])) {
        $menuSlugs[] = basename((string) $item[2]);
    }
}
$fixtureRuntime = null;
if ($fixturePurpose) {
    $notice = 'PHP 8.3 or newer is required for the controlled compatibility fixture.';
    $capture_notices = function () {
        ob_start();
        do_action('admin_notices');
        return ob_get_clean();
    };
    $activePlugins = (array) get_option('active_plugins', array());
    if ($purpose === 'fixture-activate') {
        $ok = is_plugin_active($plugin) && function_exists('php_floor_fixture_normal_surface') && has_action('init', 'php_floor_fixture_normal_surface');
        $fixtureRuntime = array('status' => $ok ? 'PASS' : 'FAIL', 'active_plugins' => $activePlugins, 'normal_surface' => $ok);
    } elseif ($purpose === 'fixture-low') {
        $first = $capture_notices();
        $second = $capture_notices();
        $subscriber = get_user_by('login', 'compat-subscriber');
        if (!$subscriber) {
            $subscriberId = wp_create_user('compat-subscriber', 'compat-password', 'compat-subscriber@example.test');
            $subscriber = get_user_by('id', $subscriberId);
        }
        wp_set_current_user($subscriber->ID);
        $unauthorized = $capture_notices();
        wp_set_current_user(0);
        $public = $capture_notices();
        wp_set_current_user($admin->ID);
        $ok = is_plugin_active($plugin) && !function_exists('php_floor_fixture_normal_surface') && !has_action('init', 'php_floor_fixture_normal_surface') && substr_count($first, $notice) === 1 && substr_count($second, $notice) === 1 && strpos($unauthorized, $notice) === false && strpos($public, $notice) === false;
        $fixtureRuntime = array('status' => $ok ? 'PASS' : 'FAIL', 'active_plugins' => $activePlugins, 'first_notice' => $first, 'second_notice' => $second, 'normal_surface' => function_exists('php_floor_fixture_normal_surface'));
    } else {
        $notices = $capture_notices();
        $ok = is_plugin_active($plugin) && function_exists('php_floor_fixture_normal_surface') && has_action('init', 'php_floor_fixture_normal_surface') && strpos($notices, $notice) === false;
        $fixtureRuntime = array('status' => $ok ? 'PASS' : 'FAIL', 'active_plugins' => $activePlugins, 'normal_surface' => $ok);
    }
    if ($fixtureRuntime['status'] !== 'PASS') {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture runtime-floor contract failed', 'file' => __FILE__, 'line' => __LINE__);
    }
}
$real_plugin_inventory = array(
    'functions' => array('gigpress_admin_menu', 'gigpress_shows', 'gigpress_ical'),
    'hooks' => array('admin_menu' => 'gigpress_admin_menu', 'init' => 'gigpress_init'),
    'modules' => array('admin/db.php', 'output/gigpress_shows.php', 'output/ical.php'),
);
$menuTrace = null;
if ($purpose === 'diagnose-menu' && function_exists('gigpress_menu_trace_result')) {
    $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/gigpress/gigpress.php', false, false);
    $menuTrace = gigpress_menu_trace_result($menuSlugs, $menuWarnings, array(
        'version' => $pluginData['Version'],
        'hash' => hash_file('sha256', WP_PLUGIN_DIR . '/gigpress/gigpress.php'),
    ));
}
$csvRoundTrip = null;
if ((getenv('COMPAT_PURPOSE') ?: 'activation-menu') === 'csv-roundtrip') {
    $fixture = '/compat/fixtures/shows.csv';
    if (!is_readable($fixture)) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'CSV fixture is unavailable', 'file' => $fixture, 'line' => 0);
    } else {
        require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
        $_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'));
        $_REQUEST = $_POST;
        $_FILES = array('gp_import' => array('name' => 'shows.csv', 'tmp_name' => $fixture, 'error' => UPLOAD_ERR_OK, 'size' => filesize($fixture)));
        ob_start();
        gigpress_import();
        ob_end_clean();

        $_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'), 'scope' => '-1', 'artist_id' => '-1', 'tour_id' => '-1');
        $_REQUEST = $_POST;
        ob_start();
        gigpress_export();
        $exported = ob_get_clean();
        $roundTrip = new parseCSV();
        $roundTrip->parse($exported);
        $row = isset($roundTrip->data[0]) ? $roundTrip->data[0] : array();
        $csvRoundTrip = array(
            'columns' => array_keys($row),
            'artist' => isset($row['Artist']) ? $row['Artist'] : null,
            'venue' => isset($row['Venue']) ? $row['Venue'] : null,
            'notes' => isset($row['Notes']) ? $row['Notes'] : null,
            'time' => isset($row['Time']) ? $row['Time'] : null,
            'all_day_sentinel_preserved' => isset($row['Time']) && $row['Time'] === '',
        );
        if ($csvRoundTrip['artist'] !== 'The Compatibility Band' || $csvRoundTrip['venue'] !== 'The Test Hall' || $csvRoundTrip['notes'] !== 'Quoted, durable notes' || !$csvRoundTrip['all_day_sentinel_preserved']) {
            $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'CSV round trip changed fixture values or the all-day sentinel', 'file' => $fixture, 'line' => 0);
        }
    }
}
$result = array(
    'status' => $pluginErrors ? 'FAIL' : 'PASS',
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => PHP_VERSION,
    'plugin_active' => is_plugin_active($plugin),
    'menu_slugs' => array_values(array_unique($menuSlugs)),
    'plugin_errors' => $pluginErrors,
    'fatal' => $fatal,
    'purpose' => $purpose,
    'csv_roundtrip' => $csvRoundTrip,
    'fixture_runtime' => $fixtureRuntime,
    'real_plugin_inventory' => $real_plugin_inventory,
    'menu_trace' => $menuTrace,
);
echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);

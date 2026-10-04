<?php
/* The probe is executed only inside the disposable WordPress container. */
error_reporting(E_ALL);
ini_set('display_errors', '0');

$pluginErrors = array();
$menuWarnings = array();
$fatal = null;
set_error_handler(function ($severity, $message, $file, $line) use (&$pluginErrors, &$menuWarnings) {
    if (in_array(getenv('COMPAT_PURPOSE') ?: 'activation-menu', array('diagnose-menu', 'admin-menu'), true)
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
$upgradeCase = getenv('COMPAT_UPGRADE_CASE') ?: 'tracer-1.4';
$upgradePreservation = null;

/* The controlled fixture keeps a narrow API facade for its isolated guard contract.
 * The real GigPress low-floor check uses the normal WordPress bootstrap below.
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
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
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
    if ($purpose === 'fixture-low' || $purpose === 'real-low') {
        $first = compat_fixture_notices();
        $second = compat_fixture_notices();
        $compatCanManagePlugins = false;
        $unauthorized = compat_fixture_notices();
        $public = compat_fixture_notices();
        $normalModules = array('admin/db.php', 'output/gigpress_shows.php', 'output/ical.php');
        $loadedModules = array();
        foreach (get_included_files() as $includedFile) {
            foreach ($normalModules as $module) {
                if (substr(str_replace('\\', '/', $includedFile), -strlen($module)) === $module) {
                    $loadedModules[] = $module;
                }
            }
        }
        $normalHooks = isset($compatHooks['admin_menu']) || isset($compatHooks['init']);
        $ok = !$pluginErrors
            && !$normalSurface
            && !$normalHooks
            && !$loadedModules
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

if (!in_array($purpose, array('real-recover', 'real-low-live'), true)) {
    define('WP_INSTALLING', true);
}
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_blog_installed()) {
    wp_install('GigPress Compatibility', 'compat-admin', 'compat-admin@example.test', true, '', 'compat-password');
    update_option('home', 'http://gigpress-compat.test');
    update_option('siteurl', 'http://gigpress-compat.test');
}
$admin = get_user_by('login', 'compat-admin');
wp_set_current_user($admin->ID);
$upgradeFixture = null;
function upgrade_preservation_seed($fixture) {
    global $wpdb, $pluginErrors;
    if (!is_array($fixture) || $wpdb->prefix !== $fixture['prefix']) return false;
    foreach (array('shows', 'artists', 'venues', 'tours') as $name) {
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'gigpress_' . $name);
    }
    delete_option('gigpress_settings');
    delete_option('gigpress_upgrade_state');
    $tables = array(
        'shows' => 'show_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, show_artist_id bigint(20) NOT NULL, show_venue_id bigint(20) NOT NULL, show_tour_id bigint(20) NOT NULL DEFAULT 0, show_date date NOT NULL, show_multi tinyint(1) NULL, show_time time NOT NULL, show_expire date NOT NULL, show_price varchar(255) NULL, show_tix_url varchar(255) NULL, show_tix_phone varchar(255) NULL, show_ages varchar(255) NULL, show_notes text NULL, show_related bigint(20) NOT NULL DEFAULT 0, show_status varchar(32) NOT NULL DEFAULT \'active\', show_external_url varchar(255) NULL, show_tour_restore tinyint(1) NOT NULL DEFAULT 0, show_address varchar(255) NULL, show_locale varchar(255) NULL, show_country varchar(2) NULL, show_venue varchar(255) NULL, show_venue_url varchar(255) NULL, show_venue_phone varchar(255) NULL, PRIMARY KEY (show_id)',
        'artists' => 'artist_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, artist_name varchar(255) NOT NULL, artist_alpha varchar(255) NOT NULL, artist_url varchar(255) NULL, artist_order bigint(20) NOT NULL DEFAULT 0, PRIMARY KEY (artist_id)',
        'venues' => 'venue_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, venue_name varchar(255) NOT NULL, venue_address varchar(255) NULL, venue_city varchar(255) NOT NULL, venue_state varchar(255) NULL, venue_postal_code varchar(32) NULL, venue_country varchar(2) NOT NULL, venue_url varchar(255) NULL, venue_phone varchar(255) NULL, PRIMARY KEY (venue_id)',
        'tours' => 'tour_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, tour_name varchar(255) NOT NULL, tour_status varchar(32) NOT NULL DEFAULT \'active\', PRIMARY KEY (tour_id)',
    );
    foreach ($tables as $name => $definition) {
        if ($wpdb->query('CREATE TABLE ' . $wpdb->prefix . 'gigpress_' . $name . ' (' . $definition . ')') === false) return false;
    }
    $postId = wp_insert_post(array('post_title' => 'Reconstructed linked post', 'post_content' => 'Existing WordPress content remains unchanged.', 'post_status' => 'publish', 'post_type' => 'post'));
    foreach (array('artists', 'venues', 'tours', 'shows') as $kind) {
        foreach ($fixture[$kind] as $row) {
            if ($kind === 'shows' && $row['show_related'] === 0 && ($fixture['linked_show_id'] ?? 109) === $row['show_id']) $row['show_related'] = (int) $postId;
            if ($wpdb->insert($wpdb->prefix . 'gigpress_' . $kind, $row) === false) return false;
        }
    }
    update_option('gigpress_settings', $fixture['settings']);
    return true;
}
if ($purpose === 'upgrade-preservation') {
    $fixtureVersion = $upgradeCase === 'versions-1.0-1.2' ? '1.0' : '1.4';
    $fixturePath = '/var/www/html/wp-content/plugins/gigpress/tests/compat/fixtures/upgrade-preservation/' . $fixtureVersion . '.php';
    $upgradeFixture = is_readable($fixturePath) ? require $fixturePath : null;
    if (!is_array($upgradeFixture) || ($upgradeFixture['label'] ?? '') !== 'reconstructed-' . $fixtureVersion) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Reconstructed upgrade fixture is unavailable', 'file' => $fixturePath, 'line' => 0);
    }
    if (!upgrade_preservation_seed($upgradeFixture)) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Upgrade fixture did not receive its nondefault prefix', 'file' => __FILE__, 'line' => __LINE__);
    }
    if ($upgradeCase === 'metadata-classification') {
        $unsafeSettings = $upgradeFixture['settings'];
        $unsafeSettings['db_version'] = '2.0';
        update_option('gigpress_settings', $unsafeSettings);
    }
}
$upgradeFailurePoint = $purpose === 'upgrade-preservation' && $upgradeCase === 'safety-1.4' ? 'before_schema' : null;
if ($purpose === 'upgrade-preservation') {
    add_filter('gigpress_upgrade_failure_point', function ($fail, $point) use (&$upgradeFailurePoint) { return $point === $upgradeFailurePoint; }, 10, 2);
}
$fixturePurpose = in_array($purpose, array('fixture-activate', 'fixture-low', 'fixture-recover'), true);
$plugin = $fixturePurpose ? 'php-floor-plugin.php' : 'gigpress/gigpress.php';
$skipGigPressActivation = $purpose === 'real-low-live'
    || ($purpose === 'diagnose-menu' && (getenv('COMPAT_SKIP_GIGPRESS_ACTIVATION') ?: '') === '1');
if (($purpose === 'fixture-activate' || !$fixturePurpose) && !$skipGigPressActivation) {
    $activation = activate_plugin($plugin, '', false, false);
    if (is_wp_error($activation)) {
        echo json_encode(array('status' => 'FAIL', 'reason' => $activation->get_error_message(), 'plugin_errors' => $pluginErrors)) . PHP_EOL;
        exit(1);
    }
} elseif (!$skipGigPressActivation && !is_plugin_active($plugin)) {
    $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture lost active state', 'file' => __FILE__, 'line' => __LINE__);
}
if ($purpose === 'upgrade-preservation' && $upgradeCase !== 'versions-1.0-1.2') {
    $snapshot = function () use ($wpdb) {
        $data = array('prefix' => $wpdb->prefix, 'settings' => get_option('gigpress_settings'));
        foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $kind => $id) {
            $data[$kind] = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gigpress_' . $kind . ' ORDER BY ' . $id, ARRAY_A);
        }
        $postId = isset($data['shows'][0]['show_related']) ? (int) $data['shows'][0]['show_related'] : 0;
        $post = $postId ? get_post($postId, ARRAY_A) : null;
        $data['linked_post'] = $post ? array('ID' => (int) $post['ID'], 'post_title' => $post['post_title'], 'post_content' => $post['post_content']) : null;
        return $data;
    };
    $safetyPass = true;
    $metadataPass = true;
    if ($upgradeCase === 'safety-1.4') {
        foreach (array('before_schema', 'after_schema', 'before_artist_alpha', 'before_venue_state', 'before_final_marker') as $point) {
            if ($point !== 'before_schema' && !upgrade_preservation_seed($upgradeFixture)) {
                $safetyPass = false;
                continue;
            }
            unset($GLOBALS['gigpress_db_bootstrap_result']);
            $upgradeFailurePoint = $point;
            $blocked = gigpress_db_bootstrap();
            $during = $snapshot();
            $safeMarker = ($during['settings']['db_version'] ?? null) === '1.4';
            $journalPresent = is_array(get_option('gigpress_upgrade_state', false));
            $sourceIds = array_map('intval', array_column($upgradeFixture['shows'], 'show_id'));
            $safeRows = array_map('intval', array_column($during['shows'], 'show_id')) === $sourceIds;
            $upgradeFailurePoint = null;
            unset($GLOBALS['gigpress_db_bootstrap_result']);
            $retry = gigpress_db_bootstrap();
            $safetyPass = $safetyPass && $blocked['status'] === 'blocked' && $safeMarker && $journalPresent && $safeRows && $retry['status'] === 'ready';
        }
    }
    if ($upgradeCase === 'metadata-classification') {
        $notice = function () { ob_start(); do_action('admin_notices'); return ob_get_clean(); };
        $authorizedNotice = $notice();
        $subscriber = get_user_by('login', 'compat-subscriber');
        if (!$subscriber) {
            $subscriberId = wp_create_user('compat-subscriber', 'compat-password', 'compat-subscriber@example.test');
            $subscriber = is_wp_error($subscriberId) ? false : get_user_by('id', $subscriberId);
        }
        if ($subscriber) wp_set_current_user($subscriber->ID);
        $unauthorizedNotice = $notice();
        wp_set_current_user(0);
        $publicNotice = $notice();
        wp_set_current_user($admin->ID);
        $metadataPass = !function_exists('gigpress_admin_menu')
            && strpos($authorizedNotice, 'GigPress data upgrade is paused (unsafe_metadata)') !== false
            && strpos($unauthorizedNotice, 'GigPress data upgrade is paused') === false
            && strpos($publicNotice, 'GigPress data upgrade is paused') === false;
        foreach (array(false, 'malformed', '1.9', '2.0') as $metadata) {
            $metadataPass = $metadataPass && upgrade_preservation_seed($upgradeFixture);
            if ($metadata === false) delete_option('gigpress_settings');
            else {
                $settings = $upgradeFixture['settings'];
                if ($metadata === 'malformed') $settings = 'not-an-array';
                else $settings['db_version'] = $metadata;
                update_option('gigpress_settings', $settings);
            }
            $before = $snapshot();
            unset($GLOBALS['gigpress_db_bootstrap_result']);
            $blocked = gigpress_db_bootstrap();
            $after = $snapshot();
            $metadataPass = $metadataPass && $blocked['status'] === 'blocked' && $before === $after;
        }
        foreach (array('shows', 'artists', 'venues', 'tours') as $name) $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'gigpress_' . $name);
        delete_option('gigpress_settings');
        delete_option('gigpress_upgrade_state');
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $fresh = gigpress_db_bootstrap();
        $metadataPass = $metadataPass && $fresh['status'] === 'ready';
        $metadataPass = $metadataPass && upgrade_preservation_seed($upgradeFixture);
        unset($GLOBALS['gigpress_db_bootstrap_result']);
        $metadataPass = $metadataPass && gigpress_db_bootstrap()['status'] === 'ready';
    }
    $first = $snapshot();
    $second = function_exists('gigpress_db_bootstrap') ? $snapshot() : null;
    $expected = is_array($upgradeFixture) ? $upgradeFixture['expected'] : array();
    $checks = array(
        'prefix' => $first['prefix'] === ($upgradeFixture['prefix'] ?? null),
        'version' => ($first['settings']['db_version'] ?? null) === ($expected['version'] ?? null),
        'show_ids' => array_map('intval', array_column($first['shows'], 'show_id')) === ($expected['show_ids'] ?? null),
        'artist_ids' => array_map('intval', array_column($first['artists'], 'artist_id')) === ($expected['artist_ids'] ?? null),
        'venue_ids' => array_map('intval', array_column($first['venues'], 'venue_id')) === ($expected['venue_ids'] ?? null),
        'tour_ids' => array_map('intval', array_column($first['tours'], 'tour_id')) === ($expected['tour_ids'] ?? null),
        'artist_alpha' => ($first['artists'][0]['artist_alpha'] ?? null) === ($expected['artist_alpha'] ?? null),
        'venue_city' => ($first['venues'][0]['venue_city'] ?? null) === ($expected['venue_city'] ?? null),
        'venue_state' => ($first['venues'][0]['venue_state'] ?? null) === ($expected['venue_state'] ?? null),
        'journal_removed' => !get_option('gigpress_upgrade_state', false),
    );
    $manifestMatches = !in_array(false, $checks, true);
    $ok = function_exists('gigpress_db_bootstrap') && is_array($upgradeFixture) && $manifestMatches && $first === $second && $safetyPass && $metadataPass;
    $upgradePreservation = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'case' => $upgradeCase,
        'fixture' => is_array($upgradeFixture) ? $upgradeFixture['label'] : null,
        'manifest_matches' => $manifestMatches,
        'repeat_matches' => $first === $second,
        'checks' => $checks,
        'safety_passed' => $safetyPass,
        'metadata_passed' => $metadataPass,
    );
    if (!$ok) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Upgrade coordinator is not available for the reconstructed fixture', 'file' => WP_PLUGIN_DIR . '/gigpress/admin/db.php', 'line' => 0);
    }
}
if ($purpose === 'upgrade-preservation' && $upgradeCase === 'versions-1.0-1.2') {
    require WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
    $upgradePreservation = gigpress_upgrade_preservation_run_versions(array('1.0', '1.1', '1.2'));
    if ($upgradePreservation['status'] !== 'PASS') $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Early-version migration matrix did not converge', 'file' => __FILE__, 'line' => __LINE__);
}
if (($purpose === 'diagnose-menu' && (getenv('COMPAT_CONFLICT_MODE') ?: '') === 'exact-key-late-add')
    || ($purpose === 'admin-menu' && (getenv('COMPAT_CONFLICT_MODE') ?: '') === 'order-only')) {
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
$realPluginRuntime = null;
if (in_array($purpose, array('real-activate', 'real-recover', 'real-low-live'), true)) {
    $capture_notices = function () {
        ob_start();
        do_action('admin_notices');
        return ob_get_clean();
    };
    $activePlugins = (array) get_option('active_plugins', array());
    $options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'gigpress\\_%' ORDER BY option_name", ARRAY_A);
    $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}gigpress\\_%'");
    $tableRows = array();
    foreach ((array) $tables as $table) {
        $tableRows[$table] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
    $normalSurface = function_exists('gigpress_admin_menu') && function_exists('gigpress_shows') && function_exists('gigpress_ical');
    $normalHooks = has_action('admin_menu', 'gigpress_admin_menu') && has_action('init', 'add_gigpress_feeds');
    $lowRuntime = $purpose === 'real-low-live';
    $notice = 'GigPress requires PHP 8.3 or newer. This site is running an incompatible PHP version.';
    if ($lowRuntime) {
        $firstNotice = $capture_notices();
        $secondNotice = $capture_notices();
        $subscriber = get_user_by('login', 'compat-subscriber');
        if (!$subscriber) {
            $subscriberId = wp_create_user('compat-subscriber', wp_generate_password(), 'compat-subscriber@example.test');
            $subscriber = is_wp_error($subscriberId) ? false : get_user_by('id', $subscriberId);
        }
        if ($subscriber) {
            wp_set_current_user($subscriber->ID);
        }
        $unauthorizedNotice = $capture_notices();
        wp_set_current_user(0);
        $publicNotice = $capture_notices();
        wp_set_current_user($admin->ID);
        $loadedModules = array();
        foreach (get_included_files() as $includedFile) {
            foreach ($real_plugin_inventory['modules'] as $module) {
                if (substr(str_replace('\\', '/', $includedFile), -strlen($module)) === $module) {
                    $loadedModules[] = $module;
                }
            }
        }
        $noticeChecks = substr_count($firstNotice, $notice) === 1
            && substr_count($secondNotice, $notice) === 1
            && strpos($unauthorizedNotice, $notice) === false
            && strpos($publicNotice, $notice) === false;
        $ok = is_plugin_active($plugin)
            && !$normalSurface
            && !$normalHooks
            && !$loadedModules
            && $noticeChecks;
    } else {
        $notices = $capture_notices();
        $ok = is_plugin_active($plugin)
            && $normalSurface
            && $normalHooks
            && strpos($notices, $notice) === false;
    }
    $realPluginRuntime = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'active_state' => array('active_plugins' => $activePlugins, 'network_active_plugins' => array()),
        'data_snapshot' => array('options' => $options, 'table_rows' => $tableRows),
        'normal_surface' => $normalSurface,
        'normal_hooks' => (bool) $normalHooks,
        'modules_loaded' => isset($loadedModules) ? $loadedModules : array(),
        'notice_present' => $lowRuntime ? substr_count($firstNotice, $notice) === 1 : strpos($notices, $notice) !== false,
        'notice_repeat_count' => $lowRuntime ? substr_count($secondNotice, $notice) : null,
        'unauthorized_notice' => $lowRuntime ? strpos($unauthorizedNotice, $notice) !== false : null,
        'public_notice' => $lowRuntime ? strpos($publicNotice, $notice) !== false : null,
    );
    if (!$ok) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Real GigPress runtime contract failed', 'file' => __FILE__, 'line' => __LINE__);
    }
}
$menuTrace = null;
if ($purpose === 'diagnose-menu' && function_exists('gigpress_menu_trace_result')) {
    $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/gigpress/gigpress.php', false, false);
    $menuTrace = gigpress_menu_trace_result($menuSlugs, $menuWarnings, array(
        'version' => $pluginData['Version'],
        'hash' => hash_file('sha256', WP_PLUGIN_DIR . '/gigpress/gigpress.php'),
    ));
}
$csvRoundTrip = null;
$fullWorkflows = null;
if (in_array((getenv('COMPAT_PURPOSE') ?: 'activation-menu'), array('csv-roundtrip', 'full-workflows'), true)) {
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
        $firstImport = ob_get_clean();
        $duplicateImport = '';
        if ($purpose === 'full-workflows') {
            $_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'));
            $_REQUEST = $_POST;
            $_FILES = array('gp_import' => array('name' => 'shows.csv', 'tmp_name' => $fixture, 'error' => UPLOAD_ERR_OK, 'size' => filesize($fixture)));
            ob_start();
            gigpress_import();
            $duplicateImport = ob_get_clean();
        }

        $show = $wpdb->get_row("SELECT * FROM " . GIGPRESS_SHOWS . " WHERE show_status != 'deleted' ORDER BY show_id ASC LIMIT 1");
        $artist = $show ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . GIGPRESS_ARTISTS . " WHERE artist_id = %d", $show->show_artist_id)) : null;
        $venue = $show ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . GIGPRESS_VENUES . " WHERE venue_id = %d", $show->show_venue_id)) : null;
        $tour = $show ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . GIGPRESS_TOURS . " WHERE tour_id = %d", $show->show_tour_id)) : null;
        $adminCreateEditRead = false;
        if ($show && $artist && $venue && $tour) {
            $future = gmdate('Y-m-d', strtotime('+30 days'));
            list($year, $month, $day) = array_map('intval', explode('-', $future));
            $_POST = array(
                '_wpnonce' => wp_create_nonce('gigpress-action'), 'show_id' => $show->show_id,
                'gp_yy' => $year, 'gp_mm' => $month, 'gp_dd' => $day, 'gp_hh' => 'na', 'gp_min' => 'na',
                'show_price' => $show->show_price, 'show_tix_url' => $show->show_tix_url, 'show_tix_phone' => $show->show_tix_phone,
                'show_external_url' => $show->show_external_url, 'show_ages' => $show->show_ages, 'show_notes' => $show->show_notes,
                'show_status' => 'active', 'show_artist_id' => $artist->artist_id, 'show_venue_id' => $venue->venue_id,
                'show_tour_id' => $tour->tour_id, 'show_related' => 0,
            );
            $_REQUEST = $_POST;
            ob_start();
            gigpress_update_show();
            $updateOutput = ob_get_clean();
            $updated = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . GIGPRESS_SHOWS . " WHERE show_id = %d", $show->show_id));
            $adminCreateEditRead = $updated && $updated->show_date === $future && $updated->show_notes === $show->show_notes && strpos($firstImport, 'successfully imported') !== false && strpos($updateOutput, 'successfully updated') !== false;
        }

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
        if ($purpose === 'full-workflows') {
            $_GET = array();
            $shortcode = do_shortcode('[gigpress_shows scope="upcoming"]');
            ob_start();
            gigpress_feed();
            $rss = ob_get_clean();
            ob_start();
            gigpress_ical();
            $ical = ob_get_clean();
            $fullWorkflows = array(
                'status' => ($adminCreateEditRead && strpos($shortcode, 'The Compatibility Band') !== false && strpos($rss, '<rss ') !== false && strpos($rss, 'The Compatibility Band') !== false && strpos($ical, 'BEGIN:VCALENDAR') !== false && strpos($ical, 'BEGIN:VEVENT') !== false && strpos($duplicateImport, 'deemed duplicates') !== false) ? 'PASS' : 'FAIL',
                'admin_create_edit_read' => $adminCreateEditRead,
                'public_shortcode' => strpos($shortcode, 'The Compatibility Band') !== false,
                'rss' => strpos($rss, '<rss ') !== false && strpos($rss, 'The Compatibility Band') !== false,
                'ical' => strpos($ical, 'BEGIN:VCALENDAR') !== false && strpos($ical, 'BEGIN:VEVENT') !== false,
                'csv_import_export' => $csvRoundTrip['artist'] === 'The Compatibility Band' && $csvRoundTrip['venue'] === 'The Test Hall',
                'duplicate_preserved' => strpos($duplicateImport, 'deemed duplicates') !== false,
            );
            if ($fullWorkflows['status'] !== 'PASS') {
                $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Full compatibility workflow contract failed', 'file' => __FILE__, 'line' => __LINE__);
            }
        }
    }
}
$result = array(
    'status' => $pluginErrors ? 'FAIL' : 'PASS',
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => PHP_VERSION,
    'plugin_active' => is_plugin_active($plugin),
    'menu_slugs' => $menuSlugs,
    'menu_warnings' => $menuWarnings,
    'menu_order_conflict' => !empty($GLOBALS['gigpress_menu_order_conflict']),
    'plugin_errors' => $pluginErrors,
    'fatal' => $fatal,
    'purpose' => $purpose,
    'csv_roundtrip' => $csvRoundTrip,
    'full_workflows' => $fullWorkflows,
    'upgrade_preservation' => $upgradePreservation,
    'fixture_runtime' => $fixtureRuntime,
    'real_plugin_runtime' => $realPluginRuntime,
    'real_plugin_inventory' => $real_plugin_inventory,
    'menu_trace' => $menuTrace,
);
echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);

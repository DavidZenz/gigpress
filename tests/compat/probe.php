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
$administrationWorkflows = null;
$publicPublishing = null;
$publicCase = $upgradeCase;
$administrationRequiredCases = array('entry-create', 'entry-recovery', 'entry-controls', 'settings-save', 'settings-sections', 'list-single', 'list-navigation', 'list-bulk');

function gigpress_administration_case($case) {
    global $pluginErrors, $menuWarnings;
    $started = microtime(true);
    $readiness = function_exists('gigpress_db_bootstrap') ? gigpress_db_bootstrap() : array('status' => 'blocked');
    $ready = ($readiness['status'] ?? 'blocked') === 'ready';
    $family = strpos($case, 'entry-') === 0 ? 'entry' : (strpos($case, 'settings-') === 0 ? 'settings' : 'list');
    $module = WP_PLUGIN_DIR . '/gigpress/tests/compat/administration-' . $family . '.php';
    $callback = 'gigpress_administration_' . $family . '_case';
    $record = array('case' => $case, 'checks' => array());
    if (is_readable($module)) {
        require_once $module;
        if (function_exists($callback)) $record = call_user_func($callback, $case);
    }
    $checks = is_array($record['checks'] ?? null) ? $record['checks'] : array();
    $active = is_plugin_active('gigpress/gigpress.php');
    $ok = $active && $ready && ($record['case'] ?? null) === $case && count($checks) > 0
        && !array_filter($checks, function ($value) { return $value !== true; })
        && !$pluginErrors && !$menuWarnings;
    return array_merge($record, array('case' => $case, 'status' => $ok ? 'PASS' : 'FAIL', 'checks' => $checks,
        'ready' => $ready, 'plugin_active' => $active, 'assertion_count' => count($checks), 'warning_count' => count($menuWarnings), 'fatal_count' => 0,
        'plugin_error_count' => count($pluginErrors), 'elapsed_seconds' => round(microtime(true) - $started, 4)));
}

function gigpress_administration_all($required) {
    $canonical = array('entry-create', 'entry-recovery', 'entry-controls', 'settings-save', 'settings-sections', 'list-single', 'list-navigation', 'list-bulk');
    $sorted = $required;
    sort($sorted);
    $expected = $canonical;
    sort($expected);
    if ($sorted !== $expected) return array('case' => 'all', 'status' => 'FAIL', 'required_cases' => $required, 'cases' => array());
    $cases = array();
    foreach ($required as $case) {
        $output = array();
        $exit = 1;
        exec('COMPAT_UPGRADE_CASE=' . escapeshellarg($case) . ' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' 2>&1', $output, $exit);
        $result = json_decode(end($output), true);
        $record = $result['administration_workflows'] ?? array('case' => $case, 'status' => 'FAIL', 'checks' => array(), 'assertion_count' => 0);
        $checks = $record['checks'] ?? array();
        if ($exit !== 0 || ($result['status'] ?? '') !== 'PASS' || ($result['plugin_active'] ?? false) !== true
            || !empty($result['fatal']) || !empty($result['plugin_errors']) || !empty($result['menu_warnings'])
            || ($record['case'] ?? '') !== $case || !$checks || ($record['assertion_count'] ?? 0) !== count($checks)
            || array_filter($checks, function ($check) { return $check !== true; })
            || ($record['warning_count'] ?? -1) !== 0 || ($record['fatal_count'] ?? -1) !== 0 || ($record['plugin_error_count'] ?? -1) !== 0) {
            $record['status'] = 'FAIL';
            $record['failure_detail'] = is_array($result) ? ($result['fatal']['message'] ?? 'Child result did not satisfy the administration contract.') : implode("\n", $output);
        }
        $cases[] = $record;
    }
    $passed = count($required) === count(array_unique($required)) && count($cases) === count($required)
        && !array_filter($cases, function ($case) { return $case['status'] !== 'PASS'; });
    return array('case' => 'all', 'status' => $passed ? 'PASS' : 'FAIL', 'required_cases' => $required, 'cases' => $cases);
}

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

function upgrade_preservation_source_schema($version) {
    $shows = array(
        'show_id' => 'INTEGER(4) AUTO_INCREMENT',
        'show_artist_id' => 'INTEGER(4) NOT NULL',
        'show_venue_id' => 'INTEGER(4) NOT NULL',
        'show_tour_id' => 'INTEGER(4) DEFAULT 0',
        'show_date' => 'DATE NOT NULL',
        'show_multi' => 'INTEGER(1)',
        'show_time' => 'TIME NOT NULL',
        'show_price' => 'VARCHAR(255)',
        'show_tix_url' => 'VARCHAR(255)',
        'show_tix_phone' => 'VARCHAR(255)',
        'show_ages' => 'VARCHAR(255)',
        'show_notes' => 'TEXT',
        'show_related' => 'BIGINT(20) DEFAULT 0',
        'show_tour_restore' => 'INTEGER(1) DEFAULT 0',
        'show_address' => 'VARCHAR(255)',
        'show_locale' => 'VARCHAR(255)',
        'show_country' => 'VARCHAR(2)',
        'show_venue' => 'VARCHAR(255)',
        'show_venue_url' => 'VARCHAR(255)',
        'show_venue_phone' => 'VARCHAR(255)',
    );
    if ($version !== '1.0') $shows['show_expire'] = 'DATE NOT NULL';
    if (in_array($version, array('1.2', '1.3', '1.4', '1.5', '1.6'), true)) $shows['show_status'] = 'VARCHAR(32) DEFAULT \'active\'';
    if ($version === '1.6') $shows['show_external_url'] = 'VARCHAR(255)';

    $artists = array(
        'artist_id' => 'INTEGER(4) AUTO_INCREMENT',
        'artist_name' => 'VARCHAR(255) NOT NULL',
    );
    $venues = array(
        'venue_id' => 'INTEGER(4) AUTO_INCREMENT',
        'venue_name' => 'VARCHAR(255) NOT NULL',
        'venue_address' => 'VARCHAR(255)',
        'venue_city' => 'VARCHAR(255) NOT NULL',
        'venue_country' => 'VARCHAR(2) NOT NULL',
        'venue_url' => 'VARCHAR(255)',
        'venue_phone' => 'VARCHAR(255)',
    );
    $tours = array(
        'tour_id' => 'INTEGER(4) AUTO_INCREMENT',
        'tour_name' => 'VARCHAR(255) NOT NULL',
    );
    if (in_array($version, array('1.2', '1.3', '1.4', '1.5', '1.6'), true)) $tours['tour_status'] = 'VARCHAR(32) DEFAULT \'active\'';
    if ($version === '1.6') {
        $artists['artist_alpha'] = 'VARCHAR(255) NOT NULL';
        $artists['artist_url'] = 'VARCHAR(255)';
        $artists['artist_order'] = 'INTEGER(4) DEFAULT 0';
        $venues['venue_state'] = 'VARCHAR(255)';
        $venues['venue_postal_code'] = 'VARCHAR(32)';
    }
    return array('shows' => $shows, 'artists' => $artists, 'venues' => $venues, 'tours' => $tours);
}

function upgrade_preservation_current_schema() {
    $column = function ($field, $type, $null, $key = '', $default = null, $extra = '') {
        return array('Field' => $field, 'Type' => $type, 'Null' => $null, 'Key' => $key, 'Default' => $default, 'Extra' => $extra);
    };
    return array(
        'shows' => array(
            $column('show_id', 'int(4)', 'NO', 'PRI', null, 'auto_increment'),
            $column('show_artist_id', 'int(4)', 'NO'), $column('show_venue_id', 'int(4)', 'NO'),
            $column('show_tour_id', 'int(4)', 'YES', '', '0'), $column('show_date', 'date', 'NO'),
            $column('show_multi', 'int(1)', 'YES'), $column('show_time', 'time', 'NO'), $column('show_expire', 'date', 'NO'),
            $column('show_price', 'varchar(255)', 'YES'), $column('show_tix_url', 'varchar(255)', 'YES'),
            $column('show_tix_phone', 'varchar(255)', 'YES'), $column('show_ages', 'varchar(255)', 'YES'),
            $column('show_notes', 'text', 'YES'), $column('show_related', 'bigint(20)', 'YES', '', '0'),
            $column('show_status', 'varchar(32)', 'YES', '', 'active'), $column('show_external_url', 'varchar(255)', 'YES'),
            $column('show_tour_restore', 'int(1)', 'YES', '', '0'), $column('show_address', 'varchar(255)', 'YES'),
            $column('show_locale', 'varchar(255)', 'YES'), $column('show_country', 'varchar(2)', 'YES'),
            $column('show_venue', 'varchar(255)', 'YES'), $column('show_venue_url', 'varchar(255)', 'YES'),
            $column('show_venue_phone', 'varchar(255)', 'YES'),
        ),
        'artists' => array(
            $column('artist_id', 'int(4)', 'NO', 'PRI', null, 'auto_increment'), $column('artist_name', 'varchar(255)', 'NO'),
            $column('artist_alpha', 'varchar(255)', 'NO'), $column('artist_url', 'varchar(255)', 'YES'),
            $column('artist_order', 'int(4)', 'YES', '', '0'),
        ),
        'venues' => array(
            $column('venue_id', 'int(4)', 'NO', 'PRI', null, 'auto_increment'), $column('venue_name', 'varchar(255)', 'NO'),
            $column('venue_address', 'varchar(255)', 'YES'), $column('venue_city', 'varchar(255)', 'NO'),
            $column('venue_state', 'varchar(255)', 'YES'), $column('venue_postal_code', 'varchar(32)', 'YES'),
            $column('venue_country', 'varchar(2)', 'NO'), $column('venue_url', 'varchar(255)', 'YES'), $column('venue_phone', 'varchar(255)', 'YES'),
        ),
        'tours' => array(
            $column('tour_id', 'int(4)', 'NO', 'PRI', null, 'auto_increment'), $column('tour_name', 'varchar(255)', 'NO'),
            $column('tour_status', 'varchar(32)', 'YES', '', 'active'),
        ),
    );
}

function upgrade_preservation_create_table($name, $columns, $primary) {
    global $wpdb;
    $definition = array();
    foreach ($columns as $column => $type) $definition[] = $column . ' ' . $type;
    $definition[] = 'PRIMARY KEY (' . $primary . ')';
    return $wpdb->query('CREATE TABLE ' . $wpdb->prefix . 'gigpress_' . $name . ' (' . implode(', ', $definition) . ')') !== false;
}

function upgrade_preservation_seed($fixture) {
    global $wpdb, $pluginErrors;
    if (!is_array($fixture) || $wpdb->prefix !== $fixture['prefix']) return false;
    foreach (array('shows', 'artists', 'venues', 'tours') as $name) {
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'gigpress_' . $name);
    }
    delete_option('gigpress_settings');
    delete_option('gigpress_upgrade_state');
    $schema = upgrade_preservation_source_schema($fixture['settings']['db_version'] ?? '');
    foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $name => $primary) {
        if (!isset($schema[$name]) || !upgrade_preservation_create_table($name, $schema[$name], $primary)) return false;
    }
    $postId = wp_insert_post(array('post_title' => 'Reconstructed linked post', 'post_content' => 'Existing WordPress content remains unchanged.', 'post_status' => 'publish', 'post_type' => 'post'));
    foreach (array('artists', 'venues', 'tours', 'shows') as $kind) {
        foreach ($fixture[$kind] as $row) {
            if ($kind === 'shows' && $row['show_related'] === 0 && ($fixture['linked_show_id'] ?? 109) === $row['show_id']) $row['show_related'] = (int) $postId;
            $row = array_intersect_key($row, array_flip(array_keys($schema[$kind])));
            if ($wpdb->insert($wpdb->prefix . 'gigpress_' . $kind, $row) === false) return false;
        }
    }
    update_option('gigpress_settings', $fixture['settings']);
    return true;
}

$upgradePreservationRequiredCases = array(
    'tracer-1.4',
    'safety-1.4',
    'metadata-classification',
    'versions-1.0-1.2',
    'versions-1.3-1.5',
    'current-1.6',
    'settings-repeat',
    'show-lifecycle',
    'optional-request-fields',
    'entity-guards',
    'tour-undo',
);

$tourRestoreFailurePoint = null;
add_filter('gigpress_tour_restore_failure_point', function ($fail, $point) use (&$tourRestoreFailurePoint) {
    return $fail || $tourRestoreFailurePoint === $point;
}, 10, 2);

function gigpress_upgrade_preservation_reset_case_state() {
    $_GET = array();
    $_POST = array();
    $_REQUEST = array();
    $_FILES = array();
    unset($GLOBALS['gigpress_db_bootstrap_result'], $GLOBALS['upgradeFailurePoint']);
}

function gigpress_upgrade_preservation_run_all($requiredCases) {
    $migrationModule = WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
    $crudModule = WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-crud.php';
    $moduleErrors = array();
    foreach (array($migrationModule, $crudModule) as $module) {
        if (!is_readable($module)) $moduleErrors[] = $module;
    }
    if (count($requiredCases) !== count(array_unique($requiredCases)) || $moduleErrors) {
        return array(
            'status' => 'FAIL',
            'case' => 'all',
            'required_cases' => $requiredCases,
            'cases' => array(),
            'module_errors' => $moduleErrors,
            'reason' => $moduleErrors ? 'required support module is unavailable' : 'required case registry contains duplicate IDs',
        );
    }

    $originalCase = getenv('COMPAT_UPGRADE_CASE');
    $caseEvidence = array();
    foreach ($requiredCases as $case) {
        gigpress_upgrade_preservation_reset_case_state();
        putenv('COMPAT_UPGRADE_CASE=' . $case);
        $output = array();
        $exitCode = 1;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' 2>&1', $output, $exitCode);
        $record = json_decode(end($output), true);
        $preservation = is_array($record) && isset($record['upgrade_preservation']) && is_array($record['upgrade_preservation']) ? $record['upgrade_preservation'] : array();
        $warnings = is_array($record) && isset($record['menu_warnings']) && is_array($record['menu_warnings']) ? $record['menu_warnings'] : array();
        $errors = is_array($record) && isset($record['plugin_errors']) && is_array($record['plugin_errors']) ? $record['plugin_errors'] : array();
        $fixture = $preservation['fixture'] ?? null;
        $caseMatches = ($preservation['case'] ?? null) === $case;
        if (in_array($case, array('versions-1.0-1.2', 'versions-1.3-1.5', 'settings-repeat'), true)) {
            $fixtures = isset($preservation['fixtures']) && is_array($preservation['fixtures']) ? $preservation['fixtures'] : array();
            $caseMatches = !empty($fixtures) && !array_filter($fixtures, function ($detail) {
                return empty($detail['repeat']) || empty($detail['checks']) || in_array(false, $detail['checks'], true);
            });
            $fixture = $fixtures ? 'reconstructed-' . implode(',', array_keys($fixtures)) : null;
        } elseif ($case === 'current-1.6') {
            $caseMatches = !empty($preservation['unchanged']) && !empty($preservation['repeat']) && !empty($preservation['journal_absent']) && !empty($preservation['checks']) && !in_array(false, $preservation['checks'], true);
            $fixture = 'reconstructed-1.6';
        }
        $ready = $exitCode === 0
            && is_array($record)
            && ($record['status'] ?? 'FAIL') === 'PASS'
            && ($record['plugin_active'] ?? false) === true
            && ($preservation['status'] ?? 'FAIL') === 'PASS'
            && $caseMatches
            && !$warnings
            && !$errors
            && empty($record['fatal']);
        $caseEvidence[] = array(
            'case' => $case,
            'status' => $ready ? 'PASS' : 'FAIL',
            'fixture' => $fixture,
            'ready' => $ready,
            'plugin_active' => (bool) ($record['plugin_active'] ?? false),
            'warning_count' => count($warnings),
            'fatal_count' => empty($record['fatal']) ? 0 : 1,
            'plugin_error_count' => count($errors),
            'failure_detail' => $ready ? null : (is_array($record) ? ($record['fatal']['message'] ?? ($errors[0]['message'] ?? 'child result did not satisfy the preservation contract')) : implode("\n", $output)),
        );
    }
    if ($originalCase === false) putenv('COMPAT_UPGRADE_CASE');
    else putenv('COMPAT_UPGRADE_CASE=' . $originalCase);
    $passed = !array_filter($caseEvidence, function ($evidence) { return $evidence['status'] !== 'PASS'; });
    return array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'case' => 'all',
        'required_cases' => $requiredCases,
        'cases' => $caseEvidence,
        'plugin_active' => $passed,
        'ready' => $passed,
    );
}

if ($purpose === 'upgrade-preservation' && $upgradeCase === 'all') {
    $upgradePreservation = gigpress_upgrade_preservation_run_all($upgradePreservationRequiredCases);
    if ($upgradePreservation['status'] !== 'PASS') {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Aggregate upgrade preservation registry did not satisfy every required case', 'file' => __FILE__, 'line' => __LINE__);
    }
    $result = array(
        'status' => $pluginErrors ? 'FAIL' : 'PASS',
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'plugin_active' => $upgradePreservation['plugin_active'],
        'menu_slugs' => array(),
        'menu_warnings' => array(),
        'menu_order_conflict' => false,
        'plugin_errors' => $pluginErrors,
        'fatal' => $fatal,
        'purpose' => $purpose,
        'csv_roundtrip' => null,
        'full_workflows' => null,
        'upgrade_preservation' => $upgradePreservation,
        'fixture_runtime' => null,
        'real_plugin_runtime' => null,
        'real_plugin_inventory' => array(),
        'menu_trace' => null,
    );
    echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['status'] === 'PASS' ? 0 : 1);
}
if ($purpose === 'upgrade-preservation' || $purpose === 'public-publishing') {
    $fixtureVersion = $upgradeCase === 'versions-1.0-1.2' ? '1.0' : ($upgradeCase === 'versions-1.3-1.5' ? '1.3' : (in_array($upgradeCase, array('current-1.6', 'settings-repeat'), true) ? '1.6' : '1.4'));
    $fixturePath = '/var/www/html/wp-content/plugins/gigpress/tests/compat/fixtures/upgrade-preservation/' . $fixtureVersion . '.php';
    $upgradeFixture = is_readable($fixturePath) ? require $fixturePath : null;
    if (!is_array($upgradeFixture) || ($upgradeFixture['label'] ?? '') !== 'reconstructed-' . $fixtureVersion) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Reconstructed upgrade fixture is unavailable', 'file' => $fixturePath, 'line' => 0);
    }
    if (!upgrade_preservation_seed($upgradeFixture)) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Upgrade fixture did not receive its nondefault prefix', 'file' => __FILE__, 'line' => __LINE__);
    }
    if ($purpose === 'public-publishing' && $upgradeCase === 'tracer-1.4') {
        require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
        $GLOBALS['gigpress_public_source_snapshot'] = gigpress_upgrade_preservation_snapshot();
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
/* WP_INSTALLING skips active-plugin loading in child probes; activation must
 * include the real plugin afresh before administration cases use its constants. */
if (in_array($purpose, array('upgrade-preservation', 'administration-workflows', 'public-publishing'), true) && !$skipGigPressActivation) {
    deactivate_plugins($plugin, false, false);
}
if (($purpose === 'fixture-activate' || !$fixturePurpose) && !$skipGigPressActivation) {
    $activation = activate_plugin($plugin, '', false, false);
    if (is_wp_error($activation)) {
        echo json_encode(array('status' => 'FAIL', 'reason' => $activation->get_error_message(), 'plugin_errors' => $pluginErrors)) . PHP_EOL;
        exit(1);
    }
} elseif (!$skipGigPressActivation && !is_plugin_active($plugin)) {
    $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Controlled fixture lost active state', 'file' => __FILE__, 'line' => __LINE__);
}
if ($purpose === 'upgrade-preservation' && !in_array($upgradeCase, array('versions-1.0-1.2', 'versions-1.3-1.5', 'current-1.6', 'settings-repeat'), true)) {
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
if ($purpose === 'upgrade-preservation' && $upgradeCase === 'versions-1.3-1.5') {
    require WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
    $upgradePreservation = gigpress_upgrade_preservation_run_versions(array('1.3', '1.5'));
    if ($upgradePreservation['status'] !== 'PASS') $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Later-version migration matrix did not converge', 'file' => __FILE__, 'line' => __LINE__);
}
if ($purpose === 'upgrade-preservation' && in_array($upgradeCase, array('current-1.6', 'settings-repeat'), true)) {
    require WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
    $upgradePreservation = $upgradeCase === 'current-1.6' ? gigpress_upgrade_preservation_run_current() : gigpress_upgrade_preservation_run_settings_repeat();
    if ($upgradePreservation['status'] !== 'PASS') $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Current-version preservation matrix did not converge', 'file' => __FILE__, 'line' => __LINE__);
}
if ($purpose === 'upgrade-preservation' && in_array($upgradeCase, array('show-lifecycle', 'optional-request-fields', 'entity-guards', 'tour-undo'), true)) {
    require WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-crud.php';
    $upgradePreservation = gigpress_upgrade_preservation_run_crud($upgradeCase);
    if ($upgradePreservation['status'] !== 'PASS') $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Post-upgrade show lifecycle did not preserve handler semantics', 'file' => __FILE__, 'line' => __LINE__);
}
if (($purpose === 'diagnose-menu' && (getenv('COMPAT_CONFLICT_MODE') ?: '') === 'exact-key-late-add')
    || ($purpose === 'admin-menu' && (getenv('COMPAT_CONFLICT_MODE') ?: '') === 'order-only')) {
    $fixtureActivation = activate_plugin('menu-conflict-plugin.php', '', false, false);
    if (is_wp_error($fixtureActivation)) {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => $fixtureActivation->get_error_message(), 'file' => __FILE__, 'line' => __LINE__);
    }
}
require ABSPATH . 'wp-admin/menu.php';
if ($purpose === 'administration-workflows') {
    $administrationWorkflows = $upgradeCase === 'all' ? gigpress_administration_all($administrationRequiredCases)
        : (in_array($upgradeCase, $administrationRequiredCases, true) ? gigpress_administration_case($upgradeCase)
        : array('case' => $upgradeCase, 'status' => 'FAIL', 'checks' => array(), 'assertion_count' => 0));
}
if ($purpose === 'public-publishing') {
    $registryPath = WP_PLUGIN_DIR . '/gigpress/tests/compat/public-publishing.php';
    if (!is_readable($registryPath)) {
        $publicPublishing = array('case' => $publicCase, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'public dispatcher module is unreadable');
    } else {
        require_once $registryPath;
        $publicPublishing = gigpress_public_publishing_case_dispatch($publicCase);
    }
    if (($publicPublishing['status'] ?? 'FAIL') !== 'PASS') {
        $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Public publishing case did not satisfy its fail-closed contract', 'file' => __FILE__, 'line' => __LINE__);
    }
}
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
    'status' => $pluginErrors || ($purpose === 'administration-workflows' && ($administrationWorkflows['status'] ?? '') !== 'PASS') || ($purpose === 'public-publishing' && ($publicPublishing['status'] ?? '') !== 'PASS') ? 'FAIL' : 'PASS',
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
    'administration_workflows' => $administrationWorkflows,
    'public_publishing' => $publicPublishing,
    'fixture_runtime' => $fixtureRuntime,
    'real_plugin_runtime' => $realPluginRuntime,
    'real_plugin_inventory' => $real_plugin_inventory,
    'menu_trace' => $menuTrace,
);
echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);

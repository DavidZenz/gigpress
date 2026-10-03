<?php
/* The probe is executed only inside the disposable WordPress container. */
error_reporting(E_ALL);
ini_set('display_errors', '0');

$pluginErrors = array();
$fatal = null;
set_error_handler(function ($severity, $message, $file, $line) use (&$pluginErrors) {
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

define('WP_INSTALLING', true);
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_blog_installed()) {
    wp_install('GigPress Compatibility', 'compat-admin', 'compat-admin@example.test', true, '', 'compat-password');
}
$admin = get_user_by('login', 'compat-admin');
wp_set_current_user($admin->ID);
$plugin = 'gigpress/gigpress.php';
$activation = activate_plugin($plugin, '', false, false);
if (is_wp_error($activation)) {
    echo json_encode(array('status' => 'FAIL', 'reason' => $activation->get_error_message(), 'plugin_errors' => $pluginErrors)) . PHP_EOL;
    exit(1);
}
do_action('admin_menu');
global $menu;
$menuSlugs = array();
foreach ((array) $menu as $item) {
    if (isset($item[2])) {
        $menuSlugs[] = basename((string) $item[2]);
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
    'purpose' => getenv('COMPAT_PURPOSE') ?: 'activation-menu',
);
echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);

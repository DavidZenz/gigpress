<?php
/*
Plugin Name: GigPress Compatibility PHP Floor Fixture
Description: Controlled test-only plugin for the active-but-inert PHP floor lifecycle.
Version: 1.0.0
*/

if (PHP_VERSION_ID < 80300) {
    add_action('admin_notices', 'php_floor_fixture_compatibility_notice');
    function php_floor_fixture_compatibility_notice() {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-warning"><p>PHP 8.3 or newer is required for the controlled compatibility fixture.</p></div>';
        }
    }
    return;
}

if (PHP_VERSION_ID >= 80300) {
    function php_floor_fixture_normal_surface() {
        return 'available';
    }

    add_action('init', 'php_floor_fixture_normal_surface');
}

<?php
/* Real Settings API callback/update/read-back cases; HTTP options.php is a separate browser gate. */
function gigpress_administration_settings_render($settings) {
    $GLOBALS['gpo'] = $settings;
    require_once WP_PLUGIN_DIR . '/gigpress/admin/settings.php';
    ob_start();
    gigpress_settings();
    return ob_get_clean();
}

function gigpress_administration_settings_form_context($enabled = true) {
    $_SERVER['REQUEST_METHOD'] = $enabled ? 'POST' : 'GET';
    $_POST = $enabled ? array('option_page' => 'gigpress', 'action' => 'update') : array();
    $_REQUEST = $_POST;
}

function gigpress_administration_settings_case($case) {
    global $wp_registered_settings;
    register_gigpress_settings();
    $checks = array();
    $baseline = get_option('gigpress_settings');
    $baseline = array_merge($baseline, array(
        'unknown_scalar' => 'extension setting', 'unknown_nested' => array('zero' => 0, 'false' => false, 'empty' => '', 'list' => array(1, '2')),
        'unknown_false' => false, 'unknown_zero' => 0, 'unknown_empty' => '',
        'default_date' => '2033-03-04', 'default_time' => '19:17:00', 'default_artist' => 42, 'default_venue' => 43,
        'default_ages' => '', 'default_title' => 'Stored title', 'welcome' => 'no', 'related_date' => 'now',
    ));
    gigpress_administration_settings_form_context(false);
    update_option('gigpress_settings', $baseline);
    $html = gigpress_administration_settings_render($baseline);
    $checks['registered_sanitize_callback'] = ($wp_registered_settings['gigpress_settings']['sanitize_callback'] ?? null) === 'gigpress_sanitize_settings';
    $checks['existing_option_group'] = ($wp_registered_settings['gigpress_settings']['group'] ?? null) === 'gigpress';
    $checks['rendered_real_label'] = strpos($html, 'name="gigpress_settings[artist_label]"') !== false;
    $checks['real_options_form'] = strpos($html, 'action="options.php"') !== false && preg_match('/name=[\x22\x27]option_page[\x22\x27] value=[\x22\x27]gigpress[\x22\x27]/', $html) === 1;
    gigpress_administration_settings_form_context();
    $submitted = array('artist_label' => 'Performers', 'db_version' => '0', 'welcome' => 'forged', 'default_date' => '1990-01-01', 'unknown_nested' => array('forged' => true));
    update_option('gigpress_settings', $submitted);
    $expected = $baseline;
    $expected['artist_label'] = 'Performers';
    $saved = get_option('gigpress_settings');
    $checks['editable_label_saved_and_reloaded'] = ($saved['artist_label'] ?? null) === 'Performers';
    $checks['exact_baseline_owned_keys_preserved'] = $saved === $expected;
    update_option('gigpress_settings', $submitted);
    $checks['repeated_registered_save_idempotent'] = get_option('gigpress_settings') === $expected;
    $GLOBALS['wp_settings_errors'] = array();
    update_option('gigpress_settings', 'malformed');
    $checks['malformed_array_preserves_storage'] = get_option('gigpress_settings') === $expected;
    $checks['malformed_array_text_error'] = count(get_settings_errors('gigpress_settings')) > 0;
    $GLOBALS['wp_settings_errors'] = array();
    update_option('gigpress_settings', array('artist_label' => array('invalid'), 'rss_limit' => '-2', 'shows_page' => 'javascript:alert(1)'));
    $checks['invalid_editables_preserve_storage'] = get_option('gigpress_settings') === $expected;
    $checks['invalid_editables_text_errors'] = count(get_settings_errors('gigpress_settings')) === 3;
    gigpress_administration_settings_form_context(false);
    update_option('gigpress_settings', array('default_date' => '2034-06-07', 'default_time' => '00:00:01', 'welcome' => 'yes'));
    $expected['default_date'] = '2034-06-07';
    $expected['default_time'] = '00:00:01';
    $expected['welcome'] = 'yes';
    $checks['programmatic_sticky_welcome_update_and_reload'] = get_option('gigpress_settings') === $expected;
    $checks['absent_programmatic_controls_preserved'] = (get_option('gigpress_settings')['artist_link'] ?? null) === $baseline['artist_link'];
    return array('case' => $case, 'checks' => $checks);
}

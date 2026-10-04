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

function gigpress_administration_settings_dom($html) {
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($dom);
}

/* Serialize successful controls in document order, including explicit unchecked
 * hidden values. This observes markup; it is not an HTTP/browser submission. */
function gigpress_administration_settings_values($xpath, $uncheck = false) {
    $values = array();
    foreach ($xpath->query('//form//input | //form//select') as $control) {
        if (!preg_match('/^gigpress_settings\[([^]]+)\]$/', $control->getAttribute('name'), $match)) continue;
        $type = $control->getAttribute('type');
        if (($type === 'checkbox' && ($uncheck || !$control->hasAttribute('checked'))) || ($type === 'radio' && !$control->hasAttribute('checked'))) continue;
        $value = $control->getAttribute('value');
        if ($control->nodeName === 'select') {
            $options = $xpath->query('./option[@selected]', $control);
            if (!$options->length) $options = $xpath->query('./option', $control);
            if (!$options->length) continue;
            $value = $options->item(0)->getAttribute('value');
        }
        $values[$match[1]] = $value;
    }
    return $values;
}

function gigpress_administration_settings_sections($baseline) {
    $checks = array();
    $groups = array(
        'display-formatting' => array('Display & formatting', array('date_format','date_format_long','time_format','alternate_clock','display_country','country_view')),
        'show-labels-links' => array('Show labels & links', array('shows_page','noupcoming','nopast','artist_label','tour_label','external_link_label','buy_tickets_label','age_restrictions','artist_link','target_blank')),
        'related-posts' => array('Related posts', array('related_position','related_heading','autocreate_post','related_category','category_exclude','relatedlink_date','relatedlink_city','relatedlink_notes','related')),
        'feeds' => array('Feeds', array('rss_head','display_subscriptions','rss_title','rss_limit')),
        'permissions' => array('Permissions', array('user_level')),
        'advanced' => array('Advanced', array('output_schema_json','load_jquery','disable_css','disable_js')),
    );
    $baseline['artist_label'] = '\" autofocus><script>alert(1)</script>&';
    $baseline['date_format'] = '\\<\\b\\>Y\\<\\/\\b\\>';
    $baseline['related_position'] = 'custom \"/><script>alert(2)</script>';
    $baseline['output_schema_json'] = 'custom-schema';
    $baseline['user_level'] = 'custom_capability';
    $baseline['related_category'] = 'gone \"/><script>alert(3)</script>';
    $baseline['country_view'] = 'custom-country';
    $html = gigpress_administration_settings_render($baseline);
    $xpath = gigpress_administration_settings_dom($html);
    $checks['six_visible_sections'] = $xpath->query('//section[contains(@class,"gp-settings-section")]')->length === 6;
    $checks['one_options_form'] = $xpath->query('//form[@action="options.php"]')->length === 1;
    $checks['one_save_action'] = $xpath->query('//form//input[@type="submit"] | //form//button[@type="submit"]')->length === 1;
    $checks['jump_navigation_label'] = strpos($html, 'Jump to section') !== false;
    foreach ($groups as $slug => $group) {
        $id = 'gp-settings-' . $slug;
        $headings = $xpath->query('//h2[@id="' . $id . '"]');
        $checks['heading_' . $slug] = $headings->length === 1 && trim($headings->item(0)->textContent) === $group[0];
        $checks['jump_' . $slug] = $xpath->query('//nav//a[@href="#' . $id . '"]')->length === 1;
        $checks['visible_' . $slug] = $xpath->query('//section[@aria-labelledby="' . $id . '"][@hidden or @style] | //details//h2[@id="' . $id . '"]')->length === 0;
        foreach ($group[1] as $key) {
            $controls = $xpath->query('//section[@aria-labelledby="' . $id . '"]//input[@name="gigpress_settings[' . $key . ']" and @type!="hidden"] | //section[@aria-labelledby="' . $id . '"]//select[@name="gigpress_settings[' . $key . ']"]');
            $checks['grouped_' . $key] = $controls->length > 0;
            $labels = $controls->length > 0;
            $help = $controls->length > 0;
            foreach ($controls as $control) {
                $control_id = $control->getAttribute('id');
                $help_id = $control->getAttribute('aria-describedby');
                $labels = $labels && $control_id !== '' && $xpath->query('//label[@for="' . $control_id . '"]')->length === 1;
                $help = $help && $help_id !== '' && $xpath->query('//*[@id="' . $help_id . '"]')->length === 1;
            }
            $checks['associated_label_' . $key] = $labels;
            $checks['associated_help_' . $key] = $help;
            $checks['semantic_row_' . $key] = $xpath->query('//tr[@id="gp-setting-row-' . $key . '"]/th[@scope="row"]')->length === 1;
        }
    }
    foreach (array('related_position', 'output_schema_json') as $key) {
        $checks['radio_fieldset_' . $key] = $xpath->query('//tr[@id="gp-setting-row-' . $key . '"]//fieldset/legend')->length === 1;
    }
    foreach (array('country_view','related_position','output_schema_json','user_level','related_category') as $key) {
        $values = gigpress_administration_settings_values($xpath);
        $checks['unknown_choice_represented_' . $key] = ($values[$key] ?? null) === $baseline[$key];
    }
    $checks['hostile_attributes_encoded'] = $xpath->query('//input[@name="gigpress_settings[artist_label]"]')->item(0)->getAttribute('value') === $baseline['artist_label'] && $xpath->query('//script | //*[@autofocus]')->length === 0;
    $checks['format_example_encoded'] = strpos($html, '&lt;b&gt;') !== false && $xpath->query('//p[contains(@class,"description")]//b')->length === 0;
    $checks['format_guidance_link'] = $xpath->query('//a[@href="https://wordpress.org/documentation/article/customize-date-and-time-format/"]')->length > 0;
    $checks['no_duplicate_ids'] = true;
    $seen = array();
    foreach ($xpath->query('//*[@id]') as $node) {
        $id = $node->getAttribute('id');
        if (isset($seen[$id])) $checks['no_duplicate_ids'] = false;
        $seen[$id] = true;
    }
    return $checks;
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
    if ($case === 'settings-sections') return array('case' => $case, 'checks' => gigpress_administration_settings_sections($baseline));
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
    $unknown = array('country_view' => 'regional', 'related_position' => 'custom-position', 'output_schema_json' => 'custom-schema', 'user_level' => 'custom_cap', 'related_category' => 'retired-category');
    update_option('gigpress_settings', $unknown);
    $expected = array_replace($expected, $unknown);
    $values = gigpress_administration_settings_values(gigpress_administration_settings_dom(gigpress_administration_settings_render($expected)));
    gigpress_administration_settings_form_context();
    update_option('gigpress_settings', $values);
    $checks['unchanged_complete_form_exact_storage'] = get_option('gigpress_settings') === $expected;
    update_option('gigpress_settings', $values);
    $checks['repeated_complete_form_idempotent'] = get_option('gigpress_settings') === $expected;
    foreach ($unknown as $key => $value) $checks['untouched_unknown_' . $key] = (get_option('gigpress_settings')[$key] ?? null) === $value;
    $GLOBALS['wp_settings_errors'] = array();
    update_option('gigpress_settings', array('user_level' => 'arbitrary-cap', 'country_view' => 'arbitrary-country', 'related_position' => 'arbitrary-position', 'output_schema_json' => 'arbitrary-schema', 'related_category' => 'arbitrary-category'));
    $checks['new_unknown_choices_rejected'] = get_option('gigpress_settings') === $expected && count(get_settings_errors('gigpress_settings')) === 5;
    $flags = array('alternate_clock','display_country','artist_link','target_blank','autocreate_post','category_exclude','relatedlink_date','relatedlink_city','relatedlink_notes','rss_head','display_subscriptions','load_jquery','disable_css','disable_js');
    update_option('gigpress_settings', array_fill_keys($flags, '1'));
    foreach ($flags as $key) $expected[$key] = 1;
    $unchecked = gigpress_administration_settings_values(gigpress_administration_settings_dom(gigpress_administration_settings_render($expected)), true);
    update_option('gigpress_settings', $unchecked);
    foreach ($flags as $key) {
        $expected[$key] = 0;
        $checks['unchecked_' . $key] = (get_option('gigpress_settings')[$key] ?? null) === 0;
    }
    $checks['unchecked_preserves_other_storage'] = get_option('gigpress_settings') === $expected;
    $supported = array('country_view' => '', 'related_position' => 'before', 'output_schema_json' => 'n', 'user_level' => 'activate_plugins', 'related_category' => (int) get_option('default_category'));
    update_option('gigpress_settings', $supported);
    $expected = array_replace($expected, $supported);
    $checks['supported_replacements_exact_storage'] = get_option('gigpress_settings') === $expected;
    foreach (array('country_view' => array('long',''), 'related_position' => array('before','after','nowhere'), 'output_schema_json' => array('y','n'), 'user_level' => array('activate_plugins','edit_published_posts','publish_posts','edit_posts')) as $key => $choices) {
        foreach ($choices as $choice) {
            update_option('gigpress_settings', array($key => $choice));
            $expected[$key] = $choice;
            $checks['supported_' . $key . '_' . ($choice === '' ? 'empty' : $choice)] = get_option('gigpress_settings') === $expected;
        }
    }
    gigpress_administration_settings_form_context(false);
    require_once WP_PLUGIN_DIR . '/gigpress/tests/compat/administration-entry.php';
    $GLOBALS['gpo'] = $expected;
    $request = gigpress_administration_entry_base();
    $request['show_notes'] = 'Settings sticky integration';
    $created = gigpress_administration_entry_request($request);
    $expected = array_replace($expected, array('default_date' => '2032-05-06', 'default_time' => '00:00:01', 'default_ages' => 'Not sure',
        'default_artist' => $request['show_artist_id'], 'default_venue' => $request['show_venue_id'], 'default_tour' => 0));
    $checks['real_show_handler_sticky_defaults_and_unknown_baseline'] = ($created['outcome']['status'] ?? null) === 'saved' && get_option('gigpress_settings') === $expected;
    return array('case' => $case, 'checks' => $checks);
}

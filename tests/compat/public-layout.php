<?php
/* Layout and compact-surface contracts use the migrated fixture plus public-only records. */

function gigpress_public_layout_capture($callback) {
	ob_start();
	$value = call_user_func($callback);
	return array($value, ob_get_clean());
}

function gigpress_public_layout_seed_supplemental() {
	global $wpdb;
	$artistRows = array(
		array('artist_id' => 701, 'artist_name' => 'Æther Orchestra 東京' . str_repeat('x', 80), 'artist_alpha' => 'aether orchestra', 'artist_order' => 1, 'artist_url' => ''),
		array('artist_id' => 702, 'artist_name' => 'Second Layout Artist', 'artist_alpha' => 'second layout artist', 'artist_order' => 2, 'artist_url' => ''),
	);
	$venueRows = array(
		array('venue_id' => 701, 'venue_name' => 'LongVenue' . str_repeat('unbroken', 24), 'venue_address' => 'Long address ' . str_repeat('address-', 20), 'venue_city' => 'Vienna', 'venue_state' => 'AT', 'venue_postal_code' => '1010', 'venue_country' => 'AT', 'venue_url' => '', 'venue_phone' => '+43 555 0101'),
		array('venue_id' => 702, 'venue_name' => 'Status Hall', 'venue_address' => '1 Public Way', 'venue_city' => 'Graz', 'venue_state' => '', 'venue_postal_code' => '8010', 'venue_country' => 'AT', 'venue_url' => '', 'venue_phone' => ''),
	);
	$showRows = array(
		array('show_id' => 801, 'show_artist_id' => 701, 'show_venue_id' => 701, 'show_tour_id' => 0, 'show_date' => '2031-05-10', 'show_multi' => 1, 'show_time' => '00:00:01', 'show_expire' => '2031-05-12', 'show_price' => '24.00', 'show_tix_url' => 'https://tickets.example.test/saved-801', 'show_tix_phone' => '+43 555 0180', 'show_ages' => 'All Ages', 'show_notes' => '<p>Doors at <strong>19:00</strong></p><ul><li>' . str_repeat('Readable long detail ', 35) . '</li></ul>', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => 'Long address ' . str_repeat('address-', 20), 'show_locale' => 'Vienna', 'show_country' => 'AT', 'show_venue' => 'LongVenue', 'show_venue_url' => '', 'show_venue_phone' => '+43 555 0101'),
		array('show_id' => 802, 'show_artist_id' => 701, 'show_venue_id' => 702, 'show_tour_id' => 0, 'show_date' => '2031-05-14', 'show_multi' => 0, 'show_time' => '00:00:00', 'show_expire' => '2031-05-14', 'show_price' => 'Free', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => '', 'show_notes' => 'Sold-out details remain visible.', 'show_related' => 0, 'show_status' => 'soldout', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '1 Public Way', 'show_locale' => 'Graz', 'show_country' => 'AT', 'show_venue' => 'Status Hall', 'show_venue_url' => '', 'show_venue_phone' => ''),
		array('show_id' => 803, 'show_artist_id' => 702, 'show_venue_id' => 702, 'show_tour_id' => 0, 'show_date' => '2031-05-16', 'show_multi' => 0, 'show_time' => '00:00:01', 'show_expire' => '2031-05-16', 'show_price' => '15.00', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => '', 'show_notes' => 'Cancelled details remain visible.', 'show_related' => 0, 'show_status' => 'cancelled', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '1 Public Way', 'show_locale' => 'Graz', 'show_country' => 'AT', 'show_venue' => 'Status Hall', 'show_venue_url' => '', 'show_venue_phone' => ''),
	);
	foreach ($artistRows as $row) if ($wpdb->insert(GIGPRESS_ARTISTS, $row) === false) return false;
	foreach ($venueRows as $row) if ($wpdb->insert(GIGPRESS_VENUES, $row) === false) return false;
	foreach ($showRows as $row) if ($wpdb->insert(GIGPRESS_SHOWS, $row) === false) return false;
	return true;
}

function gigpress_public_layout_override_location_path($location, $child, $parent) {
	if ($location === 'child') return $child . '/gigpress-templates';
	if ($location === 'parent') return $parent . '/gigpress-templates';
	return WP_CONTENT_DIR . '/gigpress-templates';
}

function gigpress_public_layout_override_write($directory, $template, $source) {
	if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) return false;
	return file_put_contents($directory . '/' . $template . '.php', $source) !== false;
}

function gigpress_public_layout_override_cleanup($fixture, $child, $parent, $removeContentDirectory) {
	$templates = array_merge($fixture['structural'], array('shows-artist-heading', 'shows-tour-heading', 'shows-list-footer'));
	$locations = array('child' => $child . '/gigpress-templates', 'parent' => $parent . '/gigpress-templates', 'wp-content' => WP_CONTENT_DIR . '/gigpress-templates');
	foreach ($locations as $directory) {
		foreach ($templates as $template) {
			$file = $directory . '/' . $template . '.php';
			if (is_file($file)) @unlink($file);
		}
		if (is_dir($directory) && ($directory === WP_CONTENT_DIR . '/gigpress-templates' ? $removeContentDirectory : true)) @rmdir($directory);
	}
	foreach (array($child, $parent) as $directory) if (is_dir($directory)) @rmdir($directory);
}

function gigpress_public_layout_override_case() {
	global $gpo;
	$fixturePath = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/public-publishing/overrides.php';
	$fixture = is_readable($fixturePath) ? require $fixturePath : null;
	$checks = array(
		'fixture_loaded' => is_array($fixture) && isset($fixture['files'], $fixture['structural']),
		'child_parent_wp_content_bundled_priority' => false,
		'complete_child_parent_wp_content_sets_remain_owned' => false,
		'bundled_start_owner_body_is_unmarked' => false,
		'owner_start_bundled_body_is_unmarked' => false,
		'owner_end_is_unmarked' => false,
		'cross_location_mixed_set_is_unmarked' => false,
		'explicit_owner_opt_in_is_respected' => false,
		'heading_and_footer_overrides_render' => false,
		'owner_context_and_hooks_preserved' => false,
		'optional_css_js_and_custom_stylesheet_contracts_preserved' => false,
	);
	if (!$checks['fixture_loaded']) return array('case' => 'override-priority', 'checks' => $checks, 'reason' => 'override fixture is unreadable');
	$root = WP_CONTENT_DIR . '/compat-public-layout-' . str_replace('.', '', uniqid('', true));
	$child = $root . '/child';
	$parent = $root . '/parent';
	$contentDirectory = WP_CONTENT_DIR . '/gigpress-templates';
	$removeContentDirectory = !is_dir($contentDirectory);
	$targetNames = array_merge($fixture['structural'], array('shows-artist-heading', 'shows-tour-heading', 'shows-list-footer'));
	foreach ($targetNames as $template) {
		if (is_file($contentDirectory . '/' . $template . '.php')) {
			$checks['fixture_loaded'] = false;
			return array('case' => 'override-priority', 'checks' => $checks, 'reason' => 'wp-content override fixture path is already occupied');
		}
	}
	$childFilter = function () use ($child) { return $child; };
	$parentFilter = function () use ($parent) { return $parent; };
	add_filter('stylesheet_directory', $childFilter, 99, 1);
	add_filter('template_directory', $parentFilter, 99, 1);
	$gpo['display_subscriptions'] = 1;
	$gpo['display_country'] = 1;
	$okWrites = @mkdir($child, 0775, true) && @mkdir($parent, 0775, true);
	$put = function ($location, $template, $sourceName = null) use ($fixture, $child, $parent) {
		$directory = gigpress_public_layout_override_location_path($location, $child, $parent);
		$sourceName = $sourceName ?: $template;
		$source = str_replace('__LOCATION__', $location, $fixture['files'][$sourceName]);
		return gigpress_public_layout_override_write($directory, $template, $source);
	};
	$clear = function () use ($fixture, $child, $parent) {
		foreach (array('child' => $child, 'parent' => $parent, 'wp-content' => WP_CONTENT_DIR) as $location => $root) {
			$directory = $location === 'wp-content' ? $root . '/gigpress-templates' : $root . '/gigpress-templates';
			foreach (array_merge($fixture['structural'], array('shows-artist-heading', 'shows-tour-heading', 'shows-list-footer')) as $template) {
				$file = $directory . '/' . $template . '.php';
				if (is_file($file)) @unlink($file);
			}
		}
	};
	$sourcePath = function ($location, $template) use ($child, $parent) {
		return gigpress_public_layout_override_location_path($location, $child, $parent) . '/' . $template . '.php';
	};
	try {
		$priorityPass = $okWrites;
		foreach ($fixture['structural'] as $template) {
			foreach (array('child', 'parent', 'wp-content') as $location) $priorityPass = $priorityPass && $put($location, $template);
			$resolved = gigpress_template($template === 'shows-list-start' ? 'shows-list-start' : ($template === 'shows-list' ? 'shows-list' : 'shows-list-end'));
			$priorityPass = $priorityPass && realpath($resolved) === realpath($sourcePath('child', $template));
			@unlink($sourcePath('child', $template));
			$resolved = gigpress_template($template);
			$priorityPass = $priorityPass && realpath($resolved) === realpath($sourcePath('parent', $template));
			@unlink($sourcePath('parent', $template));
			$resolved = gigpress_template($template);
			$priorityPass = $priorityPass && realpath($resolved) === realpath($sourcePath('wp-content', $template));
			@unlink($sourcePath('wp-content', $template));
			$resolved = gigpress_template($template);
			$bundled = WP_PLUGIN_DIR . '/gigpress/templates/' . $template . '.php';
			$priorityPass = $priorityPass && realpath($resolved) === realpath($bundled) && basename($resolved) === $template . '.php';
		}
		$checks['child_parent_wp_content_bundled_priority'] = $priorityPass;

		$completePass = true;
		$contextPass = true;
		foreach (array('child', 'parent', 'wp-content') as $location) {
			$clear();
			foreach ($fixture['structural'] as $template) $completePass = $completePass && $put($location, $template);
			$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
			$completePass = $completePass && strpos($html, 'class="compat-override-start') !== false
				&& strpos($html, 'compat-' . $location . '-body') !== false && strpos($html, 'compat-' . $location . '-end') !== false
				&& strpos($html, 'gigpress-layout-bundled') === false;
			$contextOk = strpos($html, 'data-show-id="109"') !== false && strpos($html, 'class="gigpress-row active') !== false
				&& strpos($html, '&quot;artist&quot;:false') !== false && strpos($html, '&quot;group_artists&quot;:&quot;no&quot;') !== false
				&& strpos($html, '&quot;total_artists&quot;:3') !== false && strpos($html, '&quot;scope&quot;:&quot;upcoming&quot;') !== false
				&& strpos($html, '&quot;cols&quot;:3') !== false && strpos($html, '&quot;artist_label&quot;:&quot;Acts&quot;') !== false;
			$contextPass = $contextPass && $contextOk;
		}
		$checks['complete_child_parent_wp_content_sets_remain_owned'] = $completePass;
		$checks['owner_context_and_hooks_preserved'] = $contextPass;

		$clear();
		$put('parent', 'shows-list');
		$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$checks['bundled_start_owner_body_is_unmarked'] = strpos($html, 'gigpress-layout-bundled') === false && strpos($html, 'compat-parent-body') !== false;

		$clear();
		$put('child', 'shows-list-start');
		$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$checks['owner_start_bundled_body_is_unmarked'] = strpos($html, 'compat-override-start') !== false && strpos($html, 'gigpress-layout-bundled') === false;

		$clear();
		$put('child', 'shows-list-end');
		$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$checks['owner_end_is_unmarked'] = strpos($html, 'compat-child-end') !== false && strpos($html, 'gigpress-layout-bundled') === false;

		$clear();
		$put('child', 'shows-list-start');
		$put('parent', 'shows-list');
		$put('wp-content', 'shows-list-end');
		$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$checks['cross_location_mixed_set_is_unmarked'] = strpos($html, 'compat-override-start') !== false
			&& strpos($html, 'compat-parent-body') !== false && strpos($html, 'compat-wp-content-end') !== false
			&& strpos($html, 'gigpress-layout-bundled') === false;

		$clear();
		$put('child', 'shows-list-start', 'shows-list-start-explicit');
		$put('child', 'shows-list');
		$put('child', 'shows-list-end');
		$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$checks['explicit_owner_opt_in_is_respected'] = strpos($html, 'class="compat-override-start gigpress-layout-bundled"') !== false;

		$clear();
		foreach (array('shows-artist-heading', 'shows-tour-heading', 'shows-list-footer') as $template) $put('child', $template);
		$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="yes"]');
		$checks['heading_and_footer_overrides_render'] = strpos($html, 'compat-child-artist-heading') !== false
			&& strpos($html, 'compat-child-tour-heading') !== false && strpos($html, 'compat-child-footer') !== false;
		$pluginSource = file_get_contents(WP_PLUGIN_DIR . '/gigpress/gigpress.php');
		$cssSource = file_get_contents(WP_PLUGIN_DIR . '/gigpress/css/gigpress.css');
		$jsSource = file_get_contents(WP_PLUGIN_DIR . '/gigpress/scripts/gigpress.js');
		$checks['optional_css_js_and_custom_stylesheet_contracts_preserved'] = is_string($pluginSource) && is_string($cssSource) && is_string($jsSource)
			&& strpos($pluginSource, "if(empty(\$gpo['disable_css']))") !== false
			&& strpos($pluginSource, "get_stylesheet_directory().\"/gigpress.css\"") !== false
			&& strpos($pluginSource, "get_template_directory().\"/gigpress.css\"") !== false
			&& strpos($pluginSource, "if(empty(\$gpo['disable_js']))") !== false
			&& strpos($pluginSource, "wp_enqueue_script('gigpress-js'") !== false
			&& strpos($cssSource, '.gigpress-table:not(.gigpress-layout-bundled) div.gigpress-calendar-links') !== false
			&& strpos($jsSource, 'gigpress-links-toggle') !== false;
	} catch (Throwable $error) {
		$checks['fixture_loaded'] = false;
		$reason = $error->getMessage();
	} finally {
		remove_filter('stylesheet_directory', $childFilter, 99);
		remove_filter('template_directory', $parentFilter, 99);
		gigpress_public_layout_override_cleanup($fixture, $child, $parent, $removeContentDirectory);
	}
	return array('case' => 'override-priority', 'checks' => $checks, 'locations' => array('child', 'parent', 'wp-content', 'bundled'), 'reason' => $reason ?? null);
}

function gigpress_public_layout_case($case) {
	global $gpo, $wpdb, $post, $is_excerpt;
	$checks = array();
	if (!in_array($case, array('layout-main', 'layout-compact', 'override-priority'), true)) {
		return array('case' => $case, 'checks' => array('known_layout_case' => false));
	}
	if (!gigpress_public_layout_seed_supplemental()) {
		return array('case' => $case, 'checks' => array('supplemental_seeded' => false), 'reason' => 'public layout records could not be seeded');
	}
	$oldSettings = $gpo;
	$gpo['buy_tickets_label'] = 'Saved Label';
	$gpo['display_subscriptions'] = 1;
	$gpo['display_country'] = 1;
	$gpo['relatedlink_notes'] = 1;
	if ($case === 'override-priority') {
		$result = gigpress_public_layout_override_case();
		$gpo = $oldSettings;
		return $result;
	}
	$main = do_shortcode('[gigpress_shows scope="upcoming" group_artists="yes" artist_order="custom"]');
	$evidence = array();
	$checks['supplemental_seeded'] = strpos($main, 'data-show-id="801"') !== false && strpos($main, 'data-show-id="803"') !== false;
	if ($case === 'layout-main') {
		preg_match_all('/<tr class="gigpress-row [^"]*" data-show-id="(\d+)"/', $main, $rowIds);
		$ids = array_map('intval', $rowIds[1] ?? array());
		$evidence['actual_row_ids'] = $ids;
		$dateStart = mysql2date($gpo['date_format'], '2031-05-10');
		$dateEnd = mysql2date($gpo['date_format'], '2031-05-12');
		$artistHeading = strpos($main, 'id="artist-701"');
		$tableStart = strpos($main, 'class="gigpress-table upcoming gigpress-layout-bundled"');
		$checks['exact_show_order'] = $ids === array(109, 801, 802, 803);
		$checks['wide_table_and_bundled_opt_in'] = $tableStart !== false && strpos($main, '<th scope="col" class="gigpress-date">Date</th>') !== false && strpos($main, '<th scope="col" class="gigpress-venue">Venue</th>') !== false;
		$tourHeading = strpos($main, 'Preservation Tour');
		$lastShow = strpos($main, 'data-show-id="109"');
		$groupTable = $artistHeading === false ? false : strpos($main, '<table', $artistHeading);
		$checks['group_heading_precedes_blocks'] = $artistHeading !== false && $groupTable !== false && $artistHeading < $groupTable
			&& $tourHeading !== false && $lastShow !== false && $tourHeading < $lastShow;
		$ungrouped = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$checks['real_markup_labels'] = strpos($ungrouped, 'gigpress-mobile-label') !== false && strpos($ungrouped, '>Date:</span>') !== false && strpos($ungrouped, '>Acts:</span>') !== false && strpos($ungrouped, '>City:</span>') !== false && strpos($ungrouped, '>Venue:</span>') !== false;
		$visibleMain = preg_replace('/\s+/', ' ', wp_strip_all_tags($main));
		$checks['date_range_uses_saved_format'] = strpos($visibleMain, $dateStart . ' - ' . $dateEnd) !== false;
		$evidence['expected_range'] = $dateStart . ' - ' . $dateEnd;
		$evidence['range_found'] = $checks['date_range_uses_saved_format'];
		$evidence['artist_701_heading_found'] = $artistHeading !== false;
		$evidence['group_table_after_heading'] = $groupTable !== false && $artistHeading !== false && $groupTable > $artistHeading;
		$evidence['tour_heading_found'] = $tourHeading !== false;
		$evidence['tour_heading_before_show_109'] = $tourHeading !== false && $lastShow !== false && $tourHeading < $lastShow;
		$checks['no_time_omitted_and_midnight_visible'] = preg_match('/<tr class="gigpress-info active[^"]*" data-show-id="801">(.*?)<\/tr>/s', $main, $noTimeRow) === 1
			&& strpos($noTimeRow[1], '<span class="gigpress-info-label">Time:</span>') === false
			&& preg_match('/<tr class="gigpress-info soldout[^"]*" data-show-id="802">(.*?)<\/tr>/s', $main, $midnightRow) === 1
			&& strpos($midnightRow[1], '12:00am') !== false;
		$checks['ticket_label_destination_and_status_badges'] = strpos($main, 'href="https://tickets.example.test/saved-801"') !== false && strpos($main, '>Saved Label</a>') !== false
			&& strpos($main, '<strong class="gigpress-soldout">Sold Out</strong>') !== false
			&& strpos($main, '<strong class="gigpress-cancelled">Cancelled</strong>') !== false
			&& strpos($main, 'Sold-out details remain visible.') !== false && strpos($main, 'Cancelled details remain visible.') !== false;
		$checks['calendar_action_labels_and_placement'] = preg_match('/<tr class="gigpress-info active[^"]*" data-show-id="801">(.*?)<\/tr>/s', $main, $actionRow) === 1
			&& strpos($actionRow[1], 'Add to Google Calendar') !== false && strpos($actionRow[1], 'Download iCalendar') !== false
			&& strpos($actionRow[1], 'gigpress-calendar-actions') < strpos($actionRow[1], 'Admission:');
		$checks['long_details_are_rendered'] = strpos($main, str_repeat('Readable long detail ', 35)) !== false && strpos($main, 'All Ages') !== false && strpos($main, 'Long address') !== false;
		$css = file_get_contents(WP_PLUGIN_DIR . '/gigpress/css/gigpress.css');
		$checks['bundled_only_responsive_css'] = is_string($css) && strpos($css, '@media screen and (max-width: 42em)') !== false
			&& strpos($css, '.gigpress-layout-bundled > tbody > tr.gigpress-row') !== false
			&& strpos($css, 'content:') === false;
	} elseif ($case === 'layout-compact') {
		$compact = do_shortcode('[gigpress_shows scope="upcoming" artist="701"]');
		$artistFilter = strpos($compact, 'artist=701') !== false;
		$checks['subscription_setting_and_artist_filter'] = $artistFilter && strpos($compact, 'Subscribe:') !== false
			&& strpos($compact, 'class="gigpress-rss"') !== false && strpos($compact, 'class="gigpress-ical"') !== false;
		$checks['subscription_urls_and_visible_labels'] = strpos($compact, GIGPRESS_RSS . '&amp;artist=701') !== false
			&& strpos($compact, GIGPRESS_WEBCAL . '&amp;artist=701') !== false && strpos($compact, '>RSS</a>') !== false && strpos($compact, '>iCal</a>') !== false;
		$widget = '';
		if (class_exists('Gigpress_widget')) {
			ob_start();
			(new Gigpress_widget())->widget(array('before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'), array('title' => 'Layout', 'scope' => 'upcoming', 'limit' => 5, 'group_artists' => 'no', 'show_feeds' => 'no'));
			$widget = ob_get_clean();
		}
		$checks['widget_stays_compact_and_wraps'] = strpos($widget, '<ul class="gigpress-listing">') !== false && strpos($widget, 'LongVenue') !== false
			&& strpos(file_get_contents(WP_PLUGIN_DIR . '/gigpress/css/gigpress.css'), '.gigpress-listing li') !== false
			&& strpos(file_get_contents(WP_PLUGIN_DIR . '/gigpress/css/gigpress.css'), 'overflow-wrap: anywhere') !== false;
		$relatedShow = $wpdb->get_row('SELECT show_related FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = 109');
		$post = $relatedShow && $relatedShow->show_related ? get_post((int) $relatedShow->show_related) : null;
		$is_excerpt = false;
		$related = gigpress_show_related(array('scope' => 'upcoming'));
		$checks['related_stays_compact_and_readable'] = is_string($related) && strpos($related, 'class="gigpress-related-show') !== false
			&& strpos($related, 'gigpress-related-label') !== false && strpos($related, 'gigpress-layout-bundled') === false;
		$css = file_get_contents(WP_PLUGIN_DIR . '/gigpress/css/gigpress.css');
		$scopedStart = is_string($css) ? strpos($css, '/* Labels are real markup') : false;
		$scopedEnd = is_string($css) ? strpos($css, '/* These styles control the peek-a-boo', $scopedStart === false ? 0 : $scopedStart) : false;
		$scopedCss = ($scopedStart !== false && $scopedEnd !== false) ? substr($css, $scopedStart, $scopedEnd - $scopedStart) : false;
		$checks['theme_typography_and_width_inherit'] = is_string($scopedCss) && strpos($scopedCss, 'font-family:') === false
			&& !preg_match('/\s+max-width:\s*\d+(?:px|em|rem)/', $scopedCss) && !preg_match('/color:\s*#[0-9a-f]{3,8}/i', $scopedCss);
		$gpo = $oldSettings;
		$gpo['display_subscriptions'] = 0;
		$hiddenSubscriptions = do_shortcode('[gigpress_shows scope="upcoming"]');
		$checks['subscriptions_remain_configurable'] = strpos($hiddenSubscriptions, 'Subscribe:') === false;
	}
	$gpo = $oldSettings;
	return array('case' => $case, 'checks' => $checks, 'show_ids' => array(109, 801, 802, 803), 'evidence' => $evidence, 'fixture' => 'reconstructed-1.4-plus-supplemental');
}

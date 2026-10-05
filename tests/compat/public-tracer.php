<?php
/* The first real public slice starts from the reconstructed, migrated 1.4 manifest. */

function gigpress_public_tracer_capture($callback) {
	ob_start();
	$value = call_user_func($callback);
	$output = ob_get_clean();
	return array($value, $output);
}

function gigpress_public_tracer_http_reads($urls) {
	if (!function_exists('curl_init') || !function_exists('curl_multi_init')) return array();
	$multi = curl_multi_init();
	$handles = array();
	foreach ($urls as $key => $url) {
		$handle = curl_init($url);
		curl_setopt_array($handle, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_HTTPHEADER => array('Host: ' . wp_parse_url(home_url('/'), PHP_URL_HOST)),
		));
		curl_multi_add_handle($multi, $handle);
		$handles[$key] = $handle;
	}
	$running = null;
	do {
		$status = curl_multi_exec($multi, $running);
		if ($status !== CURLM_OK) break;
		if ($running > 0 && curl_multi_select($multi, 1.0) === -1) usleep(100000);
	} while ($running > 0);
	$results = array();
	foreach ($handles as $key => $handle) {
		$results[$key] = array(
			'body' => curl_multi_getcontent($handle),
			'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
			'content_type' => (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE),
			'error' => curl_error($handle),
		);
		curl_multi_remove_handle($multi, $handle);
	}
	curl_multi_close($multi);
	return $results;
}

function gigpress_public_tracer_case($case) {
	global $wpdb, $pluginErrors, $menuWarnings, $post, $is_excerpt;
	$fixturePath = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$fixture = is_readable($fixturePath) ? require $fixturePath : null;
	$source = $GLOBALS['gigpress_public_source_snapshot'] ?? null;
	$checks = array(
		'case' => $case === 'tracer-1.4',
		'canonical_source' => is_array($fixture) && ($fixture['label'] ?? null) === 'reconstructed-1.4',
		'nondefault_prefix' => is_array($fixture) && ($wpdb->prefix === $fixture['prefix']),
		'migration_ready' => false,
		'independent_manifest' => false,
		'linked_post_preserved' => false,
		'main_shortcode' => false,
		'legacy_wrapper' => false,
		'widget' => false,
		'related_post' => false,
		'rss_endpoint' => false,
		'ical_endpoint' => false,
		'child_override' => false,
		'calendar_ticket_labels' => false,
		'anonymous_http_endpoints' => false,
		'sequential_repeat_stable' => false,
		'concurrent_reads_stable' => false,
		'snapshot_unchanged' => false,
		'volatile_stamp_valid' => false,
	);
	if (!is_array($fixture) || !is_array($source) || !function_exists('gigpress_upgrade_preservation_snapshot')) return array('case' => $case, 'checks' => $checks, 'reason' => 'canonical fixture or migration snapshot is unavailable');

	unset($GLOBALS['gigpress_db_bootstrap_result']);
	$readiness = function_exists('gigpress_db_bootstrap') ? gigpress_db_bootstrap() : array('status' => 'blocked');
	$afterMigration = gigpress_upgrade_preservation_snapshot();
	list($manifestChecks, $manifestMatches) = gigpress_upgrade_preservation_manifest_matches($fixture, $afterMigration, $source);
	$checks['migration_ready'] = ($readiness['status'] ?? '') === 'ready'
		&& ($afterMigration['settings']['db_version'] ?? '') === ($fixture['expected']['version'] ?? '');
	$checks['independent_manifest'] = $manifestMatches && !in_array(false, $manifestChecks, true);
	$relatedPostId = (int) ($afterMigration['linked_posts'][109]['ID'] ?? 0);
	$checks['linked_post_preserved'] = $relatedPostId > 0 && ($source['linked_posts'][109] ?? null) === ($afterMigration['linked_posts'][109] ?? null);
	$beforeReads = $afterMigration;

	wp_set_current_user(0);
	$_GET = array(); $_POST = array(); $_REQUEST = array();
	$main = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
	$legacy = gigpress_upcoming(array('group_artists' => 'no'));
	$checks['main_shortcode'] = is_string($main) && strpos($main, 'Archive Hall') !== false && strpos($main, 'show_id=109') !== false;
	$checks['legacy_wrapper'] = is_string($legacy) && strpos($legacy, 'Archive Hall') !== false && strpos($legacy, 'show_id=109') !== false;
	$checks['calendar_ticket_labels'] = is_string($main)
		&& strpos($main, 'Add to Google Calendar') !== false
		&& strpos($main, 'Download iCalendar') !== false
		&& strpos($main, 'tickets.example.test/109') !== false;

	$widgetOutput = '';
	if (class_exists('Gigpress_widget')) {
		$widget = new Gigpress_widget();
		ob_start();
		$widget->widget(array('before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'), array('title' => 'Tracer', 'scope' => 'upcoming', 'limit' => 5, 'group_artists' => 'no', 'show_feeds' => 'no'));
		$widgetOutput = ob_get_clean();
	}
	$checks['widget'] = $widgetOutput !== '' && strpos($widgetOutput, 'Archive Hall') !== false
		&& strpos($widgetOutput, 'tickets.example.test/109') !== false;

	$post = $relatedPostId > 0 ? get_post($relatedPostId) : null;
	$is_excerpt = false;
	$related = gigpress_show_related(array('scope' => 'upcoming'));
	$checks['related_post'] = is_string($related) && strpos($related, 'Archive Hall') !== false && strpos($related, 'Reconstructed Band') !== false;

	$_GET = array();
	list(, $rss) = gigpress_public_tracer_capture('gigpress_feed');
	$_GET = array('show_id' => 109);
	list(, $ical) = gigpress_public_tracer_capture('gigpress_ical');
	$checks['rss_endpoint'] = strpos($rss, '<rss ') !== false && strpos($rss, '<guid isPermaLink="false">#show-109</guid>') !== false;
	$checks['ical_endpoint'] = strpos($ical, 'BEGIN:VCALENDAR') !== false && preg_match('/^UID:[^\r\n]*-109-/m', $ical) === 1;
	$_GET = array();

	$overrideRoot = WP_CONTENT_DIR . '/themes/gigpress-public-tracer';
	$overrideTemplates = $overrideRoot . '/gigpress-templates';
	$overrideSource = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/public-publishing/child/gigpress-templates/shows-list.php';
	$overrideCopied = false;
	if (is_readable($overrideSource) && !is_dir($overrideTemplates)) {
		$overrideCopied = @mkdir($overrideTemplates, 0775, true) && @copy($overrideSource, $overrideTemplates . '/shows-list.php');
	}
	if ($overrideCopied) {
		$overrideFilter = function ($directory) use ($overrideRoot) { return $overrideRoot; };
		add_filter('stylesheet_directory', $overrideFilter, 99, 1);
		$overrideOutput = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		remove_filter('stylesheet_directory', $overrideFilter, 99);
		$checks['child_override'] = strpos($overrideOutput, 'compat-child-override') !== false && strpos($overrideOutput, 'data-show-id="109"') !== false;
		@unlink($overrideTemplates . '/shows-list.php');
		@rmdir($overrideTemplates);
		@rmdir($overrideRoot);
	}

	$pathBase = 'http://127.0.0.1/';
	$httpUrls = array(
		$pathBase . '?feed=gigpress',
		$pathBase . '?feed=gigpress-ical&show_id=109',
		$pathBase . '?feed=gigpress',
		$pathBase . '?feed=gigpress-ical&show_id=109',
	);
	$http = gigpress_public_tracer_http_reads($httpUrls);
	$httpOk = count($http) === 4;
	foreach ($http as $index => $response) {
		$expectedType = ($index % 2 === 0) ? 'xml' : 'calendar';
		$httpOk = $httpOk && $response['status'] === 200 && $response['error'] === ''
			&& strpos(strtolower($response['content_type']), $expectedType) !== false;
		if ($index % 2 === 0) $httpOk = $httpOk && strpos($response['body'], '<rss ') !== false && strpos($response['body'], '#show-109') !== false;
		else $httpOk = $httpOk && preg_match('/^UID:\d{8}T\d{6}Z-109-/m', $response['body']) === 1;
	}
	$checks['anonymous_http_endpoints'] = $httpOk;
	$rssBodiesEqual = isset($http[0]['body'], $http[2]['body']) && $http[0]['body'] === $http[2]['body'];
	$icalOne = preg_replace('/^DTSTAMP:[^\r\n]+/m', 'DTSTAMP:volatile', $http[1]['body'] ?? '');
	$icalTwo = preg_replace('/^DTSTAMP:[^\r\n]+/m', 'DTSTAMP:volatile', $http[3]['body'] ?? '');
	$checks['sequential_repeat_stable'] = $checks['rss_endpoint'] && $checks['ical_endpoint'] && $rssBodiesEqual
		&& $icalOne !== '' && $icalOne === $icalTwo && strpos($rss, '#show-109') !== false;
	$checks['concurrent_reads_stable'] = $httpOk;
	$httpIcal = $http[1]['body'] ?? '';
	$stampTimestamp = false;
	if (preg_match('/^DTSTAMP:(\d{8}T\d{6}Z)\r?$/m', $httpIcal, $stamp) === 1) {
		$stampDate = substr($stamp[1], 0, 4) . '-' . substr($stamp[1], 4, 2) . '-' . substr($stamp[1], 6, 2);
		$stampClock = substr($stamp[1], 9, 2) . ':' . substr($stamp[1], 11, 2) . ':' . substr($stamp[1], 13, 2);
		if (checkdate((int) substr($stamp[1], 4, 2), (int) substr($stamp[1], 6, 2), (int) substr($stamp[1], 0, 4))) {
			$stampTimestamp = strtotime($stampDate . 'T' . $stampClock . 'Z');
		}
	}
	$checks['volatile_stamp_valid'] = $stampTimestamp !== false
		&& $stampTimestamp >= time() - 3600 && $stampTimestamp <= time() + 3600;
	$afterReads = gigpress_upgrade_preservation_snapshot();
	$checks['snapshot_unchanged'] = $beforeReads === $afterReads;
	return array('case' => $case, 'checks' => $checks, 'fixture' => $fixture['label'], 'source_show_ids' => array(109, 113),
		'published_show_ids' => array(109), 'readiness' => $readiness['status'] ?? 'blocked', 'manifest_checks' => $manifestChecks,
		'http_statuses' => array_column($http, 'status'), 'ical_stamp' => $stamp[1] ?? null,
		'linked_post_id' => $relatedPostId, 'snapshot_unchanged' => $beforeReads === $afterReads,
		'plugin_error_count' => count($pluginErrors), 'warning_count' => count($menuWarnings));
}

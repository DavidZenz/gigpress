<?php
/* Public preservation evidence over every recognized reconstructed source state. */

function gigpress_public_migrated_http_reads($urls) {
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
		curl_close($handle);
	}
	curl_multi_close($multi);
	return $results;
}

function gigpress_public_migrated_capture($callback) {
	ob_start();
	$value = call_user_func($callback);
	return array($value, ob_get_clean());
}

function gigpress_public_migrated_ids($pattern, $subject) {
	if (!preg_match_all($pattern, (string) $subject, $matches)) return array();
	return array_values(array_unique(array_map('intval', $matches[1])));
}

function gigpress_public_migrated_case($case) {
	global $wpdb, $gpo, $post, $is_excerpt;
	$migrationModule = WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
	if (is_readable($migrationModule)) require_once $migrationModule;
	$versions = array('1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6');
	$expectedUpcoming = array(
		'1.0' => array(101), '1.1' => array(113, 111), '1.2' => array(121),
		'1.3' => array(131), '1.4' => array(109), '1.5' => array(151), '1.6' => array(161),
	);
	$expectedVenues = array(
		'1.0' => array('First Hall'), '1.1' => array('Thirteen Hall', 'Eleven Hall'),
		'1.2' => array('Twelve Hall'), '1.3' => array('Shared Name'), '1.4' => array('Archive Hall'),
		'1.5' => array('Fifteen Hall'), '1.6' => array('Current Hall'),
	);
	$expectedRelatedVenues = array(
		'1.0' => array('First Hall'), '1.1' => array('Eleven Hall'),
		'1.2' => array('Twelve Hall'), '1.3' => array('Shared Name'), '1.4' => array('Archive Hall'),
		'1.5' => array('Fifteen Hall'), '1.6' => array('Current Hall'),
	);
	$expectedTicketIds = array('1.0' => 101, '1.1' => 111, '1.2' => 121, '1.3' => 131, '1.4' => 109, '1.5' => 151, '1.6' => 161);
	$expectedTimes = array(
		'1.0' => '00:00:01', '1.1' => '19:30:00', '1.2' => '20:00:00',
		'1.3' => '20:00:00', '1.4' => '20:30:00', '1.5' => '21:00:00', '1.6' => '20:00:00',
	);
	$checks = array(
		'case' => $case === 'migrated-contracts',
		'exact_source_set' => $versions === array_keys($expectedUpcoming),
		'all_sources_ready' => false,
		'all_source_manifests' => false,
		'all_public_destinations' => false,
		'all_related_posts' => false,
		'all_anonymous_feeds' => false,
		'all_repeat_reads_stable' => false,
		'all_concurrent_reads_stable' => false,
		'all_read_snapshots_unchanged' => false,
		'optional_time_and_midnight_meaning' => false,
	);
	if ($case !== 'migrated-contracts' || !function_exists('gigpress_upgrade_preservation_snapshot')
		|| !function_exists('gigpress_upgrade_preservation_manifest_matches')) {
		return array('case' => $case, 'checks' => $checks, 'reason' => 'migration evidence helpers are unavailable');
	}

	$allReady = $allManifests = $allDestinations = $allRelated = $allFeeds = true;
	$allRepeats = $allConcurrent = $allSnapshots = $timeMeaning = true;
	$sourceEvidence = array();
	foreach ($versions as $version) {
		$fixture = gigpress_upgrade_preservation_fixture($version);
		$seeded = is_array($fixture) && upgrade_preservation_seed($fixture);
		$source = $seeded ? gigpress_upgrade_preservation_snapshot() : null;
		unset($GLOBALS['gigpress_db_bootstrap_result']);
		$ready = function_exists('gigpress_db_bootstrap') ? gigpress_db_bootstrap() : array('status' => 'blocked');
		$afterMigration = gigpress_upgrade_preservation_snapshot();
		list($manifestChecks, $manifestMatches) = gigpress_upgrade_preservation_manifest_matches($fixture, $afterMigration, $source);
		$gpo = get_option('gigpress_settings');
		$activeIds = $expectedUpcoming[$version];
		$showId = $expectedTicketIds[$version];
		$ticket = 'tickets.example.test/' . $showId;
		$names = array();
		foreach ($afterMigration['shows'] as $row) {
			if ((int) $row['show_id'] === $showId) $names[] = $row;
		}
		$showExists = count($names) === 1 && ($names[0]['show_status'] ?? '') !== 'deleted';

		wp_set_current_user(0);
		$_GET = array(); $_POST = array(); $_REQUEST = array();
		$main = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
		$legacy = gigpress_upcoming(array('group_artists' => 'no'));
		$mainOk = $showExists && is_string($main) && strpos($main, $ticket) !== false;
		$legacyOk = $showExists && is_string($legacy) && strpos($legacy, $ticket) !== false;
		foreach ($expectedVenues[$version] as $venueName) {
			$mainOk = $mainOk && strpos($main, $venueName) !== false;
			$legacyOk = $legacyOk && strpos($legacy, $venueName) !== false;
		}

		$widgetOutput = '';
		if (class_exists('Gigpress_widget')) {
			$widget = new Gigpress_widget();
			ob_start();
			$widget->widget(array('before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'), array('title' => 'Migrated', 'scope' => 'upcoming', 'limit' => 20, 'group_artists' => 'no', 'show_feeds' => 'no'));
			$widgetOutput = ob_get_clean();
		}
		$widgetOk = $showExists && strpos($widgetOutput, $ticket) !== false;
		foreach ($expectedVenues[$version] as $venueName) $widgetOk = $widgetOk && strpos($widgetOutput, $venueName) !== false;

		$linkedPost = $afterMigration['linked_posts'][$showId] ?? null;
		$post = is_array($linkedPost) ? get_post((int) $linkedPost['ID']) : null;
		$is_excerpt = false;
		$related = gigpress_show_related(array('scope' => 'upcoming'));
		$relatedOk = is_string($related) && $showExists && strpos($related, $ticket) !== false;
		foreach ($expectedRelatedVenues[$version] as $venueName) $relatedOk = $relatedOk && strpos($related, $venueName) !== false;

		$_GET = array();
		list(, $rss) = gigpress_public_migrated_capture('gigpress_feed');
		$_GET = array('show_id' => $showId);
		list(, $ical) = gigpress_public_migrated_capture('gigpress_ical');
		$expectedGuid = '<guid isPermaLink="false">#show-' . $showId . '</guid>';
		$rssIds = gigpress_public_migrated_ids('/<guid isPermaLink="false">#show-(\d+)<\/guid>/', $rss);
		$icalIds = gigpress_public_migrated_ids('/^UID:[^\r\n]*-(\d+)-/m', $ical);
		$rssOk = strpos($rss, '<rss ') !== false && strpos($rss, $expectedGuid) !== false && $rssIds === $activeIds;
		$icalOk = strpos($ical, 'BEGIN:VCALENDAR') !== false && $icalIds === array($showId);

		$pathBase = 'http://127.0.0.1/';
		$http = gigpress_public_migrated_http_reads(array(
			$pathBase . '?feed=gigpress',
			$pathBase . '?feed=gigpress-ical&show_id=' . $showId,
			$pathBase . '?feed=gigpress',
			$pathBase . '?feed=gigpress-ical&show_id=' . $showId,
		));
		$httpOk = count($http) === 4;
		foreach ($http as $index => $response) {
			$expectedType = ($index % 2 === 0) ? 'xml' : 'calendar';
			$httpOk = $httpOk && $response['status'] === 200 && $response['error'] === ''
				&& strpos(strtolower($response['content_type']), $expectedType) !== false;
			if ($index % 2 === 0) $httpOk = $httpOk && strpos($response['body'], $expectedGuid) !== false
				&& gigpress_public_migrated_ids('/<guid isPermaLink="false">#show-(\d+)<\/guid>/', $response['body']) === $activeIds;
			else $httpOk = $httpOk && preg_match('/^UID:\d{8}(?:T\d{6}Z)?-' . preg_quote((string) $showId, '/') . '-/m', $response['body']) === 1
				&& gigpress_public_migrated_ids('/^UID:[^\r\n]*-(\d+)-/m', $response['body']) === array($showId);
		}
		$icalFirst = preg_replace('/^DTSTAMP:[^\r\n]+/m', 'DTSTAMP:volatile', $http[1]['body'] ?? '');
		$icalSecond = preg_replace('/^DTSTAMP:[^\r\n]+/m', 'DTSTAMP:volatile', $http[3]['body'] ?? '');
		$repeatStable = isset($http[0]['body'], $http[2]['body']) && $http[0]['body'] === $http[2]['body']
			&& $icalFirst !== '' && $icalFirst === $icalSecond;
		$beforeReads = $afterMigration;
		$afterReads = gigpress_upgrade_preservation_snapshot();
		$snapshotUnchanged = $beforeReads === $afterReads;

		$readinessOk = $seeded && ($ready['status'] ?? '') === 'ready'
			&& ($afterMigration['settings']['db_version'] ?? null) === '1.6';
		$destinationOk = $mainOk && $legacyOk && $widgetOk;
		$feedsOk = $rssOk && $icalOk && $httpOk;
		$allReady = $allReady && $readinessOk;
		$allManifests = $allManifests && $manifestMatches && !in_array(false, $manifestChecks, true);
		$allDestinations = $allDestinations && $destinationOk;
		$allRelated = $allRelated && $relatedOk;
		$allFeeds = $allFeeds && $feedsOk;
		$allRepeats = $allRepeats && $repeatStable;
		$allConcurrent = $allConcurrent && $httpOk;
		$allSnapshots = $allSnapshots && $snapshotUnchanged;
		$timeMeaning = $timeMeaning && $showExists && ($names[0]['show_time'] ?? null) === $expectedTimes[$version];
		$sourceEvidence[$version] = array(
			'fixture' => $fixture['label'] ?? null,
			'source_ids' => $fixture['expected']['show_ids'] ?? array(),
			'public_upcoming_ids' => $activeIds,
			'checks' => array(
				'seeded' => (bool) $seeded, 'ready' => ($ready['status'] ?? '') === 'ready',
				'manifest' => $manifestChecks, 'main_shortcode' => $mainOk,
				'legacy_wrapper' => $legacyOk, 'widget' => $widgetOk, 'related_post' => $relatedOk,
				'rss' => $rssOk, 'ical' => $icalOk, 'anonymous_http' => $httpOk,
				'repeat_stable' => $repeatStable, 'snapshot_unchanged' => $snapshotUnchanged,
			),
			'linked_post_id' => $linkedPost['ID'] ?? null,
			'snapshot_unchanged' => $snapshotUnchanged,
			'http_statuses' => array_column($http, 'status'),
			'http_content_types' => array_column($http, 'content_type'),
			'http_errors' => array_column($http, 'error'),
			'expected_feed_ids' => $activeIds,
			'rss_ids' => $rssIds,
			'ical_ids' => $icalIds,
			'http_feed_ids' => array_map(function ($response, $index) {
				$pattern = ($index % 2 === 0)
					? '/<guid isPermaLink="false">#show-(\d+)<\/guid>/'
					: '/^UID:[^\r\n]*-(\d+)-/m';
				return gigpress_public_migrated_ids($pattern, $response['body'] ?? '');
			}, $http, array_keys($http)),
		);
		$_GET = array(); $_POST = array(); $_REQUEST = array();
	}
	$post = null;
	$checks['all_sources_ready'] = $allReady;
	$checks['all_source_manifests'] = $allManifests;
	$checks['all_public_destinations'] = $allDestinations;
	$checks['all_related_posts'] = $allRelated;
	$checks['all_anonymous_feeds'] = $allFeeds;
	$checks['all_repeat_reads_stable'] = $allRepeats;
	$checks['all_concurrent_reads_stable'] = $allConcurrent;
	$checks['all_read_snapshots_unchanged'] = $allSnapshots;
	$checks['optional_time_and_midnight_meaning'] = $timeMeaning;
	return array('case' => $case, 'checks' => $checks, 'sources' => $sourceEvidence,
		'expected_public_upcoming_ids' => $expectedUpcoming, 'source_count' => count($sourceEvidence));
}

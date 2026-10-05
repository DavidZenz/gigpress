<?php
/* Independent anonymous feed parsers and hostile-value contracts. */

function gigpress_public_feeds_http($urls) {
	if (!function_exists('curl_multi_init')) return array();
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

function gigpress_public_feeds_seed() {
	static $seed = null;
	if ($seed !== null) return $seed;
	global $wpdb;
	$artist = array('artist_id' => 1891, 'artist_name' => "A&B <Orchestra> \"quoted\" 東京\r\nSecond line", 'artist_alpha' => 'hostile feed orchestra', 'artist_order' => 1, 'artist_url' => 'https://artists.example.test/feed');
	$venue = array('venue_id' => 1891, 'venue_name' => 'Hall & <Room> "Vienna"', 'venue_address' => '1 & <2> "Way"', 'venue_city' => "Wien & <City>\nSecond line", 'venue_state' => 'AT', 'venue_postal_code' => '1010', 'venue_country' => 'AT', 'venue_url' => 'https://venues.example.test/feed', 'venue_phone' => '+43 & 555');
	$tour = array('tour_id' => 1891, 'tour_name' => 'Notes & <More> "Tour"', 'tour_status' => 'active');
	$notes = "<p>Doors <strong>open</strong> &amp; music</p>\r\nCDATA closer: ]]>\nBEGIN:VEVENT\nX-Injected: no\nEnd.";
	$shows = array(
		array('show_id' => 1891, 'show_artist_id' => 1891, 'show_venue_id' => 1891, 'show_tour_id' => 1891, 'show_date' => '2031-06-10', 'show_multi' => 0, 'show_time' => '19:30:00', 'show_expire' => '2031-06-10', 'show_price' => '24 & <free>', 'show_tix_url' => 'https://tickets.example.test/1891?a=1&b=2', 'show_tix_phone' => '+43 "box"', 'show_ages' => 'All ages & <12', 'show_notes' => $notes, 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => 'https://events.example.test/1891?a=1&b=2', 'show_tour_restore' => 0, 'show_address' => '1 & <2> "Way"', 'show_locale' => 'Wien & <City>', 'show_country' => 'AT', 'show_venue' => 'Hall & <Room>', 'show_venue_url' => 'https://venues.example.test/feed', 'show_venue_phone' => '+43 & 555'),
		array('show_id' => 1892, 'show_artist_id' => 1891, 'show_venue_id' => 1891, 'show_tour_id' => 1891, 'show_date' => '2031-06-11', 'show_multi' => 0, 'show_time' => '20:00:00', 'show_expire' => '2031-06-11', 'show_price' => '31', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => '', 'show_notes' => 'Second item & <ordered>', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '1 & <2> "Way"', 'show_locale' => 'Wien & <City>', 'show_country' => 'AT', 'show_venue' => 'Hall & <Room>', 'show_venue_url' => 'https://venues.example.test/feed', 'show_venue_phone' => '+43 & 555'),
	);
	if ($wpdb->insert(GIGPRESS_ARTISTS, $artist) === false || $wpdb->insert(GIGPRESS_VENUES, $venue) === false || $wpdb->insert(GIGPRESS_TOURS, $tour) === false) return false;
	foreach ($shows as $show) if ($wpdb->insert(GIGPRESS_SHOWS, $show) === false) return false;
	$seed = array('artist' => $artist, 'venue' => $venue, 'tour' => $tour, 'shows' => $shows, 'notes' => $notes);
	return $seed;
}

function gigpress_public_feeds_snapshot() {
	if (!function_exists('gigpress_upgrade_preservation_snapshot')) {
		$module = WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-migrations.php';
		if (is_readable($module)) require_once $module;
	}
	return function_exists('gigpress_upgrade_preservation_snapshot') ? gigpress_upgrade_preservation_snapshot() : null;
}

function gigpress_public_feeds_parse_xml($xml) {
	$document = new DOMDocument('1.0', 'UTF-8');
	$previous = libxml_use_internal_errors(true);
	$loaded = $document->loadXML((string) $xml, LIBXML_NONET | LIBXML_NOBLANKS);
	$errors = libxml_get_errors();
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	return array($loaded ? $document : null, $errors);
}

function gigpress_public_feeds_ids($document) {
	$ids = array();
	if (!$document) return $ids;
	foreach ($document->getElementsByTagName('guid') as $guid) {
		if (preg_match('/^#show-(\d+)$/', $guid->textContent, $matches)) $ids[] = (int) $matches[1];
	}
	return $ids;
}

function gigpress_public_feeds_case($case) {
	global $wpdb, $gpo;
	$valid = array('rss-contract', 'ical-contract', 'empty-contracts');
	if (!in_array($case, $valid, true)) return array('case' => $case, 'checks' => array('known_feed_case' => false));
	$seed = gigpress_public_feeds_seed();
	if (!$seed) return array('case' => $case, 'checks' => array('feed_fixture_seeded' => false), 'reason' => 'hostile feed fixture could not be seeded');
	$oldSettings = $gpo;
	$storedSettings = get_option('gigpress_settings');
	$storedRssTitle = is_array($storedSettings) ? (string) ($storedSettings['rss_title'] ?? '') : '';
	$gpo['rss_title'] = 'Festival & "Friends" <Shows>';
	$gpo['rss_limit'] = 100;
	$gpo['artist_label'] = 'Acts & <Artists>';
	$gpo['tour_label'] = 'Tour & <Route>';
	$gpo['external_link_label'] = 'More & <Events>';
	$gpo['related'] = 'Related & <Post>';
	$before = gigpress_public_feeds_snapshot();
	$base = 'http://127.0.0.1/?feed=gigpress';
	$filterUrls = array(
		$base . '&artist=1891',
		$base . '&tour=1891',
		$base . '&venue=1891',
		$base . '&artist=1891&tour=1891&venue=1891',
	);
	$checks = array('feed_fixture_seeded' => true, 'anonymous_http_client_available' => function_exists('curl_multi_init'));
	$evidence = array();

	if ($case === 'rss-contract') {
		$reads = gigpress_public_feeds_http($filterUrls);
		$exactIds = array();
		$allParsed = count($reads) === count($filterUrls);
		$headersOk = $allParsed;
		foreach ($reads as $index => $response) {
			list($document, $xmlErrors) = gigpress_public_feeds_parse_xml($response['body']);
			$allParsed = $allParsed && $document instanceof DOMDocument && count($xmlErrors) === 0;
			$headersOk = $headersOk && $response['status'] === 200 && strpos(strtolower($response['content_type']), 'xml') !== false && $response['error'] === '';
			$exactIds[] = gigpress_public_feeds_ids($document);
		}
		$expectedIds = array(1891, 1892);
		$firstDocument = isset($reads[0]) ? gigpress_public_feeds_parse_xml($reads[0]['body'])[0] : null;
		$items = $firstDocument ? $firstDocument->getElementsByTagName('item') : array();
		$firstItem = $items && $items->length ? $items->item(0) : null;
		$firstDescription = $firstItem instanceof DOMElement ? $firstItem->getElementsByTagName('description')->item(0)->textContent : '';
		$descriptionDom = new DOMDocument('1.0', 'UTF-8');
		$descriptionPrev = libxml_use_internal_errors(true);
		$descriptionParsed = $descriptionDom->loadHTML('<?xml encoding="UTF-8">' . $firstDescription, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
		libxml_clear_errors();
		libxml_use_internal_errors($descriptionPrev);
		$channelTitle = $firstDocument ? $firstDocument->getElementsByTagName('title')->item(0)->textContent : '';
		$channelDescription = $firstDocument ? $firstDocument->getElementsByTagName('description')->item(0)->textContent : '';
		$atomLinks = $firstDocument ? $firstDocument->getElementsByTagNameNS('http://www.w3.org/2005/Atom', 'link') : array();
		$selfHref = $atomLinks && $atomLinks->length ? $atomLinks->item(0)->getAttribute('href') : '';
		$firstTitle = $firstItem instanceof DOMElement ? $firstItem->getElementsByTagName('title')->item(0)->textContent : '';
		$firstLink = $firstItem instanceof DOMElement ? $firstItem->getElementsByTagName('link')->item(0)->textContent : '';
		$guid = $firstItem instanceof DOMElement ? $firstItem->getElementsByTagName('guid')->item(0)->textContent : '';
		$repeat = gigpress_public_feeds_http(array_fill(0, 4, $filterUrls[3]));
		$repeatBodies = array_column($repeat, 'body');
		$limit = $gpo['rss_limit'];
		$gpo['rss_limit'] = 1;
		$_GET = array('artist' => '1891', 'tour' => '1891', 'venue' => '1891');
		ob_start(); gigpress_feed(); $limitedBody = ob_get_clean();
		$_GET = array();
		$gpo['rss_limit'] = $limit;
		$limitedDocument = gigpress_public_feeds_parse_xml($limitedBody)[0];
		$limitedIds = gigpress_public_feeds_ids($limitedDocument);
		$expectedItemTitle = $seed['artist']['artist_name'] . ' ' . __('in', 'gigpress') . ' ' . $seed['venue']['venue_city'] . ' ' . __('on', 'gigpress') . ' ' . mysql2date($gpo['date_format'], $seed['shows'][0]['show_date']);
		$checks['rss_all_artist_tour_venue_filters_keep_exact_order'] = $exactIds === array($expectedIds, $expectedIds, $expectedIds, $expectedIds);
		$checks['rss_real_anonymous_responses_have_valid_headers'] = $headersOk;
		$checks['rss_independent_xml_parse_has_one_channel_and_exact_count'] = $allParsed && $firstDocument && $firstDocument->getElementsByTagName('channel')->length === 1 && $items->length === 2;
		$checks['rss_channel_text_and_self_url_are_xml_safe_and_exact'] = $channelTitle === $storedRssTitle . ': ' . $seed['artist']['artist_name'] && $channelDescription === $channelTitle && $selfHref === GIGPRESS_RSS . '&artist=1891';
		$checks['rss_title_link_guid_and_hostile_unicode_values_decode_exactly'] = $firstTitle === $expectedItemTitle && $firstLink === get_bloginfo('url') && $guid === '#show-1891';
		$descriptionText = $descriptionParsed ? $descriptionDom->textContent : '';
		$richTextPreserved = false;
		if ($descriptionParsed) foreach ($descriptionDom->getElementsByTagName('strong') as $strong) if ($strong->textContent === 'open') $richTextPreserved = true;
		$descriptionLinks = $descriptionDom->getElementsByTagName('a');
		$ticketLink = $descriptionLinks->length ? $descriptionLinks->item(0)->getAttribute('href') : '';
		$externalLink = $descriptionLinks->length > 1 ? $descriptionLinks->item(1)->getAttribute('href') : '';
		$calendarLink = $descriptionLinks->length > 2 ? $descriptionLinks->item(2)->getAttribute('href') : '';
		$icalLink = $descriptionLinks->length > 3 ? $descriptionLinks->item(3)->getAttribute('href') : '';
		$checks['rss_rich_notes_are_safe_and_cdata_terminator_round_trips'] = $descriptionParsed && strpos($descriptionText, ']]>') !== false && $richTextPreserved && !$descriptionDom->getElementsByTagName('script')->length && strpos($descriptionText, 'X-Injected: no') !== false;
		$checks['rss_description_links_keep_saved_destinations_and_calendar_identity'] = $ticketLink === $seed['shows'][0]['show_tix_url'] && $externalLink === $seed['shows'][0]['show_external_url'] && strpos($calendarLink, 'http://www.google.com/calendar/event?action=TEMPLATE') === 0 && strpos($icalLink, GIGPRESS_ICAL . '&show_id=1891') === 0;
		$checks['rss_xml_text_has_no_injected_markup_or_unexpected_entities'] = $firstDocument && $firstDocument->getElementsByTagName('free')->length === 0 && $firstDocument->getElementsByTagName('script')->length === 0 && strpos($firstDocument->saveXML(), '&amp;amp;') === false;
		$checks['rss_configured_limit_is_preserved'] = $limitedDocument && $limitedIds === array(1891);
		$checks['rss_repeated_and_concurrent_reads_are_complete'] = count($repeat) === 4 && count(array_unique($repeatBodies)) === 1 && !array_filter($repeat, function ($response) { return $response['status'] !== 200 || $response['error'] !== ''; }) && gigpress_public_feeds_ids(gigpress_public_feeds_parse_xml($repeatBodies[0] ?? '')[0]) === $expectedIds;
		$after = gigpress_public_feeds_snapshot();
		$checks['rss_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged'] = $before === $after;
		$evidence = array('expected_ids' => $expectedIds, 'filter_ids' => $exactIds, 'channel_title' => $channelTitle, 'expected_channel_title' => $storedRssTitle . ': ' . $seed['artist']['artist_name'], 'item_title' => $firstTitle, 'description' => $firstDescription, 'description_strong_count' => $descriptionDom->getElementsByTagName('strong')->length, 'description_has_injection_marker' => strpos($firstDescription, 'X-Injected: no') !== false, 'concurrent_responses' => count($repeat), 'limited_ids' => $limitedIds, 'snapshot_unchanged' => $before === $after);
	}

	if ($case === 'ical-contract') {
		$reads = gigpress_public_feeds_http(array($base . '&show_id=1891', $base . '&show_id=1892'));
		$checks['ical_endpoint_returns_anonymous_responses'] = count($reads) === 2 && !array_filter($reads, function ($response) { return $response['status'] !== 200 || $response['error'] !== '' || strpos(strtolower($response['content_type']), 'calendar') === false; });
		$checks['ical_parser_contract_is_pending_implementation'] = false;
		$evidence = array('responses' => array_map(function ($response) { return array('status' => $response['status'], 'content_type' => $response['content_type'], 'body' => $response['body']); }, $reads));
	}

	if ($case === 'empty-contracts') {
		$reads = gigpress_public_feeds_http(array($base . '&artist=99999999'));
		$rss = isset($reads[0]) ? $reads[0] : array('body' => '', 'status' => 0, 'content_type' => '', 'error' => 'missing response');
		list($document, $xmlErrors) = gigpress_public_feeds_parse_xml($rss['body']);
		$checks['empty_rss_anonymous_endpoint_has_valid_xml_header'] = $rss['status'] === 200 && strpos(strtolower($rss['content_type']), 'xml') !== false && $rss['error'] === '' && $document instanceof DOMDocument && !$xmlErrors;
		$checks['empty_rss_retains_channel_and_has_zero_items'] = $document && $document->getElementsByTagName('channel')->length === 1 && $document->getElementsByTagName('item')->length === 0;
		$evidence = array('rss_status' => $rss['status'], 'rss_item_count' => $document ? $document->getElementsByTagName('item')->length : null);
	}

	$gpo = $oldSettings;
	return array('case' => $case, 'checks' => $checks, 'evidence' => $evidence);
}

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
	}
	curl_multi_close($multi);
	return $results;
}

function gigpress_public_feeds_seed() {
	static $seed = null;
	if ($seed !== null) return $seed;
	global $wpdb;
	$artist = array('artist_id' => 1891, 'artist_name' => "A&B <Orchestra> \"quoted\" 東京" . str_repeat('🎻', 40) . "\r\nSecond line", 'artist_alpha' => 'hostile feed orchestra', 'artist_order' => 1, 'artist_url' => 'https://artists.example.test/feed');
	$venue = array('venue_id' => 1891, 'venue_name' => 'Hall & <Room> "Vienna"', 'venue_address' => '1 & <2> "Way"', 'venue_city' => "Wien & <City>\nSecond line", 'venue_state' => 'AT', 'venue_postal_code' => '1010', 'venue_country' => 'AT', 'venue_url' => 'https://venues.example.test/feed', 'venue_phone' => '+43 & 555');
	$tour = array('tour_id' => 1891, 'tour_name' => 'Notes & <More> "Tour"', 'tour_status' => 'active');
	$notes = "<p>Doors <strong>open</strong> &amp; music</p>\r\nCDATA closer: ]]>\nBEGIN:VEVENT\nX-Injected: no\nBackslash \\route, commas ; semicolons.\nEnd.";
	$shows = array(
		array('show_id' => 1891, 'show_artist_id' => 1891, 'show_venue_id' => 1891, 'show_tour_id' => 1891, 'show_date' => '2031-06-10', 'show_multi' => 0, 'show_time' => '19:30:00', 'show_expire' => '2031-06-10', 'show_price' => '24 & <free>', 'show_tix_url' => 'https://tickets.example.test/1891?a=1&b=2', 'show_tix_phone' => '+43 "box"', 'show_ages' => 'All ages & <12', 'show_notes' => $notes, 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => 'https://events.example.test/1891?a=1&b=2', 'show_tour_restore' => 0, 'show_address' => '1 & <2> "Way"', 'show_locale' => 'Wien & <City>', 'show_country' => 'AT', 'show_venue' => 'Hall & <Room>', 'show_venue_url' => 'https://venues.example.test/feed', 'show_venue_phone' => '+43 & 555'),
		array('show_id' => 1892, 'show_artist_id' => 1891, 'show_venue_id' => 1891, 'show_tour_id' => 1891, 'show_date' => '2031-06-11', 'show_multi' => 1, 'show_time' => '00:00:01', 'show_expire' => '2031-06-13', 'show_price' => '31', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => '', 'show_notes' => 'Second item & <ordered>', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '1 & <2> "Way"', 'show_locale' => 'Wien & <City>', 'show_country' => 'AT', 'show_venue' => 'Hall & <Room>', 'show_venue_url' => 'https://venues.example.test/feed', 'show_venue_phone' => '+43 & 555'),
		array('show_id' => 1893, 'show_artist_id' => 1891, 'show_venue_id' => 1891, 'show_tour_id' => 1891, 'show_date' => '2031-06-14', 'show_multi' => 0, 'show_time' => '00:00:00', 'show_expire' => '2031-06-14', 'show_price' => '32', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => '', 'show_notes' => 'Actual midnight & <kept>', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '1 & <2> "Way"', 'show_locale' => 'Wien & <City>', 'show_country' => 'AT', 'show_venue' => 'Hall & <Room>', 'show_venue_url' => 'https://venues.example.test/feed', 'show_venue_phone' => '+43 & 555'),
		array('show_id' => 1894, 'show_artist_id' => 1891, 'show_venue_id' => 1891, 'show_tour_id' => 1891, 'show_date' => '2031-06-15', 'show_multi' => 1, 'show_time' => '20:00:00', 'show_expire' => '2031-06-17', 'show_price' => '33', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => '', 'show_notes' => 'Timed multi-day & <kept>', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '1 & <2> "Way"', 'show_locale' => 'Wien & <City>', 'show_country' => 'AT', 'show_venue' => 'Hall & <Room>', 'show_venue_url' => 'https://venues.example.test/feed', 'show_venue_phone' => '+43 & 555'),
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

function gigpress_public_feeds_unescape_text($value) {
	return preg_replace_callback('/\\\\([\\\\nN;,])/', function ($match) {
		switch ($match[1]) {
			case 'n': case 'N': return "\n";
			case ',': return ',';
			case ';': return ';';
			case '\\': return '\\';
		}
		return $match[0];
	}, (string) $value);
}

function gigpress_public_feeds_parse_ical($body) {
	$body = (string) $body;
	$physicalLines = explode("\r\n", $body);
	$crlfOnly = substr($body, -2) === "\r\n" && !preg_match('/(?<!\r)\n|\r(?!\n)/', $body);
	$folded = false;
	$validUtf8Lines = true;
	$withinLimits = true;
	foreach ($physicalLines as $line) {
		if ($line === '') continue;
		if (strlen($line) > 75) $withinLimits = false;
		if (preg_match('//u', $line) !== 1) $validUtf8Lines = false;
		if (strpos($line, ' ') === 0 || strpos($line, "\t") === 0) $folded = true;
	}
	$logical = preg_replace("/\r\n[ \t]/", '', $body);
	$lines = explode("\r\n", $logical);
	$events = array();
	$current = null;
	$propertiesOutside = array();
	foreach ($lines as $line) {
		if ($line === '' || $line === 'BEGIN:VCALENDAR' || $line === 'END:VCALENDAR') continue;
		if ($line === 'BEGIN:VEVENT') { $current = array(); continue; }
		if ($line === 'END:VEVENT') { if (is_array($current)) $events[] = $current; $current = null; continue; }
		$split = strpos($line, ':');
		if ($split === false) continue;
		$name = substr($line, 0, $split);
		$value = substr($line, $split + 1);
		$property = array('raw_name' => $name, 'value' => $value);
		if (preg_match('/;VALUE=([^;]+)/', $name, $matches)) $property['value_type'] = strtoupper($matches[1]);
		if (strpos($name, 'DTSTART') === 0 || strpos($name, 'DTEND') === 0 || strpos($name, 'DTSTAMP') === 0) {
			$property['value_type'] = strpos($name, 'VALUE=DATE') !== false && strpos($name, 'VALUE=DATE-TIME') === false ? 'DATE' : 'DATE-TIME';
		}
		if (in_array(substr($name, 0, strpos($name, ';') === false ? strlen($name) : strpos($name, ';')), array('SUMMARY', 'DESCRIPTION', 'LOCATION', 'UID', 'X-WR-CALNAME', 'PRODID', 'METHOD', 'CALSCALE', 'X-WR-TIMEZONE'), true)) {
			$property['decoded'] = gigpress_public_feeds_unescape_text($value);
		}
		if (is_array($current)) $current[$name] = $property;
		else $propertiesOutside[$name][] = $property;
	}
	return array(
		'valid_envelope' => strpos($body, "BEGIN:VCALENDAR\r\n") === 0 && substr($body, -strlen("END:VCALENDAR\r\n")) === "END:VCALENDAR\r\n",
		'crlf_only' => $crlfOnly,
		'within_limits' => $withinLimits,
		'valid_utf8_lines' => $validUtf8Lines,
		'has_fold' => $folded,
		'events' => $events,
		'properties' => $propertiesOutside,
	);
}

function gigpress_public_feeds_ical_ids($parsed) {
	$ids = array();
	foreach ($parsed['events'] ?? array() as $event) {
		$uid = $event['UID']['decoded'] ?? '';
		if (preg_match('/-(\d+)-[^-]+@?/', $uid, $matches)) $ids[] = (int) $matches[1];
	}
	return $ids;
}

function gigpress_public_feeds_expected_utc($date, $time) {
	$gmt = get_gmt_from_date((string) $date . ' ' . (string) $time);
	$stamp = DateTime::createFromFormat('!Y-m-d H:i:s', $gmt, new DateTimeZone('UTC'));
	return $stamp ? $stamp->format('Ymd\THis\Z') : '';
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
			$headersOk = $headersOk && $response['status'] === 200 && strpos(strtolower($response['content_type']), 'text/xml') === 0 && $response['error'] === '';
			$exactIds[] = gigpress_public_feeds_ids($document);
		}
		$expectedIds = array(1891, 1892, 1893, 1894);
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
		$checks['rss_independent_xml_parse_has_one_channel_and_exact_count'] = $allParsed && $firstDocument && $firstDocument->getElementsByTagName('channel')->length === 1 && $items->length === count($expectedIds);
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
		$calendarQuery = array();
		if ($calendarLink !== '') parse_str((string) wp_parse_url($calendarLink, PHP_URL_QUERY), $calendarQuery);
		$checks['rss_rich_notes_are_safe_and_cdata_terminator_round_trips'] = $descriptionParsed && strpos($descriptionText, ']]>') !== false && $richTextPreserved && !$descriptionDom->getElementsByTagName('script')->length && strpos($descriptionText, 'X-Injected: no') !== false;
		$checks['rss_description_links_keep_saved_destinations_and_calendar_identity'] = $ticketLink === $seed['shows'][0]['show_tix_url'] && $externalLink === $seed['shows'][0]['show_external_url'] && strpos($calendarLink, 'http://www.google.com/calendar/event?action=TEMPLATE') === 0 && strpos($icalLink, GIGPRESS_ICAL . '&show_id=1891') === 0;
		$checks['rss_google_calendar_values_are_plain_text_with_readable_notes'] = isset($calendarQuery['text'], $calendarQuery['location'], $calendarQuery['details'])
			&& strpos($calendarQuery['text'], '<Orchestra>') === false && strpos($calendarQuery['location'], '<Room>') === false
			&& strpos($calendarQuery['details'], '<p>') === false && strpos($calendarQuery['details'], '<strong>') === false
			&& strpos($calendarQuery['details'], 'Notes: Doors open & music CDATA closer:') !== false;
		$checks['rss_xml_text_has_no_injected_markup_or_unexpected_entities'] = $firstDocument && $firstDocument->getElementsByTagName('free')->length === 0 && $firstDocument->getElementsByTagName('script')->length === 0 && strpos($firstDocument->saveXML(), '&amp;amp;') === false;
		$checks['rss_configured_limit_is_preserved'] = $limitedDocument && $limitedIds === array(1891);
		$checks['rss_repeated_and_concurrent_reads_are_complete'] = count($repeat) === 4 && count(array_unique($repeatBodies)) === 1 && !array_filter($repeat, function ($response) { return $response['status'] !== 200 || $response['error'] !== ''; }) && gigpress_public_feeds_ids(gigpress_public_feeds_parse_xml($repeatBodies[0] ?? '')[0]) === $expectedIds;
		$after = gigpress_public_feeds_snapshot();
		$checks['rss_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged'] = $before === $after;
		$evidence = array('expected_ids' => $expectedIds, 'filter_ids' => $exactIds, 'channel_title' => $channelTitle, 'expected_channel_title' => $storedRssTitle . ': ' . $seed['artist']['artist_name'], 'item_title' => $firstTitle, 'description' => $firstDescription, 'description_strong_count' => $descriptionDom->getElementsByTagName('strong')->length, 'description_has_injection_marker' => strpos($firstDescription, 'X-Injected: no') !== false, 'concurrent_responses' => count($repeat), 'limited_ids' => $limitedIds, 'snapshot_unchanged' => $before === $after);
	}

	if ($case === 'ical-contract') {
		$icalBase = 'http://127.0.0.1/?feed=gigpress-ical';
		$filterReads = gigpress_public_feeds_http(array($icalBase . '&artist=1891', $icalBase . '&tour=1891', $icalBase . '&venue=1891'));
		$repeatReads = gigpress_public_feeds_http(array_fill(0, 4, $icalBase . '&artist=1891'));
		$selectedReads = array();
		foreach (array(1891, 1892, 1893, 1894) as $id) $selectedReads[$id] = gigpress_public_feeds_http(array($icalBase . '&show_id=' . $id))[0] ?? array('body' => '', 'status' => 0, 'content_type' => '', 'error' => 'missing response');
		$filterParsed = array_map(function ($response) { return gigpress_public_feeds_parse_ical($response['body']); }, $filterReads);
		$selectedParsed = array();
		foreach ($selectedReads as $id => $response) $selectedParsed[$id] = gigpress_public_feeds_parse_ical($response['body']);
		$expectedIds = array(1891, 1892, 1893, 1894);
		$allHttp = count($filterReads) === 3 && count($repeatReads) === 4 && count($selectedReads) === 4;
		foreach (array_merge($filterReads, $repeatReads, array_values($selectedReads)) as $response) {
		$allHttp = $allHttp && $response['status'] === 200 && strpos(strtolower($response['content_type']), 'text/calendar') === 0 && $response['error'] === '';
		}
		$filterIds = array_map('gigpress_public_feeds_ical_ids', $filterParsed);
		$repeatBodies = array_map(function ($response) { return preg_replace('/^DTSTAMP:[^\r\n]+/m', 'DTSTAMP:normalized', $response['body']); }, $repeatReads);
		$repeatComplete = count(array_unique($repeatBodies)) === 1;
		foreach ($repeatReads as $response) {
			$repeated = gigpress_public_feeds_parse_ical($response['body']);
			$repeatComplete = $repeatComplete && $repeated['valid_envelope'] && count($repeated['events']) === 4 && gigpress_public_feeds_ical_ids($repeated) === array(1891, 1892, 1893, 1894);
		}
		$formatChecks = true;
		foreach (array_merge($filterReads, $repeatReads, array_values($selectedReads)) as $response) {
			$parsed = gigpress_public_feeds_parse_ical($response['body']);
			$formatChecks = $formatChecks && $parsed['valid_envelope'] && $parsed['crlf_only'] && $parsed['within_limits'] && $parsed['valid_utf8_lines'];
		}
		$parsedAll = $filterParsed[0] ?? array();
		$events = $parsedAll['events'] ?? array();
		$byId = array();
		foreach ($events as $event) {
			$uid = $event['UID']['decoded'] ?? '';
			if (preg_match('/-(\d+)-[^-]+@?/', $uid, $matches)) $byId[(int) $matches[1]] = $event;
		}
		$first = $byId[1891] ?? array();
		$noTime = $byId[1892] ?? array();
		$midnight = $byId[1893] ?? array();
		$multiTimed = $byId[1894] ?? array();
		$expectedSummary = str_replace(array("\r\n", "\r"), "\n", $seed['artist']['artist_name']) . ' at ' . $seed['venue']['venue_name'];
		$expectedLocation = $seed['venue']['venue_name'] . ', ' . $seed['venue']['venue_address'] . ', ' . $seed['venue']['venue_city'] . ', ' . $seed['venue']['venue_country'];
		$plainNotes = str_replace(array("\r\n", "\r"), "\n", wp_strip_all_tags($seed['notes']));
		$expectedDescription = $oldSettings['tour_label'] . ': ' . $seed['tour']['tour_name'] . '. Price: ' . $seed['shows'][0]['show_price'] . '. Box office: ' . $seed['shows'][0]['show_tix_phone'] . '. Venue phone: ' . $seed['venue']['venue_phone'] . '. Notes: ' . $plainNotes . ' ' . $seed['shows'][0]['show_ages'];
		$expectedStart = gigpress_public_feeds_expected_utc($seed['shows'][0]['show_date'], $seed['shows'][0]['show_time']);
		$expectedMultiStart = gigpress_public_feeds_expected_utc($seed['shows'][3]['show_date'], $seed['shows'][3]['show_time']);
		$expectedMultiEnd = gigpress_public_feeds_expected_utc($seed['shows'][3]['show_expire'], $seed['shows'][3]['show_time']);
		$firstSummary = $first['SUMMARY']['decoded'] ?? null;
		$firstDescription = $first['DESCRIPTION']['decoded'] ?? null;
		$firstLocation = $first['LOCATION']['decoded'] ?? null;
		$firstUid = $first['UID']['decoded'] ?? null;
		$firstStart = $first['DTSTART;VALUE=DATE-TIME']['value'] ?? null;
		$firstEndCount = count(array_filter(array_keys($first), function ($key) { return strpos($key, 'DTEND') === 0; }));
		$noTimeStart = $noTime['DTSTART;VALUE=DATE']['value'] ?? null;
		$noTimeEnd = $noTime['DTEND;VALUE=DATE']['value'] ?? null;
		$midnightStart = $midnight['DTSTART;VALUE=DATE-TIME']['value'] ?? null;
		$midnightEndCount = count(array_filter(array_keys($midnight), function ($key) { return strpos($key, 'DTEND') === 0; }));
		$multiStart = $multiTimed['DTSTART;VALUE=DATE-TIME']['value'] ?? null;
		$multiEnd = $multiTimed['DTEND;VALUE=DATE-TIME']['value'] ?? null;
		$timeStampsValid = true;
		foreach ($events as $event) $timeStampsValid = $timeStampsValid && preg_match('/^\d{8}T\d{6}Z$/', $event['DTSTAMP']['value'] ?? '') === 1;
		$noTZID = true;
		foreach ($events as $event) foreach (array_keys($event) as $key) if (strpos($key, 'TZID=') !== false) $noTZID = false;
		$noInjection = count($events) === 4 && !isset($first['X-INJECTED']);
		$checks['ical_real_anonymous_endpoints_and_all_filters_keep_exact_membership_order'] = $allHttp && $filterIds === array($expectedIds, $expectedIds, $expectedIds) && gigpress_public_feeds_ical_ids($selectedParsed[1891]) === array(1891) && gigpress_public_feeds_ical_ids($selectedParsed[1892]) === array(1892) && gigpress_public_feeds_ical_ids($selectedParsed[1893]) === array(1893) && gigpress_public_feeds_ical_ids($selectedParsed[1894]) === array(1894);
		$checks['ical_independent_parser_confirms_crlf_envelope_types_folds_and_utf8'] = $formatChecks && count($events) === 4 && $parsedAll['has_fold'] && $parsedAll['valid_envelope'];
		$checks['ical_decoded_text_uri_uid_and_filter_identity_are_exact'] = $firstSummary === $expectedSummary && $firstDescription === $expectedDescription && $firstLocation === $expectedLocation && $firstUid === $expectedStart . '-1891-' . get_bloginfo('admin_email') && ($first['URL']['value'] ?? null) === get_bloginfo('url') && gigpress_public_feeds_ical_ids($parsedAll) === $expectedIds;
		$checks['ical_text_escapes_backslash_punctuation_and_newlines_without_injection'] = strpos($firstDescription ?? '', "CDATA closer: ]]>\nBEGIN:VEVENT\nX-Injected: no") !== false && strpos($firstDescription ?? '', 'Backslash \\route, commas ; semicolons.') !== false && $noInjection;
		$checks['ical_date_only_exclusive_end_preserves_no_time_multi_day_range'] = $noTimeStart === '20310611' && $noTimeEnd === '20310614' && ($noTime['DTSTART;VALUE=DATE']['value_type'] ?? null) === 'DATE' && ($noTime['DTEND;VALUE=DATE']['value_type'] ?? null) === 'DATE';
		$checks['ical_actual_midnight_is_timed_and_equal_end_is_omitted'] = $midnightStart === gigpress_public_feeds_expected_utc('2031-06-14', '00:00:00') && $midnightEndCount === 0 && $firstStart === $expectedStart && $firstEndCount === 0;
		$checks['ical_meaningful_timed_multi_day_end_is_preserved'] = $multiStart === $expectedMultiStart && $multiEnd === $expectedMultiEnd && $multiEnd > $multiStart;
		$checks['ical_utc_stamps_are_valid_and_timezone_is_not_misapplied'] = $timeStampsValid && $noTZID;
		$after = gigpress_public_feeds_snapshot();
		$checks['ical_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged'] = $before === $after;
		$checks['ical_repeated_and_concurrent_reads_are_complete'] = $repeatComplete && count($repeatReads) === 4;
		$evidence = array('expected_ids' => $expectedIds, 'filter_ids' => $filterIds, 'event_count' => count($events), 'folded' => $parsedAll['has_fold'] ?? false, 'repeated_responses' => count($repeatReads), 'repeated_complete' => $repeatComplete, 'decoded' => array('summary' => $firstSummary, 'expected_summary' => $expectedSummary, 'description' => $firstDescription, 'expected_description' => $expectedDescription, 'location' => $firstLocation, 'expected_location' => $expectedLocation, 'uid' => $firstUid, 'expected_uid' => $expectedStart . '-1891-' . get_bloginfo('admin_email'), 'url' => $first['URL']['value'] ?? null, 'expected_url' => get_bloginfo('url')), 'no_time' => array('start' => $noTimeStart, 'end' => $noTimeEnd), 'midnight' => array('start' => $midnightStart, 'dtend_count' => $midnightEndCount), 'timed_multi' => array('start' => $multiStart, 'end' => $multiEnd), 'snapshot_unchanged' => $before === $after);
	}

	if ($case === 'empty-contracts') {
		$reads = gigpress_public_feeds_http(array($base . '&artist=99999999'));
		$icalReads = gigpress_public_feeds_http(array('http://127.0.0.1/?feed=gigpress-ical&artist=99999999'));
		$rss = isset($reads[0]) ? $reads[0] : array('body' => '', 'status' => 0, 'content_type' => '', 'error' => 'missing response');
		$ical = isset($icalReads[0]) ? $icalReads[0] : array('body' => '', 'status' => 0, 'content_type' => '', 'error' => 'missing response');
		list($document, $xmlErrors) = gigpress_public_feeds_parse_xml($rss['body']);
		$parsedIcal = gigpress_public_feeds_parse_ical($ical['body']);
		$checks['empty_rss_anonymous_endpoint_has_valid_xml_header'] = $rss['status'] === 200 && strpos(strtolower($rss['content_type']), 'text/xml') === 0 && $rss['error'] === '' && $document instanceof DOMDocument && !$xmlErrors;
		$checks['empty_rss_retains_channel_and_has_zero_items'] = $document && $document->getElementsByTagName('channel')->length === 1 && $document->getElementsByTagName('item')->length === 0;
		$checks['empty_ical_anonymous_endpoint_has_calendar_header_and_valid_envelope'] = $ical['status'] === 200 && strpos(strtolower($ical['content_type']), 'text/calendar') === 0 && $ical['error'] === '' && $parsedIcal['valid_envelope'] && $parsedIcal['crlf_only'];
		$checks['empty_ical_calendar_parses_with_zero_events'] = count($parsedIcal['events']) === 0;
		$evidence = array('rss_status' => $rss['status'], 'rss_item_count' => $document ? $document->getElementsByTagName('item')->length : null, 'ical_status' => $ical['status'], 'ical_event_count' => count($parsedIcal['events']));
	}

	$gpo = $oldSettings;
	return array('case' => $case, 'checks' => $checks, 'evidence' => $evidence);
}

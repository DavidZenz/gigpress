<?php
/* Parsed public rendering contracts for HTML and JSON-LD boundaries. */

function gigpress_public_html_seed($fixture) {
	global $wpdb;
	$hostile = $fixture['values']['hostile_delimiters_protocols'];
	$rich = $fixture['values']['permitted_rich_notes'];
	$migrationPath = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/upgrade-preservation/1.4.php';
	$migrationFixture = is_readable($migrationPath) ? require $migrationPath : null;
	$validTicketUrl = $migrationFixture['shows'][0]['show_tix_url'] ?? '';
	$artistText = substr($fixture['values']['long_unicode_unbroken']['artist_name'], 0, 55) . ' <a href="javascript:alert(8)" onclick="alert(9)">unsafe link</a><img src=x onerror=alert(10)><script id="injected">alert(1)</script> & ' . $hostile['text'];
	$venueText = substr($fixture['values']['long_unicode_unbroken']['venue_name'], 0, 125) . ' <b>unsafe</b> & ' . $hostile['text'];
	$rows = array(
		'artist' => array('artist_id' => 891, 'artist_name' => $artistText, 'artist_alpha' => 'hostile html artist', 'artist_order' => 1, 'artist_url' => 'https://artists.example.test/hostile'),
		'venue' => array('venue_id' => 891, 'venue_name' => $venueText, 'venue_address' => '1 <img src=x onerror=alert(1)> Public Way', 'venue_city' => 'Wien & <script>city</script>', 'venue_state' => 'AT', 'venue_postal_code' => '1010', 'venue_country' => 'AT', 'venue_url' => 'javascript:alert(2)', 'venue_phone' => '+43 555 0199'),
		'show' => array('show_id' => 891, 'show_artist_id' => 891, 'show_venue_id' => 891, 'show_tour_id' => 0, 'show_date' => '2031-06-10', 'show_multi' => 0, 'show_time' => '19:30:00', 'show_expire' => '2031-06-10', 'show_price' => '24 & <free>', 'show_tix_url' => $validTicketUrl, 'show_tix_phone' => '', 'show_ages' => 'All ages & <12', 'show_notes' => $rich . "\r\n" . $hostile['calendar_text'] . "\n<![CDATA[</script>]]>\n</script><script id=\"notes-injected\">alert(2)</script>", 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => $hostile['url'], 'show_tour_restore' => 0, 'show_address' => '', 'show_locale' => '', 'show_country' => '', 'show_venue' => '', 'show_venue_url' => '', 'show_venue_phone' => ''),
	);
	foreach (array('artist' => GIGPRESS_ARTISTS, 'venue' => GIGPRESS_VENUES, 'show' => GIGPRESS_SHOWS) as $kind => $table) {
		if ($wpdb->insert($table, $rows[$kind]) === false) return false;
	}
	return array('rows' => $rows, 'artist_text' => $artistText, 'venue_text' => $venueText, 'ticket_url' => $validTicketUrl, 'fixture' => $fixture);
}

function gigpress_public_html_snapshot($ids, $postId = 0) {
	global $wpdb;
	$snapshot = array();
	foreach (array('artists' => array(GIGPRESS_ARTISTS, 'artist_id'), 'venues' => array(GIGPRESS_VENUES, 'venue_id'), 'shows' => array(GIGPRESS_SHOWS, 'show_id')) as $kind => $details) {
		$quoted = array_map('intval', $ids);
		$snapshot[$kind] = $wpdb->get_results('SELECT * FROM ' . $details[0] . ' WHERE ' . $details[1] . ' IN (' . implode(',', $quoted) . ') ORDER BY ' . $details[1], ARRAY_A);
	}
	$snapshot['related_post'] = $postId ? get_post((int) $postId, ARRAY_A) : null;
	return $snapshot;
}

function gigpress_public_html_parse($html) {
	$document = new DOMDocument('1.0', 'UTF-8');
	$previous = libxml_use_internal_errors(true);
	$loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	return $loaded ? $document : null;
}

function gigpress_public_html_case($case) {
	global $wpdb, $gpo, $post, $is_excerpt;
	$fixturePath = WP_PLUGIN_DIR . '/gigpress/tests/compat/fixtures/public-publishing/supplemental.php';
	$fixture = is_readable($fixturePath) ? require $fixturePath : null;
	if ($case !== 'html-json' || !is_array($fixture)) return array('case' => $case, 'checks' => array('fixture_loaded' => false));
	$seed = gigpress_public_html_seed($fixture);
	if (!$seed) return array('case' => $case, 'checks' => array('hostile_show_seeded' => false), 'reason' => 'hostile public row could not be seeded');
	$relatedPostId = wp_insert_post(array('post_title' => 'Related hostile <script>fixture</script>', 'post_content' => '<p>Linked post placement marker</p>', 'post_status' => 'publish', 'post_type' => 'post'), true);
	if (is_wp_error($relatedPostId) || !$relatedPostId || $wpdb->update(GIGPRESS_SHOWS, array('show_related' => (int) $relatedPostId), array('show_id' => 891), array('%d'), array('%d')) === false) {
		return array('case' => $case, 'checks' => array('related_fixture_linked' => false), 'reason' => 'related post could not be linked to the hostile show');
	}
	$oldSettings = $gpo;
	$gpo['artist_link'] = 1;
	$gpo['display_country'] = 1;
	$gpo['output_schema_json'] = 'y';
	$gpo['buy_tickets_label'] = 'Buy & <Tickets>';
	$gpo['related_position'] = 'before';
	$gpo['relatedlink_notes'] = 1;
	$gpo['relatedlink_city'] = 1;
	$gpo['rss_title'] = 'Feed " title & <svg onload=alert(3)>';
	$gpo['display_subscriptions'] = 1;
	$gpo['artist_label'] = 'Acts "quoted" & <img src=x onerror=alert(4)>';
	$gpo['related_heading'] = 'Related "heading" & <svg onload=alert(5)>';
	$gpo['related'] = 'More "information" & <img src=x onerror=alert(6)>';
	$before = gigpress_public_html_snapshot(array(891), $relatedPostId);
	$html = do_shortcode('[gigpress_shows scope="upcoming" group_artists="no"]');
	$document = gigpress_public_html_parse($html);
	$scripts = $document ? $document->getElementsByTagName('script') : array();
	$jsonScripts = array();
	if ($document) foreach ($scripts as $script) if ($script->getAttribute('type') === 'application/ld+json') $jsonScripts[] = $script;
	$events = count($jsonScripts) === 1 ? json_decode($jsonScripts[0]->textContent, true) : null;
	$event = null;
	if (is_array($events)) foreach ($events as $candidate) {
		if (($candidate['performers']['name'] ?? null) === $seed['artist_text']) $event = $candidate;
	}
	$visible = $document ? preg_replace('/\s+/', ' ', $document->textContent) : '';
	$artistVisible = preg_replace('/\s+/', ' ', html_entity_decode(wptexturize(esc_html($seed['artist_text'])), ENT_QUOTES, 'UTF-8'));
	$venueVisible = preg_replace('/\s+/', ' ', html_entity_decode(wptexturize(esc_html($seed['venue_text'])), ENT_QUOTES, 'UTF-8'));
	$unsafeLinkFound = false;
	if ($document) foreach ($document->getElementsByTagName('a') as $link) {
		if (preg_match('/^\s*javascript\s*:/i', $link->getAttribute('href'))) $unsafeLinkFound = true;
	}
	$after = gigpress_public_html_snapshot(array(891), $relatedPostId);
	$stream = fopen('php://memory', 'r');
	$encodingFailureHandled = gigpress_json_ld_script(array('unsupported' => $stream)) === '';
	fclose($stream);
	$checks = array(
		'hostile_show_seeded' => true,
		'related_fixture_linked' => true,
		'main_dom_parses' => $document instanceof DOMDocument,
		'main_intended_json_script_count' => count($jsonScripts) === 1 && count($scripts) === 1,
		'main_json_parses_and_contains_fixture_event' => is_array($events) && is_array($event) && ($event['@type'] ?? '') === 'Event',
		'main_json_exact_plain_artist' => is_array($event) && ($event['name'] ?? null) === $seed['artist_text'] && ($event['performers']['name'] ?? null) === $seed['artist_text'],
		'main_json_exact_plain_venue_and_city' => is_array($event) && ($event['location']['name'] ?? null) === $seed['venue_text'] && ($event['location']['address']['addressLocality'] ?? null) === 'Wien & <script>city</script>',
		'main_json_keeps_valid_saved_ticket_url' => is_array($event) && ($event['offers']['url'] ?? null) === $seed['ticket_url'],
		'main_json_notes_are_plain' => is_array($event) && ($event['description'] ?? null) === wp_strip_all_tags($seed['rows']['show']['show_notes']) && strpos($jsonScripts[0]->textContent ?? '', '</script>') === false,
		'main_html_displays_hostile_text_as_text' => strpos($visible, $artistVisible) !== false && strpos($visible, $venueVisible) !== false,
		'main_html_rejects_unsafe_protocols' => $document && !$document->getElementsByTagName('img')->length && !$document->getElementsByTagName('svg')->length && !$document->getElementsByTagName('free')->length && !$unsafeLinkFound,
		'main_allowed_note_formatting_retained' => $document && $document->getElementsByTagName('strong')->length > 0 && $document->getElementsByTagName('p')->length > 0,
		'main_json_encoding_failure_is_handled' => $encodingFailureHandled,
		'stored_rows_unchanged_during_public_read' => $before === $after,
	);
	$mainXPath = $document ? new DOMXPath($document) : null;
	$fixtureRow = $mainXPath ? $mainXPath->query('//tr[@data-show-id="891" and contains(concat(" ", normalize-space(@class), " "), " active ")]')->item(0) : null;
	$ticketAnchor = $mainXPath ? $mainXPath->query('//tr[@data-show-id="891"]/following-sibling::tr[1]//a[contains(concat(" ", normalize-space(@class), " "), " gigpress-tickets-link ")]')->item(0) : null;
	$addressAnchor = $mainXPath ? $mainXPath->query('//tr[@data-show-id="891"]/following-sibling::tr[1]//a[contains(concat(" ", normalize-space(@class), " "), " gigpress-address ")]')->item(0) : null;
	$calendarAnchors = $mainXPath ? $mainXPath->query('//tr[@data-show-id="891"]/following-sibling::tr[1]//span[contains(concat(" ", normalize-space(@class), " "), " gigpress-calendar-actions ")]/a') : array();
	$mainNotes = $mainXPath ? $mainXPath->query('//tr[@data-show-id="891"]/following-sibling::tr[1]//div[contains(concat(" ", normalize-space(@class), " "), " gigpress-notes ")]')->item(0) : null;
	$addressUrl = $addressAnchor instanceof DOMElement ? wp_parse_url($addressAnchor->getAttribute('href')) : array();
	$addressParams = array();
	if (isset($addressUrl['query'])) parse_str($addressUrl['query'], $addressParams);
	$expectedAddressParts = array();
	foreach (array($seed['rows']['venue']['venue_address'], $seed['rows']['venue']['venue_city'], $seed['rows']['venue']['venue_state'], $seed['rows']['venue']['venue_postal_code'], $seed['rows']['venue']['venue_country']) as $addressPart) {
		$addressPart = trim(preg_replace('/\s+/', ' ', (string) $addressPart));
		if ($addressPart !== '') $expectedAddressParts[] = $addressPart;
	}
	$expectedAddressQuery = implode(', ', $expectedAddressParts);
	$mainNotesMarkup = $mainNotes instanceof DOMElement ? $document->saveHTML($mainNotes) : '';
	$googleCalendar = array();
	if ($calendarAnchors && $calendarAnchors->length) parse_str((string) wp_parse_url($calendarAnchors->item(0)->getAttribute('href'), PHP_URL_QUERY), $googleCalendar);
	$footerLinks = $mainXPath ? $mainXPath->query('//p[contains(concat(" ", normalize-space(@class), " "), " gigpress-subscribe ")]/a') : array();
	$footerTitleOk = false;
	if ($footerLinks) foreach ($footerLinks as $footerLink) {
		if ($footerLink->getAttribute('title') === $gpo['rss_title'] . ' RSS' || $footerLink->getAttribute('title') === $gpo['rss_title'] . ' iCalendar') $footerTitleOk = true;
	}
	$checks['main_partial_preserves_status_ticket_and_calendar_contracts'] = $fixtureRow instanceof DOMElement && $ticketAnchor instanceof DOMElement
		&& $ticketAnchor->getAttribute('href') === $seed['ticket_url'] && $ticketAnchor->textContent === $gpo['buy_tickets_label']
		&& $calendarAnchors && $calendarAnchors->length === 2 && $calendarAnchors->item(0)->textContent === __('Add to Google Calendar', 'gigpress')
		&& $calendarAnchors->item(1)->textContent === __('Download iCalendar', 'gigpress');
	$checks['main_note_markup_keeps_rich_formatting_and_source_line_breaks'] = $mainNotes instanceof DOMElement
		&& $mainNotes->getElementsByTagName('strong')->length > 0
		&& strpos(str_replace(array("\r\n", "\r"), "\n", $mainNotes->textContent), "First line\nBEGIN:VEVENT") !== false;
	$checks['main_note_block_boundaries_do_not_add_redundant_source_breaks'] = $mainNotes instanceof DOMElement
		&& strpos($mainNotesMarkup, '</ul>First line') !== false
		&& strpos($mainNotesMarkup, "</ul>\nFirst line") === false;
	$checks['main_address_uses_encoded_https_google_maps_search'] = $addressAnchor instanceof DOMElement
		&& ($addressUrl['scheme'] ?? '') === 'https'
		&& ($addressUrl['host'] ?? '') === 'www.google.com'
		&& ($addressUrl['path'] ?? '') === '/maps/search/'
		&& ($addressParams['api'] ?? '') === '1'
		&& ($addressParams['query'] ?? '') === $expectedAddressQuery
		&& strpos($addressAnchor->getAttribute('href'), 'onerror=') === false;
	$stylesheetPath = dirname(__DIR__, 2) . '/css/gigpress.css';
	$stylesheet = is_readable($stylesheetPath) ? file_get_contents($stylesheetPath) : false;
	$checks['compact_rich_notes_keep_block_flow_before_links'] = is_string($stylesheet)
		&& preg_match('/\.gigpress-layout-bundled\\s+\.gigpress-notes\\s*\\{[^}]*display\\s*:\\s*block\\s*;/s', $stylesheet) === 1;
	$checks['main_google_calendar_values_are_readable_plain_text'] = isset($googleCalendar['text'], $googleCalendar['location'], $googleCalendar['details'])
		&& strpos($googleCalendar['text'], '<a') === false && strpos($googleCalendar['text'], 'javascript:') === false
		&& strpos($googleCalendar['location'], '<img') === false && strpos($googleCalendar['location'], 'onerror=') === false
		&& strpos($googleCalendar['details'], '<p') === false && strpos($googleCalendar['details'], '<strong') === false
		&& strpos($googleCalendar['details'], '<script') === false && strpos($googleCalendar['details'], 'alert(2)') === false;
	$checks['subscription_partial_escapes_titles_and_keeps_feed_destinations'] = $footerLinks && $footerLinks->length === 2 && $footerTitleOk
		&& strpos($footerLinks->item(0)->getAttribute('href'), '?feed=gigpress') !== false
		&& strpos($footerLinks->item(1)->getAttribute('href'), 'gigpress-ical') !== false;
	$checks['hostile_main_labels_are_inert'] = $document && !$document->getElementsByTagName('img')->length && !$document->getElementsByTagName('svg')->length
		&& !$document->getElementById('injected') && !$document->getElementById('notes-injected');
	$groupedHtml = do_shortcode('[gigpress_shows scope="upcoming" group_artists="yes" artist_order="custom"]');
	$groupedDocument = gigpress_public_html_parse($groupedHtml);
	$groupedScripts = $groupedDocument ? $groupedDocument->getElementsByTagName('script') : array();
	$groupedJson = null;
	if ($groupedDocument) foreach ($groupedScripts as $script) if ($script->getAttribute('type') === 'application/ld+json') $groupedJson = json_decode($script->textContent, true);
	$groupedEvent = null;
	if (is_array($groupedJson)) foreach ($groupedJson as $candidate) if (($candidate['performers']['name'] ?? null) === $seed['artist_text']) $groupedEvent = $candidate;
	$checks['grouped_main_json_branch_has_exact_fixture_event'] = $groupedDocument && $groupedJson && is_array($groupedEvent)
		&& $groupedEvent['performers']['name'] === $seed['artist_text'] && count($groupedDocument->getElementsByTagName('script')) === 1;
	$groupedXPath = $groupedDocument ? new DOMXPath($groupedDocument) : null;
	$groupedArtistLink = $groupedXPath ? $groupedXPath->query('//h3[@id="artist-891"]/a[@href="https://artists.example.test/hostile"]')->item(0) : null;
	$checks['grouped_artist_link_renders_with_safe_destination'] = $groupedArtistLink instanceof DOMElement
		&& $groupedArtistLink->getAttribute('href') === 'https://artists.example.test/hostile';
	$checks['grouped_artist_link_escapes_artist_markup'] = $groupedArtistLink instanceof DOMElement
		&& $groupedArtistLink->getElementsByTagName('a')->length === 0
		&& strpos($groupedArtistLink->textContent, '<a href') !== false;
	$checks['grouped_artist_link_has_no_unsafe_descendants'] = $groupedXPath
		&& $groupedXPath->query('//h3[@id="artist-891"]//a[@onclick or starts-with(translate(@href,"JAVASCRIPT","javascript"),"javascript:")]')->length === 0
		&& !$groupedDocument->getElementById('injected');
	$groupedSubscriptionLinks = $groupedXPath ? $groupedXPath->query('//h3[contains(concat(" ", normalize-space(@class), " "), " gigpress-artist-heading ")]//span[contains(concat(" ", normalize-space(@class), " "), " gigpress-artist-subscriptions ")]/a[contains(@href, "artist=891")]') : array();
	$groupedTitlesAndDestinationsSafe = $groupedSubscriptionLinks && $groupedSubscriptionLinks->length === 2
		&& $groupedSubscriptionLinks->item(0)->getAttribute('title') === html_entity_decode(wptexturize($seed['artist_text']), ENT_QUOTES, 'UTF-8') . ' RSS'
		&& $groupedSubscriptionLinks->item(1)->getAttribute('title') === html_entity_decode(wptexturize($seed['artist_text']), ENT_QUOTES, 'UTF-8') . ' iCalendar'
		&& $groupedSubscriptionLinks->item(0)->getAttribute('href') === GIGPRESS_RSS . '&artist=891'
		&& $groupedSubscriptionLinks->item(1)->getAttribute('href') === GIGPRESS_WEBCAL . '&artist=891';
	$groupedBogusAttributes = false;
	if ($groupedDocument) foreach ($groupedDocument->getElementsByTagName('*') as $element) {
		foreach ($element->attributes as $attribute) if (str_starts_with(strtolower($attribute->name), 'on') || $attribute->name === 'async') $groupedBogusAttributes = true;
	}
	$checks['grouped_artist_subscription_titles_and_urls_are_safe'] = $groupedTitlesAndDestinationsSafe && !$groupedBogusAttributes;
	$checks['grouped_hostile_artist_markup_remains_inert'] = $groupedDocument && !$groupedDocument->getElementById('injected')
		&& !$groupedDocument->getElementsByTagName('svg')->length && $groupedDocument->getElementsByTagName('img')->length === 2 * $groupedXPath->query('//h3[contains(concat(" ", normalize-space(@class), " "), " gigpress-artist-heading ")]')->length
		&& $groupedXPath->query('//a[@onclick or starts-with(translate(@href,"JAVASCRIPT","javascript"),"javascript:")]')->length === 0
		&& $groupedXPath->query('//img[@src="x" or @onerror]')->length === 0;
	$post = get_post((int) $relatedPostId);
	$is_excerpt = false;
	$related = gigpress_show_related(array('scope' => 'upcoming'), '<p>Caller placement marker</p>');
	$relatedDocument = gigpress_public_html_parse($related);
	$relatedScripts = $relatedDocument ? $relatedDocument->getElementsByTagName('script') : array();
	$relatedJsonScripts = array();
	if ($relatedDocument) foreach ($relatedScripts as $script) if ($script->getAttribute('type') === 'application/ld+json') $relatedJsonScripts[] = $script;
	$relatedEvents = count($relatedJsonScripts) === 1 ? json_decode($relatedJsonScripts[0]->textContent, true) : null;
	$relatedEvent = null;
	if (is_array($relatedEvents)) foreach ($relatedEvents as $candidate) if (($candidate['performers']['name'] ?? null) === $seed['artist_text']) $relatedEvent = $candidate;
	$checks['related_json_script_parses_exact_plain_event'] = count($relatedScripts) === 1 && count($relatedJsonScripts) === 1 && is_array($relatedEvent)
		&& ($relatedEvent['performers']['name'] ?? null) === $seed['artist_text'] && ($relatedEvent['location']['name'] ?? null) === $seed['venue_text'];
	$checks['related_linked_post_placement_and_safe_urls'] = strpos($related, '<p>Caller placement marker</p>') !== false
		&& strpos($related, 'gigpress-related-show') !== false && strpos($related, 'gigpress-related-show') < strpos($related, '<p>Caller placement marker</p>')
		&& $relatedDocument && !$relatedDocument->getElementsByTagName('img')->length && $relatedDocument->getElementsByTagName('script')->length === 1;
	$relatedXPath = $relatedDocument ? new DOMXPath($relatedDocument) : null;
	$relatedSurface = $relatedXPath ? $relatedXPath->query('//ul[contains(concat(" ", normalize-space(@class), " "), " gigpress-related-show ") and contains(concat(" ", normalize-space(@class), " "), " active ")]')->item(0) : null;
	$checks['related_partial_keeps_active_class_notes_and_inert_labels'] = $relatedSurface instanceof DOMElement
		&& $relatedDocument->getElementsByTagName('strong')->length > 0 && !$relatedDocument->getElementsByTagName('img')->length
		&& !$relatedDocument->getElementsByTagName('svg')->length;
	$gpo['output_schema_json'] = 'n';
	$relatedDisabled = gigpress_show_related(array('scope' => 'upcoming'), '<p>Disabled schema marker</p>');
	$checks['related_schema_disabled_keeps_html_without_json'] = strpos($relatedDisabled, 'gigpress-related-show') !== false
		&& strpos($relatedDisabled, 'application/ld+json') === false;
	$gpo['output_schema_json'] = 'y';
	$post = wp_insert_post(array('post_title' => 'Unrelated empty fixture', 'post_status' => 'publish', 'post_type' => 'post'), true);
	$emptyRelated = gigpress_show_related(array('scope' => 'upcoming'), '<p>Empty related marker</p>');
	$checks['related_empty_branch_returns_caller_content_without_json'] = $emptyRelated === '<p>Empty related marker</p>' && strpos($emptyRelated, 'application/ld+json') === false;
	$post = get_post((int) $relatedPostId);
	$widget = new Gigpress_widget();
	ob_start();
	$widget->widget(array('before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'), array('title' => 'Hostile Widget', 'scope' => 'upcoming', 'limit' => 20, 'group_artists' => 'no', 'show_feeds' => 'no'));
	$widgetHtml = ob_get_clean();
	$widgetDocument = gigpress_public_html_parse($widgetHtml);
	$widgetVisible = $widgetDocument ? preg_replace('/\s+/', ' ', $widgetDocument->textContent) : '';
	$widgetUnsafeLink = false;
	if ($widgetDocument) foreach ($widgetDocument->getElementsByTagName('a') as $link) if (preg_match('/^\s*javascript\s*:/i', $link->getAttribute('href'))) $widgetUnsafeLink = true;
	$checks['widget_displays_hostile_text_as_text'] = strpos($widgetVisible, $venueVisible) !== false && strpos($widgetVisible, $artistVisible) !== false;
	$checks['widget_compact_output_and_unsafe_protocol_rejected'] = $widgetDocument && $widgetDocument->getElementsByTagName('li')->length > 0
		&& !$widgetUnsafeLink && !$widgetDocument->getElementsByTagName('img')->length;
	$widgetXPath = $widgetDocument ? new DOMXPath($widgetDocument) : null;
	$widgetRow = $widgetXPath ? $widgetXPath->query('//li[contains(concat(" ", normalize-space(@class), " "), " active ")]')->item(0) : null;
	$checks['sidebar_partial_keeps_compact_status_and_saved_destination'] = $widgetRow instanceof DOMElement
		&& strpos($widgetRow->textContent, 'Buy & <Tickets>') !== false && $widgetDocument->getElementsByTagName('a')->length > 0;
	$gpo['shows_page'] = 'javascript:alert(20)';
	$unsafeLinkText = 'More "hostile" <img src=x onerror=alert(21)>';
	ob_start();
	$widget->widget(array('before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'), array('title' => 'Feed Widget', 'scope' => 'upcoming', 'limit' => 20, 'group_artists' => 'no', 'show_feeds' => 'yes', 'link_text' => $unsafeLinkText));
	$feedWidgetHtml = ob_get_clean();
	$feedWidgetDocument = gigpress_public_html_parse($feedWidgetHtml);
	$feedWidgetXPath = $feedWidgetDocument ? new DOMXPath($feedWidgetDocument) : null;
	$feedLinks = $feedWidgetXPath ? $feedWidgetXPath->query('//p[contains(concat(" ", normalize-space(@class), " "), " gigpress-subscribe ")]/a') : array();
	$feedTitleSafe = $feedLinks && $feedLinks->length === 2
		&& $feedLinks->item(0)->getAttribute('title') === html_entity_decode(wptexturize($gpo['rss_title']), ENT_QUOTES, 'UTF-8') . ' RSS'
		&& $feedLinks->item(1)->getAttribute('title') === html_entity_decode(wptexturize($gpo['rss_title']), ENT_QUOTES, 'UTF-8') . ' iCalendar';
	$feedUrlsSafe = $feedLinks && $feedLinks->item(0)->getAttribute('href') === GIGPRESS_RSS
		&& $feedLinks->item(1)->getAttribute('href') === GIGPRESS_WEBCAL;
	$moreLink = $feedWidgetXPath ? $feedWidgetXPath->query('//p[contains(concat(" ", normalize-space(@class), " "), " gigpress-sidebar-more ")]/a')->item(0) : null;
	$safeLinkText = html_entity_decode(wptexturize($unsafeLinkText), ENT_QUOTES, 'UTF-8');
	$checks['sidebar_hostile_feed_titles_and_urls_are_safe'] = $feedWidgetDocument && $feedTitleSafe && $feedUrlsSafe
		&& $moreLink instanceof DOMElement && $moreLink->getAttribute('href') === ''
		&& $moreLink->getAttribute('title') === $safeLinkText && $moreLink->textContent === $safeLinkText;
	ob_start();
	$widget->widget(array('before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'), array('title' => 'Artist Feed Widget', 'scope' => 'upcoming', 'limit' => 20, 'group_artists' => 'no', 'artist' => 891, 'show_feeds' => 'yes'));
	$artistFeedWidgetHtml = ob_get_clean();
	$artistFeedWidgetDocument = gigpress_public_html_parse($artistFeedWidgetHtml);
	$artistFeedXPath = $artistFeedWidgetDocument ? new DOMXPath($artistFeedWidgetDocument) : null;
	$artistFeedLinks = $artistFeedXPath ? $artistFeedXPath->query('//p[contains(concat(" ", normalize-space(@class), " "), " gigpress-subscribe ")]/a') : array();
	$artistFeedTitlesSafe = $artistFeedLinks && $artistFeedLinks->length === 2
		&& $artistFeedLinks->item(0)->getAttribute('title') === html_entity_decode(wptexturize($seed['artist_text']), ENT_QUOTES, 'UTF-8') . ' RSS'
		&& $artistFeedLinks->item(1)->getAttribute('title') === html_entity_decode(wptexturize($seed['artist_text']), ENT_QUOTES, 'UTF-8') . ' iCalendar';
	$artistFeedUrlsSafe = $artistFeedLinks && $artistFeedLinks->item(0)->getAttribute('href') === GIGPRESS_RSS . '&artist=891'
		&& $artistFeedLinks->item(1)->getAttribute('href') === GIGPRESS_WEBCAL . '&artist=891'
		&& !$artistFeedWidgetDocument->getElementById('injected') && !$artistFeedXPath->query('//a[@onclick]')->length;
	$checks['sidebar_hostile_artist_feed_titles_are_safe'] = $artistFeedTitlesSafe;
	$checks['sidebar_hostile_artist_feed_urls_and_ids_are_safe'] = $artistFeedUrlsSafe;
	$checks['related_and_widget_reads_leave_snapshots_unchanged'] = $before === gigpress_public_html_snapshot(array(891), $relatedPostId);
	$gpo = $oldSettings;
	return array('case' => 'html-json', 'checks' => $checks, 'expected_event' => array('artist' => $seed['artist_text'], 'venue' => $seed['venue_text']), 'actual_event' => $event, 'related_event' => $relatedEvent, 'main_tag_counts' => array('img' => $document ? $document->getElementsByTagName('img')->length : null, 'svg' => $document ? $document->getElementsByTagName('svg')->length : null, 'free' => $document ? $document->getElementsByTagName('free')->length : null), 'grouped_json_event_count' => is_array($groupedJson) ? count($groupedJson) : null, 'grouped_has_event' => is_array($groupedEvent), 'script_count' => count($scripts));
}

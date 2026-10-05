<?php

/** Encode a value for XML text or an XML quoted attribute. */
function gigpress_feed_xml($value) {
	$charset = get_bloginfo('charset');
	$encoded = htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, $charset ? $charset : 'UTF-8');
	return str_replace("\r", '&#13;', $encoded);
}

/** Keep rich notes as a safe HTML fragment while protecting the enclosing CDATA. */
function gigpress_feed_cdata($value) {
	$value = str_replace(']]>', ']]]]><![CDATA[>', (string) $value);
	$value = str_replace("\r", ']]>&#13;<![CDATA[', $value);
	return '<![CDATA[' . $value . ']]>';
}

/** Return an escaped HTML fragment suitable for the rich RSS item description. */
function gigpress_feed_description($showdata, $show) {
	$plain = isset($showdata['plain']) && is_array($showdata['plain']) ? $showdata['plain'] : array();
	$html = '<ul>';
	$html .= '<li><strong>' . esc_html((string) $GLOBALS['gpo']['artist_label']) . ':</strong> ' . esc_html($plain['artist'] ?? '') . '</li>';
	if (!empty($plain['tour'])) $html .= '<li><strong>' . esc_html((string) $GLOBALS['gpo']['tour_label']) . ':</strong> ' . esc_html($plain['tour']) . '</li>';
	$html .= '<li><strong>' . esc_html__('Date', 'gigpress') . ':</strong> ' . esc_html(mysql2date($GLOBALS['gpo']['date_format_long'], $show->show_date));
	if ($show->show_expire !== $show->show_date) $html .= ' - ' . esc_html(mysql2date($GLOBALS['gpo']['date_format_long'], $show->show_expire));
	$html .= '</li>';
	if (!empty($showdata['time'])) $html .= '<li><strong>' . esc_html__('Time', 'gigpress') . ':</strong> ' . esc_html($showdata['time']) . '</li>';
	$city = (string) ($plain['city'] ?? '');
	if (!empty($plain['state'])) $city .= ', ' . $plain['state'];
	$html .= '<li><strong>' . esc_html__('City', 'gigpress') . ':</strong> ' . esc_html($city) . '</li>';
	$html .= '<li><strong>' . esc_html__('Venue', 'gigpress') . ':</strong> ' . esc_html($plain['venue'] ?? '') . '</li>';
	if (!empty($plain['address'])) $html .= '<li><strong>' . esc_html__('Address', 'gigpress') . ':</strong> ' . esc_html($plain['address']) . '</li>';
	if (!empty($plain['venue_phone'])) $html .= '<li><strong>' . esc_html__('Venue phone', 'gigpress') . ':</strong> ' . esc_html($plain['venue_phone']) . '</li>';
	$html .= '<li><strong>' . esc_html__('Country', 'gigpress') . ':</strong> ' . esc_html($plain['country'] ?? '') . '</li>';
	if (!empty($plain['price'])) $html .= '<li><strong>' . esc_html__('Admission', 'gigpress') . ':</strong> ' . esc_html($plain['price']) . '</li>';
	if (!empty($plain['admittance'])) $html .= '<li><strong>' . esc_html__('Age restrictions', 'gigpress') . ':</strong> ' . esc_html($plain['admittance']) . '</li>';
	if (!empty($plain['ticket_phone'])) $html .= '<li><strong>' . esc_html__('Box office', 'gigpress') . ':</strong> ' . esc_html($plain['ticket_phone']) . '</li>';
	if (!empty($plain['ticket_url'])) {
		$html .= '<li><a href="' . esc_url($plain['ticket_url']) . '">' . esc_html($plain['ticket_label'] ?? '') . '</a></li>';
	}
	if (!empty($show->show_external_url)) {
		$html .= '<li><a href="' . esc_url($show->show_external_url) . '">' . esc_html($GLOBALS['gpo']['external_link_label']) . '</a></li>';
	}
	if (!empty($show->show_notes)) {
		$html .= '<li><strong>' . esc_html__('Notes', 'gigpress') . ':</strong> ' . wp_kses_post((string) $show->show_notes) . '</li>';
	}
	if (!empty($plain['related_url'])) {
		$html .= '<li><a href="' . esc_url($plain['related_url']) . '">' . esc_html($GLOBALS['gpo']['related']) . '</a></li>';
	}
	$calendarStart = (string) ($showdata['calendar_start'] ?? '');
	$calendarEnd = (string) ($showdata['calendar_end'] ?? '');
	$calendarSummary = (string) ($showdata['calendar_summary'] ?? '');
	$calendarLocation = (string) ($showdata['calendar_location'] ?? '');
	$calendarDetails = (string) ($showdata['calendar_details'] ?? '');
	$gcal = 'http://www.google.com/calendar/event?action=TEMPLATE&text=' . rawurlencode($calendarSummary)
		. '&dates=' . rawurlencode($calendarStart . '/' . $calendarEnd)
		. '&sprop=website:' . rawurlencode(GIGPRESS_URL)
		. '&sprop=name:' . rawurlencode($plain['artist'] ?? '')
		. '&location=' . rawurlencode($calendarLocation)
		. '&details=' . rawurlencode($calendarDetails) . '&trp=true';
	$ical = GIGPRESS_ICAL . '&show_id=' . (int) ($showdata['id'] ?? 0);
	$html .= '<li><a href="' . esc_url($gcal) . '">' . esc_html__('Add to Google Calendar', 'gigpress') . '</a> | <a href="' . esc_url($ical) . '">' . esc_html__('Download iCalendar', 'gigpress') . '</a></li>';
	return $html . '</ul>';
}

function gigpress_feed() {
	global $wpdb, $gpo;
	header('Content-Type: text/xml; charset=' . get_bloginfo('charset'));
	$charset = get_bloginfo('charset');
	echo '<?xml version="1.0" encoding="' . gigpress_feed_xml($charset) . '"?>' . "\n";

	$filter = '';
	$filter .= isset($_GET['tour']) ? $wpdb->prepare('AND s.show_tour_id = %d ', absint($_GET['tour'])) : '';
	$filter .= isset($_GET['artist']) ? $wpdb->prepare('AND s.show_artist_id = %d ', absint($_GET['artist'])) : '';
	$filter .= isset($_GET['venue']) ? $wpdb->prepare('AND s.show_venue_id = %d ', absint($_GET['venue'])) : '';
	$limit = (!empty($gpo['rss_limit'])) ? $gpo['rss_limit'] : 100;
	$shows = $wpdb->get_results(
		$wpdb->prepare("SELECT * FROM " . GIGPRESS_ARTISTS . " AS a, " . GIGPRESS_VENUES . " as v, " . GIGPRESS_SHOWS . " AS s LEFT JOIN " . GIGPRESS_TOURS . " AS t ON s.show_tour_id = t.tour_id WHERE show_expire >= '" . GIGPRESS_NOW . "' AND show_status != 'deleted' AND s.show_artist_id = a.artist_id AND s.show_venue_id = v.venue_id " . $filter . "ORDER BY show_date ASC,show_time ASC LIMIT %d", $limit)
	);
	$shows = is_array($shows) ? $shows : array();

	$channelTitle = (string) ($gpo['rss_title'] ?? '');
	$channelDescription = $channelTitle;
	if ($shows) {
		$firstData = gigpress_prepare($shows[0], 'feed');
		$firstPlain = $firstData['plain'] ?? array();
		if (isset($_GET['artist'])) $channelTitle .= ': ' . ($firstPlain['artist'] ?? '');
		if (isset($_GET['tour'])) $channelTitle .= ': ' . ($firstPlain['tour'] ?? '');
		if (isset($_GET['venue'])) $channelTitle .= ': ' . ($firstPlain['venue'] ?? '');
		$channelDescription = $channelTitle;
	}

	$self = GIGPRESS_RSS;
	foreach (array('artist', 'tour', 'venue') as $key) {
		if (isset($_GET[$key])) $self .= '&' . rawurlencode($key) . '=' . rawurlencode((string) absint($_GET[$key]));
	}
	echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n<channel>\n";
	echo '<title>' . gigpress_feed_xml($channelTitle) . "</title>\n";
	echo '<description>' . gigpress_feed_xml($channelDescription) . "</description>\n";
	echo '<atom:link href="' . gigpress_feed_xml($self) . '" rel="self" type="application/rss+xml" />' . "\n";
	echo '<link>' . gigpress_feed_xml(GIGPRESS_URL) . "</link>\n";
	foreach ($shows as $show) {
		$showdata = gigpress_prepare($show, 'feed');
		$plain = isset($showdata['plain']) && is_array($showdata['plain']) ? $showdata['plain'] : array();
		$date = mysql2date($gpo['date_format'], $show->show_date);
		$itemTitle = (string) ($plain['artist'] ?? '') . ' ' . __('in', 'gigpress') . ' ' . (string) ($plain['city'] ?? '') . ' ' . __('on', 'gigpress') . ' ' . $date;
		$description = gigpress_feed_description($showdata, $show);
		echo "<item>\n";
		echo '<title>' . gigpress_feed_xml($itemTitle) . "</title>\n";
		echo '<description>' . gigpress_feed_cdata($description) . "</description>\n";
		$itemUrl = !empty($plain['related_url']) ? $plain['related_url'] : (!empty($gpo['shows_page']) ? $gpo['shows_page'] : get_bloginfo('url'));
		echo '<link>' . gigpress_feed_xml(esc_url_raw($itemUrl)) . "</link>\n";
		echo '<guid isPermaLink="false">' . gigpress_feed_xml('#show-' . (int) $show->show_id) . "</guid>\n";
		echo '<pubDate>' . gigpress_feed_xml($showdata['rss_date'] ?? '') . "</pubDate>\n";
		echo "</item>\n";
	}
	echo "</channel>\n</rss>\n";
}

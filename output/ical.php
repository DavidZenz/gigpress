<?php

/** Escape one RFC 5545 TEXT property value. */
function gigpress_ical_escape_text($value) {
	$value = str_replace('\\', '\\\\', (string) $value);
	$value = str_replace(array("\r\n", "\r", "\n"), '\\n', $value);
	return str_replace(array(';', ','), array('\\;', '\\,'), $value);
}

/** Fold a calendar content line at 75 octets without splitting UTF-8 characters. */
function gigpress_ical_fold_line($line) {
	$line = (string) $line;
	$characters = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
	if ($characters === false) $characters = str_split($line);
	$lines = array();
	$current = '';
	$limit = 75;
	$length = 0;
	foreach ($characters as $character) {
		$bytes = strlen($character);
		if ($length > 0 && $length + $bytes > $limit) {
			$lines[] = $current;
			$current = ' ';
			$length = 1;
			$limit = 75;
		}
		$current .= $character;
		$length += $bytes;
	}
	$lines[] = $current;
	return implode("\r\n", $lines) . "\r\n";
}

/** Serialize a typed calendar property with folding and CRLF termination. */
function gigpress_ical_property($name, $value, $type = 'text') {
	$name = (string) $name;
	$value = (string) $value;
	switch ($type) {
		case 'text':
			$value = gigpress_ical_escape_text($value);
			break;
		case 'uri':
			$value = esc_url_raw($value);
			if ($value === '' || preg_match('/[\r\n]/', $value)) return '';
			break;
		case 'date':
			if (!preg_match('/^\d{8}$/', $value)) return '';
			break;
		case 'date-time':
			if (!preg_match('/^\d{8}T\d{6}Z$/', $value)) return '';
			break;
		default:
			return '';
	}
	return gigpress_ical_fold_line($name . ':' . $value);
}

/** Convert a site-local stored event date/time into a UTC calendar stamp. */
function gigpress_ical_utc_datetime($date, $time) {
	$gmt = get_gmt_from_date((string) $date . ' ' . (string) $time);
	$parsed = DateTime::createFromFormat('!Y-m-d H:i:s', $gmt, new DateTimeZone('UTC'));
	$errors = DateTime::getLastErrors();
	if (!$parsed || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) return '';
	return $parsed->format('Ymd\THis\Z');
}

function gigpress_ical_date($date) {
	$parsed = DateTime::createFromFormat('!Y-m-d', (string) $date, new DateTimeZone('UTC'));
	$errors = DateTime::getLastErrors();
	if (!$parsed || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) return '';
	return $parsed->format('Ymd');
}

function gigpress_ical_exclusive_date($date) {
	$parsed = DateTime::createFromFormat('!Y-m-d', (string) $date, new DateTimeZone('UTC'));
	$errors = DateTime::getLastErrors();
	if (!$parsed || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) return '';
	$parsed->modify('+1 day');
	return $parsed->format('Ymd');
}

function gigpress_ical() {
	global $wpdb, $gpo;
	$further_where = '';
	if (isset($_GET['show_id'])) $further_where .= $wpdb->prepare(' AND s.show_id = %d', absint($_GET['show_id']));
	if (isset($_GET['artist'])) $further_where .= $wpdb->prepare(' AND s.show_artist_id = %d', absint($_GET['artist']));
	if (isset($_GET['tour'])) $further_where .= $wpdb->prepare(' AND s.show_tour_id = %d', absint($_GET['tour']));
	if (isset($_GET['venue'])) $further_where .= $wpdb->prepare(' AND s.show_venue_id = %d', absint($_GET['venue']));
	$limit = (!empty($gpo['rss_limit'])) ? $gpo['rss_limit'] : 100;
	$shows = $wpdb->get_results(
		$wpdb->prepare("SELECT * FROM " . GIGPRESS_ARTISTS . " AS a, " . GIGPRESS_VENUES . " as v, " . GIGPRESS_SHOWS . " AS s LEFT JOIN " . GIGPRESS_TOURS . " AS t ON s.show_tour_id = t.tour_id WHERE show_status != 'deleted' AND s.show_artist_id = a.artist_id AND s.show_venue_id = v.venue_id" . $further_where . " AND s.show_expire >= '" . GIGPRESS_NOW . "' ORDER BY s.show_date ASC, s.show_expire ASC, s.show_time ASC LIMIT %d", $limit)
	);
	$shows = is_array($shows) ? $shows : array();
	$total = count($shows);
	$title = (string) ($gpo['rss_title'] ?? '');
	$filename = sanitize_title(get_bloginfo('name')) . '-icalendar';
	if ($total) {
		$first = $shows[0];
		$firstData = gigpress_prepare($first, 'ical');
		$firstPlain = $firstData['plain'] ?? array();
		if (isset($_GET['artist'])) {
			$filename = sanitize_title($firstPlain['artist'] ?? '') . '-icalendar';
			$title = (string) ($firstPlain['artist'] ?? '');
		} elseif (isset($_GET['tour'])) {
			$filename = sanitize_title($firstPlain['tour'] ?? '') . '-icalendar';
			$title = (string) ($firstPlain['tour'] ?? '');
		} elseif (isset($_GET['venue'])) {
			$filename = sanitize_title($firstPlain['venue'] ?? '') . '-icalendar';
			$title = (string) ($firstPlain['venue'] ?? '');
		} elseif (isset($_GET['show_id'])) {
			$filename = sanitize_title($firstPlain['artist'] ?? '') . '-' . $first->show_date;
			$title = (string) ($firstPlain['artist'] ?? '') . ' - ' . mysql2date($gpo['date_format'], $first->show_date);
		}
	}

	header('Content-Type: text/calendar');
	header('Content-Disposition: attachment; filename="' . $filename . '.ics"');
	echo "BEGIN:VCALENDAR\r\n";
	echo gigpress_ical_property('VERSION', '2.0', 'text');
	echo gigpress_ical_property('PRODID', 'GIGPRESS 2.0 WORDPRESS PLUGIN', 'text');
	echo gigpress_ical_property('CALSCALE', 'GREGORIAN', 'text');
	echo gigpress_ical_property('X-WR-TIMEZONE', 'Etc/GMT', 'text');
	echo gigpress_ical_property('METHOD', 'PUBLISH', 'text');
	if ($total > 1) echo gigpress_ical_property('X-WR-CALNAME', $title, 'text');

	foreach ($shows as $show) {
		$showdata = gigpress_prepare($show, 'ical');
		$plain = isset($showdata['plain']) && is_array($showdata['plain']) ? $showdata['plain'] : array();
		$startDate = gigpress_ical_date($show->show_date);
		$noTime = (string) $show->show_time === '00:00:01';
		$start = $noTime ? $startDate : gigpress_ical_utc_datetime($show->show_date, $show->show_time);
		$end = '';
		$dateProperty = $noTime;
		if ($noTime) {
			$end = gigpress_ical_exclusive_date($show->show_expire);
		} elseif ($show->show_expire !== $show->show_date) {
			$end = gigpress_ical_utc_datetime($show->show_expire, $show->show_time);
			if ($end !== '' && $end <= $start) $end = '';
		}

		$details = '';
		if (!empty($plain['tour'])) $details .= (string) $gpo['tour_label'] . ': ' . $plain['tour'] . '. ';
		if (!empty($plain['price'])) $details .= __('Price', 'gigpress') . ': ' . $plain['price'] . '. ';
		if (!empty($plain['ticket_phone'])) $details .= __('Box office', 'gigpress') . ': ' . $plain['ticket_phone'] . '. ';
		if (!empty($plain['venue_phone'])) $details .= __('Venue phone', 'gigpress') . ': ' . $plain['venue_phone'] . '. ';
		if (!empty($plain['notes'])) $details .= __('Notes', 'gigpress') . ': ' . $plain['notes'] . ' ';
		if (!empty($plain['admittance'])) $details .= $plain['admittance'];

		echo "BEGIN:VEVENT\r\n";
		echo gigpress_ical_property('SUMMARY', $plain['calendar_title'] ?? '', 'text');
		echo gigpress_ical_property('DESCRIPTION', $details, 'text');
		echo gigpress_ical_property('LOCATION', $plain['calendar_location'] ?? '', 'text');
		$uid = ($noTime ? $startDate : $start) . '-' . (int) $show->show_id . '-' . (string) get_bloginfo('admin_email');
		echo gigpress_ical_property('UID', $uid, 'text');
		echo gigpress_ical_property('URL', $showdata['permalink'] ?? get_bloginfo('url'), 'uri');
		if ($dateProperty) {
			echo gigpress_ical_property('DTSTART;VALUE=DATE', $start, 'date');
			if ($end !== '') echo gigpress_ical_property('DTEND;VALUE=DATE', $end, 'date');
		} else {
			echo gigpress_ical_property('DTSTART;VALUE=DATE-TIME', $start, 'date-time');
			if ($end !== '') echo gigpress_ical_property('DTEND;VALUE=DATE-TIME', $end, 'date-time');
		}
		echo gigpress_ical_property('DTSTAMP', gmdate('Ymd\THis\Z'), 'date-time');
		echo "END:VEVENT\r\n";
	}
	echo "END:VCALENDAR\r\n";
}

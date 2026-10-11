<?php
/* Shared CSV schema, row validation, persistence, and outcome contracts. */

function gigpress_csv_columns() {
	return array('Date', 'Time', 'End date', 'Artist', 'Artist URL', 'Venue', 'Address', 'City', 'State', 'Postal code', 'Country', 'Venue phone', 'Venue URL', 'Admittance', 'Price', 'Ticket URL', 'Ticket phone', 'External URL', 'Notes', 'Tour', 'Status', 'Related ID', 'Related URL');
}

function gigpress_csv_statuses() {
	return array('active', 'soldout', 'cancelled');
}

function gigpress_csv_scalar($row, $column) {
	if (!array_key_exists($column, $row) || !is_scalar($row[$column])) return null;
	return (string) $row[$column];
}

function gigpress_csv_prepare_record(array $row, int $csv_row, bool $include_related) {
	$values = array();
	foreach (gigpress_csv_columns() as $column) {
		$value = gigpress_csv_scalar($row, $column);
		if ($value === null) return array('group' => 'rejected', 'row' => $csv_row, 'field' => $column, 'reason' => __('This value must be text.', 'gigpress'));
		$values[$column] = $value;
	}
	if ($values['Date'] === '' || !preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $values['Date'], $date) || !checkdate((int) $date[2], (int) $date[3], (int) $date[1])) {
		return array('group' => 'rejected', 'row' => $csv_row, 'field' => 'Date', 'reason' => __('Use a valid YYYY-MM-DD date.', 'gigpress'));
	}
	$time = $values['Time'] === '' ? '00:00:01' : $values['Time'];
	if (!preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]\z/', $time)) return array('group' => 'rejected', 'row' => $csv_row, 'field' => 'Time', 'reason' => __('Use HH:MM:SS or leave Time blank.', 'gigpress'));
	$end = $values['End date'] === '' ? $values['Date'] : $values['End date'];
	if (!preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $end, $endDate) || !checkdate((int) $endDate[2], (int) $endDate[3], (int) $endDate[1]) || $end < $values['Date']) {
		return array('group' => 'rejected', 'row' => $csv_row, 'field' => 'End date', 'reason' => __('Use a valid date on or after Date.', 'gigpress'));
	}
	if ($values['Artist'] === '' || $values['Venue'] === '' || $values['City'] === '' || $values['Country'] === '') {
		foreach (array('Artist', 'Venue', 'City', 'Country') as $required) if ($values[$required] === '') return array('group' => 'rejected', 'row' => $csv_row, 'field' => $required, 'reason' => __('This field is required.', 'gigpress'));
	}
	$status = $values['Status'] === '' ? 'active' : $values['Status'];
	if (!in_array($status, gigpress_csv_statuses(), true)) return array('group' => 'rejected', 'row' => $csv_row, 'field' => 'Status', 'reason' => __('Use Active, Sold Out, or Cancelled.', 'gigpress'));
	$related = 0;
	if ($include_related && $values['Related ID'] !== '' && ctype_digit($values['Related ID']) && (int) $values['Related ID'] > 0) {
		$postId = (int) $values['Related ID'];
		if (current_user_can('edit_post', $postId) && get_post($postId)) $related = $postId;
	}
	return array('group' => 'prepared', 'row' => $csv_row, 'values' => $values, 'date' => $values['Date'], 'time' => $time,
		'end_date' => $end, 'multi' => $values['End date'] === '' ? 0 : 1, 'status' => $status, 'related' => $related);
}

function gigpress_csv_persist_record(array $prepared) {
	global $wpdb;
	if (($prepared['group'] ?? '') !== 'prepared' || !isset($prepared['values'])) return array('group' => 'failed', 'row' => $prepared['row'] ?? 0, 'reason' => __('The row could not be prepared.', 'gigpress'));
	$v = $prepared['values'];
	$artistId = (int) $wpdb->get_var($wpdb->prepare('SELECT artist_id FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s', $v['Artist']));
	$venueId = (int) $wpdb->get_var($wpdb->prepare('SELECT venue_id FROM ' . GIGPRESS_VENUES . ' WHERE venue_name = %s AND venue_city = %s AND venue_country = %s', $v['Venue'], $v['City'], $v['Country']));
	$tourId = 0;
	if ($v['Tour'] !== '') $tourId = (int) $wpdb->get_var($wpdb->prepare('SELECT tour_id FROM ' . GIGPRESS_TOURS . " WHERE tour_name = %s AND tour_status = 'active'", $v['Tour']));
	if ($artistId && $venueId) {
		$duplicate = (int) $wpdb->get_var($wpdb->prepare("SELECT show_id FROM " . GIGPRESS_SHOWS . " WHERE show_artist_id = %d AND show_date = %s AND show_time = %s AND show_venue_id = %d AND show_status != 'deleted' LIMIT 1", $artistId, $prepared['date'], $prepared['time'], $venueId));
		if ($duplicate) return array('group' => 'duplicate', 'row' => $prepared['row'], 'show_id' => $duplicate, 'identity' => $v['Artist'] . ' — ' . $v['Date'] . ' — ' . $v['Venue']);
	}
	if (!$artistId) {
		$alpha = preg_replace('/^the /i', '', strtolower($v['Artist']));
		if ($wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => $v['Artist'], 'artist_alpha' => $alpha, 'artist_url' => $v['Artist URL']), array('%s', '%s', '%s')) === false) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Artist could not be saved.', 'gigpress'));
		$artistId = (int) $wpdb->insert_id;
	}
	if (!$tourId && $v['Tour'] !== '') {
		if ($wpdb->insert(GIGPRESS_TOURS, array('tour_name' => $v['Tour'], 'tour_status' => 'active'), array('%s', '%s')) === false) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Tour could not be saved.', 'gigpress'));
		$tourId = (int) $wpdb->insert_id;
	}
	if (!$venueId) {
		$venue = array('venue_name' => $v['Venue'], 'venue_address' => $v['Address'], 'venue_city' => $v['City'], 'venue_state' => $v['State'], 'venue_postal_code' => $v['Postal code'], 'venue_country' => $v['Country'], 'venue_phone' => $v['Venue phone'], 'venue_url' => $v['Venue URL']);
		if ($wpdb->insert(GIGPRESS_VENUES, $venue, array_fill(0, count($venue), '%s')) === false) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Venue could not be saved.', 'gigpress'));
		$venueId = (int) $wpdb->insert_id;
	}
	$show = array('show_date' => $prepared['date'], 'show_time' => $prepared['time'], 'show_multi' => $prepared['multi'], 'show_expire' => $prepared['end_date'],
		'show_artist_id' => $artistId, 'show_venue_id' => $venueId, 'show_tour_id' => $tourId,
		'show_ages' => $v['Admittance'], 'show_price' => $v['Price'], 'show_tix_url' => $v['Ticket URL'], 'show_tix_phone' => $v['Ticket phone'],
		'show_external_url' => $v['External URL'], 'show_notes' => $v['Notes'], 'show_status' => $prepared['status'], 'show_related' => (string) $prepared['related']);
	$formats = array('%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
	if ($wpdb->insert(GIGPRESS_SHOWS, $show, $formats) === false) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Show could not be saved.', 'gigpress'));
	$showId = (int) $wpdb->insert_id;
	$stored = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $showId), ARRAY_A);
	$expected = array('show_date' => $prepared['date'], 'show_time' => $prepared['time'], 'show_multi' => $prepared['multi'], 'show_expire' => $prepared['end_date'], 'show_artist_id' => $artistId, 'show_venue_id' => $venueId, 'show_tour_id' => $tourId, 'show_status' => $prepared['status'], 'show_related' => (string) $prepared['related']);
	if (!is_array($stored)) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Saved show could not be verified.', 'gigpress'));
	foreach ($expected as $column => $value) {
		$actual = (string) $stored[$column];
		if ($column === 'show_multi' || strpos($column, '_id') !== false || $column === 'show_related') {
			if ((int) $actual !== (int) $value) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Saved show relationships could not be verified.', 'gigpress'));
		} elseif ($actual !== (string) $value) return array('group' => 'failed', 'row' => $prepared['row'], 'reason' => __('Saved show values could not be verified.', 'gigpress'));
	}
	return array('group' => 'imported', 'row' => $prepared['row'], 'show_id' => $showId, 'artist_id' => $artistId, 'venue_id' => $venueId, 'tour_id' => $tourId, 'identity' => $v['Artist'] . ' — ' . $v['Date'] . ' — ' . $v['Venue']);
}

function gigpress_csv_import_result(array $records, ?array $file_error = null) {
	$groups = array('imported' => array(), 'duplicate' => array(), 'rejected' => array(), 'failed' => array());
	foreach ($records as $record) {
		$group = is_array($record) ? ($record['group'] ?? '') : '';
		if (!isset($groups[$group])) $group = 'failed';
		$groups[$group][] = $record;
	}
	return array('counts' => array('imported' => count($groups['imported']), 'duplicate' => count($groups['duplicate']), 'rejected' => count($groups['rejected']), 'failed' => count($groups['failed'])),
		'records' => $groups, 'file_error' => $file_error);
}

function gigpress_csv_import_request() {
	global $gpo;
	if (!current_user_can($gpo['user_level'] ?? 'edit_posts') || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['gpaction'] ?? '') !== 'import') {
		echo '<div class="error"><p>' . esc_html(__('You are not authorized to import shows.', 'gigpress')) . '</p></div>';
		return false;
	}
	$nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) wp_unslash($_POST['_wpnonce']) : '';
	if (!$nonce || !wp_verify_nonce($nonce, 'gigpress-action')) {
		echo '<div class="error"><p>' . esc_html(__('The import request expired. Reload the page and try again.', 'gigpress')) . '</p></div>';
		return false;
	}
	if (!gigpress_require_database_ready()) return false;
	$file = $_FILES['gp_import'] ?? null;
	if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['name']) || (int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
		$result = gigpress_csv_import_result(array(), array('reason' => __('Choose a readable CSV file and try again.', 'gigpress')));
	} else {
		$contents = file_get_contents($file['tmp_name']);
		if (!is_string($contents)) {
			$result = gigpress_csv_import_result(array(), array('reason' => __('The uploaded file could not be read.', 'gigpress')));
		} else {
			$upload = wp_upload_bits(sanitize_file_name($file['name']), null, $contents);
			if (!empty($upload['error'])) {
				$result = gigpress_csv_import_result(array(), array('reason' => (string) $upload['error']));
			} else {
				require_once WP_PLUGIN_DIR . '/gigpress/lib/parsecsv.lib.php';
				$csv = new parseCSV();
				$csv->parse($upload['file']);
				@unlink($upload['file']);
				$headers = is_array($csv->titles) ? array_values($csv->titles) : array();
				$firstHeader = $headers[0] ?? null;
				if (is_string($firstHeader)) {
					$headers[0] = preg_replace('/\A\xEF\xBB\xBF/', '', $firstHeader);
					if ($headers[0] !== $firstHeader) {
						foreach ($csv->data as &$record) {
							if (is_array($record) && array_key_exists($firstHeader, $record)) {
								$record[$headers[0]] = $record[$firstHeader];
								unset($record[$firstHeader]);
							}
						}
						unset($record);
					}
				}
				if ($headers !== gigpress_csv_columns() || !is_array($csv->data) || !$csv->data) {
					$result = gigpress_csv_import_result(array(), array('reason' => __('The CSV must contain the established GigPress column headers and at least one show.', 'gigpress')));
				} else {
					$records = array();
					$includeRelated = isset($_POST['include_related']) && is_scalar($_POST['include_related']) && wp_unslash((string) $_POST['include_related']) === 'y';
					foreach ($csv->data as $index => $row) {
						$prepared = gigpress_csv_prepare_record((array) $row, (int) $index + 2, $includeRelated);
						$records[] = ($prepared['group'] ?? '') === 'prepared' ? gigpress_csv_persist_record($prepared) : $prepared;
					}
					$result = gigpress_csv_import_result($records);
				}
			}
		}
	}
	$labels = array('imported' => __('imported', 'gigpress'), 'duplicate' => __('duplicates', 'gigpress'), 'rejected' => __('rejected', 'gigpress'), 'failed' => __('failed', 'gigpress'));
	echo '<section class="gigpress-csv-results"><h4>' . esc_html(__('Import results', 'gigpress')) . '</h4><ul>';
	foreach ($result['counts'] as $group => $count) echo '<li>' . esc_html((string) $count . ' ' . $labels[$group]) . '</li>';
	echo '</ul>';
	if ($result['file_error']) echo '<p class="error">' . esc_html($result['file_error']['reason'] ?? __('The file could not be imported.', 'gigpress')) . '</p>';
	foreach ($result['records'] as $group => $items) {
		foreach ($items as $item) {
			$identity = (string) ($item['identity'] ?? '');
			$detail = sprintf(__('Row %1$d: %2$s', 'gigpress'), (int) ($item['row'] ?? 0), $identity);
			if (!empty($item['field'])) $detail .= ' — ' . (string) $item['field'];
			if (!empty($item['reason'])) $detail .= ' — ' . (string) $item['reason'];
			echo '<p>' . esc_html($detail) . '</p>';
		}
	}
	echo '</section>';
	return $result;
}

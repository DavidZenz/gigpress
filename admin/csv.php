<?php
/* Shared CSV schema, strict import pipeline, persistence, and outcomes. */

function gigpress_csv_columns() {
	return array('Date', 'Time', 'End date', 'Artist', 'Artist URL', 'Venue', 'Address', 'City', 'State', 'Postal code', 'Country', 'Venue phone', 'Venue URL', 'Admittance', 'Price', 'Ticket URL', 'Ticket phone', 'External URL', 'Notes', 'Tour', 'Status', 'Related ID', 'Related URL');
}

function gigpress_csv_statuses() { return array('active', 'soldout', 'cancelled'); }

function gigpress_csv_scalar($row, $column) {
	if (!array_key_exists($column, $row) || !is_scalar($row[$column])) return null;
	return (string) $row[$column];
}

function gigpress_csv_identity(array $values) {
	return implode(' — ', array_filter(array($values['Artist'] ?? '', $values['Date'] ?? '', $values['Venue'] ?? ''), function ($part) { return $part !== ''; }));
}

function gigpress_csv_detail($group, $row, $identity, $field, $reason, $next_step) {
	return array('group' => $group, 'row' => (int) $row, 'identity' => (string) $identity, 'field' => (string) $field,
		'reason' => (string) $reason, 'next_step' => (string) $next_step);
}

function gigpress_csv_validate_headers(array $headers) {
	if (isset($headers[0]) && is_string($headers[0])) $headers[0] = preg_replace('/\A\xEF\xBB\xBF/', '', $headers[0]);
	$valid = array_values($headers) === gigpress_csv_columns();
	return array('valid' => $valid, 'headers' => array_values($headers),
		'error' => $valid ? null : __('The CSV headers must match the 23 GigPress columns in their established order.', 'gigpress'),
		'next_step' => $valid ? null : __('Export a fresh GigPress CSV template and keep its headers unchanged.', 'gigpress'));
}

/* RFC 4180 style parsing with exact cell preservation, including quoted newlines. */
function gigpress_csv_decode(string $contents) {
	if ($contents === '') return array('error' => __('The CSV file is empty.', 'gigpress'), 'next_step' => __('Choose a nonempty UTF-8 CSV file.', 'gigpress'));
	if (strncmp($contents, "\xFF\xFE", 2) === 0 || strncmp($contents, "\xFE\xFF", 2) === 0 || strpos($contents, "\0") !== false || preg_match('//u', $contents) !== 1) {
		return array('error' => __('The CSV must use valid UTF-8 text.', 'gigpress'), 'next_step' => __('Re-export the file as UTF-8 CSV. Do not convert or normalize its values.', 'gigpress'));
	}
	$bom = "\xEF\xBB\xBF";
	$firstBom = strncmp($contents, $bom, 3) === 0 ? 3 : 0;
	if (strpos(substr($contents, $firstBom), $bom) !== false) return array('error' => __('A UTF-8 byte order mark is only allowed at the start of the header.', 'gigpress'), 'next_step' => __('Remove any later byte order marks and try again.', 'gigpress'));
	if ($firstBom) $contents = substr($contents, 3);
	$rows = array(); $row = array(); $field = ''; $quoted = false; $closed = false; $length = strlen($contents);
	for ($i = 0; $i < $length; $i++) {
		$ch = $contents[$i];
		if ($quoted) {
			if ($ch === '"') {
				if ($i + 1 < $length && $contents[$i + 1] === '"') { $field .= '"'; $i++; }
				else { $quoted = false; $closed = true; }
			} else $field .= $ch;
			continue;
		}
		if ($closed && $ch !== ',' && $ch !== "\r" && $ch !== "\n") return array('error' => __('The CSV has malformed quoted fields.', 'gigpress'), 'next_step' => __('Correct the quoting and export the file as comma-delimited UTF-8 CSV.', 'gigpress'));
		if ($ch === '"' && $field === '' && !$closed) { $quoted = true; continue; }
		if ($ch === '"') return array('error' => __('The CSV has malformed quoted fields.', 'gigpress'), 'next_step' => __('Correct the quoting and export the file as comma-delimited UTF-8 CSV.', 'gigpress'));
		if ($ch === ',') { $row[] = $field; $field = ''; $closed = false; continue; }
		if ($ch === "\r" || $ch === "\n") {
			$row[] = $field; $rows[] = $row; $row = array(); $field = ''; $closed = false;
			if ($ch === "\r" && $i + 1 < $length && $contents[$i + 1] === "\n") $i++;
			continue;
		}
		$field .= $ch;
	}
	if ($quoted) return array('error' => __('The CSV contains an unfinished quoted field.', 'gigpress'), 'next_step' => __('Close the quoted field and try again.', 'gigpress'));
	if ($field !== '' || $row || ($length > 0 && substr($contents, -1) !== "\n" && substr($contents, -1) !== "\r")) { $row[] = $field; $rows[] = $row; }
	if (!$rows || count($rows) < 2) return array('error' => __('The CSV must contain a header and at least one show.', 'gigpress'), 'next_step' => __('Add a show row beneath the GigPress header and try again.', 'gigpress'));
	$headers = array_shift($rows);
	$headerResult = gigpress_csv_validate_headers($headers);
	if (!$headerResult['valid']) return array('error' => $headerResult['error'], 'next_step' => $headerResult['next_step']);
	$records = array();
	foreach ($rows as $index => $cells) {
		if (count($cells) !== count($headers)) {
			$records[] = gigpress_csv_detail('rejected', $index + 2, '', '', __('This record has a different number of cells than the header.', 'gigpress'), __('Add or remove cells so this row has exactly 23 values.', 'gigpress'));
			continue;
		}
		$records[] = array_combine($headers, $cells);
	}
	return array('headers' => $headers, 'records' => $records, 'error' => null);
}

function gigpress_csv_parse_upload(string $path) {
	if ($path === '' || !is_file($path) || !is_readable($path)) return array('error' => __('The uploaded file could not be read.', 'gigpress'), 'next_step' => __('Choose a readable CSV file and try again.'));
	$contents = file_get_contents($path);
	if (!is_string($contents)) return array('error' => __('The uploaded file could not be read.', 'gigpress'), 'next_step' => __('Choose a readable CSV file and try again.'));
	return gigpress_csv_decode($contents);
}

function gigpress_csv_validate_record(array $row, int $csv_row, bool $include_related) {
	$values = array();
	foreach (gigpress_csv_columns() as $column) {
		$value = gigpress_csv_scalar($row, $column);
		if ($value === null) return gigpress_csv_detail('rejected', $csv_row, gigpress_csv_identity($values), $column,
			__('This value must be text.', 'gigpress'), __('Keep one text value in every CSV column.', 'gigpress'));
		$values[$column] = $value;
	}
	$identity = gigpress_csv_identity($values);
	$dateOk = preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $values['Date'], $date) && checkdate((int) $date[2], (int) $date[3], (int) $date[1]);
	if (!$dateOk) return gigpress_csv_detail('rejected', $csv_row, $identity, 'Date', __('Use a valid YYYY-MM-DD date.', 'gigpress'), __('Correct Date to a real calendar date in YYYY-MM-DD format.', 'gigpress'));
	$time = $values['Time'] === '' ? '00:00:01' : $values['Time'];
	if (!preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]\z/', $time)) return gigpress_csv_detail('rejected', $csv_row, $identity, 'Time', __('Use HH:MM:SS or leave Time blank.', 'gigpress'), __('Enter a 24-hour time with seconds, or leave Time blank.', 'gigpress'));
	$end = $values['End date'] === '' ? $values['Date'] : $values['End date'];
	$endOk = preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $end, $endDate) && checkdate((int) $endDate[2], (int) $endDate[3], (int) $endDate[1]) && $end >= $values['Date'];
	if (!$endOk) return gigpress_csv_detail('rejected', $csv_row, $identity, 'End date', __('Use a valid date on or after Date.', 'gigpress'), __('Correct End date or leave it blank for a single-day show.', 'gigpress'));
	foreach (array('Artist', 'Venue', 'City', 'Country') as $required) if ($values[$required] === '') return gigpress_csv_detail('rejected', $csv_row, $identity, $required, __('This field is required.', 'gigpress'), __('Fill in this required show identity field.', 'gigpress'));
	$status = $values['Status'] === '' ? 'active' : strtolower($values['Status']);
	if (!in_array($status, gigpress_csv_statuses(), true)) {
		$reason = $status === '0' ? __('This legacy status needs manual correction to Active, Sold Out, or Cancelled before import.', 'gigpress') : __('Use Active, Sold Out, or Cancelled.', 'gigpress');
		return gigpress_csv_detail('rejected', $csv_row, $identity, 'Status', $reason, __('Correct Status to Active, Sold Out, or Cancelled.', 'gigpress'));
	}
	$related = 0;
	if ($include_related && $values['Related ID'] !== '') {
		if (!ctype_digit($values['Related ID']) || (int) $values['Related ID'] < 1) return gigpress_csv_detail('rejected', $csv_row, $identity, 'Related ID', __('Related ID must identify a post you can edit.', 'gigpress'), __('Clear Related ID or enter an editable WordPress post ID.', 'gigpress'));
		$postId = (int) $values['Related ID'];
		if (!current_user_can('edit_post', $postId) || !get_post($postId)) return gigpress_csv_detail('rejected', $csv_row, $identity, 'Related ID', __('Related ID must identify a post you can edit.', 'gigpress'), __('Choose a WordPress post your account can edit, or clear Related ID.', 'gigpress'));
		$related = $postId;
	}
	return array('group' => 'prepared', 'row' => $csv_row, 'identity' => $identity, 'values' => $values, 'date' => $values['Date'], 'time' => $time,
		'end_date' => $end, 'multi' => $values['End date'] === '' ? 0 : 1, 'status' => $status, 'related' => $related);
}

function gigpress_csv_query_value($sql, $field) {
	global $wpdb;
	$wpdb->last_error = '';
	$value = $wpdb->get_var($sql);
	if ($wpdb->last_error !== '') return array('ok' => false, 'value' => null);
	return array('ok' => true, 'value' => $value);
}

function gigpress_csv_verified_insert($table, array $values, array $formats, $id_column, array $expected, $failure_reason, $row, $identity) {
	global $wpdb;
	$wpdb->last_error = '';
	$result = $wpdb->insert($table, $values, $formats);
	if ($result !== 1 || (int) $wpdb->insert_id < 1 || $wpdb->last_error !== '') return array('ok' => false, 'detail' => gigpress_csv_detail('failed', $row, $identity, '', $failure_reason, __('Correct the cause and retry this import; completed related records will be reused.', 'gigpress')));
	$id = (int) $wpdb->insert_id;
	$wpdb->last_error = '';
	$stored = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE {$id_column} = %d", $id), ARRAY_A);
	if ($wpdb->last_error !== '' || !is_array($stored)) return array('ok' => false, 'detail' => gigpress_csv_detail('failed', $row, $identity, '', __('A saved record could not be verified.', 'gigpress'), __('Retry the import; already saved records will be checked and reused.', 'gigpress')));
	foreach ($expected as $key => $value) if (!array_key_exists($key, $stored) || (string) $stored[$key] !== (string) $value) return array('ok' => false, 'detail' => gigpress_csv_detail('failed', $row, $identity, '', __('A saved record did not match the imported values.', 'gigpress'), __('Retry the import after checking the database; existing records will not be overwritten.', 'gigpress')));
	return array('ok' => true, 'id' => $id);
}

function gigpress_csv_persist_record(array $prepared) {
	global $wpdb;
	if (($prepared['group'] ?? '') !== 'prepared' || !isset($prepared['values'])) return gigpress_csv_detail('failed', $prepared['row'] ?? 0, $prepared['identity'] ?? '', '', __('The row could not be prepared.', 'gigpress'), __('Correct the row and retry the import.', 'gigpress'));
	$v = $prepared['values']; $row = (int) $prepared['row']; $identity = (string) ($prepared['identity'] ?? gigpress_csv_identity($v));
	$artistQ = gigpress_csv_query_value($wpdb->prepare('SELECT artist_id FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_name = %s LIMIT 1', $v['Artist']), 'artist_id');
	$venueQ = gigpress_csv_query_value($wpdb->prepare('SELECT venue_id FROM ' . GIGPRESS_VENUES . ' WHERE venue_name = %s AND venue_city = %s AND venue_country = %s LIMIT 1', $v['Venue'], $v['City'], $v['Country']), 'venue_id');
	if (!$artistQ['ok'] || !$venueQ['ok']) return gigpress_csv_detail('failed', $row, $identity, '', __('A related record could not be checked.', 'gigpress'), __('Retry the import; no matching show was changed.', 'gigpress'));
	$artistId = (int) $artistQ['value']; $venueId = (int) $venueQ['value']; $tourId = 0;
	if ($v['Tour'] !== '') {
		$tourQ = gigpress_csv_query_value($wpdb->prepare("SELECT tour_id FROM " . GIGPRESS_TOURS . " WHERE tour_name = %s AND tour_status = 'active' LIMIT 1", $v['Tour']), 'tour_id');
		if (!$tourQ['ok']) return gigpress_csv_detail('failed', $row, $identity, '', __('The tour could not be checked.', 'gigpress'), __('Retry the import; completed related records will be reused.', 'gigpress'));
		$tourId = (int) $tourQ['value'];
	}
	if ($artistId && $venueId) {
		$duplicateQ = gigpress_csv_query_value($wpdb->prepare("SELECT show_id FROM " . GIGPRESS_SHOWS . " WHERE show_artist_id = %d AND show_date = %s AND show_time = %s AND show_venue_id = %d AND show_status != 'deleted' LIMIT 1", $artistId, $prepared['date'], $prepared['time'], $venueId), 'show_id');
		if (!$duplicateQ['ok']) return gigpress_csv_detail('failed', $row, $identity, '', __('The matching show could not be checked.', 'gigpress'), __('Retry the import after the database issue is resolved.', 'gigpress'));
		if ((int) $duplicateQ['value'] > 0) return array_merge(gigpress_csv_detail('duplicate', $row, $identity, '', __('A matching show already exists and was left unchanged.', 'gigpress'), __('Review the existing show or change its date, time, artist, or venue before retrying.', 'gigpress')), array('show_id' => (int) $duplicateQ['value']));
	}
	if (!$artistId) {
		$alpha = preg_replace('/^the /i', '', strtolower($v['Artist']));
		$created = gigpress_csv_verified_insert(GIGPRESS_ARTISTS, array('artist_name' => $v['Artist'], 'artist_alpha' => $alpha, 'artist_url' => $v['Artist URL']), array('%s', '%s', '%s'), 'artist_id', array('artist_name' => $v['Artist'], 'artist_alpha' => $alpha, 'artist_url' => $v['Artist URL']), __('Artist could not be saved.', 'gigpress'), $row, $identity);
		if (!$created['ok']) return $created['detail']; $artistId = $created['id'];
	}
	if ($v['Tour'] !== '' && !$tourId) {
		$created = gigpress_csv_verified_insert(GIGPRESS_TOURS, array('tour_name' => $v['Tour'], 'tour_status' => 'active'), array('%s', '%s'), 'tour_id', array('tour_name' => $v['Tour'], 'tour_status' => 'active'), __('Tour could not be saved.', 'gigpress'), $row, $identity);
		if (!$created['ok']) return $created['detail']; $tourId = $created['id'];
	}
	if (!$venueId) {
		$venue = array('venue_name' => $v['Venue'], 'venue_address' => $v['Address'], 'venue_city' => $v['City'], 'venue_state' => $v['State'], 'venue_postal_code' => $v['Postal code'], 'venue_country' => $v['Country'], 'venue_phone' => $v['Venue phone'], 'venue_url' => $v['Venue URL']);
		$expectedVenue = $venue;
		$created = gigpress_csv_verified_insert(GIGPRESS_VENUES, $venue, array_fill(0, count($venue), '%s'), 'venue_id', $expectedVenue, __('Venue could not be saved.', 'gigpress'), $row, $identity);
		if (!$created['ok']) return $created['detail']; $venueId = $created['id'];
	}
	$show = array('show_date' => $prepared['date'], 'show_time' => $prepared['time'], 'show_multi' => $prepared['multi'], 'show_expire' => $prepared['end_date'],
		'show_artist_id' => $artistId, 'show_venue_id' => $venueId, 'show_tour_id' => $tourId, 'show_ages' => $v['Admittance'], 'show_price' => $v['Price'],
		'show_tix_url' => $v['Ticket URL'], 'show_tix_phone' => $v['Ticket phone'], 'show_external_url' => $v['External URL'], 'show_notes' => $v['Notes'],
		'show_status' => $prepared['status'], 'show_related' => (string) $prepared['related']);
	$formats = array(); foreach ($show as $field => $value) $formats[] = in_array($field, array('show_multi', 'show_artist_id', 'show_venue_id', 'show_tour_id', 'show_related'), true) ? '%d' : '%s';
	$created = gigpress_csv_verified_insert(GIGPRESS_SHOWS, $show, $formats, 'show_id', $show, __('Show could not be saved.', 'gigpress'), $row, $identity);
	if (!$created['ok']) return $created['detail'];
	return array_merge(gigpress_csv_detail('imported', $row, $identity, '', __('Show imported and verified.', 'gigpress'), __('Review the imported show in GigPress.', 'gigpress')),
		array('show_id' => $created['id'], 'artist_id' => $artistId, 'venue_id' => $venueId, 'tour_id' => $tourId));
}

function gigpress_csv_import_records(array $records, bool $include_related = false) {
	$validated = array();
	foreach ($records as $index => $record) {
		$number = $index + 2;
		if (isset($record['group']) && $record['group'] === 'rejected') { $validated[] = $record; continue; }
		$validated[] = gigpress_csv_validate_record((array) $record, $number, $include_related);
	}
	$outcomes = array();
	foreach ($validated as $record) $outcomes[] = ($record['group'] ?? '') === 'prepared' ? gigpress_csv_persist_record($record) : $record;
	return gigpress_csv_import_result($outcomes);
}

function gigpress_csv_import_result(array $records, ?array $file_error = null) {
	$groups = array('imported' => array(), 'duplicate' => array(), 'rejected' => array(), 'failed' => array());
	foreach ($records as $record) {
		$group = is_array($record) ? ($record['group'] ?? '') : '';
		if (!isset($groups[$group])) $group = 'failed';
		$groups[$group][] = $record;
	}
	return array('counts' => array('imported' => count($groups['imported']), 'duplicate' => count($groups['duplicate']), 'rejected' => count($groups['rejected']), 'failed' => count($groups['failed'])),
		'total' => count($records), 'records' => $groups, 'file_error' => $file_error);
}

function gigpress_csv_validate_upload(array $file, array $request) {
	if (($request['method'] ?? '') !== 'POST' || ($request['action'] ?? '') !== 'import') return array('valid' => false, 'reason' => __('The import request was not valid.', 'gigpress'), 'next_step' => __('Reload the import page and submit the form again.', 'gigpress'));
	if (count($_FILES) !== 1 || !array_key_exists('gp_import', $_FILES) || !is_array($file)) return array('valid' => false, 'reason' => __('Choose one CSV file to import.', 'gigpress'), 'next_step' => __('Select a single .csv file and try again.', 'gigpress'));
	foreach (array('name', 'tmp_name', 'error', 'size') as $key) if (!isset($file[$key]) || !is_scalar($file[$key])) return array('valid' => false, 'reason' => __('The uploaded file details are incomplete.', 'gigpress'), 'next_step' => __('Choose the CSV file again and retry.', 'gigpress'));
	$name = (string) $file['name']; $path = (string) $file['tmp_name']; $error = (int) $file['error'];
	$owned = $path !== '' && is_uploaded_file($path);
	if ($error !== UPLOAD_ERR_OK) return array('valid' => false, 'owned' => $owned, 'path' => $owned ? $path : '', 'reason' => __('The file upload did not complete.', 'gigpress'), 'next_step' => __('Choose the CSV file again and check the upload size limit.', 'gigpress'));
	if (!$owned) return array('valid' => false, 'reason' => __('The file was not received through an authenticated upload.', 'gigpress'), 'next_step' => __('Submit the file through the GigPress import form.', 'gigpress'));
	if (!preg_match('/\.csv\z/i', $name)) return array('valid' => false, 'owned' => true, 'path' => $path, 'reason' => __('Choose a file with a .csv extension.', 'gigpress'), 'next_step' => __('Export the file as comma-delimited UTF-8 CSV and try again.', 'gigpress'));
	$limit = (int) wp_max_upload_size(); $actual = filesize($path); $reported = (int) $file['size'];
	if ($limit < 1 || $actual === false || $reported < 1 || $actual < 1 || $actual > $limit || $reported > $limit) return array('valid' => false, 'owned' => true, 'path' => $path,
		'reason' => $actual === 0 ? __('The CSV file is empty.', 'gigpress') : sprintf(__('The file exceeds the current upload limit of %s.', 'gigpress'), size_format($limit)),
		'next_step' => sprintf(__('Choose a nonempty CSV file no larger than %s.', 'gigpress'), size_format($limit)), 'limit' => $limit);
	if (!is_readable($path)) return array('valid' => false, 'owned' => true, 'path' => $path, 'reason' => __('The uploaded file could not be read.', 'gigpress'), 'next_step' => __('Choose a readable CSV file and retry.'));
	return array('valid' => true, 'owned' => true, 'path' => $path, 'name' => $name, 'limit' => $limit);
}

function gigpress_csv_import_request() {
	global $gpo;
	if (!current_user_can($gpo['user_level'] ?? 'edit_posts') || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['gpaction']) || !is_string($_POST['gpaction']) || wp_unslash($_POST['gpaction']) !== 'import') {
		echo '<div class="error"><p>' . esc_html(__('You are not authorized to import shows.', 'gigpress')) . '</p></div>';
		return false;
	}
	$nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) wp_unslash($_POST['_wpnonce']) : '';
	if (!$nonce || !wp_verify_nonce($nonce, 'gigpress-action')) { echo '<div class="error"><p>' . esc_html(__('The import request expired. Reload the page and try again.', 'gigpress')) . '</p></div>'; return false; }
	if (!gigpress_require_database_ready()) return false;
	$file = $_FILES['gp_import'] ?? array();
	$upload = gigpress_csv_validate_upload((array) $file, array('method' => $_SERVER['REQUEST_METHOD'] ?? '', 'action' => wp_unslash($_POST['gpaction'])));
	$result = null; $cleanupFailed = false;
	try {
		if (!$upload['valid']) $result = gigpress_csv_import_result(array(), array('reason' => $upload['reason'], 'next_step' => $upload['next_step'], 'name' => isset($file['name']) && is_scalar($file['name']) ? (string) $file['name'] : ''));
		else {
			$parsed = gigpress_csv_parse_upload($upload['path']);
			if (!empty($parsed['error'])) $result = gigpress_csv_import_result(array(), array('reason' => $parsed['error'], 'next_step' => $parsed['next_step'] ?? '', 'name' => $upload['name']));
			else {
				$includeRelated = isset($_POST['include_related']) && is_scalar($_POST['include_related']) && wp_unslash((string) $_POST['include_related']) === 'y';
				$result = gigpress_csv_import_records($parsed['records'], $includeRelated);
			}
		}
	} finally {
		if (!empty($upload['owned']) && !empty($upload['path']) && is_file($upload['path']) && !@unlink($upload['path'])) $cleanupFailed = true;
	}
	if ($cleanupFailed) { $result['file_error'] = array('reason' => __('The temporary upload could not be removed after processing.', 'gigpress'), 'next_step' => __('Ask the site administrator to remove the temporary upload safely.', 'gigpress')); }
	$labels = array('imported' => __('imported', 'gigpress'), 'duplicate' => __('duplicates', 'gigpress'), 'rejected' => __('rejected', 'gigpress'), 'failed' => __('failed', 'gigpress'));
	echo '<section class="gigpress-csv-results"><h4>' . esc_html(__('Import results', 'gigpress')) . '</h4><ul>';
	foreach ($result['counts'] as $group => $count) echo '<li>' . esc_html((string) $count . ' ' . $labels[$group]) . '</li>';
	echo '</ul>';
	if ($result['file_error']) {
		$name = isset($result['file_error']['name']) ? ' ' . sprintf(__('File: %s.', 'gigpress'), $result['file_error']['name']) : '';
		echo '<p class="error">' . esc_html(($result['file_error']['reason'] ?? __('The file could not be imported.', 'gigpress')) . $name) . '</p>';
		if (!empty($result['file_error']['next_step'])) echo '<p>' . esc_html($result['file_error']['next_step']) . '</p>';
	}
	foreach ($result['records'] as $items) foreach ($items as $item) {
		$detail = sprintf(__('Row %1$d: %2$s', 'gigpress'), (int) ($item['row'] ?? 0), (string) ($item['identity'] ?? ''));
		if (!empty($item['field'])) $detail .= ' — ' . (string) $item['field'];
		if (!empty($item['reason'])) $detail .= ' — ' . (string) $item['reason'];
		echo '<p>' . esc_html($detail) . '</p>';
		if (!empty($item['next_step'])) echo '<p>' . esc_html((string) $item['next_step']) . '</p>';
	}
	echo '</section>';
	return $result;
}

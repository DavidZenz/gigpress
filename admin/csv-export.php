<?php
/* Shared, request-scoped export selection and exact UTF-8 CSV response. */

function gigpress_csv_export_state(array $request): array {
	$read = function ($key) use ($request) {
		if (!isset($request[$key]) || !is_scalar($request[$key])) return '';
		$value = (string) $request[$key];
		return function_exists('wp_unslash') ? wp_unslash($value) : $value;
	};
	if ($read('reset_filters') === '1') return array('artist_id' => -1, 'tour_id' => -1, 'scope' => '-1');
	$normalizeId = function ($value) {
		if ($value === '-1') return -1;
		if (!preg_match('/\A[1-9][0-9]*\z/', $value) || strlen($value) > 18) return -1;
		return (int) $value;
	};
	$scope = $read('scope');
	if (!in_array($scope, array('-1', 'upcoming', 'past'), true)) $scope = '-1';
	return array('artist_id' => $normalizeId($read('artist_id')), 'tour_id' => $normalizeId($read('tour_id')), 'scope' => $scope);
}

function gigpress_csv_export_predicate(array $state): array {
	global $wpdb;
	$state = gigpress_csv_export_state($state);
	$where = array("s.show_status <> 'deleted'", 's.show_artist_id = a.artist_id', 's.show_venue_id = v.venue_id');
	$params = array();
	if ($state['scope'] === 'upcoming') { $where[] = 's.show_expire >= %s'; $params[] = GIGPRESS_NOW; }
	if ($state['scope'] === 'past') { $where[] = 's.show_expire < %s'; $params[] = GIGPRESS_NOW; }
	if ($state['artist_id'] !== -1) { $where[] = 's.show_artist_id = %d'; $params[] = $state['artist_id']; }
	if ($state['tour_id'] !== -1) { $where[] = 's.show_tour_id = %d'; $params[] = $state['tour_id']; }
	$from = ' FROM ' . GIGPRESS_SHOWS . ' AS s JOIN ' . GIGPRESS_ARTISTS . ' AS a ON s.show_artist_id = a.artist_id JOIN ' . GIGPRESS_VENUES . ' AS v ON s.show_venue_id = v.venue_id LEFT JOIN ' . GIGPRESS_TOURS . ' AS t ON s.show_tour_id = t.tour_id';
	$sqlWhere = ' WHERE ' . implode(' AND ', $where);
	return array('state' => $state, 'from' => $from, 'where' => $sqlWhere, 'params' => $params);
}

function gigpress_csv_export_selection(array $state): array {
	global $wpdb;
	$query = gigpress_csv_export_predicate($state);
	$countSql = 'SELECT COUNT(*)' . $query['from'] . $query['where'];
	$rowsSql = "SELECT s.show_date AS `Date`, s.show_time AS `Time`, s.show_expire AS `End date`, a.artist_name AS `Artist`, a.artist_url AS `Artist URL`, v.venue_name AS `Venue`, v.venue_address AS `Address`, v.venue_city AS `City`, v.venue_state AS `State`, v.venue_postal_code AS `Postal code`, v.venue_country AS `Country`, v.venue_phone AS `Venue phone`, v.venue_url AS `Venue URL`, s.show_ages AS `Admittance`, s.show_price AS `Price`, s.show_tix_url AS `Ticket URL`, s.show_tix_phone AS `Ticket phone`, s.show_external_url AS `External URL`, s.show_notes AS `Notes`, t.tour_name AS `Tour`, s.show_status AS `Status`, s.show_related AS `Related ID`" . $query['from'] . $query['where'] . ' ORDER BY s.show_date DESC, s.show_time DESC, s.show_id DESC';
	if ($query['params']) { $countSql = $wpdb->prepare($countSql, $query['params']); $rowsSql = $wpdb->prepare($rowsSql, $query['params']); }
	$count = $wpdb->get_var($countSql);
	$countError = $wpdb->last_error;
	$rows = $wpdb->get_results($rowsSql, ARRAY_A);
	if ($countError !== '' || $wpdb->last_error !== '' || !is_array($rows)) return array('state' => $query['state'], 'count' => 0, 'rows' => array(), 'error' => __('The export selection could not be read. Reload the page and try again.', 'gigpress'));
	$rows = array_map(function ($row) {
		$row['Time'] = isset($row['Time'][7]) && $row['Time'][7] === '1' ? '' : (string) ($row['Time'] ?? '');
		$row['End date'] = (string) ($row['Date'] ?? '') === (string) ($row['End date'] ?? '') ? '' : (string) ($row['End date'] ?? '');
		$row['Status'] = (string) ($row['Status'] ?? '');
		$row['Related ID'] = (string) ($row['Related ID'] ?? '');
		$row['Related URL'] = !empty($row['Related ID']) ? gigpress_related_link((int) $row['Related ID'], 'url') : '';
		return $row;
	}, $rows);
	return array('state' => $query['state'], 'count' => (int) $count, 'rows' => $rows, 'error' => null);
}

function gigpress_csv_export_encode(array $rows): string {
	$lines = array();
	$encode = function (array $values) {
		return implode(',', array_map(function ($value) { return '"' . str_replace('"', '""', (string) $value) . '"'; }, $values));
	};
	$columns = gigpress_csv_columns();
	$lines[] = $encode($columns);
	foreach ($rows as $record) {
		$values = array();
		foreach ($columns as $column) $values[] = array_key_exists($column, $record) && is_scalar($record[$column]) ? (string) $record[$column] : '';
		$lines[] = $encode($values);
	}
	return implode("\r\n", $lines) . "\r\n";
}

function gigpress_csv_export_authorized(): bool {
	global $gpo;
	$nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) wp_unslash($_POST['_wpnonce']) : '';
	return current_user_can('manage_options') && $nonce !== '' && wp_verify_nonce($nonce, 'gigpress-action') !== false;
}

function gigpress_export_preview() {
	if (!current_user_can('manage_options')) wp_send_json_error(array('message' => __('You are not authorized to preview exports.', 'gigpress')), 403);
	check_ajax_referer('gigpress-export-preview', '_ajax_nonce');
	$selection = gigpress_csv_export_selection(gigpress_csv_export_state($_POST));
	if (!empty($selection['error'])) wp_send_json_error(array('message' => (string) $selection['error']), 500);
	wp_send_json_success(array('count' => (int) $selection['count']));
}

function gigpress_csv_emit_download(array $selection): void {
	if (!gigpress_csv_export_authorized() || !empty($selection['error']) || empty($selection['count']) || empty($selection['rows'])) return;
	$csv = gigpress_csv_export_encode($selection['rows']);
	if (!headers_sent()) {
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="gigpress-export-' . gmdate('Y-m-d') . '.csv"');
	}
	echo $csv;
}

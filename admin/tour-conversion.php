<?php
/* Recoverable, administrator-confirmed conversion of active tours into artists. */

function gigpress_tour_conversion_preview(array $tour_ids) {
	global $wpdb;
	$ids = array_values(array_unique(array_filter(array_map('absint', $tour_ids))));
	if (!$ids) return array('status' => 'invalid', 'tours' => array(), 'fingerprint' => '');
	$preview = array();
	foreach ($ids as $tour_id) {
		$tour = $wpdb->get_row($wpdb->prepare('SELECT tour_id, tour_name, tour_status FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $tour_id), ARRAY_A);
		if (!$tour || $tour['tour_status'] !== 'active') continue;
		$shows = $wpdb->get_results($wpdb->prepare('SELECT show_id, show_artist_id, show_tour_id, show_status FROM ' . GIGPRESS_SHOWS . ' WHERE show_tour_id = %d ORDER BY show_id', $tour_id), ARRAY_A);
		$active = 0; $trashed = 0; $snapshot = array();
		foreach ((array) $shows as $show) {
			if ($show['show_status'] === 'deleted') $trashed++;
			else $active++;
			$snapshot[] = array('show_id' => (int) $show['show_id'], 'original' => array('artist_id' => (int) $show['show_artist_id'], 'tour_id' => (int) $show['show_tour_id']));
		}
		$preview[] = array('tour_id' => (int) $tour_id, 'name' => (string) $tour['tour_name'], 'active_count' => $active,
			'trashed_count' => $trashed, 'shows' => $snapshot);
	}
	$payload = array('owner' => get_current_user_id(), 'tours' => $preview);
	return array('status' => count($preview) === count($ids) ? 'ready' : 'invalid', 'owner' => get_current_user_id(),
		'tours' => $preview, 'fingerprint' => hash('sha256', serialize($payload)));
}

function gigpress_tour_conversion_intent(array $preview) {
	if (($preview['status'] ?? '') !== 'ready' || empty($preview['tours']) || empty($preview['fingerprint'])) return array('status' => 'invalid');
	try { $token = bin2hex(random_bytes(20)); } catch (Throwable $error) { return array('status' => 'storage_failed'); }
	$intent = array('token' => $token, 'owner' => get_current_user_id(), 'expires' => time() + 900,
		'fingerprint' => $preview['fingerprint'], 'tours' => $preview['tours'], 'consumed' => false);
	if (!set_transient(gigpress_tour_conversion_intent_key($token), $intent, 900)) {
		$stored = get_transient(gigpress_tour_conversion_intent_key($token));
		if (!is_array($stored) || $stored !== $intent) return array('status' => 'storage_failed');
	}
	return $intent;
}

function gigpress_tour_conversion_intent_key($token) {
	return 'gigpress_tour_conversion_intent_' . hash('sha256', (string) $token);
}

function gigpress_tour_conversion_get_intent($token, $owner_id) {
	if (!is_string($token) || !preg_match('/\A[a-f0-9]{40}\z/', $token)) return false;
	$intent = get_transient(gigpress_tour_conversion_intent_key($token));
	if (!is_array($intent) || !hash_equals((string) ($intent['token'] ?? ''), $token)
		|| (int) ($intent['owner'] ?? 0) !== (int) $owner_id || (int) ($intent['expires'] ?? 0) < time()) return false;
	return $intent;
}

function gigpress_tour_conversion_cancel($token, $owner_id) {
	$intent = gigpress_tour_conversion_get_intent($token, $owner_id);
	if (!$intent || !empty($intent['consumed'])) return false;
	delete_transient(gigpress_tour_conversion_intent_key($token));
	return get_transient(gigpress_tour_conversion_intent_key($token)) === false;
}

function gigpress_tour_conversion_resolve_target($name, $create = true, $known_artist_id = 0) {
	global $wpdb;
	if (!is_string($name)) return array('status' => 'invalid');
	$candidates = $wpdb->get_results($wpdb->prepare('SELECT artist_id, artist_name FROM ' . GIGPRESS_ARTISTS . ' WHERE BINARY artist_name = BINARY %s ORDER BY artist_id', $name), ARRAY_A);
	$candidates = array_values(array_filter((array) $candidates, function ($row) use ($name) { return (string) $row['artist_name'] === $name; }));
	if (count($candidates) === 1) {
		if ($known_artist_id > 0 && (int) $candidates[0]['artist_id'] !== $known_artist_id) return array('status' => 'conflict', 'reason' => 'target_identity_changed');
		return array('status' => 'reused', 'artist_id' => (int) $candidates[0]['artist_id']);
	}
	if (count($candidates) > 1) return array('status' => 'ambiguous', 'artist_ids' => array_map(function ($row) { return (int) $row['artist_id']; }, $candidates));
	if (!$create) return array('status' => 'missing');
	$current_tour = (int) ($GLOBALS['gigpress_tour_conversion_current_tour'] ?? 0);
	if (gigpress_tour_conversion_fault('insert-before', $current_tour)) return array('status' => 'failed', 'reason' => 'target_insert_failed');
	$insert = $wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => $name, 'artist_alpha' => preg_replace('/^the /iu', '', strtolower($name)), 'artist_url' => ''), array('%s', '%s', '%s'));
	if ($insert === false || (int) $wpdb->insert_id < 1) return array('status' => 'failed', 'reason' => 'target_insert_failed');
	$created_id = (int) $wpdb->insert_id;
	$readback = $wpdb->get_row($wpdb->prepare('SELECT artist_id, artist_name FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_id = %d', $created_id), ARRAY_A);
	if (!$readback || (int) $readback['artist_id'] !== $created_id || $readback['artist_name'] !== $name || gigpress_tour_conversion_fault('target-readback', $current_tour)) return array('status' => 'failed', 'reason' => 'target_readback_mismatch');
	if (gigpress_tour_conversion_fault('insert-after', $current_tour)) return array('status' => 'failed', 'reason' => 'target_insert_readback_failed');
	return array('status' => 'created', 'artist_id' => $created_id, 'readback' => true);
}

function gigpress_tour_conversion_journal_key($intent_token, $tour_id) {
	return hash('sha256', (string) $intent_token . ':' . (int) $tour_id);
}

function gigpress_tour_conversion_journal_read() {
	$value = get_option('gigpress_tour_conversion_journal', array());
	return is_array($value) ? $value : array();
}

function gigpress_tour_conversion_journal_write($key, array $entry) {
	if (gigpress_tour_conversion_fault('journal-before', $entry['tour_id'] ?? 0)) return false;
	$journal = gigpress_tour_conversion_journal_read();
	$journal[$key] = $entry;
	$ok = update_option('gigpress_tour_conversion_journal', $journal, false);
	$readback = gigpress_tour_conversion_journal_read();
	$valid = (($ok !== false || isset($readback[$key])) && isset($readback[$key]) && $readback[$key] === $entry);
	return $valid && !gigpress_tour_conversion_fault('journal-after', $entry['tour_id'] ?? 0);
}

function gigpress_tour_conversion_fault($stage, $tour_id, $show_id = 0) {
	$fault = $GLOBALS['gigpress_tour_conversion_test_fault'] ?? null;
	return is_callable($fault) ? (bool) call_user_func($fault, $stage, (int) $tour_id, (int) $show_id) : false;
}

function gigpress_tour_conversion_current_show($show_id) {
	global $wpdb;
	$row = $wpdb->get_row($wpdb->prepare('SELECT show_artist_id, show_tour_id FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $show_id), ARRAY_A);
	return $row ? array('artist_id' => (int) $row['show_artist_id'], 'tour_id' => (int) $row['show_tour_id']) : null;
}

function gigpress_tour_conversion_entry($intent, $tour) {
	$entry = array('intent_token_hash' => hash('sha256', $intent['token']), 'owner' => (int) $intent['owner'],
		'tour_id' => (int) $tour['tour_id'], 'tour_name' => (string) $tour['name'], 'source_fingerprint' => hash('sha256', serialize($tour)),
		'target_artist_id' => 0, 'stage' => 'started', 'shows' => array(), 'errors' => array(), 'terminal' => false);
	foreach ($tour['shows'] as $show) {
		$original = array('artist_id' => (int) $show['original']['artist_id'], 'tour_id' => (int) $show['original']['tour_id']);
		$entry['shows'][(int) $show['show_id']] = array('original' => $original,
			'intended' => array('artist_id' => 0, 'tour_id' => 0), 'latest' => null, 'ownership' => 'unverified', 'readback' => false, 'error' => '');
	}
	return $entry;
}

function gigpress_tour_conversion_process_tour($intent, $tour) {
	global $wpdb;
	$tour_id = (int) $tour['tour_id'];
	$GLOBALS['gigpress_tour_conversion_current_tour'] = $tour_id;
	$key = gigpress_tour_conversion_journal_key($intent['token'], $tour_id);
	$journal = gigpress_tour_conversion_journal_read();
	$entry = $journal[$key] ?? gigpress_tour_conversion_entry($intent, $tour);
	if (!isset($journal[$key]) && !gigpress_tour_conversion_journal_write($key, $entry)) return array('status' => 'failed', 'stage' => 'journal-start', 'reason' => 'journal_write_failed', 'tour_id' => $tour_id);
	if (!empty($entry['terminal'])) return array('status' => 'complete', 'tour_id' => $tour_id, 'artist_id' => (int) $entry['target_artist_id']);
	$source = $wpdb->get_row($wpdb->prepare('SELECT tour_id, tour_name, tour_status FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $tour_id), ARRAY_A);
	if (!$source || $source['tour_name'] !== $tour['name'] || $source['tour_status'] !== 'active') return array('status' => 'failed', 'stage' => 'source-check', 'reason' => 'source_changed', 'tour_id' => $tour_id);
	if ((int) $entry['target_artist_id'] > 0) $target = gigpress_tour_conversion_resolve_target($tour['name'], false, (int) $entry['target_artist_id']);
	else $target = gigpress_tour_conversion_resolve_target($tour['name'], true);
	if (!in_array($target['status'] ?? '', array('created', 'reused'), true)) return array('status' => 'failed', 'stage' => 'target', 'reason' => $target['status'] ?? 'target_failed', 'tour_id' => $tour_id);
	$entry['target_artist_id'] = (int) $target['artist_id'];
	foreach ($entry['shows'] as $show_id => &$showState) $showState['intended'] = array('artist_id' => (int) $target['artist_id'], 'tour_id' => 0);
	unset($showState);
	$entry['stage'] = 'target-ready';
	if (!gigpress_tour_conversion_journal_write($key, $entry)) return array('status' => 'failed', 'stage' => 'journal-target', 'reason' => 'journal_write_failed', 'tour_id' => $tour_id);
	foreach ($entry['shows'] as $show_id => &$showState) {
		$current = gigpress_tour_conversion_current_show($show_id);
		$showState['latest'] = $current;
		if ($current === null) { $showState['ownership'] = 'conflict'; $showState['error'] = 'show_missing'; }
		elseif ($current === $showState['intended'] && !empty($showState['readback'])) { $showState['ownership'] = 'owned'; }
		elseif ($current !== $showState['original']) { $showState['ownership'] = 'conflict'; $showState['error'] = 'intervening_reassignment'; }
		else {
			$showState['ownership'] = 'owned';
			if (gigpress_tour_conversion_fault('update-before', $tour_id, $show_id)) { $showState['error'] = 'update_failed'; continue; }
			$changed = $wpdb->update(GIGPRESS_SHOWS, array('show_artist_id' => $entry['target_artist_id'], 'show_tour_id' => 0),
				array('show_id' => $show_id, 'show_artist_id' => $showState['original']['artist_id'], 'show_tour_id' => $tour_id), array('%d', '%d'), array('%d', '%d', '%d'));
			if ($changed === false || $changed !== 1 || gigpress_tour_conversion_fault('update-after', $tour_id, $show_id)) { $showState['error'] = 'update_failed'; continue; }
			$current = gigpress_tour_conversion_current_show($show_id);
			$showState['latest'] = $current;
			if ($current !== $showState['intended']) { $showState['error'] = 'update_readback_mismatch'; continue; }
			$showState['readback'] = true; $showState['ownership'] = 'owned'; $showState['error'] = '';
			if (!gigpress_tour_conversion_journal_write($key, $entry)) { $showState['error'] = 'journal_write_failed'; break; }
		}
		if ($showState['ownership'] === 'conflict' || $showState['error'] !== '') gigpress_tour_conversion_journal_write($key, $entry);
	}
	unset($showState);
	$journal = gigpress_tour_conversion_journal_read(); $entry = $journal[$key] ?? $entry;
	foreach ($entry['shows'] as $show_id => &$showState) {
		$current = gigpress_tour_conversion_current_show($show_id);
		if ($current !== null) $showState['latest'] = $current;
		if ($current !== $showState['intended'] || empty($showState['readback']) || $showState['ownership'] !== 'owned') {
			$showState['ownership'] = $current !== $showState['original'] && $current !== $showState['intended'] ? 'conflict' : $showState['ownership'];
			$showState['error'] = $showState['error'] ?: ($showState['ownership'] === 'conflict' ? 'intervening_reassignment' : 'reference_unverified');
		}
	}
	unset($showState);
	$entry['stage'] = 'references-reassigned';
	$hasErrors = false; foreach ($entry['shows'] as $showState) if ($showState['error'] !== '' || $showState['ownership'] !== 'owned' || empty($showState['readback'])) $hasErrors = true;
	if (!gigpress_tour_conversion_journal_write($key, $entry)) return array('status' => 'failed', 'stage' => 'journal-references', 'reason' => 'journal_write_failed', 'tour_id' => $tour_id);
	if ($hasErrors) return array('status' => 'failed', 'stage' => 'references-reassigned', 'reason' => 'reference_conflict_or_failure', 'tour_id' => $tour_id, 'shows' => $entry['shows']);
	$remaining = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE show_tour_id = %d', $tour_id));
	if ($remaining !== 0) return array('status' => 'failed', 'stage' => 'references-reassigned', 'reason' => 'remaining_references', 'tour_id' => $tour_id);
	if (gigpress_tour_conversion_fault('delete-before', $tour_id)) return array('status' => 'failed', 'stage' => 'source-delete', 'reason' => 'source_delete_failed', 'tour_id' => $tour_id);
	$deleted = $wpdb->delete(GIGPRESS_TOURS, array('tour_id' => $tour_id, 'tour_name' => $tour['name']), array('%d', '%s'));
	$sourceAfter = $wpdb->get_var($wpdb->prepare('SELECT tour_id FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $tour_id));
	if ($deleted !== 1 || $sourceAfter || gigpress_tour_conversion_fault('delete-after', $tour_id)) return array('status' => 'failed', 'stage' => 'source-delete', 'reason' => 'source_delete_readback_failed', 'tour_id' => $tour_id);
	$entry['stage'] = 'source-deleted';
	if (!gigpress_tour_conversion_journal_write($key, $entry)) return array('status' => 'failed', 'stage' => 'journal-complete', 'reason' => 'journal_write_failed_after_delete', 'tour_id' => $tour_id);
	$entry['terminal'] = true; $entry['stage'] = 'complete';
	if (!gigpress_tour_conversion_journal_write($key, $entry)) return array('status' => 'failed', 'stage' => 'journal-complete', 'reason' => 'journal_write_failed_after_delete', 'tour_id' => $tour_id);
	return array('status' => 'complete', 'tour_id' => $tour_id, 'artist_id' => (int) $entry['target_artist_id']);
}

function gigpress_tour_conversion_confirm($token, $owner_id = null) {
	if ($owner_id === null) $owner_id = get_current_user_id();
	$intent = gigpress_tour_conversion_get_intent($token, $owner_id);
	if (!$intent) return array('status' => 'rejected', 'reason' => 'invalid_intent');
	$journal = gigpress_tour_conversion_journal_read();
	if (!empty($intent['consumed'])) {
		$hasPending = false;
		foreach ($intent['tours'] as $tour) {
			$key = gigpress_tour_conversion_journal_key($token, $tour['tour_id']);
			if (empty($journal[$key]['terminal'])) $hasPending = true;
		}
		if (!$hasPending) return array('status' => 'rejected', 'reason' => 'replayed_intent');
	} else {
		$fresh = gigpress_tour_conversion_preview(array_column($intent['tours'], 'tour_id'));
		if (($fresh['status'] ?? '') !== 'ready' || !hash_equals($intent['fingerprint'], (string) $fresh['fingerprint'])) return array('status' => 'rejected', 'reason' => 'source_changed');
		$intent['consumed'] = true;
		if (!set_transient(gigpress_tour_conversion_intent_key($token), $intent, max(1, (int) $intent['expires'] - time()))) return array('status' => 'rejected', 'reason' => 'intent_persist_failed');
	}
	$results = array(); $complete = array(); $failed = array();
	foreach ($intent['tours'] as $tour) {
		$result = gigpress_tour_conversion_process_tour($intent, $tour);
		$results[] = $result;
		if (($result['status'] ?? '') === 'complete') $complete[] = $result; else $failed[] = $result;
	}
	return array('status' => $failed ? 'partial' : 'complete', 'completed' => $complete, 'failed' => $failed, 'results' => $results);
}

function gigpress_tour_conversion_dispatch() {
	global $gpo;
	if (!current_user_can($gpo['user_level'] ?? 'manage_options')) return array('status' => 'rejected', 'reason' => 'not_authorized');
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !check_admin_referer('gigpress-tour-conversion', '_wpnonce', false)) return array('status' => 'rejected', 'reason' => 'invalid_nonce');
	if (!gigpress_require_database_ready()) return array('status' => 'rejected', 'reason' => 'database_not_ready');
	$action = sanitize_key(wp_unslash($_POST['gpaction'] ?? ''));
	if ($action === 'tour-conversion-preview') {
		$ids = $_POST['tour_ids'] ?? array();
		if (!is_array($ids) || array_filter($ids, function ($id) { return !is_scalar($id); })) return array('status' => 'rejected', 'reason' => 'invalid_selection');
		$preview = gigpress_tour_conversion_preview($ids);
		if (($preview['status'] ?? '') !== 'ready') return array('status' => 'rejected', 'reason' => 'source_changed');
		return gigpress_tour_conversion_intent($preview);
	}
	$token = isset($_POST['conversion_token']) && is_scalar($_POST['conversion_token']) ? sanitize_text_field(wp_unslash($_POST['conversion_token'])) : '';
	if ($action === 'tour-conversion-cancel') return array('status' => gigpress_tour_conversion_cancel($token, get_current_user_id()) ? 'cancelled' : 'rejected');
	if ($action === 'tour-conversion-confirm') return gigpress_tour_conversion_confirm($token, get_current_user_id());
	return array('status' => 'rejected', 'reason' => 'invalid_action');
}

function gigpress_tour_conversion_render_preview(array $intent) {
	if (($intent['status'] ?? '') !== 'ready' && empty($intent['token'])) return;
	echo '<section class="gigpress-tour-conversion-preview"><h3>' . esc_html__('Review tour conversion', 'gigpress') . '</h3>';
	echo '<p>' . esc_html__('Confirm to reassign the listed shows to artists and remove each source tour only after verification. Cancel leaves show and tour records unchanged.', 'gigpress') . '</p><ul>';
	foreach ($intent['tours'] as $tour) echo '<li>' . esc_html($tour['name']) . ' — ' . esc_html(sprintf(__('%d active and %d trashed show references', 'gigpress'), $tour['active_count'], $tour['trashed_count'])) . '</li>';
	echo '</ul><form method="post" action="' . esc_url(admin_url('admin.php?page=gigpress-artists')) . '">';
	wp_nonce_field('gigpress-tour-conversion'); echo '<input type="hidden" name="conversion_token" value="' . esc_attr($intent['token']) . '" />';
	echo '<button class="button button-primary" name="gpaction" value="tour-conversion-confirm">' . esc_html__('Confirm conversion', 'gigpress') . '</button> ';
	echo '<button class="button" name="gpaction" value="tour-conversion-cancel">' . esc_html__('Cancel', 'gigpress') . '</button></form></section>';
}

function gigpress_tour_conversion_render_result(array $result) {
	$status = $result['status'] ?? 'failed';
	$class = $status === 'complete' || $status === 'cancelled' ? 'updated' : 'error';
	echo '<div id="message" class="' . esc_attr($class) . ' fade"><p>';
	if ($status === 'complete') echo esc_html__('All confirmed tours were converted and verified.', 'gigpress');
	elseif ($status === 'cancelled') echo esc_html__('Tour conversion cancelled. Show and tour records were not changed.', 'gigpress');
	elseif (isset($result['reason'])) echo esc_html(sprintf(__('Tour conversion was not completed (%s). Review the retained source tours and retry after resolving the issue.', 'gigpress'), $result['reason']));
	else echo esc_html__('Some tours could not be converted. Completed tours are verified; retained tours can be retried after resolving the listed conflicts.', 'gigpress');
	echo '</p>';
	foreach (($result['failed'] ?? array()) as $failure) {
		echo '<p>' . esc_html(sprintf(__('Tour %1$d stopped at %2$s: %3$s', 'gigpress'), $failure['tour_id'] ?? 0, $failure['stage'] ?? '', $failure['reason'] ?? '')) . '</p>';
		foreach (($failure['shows'] ?? array()) as $show_id => $show) if (($show['ownership'] ?? '') === 'conflict') echo '<p>' . esc_html(sprintf(__('Show %1$d has an intervening relationship (artist %2$d, tour %3$d); it was left unchanged.', 'gigpress'), $show_id, $show['latest']['artist_id'] ?? 0, $show['latest']['tour_id'] ?? 0)) . '</p>';
	}
	if ($status === 'partial') {
		$pending = array(); foreach (($result['failed'] ?? array()) as $failure) if (!empty($failure['tour_id'])) $pending[] = (int) $failure['tour_id'];
		if ($pending) {
			echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=gigpress-artists')) . '">'; wp_nonce_field('gigpress-tour-conversion');
			$intent = get_transient(gigpress_tour_conversion_intent_key($_POST['conversion_token'] ?? ''));
			if (is_array($intent)) echo '<input type="hidden" name="conversion_token" value="' . esc_attr($intent['token']) . '" /><button class="button" name="gpaction" value="tour-conversion-confirm">' . esc_html__('Retry retained conversions', 'gigpress') . '</button>';
			echo '</form>';
		}
	}
	echo '</div>';
}

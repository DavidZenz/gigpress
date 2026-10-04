<?php

function gigpress_require_database_ready() {
	$result = function_exists('gigpress_db_bootstrap') ? gigpress_db_bootstrap() : array('status' => 'blocked', 'code' => 'bootstrap_unavailable');
	if (!is_array($result) || ($result['status'] ?? 'blocked') !== 'ready') {
		$code = is_array($result) && !empty($result['code']) ? $result['code'] : 'unsafe_metadata';
		echo '<div id="message" class="error fade"><p>' . esc_html(sprintf(__('GigPress data upgrade is paused (%s). Resolve the database condition before changing show data.', 'gigpress'), $code)) . '</p></div>';
		return false;
	}
	return true;
}

function gigpress_entity_has_show_dependencies($column, $id) {
	global $wpdb;
	if (!in_array($column, array('show_artist_id', 'show_venue_id'), true)) return false;
	return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE ' . $column . ' = %d', absint($id))) > 0;
}

function gigpress_block_dependent_entity_deletion($label) {
	echo '<div id="message" class="error fade"><p>' . esc_html(sprintf(__('%s cannot be deleted because active or trashed shows still reference it.', 'gigpress'), $label)) . '</p></div>';
	return false;
}

// HANDLER: ADD A SHOW
// ===================


/* Show-only request handling; other entity handlers keep their established contracts. */
function gigpress_show_raw_state() {
	$raw = array();
	foreach ($_POST as $key => $value) {
		if (is_scalar($value)) $raw[$key] = wp_unslash((string) $value);
	}
	return $raw;
}

function gigpress_show_calendar_date($value) {
	if (!is_string($value) || !preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $value, $parts)) return false;
	return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : false;
}

function gigpress_show_received_date($raw, $end = false) {
	$key = $end ? 'show_end_date' : 'show_date';
	$prefix = $end ? 'exp_' : 'gp_';
	if (($raw['replace_' . $key] ?? '') === '1') return $raw[$key . '_picker'] ?? '';
	if (array_key_exists($key, $raw) || array_key_exists($key, $_POST)) return $raw[$key] ?? '';
	$year = $raw[$prefix . 'yy'] ?? '';
	$month = $raw[$prefix . 'mm'] ?? '';
	$day = $raw[$prefix . 'dd'] ?? '';
	if (!preg_match('/\A[0-9]{4}\z/', $year) || !preg_match('/\A[0-9]{1,2}\z/', $month) || !preg_match('/\A[0-9]{1,2}\z/', $day)) {
		return $year . '-' . $month . '-' . $day;
	}
	return sprintf('%s-%02d-%02d', $year, (int) $month, (int) $day);
}

function gigpress_show_completed_ids($raw) {
	$created = array();
	foreach (array('artist' => 'show_artist_id', 'venue' => 'show_venue_id', 'tour' => 'show_tour_id', 'post' => 'show_related') as $kind => $field) {
		$id = $raw['created_' . $kind . '_id'] ?? '';
		if (preg_match('/\A[1-9][0-9]*\z/', $id) && $id === ($raw[$field] ?? '')) $created[$kind] = (int) $id;
	}
	return $created;
}

function gigpress_show_validate($raw, $mode) {
	global $wpdb;
	$errors = array();
	$fields = array('gpaction', 'show_id', 'show_date', 'show_end_date', 'show_date_picker', 'show_end_date_picker', 'replace_show_date', 'replace_show_end_date',
		'gp_yy', 'gp_mm', 'gp_dd', 'exp_yy', 'exp_mm', 'exp_dd', 'gp_hh', 'gp_min', 'show_multi',
		'show_artist_id', 'artist_name', 'artist_url', 'show_venue_id', 'venue_name', 'venue_address', 'venue_city', 'venue_state', 'venue_postal_code',
		'venue_country', 'venue_url', 'venue_phone', 'show_tour_id', 'tour_name', 'show_related', 'show_related_title', 'show_related_date',
		'show_price', 'show_tix_url', 'show_tix_phone', 'show_external_url', 'show_ages', 'show_notes', 'show_status',
		'created_artist_id', 'created_venue_id', 'created_tour_id', 'created_post_id');
	foreach ($fields as $field) {
		if (array_key_exists($field, $_POST) && !is_scalar($_POST[$field])) $errors[$field] = __('Enter a single value for this field.', 'gigpress');
	}
	if (isset($raw['gpaction']) && $raw['gpaction'] !== $mode) $errors['gpaction'] = __('This form action is invalid. Open Add a show or Edit from the show list and retry.', 'gigpress');
	foreach (array('show_multi', 'replace_show_date', 'replace_show_end_date') as $field) {
		if (isset($raw[$field]) && !in_array($raw[$field], array('', '0', '1'), true)) $errors[$field] = __('Choose whether this option is checked.', 'gigpress');
	}
	if (!gigpress_show_calendar_date(gigpress_show_received_date($raw))) $errors['show_date'] = __('Enter a valid date in YYYY-MM-DD format.', 'gigpress');
	if (($raw['show_multi'] ?? '') === '1' && !gigpress_show_calendar_date(gigpress_show_received_date($raw, true))) $errors['show_end_date'] = __('Enter a valid end date in YYYY-MM-DD format.', 'gigpress');
	$hour = $raw['gp_hh'] ?? 'na';
	$minute = $raw['gp_min'] ?? 'na';
	if ($hour !== 'na' && !preg_match('/\A(?:[01]?[0-9]|2[0-3])\z/', $hour)) $errors['gp_hh'] = __('Select an hour from 00 to 23, or Not specified.', 'gigpress');
	if ($minute !== 'na' && !preg_match('/\A[0-5]?[0-9]\z/', $minute)) $errors['gp_min'] = __('Select a minute from 00 to 59.', 'gigpress');
	if (!in_array($raw['show_status'] ?? 'active', array('active', 'soldout', 'cancelled'), true)) $errors['show_status'] = __('Select an available show status.', 'gigpress');
	if (isset($raw['show_related_date']) && !in_array($raw['show_related_date'], array('now', 'show'), true)) $errors['show_related_date'] = __('Select when to publish the related post.', 'gigpress');
	if (isset($raw['venue_country'])) {
		global $gp_countries;
		if (!array_key_exists($raw['venue_country'], $gp_countries)) $errors['venue_country'] = __('Select a country from the list.', 'gigpress');
	}
	foreach (array('artist' => array('show_artist_id', GIGPRESS_ARTISTS, 'artist_id'), 'venue' => array('show_venue_id', GIGPRESS_VENUES, 'venue_id'),
		'tour' => array('show_tour_id', GIGPRESS_TOURS, 'tour_id'), 'post' => array('show_related', $wpdb->posts, 'ID')) as $kind => $spec) {
		list($field, $table, $column) = $spec;
		$value = $raw[$field] ?? ($kind === 'tour' || $kind === 'post' ? '0' : '');
		if ($value === 'new') {
			$required = $kind === 'venue' ? array('venue_name', 'venue_city') : ($kind === 'post' ? array() : array($kind . '_name'));
			foreach ($required as $requiredField) if (trim($raw[$requiredField] ?? '') === '') $errors[$requiredField] = __('Enter a value for this required field.', 'gigpress');
		} elseif (($kind === 'tour' || $kind === 'post') && $value === '0') {
			continue;
		} elseif (!preg_match('/\A[1-9][0-9]*\z/', $value) || strlen($value) > 18 || !$wpdb->get_var($wpdb->prepare('SELECT ' . $column . ' FROM ' . $table . ' WHERE ' . $column . ' = %d', $value))) {
			$errors[$field] = __('Select an existing entry or choose Add a new entry.', 'gigpress');
		}
	}
	if ($mode === 'update' && (!preg_match('/\A[1-9][0-9]*\z/', $raw['show_id'] ?? '') || strlen($raw['show_id'] ?? '') > 18)) $errors['show_id'] = __('The show identity is invalid. Open it again from the show list.', 'gigpress');
	return $errors;
}

function gigpress_prepare_show_fields($context = 'new', $raw = null) {
	global $wpdb;
	if ($raw === null) $raw = gigpress_show_raw_state();
	$mode = $context === 'edit' ? 'update' : 'add';
	$prepared = array('fields' => array(), 'field_errors' => gigpress_show_validate($raw, $mode), 'system_errors' => array(),
		'created_ids' => gigpress_show_completed_ids($raw), 'raw_state' => $raw);
	if ($prepared['field_errors']) return $prepared;
	$gpo = get_option('gigpress_settings');
	$date = gigpress_show_received_date($raw);
	$hour = $raw['gp_hh'] ?? 'na';
	$minute = $raw['gp_min'] ?? 'na';
	$show = array('show_date' => $date, 'show_time' => $hour === 'na' ? '00:00:01' : sprintf('%02d:%02d:00', (int) $hour, $minute === 'na' ? 0 : (int) $minute),
		'show_multi' => ($raw['show_multi'] ?? '') === '1' ? 1 : 0,
		'show_expire' => ($raw['show_multi'] ?? '') === '1' ? gigpress_show_received_date($raw, true) : $date,
		'show_status' => $raw['show_status'] ?? 'active');
	foreach (array('show_price', 'show_tix_phone', 'show_ages') as $field) $show[$field] = sanitize_text_field(trim($raw[$field] ?? ''));
	foreach (array('show_tix_url', 'show_external_url', 'show_notes') as $field) $show[$field] = wp_kses_post(trim($raw[$field] ?? ''));

	/* Validate the whole form before the first creation, then stop at the first failed substep. */
	foreach (array('artist' => 'show_artist_id', 'venue' => 'show_venue_id', 'tour' => 'show_tour_id', 'post' => 'show_related') as $kind => $field) {
		$value = $raw[$field] ?? '0';
		if ($value !== 'new') {
			$show[$field] = (int) $value;
			continue;
		}
		if ($kind === 'artist') {
			$name = sanitize_text_field(trim($raw['artist_name']));
			$data = array('artist_name' => $name, 'artist_alpha' => preg_replace('/^the /iu', '', strtolower($name)), 'artist_url' => wp_kses_post(trim($raw['artist_url'] ?? '')));
			$write = $wpdb->insert(GIGPRESS_ARTISTS, $data);
			$id = $write === false ? 0 : (int) $wpdb->insert_id;
		} elseif ($kind === 'venue') {
			$data = array();
			foreach (array('venue_name', 'venue_address', 'venue_city', 'venue_state', 'venue_postal_code', 'venue_country', 'venue_url', 'venue_phone') as $key) {
				$data[$key] = $key === 'venue_url' ? wp_kses_post(trim($raw[$key] ?? '')) : sanitize_text_field(trim($raw[$key] ?? ''));
			}
			$write = $wpdb->insert(GIGPRESS_VENUES, $data);
			$id = $write === false ? 0 : (int) $wpdb->insert_id;
		} elseif ($kind === 'tour') {
			$write = $wpdb->insert(GIGPRESS_TOURS, array('tour_name' => sanitize_text_field(trim($raw['tour_name']))));
			$id = $write === false ? 0 : (int) $wpdb->insert_id;
		} else {
			$artist = $wpdb->get_var($wpdb->prepare('SELECT artist_name FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_id = %d', $show['show_artist_id']));
			$venue = $wpdb->get_row($wpdb->prepare('SELECT venue_name, venue_city FROM ' . GIGPRESS_VENUES . ' WHERE venue_id = %d', $show['show_venue_id']), ARRAY_A);
			$title = strip_tags(trim($raw['show_related_title'] ?? $gpo['default_title']));
			$title = str_replace(array('%date%', '%long_date%', '%artist%', '%venue%', '%city%'), array(mysql2date($gpo['date_format'], $date), mysql2date($gpo['date_format_long'], $date), $artist, $venue['venue_name'], $venue['venue_city']), $title);
			$write = wp_insert_post(wp_slash(array('post_title' => $title, 'post_category' => array($gpo['related_category']),
				'post_date' => ($raw['show_related_date'] ?? $gpo['related_date']) === 'show' ? $date . ' ' . $show['show_time'] : '',
				'post_status' => 'publish', 'post_content' => '')), true);
			$id = is_wp_error($write) ? 0 : (int) $write;
		}
		if ($id === 0) {
			$labels = array('artist' => __('artist', 'gigpress'), 'venue' => __('venue', 'gigpress'), 'tour' => __('tour', 'gigpress'), 'post' => __('related post', 'gigpress'));
			$prepared['system_errors'][] = sprintf(__('The new %s could not be created. Keep these values and retry. Entries already created are selected below.', 'gigpress'), $labels[$kind]);
			$prepared['fields'] = $show;
			return $prepared;
		}
		$show[$field] = $id;
		$prepared['created_ids'][$kind] = $id;
		$prepared['raw_state'][$field] = (string) $id;
		$prepared['raw_state']['created_' . $kind . '_id'] = (string) $id;
	}
	$prepared['fields'] = $show;
	return $prepared;
}

function gigpress_show_save($mode) {
	global $wpdb;
	$raw = gigpress_show_raw_state();
	$outcome = array('status' => 'invalid', 'mode' => $mode, 'raw_state' => $raw, 'field_errors' => array(), 'system_errors' => array(), 'created_ids' => gigpress_show_completed_ids($raw));
	$gpo = get_option('gigpress_settings');
	if (!current_user_can($gpo['user_level'])) {
		$outcome['status'] = 'blocked';
		$outcome['system_errors'][] = __('You do not have permission to save shows. Ask a site administrator for access.', 'gigpress');
		return $outcome;
	}
	check_admin_referer('gigpress-action');
	/* Reuse the readiness guard without its legacy output: this form renders its own actionable outcome. */
	ob_start();
	$ready = gigpress_require_database_ready();
	ob_end_clean();
	if (!$ready) {
		$outcome['status'] = 'blocked';
		$outcome['system_errors'][] = __('Show data is unavailable while its upgrade is paused. Keep these values and retry after your site administrator resolves the database condition.', 'gigpress');
		return $outcome;
	}
	$outcome['field_errors'] = gigpress_show_validate($raw, $mode);
	$id = 0;
	if ($mode === 'update' && !isset($outcome['field_errors']['show_id'])) {
		$id = (int) ($raw['show_id'] ?? 0);
		$existing = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $id), ARRAY_A);
		if (!$existing) {
			$outcome['status'] = 'failed';
			$outcome['system_errors'][] = __('This show is no longer available. Return to the show list and open the show you want to edit.', 'gigpress');
			return $outcome;
		}
		$outcome['show_id'] = $id;
	}
	if ($outcome['field_errors']) return $outcome;
	$prepared = gigpress_prepare_show_fields($mode === 'add' ? 'new' : 'edit', $raw);
	foreach (array('raw_state', 'field_errors', 'system_errors', 'created_ids') as $key) $outcome[$key] = $prepared[$key];
	if ($prepared['field_errors'] || $prepared['system_errors']) {
		$outcome['status'] = $prepared['field_errors'] ? 'invalid' : 'failed';
		return $outcome;
	}
	$show = $prepared['fields'];
	$write = $mode === 'add' ? $wpdb->insert(GIGPRESS_SHOWS, $show)
		: $wpdb->update(GIGPRESS_SHOWS, $show, array('show_id' => $id), null, array('%d'));
	if ($mode === 'add') $id = $write === false ? 0 : (int) $wpdb->insert_id;
	$saved = $id > 0 ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $id), ARRAY_A) : null;
	$matches = $saved !== null;
	foreach ($show as $field => $value) if (!$saved || (string) $saved[$field] !== (string) $value) $matches = false;
	if ($write === false || !$matches) {
		$outcome['status'] = 'failed';
		$outcome['system_errors'][] = __('The show could not be saved. Keep these values and try saving again. Entries already created are selected below.', 'gigpress');
		return $outcome;
	}
	$outcome['status'] = 'saved';
	$outcome['show_id'] = $id;
	if ($mode === 'add') {
		foreach (array('date','time','ages','artist_id','venue_id','tour_id') as $key) {
			$setting = array('artist_id' => 'artist', 'venue_id' => 'venue', 'tour_id' => 'tour')[$key] ?? $key;
			$gpo['default_' . $setting] = $show['show_' . $key];
		}
		if (($raw['show_venue_id'] ?? '') === 'new') $gpo['default_country'] = $raw['venue_country'];
		if (($raw['show_related'] ?? '') === 'new') {
			$gpo['default_title'] = strip_tags(trim($raw['show_related_title'] ?? $gpo['default_title']));
			$gpo['related_date'] = $raw['show_related_date'] ?? $gpo['related_date'];
		}
		update_option('gigpress_settings', $gpo);
	}
	/* Preserve output for established handler callers, while returning machine-readable success. */
	gigpress_show_outcome_notice($outcome);
	$_POST = array();
	return $outcome;
}

function gigpress_show_outcome_notice($outcome) {
	if ($outcome['status'] === 'saved') {
		echo '<div id="message" class="notice notice-success" role="status" tabindex="-1"><p>' . esc_html($outcome['mode'] === 'add'
			? __('Your show was successfully added. Add another show below.', 'gigpress') : __('Your show was successfully updated.', 'gigpress')) . ' ';
		echo '<a href="' . esc_url(admin_url('admin.php?page=gigpress/gigpress.php&gpaction=edit&show_id=' . $outcome['show_id'])) . '">' . esc_html__('Edit saved show', 'gigpress') . '</a> | ';
		echo '<a href="' . esc_url(admin_url('admin.php?page=gigpress-shows')) . '">' . esc_html__('View list', 'gigpress') . '</a></p></div>';
		return;
	}
	echo '<div id="gigpress-errors" class="notice notice-error" role="alert" tabindex="-1"><p>' . esc_html__('The show has not been saved. Correct the fields below or retry after the problem is resolved.', 'gigpress') . '</p>';
	if ($outcome['field_errors']) {
		echo '<ul>';
		$targets = array('gpaction' => 'show_form', 'show_id' => 'show_form', 'gp_yy' => 'show_date', 'gp_mm' => 'show_date', 'gp_dd' => 'show_date',
			'exp_yy' => 'show_end_date', 'exp_mm' => 'show_end_date', 'exp_dd' => 'show_end_date', 'created_artist_id' => 'show_artist_id',
			'created_venue_id' => 'show_venue_id', 'created_tour_id' => 'show_tour_id', 'created_post_id' => 'show_related');
		foreach ($outcome['field_errors'] as $field => $error) echo '<li><a href="#' . esc_attr($targets[$field] ?? $field) . '">' . esc_html($error) . '</a></li>';
		echo '</ul>';
	}
	foreach ($outcome['system_errors'] as $error) echo '<p>' . esc_html($error) . '</p>';
	echo '<p><a href="' . esc_url(admin_url('admin.php?page=gigpress-shows')) . '">' . esc_html__('View list', 'gigpress') . '</a></p></div>';
}

function gigpress_add_show() { return gigpress_show_save('add'); }
function gigpress_update_show() { return gigpress_show_save('update'); }


function gigpress_prepare_venue_fields() {

	$venue = array(
		'venue_name' => gigpress_db_in($_POST['venue_name']),
		'venue_address' => gigpress_db_in($_POST['venue_address'] ?? null),
		'venue_city' => gigpress_db_in($_POST['venue_city']),		
		'venue_state' => gigpress_db_in($_POST['venue_state'] ?? null),
		'venue_postal_code' => gigpress_db_in($_POST['venue_postal_code'] ?? null),
		'venue_country' => gigpress_db_in($_POST['venue_country']),
		'venue_url' => gigpress_db_in($_POST['venue_url'] ?? null, FALSE),
		'venue_phone' => gigpress_db_in($_POST['venue_phone'] ?? null)
	);
	return $venue;

}


function gigpress_error_checking($context) {
	
	$errors = array();
	
	switch($context) {
		case 'show':
			return gigpress_show_validate(gigpress_show_raw_state(), ($_POST['gpaction'] ?? 'add') === 'update' ? 'update' : 'add');
		case 'artist':
			if(empty($_POST['artist_name']))
				$errors['artist_name'] = __("You must enter an artist name.", "gigpress");
			break;
		case 'tour':
			if(empty($_POST['tour_name']))
				$errors['tour_name'] = __("You must enter a tour name.", "gigpress");
			break;	
		case 'venue':
			if(empty($_POST['venue_name']))
				$errors['venue_name'] = __("You must enter a venue name.", "gigpress");
			if(empty($_POST['venue_city']))
				$errors['venue_city'] = __("You must enter a city.", "gigpress");
			break;
	}

	return $errors;
}


// HANDLER: DELETE A SHOW
// ======================


function gigpress_delete_show() {

	global $wpdb;
	$wpdb->show_errors();
	
	// Check the nonce
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	
	if(is_array($_REQUEST['show_id'])) {
		// We're deleting multiple shows, so we need to sanitize each id individually
		$shows = array();
		foreach($_REQUEST['show_id'] as $show) {
			$shows[] = $wpdb->prepare('%d', $show);
		}
		$shows = implode(',', $shows);
	
	} else {
		// Single show_id
		$shows = $wpdb->prepare('%d', $_REQUEST['show_id']);
	}
	
	$undo = wp_nonce_url(admin_url('admin.php?page=gigpress-shows&amp;gpaction=undo&amp;show_id='.$shows), 'gigpress-action');
		
	// Delete the show(s)
	$trashshow = $wpdb->query("UPDATE ".GIGPRESS_SHOWS." SET show_status = 'deleted' WHERE show_id IN($shows)");
	if($trashshow != FALSE) { ?>
			
		<div id="message" class="updated fade">
			<p><?php _e("Show(s) successfully deleted.", "gigpress"); ?> 
			<small>(<a href="<?php echo $undo; ?>"><?php _e("Undo", "gigpress"); ?></a>)</small></p>
		</div>
		
	<?php } elseif($trashshow === FALSE) { ?>
		
		<div id="message" class="error fade">
			<p><?php _e("We ran into some trouble deleting the show(s). Sorry.", "gigpress"); ?></p>
		</div>				
	<?php }
}


// HANDLER: ADD A VENUE
// ===================


function gigpress_add_venue() {

	global $wpdb, $gpo;	
	$errors = array();
	$wpdb->show_errors();
	
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	
	$errors = gigpress_error_checking('venue');
	
	if($errors) {
		echo('<div id="message" class="error fade">');
		foreach($errors as $error)
			echo("<p>".$error."</p>");
		echo("</div>");
		
		return $errors;
		
	} else {
	
		// Looks like we're all here, so let's add to the DB
		$venue = gigpress_prepare_venue_fields();
		$format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
		$addvenue = $wpdb->insert(GIGPRESS_VENUES, $venue, $format);
		
		$gpo['default_country'] = $venue['venue_country'];
		update_option('gigpress_settings', $gpo);
		
		// Was the query successful?
		if($addvenue != FALSE) { ?>
			<div id="message" class="updated fade"><p><?php echo wptexturize($venue['venue_name']) .' '. __("was successfully added to the database.", "gigpress"); ?></p></div>
	<?php } elseif($addvenue === FALSE) { ?>
			<div id="message" class="error fade"><p><?php _e("Something ain't right - try again?", "gigpress"); ?></p></div>
	<?php }
		unset($venue);
	}
}


// HANDLER: UPDATE A VENUE
// ======================


function gigpress_update_venue() {

	global $wpdb;
	
	$wpdb->show_errors();
	
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
			
	$errors = gigpress_error_checking('venue');
	
	if($errors) {
		echo('<div id="message" class="error fade">');
		foreach($errors as $error)
			echo("<p>".$error."</p>");
		echo("</div>");
		$errors['editing'] = TRUE;
		return $errors;

	} else {
	
		// Looks like we're all here, so let's add to the DB
		$venue = gigpress_prepare_venue_fields();
		$format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
		$where = array('venue_id' => absint($_POST['venue_id']));
		$where_format = array('%d');
		$updatevenue = $wpdb->update(GIGPRESS_VENUES, $venue, $where, $format, $where_format);
		
		// Was the query successful?
		if($updatevenue != FALSE) { ?>
			<div id="message" class="updated fade"><p><?php echo wptexturize($venue['venue_name']) .' '. __("was successfully updated.", "gigpress"); ?></p></div>
	<?php } elseif($updatevenue === FALSE) { ?>
			<div id="message" class="error fade"><p><?php _e("Something ain't right - try again?", "gigpress"); ?></p></div>
	<?php }
		unset($venue,$where);
	}
}

// HANDLER: DELETE A VENUE
// ======================


function gigpress_delete_venue() {

	global $wpdb;
	
	$wpdb->show_errors();
	
	// Check the nonce
	check_admin_referer('gigpress-action');	
	if (!gigpress_require_database_ready()) return false;
	$venue_id = absint($_GET['venue_id']);
	if (gigpress_entity_has_show_dependencies('show_venue_id', $venue_id)) return gigpress_block_dependent_entity_deletion(__('Venue', 'gigpress'));
	
	// Delete the venue
	$trashvenue = $wpdb->query($wpdb->prepare("DELETE FROM ". GIGPRESS_VENUES ." WHERE venue_id = %d LIMIT 1", $venue_id));
	if($trashvenue != FALSE) {	?>	
		<div id="message" class="updated fade"><p><?php _e("Venue successfully deleted.", "gigpress"); ?></p></div>	
	<?php } elseif($trashvenue === FALSE) { ?>
		<div id="message" class="error fade"><p><?php _e("We ran into some trouble deleting the venue. Sorry.", "gigpress"); ?></p></div>				
	<?php }
}


// HANDLER: ADD A TOUR
// ===================


function gigpress_add_tour() {

	global $wpdb;	
	
	$wpdb->show_errors();
	
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	
	$errors = gigpress_error_checking('tour');
	
	if($errors) {
		echo('<div id="message" class="error fade">');
		foreach($errors as $error)
			echo("<p>".$error."</p>");
		echo("</div>");
		
		return $errors;
		
	} else {
	
		// Looks like we're all here, so let's add to the DB
		
		$tour = array('tour_name' => gigpress_db_in($_POST['tour_name']));
		$addtour = $wpdb->insert(GIGPRESS_TOURS, $tour, array('%s','%d'));
		
		// Was the query successful?
		if($addtour != FALSE) { ?>
			<div id="message" class="updated fade"><p><?php echo wptexturize($tour['tour_name']) .' '. __("was successfully added to the database.", "gigpress"); ?></p></div>
	<?php } elseif($addtour === FALSE) { ?>
			<div id="message" class="error fade"><p><?php _e("Something ain't right - try again?", "gigpress"); ?></p></div>
	<?php }
		unset($tour);
	}
}


// HANDLER: UPDATE A TOUR
// ======================


function gigpress_update_tour() {

	global $wpdb;
	
	$wpdb->show_errors();
	
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
			
	$errors = gigpress_error_checking('tour');
	
	if($errors) {
		echo('<div id="message" class="error fade">');
		foreach($errors as $error)
			echo("<p>".$error."</p>");
		echo("</div>");
		$errors['editing'] = TRUE;
		return $errors;

	} else {
			
		// Looks like we're all here, so let's update the DB
		$tour = array( 'tour_name' => gigpress_db_in($_POST['tour_name']) );
		$where = array('tour_id' => absint($_POST['tour_id']));	
		$updatetour = $wpdb->update(GIGPRESS_TOURS, $tour, $where, array('%s'), array('%d'));
		
		// Was the query successful?
		if($updatetour != FALSE) { ?>
			<div id="message" class="updated fade"><p><?php _e("Tour name successfully changed to", "gigpress"); echo ': ' . wptexturize($tour['tour_name']); ?></p></div>
	<?php } elseif($updatetour === FALSE) { ?>
			<div id="message" class="error fade"><p><?php _e("Something ain't right - try again?", "gigpress"); ?></p></div>
	<?php }
		unset($tour, $where);
	}
}


// HANDLER: DELETE A TOUR
// ======================


function gigpress_delete_tour() {

	global $wpdb;
	$tour_id = absint($_GET['tour_id']);
	$undo = wp_nonce_url(admin_url('admin.php?page=gigpress-tours&amp;gpaction=undo&amp;tour_id='.$tour_id), 'gigpress-action');
	
	$wpdb->show_errors();
	
	// Check the nonce
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	$show_ids = array_map('absint', (array) $wpdb->get_col($wpdb->prepare('SELECT show_id FROM ' . GIGPRESS_SHOWS . ' WHERE show_tour_id = %d', $tour_id)));
	$restore_map = get_option('gigpress_tour_restore_map', array());
	$restore_map = is_array($restore_map) ? $restore_map : array();
	if (!isset($restore_map[$tour_id]) || !is_array($restore_map[$tour_id])) $restore_map[$tour_id] = array();
	$map_changed = false;
	foreach ($show_ids as $show_id) {
		if ($show_id > 0 && (!isset($restore_map[$tour_id][$show_id]) || (int) $restore_map[$tour_id][$show_id] !== $tour_id)) {
			$restore_map[$tour_id][$show_id] = $tour_id;
			$map_changed = true;
		}
	}
	if ($map_changed) {
		update_option('gigpress_tour_restore_map', $restore_map);
		$stored_map = get_option('gigpress_tour_restore_map', array());
		if (!is_array($stored_map) || $stored_map !== $restore_map) {
			echo '<div id="message" class="error fade"><p>' . esc_html(__('We could not safely record the shows needed for this tour undo.', 'gigpress')) . '</p></div>';
			return false;
		}
	}
	
	// Delete the tour
	$where = array('tour_id' => $tour_id);
	$trashtour = $wpdb->update(GIGPRESS_TOURS, array('tour_status' => 'deleted'), $where, array('%s'), array('%s'));
	unset($where);
		
	if($trashtour != FALSE) {
		// Detach only this tour's recorded shows; other pending undo markers stay intact.
		$where = array('show_tour_id' => $tour_id);
		$restore = $wpdb->update(GIGPRESS_SHOWS, array('show_tour_id' => 0, 'show_tour_restore' => 1), $where, array('%d','%d'), array('%d'));
		unset($where);
		?>
		
		<div id="message" class="updated fade"><p><?php _e("Tour successfully deleted.", "gigpress"); ?> <small>(<a href="<?php echo $undo; ?>"><?php _e("Undo", "gigpress"); ?></a>)</small></p></div>
		
	<?php } elseif($trashtour === FALSE) { ?>
		
		<div id="message" class="error fade"><p><?php _e("We ran into some trouble deleting the tour. Sorry.", "gigpress"); ?></p></div>				
	<?php }
}



// HANDLER: ADD AN ARTIST
// ===================


function gigpress_add_artist() {

	global $wpdb;	
	
	$wpdb->show_errors();

	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	
	$errors = gigpress_error_checking('artist');
	
	if($errors) {
		echo('<div id="message" class="error fade">');
		foreach($errors as $error)
			echo("<p>".$error."</p>");
		echo("</div>");
		
		return $errors;
		
	} else {
		
		$alpha = preg_replace("/^the /uix", "", strtolower($_POST['artist_name']));
		$artist = array(
			'artist_name' => gigpress_db_in($_POST['artist_name']),
			'artist_alpha' => gigpress_db_in($alpha),
			'artist_url' => gigpress_db_in($_POST['artist_url'], FALSE)
		);
		$format = array('%s', '%s', '%s');
		$addartist = $wpdb->insert(GIGPRESS_ARTISTS, $artist, $format);
		
		// Was the query successful?
		if($addartist != FALSE) { ?>
			<div id="message" class="updated fade"><p><?php echo wptexturize($artist['artist_name']) .' '. __("was successfully added to the database.", "gigpress"); ?></p></div>
	<?php } elseif($addartist === FALSE) { ?>
			<div id="message" class="error fade"><p><?php _e("Something ain't right - try again?", "gigpress"); ?></p></div>
	<?php }
		unset($artist);
	}
}


// HANDLER: UPDATE AN ARTIST
// ======================


function gigpress_update_artist() {

	global $wpdb;
	
	$wpdb->show_errors();
	
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
			
	$errors = gigpress_error_checking('artist');
	
	if($errors) {
		echo('<div id="message" class="error fade">');
		foreach($errors as $error)
			echo("<p>".$error."</p>");
		echo("</div>");
		$errors['editing'] = TRUE;
		return $errors;

	} else {

		$alpha = preg_replace("/^the /uix", "", strtolower($_POST['artist_name']));
		$artist = array(
			'artist_name' => gigpress_db_in($_POST['artist_name']),
			'artist_alpha' => gigpress_db_in($alpha),
			'artist_url' => gigpress_db_in($_POST['artist_url'], FALSE)
		);
		$format = array('%s', '%s', '%s');
		$where = array('artist_id' => absint($_POST['artist_id']));
		$updateartist = $wpdb->update(GIGPRESS_ARTISTS, $artist, $where, $format, array('%d'));
		
		// Was the query successful?
		if($updateartist != FALSE) { ?>
			<div id="message" class="updated fade"><p><?php echo wptexturize($artist['artist_name']) .' '. __("successfully updated.", "gigpress"); ?></p></div>
	<?php } elseif($updateartist === FALSE) { ?>
			<div id="message" class="error fade"><p><?php _e("Something ain't right - try again?", "gigpress"); ?></p></div>
	<?php }
		unset($artist, $where);
	}
}


// HANDLER: DELETE AN ARTIST
// ======================


function gigpress_delete_artist() {

	global $wpdb;
		
	$wpdb->show_errors();
	
	// Check the nonce
	check_admin_referer('gigpress-action');	
	if (!gigpress_require_database_ready()) return false;
	$artist_id = absint($_GET['artist_id']);
	if (gigpress_entity_has_show_dependencies('show_artist_id', $artist_id)) return gigpress_block_dependent_entity_deletion(__('Artist', 'gigpress'));
	
	// Delete the artist
	$trashartist = $wpdb->query($wpdb->prepare("DELETE FROM ". GIGPRESS_ARTISTS ." WHERE artist_id = %d LIMIT 1", $artist_id));
	if($trashartist != FALSE) {	?>	
		<div id="message" class="updated fade"><p><?php _e("Artist successfully deleted.", "gigpress"); ?></p></div>	
	<?php } elseif($trashartist === FALSE) { ?>
		
		<div id="message" class="error fade"><p><?php _e("We ran into some trouble deleting the artist. Sorry.", "gigpress"); ?></p></div>				
	<?php }
}



// HANDLER: UNDO DELETING SOMETHING
// ======================

function gigpress_tour_restore_should_fail($point) {
	return (bool) apply_filters('gigpress_tour_restore_failure_point', false, $point);
}

function gigpress_undo($type) {

	global $wpdb;
	$wpdb->show_errors();
	
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	
	if($type == "show") {
		
		$show_ids = explode(',', $_REQUEST['show_id']);
		
		if(count($show_ids) > 1) {
			// We're restoring multiple shows, so we santiize each show_id individually
			$shows = array();
			foreach($show_ids as $show) {
				$shows[] = $wpdb->prepare('%d', $show);
			}
			$shows = implode(',', $shows);
		} else {
			$shows = $wpdb->prepare('%d', $_REQUEST['show_id']);
		}
					
		// Restore the show(s)
		$undo = $wpdb->query("UPDATE ".GIGPRESS_SHOWS." SET show_status = 'active' WHERE show_id IN($shows)");		
		
		if($undo != FALSE) { ?>
			<div id="message" class="updated fade">
				<p><?php _e("Show(s) successfully restored.", "gigpress"); ?></p>
			</div>
		<?php } elseif($undo === FALSE) { ?>
			<div id="message" class="error fade">
				<p><?php _e("We ran into some trouble restoring your show(s). Sorry.", "gigpress"); ?></p>
			</div>				
		<?php }
	}
	
	if($type == "tour") {
		$tour_id = absint($_GET['tour_id']);
		$restore_map = get_option('gigpress_tour_restore_map', array());
		$restore_map = is_array($restore_map) ? $restore_map : array();
		$owned_shows = isset($restore_map[$tour_id]) && is_array($restore_map[$tour_id]) ? $restore_map[$tour_id] : array();
		$pending_shows = $owned_shows;
		$restored = 0;
		$skipped = 0;

		// Restore the tour before touching its detached shows. A failed tour update
		// leaves the complete ownership record available for a safe retry.
		$where = array('tour_id' => $tour_id);
		$undo = gigpress_tour_restore_should_fail('before_tour_restore') ? false : $wpdb->update(GIGPRESS_TOURS, array('tour_status' => 'active'), $where, array('%s'), array('%d'));
		unset($where);
		$tour_restored = $undo !== false && $wpdb->get_var($wpdb->prepare('SELECT tour_status FROM ' . GIGPRESS_TOURS . ' WHERE tour_id = %d', $tour_id)) === 'active';
		if (!$tour_restored) { ?>
			<div id="message" class="error fade"><p><?php _e("We ran into some trouble restoring the tour. The affected shows remain queued for retry.", "gigpress"); ?></p></div>
		<?php return false;
		}

		foreach ($owned_shows as $show_id => $source_tour_id) {
			$show_id = absint($show_id);
			if ($show_id <= 0 || (int) $source_tour_id !== $tour_id) {
				$skipped++;
				continue;
			}
			$restore = gigpress_tour_restore_should_fail('before_show_restore') ? false : $wpdb->update(
				GIGPRESS_SHOWS,
				array('show_tour_id' => $tour_id, 'show_tour_restore' => 0),
				array('show_id' => $show_id, 'show_tour_id' => 0, 'show_tour_restore' => 1),
				array('%d', '%d'),
				array('%d', '%d', '%d')
			);
			$current = $wpdb->get_row($wpdb->prepare('SELECT show_tour_id, show_tour_restore FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $show_id), ARRAY_A);
			if ($restore !== false && $current && (int) $current['show_tour_id'] === $tour_id && (int) $current['show_tour_restore'] === 0) {
				unset($pending_shows[$show_id]);
				$restored++;
			} elseif (!$current || (int) $current['show_tour_id'] !== 0 || (int) $current['show_tour_restore'] !== 1) {
				// Deleted and deliberately reassigned rows can no longer be restored by this undo.
				unset($pending_shows[$show_id]);
				$skipped++;
			} else {
				// The row remains detached, so keep its recovery mapping for another attempt.
				$skipped++;
			}
		}
		if ($pending_shows) $restore_map[$tour_id] = $pending_shows;
		else unset($restore_map[$tour_id]);
		if ($restore_map) {
			update_option('gigpress_tour_restore_map', $restore_map);
			$map_stored = get_option('gigpress_tour_restore_map', array());
		} else {
			delete_option('gigpress_tour_restore_map');
			$map_stored = get_option('gigpress_tour_restore_map', false);
		}
		if (($restore_map && (!is_array($map_stored) || $map_stored !== $restore_map)) || (!$restore_map && $map_stored !== false)) { ?>
			<div id="message" class="error fade"><p><?php _e("The tour was restored, but its show recovery map could not be updated safely. Please retry.", "gigpress"); ?></p></div>
		<?php return false;
		}

		if($tour_restored) { ?>
			<div id="message" class="updated fade"><p><?php _e("Tour successfully restored from the database.", "gigpress"); echo ' ' . sprintf(__('%d show(s) restored; %d skipped.', 'gigpress'), $restored, $skipped); ?></p></div>
		<?php }
	}
}


// HANDLER: IMPORT FROM CSV
// ========================

function gigpress_import() {

	// Deep breath
	
	global $wpdb, $gpo;
	
	// We've just uploaded a file to import
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;
	$upload = wp_upload_bits( $_FILES['gp_import']['name'], null, file_get_contents($_FILES['gp_import']['tmp_name']) );
	
	if (empty($upload['error'])) {
		// The file was uploaded, so let's try and parse the mofo
		require_once(WP_PLUGIN_DIR . '/gigpress/lib/parsecsv.lib.php');
		// This is under MIT license, which ain't GNU, but was else is new? Don't tell on me!
		$csv = new parseCSV();
		$csv->parse($upload['file']);
		
		if($csv->data) {
				
			// Looks like we parsed something
			$inserted = $skipped = $duplicates = $errors = array();
			
			foreach($csv->data as $key => $show) {				
				// Check to see if we have this artist
				$artist_exists = $wpdb->get_var(
					$wpdb->prepare("SELECT artist_id FROM " . GIGPRESS_ARTISTS . " WHERE artist_name = '%s'", $show['Artist'])
				);
								
				if(!empty($show['Tour'])) {
					// Check to see if we have this tour
					$tour_exists = $wpdb->get_var(
						$wpdb->prepare("SELECT tour_id FROM " . GIGPRESS_TOURS . " WHERE tour_name = '%s' AND tour_status = 'active'", $show['Tour'])
					);
					if(empty($tour_exists)) {
						// Can't find a tour with this name, so we'll have to create it
						$new_tour = array('tour_name' => gigpress_db_in($show['Tour']));
						$wpdb->insert(GIGPRESS_TOURS, $new_tour, '%s');
						$show['tour_id'] = $wpdb->insert_id;
					} else {
						$show['tour_id'] = $tour_exists;
					}
				}
				else
				{
					$show['tour_id'] = 0;
				}

				if(empty($artist_exists)) {
					// Can't find an artist with this name, so we'll have to create them
					$alpha = preg_replace("/^the /uix", "", strtolower($show['Artist']));
					$new_artist = array(
						'artist_name' => gigpress_db_in($show['Artist']),
						'artist_alpha' => gigpress_db_in($alpha),
						'artist_url' => gigpress_db_in(@$show['Artist URL'], FALSE)
					);
					$wpdb->insert(GIGPRESS_ARTISTS, $new_artist, '%s');
					$show['artist_id'] = $wpdb->insert_id;
				} else {
					$show['artist_id'] = $artist_exists;
				}
				
				// Make sure we now have an artist
				if(!empty($show['artist_id']))
				{
					// Check to see if we have this venue
					$venue_exists = $wpdb->get_var(
						$wpdb->prepare("SELECT venue_id FROM " . GIGPRESS_VENUES . " WHERE venue_name = '%s' AND venue_city = '%s' AND venue_country = '%s'", $show['Venue'], $show['City'], $show['Country'])
					);
					if(empty($venue_exists))
					{
						// Can't find a venue with this name, so we'll have to create it
						$new_venue = array(
							'venue_name' => gigpress_db_in(@$show['Venue']),
							'venue_address' => gigpress_db_in(@$show['Address']),
							'venue_city' => gigpress_db_in(@$show['City']),
							'venue_state' => gigpress_db_in(@$show['State']),
							'venue_postal_code' => gigpress_db_in(@$show['Postal code']),
							'venue_country' => gigpress_db_in(@$show['Country']),
							'venue_url' => gigpress_db_in(@$show['Venue URL'], FALSE),
							'venue_phone' => gigpress_db_in(@$show['Venue phone'])
						);
						$wpdb->insert(GIGPRESS_VENUES, $new_venue, '%s');
						$show['venue_id'] = $wpdb->insert_id;
					} else {
						$show['venue_id'] = $venue_exists;
					}
					
					// Make sure we now have a venue
					if(!empty($show['venue_id']))
					{
						if($show['Time'] == FALSE) $show['Time'] = '00:00:01';
			
						if($wpdb->get_var(
							$wpdb->prepare(
								"SELECT count(*) FROM " . GIGPRESS_SHOWS . " WHERE show_artist_id = '%d' AND show_date = '%s' AND show_time = '%s' AND show_venue_id = '%d' AND show_status != 'deleted'",
								$show['artist_id'],
								$show['Date'],
								$show['Time'],
								$show['venue_id']
								)
							) > 0) {
							// It's a duplicate, so log it and move on
							$duplicates[] = $show;
						} else {
							if($show['End date'] == FALSE) {
								$show['show_multi'] = 0; $show['End date'] = $show['Date'];
							} else {
								$show['show_multi'] = 1;
							}
							
							$new_show = array(
								'show_date' => $show['Date'],
								'show_time' => $show['Time'],
								'show_multi' => $show['show_multi'],
								'show_expire' => $show['End date'],
								'show_artist_id' => $show['artist_id'],
								'show_venue_id' => $show['venue_id'],
								'show_tour_id' => $show['tour_id'],
								'show_ages' => gigpress_db_in(@$show['Admittance']),
								'show_price' => gigpress_db_in(@$show['Price']),
								'show_tix_url' => gigpress_db_in(@$show['Ticket URL'], FALSE),
								'show_tix_phone' => gigpress_db_in(@$show['Ticket phone']),
								'show_external_url' => gigpress_db_in(@$show['External URL']),
								'show_notes' => gigpress_db_in(@$show['Notes'], FALSE),
								'show_status' => (!empty($show['Status'])) ? gigpress_db_in($show['Status']) : 'active',
								'show_related' => '0'
							);
							
							// Are we importing related post IDs?
							if(isset($_POST['include_related']) && $_POST['include_related'] = 'y') {
								$new_show['show_related'] = @$show['Related ID'];
							}
							
							$format = array('%s','%s','%d','%s','%d','%d','%d','%s','%s','%s','%s','%s', '%s', '%d', '%d');
							
							$import = $wpdb->insert(GIGPRESS_SHOWS, $new_show, $format);
							
							if($import != FALSE) {
								$inserted[] = $show;
							} else {
								$show['error'] = __("error importing show", "gigpress");
								$skipped[] = $show;
							}
						}						
					}
					else
					{
						// No venue
						$show['error'] = __("error importing venue", "gigpress");
						$skipped[] = $show;
					}				
				}
				else
				{
					// No artist
					$show['error'] = __("error importing artist", "gigpress");
					$skipped[] = $show;
				}
				
			} // end foreach import

			if(!empty($skipped)) {
				echo('<h4 class="error">' . count($skipped) . ' ' . __("shows were skipped due to errors", "gigpress") . '.</h4>');
				echo('<ul class="ul-square">');
				foreach($skipped as $key => $show) {
					echo('<li>' . wptexturize($show['Artist']) . ' ' . __("in", "gigpress") . ' ' . wptexturize($show['City']) . ' ' . __("at", "gigpress") . ' ' . wptexturize($show['Venue']) . ' ' . __("on", "gigpress") . ' ' .  mysql2date($gpo['date_format'], $show['Date']) . ' <strong>('.$show['error'].')</strong></li>'); 
				}
				echo('</ul>');
			}
			
			if(!empty($duplicates)) {
				echo('<h4 class="error">' . count($duplicates) . ' ' . __("shows were skipped as they were deemed duplicates", "gigpress") . '.</h4>');
				echo('<ul class="ul-square">');
				foreach($duplicates as $key => $show) {
					echo('<li>' . wptexturize($show['Artist']) . ' ' . __("in", "gigpress") . ' ' . wptexturize($show['City']) . ' ' . __("at", "gigpress") . ' ' . wptexturize($show['Venue']) . ' ' . __("on", "gigpress") . ' ' .  mysql2date($gpo['date_format'], $show['Date']) . '</li>'); 
				}
				echo('</ul>');
			}	
			
			if(!empty($inserted)) {
				echo('<h4 class="updated">' . count($inserted) . ' ' . __("shows were successfully imported", "gigpress") . '.</h4>');
				echo('<ul class="ul-square">');
				foreach($inserted as $key => $show) {
					echo('<li>' . wptexturize($show['Artist']) . ' ' . __("in", "gigpress") . ' ' . wptexturize($show['City']) . ' ' . __("at", "gigpress") . ' ' . wptexturize($show['Venue']) . ' ' . __("on", "gigpress") . ' ' .  mysql2date($gpo['date_format'], $show['Date']) . '</li>'); 
				}
				echo('</ul>');
			}								
			
		} else {
			// The file uploaded, but there were no results from the parse
			echo('<div id="message" class="error fade"><p>' . __("Sorry, but there was an error parsing your file. Maybe double-check your formatting and file type?", "gigpress") . '.</p></div>');
		
		}
		
		// Bye-bye
		unlink($upload['file']);
	
	} else {
		// The upload failed
		echo('<div id="message" class="error fade"><p>' . __("Sorry, but there was an error uploading", "gigpress") . ' <strong>' . $_FILES['gp_import']['name'] . '</strong>: ' . $upload['error'] . '.</p></div>');
	}

}


// HANDLER: EMPTY TRASH
// ======================

function gigpress_empty_trash() {

	global $wpdb;
	$wpdb->show_errors();
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;

	$trashshows = $wpdb->query("DELETE FROM ". GIGPRESS_SHOWS ." WHERE show_status = 'deleted'");
	$trashtours = $wpdb->query("DELETE FROM ". GIGPRESS_TOURS ." WHERE tour_status = 'deleted'");

	if($trashshows || $trashtours) { ?>
		<div id="message" class="updated fade"><p><?php _e("All shows and tours in the trash have been permanently deleted.", "gigpress"); ?></p></div>
	<?php } else { ?>
		<div id="message" class="error fade"><p><?php _e("We ran into some trouble emptying the trash. Sorry.", "gigpress"); ?></p></div>				
	<?php }
	
}


// MAP TOURS TO ARTISTS
// (useful for some migrations from 1.4.x to 2.0)
// ==============================================

function gigpress_map_tours_to_artists() {

	global $wpdb;
	check_admin_referer('gigpress-action');
	if (!gigpress_require_database_ready()) return false;

	$tours = $wpdb->get_results("SELECT tour_name, tour_id FROM " . GIGPRESS_TOURS . " WHERE tour_status = 'active'");
	if($tours) {
		foreach($tours as $tour) {
			$insert = $wpdb->insert(GIGPRESS_ARTISTS, array('artist_name' => $tour->tour_name));
			$update = $wpdb->update(GIGPRESS_SHOWS, array('show_artist_id' => $wpdb->insert_id, 'show_tour_id' => 0), array('show_tour_id' => $tour->tour_id));
			$delete = $wpdb->query("DELETE FROM " . GIGPRESS_TOURS . " WHERE tour_id = " . $tour->tour_id . " LIMIT 1");
		}
		if($insert && $update && $delete) {
			echo('<div id="message" class="updated fade"><p>' . __("All tours have been migrated into artists.", "gigpress") . '</p></div>');
		} else {
			echo('<div id="message" class="error fade"><p>' . __("There was an error migrating tours to artists. Sorry.", "gigpress") . '</p></div>');

		}
	} else {
		echo('<div id="message" class="error fade"><p>' . __("There were no tours to migrate.", "gigpress") . '</p></div>');
	}

}

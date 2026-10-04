<?php

/* Helpers are local to the existing show form, using destination-specific escaping. */
function gigpress_entry_error_attributes($field, $errors, $help = '') {
	$descriptions = $help ? array($help) : array();
	if (isset($errors[$field])) $descriptions[] = $field . '-error';
	echo isset($errors[$field]) ? ' aria-invalid="true"' : '';
	if ($descriptions) echo ' aria-describedby="' . esc_attr(implode(' ', $descriptions)) . '"';
}

function gigpress_entry_field_error($field, $errors) {
	if (isset($errors[$field])) echo '<p id="' . esc_attr($field) . '-error" class="gigpress-field-error">' . esc_html($errors[$field]) . '</p>';
}

function gigpress_entry_options($options, $value) {
	$value = (string) $value;
	if ($value !== '' && !array_key_exists($value, $options)) $options[$value] = $value;
	foreach ($options as $key => $label) {
		echo '<option value="' . esc_attr($key) . '"' . selected((string) $key, $value, false) . '>' . esc_html($label) . '</option>';
	}
}

function gigpress_entry_date($key, $value, $state, $errors) {
	$valid = gigpress_show_calendar_date($value) !== false;
	$replacement = !$valid || isset($state['replace_' . $key]) || isset($state[$key . '_picker']);
	echo '<input type="' . ($valid ? 'date' : 'text') . '" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '"';
	gigpress_entry_error_attributes($key, $errors, $key . '-help');
	echo ' />';
	echo '<p id="' . esc_attr($key) . '-help" class="description">' . esc_html__('Use YYYY-MM-DD. The received value can be corrected here.', 'gigpress') . '</p>';
	if ($replacement) {
		echo '<p><label for="' . esc_attr($key . '_picker') . '">' . esc_html__('Choose a replacement date', 'gigpress') . '</label> ';
		$picker = $state[$key . '_picker'] ?? '';
		if ($picker !== '' && !gigpress_show_calendar_date($picker)) {
			/* An invalid received picker value must remain editable too. */
			echo '<input type="text"';
		} else echo '<input type="date"';
		echo ' id="' . esc_attr($key . '_picker') . '" name="' . esc_attr($key . '_picker') . '" value="' . esc_attr($picker) . '"';
		gigpress_entry_error_attributes($key . '_picker', $errors, $key . '-help');
		echo ' /></p>';
		gigpress_entry_field_error($key . '_picker', $errors);
		echo '<p><label for="' . esc_attr('replace_' . $key) . '"><input type="checkbox" id="' . esc_attr('replace_' . $key) . '" name="' . esc_attr('replace_' . $key) . '" value="1"' . checked($state['replace_' . $key] ?? '', '1', false);
		gigpress_entry_error_attributes('replace_' . $key, $errors, $key . '-help');
		echo ' /> ' . esc_html__('Use the replacement date when saving', 'gigpress') . '</label></p>';
		gigpress_entry_field_error('replace_' . $key, $errors);
	}
	gigpress_entry_field_error($key, $errors);
}

function gigpress_add() {
	global $wpdb, $gp_countries;
	require_once __DIR__ . '/handlers.php';
	$outcome = null;
	/* A malformed action still enters the validation path without coercing its value. */
	if (isset($_POST['gpaction'])) $outcome = is_scalar($_POST['gpaction']) && $_POST['gpaction'] === 'update' ? gigpress_update_show() : gigpress_add_show();
	$gpo = get_option('gigpress_settings');
	/* Preserve the existing welcome dismissal with the same guarded write boundary. */
	if (isset($_GET['gpaction']) && is_scalar($_GET['gpaction']) && $_GET['gpaction'] === 'killwelcome'
		&& current_user_can($gpo['user_level']) && isset($_GET['_gpwelcome_nonce']) && is_scalar($_GET['_gpwelcome_nonce'])
		&& wp_verify_nonce(wp_unslash($_GET['_gpwelcome_nonce']), 'gigpress-dismiss-welcome')) {
		$readiness = gigpress_db_bootstrap();
		if (($readiness['status'] ?? '') === 'ready') {
			$gpo['welcome'] = 'no';
			update_option('gigpress_settings', $gpo);
		}
	}
	$defaults = array('show_date' => $gpo['default_date'], 'show_end_date' => $gpo['default_date'], 'gp_hh' => 'na', 'gp_min' => 'na', 'show_multi' => '0',
		'show_artist_id' => (string) ($gpo['default_artist'] ?? 'new'), 'show_venue_id' => (string) ($gpo['default_venue'] ?? ''),
		'show_tour_id' => (string) ($gpo['default_tour'] ?? '0'), 'show_related' => !empty($gpo['autocreate_post']) ? 'new' : '0',
		'venue_country' => $gpo['default_country'], 'show_related_title' => $gpo['default_title'], 'show_related_date' => $gpo['related_date'],
		'show_status' => 'active', 'show_ages' => $gpo['default_ages'] ?? 'Not sure');
	$time = explode(':', $gpo['default_time']);
	if (($time[2] ?? '') !== '01') {
		$defaults['gp_hh'] = $time[0] ?? 'na';
		$defaults['gp_min'] = $time[1] ?? 'na';
	}
	$state = $defaults;
	$mode = 'add';
	$id = 0;
	$errors = array();
	if ($outcome !== null) {
		$mode = $outcome['mode'];
		if ($outcome['status'] !== 'saved' || $mode === 'update') {
			$state = array_merge($defaults, $outcome['raw_state']);
			$state['show_date'] = $outcome['raw_state']['show_date'] ?? gigpress_show_received_date($outcome['raw_state']);
			$state['show_end_date'] = $outcome['raw_state']['show_end_date'] ?? gigpress_show_received_date($outcome['raw_state'], true);
			$id = $outcome['show_id'] ?? 0;
			$errors = $outcome['field_errors'];
		}
		if ($outcome['status'] !== 'saved') gigpress_show_outcome_notice($outcome);
	} elseif (isset($_GET['gpaction']) && is_scalar($_GET['gpaction']) && in_array($_GET['gpaction'], array('edit', 'copy'), true)) {
		$requested = is_scalar($_GET['show_id'] ?? null) ? (string) $_GET['show_id'] : '';
		$row = preg_match('/\A[1-9][0-9]*\z/', $requested) ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $requested), ARRAY_A) : null;
		if ($row) {
			$state = array_merge($defaults, $row);
			$state['show_end_date'] = $row['show_expire'];
			$time = explode(':', $row['show_time']);
			$state['gp_hh'] = ($time[2] ?? '') === '01' ? 'na' : $time[0];
			$state['gp_min'] = ($time[2] ?? '') === '01' ? 'na' : $time[1];
			if ($_GET['gpaction'] === 'edit') { $mode = 'update'; $id = (int) $row['show_id']; }
		} else {
			gigpress_show_outcome_notice(array('status' => 'failed', 'field_errors' => array(), 'system_errors' => array(__('This show could not be loaded. Return to the show list and open it again.', 'gigpress'))));
		}
	}
	$artists = array('' => __('Select an artist', 'gigpress'), 'new' => __('Add a new artist', 'gigpress'));
	foreach (fetch_gigpress_artists() ?: array() as $artist) $artists[$artist->artist_id] = $artist->artist_name;
	$venues = array('' => __('Select a venue', 'gigpress'), 'new' => __('Add a new venue', 'gigpress'));
	foreach (fetch_gigpress_venues() ?: array() as $venue) $venues[$venue->venue_id] = $venue->venue_name . ' (' . $venue->venue_city . ')';
	$tours = array('0' => __('No', 'gigpress'), 'new' => __('Add a new tour', 'gigpress'));
	foreach (fetch_gigpress_tours() ?: array() as $tour) $tours[$tour->tour_id] = $tour->tour_name;
	$posts = array('0' => __('None', 'gigpress'), 'new' => __('Add a new post', 'gigpress'));
	$entries = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status IN ('publish', 'future') AND post_type != 'page' ORDER BY post_date DESC LIMIT 500", ARRAY_A);
	foreach ($entries as $post) $posts[$post['ID']] = $post['post_title'];
	if (ctype_digit((string) $state['show_related']) && (int) $state['show_related'] > 0 && !isset($posts[$state['show_related']])) {
		$post = get_post((int) $state['show_related']);
		if ($post) $posts[$post->ID] = $post->post_title;
	}
	?>
	<div class="wrap gigpress gigpress-entry">
	<h1 id="gigpress-entry-heading"><?php echo esc_html($mode === 'update' ? __('Edit this show', 'gigpress') : __('Add a show', 'gigpress')); ?></h1>
	<?php if (($gpo['welcome'] ?? '') === 'yes') { ?><div class="notice notice-info"><p><?php echo esc_html__('Welcome to GigPress! Display shows by adding [gigpress_shows] to a page or post.', 'gigpress'); ?> <a href="https://gigpress.com/docs/"><?php esc_html_e('Documentation', 'gigpress'); ?></a> <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gigpress/gigpress.php&gpaction=killwelcome'), 'gigpress-dismiss-welcome', '_gpwelcome_nonce')); ?>"><?php esc_html_e("Don't show this again", 'gigpress'); ?></a></p></div><?php } ?>
	<form id="show_form" tabindex="-1" method="post" novalidate action="<?php echo esc_url(admin_url('admin.php?page=gigpress/gigpress.php')); ?>">
	<?php wp_nonce_field('gigpress-action'); ?>
	<input type="hidden" id="gpaction" name="gpaction" value="<?php echo esc_attr($mode); ?>" />
	<?php if ($mode === 'update') { ?><input type="hidden" id="show_id" name="show_id" value="<?php echo esc_attr($id); ?>" /><?php } ?>
	<?php foreach (($outcome['created_ids'] ?? array()) as $kind => $createdId) { ?><input type="hidden" name="<?php echo esc_attr('created_' . $kind . '_id'); ?>" value="<?php echo esc_attr($createdId); ?>" /><?php } ?>
	<?php gigpress_entry_field_error('gpaction', $errors); gigpress_entry_field_error('show_id', $errors); ?>
	<table class="form-table gp-table"><tbody>
	<tr class="gigpress-entry-section"><th colspan="2"><h2><?php esc_html_e('Dates and time', 'gigpress'); ?></h2></th></tr>
	<tr><th scope="row"><label for="show_date"><?php esc_html_e('Date', 'gigpress'); ?> <span class="gp-required">*</span></label></th><td><?php gigpress_entry_date('show_date', $state['show_date'], $state, $errors); ?></td></tr>
	<tr><th scope="row"><label for="gp_hh"><?php esc_html_e('Time (optional)', 'gigpress'); ?></label></th><td>
	<select name="gp_hh" id="gp_hh"<?php gigpress_entry_error_attributes('gp_hh', $errors, 'gigpress-time-help'); ?>>
	<?php $hours = array('na' => __('Not specified', 'gigpress')); for ($hour = 0; $hour < 24; $hour++) $hours[sprintf('%02d', $hour)] = !empty($gpo['alternate_clock']) ? sprintf('%02d', $hour) : sprintf('%d %s', $hour % 12 ?: 12, $hour < 12 ? __('AM', 'gigpress') : __('PM', 'gigpress')); gigpress_entry_options($hours, $state['gp_hh']); ?>
	</select><label for="gp_min"><?php esc_html_e('Minute', 'gigpress'); ?></label>
	<select name="gp_min" id="gp_min"<?php gigpress_entry_error_attributes('gp_min', $errors, 'gigpress-time-help'); ?>><?php $minutes = array('na' => __('Not specified', 'gigpress')); for ($minute = 0; $minute < 60; $minute++) $minutes[sprintf('%02d', $minute)] = sprintf('%02d', $minute); gigpress_entry_options($minutes, $state['gp_min']); ?></select>
	<p id="gigpress-time-help" class="description"><?php esc_html_e('Choose Not specified for an event without a time. Choose an hour and minute for a timed event; an unspecified minute means :00.', 'gigpress'); ?></p>
	<?php gigpress_entry_field_error('gp_hh', $errors); gigpress_entry_field_error('gp_min', $errors); ?>
	<p><label for="show_multi"><input type="checkbox" id="show_multi" name="show_multi" value="1"<?php checked($state['show_multi'], '1'); gigpress_entry_error_attributes('show_multi', $errors, 'gigpress-end-date-help'); ?> /> <?php esc_html_e('This is a multi-day event', 'gigpress'); ?></label><?php gigpress_entry_field_error('show_multi', $errors); ?></p></td></tr>
	<tr id="expire"<?php if ((string) $state['show_multi'] !== '1') echo ' class="gigpress-inactive"'; ?>><th scope="row"><label for="show_end_date"><?php esc_html_e('End date — last day of the event', 'gigpress'); ?></label></th><td><?php gigpress_entry_date('show_end_date', $state['show_end_date'], $state, $errors); ?><p id="gigpress-end-date-help" class="description"><?php esc_html_e('The event remains upcoming through its last date under GigPress’s existing daily cutoff. This end date is used only for a multi-day event.', 'gigpress'); ?></p></td></tr>
	<tr class="gigpress-entry-section"><th colspan="2"><h2><?php esc_html_e('Artists, venues and related content', 'gigpress'); ?></h2></th></tr>
	<?php
	$groups = array(
		'show_artist_id' => array(__('Artist', 'gigpress'), $artists, array('artist_name' => __('Artist name', 'gigpress'), 'artist_url' => __('Artist URL', 'gigpress'))),
		'show_venue_id' => array(__('Venue', 'gigpress'), $venues, array('venue_name' => __('Venue name', 'gigpress'), 'venue_address' => __('Venue address', 'gigpress'), 'venue_city' => __('Venue city', 'gigpress'), 'venue_state' => __('Venue state/province', 'gigpress'), 'venue_postal_code' => __('Venue postal code', 'gigpress'), 'venue_country' => __('Venue country', 'gigpress'), 'venue_url' => __('Venue website', 'gigpress'), 'venue_phone' => __('Venue phone', 'gigpress'))),
		'show_tour_id' => array(__('Part of a tour?', 'gigpress'), $tours, array('tour_name' => __('Tour name', 'gigpress'))),
		'show_related' => array(__('Related post', 'gigpress'), $posts, array('show_related_title' => __('Related post title', 'gigpress'))));
	foreach ($groups as $field => $group) {
		list($label, $options, $extra) = $group;
		echo '<tr><th scope="row"><label for="' . esc_attr($field) . '">' . esc_html($label) . '</label></th><td><select class="can-add-new" id="' . esc_attr($field) . '" name="' . esc_attr($field) . '"';
		gigpress_entry_error_attributes($field, $errors);
		echo '>'; gigpress_entry_options($options, $state[$field]); echo '</select>'; gigpress_entry_field_error($field, $errors); echo '</td></tr></tbody>';
		echo '<tbody id="' . esc_attr($field . '_new') . '" class="gigpress-addition' . ($state[$field] === 'new' ? '' : ' gigpress-inactive') . '">';
		foreach ($extra as $key => $text) {
			echo '<tr><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($text) . '</label></th><td>';
			if ($key === 'venue_country') {
				echo '<select name="venue_country" id="venue_country"'; gigpress_entry_error_attributes($key, $errors); echo '>'; gigpress_entry_options($gp_countries, $state[$key]); echo '</select>';
			} else {
				echo '<input type="text" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($state[$key] ?? '') . '"'; gigpress_entry_error_attributes($key, $errors); echo ' />';
			}
			gigpress_entry_field_error($key, $errors);
			if ($key === 'show_related_title') {
				echo '<p class="description">' . esc_html__('Available placeholders: %date%, %long_date%, %artist%, %city%, %venue%.', 'gigpress') . '</p>';
				echo '<fieldset id="show_related_date" tabindex="-1"'; gigpress_entry_error_attributes('show_related_date', $errors); echo '><legend>' . esc_html__('Publish related post', 'gigpress') . '</legend>';
				foreach (array('now' => __('Publish now', 'gigpress'), 'show' => __('Publish on show date', 'gigpress')) as $choice => $text) {
					echo '<label for="show_related_date_' . esc_attr($choice) . '"><input type="radio" id="show_related_date_' . esc_attr($choice) . '" name="show_related_date" value="' . esc_attr($choice) . '"' . checked($state['show_related_date'], $choice, false);
					gigpress_entry_error_attributes('show_related_date', $errors);
					echo ' /> ' . esc_html($text) . '</label> ';
				}
				gigpress_entry_field_error('show_related_date', $errors);
				echo '</fieldset>';
			}
			echo '</td></tr>';
		}
		echo '</tbody><tbody>';
	}
	$ages = array('Not sure' => __('Not sure', 'gigpress'));
	echo '<tr class="gigpress-entry-section"><th colspan="2"><h2>' . esc_html__('Tickets and other details', 'gigpress') . '</h2></th></tr>';
	foreach (explode('|', $gpo['age_restrictions']) as $age) $ages[trim($age)] = trim($age);
	foreach (array('show_status' => array(__('Status', 'gigpress'), array('active' => __('Active', 'gigpress'), 'soldout' => __('Sold Out', 'gigpress'), 'cancelled' => __('Cancelled', 'gigpress'))), 'show_ages' => array(__('Admittance', 'gigpress'), $ages)) as $field => $config) {
		echo '<tr><th scope="row"><label for="' . esc_attr($field) . '">' . esc_html($config[0]) . '</label></th><td><select name="' . esc_attr($field) . '" id="' . esc_attr($field) . '"'; gigpress_entry_error_attributes($field, $errors); echo '>'; gigpress_entry_options($config[1], $state[$field]); echo '</select>'; gigpress_entry_field_error($field, $errors); echo '</td></tr>';
	}
	foreach (array('show_price' => __('Price (include currency symbol)', 'gigpress'), 'show_tix_url' => __('Ticket URL', 'gigpress'), 'show_tix_phone' => __('Ticket phone', 'gigpress'), 'show_external_url' => __('External URL', 'gigpress'), 'show_notes' => __('Notes', 'gigpress')) as $field => $label) {
		echo '<tr><th scope="row"><label for="' . esc_attr($field) . '">' . esc_html($label) . '</label></th><td>';
		if ($field === 'show_notes') {
			echo '<textarea name="show_notes" id="show_notes" rows="5" cols="45"'; gigpress_entry_error_attributes($field, $errors); echo '>' . esc_textarea($state[$field] ?? '') . '</textarea>';
		} else {
			echo '<input type="text" name="' . esc_attr($field) . '" id="' . esc_attr($field) . '" value="' . esc_attr($state[$field] ?? '') . '"'; gigpress_entry_error_attributes($field, $errors); echo ' />';
		}
		gigpress_entry_field_error($field, $errors);
		echo '</td></tr>';
	}
	?>
	</tbody></table>
	<p class="submit"><input type="submit" class="button button-primary" value="<?php echo esc_attr($mode === 'update' ? __('Update show', 'gigpress') : __('Add show', 'gigpress')); ?>" /> <?php if ($mode === 'update') { ?><a href="<?php echo esc_url(admin_url('admin.php?page=gigpress-shows')); ?>"><?php esc_html_e('Cancel', 'gigpress'); ?></a><?php } ?></p>
	</form></div>
	<?php
	return $outcome;
}

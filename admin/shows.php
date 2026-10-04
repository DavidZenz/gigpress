<?php

function gigpress_admin_shows() {
	require_once __DIR__ . '/handlers.php';
	global $wpdb;
	$invalid = array();
	$state = gigpress_list_state(wp_unslash($_GET), true, $invalid);
	ob_start();
	$outcome = null;
	if (($_REQUEST['gpaction'] ?? null) === 'delete') {
		$outcome = gigpress_delete_show();
		if (($outcome['status'] ?? '') === 'preview') { echo ob_get_clean(); return $outcome; }
		if (isset($outcome['state'])) $state = $outcome['state'];
	}
	if (($_GET['gpaction'] ?? null) === 'undo') gigpress_undo('show');
	if (($_POST['gpaction'] ?? null) === 'update') gigpress_update_show();
	if (($_GET['gpaction'] ?? null) === 'trash') gigpress_empty_trash();
	$feedback = ob_get_clean();
	$data = gigpress_list_query($state);
	$pagination = array('output' => '', 'offset' => $data['offset'], 'records_per_page' => $state['limit'], 'total_pages' => $data['total_pages']);
	if ($data['total_pages'] > 1) {
		// The shared helper reads GET; supply the validated, clamped page during this call.
		$oldGet = $_GET;
		$_GET = array_merge($_GET, $state);
		$args = array_merge(array('page' => 'gigpress-shows'), $state);
		unset($args['gp-page']);
		$built = gigpress_admin_pagination($data['count'], $state['limit'], $args);
		$_GET = $oldGet;
		$pagination['output'] = is_array($built) ? $built['output'] : '';
	}
	$reset = array_merge($state, array('artist_id' => -1, 'venue_id' => -1, 'tour_id' => -1, 'gp-page' => 1));
	$resetUrl = gigpress_list_url($reset, array('reset_filters' => '1'));
	?>
	<div class="wrap gigpress">
		<?php screen_icon('gigpress'); ?>
		<h2><?php esc_html_e('Shows', 'gigpress'); ?></h2>
		<?php echo $feedback; ?>
		<?php if ($invalid) : ?><div class="notice notice-warning" role="status"><p><?php esc_html_e('Invalid list choices were replaced with safe defaults. Check the filters and retry.', 'gigpress'); ?></p></div><?php endif; ?>
		<ul class="subsubsub">
		<?php foreach (array('all' => __('All', 'gigpress'), 'upcoming' => __('Upcoming', 'gigpress'), 'past' => __('Past', 'gigpress')) as $scope => $label) {
			$where = "show_status != 'deleted'";
			if ($scope !== 'all') $where .= $wpdb->prepare(' AND show_expire ' . ($scope === 'past' ? '<' : '>=') . ' %s', GIGPRESS_NOW);
			$count = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . GIGPRESS_SHOWS . ' WHERE ' . $where);
			echo '<li><a href="' . esc_url(gigpress_list_url($state, array('scope' => $scope))) . '"' . ($state['scope'] === $scope ? ' class="current" aria-current="page"' : '') . '>' . esc_html($label) . '</a> <span class="count">(' . esc_html($count) . ')</span>' . ($scope !== 'past' ? ' | ' : '') . '</li>';
		} ?>
		</ul>
		<div class="tablenav">
			<form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="get" class="alignleft">
				<input type="hidden" name="page" value="gigpress-shows" />
				<input type="hidden" name="scope" value="<?php echo esc_attr($state['scope']); ?>" />
				<input type="hidden" name="gp-page" value="<?php echo esc_attr($state['gp-page']); ?>" />
				<?php
				foreach (array('artist_id' => array(__('Artist', 'gigpress'), __('View all artists', 'gigpress'), fetch_gigpress_artists(), 'artist_id', 'artist_name'),
					'tour_id' => array(__('Tour', 'gigpress'), __('View all tours', 'gigpress'), fetch_gigpress_tours(), 'tour_id', 'tour_name'),
					'venue_id' => array(__('Venue', 'gigpress'), __('View all venues', 'gigpress'), fetch_gigpress_venues(), 'venue_id', 'venue_name')) as $key => $spec) {
					$options = array(-1 => $spec[1]);
					foreach ((array) $spec[2] as $entity) $options[(int) $entity->{$spec[3]}] = $entity->{$spec[4]};
					if (!array_key_exists($state[$key], $options)) $options[$state[$key]] = sprintf(__('Selected entry #%d (not available)', 'gigpress'), $state[$key]);
					gigpress_list_select($key, $spec[0], $options, $state[$key]);
				}
				gigpress_list_select('sort', __('Sort order', 'gigpress'), array('asc' => __('Ascending', 'gigpress'), 'desc' => __('Descending', 'gigpress')), $state['sort']);
				$sizes = array(10, 25, 50, 100, 150, 200, 250, 300);
				gigpress_list_select('limit', __('Shows per page', 'gigpress'), array_combine($sizes, $sizes), $state['limit']);
				?>
				<button type="submit" class="button"><?php esc_html_e('Filter', 'gigpress'); ?></button>
				<a class="button" href="<?php echo esc_url($resetUrl); ?>"><?php esc_html_e('Reset filters', 'gigpress'); ?></a>
			</form>
			<?php echo $pagination['output']; ?><div class="clear"></div>
		</div>
		<form action="<?php echo esc_url(gigpress_list_url($state)); ?>" method="post">
			<?php wp_nonce_field('gigpress-action'); gigpress_list_state_fields($state); ?>
			<input type="hidden" name="gpaction" value="delete" /><input type="hidden" name="trash_stage" value="preview" />
			<table class="widefat">
				<?php foreach (array('thead' => 1, 'tfoot' => 2) as $section => $number) : ?>
				<<?php echo $section; ?>><tr>
					<th scope="col" class="column-cb check-column"><input id="cb-select-all-<?php echo $number; ?>" type="checkbox" /><label class="screen-reader-text" for="cb-select-all-<?php echo $number; ?>"><?php esc_html_e('Select all shows on this page', 'gigpress'); ?></label></th>
					<?php foreach (array('Date', 'Artist', 'Venue', 'City', 'Country', 'Tour', 'Actions') as $heading) echo '<th scope="col">' . esc_html__($heading, 'gigpress') . '</th>'; ?>
				</tr></<?php echo $section; ?>>
				<?php endforeach; ?>
				<tbody>
				<?php foreach ((array) $data['rows'] as $show) : $showdata = gigpress_prepare($show, 'admin'); ?>
					<tr class="<?php echo esc_attr('gigpress-' . $showdata['status']); ?>">
						<th scope="row" class="check-column"><input id="gp-select-show-<?php echo esc_attr($show->show_id); ?>" type="checkbox" name="show_id[]" value="<?php echo esc_attr($show->show_id); ?>" /><label class="screen-reader-text" for="gp-select-show-<?php echo esc_attr($show->show_id); ?>"><?php echo esc_html(sprintf(__('Select show #%d', 'gigpress'), $show->show_id)); ?></label></th>
						<td><span class="gigpress-date"><?php echo $showdata['date']; if ($showdata['end_date']) echo ' - ' . $showdata['end_date']; ?></span></td>
						<td><?php echo $showdata['artist']; ?></td><td><?php echo $showdata['venue']; ?></td>
						<td><?php echo $showdata['city']; if (!empty($showdata['state'])) echo ', ' . $showdata['state']; ?></td>
						<td><?php echo $showdata['country']; ?></td><td><?php echo $showdata['tour']; ?></td>
						<td class="gp-centre">
							<?php foreach (array('edit' => __('Edit', 'gigpress'), 'copy' => __('Copy', 'gigpress')) as $action => $label) echo '<a href="' . esc_url(gigpress_list_url($state, array('page' => 'gigpress/gigpress.php', 'gpaction' => $action, 'show_id' => $show->show_id))) . '">' . esc_html($label) . '</a> | '; ?>
							<button type="submit" class="button-link" name="trash_single_id" value="<?php echo esc_attr($show->show_id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Trash show #%d', 'gigpress'), $show->show_id)); ?>"><?php esc_html_e('Trash', 'gigpress'); ?></button>
						</td>
					</tr>
					<tr class="<?php echo esc_attr('alternate gigpress-' . $showdata['status']); ?>"><td colspan="8"><small>
						<?php
						if ($showdata['time']) echo $showdata['time'] . '. ';
						if ($showdata['price']) echo esc_html__('Price', 'gigpress') . ': ' . $showdata['price'] . '. ';
						foreach (array('admittance', 'ticket_link', 'external_link') as $key) if ($showdata[$key]) echo $showdata[$key] . '. ';
						if ($showdata['ticket_phone']) echo esc_html__('Box office', 'gigpress') . ': ' . $showdata['ticket_phone'] . '. ';
						echo $showdata['notes'] . ' ' . $showdata['related_edit']; ?>
					</small></td></tr>
				<?php endforeach; ?>
				<?php if (!$data['rows']) : ?><tr><td colspan="8"><?php esc_html_e('No shows match these filters', 'gigpress'); ?>. <a href="<?php echo esc_url($resetUrl); ?>"><?php esc_html_e('Reset filters', 'gigpress'); ?></a></td></tr><?php endif; ?>
				</tbody>
			</table>
			<div class="tablenav"><div class="alignleft">
				<button type="submit" class="button"><?php esc_html_e('Trash selected shows', 'gigpress'); ?></button>
				<?php
				$trashShows = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . GIGPRESS_SHOWS . " WHERE show_status = 'deleted'");
				$trashTours = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . GIGPRESS_TOURS . " WHERE tour_status = 'deleted'");
				if ($trashShows || $trashTours) echo '<small>' . esc_html(sprintf(__('You have %1$d shows and %2$d tours in your trash.', 'gigpress'), $trashShows, $trashTours)) . ' <a href="' . esc_url(wp_nonce_url(gigpress_list_url($state, array('gpaction' => 'trash')), 'gigpress-action')) . '">' . esc_html__('Take out the trash now', 'gigpress') . '</a>.</small>';
				?>
			</div><?php echo $pagination['output']; ?></div>
		</form>
	</div>
	<?php
	return array('status' => 'list', 'state' => $state, 'pagination' => $pagination, 'ids' => array_map(function ($show) { return (int) $show->show_id; }, (array) $data['rows']), 'action_outcome' => $outcome);
}

function gigpress_list_select($key, $label, $options, $value) {
	echo '<label for="gp-list-' . esc_attr($key) . '">' . esc_html($label) . '</label> <select id="gp-list-' . esc_attr($key) . '" name="' . esc_attr($key) . '">';
	foreach ($options as $choice => $text) echo '<option value="' . esc_attr($choice) . '"' . selected((string) $value, (string) $choice, false) . '>' . esc_html($text) . '</option>';
	echo '</select> ';
}

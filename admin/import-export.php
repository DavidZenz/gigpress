<?php

function gigpress_import_export() {
	global $gpo;
	$state = gigpress_csv_export_state($_GET);
	$selection = gigpress_csv_export_selection($state);
	$artists = fetch_gigpress_artists();
	$tours = fetch_gigpress_tours();
	$artistLabel = __('All artists', 'gigpress');
	$tourLabel = __('All tours', 'gigpress');
	foreach ((array) $artists as $artist) if ((int) $artist->artist_id === $state['artist_id']) $artistLabel = $artist->artist_name;
	foreach ((array) $tours as $tour) if ((int) $tour->tour_id === $state['tour_id']) $tourLabel = $tour->tour_name;
	$scopeLabels = array('-1' => __('All dates', 'gigpress'), 'upcoming' => __('Upcoming dates', 'gigpress'), 'past' => __('Past dates', 'gigpress'));
	?>
	<div class="wrap gigpress gp-options gp-import-export">
		<?php screen_icon('gigpress'); ?>
		<h2><?php esc_html_e('Import/Export', 'gigpress'); ?></h2>
		<section class="gp-csv-section gp-import-panel" aria-labelledby="gp-csv-import-heading">
			<h3 id="gp-csv-import-heading"><?php esc_html_e('Import', 'gigpress'); ?></h3>
			<p><?php esc_html_e('Upload a UTF-8 CSV file to import show records. Keep the 23 column names and their order unchanged.', 'gigpress'); ?></p>
			<form action="" enctype="multipart/form-data" method="post">
				<?php wp_nonce_field('gigpress-action'); ?>
				<input type="hidden" name="gpaction" value="import" />
				<label for="gp-import-file"><?php esc_html_e('CSV file', 'gigpress'); ?></label>
				<input id="gp-import-file" type="file" name="gp_import" accept=".csv,text/csv" />
				<p><label><input type="checkbox" name="include_related" value="y" /> <?php esc_html_e('Include related-post associations', 'gigpress'); ?></label></p>
				<p class="description"><?php esc_html_e('Related post IDs must identify posts on this destination site.', 'gigpress'); ?></p>
				<button type="submit" class="button button-primary"><?php esc_html_e('Import shows', 'gigpress'); ?></button>
			</form>
			<?php if (isset($_POST['gpaction']) && is_scalar($_POST['gpaction']) && wp_unslash((string) $_POST['gpaction']) === 'import') { require_once __DIR__ . '/handlers.php'; gigpress_import(); } ?>
		</section>
		<section class="gp-csv-section gp-export-panel" aria-labelledby="gp-csv-export-heading">
			<h3 id="gp-csv-export-heading"><?php esc_html_e('Export', 'gigpress'); ?></h3>
			<p><?php esc_html_e('Download matching show records as a CSV file for transfer to another GigPress installation.', 'gigpress'); ?></p>
			<?php if (isset($_GET['export_notice']) && $_GET['export_notice'] === 'no_matches') : ?><div class="notice notice-info" role="status"><p><?php esc_html_e('No shows match these export filters', 'gigpress'); ?>.</p></div><?php endif; ?>
			<?php if (isset($_GET['export_notice']) && $_GET['export_notice'] === 'error') : ?><div class="notice notice-error" role="status"><p><?php esc_html_e('The export selection could not be read. Reload the page and try again.', 'gigpress'); ?></p></div><?php endif; ?>
			<p class="description"><?php echo esc_html(sprintf(__('Artist: %1$s; Tour: %2$s; Date scope: %3$s; matching shows: %4$d.', 'gigpress'), $artistLabel, $tourLabel, $scopeLabels[$state['scope']], (int) $selection['count'])); ?></p>
			<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
				<?php wp_nonce_field('gigpress-action'); ?>
				<input type="hidden" name="action" value="gigpress_export" />
				<p><label for="gp-export-artist"><?php esc_html_e('Artist', 'gigpress'); ?></label><br />
				<select id="gp-export-artist" name="artist_id"><option value="-1" <?php selected($state['artist_id'], -1); ?>><?php esc_html_e('All artists', 'gigpress'); ?></option>
				<?php foreach ((array) $artists as $artist) echo '<option value="' . esc_attr((int) $artist->artist_id) . '" ' . selected($state['artist_id'], (int) $artist->artist_id, false) . '>' . esc_html($artist->artist_name) . '</option>'; ?></select></p>
				<p><label for="gp-export-tour"><?php esc_html_e('Tour', 'gigpress'); ?></label><br />
				<select id="gp-export-tour" name="tour_id"><option value="-1" <?php selected($state['tour_id'], -1); ?>><?php esc_html_e('All tours', 'gigpress'); ?></option>
				<?php foreach ((array) $tours as $tour) echo '<option value="' . esc_attr((int) $tour->tour_id) . '" ' . selected($state['tour_id'], (int) $tour->tour_id, false) . '>' . esc_html($tour->tour_name) . '</option>'; ?></select></p>
				<p><label for="gp-export-scope"><?php esc_html_e('Date scope', 'gigpress'); ?></label><br /><select id="gp-export-scope" name="scope">
				<?php foreach ($scopeLabels as $value => $label) echo '<option value="' . esc_attr($value) . '" ' . selected($state['scope'], $value, false) . '>' . esc_html($label) . '</option>'; ?></select></p>
				<button type="submit" class="button button-secondary"><?php esc_html_e('Download CSV', 'gigpress'); ?></button>
				<a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'gigpress-import-export', 'reset_filters' => '1'), admin_url('admin.php'))); ?>"><?php esc_html_e('Reset filters', 'gigpress'); ?></a>
			</form>
		</section>
	</div>
	<?php
}

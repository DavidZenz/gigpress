<?php

function gigpress_csv_render_import_result(array $result): string {
	$records = is_array($result['records'] ?? null) ? $result['records'] : array();
	$groups = array('imported' => array(), 'duplicate' => array(), 'rejected' => array(), 'failed' => array());
	foreach ($groups as $group => $_) if (is_array($records[$group] ?? null)) $groups[$group] = $records[$group];
	if (is_array($records['skipped'] ?? null)) foreach ($records['skipped'] as $item) $groups['duplicate'][] = $item;
	$counts = array('imported' => count($groups['imported']), 'duplicate' => count($groups['duplicate']), 'rejected' => count($groups['rejected']), 'failed' => count($groups['failed']));
	$labels = array('imported' => __('imported', 'gigpress'), 'duplicate' => __('duplicates', 'gigpress'), 'rejected' => __('rejected', 'gigpress'), 'failed' => __('failed', 'gigpress'));
	$html = '<section class="gigpress-csv-results" aria-labelledby="gp-csv-result-heading"><h4 id="gp-csv-result-heading">' . esc_html__('Import results', 'gigpress') . '</h4><p class="gp-csv-result-counts">';
	$summary = array(); foreach ($counts as $group => $count) $summary[] = $count . ' ' . $labels[$group];
	$html .= esc_html(implode(' · ', $summary)) . '</p>';
	$fileError = $result['file_error'] ?? null;
	if (is_array($fileError)) {
		$html .= '<p class="notice notice-error">' . esc_html((string) ($fileError['reason'] ?? __('The file could not be imported.', 'gigpress')));
		if (!empty($fileError['name'])) $html .= ' ' . esc_html(sprintf(__('File: %s.', 'gigpress'), (string) $fileError['name']));
		$html .= '</p>';
		if (!empty($fileError['next_step'])) $html .= '<p>' . esc_html((string) $fileError['next_step']) . '</p>';
	}
	foreach ($groups as $group => $items) {
		if (!$items) continue;
		$html .= '<details class="gp-csv-result-group gp-csv-result-' . esc_attr($group) . '"><summary>' . esc_html(ucfirst($labels[$group]) . ' (' . count($items) . ')') . '</summary><ul>';
		foreach ($items as $item) {
			$item = is_array($item) ? $item : array();
			$detail = sprintf(__('Row %1$d: %2$s', 'gigpress'), (int) ($item['row'] ?? 0), (string) ($item['identity'] ?? ''));
			if (!empty($item['field'])) $detail .= ' — ' . (string) $item['field'];
			if (!empty($item['reason'])) $detail .= ' — ' . (string) $item['reason'];
			$html .= '<li><p>' . esc_html($detail) . '</p>';
			if (!empty($item['next_step'])) $html .= '<p>' . esc_html((string) $item['next_step']) . '</p>';
			$html .= '</li>';
		}
		$html .= '</ul></details>';
	}
	$html .= '<p>' . esc_html__('Matching existing shows are not overwritten. Correct the source CSV and upload it again.', 'gigpress') . '</p>';
	$html .= '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=gigpress-shows')) . '">' . esc_html__('View shows', 'gigpress') . '</a> <a class="button" href="#gp-import-file">' . esc_html__('Upload another CSV', 'gigpress') . '</a></p></section>';
	return $html;
}

function gigpress_import_export() {
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
				<p class="description"><?php esc_html_e('Related post IDs must identify exact posts on this destination site.', 'gigpress'); ?></p>
				<button type="submit" class="button button-primary"><?php esc_html_e('Import shows', 'gigpress'); ?></button>
			</form>
			<?php
			if (isset($_POST['gpaction']) && is_scalar($_POST['gpaction']) && wp_unslash((string) $_POST['gpaction']) === 'import') {
				require_once __DIR__ . '/handlers.php';
				ob_start(); $importResult = gigpress_import(); $handlerOutput = ob_get_clean();
				if (is_array($importResult)) echo gigpress_csv_render_import_result($importResult); else echo $handlerOutput;
			}
			?>
			<details class="gp-csv-help"><summary><?php esc_html_e('CSV column reference', 'gigpress'); ?></summary>
				<p><?php esc_html_e('Keep these 23 columns in this exact order:', 'gigpress'); ?></p>
				<ol class="gp-csv-columns"><?php foreach (gigpress_csv_columns() as $column) echo '<li>' . esc_html($column) . '</li>'; ?></ol>
			</details>
		</section>
		<section class="gp-csv-section gp-export-panel" aria-labelledby="gp-csv-export-heading">
			<h3 id="gp-csv-export-heading"><?php esc_html_e('Export', 'gigpress'); ?></h3>
			<p><?php esc_html_e('Download matching show records as a CSV file for transfer to another GigPress installation.', 'gigpress'); ?></p>
			<p><?php esc_html_e('Apply filters to preview the matching show count before downloading.', 'gigpress'); ?></p>
			<p class="gp-csv-spreadsheet-note"><?php esc_html_e('Spreadsheet apps may interpret some CSV values when a file is opened directly. For exact values, import selected columns as text.', 'gigpress'); ?></p>
			<?php if (isset($_GET['export_notice']) && $_GET['export_notice'] === 'no_matches') : ?><div class="notice notice-info" role="status"><p><?php esc_html_e('No shows match these export filters', 'gigpress'); ?>.</p></div><?php endif; ?>
			<?php if (isset($_GET['export_notice']) && $_GET['export_notice'] === 'error') : ?><div class="notice notice-error" role="status"><p><?php esc_html_e('The export selection could not be read. Reload the page and try again.', 'gigpress'); ?></p></div><?php endif; ?>
			<form class="gp-csv-export-filters" action="<?php echo esc_url(admin_url('admin.php')); ?>" method="get">
				<input type="hidden" name="page" value="gigpress-import-export" />
				<p><label for="gp-export-artist"><?php esc_html_e('Artist', 'gigpress'); ?></label><br />
				<select id="gp-export-artist" name="artist_id"><option value="-1" <?php selected($state['artist_id'], -1); ?>><?php esc_html_e('All artists', 'gigpress'); ?></option>
				<?php foreach ((array) $artists as $artist) echo '<option value="' . esc_attr((int) $artist->artist_id) . '" ' . selected($state['artist_id'], (int) $artist->artist_id, false) . '>' . esc_html($artist->artist_name) . '</option>'; ?></select></p>
				<p><label for="gp-export-tour"><?php esc_html_e('Tour', 'gigpress'); ?></label><br />
				<select id="gp-export-tour" name="tour_id"><option value="-1" <?php selected($state['tour_id'], -1); ?>><?php esc_html_e('All tours', 'gigpress'); ?></option>
				<?php foreach ((array) $tours as $tour) echo '<option value="' . esc_attr((int) $tour->tour_id) . '" ' . selected($state['tour_id'], (int) $tour->tour_id, false) . '>' . esc_html($tour->tour_name) . '</option>'; ?></select></p>
				<p><label for="gp-export-scope"><?php esc_html_e('Date scope', 'gigpress'); ?></label><br /><select id="gp-export-scope" name="scope">
				<?php foreach ($scopeLabels as $value => $label) echo '<option value="' . esc_attr($value) . '" ' . selected($state['scope'], $value, false) . '>' . esc_html($label) . '</option>'; ?></select></p>
				<button type="submit" class="button button-secondary"><?php esc_html_e('Apply filters', 'gigpress'); ?></button>
			</form>
			<p class="description" role="status" aria-live="polite"><?php echo esc_html(sprintf(__('Artist: %1$s; Tour: %2$s; Date scope: %3$s; matching shows: %4$d.', 'gigpress'), $artistLabel, $tourLabel, $scopeLabels[$state['scope']], (int) $selection['count'])); ?></p>
			<form class="gp-csv-download" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
				<?php wp_nonce_field('gigpress-action'); ?>
				<input type="hidden" name="action" value="gigpress_export" />
				<input type="hidden" name="artist_id" value="<?php echo esc_attr($state['artist_id']); ?>" />
				<input type="hidden" name="tour_id" value="<?php echo esc_attr($state['tour_id']); ?>" />
				<input type="hidden" name="scope" value="<?php echo esc_attr($state['scope']); ?>" />
				<button type="submit" class="button button-secondary"><?php esc_html_e('Download CSV', 'gigpress'); ?></button>
			</form>
			<a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'gigpress-import-export', 'reset_filters' => '1'), admin_url('admin.php'))); ?>"><?php esc_html_e('Reset filters', 'gigpress'); ?></a>
			<details class="gp-csv-help"><summary><?php esc_html_e('Import spreadsheet values as text', 'gigpress'); ?></summary>
				<ol>
					<li><?php esc_html_e('In Excel, choose Data > From Text/CSV and select the downloaded file.', 'gigpress'); ?></li>
					<li><?php esc_html_e('In the preview, set columns that need exact values to Text before loading.', 'gigpress'); ?></li>
					<li><?php esc_html_e('When editing for re-import, save as UTF-8 CSV and keep the 23 headers in order.', 'gigpress'); ?></li>
				</ol>
				<p><?php esc_html_e('These steps describe Excel text import; they are not universal spreadsheet-safety guarantees. Status 0 is exported unchanged and needs manual attention.', 'gigpress'); ?></p>
			</details>
		</section>
	</div>
	<?php
}

<?php

// DB structure
$charset_collate = '';
if ( ! empty( $wpdb->charset ) )
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
if ( ! empty( $wpdb->collate ) )
	$charset_collate .= " COLLATE $wpdb->collate";

global $gp_db;
$gp_db = array();

// Note that the following columns are deprectated as of DB version 1.4,
// but cannot be dropped due to the neccessities of the upgrade process:
// show_address, show_locale, show_country, show_venue, show_venue_url, show_venue_phone
	
$gp_db[] = "CREATE TABLE " . GIGPRESS_SHOWS . " (
show_id INTEGER(4) AUTO_INCREMENT,
show_artist_id INTEGER(4) NOT NULL,
show_venue_id INTEGER(4) NOT NULL,
show_tour_id INTEGER(4) DEFAULT 0,
show_date DATE NOT NULL,
show_multi INTEGER(1),
show_time TIME NOT NULL,
show_expire DATE NOT NULL,
show_price VARCHAR(255),
show_tix_url VARCHAR(255),
show_tix_phone VARCHAR(255),
show_ages VARCHAR(255),
show_notes TEXT,
show_related BIGINT(20) DEFAULT 0,
show_status VARCHAR(32) DEFAULT 'active',
show_external_url VARCHAR(255),
show_tour_restore INTEGER(1) DEFAULT 0,
show_address VARCHAR(255),
show_locale VARCHAR(255),
show_country VARCHAR(2),
show_venue VARCHAR(255),
show_venue_url VARCHAR(255),
show_venue_phone VARCHAR(255),	
PRIMARY KEY  (show_id)
) $charset_collate";

$gp_db[] = "CREATE TABLE " . GIGPRESS_ARTISTS . " (
artist_id INTEGER(4) AUTO_INCREMENT,
artist_name VARCHAR(255) NOT NULL,
artist_alpha VARCHAR(255) NOT NULL,
artist_url VARCHAR(255),
artist_order INTEGER(4) DEFAULT 0,
PRIMARY KEY  (artist_id)
) $charset_collate";

$gp_db[] = "CREATE TABLE " . GIGPRESS_VENUES . " (
venue_id INTEGER(4) AUTO_INCREMENT,
venue_name VARCHAR(255) NOT NULL,
venue_address VARCHAR(255),
venue_city VARCHAR(255) NOT NULL,
venue_state VARCHAR(255),
venue_postal_code VARCHAR(32),
venue_country VARCHAR(2) NOT NULL,	
venue_url VARCHAR(255),
venue_phone VARCHAR(255),	
PRIMARY KEY  (venue_id)
) $charset_collate";

$gp_db[] = "CREATE TABLE " . GIGPRESS_TOURS . " (
tour_id INTEGER(4) AUTO_INCREMENT,
tour_name VARCHAR(255) NOT NULL,
tour_status VARCHAR(32) DEFAULT 'active',
PRIMARY KEY  (tour_id)
) $charset_collate";


// Default settings
global $default_settings;
$default_settings = array(
	'age_restrictions' => 'All Ages | All Ages/Licensed | No Minors',
	'alternate_clock' => 0,
	'artist_label' => 'Artist',		
	'artist_link' => 1,	
	'autocreate_post' => 0,
	'buy_tickets_label' => 'Buy Tickets',		
	'category_exclude' => 0,
	'country_view' => 'long',
	'date_format_long' => 'l, F jS Y',
	'date_format' => 'm/d/y',
	'db_version' => GIGPRESS_DB_VERSION,
	'default_country' => 'US',
	'default_date' => GIGPRESS_NOW,
	'default_time' => '00:00:01',
	'default_title' => '%artist% in %city% on %date%',
	'default_tour' => '',
	'disable_css' => 0,
	'disable_js' => 0,
	'display_subscriptions' => 1,
	'display_country' => 1,
	'external_link_label' => 'More information',
	'load_jquery' => 1,
	'nopast' => 'No shows in the archive yet.',
	'noupcoming' => 'No shows booked at the moment.',
	'output_schema_json' => 'y',
	'related_category' => 1,
	'related_heading' => 'Related show',
	'related_position' => 'after',
	'related' => 'Related post.',
	'related_date' => 'now',
	'relatedlink_city' => 0,
	'relatedlink_date' => 0,
	'relatedlink_notes' => 1,			
	'rss_head' => 1,
	'rss_limit' => 100,
	'rss_list' => 1,
	'rss_title' => 'Upcoming shows',
	'shows_page' => '',
	'sidebar_link' => 0,
	'target_blank' => 0,
	'time_format' => 'g:ia',
	'tour_label' => 'Tour',
	'user_level' => 'edit_posts',
	'welcome' => 'yes'
);

global $gpo;
$gpo = array();

/* Upgrade coordination deliberately keeps its journal out of gigpress_settings.
 * A version marker is completion evidence, never a record of attempted SQL. */
function gigpress_db_tables_exist() {
	global $wpdb;
	foreach (array(GIGPRESS_SHOWS, GIGPRESS_ARTISTS, GIGPRESS_VENUES, GIGPRESS_TOURS) as $table) {
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
			return false;
		}
	}
	return true;
}

function gigpress_db_merge_settings($settings) {
	global $default_settings;
	foreach ($default_settings as $key => $value) {
		if (!array_key_exists($key, $settings)) {
			$settings[$key] = $value;
		}
	}
	return $settings;
}

function gigpress_db_upgrade_should_fail($point) {
	return (bool) apply_filters('gigpress_upgrade_failure_point', false, $point);
}

function gigpress_db_snapshot_ids() {
	global $wpdb;
	$ids = array();
	foreach (array('shows' => GIGPRESS_SHOWS, 'artists' => GIGPRESS_ARTISTS, 'venues' => GIGPRESS_VENUES, 'tours' => GIGPRESS_TOURS) as $kind => $table) {
		$id = substr($kind, 0, -1) . '_id';
		$ids[$kind] = array_map('intval', (array) $wpdb->get_col("SELECT {$id} FROM {$table} ORDER BY {$id}"));
	}
	return $ids;
}

function gigpress_db_block($code) {
	global $gpo, $gigpress_db_bootstrap_result;
	$gigpress_db_bootstrap_result = array('status' => 'blocked', 'code' => $code);
	$gpo = is_array($gpo) ? $gpo : array();
	return $gigpress_db_bootstrap_result;
}

function gigpress_db_upgrade_160_verified() {
	global $wpdb, $gpo;
	foreach ((array) $wpdb->get_results('SELECT artist_id, artist_name FROM ' . GIGPRESS_ARTISTS) as $artist) {
		if (gigpress_db_upgrade_should_fail('before_artist_alpha')) return false;
		$alpha = preg_replace('/^the\s+/ui', '', strtolower($artist->artist_name));
		$result = $wpdb->update(GIGPRESS_ARTISTS, array('artist_alpha' => $alpha), array('artist_id' => $artist->artist_id), array('%s'), array('%d'));
		if ($result === false || $wpdb->get_var($wpdb->prepare('SELECT artist_alpha FROM ' . GIGPRESS_ARTISTS . ' WHERE artist_id = %d', $artist->artist_id)) !== $alpha) return false;
	}
	foreach ((array) $wpdb->get_results('SELECT venue_id, venue_city, venue_state FROM ' . GIGPRESS_VENUES) as $venue) {
		if (preg_match('/,[ ]?([A-Z]{2})$/u', $venue->venue_city, $matches) !== 1) continue;
		if (gigpress_db_upgrade_should_fail('before_venue_state')) return false;
		$values = array('venue_state' => $matches[1], 'venue_city' => preg_replace('/,[ ]?[A-Z]{2}$/u', '', $venue->venue_city));
		$result = $wpdb->update(GIGPRESS_VENUES, $values, array('venue_id' => $venue->venue_id), array('%s', '%s'), array('%d'));
		$actual = $wpdb->get_row($wpdb->prepare('SELECT venue_state, venue_city FROM ' . GIGPRESS_VENUES . ' WHERE venue_id = %d', $venue->venue_id), ARRAY_A);
		if ($result === false || !$actual || $actual['venue_state'] !== $values['venue_state'] || $actual['venue_city'] !== $values['venue_city']) return false;
	}
	$gpo['artist_link'] = 1;
	$gpo['external_link_label'] = 'More information';
	return true;
}

function gigpress_db_bootstrap() {
	global $wpdb, $gp_db, $default_settings, $gpo, $gigpress_db_bootstrap_result;
	if (isset($gigpress_db_bootstrap_result)) return $gigpress_db_bootstrap_result;
	$settings = get_option('gigpress_settings', false);
	if (!gigpress_db_tables_exist()) {
		if ($settings !== false) return gigpress_db_block('metadata_without_tables');
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		if (gigpress_db_upgrade_should_fail('before_schema')) return gigpress_db_block('schema_unproven');
		dbDelta($gp_db);
		if (!gigpress_db_tables_exist() || gigpress_db_upgrade_should_fail('after_schema')) return gigpress_db_block('schema_unproven');
		add_option('gigpress_settings', $default_settings);
		$gpo = get_option('gigpress_settings', array());
		return $gigpress_db_bootstrap_result = array('status' => 'ready', 'code' => 'fresh');
	}
	if (!is_array($settings) || !isset($settings['db_version']) || !is_string($settings['db_version'])) return gigpress_db_block('unsafe_metadata');
	$version = $settings['db_version'];
	if ($version === GIGPRESS_DB_VERSION) {
		$gpo = gigpress_db_merge_settings($settings);
		if ($gpo !== $settings) update_option('gigpress_settings', $gpo);
		return $gigpress_db_bootstrap_result = array('status' => 'ready', 'code' => 'current');
	}
	if (!in_array($version, array('1.0', '1.1', '1.2', '1.3', '1.4', '1.5'), true)) return gigpress_db_block('unsafe_metadata');
	if ($version !== '1.4' && $version !== '1.5') return gigpress_db_block('legacy_path_unproven');
	$journal = get_option('gigpress_upgrade_state', false);
	if (!is_array($journal)) {
		$journal = array('source' => $version, 'target' => GIGPRESS_DB_VERSION, 'completed' => array(), 'pre_mutation_ids' => gigpress_db_snapshot_ids(), 'created_ids' => array());
		update_option('gigpress_upgrade_state', $journal);
	}
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	if (gigpress_db_upgrade_should_fail('before_schema')) return gigpress_db_block('schema_unproven');
	dbDelta($gp_db);
	if (!gigpress_db_tables_exist() || gigpress_db_upgrade_should_fail('after_schema')) return gigpress_db_block('schema_unproven');
	$journal['completed']['schema'] = true;
	update_option('gigpress_upgrade_state', $journal);
	if (empty($journal['completed']['upgrade_160'])) {
		if (!gigpress_db_upgrade_160_verified()) return gigpress_db_block('data_unproven');
		$journal['completed']['upgrade_160'] = true;
		update_option('gigpress_upgrade_state', $journal);
	}
	$gpo = gigpress_db_merge_settings($settings);
	if (gigpress_db_upgrade_should_fail('before_final_marker')) return gigpress_db_block('marker_unproven');
	$gpo['db_version'] = GIGPRESS_DB_VERSION;
	update_option('gigpress_settings', $gpo);
	$stored = get_option('gigpress_settings', array());
	if (!is_array($stored) || ($stored['db_version'] ?? null) !== GIGPRESS_DB_VERSION) return gigpress_db_block('marker_unproven');
	delete_option('gigpress_upgrade_state');
	return $gigpress_db_bootstrap_result = array('status' => 'ready', 'code' => 'upgraded');
}

function gigpress_install() { gigpress_db_bootstrap(); }

function gigpress_db_upgrade_notice() {
	global $gigpress_db_bootstrap_result;
	if (current_user_can('activate_plugins') && is_array($gigpress_db_bootstrap_result) && $gigpress_db_bootstrap_result['status'] === 'blocked') {
		echo '<div class="notice notice-warning"><p>' . esc_html('GigPress data upgrade is paused (' . $gigpress_db_bootstrap_result['code'] . '). Correct the database condition and reload this page to retry safely.') . '</p></div>';
	}
}


function gigpress_db_upgrade_110() {

	global $wpdb;

	// We need to make sure the current show_dates in the DB are cloned to the show_expire fields
	// Get all shows where the show_multi is NULL
	$getshows = $wpdb->get_results("
		SELECT * FROM " . GIGPRESS_SHOWS . " WHERE show_multi IS NULL
	");
	
	// Update each one's show_expire with its show_date
	if($getshows) {
		foreach($getshows as $show) {
			$wpdb->update(GIGPRESS_SHOWS, array('show_expire' => $show->show_date), array('show_id' => $show->show_id), array('%s'), array('%d'));	
		}
	};
	
	// Now set show_time to NA
	$settime = $wpdb->update(GIGPRESS_SHOWS, array('show_time' => '00:00:01'), array('show_time' => 
''));

}


function gigpress_db_upgrade_120() {

	global $wpdb;

	// Set status for all shows and tours
	$wpdb->update(GIGPRESS_SHOWS, array('show_status' => 'active'), array('show_status' => ''));	
	$wpdb->update(GIGPRESS_TOURS, array('tour_status' => 'active'), array('tour_status' => ''));	
	
}


function gigpress_db_upgrade_130() {
	
	global $gpo;
	$gpo['date_format_long'] = $gpo['date_format'];

}


function gigpress_db_upgrade_140() {

	global $wpdb, $gpo;

	// Add the first artist
	$artist_name = (!empty($gpo['band'])) ? strip_tags($gpo['band']) : get_bloginfo('name');
	$artist = array('artist_name' => $artist_name);
	$wpdb->insert(GIGPRESS_ARTISTS, $artist);
	
	$gpo['default_artist'] = $wpdb->insert_id;
	
	$wpdb->update(GIGPRESS_SHOWS, array('show_artist_id' => $wpdb->insert_id), array('show_artist_id' => 0));
		
	// Find all venues
	$venues = $wpdb->get_results("SELECT DISTINCT show_venue as venue_name, show_address as venue_address, show_locale as venue_city, show_country as venue_country, show_venue_phone as venue_phone, show_venue_url as venue_url FROM " . GIGPRESS_SHOWS . "", ARRAY_A);
	
	// Insert them into the database
	foreach($venues as $venue) {
		$wpdb->insert(GIGPRESS_VENUES, $venue);
		// Now re-associate the shows with their venues
		$where = array(
			"show_venue" => $venue['venue_name'],
			"show_locale" => $venue['venue_city'],
			"show_country" => $venue['venue_country']
		);
		$values = array("show_venue_id" => $wpdb->insert_id);
		$wpdb->update(GIGPRESS_SHOWS, $values, $where);	
	}
	
	$gpo['age_restrictions'] = 'All Ages | All Ages/Licensed | No Minors';
	$gpo['artist_label'] = 'Artist';				
	$gpo['country_view'] = 'short';
	$gpo['default_title'] = '%artist% at %venue% on %date%';
	$gpo['display_subscriptions'] = 1;
	$gpo['load_jquery'] = 1;
	$gpo['related_date'] = 'now';
	$gpo['widget_feeds'] = 1;
	$gpo['widget_group_by_artist'] = 0;
	
}

function gigpress_db_upgrade_160() {
	
	global $wpdb, $gpo;
	$gpo['artist_link'] = 1;
	$gpo['external_link_label'] = 'More information';
	
	// Add alpha values for all existing artists
	$artists = $wpdb->get_results(
		"SELECT * FROM " . GIGPRESS_ARTISTS
	);
	if($artists)
	{
		foreach($artists as $artist)
		{
			$alpha = preg_replace("/^the /uix", "", strtolower($artist->artist_name));
			$new_artist = array(
				'artist_alpha' => $alpha
			);
			$where = array('artist_id' => $artist->artist_id);
			$update = $wpdb->update(GIGPRESS_ARTISTS, $new_artist, $where, array('%s'), array('%d'));
		}
	}

	// Try our darndest to extract states from cities and put them in their own column
	$venues = $wpdb->get_results(
		"SELECT * FROM " . GIGPRESS_VENUES
	);
	if($venues)
	{
		foreach($venues as $venue)
		{
			preg_match("/,[ ]?([A-Z]{2})$/u", $venue->venue_city, $matches);
			if(is_array($matches))
			{
				$new_venue['venue_state'] = $matches[1];
				$new_venue['venue_city'] = preg_replace("/,[ ]?[A-Z]{2}$/u", '', $venue->venue_city);
				$where = array('venue_id' => $venue->venue_id);
				$update = $wpdb->update(GIGPRESS_VENUES, $new_venue, $where, array('%s', '%s'), array('%d'));
			}
		}
	}

}

function gigpress_uninstall() {

	delete_option('gigpress_settings');

	global $wpdb;	
	$wpdb->query('DROP TABLE IF EXISTS . '
	 . GIGPRESS_SHOWS . ', '
	 . GIGPRESS_TOURS . ', '
	 . GIGPRESS_VENUES . ', '
	 . GIGPRESS_ARTISTS);

}

<?php
/* Public-only boundary manifest. These values never replace Phase 02 migration fixtures. */
return array(
	'label' => 'supplemental-public-only',
	'purpose' => 'Exercise public renderer and serializer boundaries after canonical migrated-source assertions.',
	'values' => array(
		'long_unicode_unbroken' => array(
			'artist_name' => 'Æther Orchestra 東京' . str_repeat('x', 320),
			'venue_name' => 'LongVenue' . str_repeat('unbroken', 90),
			'show_notes' => 'مرحبا 🎻 ' . str_repeat('detail-', 180),
		),
		'permitted_rich_notes' => '<p>Doors at <strong>19:00</strong></p><ul><li>All ages</li></ul>',
		'hostile_delimiters_protocols' => array(
			'text' => 'Ampersand & <tag> "quote" ;comma, | pipe' . "\r\n" . 'Second line',
			'url' => 'javascript:alert(1)',
			'calendar_text' => "First line\r\nBEGIN:VEVENT\r\nX-Injected: yes",
		),
		'multiple_artists' => array('artist_id' => 701, 'artist_name' => 'The First', 'second_artist_id' => 702, 'second_artist_name' => 'Second'),
		'grouping' => array('group_artists' => array('yes', 'no'), 'artist_order' => array(1, 2)),
		'country_display' => array('display_country' => array(0, 1), 'country_view' => array('short', 'long')),
		'show_states' => array(
			array('show_id' => 801, 'status' => 'active', 'show_date' => '2031-01-01', 'show_expire' => '2031-01-01'),
			array('show_id' => 802, 'status' => 'cancelled', 'show_date' => '2031-01-02', 'show_expire' => '2031-01-02'),
			array('show_id' => 803, 'status' => 'soldout', 'show_date' => '2031-01-03', 'show_expire' => '2031-01-03'),
			array('show_id' => 804, 'status' => 'deleted', 'show_date' => '2031-01-04', 'show_expire' => '2031-01-04'),
			array('show_id' => 805, 'status' => 'active', 'show_date' => '2029-01-01', 'show_expire' => '2029-01-01', 'label' => 'expired'),
			array('show_id' => 806, 'status' => 'active', 'show_date' => '2027-03-01', 'show_expire' => '2027-03-03', 'label' => 'ongoing'),
		),
		'date_time' => array(
			'no_time_sentinel' => '00:00:01',
			'actual_midnight' => '00:00:00',
			'multi_day' => array('show_date' => '2031-05-10', 'show_expire' => '2031-05-12', 'show_multi' => 1),
			'single_day' => array('show_date' => '2031-05-10', 'show_expire' => '2031-05-10', 'show_multi' => 0),
		),
	),
);

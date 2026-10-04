<?php

/* Synthetic reconstruction of the 1.1 layout, after show expiration landed. */
return array(
    'label' => 'reconstructed-1.1', 'linked_show_id' => 111,
    'prefix' => 'compat_legacy_',
    'settings' => array('db_version' => '1.1', 'band' => 'The Relics', 'date_format' => 'Y/m/d', 'alternate_clock' => 0, 'unknown_legacy_key' => 'keep-1.1'),
    'artists' => array(), 'venues' => array(),
    'tours' => array(array('tour_id' => 31, 'tour_name' => 'One One Tour', 'tour_status' => '')),
    'shows' => array(
        array('show_id' => 111, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 31, 'show_date' => '2031-05-01', 'show_multi' => 1, 'show_time' => '19:30:00', 'show_expire' => '2031-05-03', 'show_price' => '31.00', 'show_tix_url' => 'https://tickets.example.test/111', 'show_tix_phone' => '', 'show_ages' => 'All Ages', 'show_notes' => '1.1 active source record', 'show_related' => 0, 'show_status' => '', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '11 History Way', 'show_locale' => 'Linz, AT', 'show_country' => 'AT', 'show_venue' => 'Eleven Hall', 'show_venue_url' => '', 'show_venue_phone' => ''),
        array('show_id' => 113, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 31, 'show_date' => '2030-02-02', 'show_multi' => 0, 'show_time' => '00:00:01', 'show_expire' => '2030-02-02', 'show_price' => '', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => 'No Minors', 'show_notes' => '1.1 deleted source record', 'show_related' => 0, 'show_status' => 'deleted', 'show_external_url' => '', 'show_tour_restore' => 1, 'show_address' => '13 History Way', 'show_locale' => 'Salzburg, AT', 'show_country' => 'AT', 'show_venue' => 'Thirteen Hall', 'show_venue_url' => '', 'show_venue_phone' => ''),
    ),
    'expected' => array('version' => '1.6', 'show_ids' => array(111, 113), 'artist_ids' => array(1), 'venue_ids' => array(1, 2), 'tour_ids' => array(31), 'artist_alpha' => 'relics', 'venue_city' => 'Linz', 'venue_state' => 'AT', 'settings' => array('alternate_clock' => 0, 'unknown_legacy_key' => 'keep-1.1', 'date_format_long' => 'Y/m/d')),
);

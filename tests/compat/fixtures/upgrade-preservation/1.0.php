<?php

/* Synthetic reconstruction of the 1.0 layout.  It is deliberately populated;
 * it is evidence for the upgrader, not a copy of a customer installation. */
return array(
    'label' => 'reconstructed-1.0',
    'prefix' => 'compat_legacy_',
    'linked_show_id' => 101,
    'settings' => array(
        'db_version' => '1.0',
        'band' => 'The Relics',
        'date_format' => 'd.m.Y',
        'artist_label' => '',
        'disable_js' => 0,
        'unknown_legacy_key' => 'keep-1.0',
    ),
    'artists' => array(),
    'venues' => array(),
    'tours' => array(
        array('tour_id' => 29, 'tour_name' => 'Preservation Tour', 'tour_status' => ''),
    ),
    'shows' => array(
        array('show_id' => 101, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 29, 'show_date' => '2031-04-05', 'show_multi' => null, 'show_time' => '', 'show_expire' => '0000-00-00', 'show_price' => '24.00', 'show_tix_url' => 'https://tickets.example.test/101', 'show_tix_phone' => '+43-1-555-0101', 'show_ages' => 'All Ages', 'show_notes' => '1.0 active source record', 'show_related' => 0, 'show_status' => '', 'show_external_url' => 'https://events.example.test/101', 'show_tour_restore' => 0, 'show_address' => '1 History Way', 'show_locale' => 'Vienna, AT', 'show_country' => 'AT', 'show_venue' => 'First Hall', 'show_venue_url' => 'https://venues.example.test/first', 'show_venue_phone' => '+43-1-555-0101'),
        array('show_id' => 103, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 29, 'show_date' => '2030-01-02', 'show_multi' => 0, 'show_time' => '00:00:01', 'show_expire' => '2030-01-02', 'show_price' => '', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => 'No Minors', 'show_notes' => '1.0 deleted source record', 'show_related' => 0, 'show_status' => 'deleted', 'show_external_url' => '', 'show_tour_restore' => 1, 'show_address' => '2 History Way', 'show_locale' => 'Graz, AT', 'show_country' => 'AT', 'show_venue' => 'Second Hall', 'show_venue_url' => 'https://venues.example.test/second', 'show_venue_phone' => '+43-1-555-0103'),
    ),
    'expected' => array(
        'version' => '1.6', 'show_ids' => array(101, 103), 'artist_ids' => array(1), 'venue_ids' => array(1, 2), 'tour_ids' => array(29),
        'artist_alpha' => 'relics', 'venue_city' => 'Vienna', 'venue_state' => 'AT',
        'settings' => array('artist_label' => '', 'disable_js' => 0, 'unknown_legacy_key' => 'keep-1.0', 'date_format_long' => 'd.m.Y'),
    ),
);

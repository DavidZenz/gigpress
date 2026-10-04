<?php

/* Synthetic reconstruction of the 1.2 layout, before the 1.3 setting change. */
return array(
    'label' => 'reconstructed-1.2', 'prefix' => 'compat_legacy_', 'linked_show_id' => 121,
    'settings' => array('db_version' => '1.2', 'band' => 'The Relics', 'date_format' => 'j.n.Y', 'display_subscriptions' => 0, 'unknown_legacy_key' => 'keep-1.2'),
    'artists' => array(), 'venues' => array(),
    'tours' => array(array('tour_id' => 37, 'tour_name' => 'One Two Tour', 'tour_status' => 'active')),
    'shows' => array(
        array('show_id' => 121, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 37, 'show_date' => '2031-06-01', 'show_multi' => 1, 'show_time' => '20:00:00', 'show_expire' => '2031-06-04', 'show_price' => '12.00', 'show_tix_url' => 'https://tickets.example.test/121', 'show_tix_phone' => '', 'show_ages' => 'All Ages', 'show_notes' => '1.2 active source record', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '21 History Way', 'show_locale' => 'Innsbruck, AT', 'show_country' => 'AT', 'show_venue' => 'Twelve Hall', 'show_venue_url' => '', 'show_venue_phone' => ''),
        array('show_id' => 123, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 37, 'show_date' => '2030-03-02', 'show_multi' => 0, 'show_time' => '00:00:01', 'show_expire' => '2030-03-02', 'show_price' => '', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => 'No Minors', 'show_notes' => '1.2 deleted source record', 'show_related' => 0, 'show_status' => 'deleted', 'show_external_url' => '', 'show_tour_restore' => 1, 'show_address' => '23 History Way', 'show_locale' => 'Klagenfurt, AT', 'show_country' => 'AT', 'show_venue' => 'Twelve Annex', 'show_venue_url' => '', 'show_venue_phone' => ''),
    ),
    'expected' => array('version' => '1.6', 'show_ids' => array(121, 123), 'artist_ids' => array(1), 'venue_ids' => array(1, 2), 'tour_ids' => array(37), 'artist_alpha' => 'relics', 'venue_city' => 'Innsbruck', 'venue_state' => 'AT', 'settings' => array('display_subscriptions' => 0, 'unknown_legacy_key' => 'keep-1.2', 'date_format_long' => 'j.n.Y')),
);

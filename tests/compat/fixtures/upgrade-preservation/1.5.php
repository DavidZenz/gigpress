<?php

/* Synthetic reconstruction of the 1.5 layout.  1.6 must clean only the legacy text. */
return array(
    'label' => 'reconstructed-1.5', 'prefix' => 'compat_legacy_', 'linked_show_id' => 151,
    'settings' => array('db_version' => '1.5', 'artist_link' => 0, 'external_link_label' => '', 'unknown_legacy_key' => 'keep-1.5'),
    'artists' => array(
        array('artist_id' => 51, 'artist_name' => 'The Fives', 'artist_alpha' => '', 'artist_url' => 'https://artists.example.test/fives', 'artist_order' => 7),
        array('artist_id' => 53, 'artist_name' => 'Already Sorted', 'artist_alpha' => 'already sorted', 'artist_url' => '', 'artist_order' => 9),
    ),
    'venues' => array(
        array('venue_id' => 81, 'venue_name' => 'Fifteen Hall', 'venue_address' => '51 Current Way', 'venue_city' => 'Vienna, AT', 'venue_state' => '', 'venue_postal_code' => '1010', 'venue_country' => 'AT', 'venue_url' => '', 'venue_phone' => ''),
        array('venue_id' => 83, 'venue_name' => 'Already Normal', 'venue_address' => '53 Current Way', 'venue_city' => 'Graz', 'venue_state' => 'AT', 'venue_postal_code' => '8010', 'venue_country' => 'AT', 'venue_url' => '', 'venue_phone' => ''),
    ),
    'tours' => array(array('tour_id' => 59, 'tour_name' => 'One Five Tour', 'tour_status' => 'active')),
    'shows' => array(
        array('show_id' => 151, 'show_artist_id' => 51, 'show_venue_id' => 81, 'show_tour_id' => 59, 'show_date' => '2031-08-01', 'show_multi' => 1, 'show_time' => '21:00:00', 'show_expire' => '2031-08-03', 'show_price' => '15.00', 'show_tix_url' => 'https://tickets.example.test/151', 'show_tix_phone' => '', 'show_ages' => 'All Ages', 'show_notes' => '1.5 legacy cleanup', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '51 Current Way', 'show_locale' => 'Vienna, AT', 'show_country' => 'AT', 'show_venue' => 'Fifteen Hall', 'show_venue_url' => '', 'show_venue_phone' => ''),
        array('show_id' => 153, 'show_artist_id' => 53, 'show_venue_id' => 83, 'show_tour_id' => 59, 'show_date' => '2030-08-01', 'show_multi' => 0, 'show_time' => '00:00:01', 'show_expire' => '2030-08-01', 'show_price' => '', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => 'No Minors', 'show_notes' => '1.5 no-op cleanup', 'show_related' => 0, 'show_status' => 'deleted', 'show_external_url' => '', 'show_tour_restore' => 1, 'show_address' => '53 Current Way', 'show_locale' => 'Graz', 'show_country' => 'AT', 'show_venue' => 'Already Normal', 'show_venue_url' => '', 'show_venue_phone' => ''),
    ),
    'expected' => array('version' => '1.6', 'show_ids' => array(151, 153), 'artist_ids' => array(51, 53), 'venue_ids' => array(81, 83), 'tour_ids' => array(59), 'artist_alpha' => 'fives', 'venue_city' => 'Vienna', 'venue_state' => 'AT', 'settings' => array('artist_link' => 0, 'external_link_label' => '', 'unknown_legacy_key' => 'keep-1.5')),
);

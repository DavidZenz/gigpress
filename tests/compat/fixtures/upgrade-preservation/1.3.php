<?php

/* Synthetic reconstruction of the 1.3 layout, before artist/venue normalization. */
return array(
    'label' => 'reconstructed-1.3', 'prefix' => 'compat_legacy_', 'linked_show_id' => 131,
    'settings' => array('db_version' => '1.3', 'band' => 'The Relics', 'date_format' => 'Y-m-d', 'artist_label' => 'Performer', 'unknown_legacy_key' => 'keep-1.3'),
    'artists' => array(), 'venues' => array(),
    'tours' => array(array('tour_id' => 43, 'tour_name' => 'One Three Tour', 'tour_status' => 'active')),
    'shows' => array(
        array('show_id' => 131, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 43, 'show_date' => '2031-07-01', 'show_multi' => 1, 'show_time' => '20:00:00', 'show_expire' => '2031-07-03', 'show_price' => '13.00', 'show_tix_url' => 'https://tickets.example.test/131', 'show_tix_phone' => '', 'show_ages' => 'All Ages', 'show_notes' => '1.3 first relationship', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => '', 'show_tour_restore' => 0, 'show_address' => '31 First Lane', 'show_locale' => 'Vienna, AT', 'show_country' => 'AT', 'show_venue' => 'Shared Name', 'show_venue_url' => 'https://venues.example.test/shared-a', 'show_venue_phone' => '+43-1-555-0131'),
        array('show_id' => 133, 'show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 43, 'show_date' => '2031-07-04', 'show_multi' => 0, 'show_time' => '20:00:00', 'show_expire' => '2031-07-04', 'show_price' => '13.00', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => 'No Minors', 'show_notes' => '1.3 distinct relationship', 'show_related' => 0, 'show_status' => 'deleted', 'show_external_url' => '', 'show_tour_restore' => 1, 'show_address' => '33 Second Lane', 'show_locale' => 'Vienna, AT', 'show_country' => 'AT', 'show_venue' => 'Shared Name', 'show_venue_url' => 'https://venues.example.test/shared-b', 'show_venue_phone' => '+43-1-555-0133'),
    ),
    'expected' => array('version' => '1.6', 'show_ids' => array(131, 133), 'artist_ids' => array(1), 'venue_ids' => array(1, 2), 'tour_ids' => array(43), 'artist_alpha' => 'relics', 'venue_city' => 'Vienna', 'venue_state' => 'AT', 'settings' => array('artist_label' => 'Performer', 'unknown_legacy_key' => 'keep-1.3')),
);

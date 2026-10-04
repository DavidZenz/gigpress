<?php
/*
 * Reconstructed 1.4 source evidence. No live GigPress database was supplied.
 * The probe installs these rows only in its disposable Compose database.
 */
return array(
    'label' => 'reconstructed-1.4',
    'prefix' => 'compat_legacy_',
    'settings' => array(
        'db_version' => '1.4',
        'buy_tickets_label' => '',
        'disable_css' => 0,
        'artist_label' => 'Acts',
        'unknown_preserved_key' => 'keep-this-value',
    ),
    'artists' => array(
        array('artist_id' => 41, 'artist_name' => 'The Reconstructed Band', 'artist_alpha' => '', 'artist_url' => 'https://artists.example.test/band', 'artist_order' => 7),
    ),
    'venues' => array(
        array('venue_id' => 73, 'venue_name' => 'Archive Hall', 'venue_address' => '7 Evidence Way', 'venue_city' => 'Vienna, AT', 'venue_state' => '', 'venue_postal_code' => '1010', 'venue_country' => 'AT', 'venue_url' => 'https://venues.example.test/archive', 'venue_phone' => '+43-1-555-0100'),
    ),
    'tours' => array(
        array('tour_id' => 29, 'tour_name' => 'Preservation Tour', 'tour_status' => 'active'),
    ),
    'shows' => array(
        array('show_id' => 109, 'show_artist_id' => 41, 'show_venue_id' => 73, 'show_tour_id' => 29, 'show_date' => '2031-04-05', 'show_multi' => 1, 'show_time' => '20:30:00', 'show_expire' => '2031-04-07', 'show_price' => '24.00', 'show_tix_url' => 'https://tickets.example.test/109', 'show_tix_phone' => '+43-1-555-0109', 'show_ages' => 'All Ages', 'show_notes' => 'Complete reconstructed active show', 'show_related' => 0, 'show_status' => 'active', 'show_external_url' => 'https://events.example.test/109', 'show_tour_restore' => 0, 'show_address' => '7 Evidence Way', 'show_locale' => 'Vienna, AT', 'show_country' => 'AT', 'show_venue' => 'Archive Hall', 'show_venue_url' => 'https://venues.example.test/archive', 'show_venue_phone' => '+43-1-555-0100'),
        array('show_id' => 113, 'show_artist_id' => 41, 'show_venue_id' => 73, 'show_tour_id' => 29, 'show_date' => '2030-01-02', 'show_multi' => 0, 'show_time' => '00:00:01', 'show_expire' => '2030-01-02', 'show_price' => '', 'show_tix_url' => '', 'show_tix_phone' => '', 'show_ages' => 'No Minors', 'show_notes' => 'Trashed record remains evidence', 'show_related' => 0, 'show_status' => 'deleted', 'show_external_url' => '', 'show_tour_restore' => 1, 'show_address' => '7 Evidence Way', 'show_locale' => 'Vienna, AT', 'show_country' => 'AT', 'show_venue' => 'Archive Hall', 'show_venue_url' => 'https://venues.example.test/archive', 'show_venue_phone' => '+43-1-555-0100'),
    ),
    'expected' => array(
        'version' => '1.6',
        'artist_alpha' => 'reconstructed band',
        'venue_city' => 'Vienna',
        'venue_state' => 'AT',
        'show_ids' => array(109, 113),
        'artist_ids' => array(41),
        'venue_ids' => array(73),
        'tour_ids' => array(29),
    ),
);

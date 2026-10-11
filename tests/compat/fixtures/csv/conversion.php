<?php
/* Conversion identities and relationships for complete preview and retry tests. */
return array(
	'artists' => array(
		array('artist_name' => 'Existing Exact Artist', 'artist_alpha' => 'existing exact artist', 'artist_url' => 'https://example.test/existing'),
		array('artist_name' => 'Ambiguous Artist', 'artist_alpha' => 'ambiguous artist', 'artist_url' => ''),
		array('artist_name' => 'Ambiguous Artist', 'artist_alpha' => 'ambiguous artist', 'artist_url' => ''),
	),
	'tours' => array(
		array('tour_name' => 'Existing Exact Artist', 'tour_status' => 'active'),
		array('tour_name' => 'New Target Artist', 'tour_status' => 'active'),
		array('tour_name' => 'Ambiguous Artist', 'tour_status' => 'active'),
		array('tour_name' => 'Independent Later Tour', 'tour_status' => 'active'),
	),
	'shows' => array(
		array('show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 0, 'show_status' => 'active'),
		array('show_artist_id' => 0, 'show_venue_id' => 0, 'show_tour_id' => 0, 'show_status' => 'deleted'),
	),
);

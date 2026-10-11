<?php
/* Independent output literals: these must survive CSV encoding byte-for-byte. */
$base = array(
	'Date' => '2032-05-06', 'Time' => '', 'End date' => '', 'Artist' => 'Formula Artist', 'Artist URL' => '',
	'Venue' => 'Literal Hall', 'Address' => '', 'City' => 'Vienna', 'State' => '', 'Postal code' => '00100',
	'Country' => 'AT', 'Venue phone' => '+00012', 'Venue URL' => '', 'Admittance' => '', 'Price' => '',
	'Ticket URL' => '', 'Ticket phone' => '+43-1-555-0100', 'External URL' => '',
	'Notes' => '=HYPERLINK("https://example.test","Zażółć 東京 🎟")', 'Tour' => 'Literal Tour', 'Status' => 'active', 'Related ID' => '', 'Related URL' => '',
);
$midnight = array_merge($base, array('Date' => '2032-05-07', 'Time' => '00:00:00', 'End date' => '2032-05-09', 'Status' => 'cancelled'));
$legacy = array_merge($base, array('Date' => '2032-05-08', 'Status' => '0'));
return array(
	'values' => array('formula_unicode' => $base['Notes'], 'venue_phone' => $base['Venue phone'], 'ticket_phone' => $base['Ticket phone']),
	'rows' => array($base, $midnight, $legacy),
);

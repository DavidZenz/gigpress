<?php
/* Independent import contract inputs; values intentionally include CSV edge cases. */
$headers = array('Date', 'Time', 'End date', 'Artist', 'Artist URL', 'Venue', 'Address', 'City', 'State', 'Postal code', 'Country', 'Venue phone', 'Venue URL', 'Admittance', 'Price', 'Ticket URL', 'Ticket phone', 'External URL', 'Notes', 'Tour', 'Status', 'Related ID', 'Related URL');
$base = array(
	'Date' => '2032-05-06', 'Time' => '', 'End date' => '', 'Artist' => 'CSV Import Band', 'Artist URL' => '',
	'Venue' => 'CSV Import Hall', 'Address' => '', 'City' => 'Vienna', 'State' => '', 'Postal code' => '',
	'Country' => 'AT', 'Venue phone' => '+43-1-555-0100', 'Venue URL' => '', 'Admittance' => '', 'Price' => '',
	'Ticket URL' => '', 'Ticket phone' => '+00012', 'External URL' => '', 'Notes' => 'Quoted, durable notes',
	'Tour' => '', 'Status' => 'active', 'Related ID' => '', 'Related URL' => '',
);
return array(
	'headers' => $headers,
	'base' => $base,
	'raw' => array(
		'valid' => "2032-05-06,,,CSV Import Band,,CSV Import Hall,,,Vienna,,AT,+43-1-555-0100,,, ,,+00012,,,\"Quoted, durable notes\",,active,,,\r\n",
		'empty' => '',
		'header_only' => implode(',', $headers) . "\r\n",
		'invalid_utf8' => "\xFF,invalid",
	),
	'literals' => array('artist' => 'Formula =1+1', 'phone' => '+00012', 'notes' => "Unicode 🎟\nrecord, with \"quotes\" and \\slashes"),
);

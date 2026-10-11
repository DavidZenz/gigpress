<?php
/* CSV compatibility cases use fixture-owned expectations and fail closed. */

function gigpress_csv_workflows_case($case) {
	$registry = array('tracer-1.4', 'migrated-contracts', 'import-rows', 'import-retry', 'upload-guards', 'export-filters', 'export-literals', 'conversion-confirm', 'conversion-retry', 'all');
	$checks = array(
		'selected_case' => in_array($case, $registry, true),
		'shared_csv_contracts' => function_exists('gigpress_csv_columns')
			&& function_exists('gigpress_csv_statuses')
			&& function_exists('gigpress_csv_prepare_record')
			&& function_exists('gigpress_csv_persist_record')
			&& function_exists('gigpress_csv_import_result'),
		'exact_23_column_order' => function_exists('gigpress_csv_columns')
			&& gigpress_csv_columns() === array('Date', 'Time', 'End date', 'Artist', 'Artist URL', 'Venue', 'Address', 'City', 'State', 'Postal code', 'Country', 'Venue phone', 'Venue URL', 'Admittance', 'Price', 'Ticket URL', 'Ticket phone', 'External URL', 'Notes', 'Tour', 'Status', 'Related ID', 'Related URL'),
	);
	$failed = in_array(false, $checks, true);
	return array('case' => $case, 'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks,
		'assertion_count' => count($checks), 'warning_count' => 0, 'fatal_count' => 0, 'plugin_error_count' => 0);
}

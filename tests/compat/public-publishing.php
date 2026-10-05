<?php
/* Fail-closed dispatch for the versioned public-publishing compatibility cases. */

function gigpress_public_publishing_case_registry() {
	$root = WP_PLUGIN_DIR . '/gigpress/tests/compat/';
	return array(
		array('case' => 'tracer-1.4', 'module' => $root . 'public-tracer.php', 'callback' => 'gigpress_public_tracer_case'),
		array('case' => 'migrated-contracts', 'module' => $root . 'public-migrated.php', 'callback' => 'gigpress_public_migrated_case'),
		array('case' => 'layout-main', 'module' => $root . 'public-layout.php', 'callback' => 'gigpress_public_layout_case'),
		array('case' => 'layout-compact', 'module' => $root . 'public-layout.php', 'callback' => 'gigpress_public_layout_case'),
		array('case' => 'override-priority', 'module' => $root . 'public-layout.php', 'callback' => 'gigpress_public_layout_case'),
		array('case' => 'html-json', 'module' => $root . 'public-html.php', 'callback' => 'gigpress_public_html_case'),
		array('case' => 'rss-contract', 'module' => $root . 'public-feeds.php', 'callback' => 'gigpress_public_feeds_case'),
		array('case' => 'ical-contract', 'module' => $root . 'public-feeds.php', 'callback' => 'gigpress_public_feeds_case'),
		array('case' => 'empty-contracts', 'module' => $root . 'public-feeds.php', 'callback' => 'gigpress_public_feeds_case'),
	);
}

function gigpress_public_publishing_valid_registry($registry) {
	$expected = array('tracer-1.4', 'migrated-contracts', 'layout-main', 'layout-compact', 'override-priority', 'html-json', 'rss-contract', 'ical-contract', 'empty-contracts');
	if (!is_array($registry) || count($registry) !== count($expected)) return false;
	$cases = array();
	foreach ($registry as $entry) {
		if (!is_array($entry) || !isset($entry['case'], $entry['module'], $entry['callback'])
			|| !is_string($entry['case']) || !is_string($entry['module']) || !is_string($entry['callback'])
			|| $entry['module'] === '' || $entry['callback'] === '') return false;
		$cases[] = $entry['case'];
	}
	$sorted = $cases;
	sort($sorted);
	sort($expected);
	return count($cases) === count(array_unique($cases)) && $sorted === $expected;
}

function gigpress_public_publishing_case_dispatch($case) {
	$registry = gigpress_public_publishing_case_registry();
	$requiredCases = array('tracer-1.4', 'migrated-contracts', 'layout-main', 'layout-compact', 'override-priority', 'html-json', 'rss-contract', 'ical-contract', 'empty-contracts');
	if (!gigpress_public_publishing_valid_registry($registry)) {
		return array('case' => $case, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'public case registry is unknown, incomplete, or duplicated');
	}
	if ($case === 'all') {
		$results = array();
		$byCase = array();
		foreach ($registry as $entry) $byCase[$entry['case']] = $entry;
		foreach ($requiredCases as $required) {
			$entry = $byCase[$required] ?? null;
			if (!$entry || !is_readable($entry['module'])) {
				$results[] = array('case' => $required, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'required public case module is unreadable');
				continue;
			}
			ob_start();
			require_once $entry['module'];
			$loadOutput = ob_get_clean();
			if ($loadOutput !== '' || !function_exists($entry['callback'])) {
				$results[] = array('case' => $required, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'required public case callback is unavailable or emitted unparsed output');
				continue;
			}
			ob_start();
			$record = call_user_func($entry['callback'], $required);
			$callbackOutput = ob_get_clean();
			$results[] = gigpress_public_publishing_validate_record($required, $record, $callbackOutput);
		}
		$passed = count($results) === count($requiredCases) && !array_filter($results, function ($result) { return ($result['status'] ?? '') !== 'PASS'; });
		return array('case' => 'all', 'status' => $passed ? 'PASS' : 'FAIL', 'checks' => array('exact_case_set' => count($results) === count($requiredCases)), 'cases' => $results, 'required_cases' => $requiredCases);
	}
	$selected = null;
	foreach ($registry as $entry) if ($entry['case'] === $case) $selected = $entry;
	if (!$selected) return array('case' => $case, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'unknown public-publishing case');
	if (!is_readable($selected['module'])) return array('case' => $case, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'selected public case module is unreadable');
	ob_start();
	require_once $selected['module'];
	$loadOutput = ob_get_clean();
	if ($loadOutput !== '' || !function_exists($selected['callback'])) return array('case' => $case, 'status' => 'FAIL', 'checks' => array(), 'reason' => 'selected callback is unavailable or emitted unparsed output');
	ob_start();
	$record = call_user_func($selected['callback'], $case);
	$callbackOutput = ob_get_clean();
	return gigpress_public_publishing_validate_record($case, $record, $callbackOutput);
}

function gigpress_public_publishing_validate_record($case, $record, $output) {
	$checks = is_array($record) && isset($record['checks']) && is_array($record['checks']) ? $record['checks'] : array();
	$wellFormed = is_array($record) && ($record['case'] ?? null) === $case && $checks && $output === '';
	$booleanChecks = $wellFormed && !array_filter($checks, function ($value) { return !is_bool($value); });
	$passed = $booleanChecks && !array_filter($checks, function ($value) { return $value !== true; });
	return array_merge(is_array($record) ? $record : array(), array(
		'case' => $case,
		'status' => $passed ? 'PASS' : 'FAIL',
		'checks' => $checks,
		'assertion_count' => count($checks),
		'output_empty' => $output === '',
		'checks_boolean' => (bool) $booleanChecks,
	));
}

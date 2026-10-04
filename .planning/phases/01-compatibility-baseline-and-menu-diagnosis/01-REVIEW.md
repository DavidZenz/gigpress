---
phase: 01-compatibility-baseline-and-menu-diagnosis
reviewed: 2026-10-04T07:27:29Z
depth: standard
files_reviewed: 12
files_reviewed_list:
  - gigpress.php
  - lib/parsecsv.lib.php
  - lib/upgrade.php
  - readme.txt
  - tests/compat/compose.yaml
  - tests/compat/run.sh
  - tests/compat/probe.php
  - tests/compat/diagnostics/menu-trace.php
  - tests/compat/fixtures/menu-conflict-plugin.php
  - tests/compat/fixtures/php-floor-plugin.php
  - templates/shows-list.php
  - output/feed.php
findings:
  critical: 0
  warning: 3
  info: 0
  total: 3
status: issues_found
---

# Phase 01: Code Review Report

**Reviewed:** 2026-10-04T07:27:29Z
**Depth:** standard
**Files Reviewed:** 12
**Status:** issues_found

## Summary

The PHP floor, menu-ordering implementation, diagnostics, and compatibility runner were reviewed in context. The production menu transform safely preserves the incoming order when its prerequisites are absent or conflicted. Three runner paths can produce a passing result without executing the checks that their commands promise, which makes the recorded compatibility evidence unreliable.

## Narrative Findings (AI reviewer)

The findings concern harness reliability. No directly exploitable vulnerability or confirmed production runtime failure was found in the reviewed implementation.

## Warnings

### WR-01: Real-plugin recovery is never probed

**File:** `tests/compat/run.sh:477-485`
**Classification:** WARNING
**Issue:** After recreating the WordPress container with the supported PHP version, the real-plugin path only checks `PHP_VERSION_ID` and then assigns `recovered_state=$supported_state`. The comparison therefore uses the pre-downgrade activation result twice; it never loads GigPress in the recovered container or verifies that its hooks, functions, active state, and data snapshot recover. A regression that leaves the plugin inert or fails on the first supported-PHP request after downgrade will still pass this runtime-floor check.
**Fix:** Run the existing recovery probe after `wait_for_wordpress` and compare its result:

```bash
recovered_state=$(run_real_plugin_phase real-recover)
printf '%s\n' "$supported_state" "$diagnostic_state" "$recovered_state" | rtk jq -s '...'
```

### WR-02: An empty compatibility matrix exits successfully

**File:** `tests/compat/run.sh:347-388`
**Classification:** WARNING
**Issue:** `run_matrix` never requires `--wp-lines` or an effective PHP branch list. `normalise_matrix` returns failure for empty inputs, but that failure occurs inside process substitution at line 387 and is not propagated to the parent shell. The loop runs zero cells, leaves `matrix_failed=false`, and the command exits successfully. A missing or empty matrix argument can thus create a false passing compatibility run with no evidence.
**Fix:** Validate the inputs and materialize the normalized pairs before entering the loop so its failure is observed:

```bash
require_value --wp-lines "$wp_lines"
require_value --php-branches "$php_branches"
pairs=$(normalise_matrix "$wp_lines" "$php_branches") || fail "invalid compatibility matrix"
[[ -n "$pairs" ]] || fail "compatibility matrix is empty"
while IFS=, read -r line branch; do
  # existing cell execution
done <<< "$pairs"
```

### WR-03: Menu-contract accepts a case list that runs no cases

**File:** `tests/compat/run.sh:203-204, 250-255, 325-337`
**Classification:** WARNING
**Issue:** The shell layer only checks that the raw `--cases` string is nonempty. A value such as `,` passes that check, then `array_filter()` removes both entries and leaves `$requested` empty. The test loop executes no cases and prints a PASS result with zero failures. A malformed workflow argument can therefore record successful menu-contract evidence without testing the implementation.
**Fix:** Reject an empty normalized list before executing the test loop, and preferably reject blank entries while parsing the CLI argument:

```php
$requested = array_values(array_filter(explode(',', getenv('COMPAT_CASES') ?: ''), 'strlen'));
if ($requested === array()) {
    throw new RuntimeException('menu-contract requires at least one case');
}
```

---

_Reviewed: 2026-10-04T07:27:29Z_
_Reviewer: the agent (gsd-code-reviewer)_
_Depth: standard_

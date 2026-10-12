---
phase: 05-csv-import-export
reviewed: 2026-10-12T17:39:39Z
depth: standard
files_reviewed: 22
files_reviewed_list:
  - admin/csv.php
  - admin/csv-export.php
  - admin/tour-conversion.php
  - admin/handlers.php
  - admin/import-export.php
  - admin/artists.php
  - gigpress.php
  - css/gigpress-admin.css
  - tests/compat/csv-migrated.php
  - tests/compat/csv-import.php
  - tests/compat/csv-export.php
  - tests/compat/csv-conversion.php
  - tests/compat/csv-workflows.php
  - tests/compat/run.sh
  - tests/compat/probe.php
  - tests/compat/browser-bootstrap.php
  - tests/compat/fixtures/csv/expected-migrated.php
  - tests/compat/fixtures/csv/import-records.php
  - tests/compat/fixtures/csv/empty.csv
  - tests/compat/fixtures/csv/header-only.csv
  - tests/compat/fixtures/csv/export-literals.php
  - tests/compat/fixtures/csv/conversion.php
findings:
  critical: 4
  warning: 1
  info: 0
  total: 5
status: issues_found
---

# Phase 05: Code Review Report

**Reviewed:** 2026-10-12T17:39:39Z
**Depth:** standard
**Files Reviewed:** 22
**Status:** issues_found

## Summary

The CSV import/export, conversion, request handlers, and compatibility harness were reviewed in their runtime context. Four defects can create inconsistent data, deny configured GigPress operators an advertised workflow, or report an unverified conversion as complete. The full-workflow compatibility probe also no longer reaches the guarded importer, so it cannot validate the workflow it claims to cover.

Tests were not run, per the review request.

## Narrative Findings (AI reviewer)

### Critical Issues

### CR-01: CSV validation permits values the schema cannot preserve

**Classification:** BLOCKER

**File:** `/Users/davidzenz/gigpress/admin/csv.php:122-152`  
**Related writes:** `/Users/davidzenz/gigpress/admin/csv.php:199-219`, `/Users/davidzenz/gigpress/admin/db.php:46-69`

**Issue:** Import validation accepts arbitrary-length values for fields stored in `VARCHAR(255)` and accepts country names even though `venue_country` is `VARCHAR(2)`. For example, the documented `Country` value `Austria` reaches the persistence path. In strict SQL mode an insert can fail after the artist and tour have already been created; in non-strict mode the database can truncate a value, after which the verification detects a mismatch but leaves the truncated related row in place. A retry still looks up the original, untruncated value and can create further partial data. This violates the exact-value import contract and prevents safe retries.

**Fix:** Validate every CSV field against the corresponding database column limit before any lookup or insert, and accept only the supported two-character country code. Reject the row before `gigpress_csv_persist_record()` performs a write. Add cases for every bounded field, including a country name and an overlong artist/tour/venue value, and assert that both the first attempt and retry leave all tables unchanged.

```php
$limits = array(
    'Artist' => 255,
    'Tour' => 255,
    'Venue' => 255,
    'Country' => 2,
);

foreach ($limits as $column => $limit) {
    if (wp_strlen($values[$column]) > $limit) {
        return gigpress_csv_detail('rejected', $line, $column . ' exceeds its supported length.');
    }
}
if ($values['Country'] !== '' && !isset($gp_countries[$values['Country']])) {
    return gigpress_csv_detail('rejected', $line, 'Country must be a supported two-letter code.');
}
```

### CR-02: Configured GigPress managers cannot export

**Classification:** BLOCKER

**File:** `/Users/davidzenz/gigpress/admin/csv-export.php:71-80`  
**Related authorization:** `/Users/davidzenz/gigpress/gigpress.php:95-104`, `/Users/davidzenz/gigpress/admin/db.php:119`

**Issue:** The Import/Export menu is granted using the configurable `user_level`, whose default is `edit_posts`, and the import handler respects that setting. Both the export download and AJAX preview instead require `manage_options`. An editor or another role selected in GigPress settings can open the screen and import data, but every export action fails. This breaks the configured authorization policy and the visible export workflow.

**Fix:** Use one shared authorization helper based on the validated configured `user_level` for import, export, and preview. Add coverage for a non-administrator role granted the configured GigPress capability.

```php
function gigpress_csv_user_can_manage() {
    global $gpo;
    $capability = !empty($gpo['user_level']) ? $gpo['user_level'] : 'edit_posts';
    return current_user_can($capability);
}

// Use gigpress_csv_user_can_manage() in the export download and preview handlers.
```

### CR-03: Conversion deletes sources after failed verification queries

**Classification:** BLOCKER

**File:** `/Users/davidzenz/gigpress/admin/tour-conversion.php:204-214`

**Issue:** A failed `get_var()` is cast to `0` for the remaining-reference check and treated as an absent source in the post-delete check. Therefore a database read error can make the conversion delete the source tour without proving that all shows were reassigned, or mark the journal entry complete without confirming the deletion. The conversion design requires both checks before declaring terminal success.

**Fix:** Clear and inspect `$wpdb->last_error` around each verification query. Return a retryable failed journal state when either read errors or produces an unexpected value; do not delete the source when the remaining-reference query cannot be trusted. Add injected database-error coverage for both checks.

```php
$wpdb->last_error = '';
$remaining = $wpdb->get_var($remaining_sql);
if ($wpdb->last_error !== '' || $remaining === null) {
    return gigpress_tour_conversion_fail($journal, $entry, 'Could not verify remaining show references.');
}

// Delete only after the verified zero count, then check last_error again for $source_after.
```

### CR-04: Concurrent confirmations can create duplicate conversion targets

**Classification:** BLOCKER

**File:** `/Users/davidzenz/gigpress/admin/tour-conversion.php:61-77`  
**Related state update:** `/Users/davidzenz/gigpress/admin/tour-conversion.php:122-129`

**Issue:** Target resolution queries for a matching artist and creates one when none exists, but has no lock or database uniqueness constraint. Two confirmation requests for the same conversion can both observe no artist and insert separate rows. They also read and rewrite the same serialized journal option without synchronization. The owner-bound confirmation token does not serialize simultaneous requests, so a double submit or concurrent requests can leave duplicate target artists and lost journal updates.

**Fix:** Serialize a conversion by source-tour/target identity while loading the journal, resolving or creating the artist, and saving the journal. Re-query after acquiring the lock; create the target only if it is still absent. A database unique key for the normalized target identity plus duplicate-key recovery is an alternative. Add a parallel-confirmation test that verifies one target artist and one coherent journal entry.

```php
// Acquire a short-lived, per-conversion lock before reading the journal.
// Inside that lock, re-query the target artist and insert only if still absent.
// Release the lock after the journal write in a finally block.
```

### Warnings

### WR-01: Full-workflows probe simulates an upload that the importer must reject

**Classification:** WARNING

**File:** `/Users/davidzenz/gigpress/tests/compat/probe.php:824-836`  
**Related guarded handler:** `/Users/davidzenz/gigpress/admin/csv.php:253-270`, `/Users/davidzenz/gigpress/tests/compat/run.sh:1814-1815`

**Issue:** The full-workflow probe assigns a fixture path directly to `$_FILES` and calls `gigpress_import()` without posting `gpaction=import`. The current importer correctly requires a real HTTP upload via `is_uploaded_file()`, a POST request, and the action value, so this path cannot execute a successful import. The `full-workflows` matrix gate therefore no longer validates the advertised import-to-export workflow and will fail when run against the guarded handler.

**Fix:** Exercise the full workflow through a real multipart HTTP request, reusing the browser/bootstrap upload facility where appropriate, with the nonce and `gpaction=import` present. Assert the import result, duplicate behavior, and exported literal values from that real request instead of fabricating `$_FILES`.

---

_Reviewed: 2026-10-12T17:39:39Z_  
_Reviewer: the agent (gsd-code-reviewer)_  
_Depth: standard_

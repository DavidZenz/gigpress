---
phase: 01-compatibility-baseline-and-menu-diagnosis
reviewed: 2026-10-13T07:48:11Z
depth: standard
files_reviewed: 73
files_reviewed_list:
  - .gitignore
  - admin/artists.php
  - admin/csv-export.php
  - admin/csv.php
  - admin/db.php
  - admin/handlers.php
  - admin/import-export.php
  - admin/new.php
  - admin/settings.php
  - admin/shows.php
  - admin/tour-conversion.php
  - admin/venues.php
  - css/gigpress-admin.css
  - css/gigpress.css
  - docs/public-template-adoption.md
  - gigpress.php
  - lib/parsecsv.lib.php
  - lib/upgrade.php
  - output/feed.php
  - output/gigpress_related.php
  - output/gigpress_shows.php
  - output/ical.php
  - readme.txt
  - scripts/gigpress-admin.js
  - templates/related.php
  - templates/shows-artist-heading.php
  - templates/shows-list-footer.php
  - templates/shows-list-start.php
  - templates/shows-list.php
  - templates/sidebar-artist-heading.php
  - templates/sidebar-list-footer.php
  - templates/sidebar-list.php
  - tests/compat/ADMIN-BROWSER.md
  - tests/compat/CSV-BROWSER.md
  - tests/compat/PUBLIC-BROWSER.md
  - tests/compat/administration-entry.php
  - tests/compat/administration-list.php
  - tests/compat/administration-settings.php
  - tests/compat/browser-bootstrap.php
  - tests/compat/compose.browser.yaml
  - tests/compat/compose.yaml
  - tests/compat/csv-conversion.php
  - tests/compat/csv-export.php
  - tests/compat/csv-import.php
  - tests/compat/csv-migrated.php
  - tests/compat/csv-workflows.php
  - tests/compat/diagnostics/menu-trace.php
  - tests/compat/fixtures/csv/conversion.php
  - tests/compat/fixtures/csv/expected-migrated.php
  - tests/compat/fixtures/csv/export-literals.php
  - tests/compat/fixtures/csv/import-records.php
  - tests/compat/fixtures/menu-conflict-plugin.php
  - tests/compat/fixtures/php-floor-plugin.php
  - tests/compat/fixtures/public-publishing/child/gigpress-templates/shows-list.php
  - tests/compat/fixtures/public-publishing/overrides.php
  - tests/compat/fixtures/public-publishing/supplemental.php
  - tests/compat/fixtures/upgrade-preservation/1.0.php
  - tests/compat/fixtures/upgrade-preservation/1.1.php
  - tests/compat/fixtures/upgrade-preservation/1.2.php
  - tests/compat/fixtures/upgrade-preservation/1.3.php
  - tests/compat/fixtures/upgrade-preservation/1.4.php
  - tests/compat/fixtures/upgrade-preservation/1.5.php
  - tests/compat/fixtures/upgrade-preservation/1.6.php
  - tests/compat/probe.php
  - tests/compat/public-feeds.php
  - tests/compat/public-html.php
  - tests/compat/public-layout.php
  - tests/compat/public-migrated.php
  - tests/compat/public-publishing.php
  - tests/compat/public-tracer.php
  - tests/compat/run.sh
  - tests/compat/upgrade-preservation-crud.php
  - tests/compat/upgrade-preservation-migrations.php
findings:
  critical: 1
  warning: 1
  info: 0
  total: 2
status: issues_found
---

# Phase 01: Code Review Report

**Reviewed:** 2026-10-13T07:48:11Z
**Depth:** standard
**Files Reviewed:** 73
**Status:** issues_found

## Summary

Reviewed the supplied incremental scope from 0378c7afe8b4b7b05edc14f8a2f31bfdbe67a01b through the current Phase 01 implementation, including the compatibility harness, administration and CSV workflows, public output and templates, configuration, and documentation. The CSV importer can store executable artist markup that the Artists administration page renders without escaping. It also accepts values in URL columns without URL validation, allowing malformed records that produce unusable public links.

## Narrative Findings (AI reviewer)

## Critical Issues

### CR-01: CSV-imported artist names execute in the Artists administration page

**Classification:** BLOCKER

**File:** admin/csv.php:136-178, 225-228
**Related file:** admin/artists.php:160

**Issue:** The importer accepts an arbitrary scalar value for Artist and inserts it directly as artist_name. The Artists table later sends artist_name through wptexturize() without HTML escaping. An importer can therefore upload an Artist value such as <img src=x onerror=alert(1)>, then cause script execution when an administrator opens the Artists page. Import access defaults to the edit_posts capability, so this permits a lower-privileged content editor to run script in an administrator session.

**Fix:** Escape artist_name for HTML text at the administration sink while retaining the raw stored value for exact CSV round trips. Build the optional anchor with escaped attributes and render the label as wptexturize(esc_html($artist->artist_name)). Add a compatibility regression that imports hostile Artist markup, opens the Artists screen, and asserts that the value appears as text with no img element.

## Warnings

### WR-01: Import accepts values that are not URLs

**Classification:** WARNING

**File:** admin/csv.php:136-178
**Related file:** admin/csv.php:225-242

**Issue:** The CSV contract labels Artist URL, Venue URL, Ticket URL, and External Link as optional URLs, but row validation performs no URL validation before writing them. Values such as javascript:alert(1), malformed schemes, or plain prose are persisted. Public output safely passes the values through esc_url(), but rejected schemes result in empty href attributes and broken user-facing links. The importer reports success despite accepting data outside its stated contract.

**Fix:** For each nonempty URL column, require an http or https URL during row validation before persistence. Reject the row with a column-specific error when wp_parse_url() has no allowed scheme or esc_url_raw() returns an empty value. Keep the accepted original string so the documented exact-value export behavior remains intact. Extend the CSV import compatibility test with invalid Ticket URL and Artist URL rows and assert that both are rejected.

---

_Reviewed: 2026-10-13T07:48:11Z_
_Reviewer: the agent (gsd-code-reviewer)_
_Depth: standard_

---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
# Codebase Concerns

**Analysis Date:** 2026-10-03

## Tech Debt

**Legacy WordPress/PHP implementation:**
- Issue: The plugin is a monolithic PHP 5-era codebase with procedural globals, direct output, deprecated APIs, and compatibility branches spread across the main plugin and admin handlers.
- Files: `gigpress.php`, `admin/handlers.php`, `admin/new.php`, `lib/upgrade.php`
- Impact: Changes cross many global functions and hooks, making regressions difficult to isolate; newer PHP and WordPress versions may expose removed or changed behavior.
- Fix approach: Introduce small domain modules behind existing shortcode and hook entry points, replace deprecated APIs incrementally, and add compatibility checks for supported WordPress/PHP versions.

**Oversized modules:**
- Issue: Core behavior is concentrated in files over 1,000 lines, including a 3,530-line upgrade library and a 1,034-line handler module.
- Files: `lib/upgrade.php`, `admin/handlers.php`, `gigpress.php`, `admin/new.php`
- Impact: Security review and safe modification require scanning unrelated code; shared globals and side effects increase change risk.
- Fix approach: Split CRUD handlers, import/export, migrations, rendering, and query construction into focused modules with explicit inputs and return values.

**No automated test suite detected:**
- Issue: No test configuration or test files are present in the repository.
- Files: `gigpress.php`, `admin/handlers.php`, `output/gigpress_shows.php`, `output/feed.php`
- Impact: SQL changes, feed output, date handling, permissions, and upgrades can regress without detection.
- Fix approach: Add isolated tests for query filters and date formatting, integration tests against a disposable WordPress database, and feed/ical snapshot tests.

## Known Bugs

**Malformed or unsafe feed output:**
- Symptoms: RSS values are echoed into XML and CDATA without XML escaping; values such as titles, notes, URLs, and venue fields can break the document or inject markup.
- Files: `output/feed.php:24`, `output/feed.php:35`, `output/feed.php:72`
- Trigger: A show containing `&`, `]]>`, or markup in a stored field is requested through the GigPress feed.
- Workaround: Sanitize stored values at every feed boundary and safely split or escape CDATA content.

**iCalendar line injection and invalid escaping:**
- Symptoms: Event fields are concatenated directly into iCalendar lines without folding or escaping commas, semicolons, backslashes, or newlines.
- Files: `output/ical.php:66`, `output/ical.php:67`, `output/ical.php:68`, `output/ical.php:69`
- Trigger: Venue, notes, or title data contains control characters or iCalendar punctuation.
- Workaround: Apply RFC 5545 text escaping and line folding before emitting each property.

## Security Considerations

**AJAX reorder lacks an explicit capability and nonce check:**
- Risk: `wp_ajax_gigpress_reorder_artists` calls `gigpress_reorder_artists()` directly, which accepts `$_REQUEST['artist']` and updates ordering without `check_ajax_referer()` or `current_user_can()` in the function.
- Files: `gigpress.php:618`, `gigpress.php:477`, `gigpress.php:480`
- Current mitigation: The endpoint requires an authenticated WordPress AJAX request, but authentication alone does not enforce the plugin’s intended editor capability.
- Recommendations: Require the configured GigPress capability and a dedicated AJAX nonce before accepting the array; validate each ID and order value.

**Bundled upgrade library contains dynamic code execution and shell execution:**
- Risk: `eval()` and `exec()` are present in the bundled generic upgrade code. Even if those paths are not normally reached, they expand the attack and maintenance surface.
- Files: `lib/upgrade.php:554`, `lib/upgrade.php:1225`
- Current mitigation: These calls are inside upgrade/helper paths rather than public request handlers.
- Recommendations: Remove unused legacy upgrade code, replace dynamic class aliases with supported PHP constructs, and eliminate shell execution or strictly constrain and document any required migration operation.

**Debug page prints settings without output escaping:**
- Risk: If debug mode is enabled, stored option values are echoed directly into HTML and can become stored XSS.
- Files: `admin/debug.php:22`, `admin/debug.php:31`
- Current mitigation: The debug submenu is gated by the `GIGPRESS_DEBUG` constant and `manage_options`.
- Recommendations: Escape keys and values with `esc_html()` and keep debug output disabled in production.

## Performance Bottlenecks

**Unindexed show queries:**
- Problem: Public feeds, shortcodes, sidebar output, and admin screens filter and sort large show tables by date, status, and foreign-key columns.
- Files: `admin/db.php:16`, `output/feed.php:16`, `output/ical.php:21`, `output/gigpress_shows.php:159`, `output/gigpress_sidebar.php:270`
- Cause: The schema defines primary keys only; there are no indexes on `show_date`, `show_expire`, `show_status`, or relationship IDs.
- Improvement path: Add migration-managed indexes for common filters and inspect query plans before and after; preserve compatibility with existing installations.

**N+1 migration updates:**
- Problem: The first database upgrade loads every legacy show and updates rows one at a time.
- Files: `admin/db.php:199`, `admin/db.php:204`, `admin/db.php:211`
- Cause: Upgrade logic iterates over a full result set and issues one update per record.
- Improvement path: Replace with bounded or set-based SQL updates and make migrations resumable for large databases.

## Fragile Areas

**Request handlers assume complete form payloads:**
- Files: `admin/handlers.php:14`, `admin/handlers.php:20`, `admin/handlers.php:29`, `admin/new.php:63`
- Why fragile: Many fields are read directly from `$_POST` before `isset()` or type validation. Programmatic requests, old forms, or malformed submissions can produce notices and invalid dates or IDs.
- Safe modification: Normalize request data at the handler boundary with defaults, strict allowlists, and `absint()`/`sanitize_*()` before building the show array.
- Test coverage: No automated coverage detected for missing fields, invalid dates, or authorization boundaries.

**Manual SQL assembled across output and admin paths:**
- Files: `output/gigpress_shows.php:162`, `output/gigpress_sidebar.php:270`, `admin/shows.php:120`, `gigpress.php:533`
- Why fragile: Although many numeric filters use `$wpdb->prepare()`, queries are assembled from fragments and hard-coded joins, making it easy to introduce an unprepared clause or inconsistent deleted/status handling.
- Safe modification: Centralize query builders and pass typed filter objects; use `$wpdb->prepare()` for every value and share status/date predicates.
- Test coverage: No query or output tests are present.

**Option registration accepts the whole settings array:**
- Files: `gigpress.php:568`, `admin/settings.php:1`
- Why fragile: `register_setting()` registers `gigpress_settings` without a visible sanitize callback, while the settings form writes many values and later code trusts them as configuration.
- Safe modification: Register a sanitize callback with explicit keys, types, ranges, and allowed values, then escape on output.
- Test coverage: No settings validation tests detected.

## Scaling Limits

**Feed and shortcode result sets are bounded by configuration but still materialize full rows:**
- Current capacity: RSS/iCalendar defaults to 100 rows and public list queries commonly select `*`.
- Limit: Large show, venue, or notes fields increase memory and response size; uncapped shortcode paths can load all matching rows.
- Scaling path: Select only required columns, paginate or stream exports, and enforce a safe upper bound for public query limits.

## Dependencies at Risk

**WordPress and PHP compatibility surface:**
- Risk: The plugin targets a 2015-era WordPress API and includes PHP-era compatibility code, while current runtimes may remove APIs or change escaping and database behavior.
- Files: `gigpress.php`, `admin/settings.php`, `lib/upgrade.php`, `readme.txt`
- Impact: Installation, admin rendering, or upgrades may fail on supported current hosts.
- Migration plan: Declare tested PHP/WordPress ranges, run compatibility checks in CI, and replace deprecated calls in small, tested increments.

## Missing Critical Features

**Capability checks are not consistently enforced at handler boundaries:**
- Problem: Mutating functions consistently verify nonces but generally rely on menu registration for access rather than checking capability inside each operation.
- Blocks: Safe reuse of handlers through alternate admin routes and reliable least-privilege enforcement.
- Files: `admin/handlers.php:226`, `admin/handlers.php:341`, `admin/handlers.php:429`, `gigpress.php:616`

## Test Coverage Gaps

**Security and serialization boundaries:**
- What's not tested: RSS XML escaping, iCalendar escaping, settings sanitization, AJAX authorization, CSV import/export, and upgrade behavior.
- Files: `output/feed.php`, `output/ical.php`, `admin/import-export.php`, `admin/handlers.php`, `admin/db.php`
- Risk: Stored content can corrupt feeds or produce XSS/injection, and upgrades can silently damage large databases.
- Priority: High

---

*Concerns audit: 2026-10-03*

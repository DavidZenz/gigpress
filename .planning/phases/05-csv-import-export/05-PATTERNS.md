# Phase 05: CSV Import/Export - Pattern Map

**Mapped:** 2026-10-11
**Files analyzed:** 21 candidate new/modified paths (proposed module names are recommendations)
**Analogs found:** 20 / 21; durable conversion journal has only a partial behavioral analog

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `admin/import-export.php` | component/controller | request-response, file-I/O | `admin/settings.php`, `admin/shows.php` | role-match |
| `admin/handlers.php` | controller/service | CRUD, batch | modern show-save and trash functions in same file | exact |
| `admin/csv.php` (proposed) | utility/service | transform, CRUD | modern show helpers in `admin/handlers.php` | role-match |
| `admin/csv-import.php` (optional extraction) | service | file-I/O, batch CRUD | `admin/handlers.php` show-save | role-match |
| `admin/csv-export.php` (optional extraction) | service | transform, file-I/O | `gigpress.php` export callback | exact |
| `admin/tour-conversion.php` (proposed) | service | batch CRUD | `admin/handlers.php` confirmed trash + verified show-save | role-match |
| `admin/artists.php` | controller/component | request-response | `admin/shows.php` preview dispatch | exact |
| `gigpress.php` | route/controller | request-response, file-I/O | existing `gigpress_export()` | exact |
| `css/gigpress-admin.css` | component | transform | current settings/entry styles; existing CSV selectors | role-match |
| `scripts/gigpress-admin.js` (conditional) | component | event-driven | current entry focus/reveal handlers | exact |
| `lib/parsecsv.lib.php` (only demonstrated repair) | utility | transform, file-I/O | existing parser/serializer | exact |
| `tests/compat/run.sh` | config/test | batch, file-I/O | current administration/public scenario routing | exact |
| `tests/compat/probe.php` | test | batch | current administration case dispatcher | exact |
| `tests/compat/csv-workflows.php` (proposed) | test | batch | `tests/compat/probe.php` case contract | exact |
| `tests/compat/csv-migrated.php` (proposed tracer + migrated cases) | test | batch CRUD | `tests/compat/public-migrated.php` | exact |
| `tests/compat/csv-import.php` (proposed) | test | CRUD, file-I/O | `tests/compat/administration-entry.php` | role-match |
| `tests/compat/csv-export.php` (proposed) | test | file-I/O, transform | `tests/compat/public-migrated.php` captured output + HTTP records | role-match |
| `tests/compat/csv-conversion.php` (proposed) | test | batch CRUD | `tests/compat/administration-entry.php` snapshot/recovery assertions | role-match |
| `tests/compat/fixtures/csv/*` (proposed fixture family) | test | file-I/O | `tests/compat/fixtures/shows.csv`, reconstructed upgrade fixtures | exact |
| `tests/compat/browser-bootstrap.php` | test | request-response, file-I/O | existing owned HTTP/snapshot functions in same file | exact |
| `tests/compat/CSV-BROWSER.md` (proposed) | config/test | batch | `tests/compat/ADMIN-BROWSER.md`, `tests/compat/PUBLIC-BROWSER.md` | role-match |

All existing analog paths above were verified by nonempty `git ls-files -- <path>` output. No install/runtime mirror is an analog. Optional extractions need not all be created: choose a small procedural seam, then keep that filename stable in every plan. No new model, migration or package is indicated.

## Pattern Assignments

### Import screen: `admin/import-export.php`

**Analogs:** `admin/settings.php` sections (58–75, 111–136) and `admin/shows.php` controls (53–77, 128–132). Use stacked semantic sections and associated help IDs. Keep upload, related-post control, outcomes and another upload available on the same page. Settings sections provide organization; the show-list helper provides escaped labels/options and retained selected values.

Concrete help/control pattern, `admin/settings.php:118–120`:

```php
<input type="<?php echo esc_attr($input_type); ?>" class="regular-text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php disabled($disabled); ?> <?php if ($input_type === 'number') echo 'min="0" step="1"'; ?> aria-describedby="<?php echo esc_attr($help_id); ?>" />
<?php } ?>
    <p class="description" id="<?php echo esc_attr($help_id); ?>"><?php echo esc_html($help); ?>
```

Concrete select pattern, `admin/shows.php:128–132`:

```php
function gigpress_list_select($key, $label, $options, $value) {
    echo '<label for="gp-list-' . esc_attr($key) . '">' . esc_html($label) . '</label> <select id="gp-list-' . esc_attr($key) . '" name="' . esc_attr($key) . '">';
    foreach ($options as $choice => $text) echo '<option value="' . esc_attr($choice) . '"' . selected((string) $value, (string) $choice, false) . '>' . esc_html($text) . '</option>';
    echo '</select> ';
}
```

Copy the rendering shape into a CSV-specific helper rather than importing list persistence/query semantics. The list reset preserves its scope; CSV reset must restore all artists/tours/dates. Native `details`/`summary` is a new markup choice, not an existing extracted disclosure contract.

### Import helpers and handler: `admin/handlers.php`, proposed CSV modules

**Analog:** modern show functions `admin/handlers.php:29–278`. Preserve procedural `gigpress_*` functions, global `$wpdb`, translated errors, normalized values, separate validation/preparation/mutation/outcome rendering and explicit returned state. `admin/handlers.php:185–255` is the source for capability/method/action checks, readiness, strict write failure and persisted-field verification. `257–276` is the modern outcome notice source. Use those modern ranges instead of older venue/CSV handlers that print raw errors or trust a write result alone.

Concrete calendar validation, `admin/handlers.php:37–40`:

```php
function gigpress_show_calendar_date($value) {
    if (!is_string($value) || !preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $value, $parts)) return false;
    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : false;
}
```

Concrete status validation, `admin/handlers.php:96`:

```php
if (!in_array($raw['show_status'] ?? 'active', array('active', 'soldout', 'cancelled'), true)) $errors['show_status'] = __('Select an available show status.', 'gigpress');
```

Reuse the strict date helper; adapt status errors to CSV row/field guidance. Do not pass CSV bytes through `gigpress_show_raw_state()` because its `wp_unslash` addresses POST request data. Localize CSV normalization and preserve represented literal values.

Existing compatible duplicate predicate, `admin/handlers.php:1088–1092`:

```php
"SELECT count(*) FROM " . GIGPRESS_SHOWS . " WHERE show_artist_id = '%d' AND show_date = '%s' AND show_time = '%s' AND show_venue_id = '%d' AND show_status != 'deleted'",
$show['artist_id'],
$show['Date'],
$show['Time'],
$show['venue_id']
```

This is membership to retain, not a safe implementation to copy wholesale. Validate the entire record before entity creation, distinguish query failure from absence, verify created IDs and final fields, and assign each record exactly one outcome. Replace the legacy positional format array (`1127`) with formats generated in actual data order; textual status must be `%s`. Replace assignment-based related opt-in (`1123`) with exact scalar `'y'` validation.

Imports/include convention, `admin/artists.php:3–4`:

```php
function gigpress_artists() {
    require_once __DIR__ . '/handlers.php';
```

Use `__DIR__` for sibling procedural modules. Keep upload validation in the production outer handler; a trusted parsed-row helper enables database cases without a request-controlled upload bypass.

### Export callback and proposed export module: `gigpress.php`

**Analog:** existing `gigpress_export()` (`656–703`) for exact transfer shape; modern handlers for route boundaries. Retain the existing 23 ordered columns, exclusion of deleted shows, required artist/venue joins, optional tour join and descending date/time order (`680–685`). One normalized CSV state and shared membership query must drive screen labels/count and authoritative download; show-list query is not equivalent.

Concrete projection/serializer pattern, `gigpress.php:692–698`:

```php
$show['show_expire'] = ( $show['show_date'] == $show['show_expire'] ) ? '' : $show['show_expire'];
$show['show_related_url'] = ( $show['show_related'] ) ? gigpress_related_link($show['show_related'], 'url') : '';
$export_shows[] = $show;
}

$export = new parseCSV();
$export->output($name, stripslashes_deep($export_shows), $fields, ',');
```

Retain projection meanings; characterize the existing broad time-sentinel test and `stripslashes_deep` before changing them. The latter is not permission to lose backslashes. D-17 is resolved: preserve formula-like strings and leading-plus phones exactly; guidance explains explicit spreadsheet text import. No rewriting/blocking/formula-neutralization mode.

Serializer order contract, `lib/parsecsv.lib.php:528–534`:

```php
// create data
foreach( $data as $key => $row ) {
    foreach( $row as $field => $value ) {
        $entry[] = $this->_enclose_value($value);
    }
    $string .= implode($delimiter, $entry).$this->linefeed;
    $entry = array();
}
```

The serializer consumes insertion order, not header-key lookup. Keep CSV escaping here and HTML escaping at notices. Only modify parser code for independently demonstrated structure defects.

### Conversion: `admin/artists.php`, proposed `admin/tour-conversion.php`

**Analogs:** `admin/shows.php:4–18` preview dispatch; `admin/handlers.php:410–515` owner-bound confirmed trash flow and aggregate outcomes; show-save `185–255` persisted-value verification. Existing converter (`1225–1248`) establishes affected active tours and all linked show scope, but is the defect to replace, not the mutation pattern to copy.

Concrete screen dispatch, `admin/shows.php:10–13`:

```php
if (($_REQUEST['gpaction'] ?? null) === 'delete') {
    $outcome = gigpress_delete_show();
    if (($outcome['status'] ?? '') === 'preview') { echo ob_get_clean(); return $outcome; }
    if (isset($outcome['state'])) $state = $outcome['state'];
}
```

Copy early preview return and structured outcome handoff. Confirm/Cancel must bind current owner, expiry and exact confirmed identities, consume intent, and reject stale selections before writes. Review names/counts includes trashed show references. Resolve exactly one byte-exact artist name; retain ambiguity and independent per-tour outcomes. A new durable WordPress-option journal is required by the proposed recovery design: no existing confirmed trash transient is a complete durable-conversion analog. Persist and read back journal before relying on it, verify all relationship targets/remaining references, then verify source deletion. Zero affected rows is not by itself failure for an already-completed or no-show operation.

### CSS and optional JavaScript

`css/gigpress-admin.css:182–190` owns the two opposing 48% floats; change these CSV selectors to stacked sections. Follow current settings/entry spacing without broad global selector changes. `scripts/gigpress-admin.js:27–39` is the focus enhancement analog:

```javascript
// These notices exist only after a save attempt, never on an ordinary load.
var feedback = document.getElementById('gigpress-errors') || document.querySelector('#message.notice-success');
if (feedback) feedback.focus();
```

Scope CSV enhancements to its wrapper and result IDs. Render essential fields/help/recovery server side; disclosures and export filter recovery must work without JavaScript.

### CSV case modules, fixture family, dispatcher and runner

**Analogs:** `tests/compat/probe.php:41–93` exact case/aggregate contract; `tests/compat/administration-entry.php:3–10,28–67` actual handler output and snapshots; `tests/compat/public-migrated.php:51–104` every reconstructed source with independent expected manifests. Production PHP stays separate from fixture-only HTTP/failure injection.

Concrete fail-closed case gate, `tests/compat/probe.php:56–63`:

```php
$checks = is_array($record['checks'] ?? null) ? $record['checks'] : array();
$active = is_plugin_active('gigpress/gigpress.php');
$ok = $active && $ready && ($record['case'] ?? null) === $case && count($checks) > 0
    && !array_filter($checks, function ($value) { return $value !== true; })
    && !$pluginErrors && !$menuWarnings;
return array_merge($record, array('case' => $case, 'status' => $ok ? 'PASS' : 'FAIL', 'checks' => $checks,
    'ready' => $ready, 'plugin_active' => $active, 'assertion_count' => count($checks), 'warning_count' => count($menuWarnings), 'fatal_count' => 0,
    'plugin_error_count' => count($pluginErrors), 'elapsed_seconds' => round(microtime(true) - $started, 4)));
```

Concrete migrated loop, `tests/compat/public-migrated.php:96–104`:

```php
foreach ($versions as $version) {
    $fixture = gigpress_upgrade_preservation_fixture($version);
    $seeded = is_array($fixture) && upgrade_preservation_seed($fixture);
    $source = $seeded ? gigpress_upgrade_preservation_snapshot() : null;
    unset($GLOBALS['gigpress_db_bootstrap_result']);
    $ready = function_exists('gigpress_db_bootstrap') ? gigpress_db_bootstrap() : array('status' => 'blocked');
    $afterMigration = gigpress_upgrade_preservation_snapshot();
    list($manifestChecks, $manifestMatches) = gigpress_upgrade_preservation_manifest_matches($fixture, $afterMigration, $source);
    $gpo = get_option('gigpress_settings');
```

Use versions `1.0` through `1.6`, with tracer `1.4` first. Expected CSV values belong in fixture-owned assertions, not values derived from the importer. Same-site retries preserve original identities/settings/posts; destination imports intentionally allocate destination IDs. Separate supplemental hostile/status/literal data from canonical reconstructed source evidence.

Runner analogs: `tests/compat/run.sh:1622–1629` allowlists/table prefix and `1691–1706` positive boolean/assertion counts plus exact aggregate membership. New `csv-workflows` must wire matrix and cell routes, probe dispatch/output, required registry, fingerprint/evidence aggregation and result assertion together. It is not currently a runnable scenario.

### Owned real HTTP/browser fixture and acceptance document

`tests/compat/browser-bootstrap.php:24–31` is the full no-side-effects snapshot source; `56–70` is the authenticated cookie-jar HTTP source. Its current POST helper uses `http_build_query`; add a separate actual multipart branch using uploaded-file transport rather than mislabeling URL-encoded POST as upload evidence. Preserve loopback-only paths, owned cookie jars, cleanup metadata and fixture lifecycle in runner. Existing browser docs supply acceptance-record organization; spreadsheet text-import evidence is client-specific and separate from PHP/CSV literal assertions.

## Shared Patterns

- **Guards:** Modern show-save and confirmed trash in `admin/handlers.php:185–255,410–515`: configured capability, exact method/action, nonce and readiness before reads/writes that need protection. Current CSV nonce alone is insufficient.
- **Readiness:** `admin/handlers.php:3–10` returns false with an escaped translated actionable notice when bootstrap is blocked; preserve the established boundary.
- **Output:** Use `esc_html` for row/filename/system text, `esc_attr` for control values, `esc_url` for links, translated full sentences before escaping. Typography helpers are not encoding. CSV serializer receives data, not HTML-escaped strings.
- **Validation:** Strict scalar/enum shapes, strict calendar date helper, bounded IDs and prepared SQL. Blank status defaults to active for new import; existing status `0` stays unchanged and is identified for manual correction.
- **Outcomes:** Return structured status/errors/completed identities; render later. Every CSV record has one imported/duplicate/rejected/failed outcome; file-level errors remain separate. Continue independent conversions and preserve retry state.
- **Verification:** SQL return and insert ID do not prove final state. Read back intended fields/identities; snapshot complete tables/settings/posts for forbidden side effects. Harness passing requires nonempty exact-case assertions and absence of warnings/errors.

## Shared Ownership and Independent Vertical Plans

| Hotspot | Single owner recommendation | Independent work after seam settles |
|---|---|---|
| `admin/handlers.php` | Tracer/import integrator owns wrapper/include/contract edits | Conversion owner implements only its procedural module |
| `admin/import-export.php` | Screen/export owner | Import owner returns the agreed outcome structure |
| `gigpress.php` | Export owner | Conversion does not touch callback/bootstrap |
| `tests/compat/run.sh`, `probe.php`, `browser-bootstrap.php` | Harness integrator | Import/export/conversion owners implement distinct case modules and fixture files |
| `admin/csv.php` | Initial tracer contract owner | Freeze header/status/outcome/state interfaces before parallel work |
| global CSS/JS and shared normalization | Screen/integration owner | Narrow CSV selectors/helpers preserve accepted public/admin contracts |

First vertical slice establishes a protected migrated tracer, textual status/verified row writes, literal serialization, duplicate retry and registry. Export screen/query/literals and conversion confirmation/journal can then be independent vertical plans using distinct modules. Final integrator closes aggregate evidence and browser acceptance. Avoid two workers editing shared routing or one giant handler simultaneously.

## No Analog Found

| File / responsibility | Role | Data Flow | Reason |
|---|---|---|---|
| Durable conversion journal within proposed `admin/tour-conversion.php` | service | batch CRUD | Existing trash intent is temporary; no complete persisted per-tour/per-show recovery journal exists. Use research design plus verified write/read-back patterns. |

Native CSV disclosures, actual multipart harness upload and CSV source-bound aggregate are additions with partial UI/HTTP/evidence analogs; they must not be described as existing coverage.

## Metadata

**Analog search scope:** tracked root PHP, admin PHP, bundled parser, admin CSS/JS, real-WordPress compatibility harness.
**Files inspected:** 14 source files (bounded ranges; plus tracked fixture/doc inventory).
**Strong analog families:** modern handlers, settings/show screen rendering, existing CSV serializer/export, fail-closed harness, migrated fixture loop. Search stopped after these sufficient matches.
**Project context:** No working-directory `AGENTS.md` or project skill directories exist; supplied AGENTS instructions and `/Users/davidzenz/.codex/RTK.md` were followed. Read-only analysis; no tests, source edits or commits.
**Tool adaptation:** Dedicated Read/Write tools are unavailable in this session. Reads used RTK-prefixed file tools; this artifact was created with the available structured `apply_patch` tool, without shell/heredoc writes.
**Pattern extraction date:** 2026-10-11.

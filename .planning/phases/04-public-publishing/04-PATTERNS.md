# Phase 04: Public Publishing - Pattern Map

**Mapped:** 2026-10-05
**Files analyzed:** 27 candidate create/modify files
**Analogs found:** 25 / 27 (existing files are their own strongest analog)

Scope is PUB-01/PUB-02 and the locked D-01–D-16 decisions. Paths marked NEW are proposed planner choices, not implemented interfaces. Canonical Phase 02 fixtures and migration helpers are reuse dependencies, not files to rewrite. No runtime or browser acceptance was performed for this map.

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `gigpress.php` | utility | transform | same file, preparation/resolver | exact |
| `output/gigpress_shows.php` | controller | request-response | same file, prepared partial dispatch | exact |
| `output/gigpress_sidebar.php` | controller | request-response | same file, compact partial dispatch | exact |
| `output/gigpress_related.php` | controller | request-response | `output/gigpress_shows.php` | exact |
| `output/feed.php` | controller | request-response | same RSS handler | exact |
| `output/ical.php` | controller | request-response | same iCalendar handler | exact |
| `templates/shows-list-start.php` | component | transform | same table-start partial | exact |
| `templates/shows-list.php` | component | transform | same show partial | exact |
| `templates/shows-list-end.php` | component | transform | same table-end partial | exact |
| `templates/shows-list-footer.php` | component | transform | same subscription partial | exact |
| `templates/shows-artist-heading.php` | component | transform | same heading partial | exact |
| `templates/shows-tour-heading.php` | component | transform | same heading partial | exact |
| `templates/shows-list-empty.php` | component | transform | same empty partial | exact |
| `templates/sidebar-list.php` | component | transform | `templates/shows-list.php` | exact |
| `templates/related.php` | component | transform | `templates/shows-list.php` | exact |
| `css/gigpress.css` | component | transform | same hook-based stylesheet | exact |
| `scripts/gigpress.js` | component | event-driven | same navigation/toggle script | exact |
| `tests/compat/run.sh` | utility | batch | same scenario/evidence/browser runner | exact |
| `tests/compat/probe.php` | test | batch | same module dispatcher | exact |
| `tests/compat/compose.yaml` | config | batch | `tests/compat/compose.browser.yaml` | role-match |
| `tests/compat/compose.browser.yaml` | config | request-response | same owned loopback extension | exact |
| NEW `tests/compat/public-publishing.php` | test | request-response | `tests/compat/probe.php` case wrapper | exact |
| NEW `tests/compat/fixtures/public-publishing/supplemental.php` | model | batch | `tests/compat/fixtures/upgrade-preservation/1.4.php` | exact |
| NEW `tests/compat/fixtures/public-publishing/overrides.php` | test | file-I/O | none for complete/mixed override ladder | none |
| `tests/compat/browser-bootstrap.php` | test | request-response | same fixture bootstrap | exact |
| NEW `tests/compat/PUBLIC-BROWSER.md` | config (procedure) | request-response | `tests/compat/ADMIN-BROWSER.md` | role-match |
| NEW `docs/public-template-adoption.md` | config (guidance) | transform | none for responsive adoption contract | none |

Existing headings/end/empty partials and the sidebar controller may need no edit after characterization. Keep them in plan ownership only when the final marker/encoding design requires a change. Optional format helper code can live in the existing utility/handlers; no separate serializer file is required. Machine matrix evidence and `04-BROWSER.md` are execution outputs, not source analogs.

## Pattern Assignments

### `gigpress.php` (utility, transform)

**Analog:** existing resolver and preparation in `gigpress.php`. No import framework: procedural WordPress globals, native functions and returned arrays.

**Resolver core, lines 182–191:**
```php
if(file_exists(get_stylesheet_directory() . '/gigpress-templates/' . $path . '.php')) {
    $load = get_stylesheet_directory() . '/gigpress-templates/' . $path . '.php';
} elseif(file_exists(get_template_directory() . '/gigpress-templates/' . $path . '.php')) {
    $load = get_template_directory() . '/gigpress-templates/' . $path . '.php';
} elseif(file_exists(WP_CONTENT_DIR . '/gigpress-templates/' . $path . '.php')) {
    $load = WP_CONTENT_DIR . '/gigpress-templates/' . $path . '.php';
} else {
    $load = WP_PLUGIN_DIR . '/gigpress/templates/'  . $path . '.php';
}
return $load;
```

**Preparation shape, lines 227–235:**
```php
function gigpress_prepare($show, $scope = 'public') {
    global $wpdb, $gp_countries, $gpo;
    $showdata = array();
```

**Optional time, lines 264 and 321:**
```php
$timeparts = explode(':', $show->show_time);
$showdata['time'] = ($timeparts[2] == '01') ? '' : date($gpo['time_format'], mktime($timeparts[0], $timeparts[1]));
```

Keep public/admin/venue scopes and existing keys; add raw/plain destination values without changing HTML fragment keys into raw text. Preserve midnight versus the seconds-01 sentinel. The active-ticket predicate at line 312 and status fragments at 314–316 govern existing visibility. Do not copy unsafe concatenation from lines 237–303 as an encoding recommendation. Calendar URL assembly at 329–337 is the destination inventory: encode components once and escape the final HTML attribute. New ICS TEXT/folding helpers have no standards-compliant existing implementation to copy.

### Public controllers: `output/gigpress_shows.php`, `output/gigpress_sidebar.php`, `output/gigpress_related.php`

**Analog:** main prepared-show include pipeline; sidebar's own compact dispatch.

**Main core, `output/gigpress_shows.php` lines 184–199 (selected statements):**
```php
$showdata = gigpress_prepare($show, 'public');
if($showdata['tour'] && $showdata['tour'] != $current_tour && !$tour) {
    $current_tour = $showdata['tour'];
    include gigpress_template('shows-tour-heading');
}
$class = $showdata['status'];
++ $i; $class .= ($i % 2) ? '' : ' gigpress-alt';
$class .= ($showdata['tour'] && !$tour) ? ' gigpress-tour' : '';
include gigpress_template('shows-list');
```

Preserve the omitted existing divider branch, grouping, caller variables, queries and order. The grouped path includes start/body/end/footer at lines 177–214; apply equivalent changes to the ungrouped path too. JSON-LD emission at 217–227 is a correction boundary, not an encoder analog: replace unsafe script serialization using research guidance and cover every emission branch including related output.

**Compact core, `output/gigpress_sidebar.php` lines 292, 309–313:**
```php
$showdata = gigpress_prepare($show, 'public');
$class = ($i % 2) ? 'gigpress-alt ' : ''; $i++;
$class .= ($showdata['tour'] && $show_tours) ? 'gigpress-tour ' . $showdata['status'] : $showdata['status'];
include gigpress_template('sidebar-list');
```

Keep widget/related markup compact. These public reads need no new auth guard; no permission or mutation behavior belongs in publishing. Tests should assert anonymous output and unchanged database snapshots.

### Bundled main partials (component, transform)

**Analogs:** `templates/shows-list-start.php` and `templates/shows-list.php`; footer/headings/end/empty remain independent resolver participants.

**Column contract, start lines 18–23:**
```php
$cols = 3;
$cols = ($total_artists == 1 || $artist || $group_artists == 'yes') ? $cols : $cols + 1;
$cols = (!empty($gpo['display_country'])) ? $cols + 1 : $cols;
?>
<table class="gigpress-table <?php echo $scope; ?>" cellspacing="0">
```

**Show/date contract, show lines 14–18:**
```php
<tr class="gigpress-row <?php echo $class; ?>">
    <td class="gigpress-date"><?php echo $showdata['date']; ?>
        <?php if($showdata['end_date']) : ?> - <?php echo $showdata['end_date']; ?><?php endif; ?>
    </td>
```

**Existing calendar condition and fragment contract, show lines 41, 46–47:**
```php
if($scope != 'past') : ?>
<span><?php echo $showdata['gcal']; ?></span>
<span><?php echo $showdata['ical']; ?></span>
```

Keep the exact scope rule, including `all`; it is not a per-row future test. Move direct actions to date/time with the mandated labels, retain hook spellings and all details, use real labels in narrow reading order, and retain a wide table. Do not install a marker unconditionally in the bundled start: resolve the participating structural set first so an owner show body/end cannot inherit stacked rules accidentally. Footer/headings can opt in independently only when their own resolved markup is bundled. Cases must cover bundled start + owner body and owner start + bundled body, not only a complete override.

### `templates/sidebar-list.php`, `templates/related.php`

**Analog:** prepared fragments and conditionals in `templates/shows-list.php` above; copy their own current compact structure rather than the new main-list layout. Retain each surface's options, status classes and link visibility. Scoped wrapping/status treatment can improve readability; sharing a legacy hook alone must not opt an owner partial into the new layout.

### `css/gigpress.css`, `scripts/gigpress.js`

**Analog:** existing public hook conventions, theme inheritance and progressive navigation.

**CSS core, lines 25–30:**
```css
.gigpress-table {
    width: 100%;
    border: none;
    border-collapse: collapse;
    border-top: 1px solid #CCC;
    margin: 0.5em 0 1em 0;
}
```

Use content width, theme fonts/link colors, subtle dividers and explicit bundled marker selectors. Existing global cancelled grey rules at 127–130 and status selectors at 148–158 require scoped corrections for readable details. Legacy calendar positioning/hiding rules must remain isolated from direct bundled actions. Do not copy uppercase ticket styling as a requirement; preserve an emphasized link and saved label.

**Navigation core, script lines 11–14:**
```javascript
$("select.gigpress_menu").change(function()
{
    window.location = $(this).val();
});
```

Retain legacy toggle support at lines 5–10 for owner templates; bundled links must be visible and ordinary anchors without script execution. The existing `disable_js` enqueue gate in `gigpress.php` 142–145 is not evidence that browser JavaScript was disabled.

### `output/feed.php`, `output/ical.php`

**Analogs:** their existing read/query/prepare/response boundaries. Preserve endpoints, filters, limits, membership and UID construction; use the existing handlers rather than adding routing or a framework.

Their existing serializers are defect examples, not safe patterns: RSS channel creation/CDATA handling (`output/feed.php` 20–35,37–104), ICS TEXT and date/TZID/stamp emission (`output/ical.php` 49–83), and shared calendar preparation (`gigpress.php` 270–290). Use research's format rules for corrected code: XML text and safe HTML descriptions separately; typed ICS properties with CRLF, TEXT escaping and UTF-8-safe folding; plain script-safe JSON-LD. Preserve all source storage/date/expiration meanings. Empty RSS must have a channel; empty ICS needs valid calendar serialization. Independent parsed values and property/event counts are required; substring presence is insufficient.

### `tests/compat/probe.php`, NEW `tests/compat/public-publishing.php`

**Analog:** case wrapper in probe lines 41–61 and migration helpers. Native `require_once`, named booleans, readiness/active-plugin checks and structured result records are the project convention.

**Dispatch and fail-closed result, probe lines 50–61:**
```php
if (is_readable($module)) {
    require_once $module;
    if (function_exists($callback)) $record = call_user_func($callback, $case);
}
$checks = is_array($record['checks'] ?? null) ? $record['checks'] : array();
$active = is_plugin_active('gigpress/gigpress.php');
$ok = $active && $ready && ($record['case'] ?? null) === $case && count($checks) > 0
    && !array_filter($checks, function ($value) { return $value !== true; })
    && !$pluginErrors && !$menuWarnings;
return array_merge($record, array('case' => $case, 'status' => $ok ? 'PASS' : 'FAIL', 'checks' => $checks,
    'ready' => $ready, 'plugin_active' => $active, 'assertion_count' => count($checks), 'warning_count' => count($menuWarnings), 'fatal_count' => 0,
    'plugin_error_count' => count($pluginErrors), 'elapsed_seconds' => round(microtime(true) - $started, 4)));
```

Reuse E_ALL collection at probe 3–33 without contaminating response bytes. Register public purpose, module/cases and top-level failure/result handling explicitly; missing modules or empty checks must fail. The migration aggregate at 376–404 rejects duplicate cases/missing modules and launches isolated subprocesses. Extend this shape without accepting unparsed last-line output or unknown/missing cases as PASS.

### Supplemental fixture and migration reuse

**Analog:** tracked `tests/compat/fixtures/upgrade-preservation/1.4.php` (return-array schema, source-specific IDs and independent expectations).

**Fixture identity, lines 6–9:**
```php
return array(
    'label' => 'reconstructed-1.4',
    'prefix' => 'compat_legacy_',
    'settings' => array(
```

Use the real seed from `tests/compat/probe.php` 325–346, which verifies prefix, reconstructs historical schema, inserts a real linked post and seeds canonical records/settings. It destroys plugin tables, so only call it inside an owned disposable database. Capture migrated expectations with the existing source-specific row/schema checks, then publish in that same fixture before cleanup. Do not reseed fresh rows and call them migrated evidence.

**Snapshot core, `tests/compat/upgrade-preservation-migrations.php` 24–28:**
```php
function gigpress_upgrade_preservation_snapshot() {
    global $wpdb;
    $data = array('prefix' => $wpdb->prefix, 'settings' => get_option('gigpress_settings'));
    foreach (array('shows' => 'show_id', 'artists' => 'artist_id', 'venues' => 'venue_id', 'tours' => 'tour_id') as $kind => $id) {
        $data[$kind] = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gigpress_' . $kind . ' ORDER BY ' . $id, ARRAY_A);
```

The rest of that function captures schemas and linked post contents at 29–38. Compare snapshots after public reads. Keep supplemental hostile/long/status/midnight/no-time data labelled separately; never edit canonical fixture expectations to accommodate renderer behavior.

### `tests/compat/run.sh` (utility, batch)

**Analog:** existing matrix flags (495–536), scenario validation/prefix selection (1207–1213), cell result enrichment (1253–1267) and administration evidence validator (640–712).

**Evidence validation excerpts, lines 649–650, 699–703:**
```javascript
const exact = (actual,required) => Array.isArray(actual) && actual.length === required.length && same([...actual].sort(), [...required].sort());
const must = (condition,reason) => { if (!condition) throw new Error(reason); };
```
```javascript
for (const c of a.cases) {
    must(c.status === 'PASS' && c.plugin_active === true && c.ready === true && c.checks && !Array.isArray(c.checks) && typeof c.checks === 'object', 'administration ready case');
    const checks = Object.entries(c.checks);
    must(checks.length > 0 && checks.every(([name,value])=>name.length>0 && value === true) && Number.isInteger(c.assertion_count) && c.assertion_count === checks.length,'positive named checks');
    must(c.warning_count === 0 && c.fatal_count === 0 && c.plugin_error_count === 0 && Number.isFinite(c.elapsed_seconds) && c.elapsed_seconds >= 0, 'case zero errors/timing');
```

Copy tracked-source hashing at 646–652, exact target/case coverage and pinned identities at 663–689, and aggregate counts. Create a distinct public schema/required-case registry rather than relabelling administration evidence. Register `public-publishing` in BOTH matrix and cell allow-lists, forward its cases, use nondefault legacy prefix and migrate before public callbacks. Current proposed `public-fixture --action check` and public matrix commands are NEW and unavailable until these changes land.

### Browser bootstrap, Compose and NEW procedure

**Analogs:** `tests/compat/browser-bootstrap.php`, `tests/compat/compose.browser.yaml`, `tests/compat/run.sh` 1017–1098, `tests/compat/ADMIN-BROWSER.md` 13–23.

**Compose ownership/loopback, browser Compose lines 1–11:**
```yaml
services:
  db:
    labels:
      gigpress.browser.owner: ${COMPAT_BROWSER_OWNER:?private owner marker required}
  wordpress:
    labels:
      gigpress.browser.owner: ${COMPAT_BROWSER_OWNER:?private owner marker required}
    ports:
      - "127.0.0.1::80"
    volumes:
      - ./browser-bootstrap.php:/compat/browser-bootstrap.php:ro
```

Retain metadata modes 0600/0700, same-user/repository validation, no symlinks/no `eval`, project/owner validation BEFORE teardown, credential privacy and source/runtime identity. Runner 1066–1084 verifies container/volume removal and preserves recovery metadata on failure; 1087–1097 checks each present container's owner and supports partial cleanup. External Compose/database overrides are rejected at runner 74–75. Extend these safeguards for public retained fixtures; do not connect to a site supplied by arbitrary environment variables.

Browser bootstrap's current seed is fresh admin data (`browser-bootstrap.php` 248–281), so reuse lifecycle/HTTP capture only and add an explicit migrated public seed. Add real public pages and fixture-only owner partials inside disposable runtime paths. The guide analog distinguishes HTTP assertions from actual keyboard/focus/disabled-script observations. Record measured 320 CSS pixels, scroll/client widths, actual layout/readability, wide table, narrow content/widget areas, computed inheritance, Tab/Enter links and JavaScript truly disabled. Source/HTTP checks cannot certify those observations.

## Shared Patterns

### Destination encoding

Apply to shared preparation, every public partial, feeds and JSON-LD. Existing `esc_url` call sites in `gigpress.php` 256,266,297,312 show native WordPress API use, but mixed raw/texturized content in the same fragment is not a safe pattern. Use `esc_html`/`esc_attr`/`esc_url` at their respective HTML destinations, KSES for permitted rich notes, XML serialization for RSS, ICS-specific TEXT encoding and typed values, and `wp_json_encode` with HTML-sensitive hex flags plus failure handling for script JSON. No universal encoder or store rewrite.

### Template ownership and optional assets

Apply resolver excerpt above to every partial; retain variables, filenames and hooks. Evaluate the complete structural set for bundled responsiveness; independent footer/compact markers cannot leak to owner markup. Preserve disable-css/disable-js and custom stylesheet behavior. Adoption guidance should explain explicit opt-in and mixed overrides with exact filenames/hooks, without implying old custom templates automatically receive the new layout.

### Positive evidence and read-only publishing

Apply probe wrapper and snapshot excerpts above to all public cases. Expected membership/order/values come from independent fixtures, not production queries/serializers. Case registry, runtime/image pins, tracked source hashes, nonempty named `true` assertions and zero warnings/fatals/plugin errors all form acceptance. Browser evidence remains separate. CSV import/export and inherited CR-04/05/07 belong to Phase 05.

### MVP execution order

First create one vertical `tracer-1.4`: seed canonical historical data → real migration → independent preservation/readiness → main listing + widget + related post + RSS + ICS + one override in the same owned runtime → snapshot unchanged. Include a date/ticket/calendar path. Then implement bundled presentation/format boundaries and broaden cases to all recognized versions/current state, supplemental values and complete/mixed override priority. Build the full evidence validator and actual browser record around working cases; do not front-load unrelated harness abstractions.

## No Analog Found

| File / responsibility | Role | Data Flow | Reason |
|---|---|---|---|
| NEW `tests/compat/fixtures/public-publishing/overrides.php` | test | file-I/O | No existing complete/mixed child/parent/content priority fixture; use current resolver and owned runtime lifecycle as component patterns. |
| NEW `docs/public-template-adoption.md` | config (guidance) | transform | No existing explicit bundled responsive opt-in adoption guide; derive exact guidance from implemented marker rules and D-14. |

Correct ICS folding/TEXT/type serialization and mixed-partial marker computation also lack correct existing code to copy. Use RESEARCH.md's standards and independent acceptance cases; existing serializers are not a substitute.

## Metadata

**Analog search scope:** root public utility, `output/`, `templates/`, `css/`, `scripts/`, `tests/compat/` and Phase 04 context/research.
**Files scanned:** 27 source/harness candidates; five principal families (preparation/resolver, rendering/partials, assets/format boundaries, migrated probe/evidence, owned browser lifecycle).
**Tracked-source gate:** every existing source analog named above was checked with `git ls-files -- <path>`; no install/runtime mirror analogs are emitted. Proposed NEW paths are clearly labelled.
**Project discovery:** no root on-disk AGENTS.md or project `.codex/skills/` / `.agents/skills/` entries were found; user-supplied AGENTS/RTK instructions apply.
**Pattern extraction date:** 2026-10-05.
**Evidence boundary:** static pattern map only; no source changes, tests, commits or shared tracking edits.

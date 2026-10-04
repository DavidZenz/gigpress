# Phase 03: Administration Workflows - Pattern Map

**Mapped:** 2026-10-04  
**Files classified:** 15 candidate paths (11 existing; 4 explicitly NEW proposals)  
**Analogs:** 15 / 15 have useful structural matches; novel behavior gaps are listed below.

## File Classification

Existing paths below were verified with `rtk git ls-files`. Proposed helper/case names are implementation suggestions, not files already present or locked by CONTEXT/RESEARCH. Prefer local functions when a separate helper adds no useful ownership boundary.

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `admin/new.php` MODIFY | component | request-response | same file, 3–16, 93–138, 188–206 | exact |
| `admin/handlers.php` MODIFY | controller/service | CRUD | same file, 198–237, 247–294, 358–396 | exact |
| `admin/shows.php` MODIFY | component | request-response | same file, 27–121, 151–179, 282–292 | exact |
| `admin/settings.php` MODIFY | component | request-response | same file, 12–19, 153–174, 215–228 | exact |
| `gigpress.php` MODIFY | config/utility | request-response | same file, 60–67, 93–102, 408–436, 462–464 | exact |
| `scripts/gigpress-admin.js` MODIFY | component | event-driven | same file, 1–39 | exact |
| `css/gigpress-admin.css` MODIFY | component | transform | same file, 28–43 | exact |
| `tests/compat/run.sh` MODIFY | test/config | batch | same file, 787–790, 799–832, 854–866 | exact |
| `tests/compat/probe.php` MODIFY | test/controller | batch | same file, 581–585, 814–834 | exact |
| `tests/compat/upgrade-preservation-crud.php` MODIFY if adapters change | test | CRUD | same file, 4–14, 48–65, 145–205 | exact |
| `tests/compat/compose.yaml` MODIFY only if browser transport needs it | config | request-response | same tracked disposable fixture | exact |
| `admin/workflow-state.php` NEW optional | utility | transform | `admin/handlers.php`, 198–237 | role-match |
| `tests/compat/admin-show-entry.php` NEW proposed | test | CRUD/request-response | `tests/compat/upgrade-preservation-crud.php`, 4–14, 145–205 | exact |
| `tests/compat/admin-show-list.php` NEW proposed | test | CRUD/request-response | `tests/compat/upgrade-preservation-crud.php`, 4–14, 185–205 | exact |
| `tests/compat/admin-settings.php` NEW proposed | test | CRUD/request-response | `tests/compat/upgrade-preservation-crud.php`, 4–14, 72–81 | exact |

## Pattern Assignments

### `admin/new.php` — show entry (D-01–04, D-14–15, D-18)

**Analog:** existing renderer; procedural PHP, WordPress globals and translation functions. Entry invokes existing handlers before deriving render state (`admin/new.php:3–16`). Retain edit/copy/add routing and the separate update ID:

```php
// admin/new.php:192–195
<form method="post" action="<?php echo admin_url('admin.php?page=gigpress/gigpress.php'); ?>">
    <?php wp_nonce_field('gigpress-action') ?>
    <input type="hidden" name="gpaction" value="update" />
    <input type="hidden" name="show_id" value="<?php echo $show_id; ?>" />
```

Add mode posts `gpaction=add` and `show_status=active` (`203–206`); copying content must not copy the update identity. Existing DB hydration preserves optional-time representation:

```php
// admin/new.php:115–118
if($ss == "01") {
    $hh = "na";
    $min = "na";
}
```

Keep the `alternate_clock` branches (`243–305`), actual stored minute even when outside five-minute choices, `show_multi` checkbox (`322`), and `expire` row (`326`). Replace rejected-state coercion (`52–89`: `sprintf` and `absint`) with independent raw scalar state. Existing `'new'` association markers, notes, related-post choice/title/date and all entered fields must survive. Use escaped attributes/text/textarea by destination. Native picker needs editable fallback for invalid raw/stored dates; the current select implementation is not an analog for that new behavior. Error keys must map to actual field IDs for both inline text and linked summary.

### `admin/handlers.php` — guarded writes and truthful results

**Analog:** existing show validation and CRUD. Keep table constants, `$wpdb`, existing relationships, and sentinel contract; validate before preparation creates related records.

```php
// admin/handlers.php:37–41
if($_POST['gp_hh'] == "na") {
    $show['show_time'] = "00:00:01";
} else {
    $min = ($_POST['gp_min'] == "na") ? '00' : sprintf("%02d", $_POST['gp_min']);
    $show['show_time'] = sprintf("%02d", $_POST['gp_hh']) . ':' . $min . ':00';
}
```

No-time remains distinct from midnight. Missing disabled minutes need explicit normalization without changing the sentinel. Field errors are already keyed:

```php
// admin/handlers.php:216–219
if(!checkdate($_POST['gp_mm'], $_POST['gp_dd'], $_POST['gp_yy']))
    $errors['show_date'] = __("That's not a valid date.", "gigpress");
if(isset($_POST['show_multi']) && !checkdate($_POST['exp_mm'], $_POST['exp_dd'], $_POST['exp_yy']))
    $errors['expire_date'] = __("That's not a valid end date.", "gigpress");
```

Copy keyed errors, replacing unsafe assumptions with scalar/domain checks and canonical date parsing; do not copy raw POST access unchanged. Existing nonce/readiness order:

```php
// admin/handlers.php:247–250
check_admin_referer('gigpress-action');
if (!gigpress_require_database_ready()) return false;
$errors = gigpress_error_checking('show');
```

Preserve those checks and enforce the configured capability at changed mutation boundaries. Existing insert/update APIs (`264–266`, `329`) remain. Replace unconditional POST clearing (`293`, `349`) with success-only reset/explicit outcome; strict `!== false` plus existing-row verification distinguishes no-op update from failure. Capture the new ID before further writes. Related artist creation (`60–74`) already puts successful `insert_id` in the show fields: carry such identities into retry state to prevent duplicate creation after a subsequent failure. Do not promise transactional rollback.

For selected trash, use the same `$wpdb->update`/prepared-ID/read-back style as ownership-safe tour restoration:

```php
// admin/handlers.php:847–850
$current = $wpdb->get_row($wpdb->prepare('SELECT show_tour_id, show_tour_restore FROM ' . GIGPRESS_SHOWS . ' WHERE show_id = %d', $show_id), ARRAY_A);
if ($restore !== false && $current && (int) $current['show_tour_id'] === $tour_id && (int) $current['show_tour_restore'] === 0) {
    unset($pending_shows[$show_id]);
    $restored++;
}
```

Adapt to trash status; do not copy recovery-map semantics. Existing aggregate delete SQL/undo (`380–395`) is a replacement seam: report each missing/already-trashed/failed row and build undo from actual transitions. Retain restore and relationship guarantees from Phase 02.

### `admin/shows.php` and optional NEW `admin/workflow-state.php` — canonical navigation and confirmation (D-05–08, D-16–18)

**Analog:** existing list request parsing and parameterized relationship filters. Preserve scope/page-size user-meta keys:

```php
// admin/shows.php:35–38
if(isset($_GET['scope']))
{
    $scope = sanitize_text_field($_GET['scope']);
    update_user_meta($current_user->ID, 'gigpress_scope', $scope);
}
// admin/shows.php:88–91
if(isset($_GET['artist_id']) && $_GET['artist_id'] != '-1') {
    $further_where .= ' AND s.show_artist_id = ' . $wpdb->prepare('%d', $_GET['artist_id']) . ' ';
    $pagination_args['artist_id'] = absint($_GET['artist_id']);
    $url_args .= '&amp;artist_id=' . absint($_GET['artist_id']);
```

Keep prepared predicates, replacing fragmented URL strings with one validated state and WordPress URL helpers. Allowlist scope, sort, page-size domains. Current source **also persists sort** as `gigpress_sort` (`59–69`); research prescribes request-only sort. Planner must explicitly reconcile that inherited behavior with D-05/research; never silently add artist/tour/venue preferences. Reset removes only artist/tour/venue and page position while retaining scope/sort/limit. Separate chosen page size from SQL offset/limit (`117`, `200–205`).

Existing controls derive selected entity IDs from requests (`151–179`). Add associated labels and use the same canonical state for filter form, scope/sort links, pagination, row actions, confirmation return and notices. Replace immediate delete dispatch with server-rendered preview/Confirm/Cancel; recheck capability, nonce, readiness, explicit scalar/list ID shape and confirmation at execution. Single-show preview identifies the show; bulk preview reports unique explicit selection count. Never expand selection from the current filter query. Cancellation performs no write; bypass attempts fail closed. Empty filtered results (`284–286`) use the agreed message and narrow reset action.

**Helper scope:** if introduced, NEW helper contains pure scalar state/date/outcome helpers, follows `gigpress_` function names and keyed-array returns (`admin/handlers.php:198–237`), and has one bootstrap include owner. It is not a new service framework.

### `admin/settings.php` and `gigpress.php` — grouping and actual save boundary (D-09–13, D-18)

**Analog:** existing Settings API form and registration:

```php
// admin/settings.php:17
<form method="post" action="options.php">
// admin/settings.php:226–228
<?php settings_fields('gigpress'); ?>
<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php _e("Save Changes", "gigpress") ?>" /></p>
// gigpress.php:462–464
function register_gigpress_settings() {
    register_setting('gigpress','gigpress_settings');
}
```

Retain one form/save action, option group/name and `manage_options` menu authority (`gigpress.php:101`). Group all existing editable fields into Display & formatting; Show labels & links; Related posts; Feeds; Permissions; Advanced. Use visible semantic headings/anchors and associated labels/help IDs. Keep Advanced visible. Do not rename keys or change option meanings.

Existing fields use names such as `gigpress_settings[artist_link]` and `[rss_head]` (`153–161`). Explicit unchecked values are needed for editable checkbox fields. Hidden values (`215–224`) are not authoritative preservation data; preserve protected/sticky metadata from stored options. Unknown untouched select/radio values need a representation that survives an unchanged save.

**Shared preservation analog:** `admin/db.php` is tracked; merge missing defaults without treating false/zero/empty as absent:

```php
// admin/db.php:138–145
function gigpress_db_merge_settings($settings) {
    global $default_settings;
    foreach ($default_settings as $key => $value) {
        if (!array_key_exists($key, $settings)) {
            $settings[$key] = $value;
        }
    }
    return $settings;
}
```

Use its `array_key_exists` preservation principle in a focused sanitizer registered at `gigpress.php:463`: stored baseline plus validated editable-key overlay, preserving unknown/protected/falsey values. The bootstrap merge itself is not the save callback and should remain intact. Avoid recursive `update_option` within sanitizer.

### `gigpress.php` — shared integration dependencies

Module includes are procedural (`60–67`). Any NEW helper must be available before renderers/registration callbacks use it. Existing assets are core-owned:

```php
// gigpress.php:123–126
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-sortable');
wp_enqueue_script('gigpress-admin-js', plugins_url('scripts/gigpress-admin.js', __FILE__), array('jquery'));
wp_enqueue_style('gigpress-admin-css', plugins_url('css/gigpress-admin.css', __FILE__));
```

Reuse these enqueues. Pagination uses `paginate_links` with `'add_args' => $args` (`416–424`); populate every retained choice. Its return exists only for multiple pages (`414–435`): make zero/single-page offset metadata safe without breaking other admin callers. Do not alter runtime floor, bootstrap, menu ordering, public publishing, or CSV contracts.

### `scripts/gigpress-admin.js` and `css/gigpress-admin.css` — progressive UI and feedback

**Analogs:** same tracked assets; reuse selectors and core jQuery:

```javascript
// scripts/gigpress-admin.js:1–3
$gp=jQuery.noConflict();
$gp(document).ready(function()
// scripts/gigpress-admin.js:31–36
var scope = $gp(this);
var target = $gp(this).attr('id') + '_new';
if ( $gp('option:selected', scope).val() == 'new') {
    $gp('tbody#' + target).fadeIn();
} else {
    $gp('tbody#' + target).fadeOut();
}
```

Retain artist-sort behavior; adapt multi-day reveal to actual checkbox state, remove `this.blur()` (`25`), keep optional-time controls visible, and enable minutes on selected hour. Initialize from recovered server state; JS enhances server text/confirmation, with no-JS completion remaining possible.

```css
/* css/gigpress-admin.css:28–34 */
.gp-table th { vertical-align: top; }
.gp-table p { margin-top: 0; }
```

Keep scoped `.gigpress`/`.gp-table` styling and WordPress notice/button classes. Existing `.gigpress-error` background (`36–43`) may supplement text; it cannot carry the error meaning alone. Include usable focus and help/error spacing within changed surfaces.

### Compatibility cases, `probe.php`, `run.sh`, and conditional browser fixture

**Strong analog:** `tests/compat/upgrade-preservation-crud.php` uses actual WordPress callbacks and read-back, not mocked APIs:

```php
// tests/compat/upgrade-preservation-crud.php:4–14
function gigpress_upgrade_preservation_request($handler, $request, $files = array()) {
    $nonce = wp_create_nonce('gigpress-action');
    $_GET = $request;
    $_POST = $request;
    $_FILES = $files;
    $_REQUEST = array_merge($request, array('_wpnonce' => $nonce));
    $_GET['_wpnonce'] = $nonce;
    $_POST['_wpnonce'] = $nonce;
    ob_start();
    call_user_func($handler);
    return ob_get_clean();
}
```

NEW entry/list/settings case files may copy the capture/read-back structure. For navigation/confirmation use a stricter adapter separating GET and POST; this helper populates both and cannot prove request-method enforcement. Existing date adapter (`48–65`) still sends legacy `gp_*`/`exp_*` components: deliberately retain legacy coverage and add native-date coverage. Existing lifecycle assertions (`145–205`) retain IDs, copy-source immutability, selected trash/restore and blocked-state snapshots. Update requests explicitly if confirmation changes the direct-handler contract.

Dispatch follows the tracked probe pattern:

```php
// tests/compat/probe.php:581–584
if ($purpose === 'upgrade-preservation' && in_array($upgradeCase, array('show-lifecycle', 'optional-request-fields', 'entity-guards', 'tour-undo'), true)) {
    require WP_PLUGIN_DIR . '/gigpress/tests/compat/upgrade-preservation-crud.php';
    $upgradePreservation = gigpress_upgrade_preservation_run_crud($upgradeCase);
    if ($upgradePreservation['status'] !== 'PASS') $pluginErrors[] = array('severity' => E_ERROR, 'message' => 'Post-upgrade show lifecycle did not preserve handler semantics', 'file' => __FILE__, 'line' => __LINE__);
}
```

NEW administration scenario/case names need explicit registry, required-case aggregation, result fields and fail-closed jq assertions. No administration scenario currently exists. Runner allowlists (`787–790`), execution (`832`), and result contract (`854–866`) must evolve together; preserve existing 11-case upgrade contract. Keep isolated random project, environment scrubbing and teardown (`799–814`); exact WP patch/PHP support validation remains runner-owned.

The tracked `compose.yaml` provides the existing disposable real-WP fixture; browser transport is conditional new work. Current harness does not supply a published browser endpoint. Use an isolated authorized fixture/procedure and retain teardown. Browser keyboard/picker/focus/no-JS evidence is additional to PHP render assertions; do not label it proven by callback probes.

## Shared Patterns

- **Authority:** configured `$gpo['user_level']` for show management, `manage_options` for settings (`gigpress.php:93–102`); WordPress nonce plus readiness guard (`admin/handlers.php:247–248`, `364–365`). Capability must also be enforced at changed direct/confirmed mutation boundaries.
- **Errors/output:** translated keyed field messages (`admin/handlers.php:198–237`); escaped translated notices (`3–10`, `19–21`); destination escaping for raw recovered values, URLs, details and text. Keep failure reasons actionable within existing workflow; do not introduce a repair screen.
- **Persistence:** custom table constants, `$wpdb->insert/update`, prepared IDs, strict false/read-back (`admin/handlers.php:264–266`, `329`, `847–850`). No new schema or expiration semantics.
- **Settings:** `array_key_exists` protects falsey values (`admin/db.php:138–145`); actual Options API save must preserve the stored unknown/protected baseline.
- **No framework import pattern:** direct PHP includes, WordPress functions/globals, core jQuery and standard admin classes; no new dependency needed.

## Slice Integration and File Ownership

| Vertical slice | Narrow primary ownership | Shared integration writes | Dependencies |
|---|---|---|---|
| Show entry | `admin/new.php`, NEW entry case | `admin/handlers.php`, JS/CSS, helper include, old lifecycle adapter | raw state + validated outcome contract before renderer; D-01–04/14–15/18 |
| List and confirmed selected trash | `admin/shows.php`, NEW list case | `admin/handlers.php`, pagination in `gigpress.php`, JS/CSS, old lifecycle requests | canonical state and confirmation/mutation contract; D-05–08/16–18 |
| Settings grouping and preserving save | `admin/settings.php`, NEW settings case | callback/registration in `gigpress.php`, CSS | editable/protected key map and actual save callback; D-09–13/18 |
| Harness/browser integration | `run.sh`, `probe.php`, conditional Compose/browser procedure | all case registrations/result aggregation | case modules completed; preserves existing matrix contracts |

**Prevent same-wave concurrent writes:** entry/list both modify `admin/handlers.php`; list/settings both modify `gigpress.php`; all may modify CSS, JS and harness dispatch. Assign one owner for each whole shared file per wave, or serialize these slices. Function-level ownership alone does not remove concurrent-file risk. Feasible parallel work: distinct renderer files and distinct NEW case modules after stable helper/outcome contracts, with one integration owner for handlers/bootstrap/assets/dispatch. Existing test adapter should have one owner; consumers coordinate changes before integration. Adding optional pure helper can narrow responsibilities, but its shared include still needs one owner.

## No Existing Behavioral Analog

| Behavior | Closest structural source | Planner action |
|---|---|---|
| Native picker with editable invalid-date recovery; linked field summary | show renderer and keyed handler errors | implement RESEARCH recommendations while retaining raw values |
| Server preview/Confirm/Cancel for explicit selected trash | existing list form + guarded delete + tour read-back | implement new confirmation contract; existing direct-delete flow is not suitable to copy |
| Options API stored-baseline sanitizer; six groups/jump links | settings form, registration, bootstrap merge | add actual save-boundary preservation and grouping |
| Disposable browser endpoint/keyboard evidence | existing Compose/probe isolation | add conditional transport/procedure; no existing browser case command |

All 15 candidate paths have structural analogs; these four behavior gaps prevent claiming the desired behavior already exists.

## Metadata

**Analog search scope:** tracked root bootstrap; `admin/`; `scripts/`; `css/`; `tests/compat/`.  
**Source files inspected:** 11 PHP/JS/CSS/runner files plus tracked Compose fixture configuration referenced by research. Five primary analog groups: show renderer, handlers, list, settings/registration, real-WP CRUD harness; existing assets and runner are direct modification patterns.  
**Canonical inputs:** Phase 03 CONTEXT/RESEARCH; ROADMAP, REQUIREMENTS, PROJECT; Phase 01 context; Phase 02 context, verification and UI review. No root AGENTS.md or project skill files were found; supplied RTK instructions applied.  
**Extraction date:** 2026-10-04. No source edits, tests, commits, branches, or new agents. Only this report written. `Write` tool unavailable in this session; repository patch tool used for this sole artifact.

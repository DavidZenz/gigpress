# Phase 02: Data and Upgrade Preservation - Pattern Map

**Mapped:** 2026-10-04  
**Files analyzed:** 9 planned new or modified files  
**Analogs found:** 8 / 9

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `admin/db.php` | persistence/upgrade coordinator | transform, batch | `admin/db.php` | exact (existing upgrade path) |
| `gigpress.php` | plugin bootstrap / admin notice gate | event-driven | `gigpress.php` | exact |
| `admin/handlers.php` | admin mutation handler | request-response, CRUD | `admin/handlers.php` | exact |
| `admin/artists.php` | admin list view | request-response | `admin/venues.php` | exact role match |
| `admin/venues.php` | admin list view | request-response | `admin/artists.php` | exact role match |
| `tests/compat/probe.php` | integration probe | batch, transform | `tests/compat/probe.php` | exact |
| `tests/compat/run.sh` | container test runner | batch / process orchestration | `tests/compat/run.sh` | exact |
| `tests/compat/fixtures/upgrade-preservation/*.php` or `*.sql` | reconstructed fixture data | file I/O / batch | `tests/compat/fixtures/shows.csv` | partial; no historical-schema fixture exists |
| `tests/compat/fixtures/upgrade-preservation/*.json` | expected preservation manifest | file I/O / transform | none | no analog |

All named analogs are tracked source files, verified with `git ls-files`.

## Pattern Assignments

### `admin/db.php` (persistence / upgrade coordinator, transform + batch)

**Analog:** `admin/db.php`

Keep schema declarations, option reads, `dbDelta()`, and upgrade transformations in this module. The planner should replace the load-time, best-effort version switch with a preflighted coordinator; do not introduce an ORM or a second persistence layer.

**Schema and prefix pattern** (`admin/db.php:13-71`):

```php
$gp_db[] = "CREATE TABLE " . GIGPRESS_SHOWS . " (
show_id INTEGER(4) AUTO_INCREMENT,
...
show_related BIGINT(20) DEFAULT 0,
show_status VARCHAR(32) DEFAULT 'active',
show_tour_restore INTEGER(1) DEFAULT 0,
...
PRIMARY KEY  (show_id)
) $charset_collate";
```

The constants are prefix-aware (see `gigpress.php:38-43`); every new query must use `GIGPRESS_*`, never a literal `wp_` table name.

**Existing install / core schema-reconciliation pattern** (`admin/db.php:142-151`):

```php
function gigpress_install() {
    global $wpdb, $gp_db, $default_settings;

    if($wpdb->get_var("SHOW TABLES LIKE '" . GIGPRESS_SHOWS . "'") != GIGPRESS_SHOWS) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($gp_db);
        add_option('gigpress_settings', $default_settings);
    }
}
```

Use table presence plus an option sentinel to distinguish a fresh install from an existing table set with missing or invalid version metadata. Preserve `add_option()` for fresh installation and `get_option()`/`update_option()` for existing option data.

**Current upgrade chain to replace safely** (`admin/db.php:156-193`):

```php
if ( $gpo['db_version'] < GIGPRESS_DB_VERSION ) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($gp_db);

    switch($gpo['db_version']) {
        case "1.0":
            gigpress_db_upgrade_110();
            gigpress_db_upgrade_120();
            gigpress_db_upgrade_130();
            gigpress_db_upgrade_140();
            gigpress_db_upgrade_160();
            break;
        // 1.1 through 1.5 branches omitted
    }

    $gpo['db_version'] = GIGPRESS_DB_VERSION;
    update_option('gigpress_settings', $gpo);
}
```

Use this switch as the source-version inventory only. The final marker must be written after verified steps and then read back; do not retain its unconditional completion behavior.

**`$wpdb` operation pattern and error contract** (`admin/db.php:205-214`, `admin/db.php:296-307`):

```php
$wpdb->update(
    GIGPRESS_SHOWS,
    array('show_expire' => $show->show_date),
    array('show_id' => $show->show_id),
    array('%s'),
    array('%d')
);

$where = array('artist_id' => $artist->artist_id);
$update = $wpdb->update(GIGPRESS_ARTISTS, $new_artist, $where, array('%s'), array('%d'));
```

New upgrade helpers should keep structured `$wpdb->update()` formats and treat `false` as failure; an affected-row count of `0` requires a postcondition read rather than being treated as an error.

**Settings merge anti-pattern to repair** (`admin/db.php:125-140`):

```php
if(empty($gpo['buy_tickets_label'])) {
    $gpo['buy_tickets_label'] = 'Buy Tickets';
    update_option('gigpress_settings', $gpo);
}
```

Use `array_key_exists()` for default insertion and operate on the fetched array so unknown keys and intentional `''`, `0`, and disabled values survive. The existing `default_settings` array at `admin/db.php:74-121` is the default source.

**Legacy transformation that needs idempotency guards** (`admin/db.php:241-280`):

```php
$wpdb->insert(GIGPRESS_ARTISTS, $artist);
$gpo['default_artist'] = $wpdb->insert_id;
$wpdb->update(GIGPRESS_SHOWS, array('show_artist_id' => $wpdb->insert_id), array('show_artist_id' => 0));

foreach($venues as $venue) {
    $wpdb->insert(GIGPRESS_VENUES, $venue);
    $values = array("show_venue_id" => $wpdb->insert_id);
    $wpdb->update(GIGPRESS_SHOWS, $values, $where);
}
```

Retain its established field meaning but plan deterministic reuse/proof of generated IDs before any retry. A partial 1.4 run cannot safely execute these inserts blindly again.

---

### `gigpress.php` (bootstrap / admin notice gate, event-driven)

**Analog:** `gigpress.php:26-50`

Use the current PHP runtime guard as the pattern for an actionable notice that does not execute the normal plugin surface. Any incomplete-upgrade state must allow only the safe bootstrap and authorized notice, while avoiding partial admin mutation registration.

```php
if (PHP_VERSION_ID < 80300) {
    function gigpress_php_compatibility_notice() {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-warning"><p>'
                . esc_html('GigPress requires PHP 8.3 or newer. This site is running an incompatible PHP version.')
                . '</p></div>';
        }
    }

    add_action('admin_notices', 'gigpress_php_compatibility_notice');
} else {
    global $wpdb;
    define('GIGPRESS_SHOWS', $wpdb->prefix . 'gigpress_shows');
    require('admin/db.php');
```

Keep the lower PHP floor behavior intact. Plan a named, idempotent notice callback and a narrow gate that preserves WordPress usability without exposing migration-dependent GigPress mutations.

---

### `admin/handlers.php` (admin mutation handler, request-response + CRUD)

**Analog:** `admin/handlers.php`

Maintain the existing procedural handler shape: `global $wpdb`, display DB errors, validate nonce, sanitize IDs, run a structured query, then render a translated success/error message. Put dependency guards in these handlers, not only in view templates.

**Nonce, validation, and update pattern** (`admin/handlers.php:280-326`):

```php
function gigpress_update_show() {
    global $wpdb, $gpo;
    $wpdb->show_errors();
    check_admin_referer('gigpress-action');

    $errors = gigpress_error_checking('show');
    if($errors) {
        // render errors and return them
    } else {
        $show = gigpress_prepare_show_fields('edit');
        $where = array('show_id' => $_POST['show_id']);
        $updateshow = $wpdb->update(GIGPRESS_SHOWS, $show, $where, $format, $where_format);
        if($updateshow != FALSE) {
            // success notice
        } elseif($updateshow === FALSE) {
            // error notice
        }
    }
}
```

Apply the same nonce and `absint()` boundary to new incomplete-upgrade gates and dependency checks.

**Selected-ID trash / restore pattern** (`admin/handlers.php:335-373`, `724-757`):

```php
if(is_array($_REQUEST['show_id'])) {
    $shows = array();
    foreach($_REQUEST['show_id'] as $show) {
        $shows[] = $wpdb->prepare('%d', $show);
    }
    $shows = implode(',', $shows);
} else {
    $shows = $wpdb->prepare('%d', $_REQUEST['show_id']);
}
$trashshow = $wpdb->query("UPDATE ".GIGPRESS_SHOWS." SET show_status = 'deleted' WHERE show_id IN($shows)");
```

Preserve this selected-ID behavior for DATA-02. Ensure new migration guards cover these paths only when migration state makes their assumptions unsafe.

**Current unsafe entity deletion boundary** (`admin/handlers.php:464-480`, `699-716`):

```php
check_admin_referer('gigpress-action');
$trashvenue = $wpdb->query($wpdb->prepare(
    "DELETE FROM ". GIGPRESS_VENUES ." WHERE venue_id = %d LIMIT 1",
    absint($_GET['venue_id'])
));
```

Before this delete, query `GIGPRESS_SHOWS` for the matching artist/venue ID without filtering out `show_status = 'deleted'`. A guard should render an error and return before DELETE when any active or trashed show references the entity.

**Tour trash / undo behavior to characterize and repair** (`admin/handlers.php:567-601`, `760-777`):

```php
$cleanup = $wpdb->query("UPDATE ".GIGPRESS_SHOWS." SET show_tour_restore = 0 WHERE show_tour_restore != 0");
$where = array('show_tour_id' => absint($_GET['tour_id']));
$restore = $wpdb->update(
    GIGPRESS_SHOWS,
    array('show_tour_id' => 0, 'show_tour_restore' => 1),
    $where,
    array('%d','%d'),
    array('%d')
);
```

The shared Boolean marker has no ownership data. The plan must create evidence for repeated deletion and intervening reassignment before choosing the smallest compatible repair. Keep `check_admin_referer()` and translated admin messages.

---

### `admin/artists.php` and `admin/venues.php` (admin list views, request-response)

**Analogs:** `admin/artists.php:132-156` and `admin/venues.php:192-221`

These paired list modules dispatch to `handlers.php` and use an active-show count to decide whether to show the delete action.

```php
if($n = $wpdb->get_var("SELECT count(*) FROM ". GIGPRESS_SHOWS ." WHERE show_artist_id = ". $artist->artist_id . " AND show_status != 'deleted'")) {
    $count = '<a href="' . admin_url('admin.php?page=gigpress-shows&amp;artist_id=' . $artist->artist_id) . '">' . $n . '</a>';
} else {
    $count = 0;
}
...
<?php if(!$count) { ?> | <a href="<?php echo wp_nonce_url(..., 'gigpress-action'); ?>" class="delete">Delete</a><?php } ?>
```

Modify the count predicate to include trashed references when deciding whether deletion is allowed. Keep the view restriction as a convenience, but treat the handler guard as authoritative.

---

### `tests/compat/probe.php` (integration probe, batch + transform)

**Analog:** `tests/compat/probe.php`

Extend the existing disposable WordPress bootstrap and one-line JSON contract. Add an `upgrade-preservation` purpose beside existing scenarios, rather than adding a second test framework or host-PHP script.

**Disposable WP bootstrap and activation pattern** (`tests/compat/probe.php:152-186`):

```php
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_blog_installed()) {
    wp_install('GigPress Compatibility', 'compat-admin', 'compat-admin@example.test', true, '', 'compat-password');
}
$admin = get_user_by('login', 'compat-admin');
wp_set_current_user($admin->ID);
$activation = activate_plugin($plugin, '', false, false);
```

Load reconstructed rows/options/posts after WordPress install and before the target activation/load. Snapshot fixtures using `$wpdb` and `get_option()` so assertions include prefix, IDs, complete row data, option keys, relationships, related post IDs, and trash state.

**Existing direct-handler workflow probe pattern** (`tests/compat/probe.php:323-361`):

```php
require_once WP_PLUGIN_DIR . '/gigpress/admin/handlers.php';
$_POST = array('_wpnonce' => wp_create_nonce('gigpress-action'));
$_REQUEST = $_POST;
...
ob_start();
gigpress_update_show();
$updateOutput = ob_get_clean();
$updated = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM " . GIGPRESS_SHOWS . " WHERE show_id = %d", $show->show_id
));
```

Use this style for edit, copy, selected trash/restore, guard, and tour undo sequences. Reset `$_GET`, `$_POST`, `$_REQUEST`, and capture output between operations to avoid test state leaking across assertions.

**Result contract pattern** (`tests/compat/probe.php:407-426`):

```php
$result = array(
    'status' => $pluginErrors ? 'FAIL' : 'PASS',
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => PHP_VERSION,
    'plugin_active' => is_plugin_active($plugin),
    'plugin_errors' => $pluginErrors,
    'purpose' => $purpose,
);
echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);
```

Expose a compact `upgrade_preservation` evidence object in this JSON and append assertion failures to `$pluginErrors`; `run.sh` already requires `status == PASS`.

---

### `tests/compat/run.sh` (container test runner, batch / process orchestration)

**Analog:** `tests/compat/run.sh:370-415`, `625-699`

Add `upgrade-preservation` to both scenario allowlists, then retain the existing exact-version validation, Compose lifecycle, result file, and `jq` contract style.

```bash
case "$SCENARIO" in
  activation-menu|admin-menu|csv-roundtrip|full-workflows) ;;
  *) fail "unsupported cell scenario: $SCENARIO" ;;
esac
...
output=$(compose_env exec -T -e COMPAT_PURPOSE="$SCENARIO" wordpress php /compat/probe.php) || {
  printf '%s\n' "$output" >&2; fail "probe failed";
}
...
printf '%s\n' "$result" | rtk jq -e '.status == "PASS" and .plugin_active == true and (.plugin_errors | length == 0)' >/dev/null
```

For the matrix, follow `run_matrix()` (`tests/compat/run.sh:370-415`) so Phase 02 preserves the established WordPress 7.0/7.1 and PHP 8.3+ input contracts.

---

### `tests/compat/fixtures/upgrade-preservation/*` (reconstructed fixtures, file I/O + batch)

**Partial analog:** `tests/compat/fixtures/shows.csv`

The repository has no historical schema fixture or manifest pattern. Use a self-describing, tracked fixture layout grouped by source version; ensure the test probe can load it deterministically in a prefix-aware database. Label each source as reconstructed because no live-site backup was supplied.

```text
tests/compat/fixtures/upgrade-preservation/
  1.0/  # source schema/data plus expected preservation manifest
  1.1/
  1.2/
  1.3/
  1.4/
  1.5/
```

Avoid hard-coded `wp_` table names in fixture loader SQL. The fixture format is at implementation discretion because no close analog exists.

## Shared Patterns

### WordPress authorization and mutation boundary

**Sources:** `gigpress.php:26-34`, `admin/handlers.php:224-228`

```php
if (current_user_can('activate_plugins')) {
    echo '<div class="notice notice-warning"><p>' . esc_html($notice) . '</p></div>';
}

check_admin_referer('gigpress-action');
```

Apply the capability condition to upgrade failure notices and nonce checks to handler changes. The phase does not add a new authentication system.

### `$wpdb` result semantics

**Sources:** `admin/handlers.php:301-324`, `admin/db.php:296-307`

```php
$result = $wpdb->update(GIGPRESS_SHOWS, $show, $where, $format, $where_format);
if($result != FALSE) {
    // success; zero can be a valid no-op
} elseif($result === FALSE) {
    // error; retain state and render an error notice
}
```

Use strict `false` checks. Migration completion needs explicit read-back assertions because SQL no-ops and unchanged options may yield `0`/`false` without being data loss.

### Prefix-aware persistence

**Source:** `gigpress.php:35-50`

```php
define('GIGPRESS_SHOWS', $wpdb->prefix . 'gigpress_shows');
define('GIGPRESS_TOURS', $wpdb->prefix . 'gigpress_tours');
define('GIGPRESS_ARTISTS', $wpdb->prefix . 'gigpress_artists');
define('GIGPRESS_VENUES', $wpdb->prefix . 'gigpress_venues');
```

Apply to all runtime code and fixture-loader queries; test with a nondefault table prefix.

### Disposable compatibility-harness contract

**Sources:** `tests/compat/compose.yaml:17-36`, `tests/compat/probe.php:407-426`, `tests/compat/run.sh:652-699`

The probe runs inside the WordPress container and returns final JSON. `run.sh` owns containers and validates a scenario with `jq`. Keep execution in Compose because host PHP is intentionally absent.

## No Analog Found

| File | Role | Data Flow | Reason |
|---|---|---|---|
| `tests/compat/fixtures/upgrade-preservation/*/expected.json` | preservation manifest | file I/O / transform | Existing fixtures contain CSV input only; no versioned expected-state manifest exists. |
| Optional durable upgrade checkpoint representation | migration state | batch / transform | Existing code has only `gigpress_settings['db_version']`; the implementation must choose a minimal compatible representation after fixture-driven failure evidence. |

## Metadata

**Analog search scope:** `gigpress.php`, `admin/`, `tests/compat/`  
**Files scanned:** 10 tracked source files  
**Pattern extraction date:** 2026-10-04

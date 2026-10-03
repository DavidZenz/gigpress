# Phase 01: Compatibility Baseline and Menu Diagnosis - Pattern Map

**Mapped:** 2026-10-03  
**Files analyzed:** 4 concrete source/metadata modifications; one implied validation harness  
**Analogs found:** 4 / 4 concrete modifications

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `gigpress.php` | bootstrap / route | event-driven, request-response | `gigpress.php` | exact (modify in place) |
| `readme.txt` | config / package metadata | transform | `gigpress.php` plugin header | role-match |
| `lib/parsecsv.lib.php` | utility | file-I/O, transform | `lib/parsecsv.lib.php` | exact (modify in place) |
| `lib/upgrade.php` | utility / legacy compatibility | transform | `lib/upgrade.php` | exact (modify in place) |
| disposable compatibility matrix (path TBD by runner selection) | test / config | batch | none | no analog |

All named analog paths are tracked source files (`git ls-files` verified). The repository has no existing container, Compose, test, or CI harness to copy.

## Pattern Assignments

### `gigpress.php` (bootstrap/route, event-driven and request-response)

**Analog:** `gigpress.php` (edit the existing bootstrap in place).

**Plugin-header pattern** (lines 1-22): keep package metadata in the leading PHP comment; add WordPress-recognized requirement fields beside the existing `Text Domain`, rather than creating a second metadata source.

```php
/*
Plugin Name: GigPress
...
Text Domain: gigpress
*/
```

**Bootstrap/load pattern** (lines 24-59): constants are defined before the procedural dependency includes. Any runtime eligibility logic that must execute in an active install belongs before those includes and must not introduce PHP syntax that an unsupported PHP interpreter cannot parse.

```php
global $wpdb;

define('GIGPRESS_SHOWS', $wpdb->prefix . 'gigpress_shows');
define('GIGPRESS_VERSION', '2.3.12');

require('admin/db.php');
require('admin/new.php');
```

**Admin menu registration pattern** (lines 62-90): use WordPress’s `add_menu_page()`/`add_submenu_page()` APIs and the established `$gpo['user_level']` capability. Preserve this page registration; only change the separate ordering behavior after tracing the reported environment.

```php
add_menu_page("GigPress &rsaquo; $add", "GigPress", $gpo['user_level'], __FILE__, "gigpress_add", $icon);
add_submenu_page(__FILE__, "GigPress &rsaquo; $shows", $shows, $gpo['user_level'], "gigpress-shows", "gigpress_admin_shows");
```

**Menu-order transform and fallback point** (lines 446-475; hooks at 620-621): this is the exact defective analog to replace. The callback currently mutates global `$menu`, then returns a reconstructed slug array. Retain only a pure transform of the received `$menu_order`; use strict search checking and return the original order if `gigpress/gigpress.php` or `edit-comments.php` is absent, or when an existing separator cannot safely be ordered.

```php
if($current_position = array_search('gigpress/gigpress.php', $menu_order))
{
    global $menu;
    $menu[] = array('', 'read', 'separator-gp', '', 'wp-menu-separator');
    unset($menu_order[$current_position]);
    foreach($menu_order as $menu_item) {
        $new_menu_order[] = $menu_item;
        if($menu_item == 'edit-comments.php') {
            $new_menu_order[] = 'separator-gp';
            $new_menu_order[] = 'gigpress/gigpress.php';
        }
    }
}
```

**Hook-registration pattern** (lines 599-635): register callbacks at file end. A retained ordering callback must use the existing `custom_menu_order` gate and `menu_order` hook; a diagnostic trace must be transient and not persist menu/callback values.

```php
register_activation_hook(__FILE__,'gigpress_install');
add_action('admin_menu', 'gigpress_admin_menu');
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', 'custom_menu_order');
```

**CSV export request handler and PHP 8 offset conversion** (lines 505-552): preserve nonce verification, database query, and `parseCSV` output path. Convert only offset syntax in the existing assignment.

```php
check_admin_referer('gigpress-action');
require_once(WP_PLUGIN_DIR . '/gigpress/lib/parsecsv.lib.php');
...
$show['show_time'] = ( $show['show_time']{7} == 1 ) ? '' : $show['show_time'];
...
$export->output($name, stripslashes_deep($export_shows), $fields, ',');
```

### `readme.txt` (config/package metadata, transform)

**Analog:** `gigpress.php` header lines 1-22, paired with `readme.txt` lines 1-8.

**Metadata pattern:** keep repository support declarations synchronized across the main plugin header and the readme’s leading metadata block. The readme currently establishes the formatting and requires only targeted value updates.

```text
=== GigPress ===
Contributors: mrherbivore
...
Requires at least: 3.0
Tested up to: 4.3
```

Add the PHP floor to the main plugin header as the authoritative runtime requirement; update the readme’s WordPress floor and `Tested up to` only after the matching matrix cell succeeds.

### `lib/parsecsv.lib.php` (utility, file-I/O and transform)

**Analog:** `lib/parsecsv.lib.php` (edit only removed PHP offset syntax in place).

**Character-walk pattern** (lines 267-310 and 365-405): both delimiter detection and CSV parsing take a string, loop by index, and use the previous/next characters. Convert brace-offset expressions mechanically to bracket offsets while preserving loop bounds, condition ordering, and `false` fallback behavior.

```php
for ( $i=0; $i < $strlen; $i++ ) {
    $ch = $data{$i};
    $nch = ( isset($data{$i+1}) ) ? $data{$i+1} : false ;
    $pch = ( isset($data{$i-1}) ) ? $data{$i-1} : false ;
}
```

**CSV enclosure pattern** (lines 679-688): retain the input guards and escaping rules; change only `$value{0}` to bracket form.

```php
if ( $value !== null && $value != '' ) {
    ...
    if ( preg_match("/".$delimiter."|".$enclosure."|\\n|\\r/i", $value) || ($value{0} == ' ' || substr($value, -1) == ' ') ) {
        $value = str_replace($this->enclosure, $this->enclosure.$this->enclosure, $value);
        $value = $this->enclosure.$value.$this->enclosure;
    }
}
```

### `lib/upgrade.php` (legacy-compatibility utility, transform)

**Analog:** `lib/upgrade.php` (edit only removed PHP offset syntax in place).

**Legacy helper pattern** (lines 2049-2080, 2181-2203, and 2929-2944): preserve function names, comments, and legacy fallback behavior because public output files conditionally load this file. Convert the three brace-offset expressions mechanically.

```php
$num = ord($line{0}) - 32;
...
$l = strpos($haystack, $char_list{$n});
...
$r .= $str{$n};
```

**Load-context evidence:** `output/gigpress_shows.php` loads this file at lines 220 and 283, and `output/gigpress_related.php` loads it at line 67. Do not remove helpers or alter the fallback guard in this phase without a separate behavior decision.

## Shared Patterns

### WordPress lifecycle and capability boundaries

**Source:** `gigpress.php` lines 599-621; `admin/db.php` lines 142-151.

**Apply to:** the active-but-inert compatibility guard and any temporary admin diagnostic.

```php
register_activation_hook(__FILE__,'gigpress_install');
add_action('admin_menu', 'gigpress_admin_menu');

function gigpress_install() {
    global $wpdb, $gp_db, $default_settings;
    if($wpdb->get_var("SHOW TABLES LIKE '" . GIGPRESS_SHOWS . "'") != GIGPRESS_SHOWS) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($gp_db);
        add_option('gigpress_settings', $default_settings);
    }
}
```

Use core’s requirement header handling to block new below-floor activation. For an already-active installation, branch before every normal include/hook, retain active state, register only the compatibility notice behind WordPress’s plugin-management capability, and keep diagnostic menu data out of that notice. Keep the normal procedural declarations inside the supported-runtime branch so they are absent below the floor.

### Procedural WordPress integration

**Source:** `gigpress.php` lines 39-59 and 599-635.

**Apply to:** all source modifications in this phase.

The codebase uses procedural functions, `require`/`require_once`, globals, and direct WordPress hook registration. Phase 01 should make narrow in-place changes and must not introduce classes, autoloading, Composer, or a new application layer.

### Behavior-preserving PHP 8 syntax modernization

**Source:** `gigpress.php` lines 537-547; `lib/parsecsv.lib.php` lines 289-304, 384-405, and 679-688; `lib/upgrade.php` lines 2061-2077, 2187-2203, and 2933-2943.

**Apply to:** all discovered curly-brace string/array offsets in workflow-reachable shipped PHP.

Replace `{expression}` offset access with `[expression]` only. Preserve surrounding casts, conditions, loop boundaries, and return values so the change is parse compatibility rather than a data-behavior rewrite.

## No Analog Found

| File / artifact | Role | Data Flow | Reason |
|---|---|---|---|
| Disposable WordPress/PHP matrix definition and smoke runner (path TBD) | test/config | batch | No tracked Compose, test, CI, or harness files exist. Use the installed OrbStack Docker-compatible runtime, then keep fixture/log artifacts outside shipped plugin runtime paths; do not add a host PHP dependency. |
| Controlled menu-order trace (path TBD) | test/diagnostic | event-driven | No diagnostic instrumentation exists. It must be temporary, record input/returned/final slugs plus callbacks, and not become a normal-request persistent option or notice. |

## Metadata

**Analog search scope:** tracked plugin root, `admin/`, `output/`, and `lib/`  
**Tracked PHP/metadata files scanned:** 38  
**Pattern extraction date:** 2026-10-03

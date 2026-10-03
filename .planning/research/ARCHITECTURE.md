# Architecture Research

**Domain:** Brownfield WordPress plugin modernization (procedural PHP)
**Researched:** 2026-10-03
**Confidence:** MEDIUM

## Standard Architecture

### System Overview

Keep GigPress as a single, procedural WordPress plugin with its current modules and custom-table schema. Make compatibility work at the boundaries where requests enter, hooks register, database rows are transformed, and output leaves the plugin. Do not introduce a service container or rewrite the plugin into a framework during a compatibility release.

```text
┌────────────────────────────────────────────────────────────────┐
│ WordPress request lifecycle                                     │
│ activation · init · admin_menu · admin-post · shortcodes · feeds│
└───────────────────────────┬────────────────────────────────────┘
                            ▼
┌────────────────────────────────────────────────────────────────┐
│ gigpress.php — bootstrap, hooks, capabilities, shared helpers   │
│ Stable public function names and shortcode/feed registrations   │
└──────────────┬──────────────────────────────────┬──────────────┘
               │                                  │
               ▼                                  ▼
┌─────────────────────────────┐   ┌──────────────────────────────┐
│ admin/ renderers and forms   │   │ output/ retrieval/serializers│
│ handlers.php owns mutations │   │ gigpress_prepare() shapes rows│
│ admin/db.php owns schema     │   │ HTML · RSS · iCalendar       │
└──────────────┬──────────────┘   └──────────────┬───────────────┘
               │                                  │
               └─────────────────┬────────────────┘
                                 ▼
┌────────────────────────────────────────────────────────────────┐
│ WordPress options/posts + four prefix-aware GigPress tables    │
│ shows · artists · venues · tours; no schema change by default   │
└────────────────────────────────────────────────────────────────┘
               ▲                                  ▲
               │                                  │
      CSV import/export                 templates/*.php + theme
      admin/handlers.php                gigpress-templates overrides
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| Bootstrap and WordPress integration | Define constants, load modules, register activation and request hooks, shortcode/feed/AJAX integrations, shared functions | `gigpress.php`; retain prefixed global functions and established hook names |
| Persistence and upgrades | Define four custom tables, defaults, schema version and activation/upgrade behavior | `admin/db.php`; leave the existing schema and records unchanged unless a separately justified migration is required |
| Admin screens | Render add/edit/list/settings/import-export pages and build form actions | `admin/new.php`, `admin/shows.php`, `admin/artists.php`, `admin/venues.php`, `admin/tours.php`, `admin/settings.php`, `admin/import-export.php` |
| Admin mutations | Verify request intent and capability, validate fields, mutate entities, import rows, reorder artists, report outcomes | `admin/handlers.php`; preserve action names, nonce action strings, entity semantics and redirect/notice behavior |
| Shared show preparation | Turn joined rows into legacy associative fields for admin, public, feed and calendar contexts | `gigpress_prepare()` in `gigpress.php`; retain existing keys and distinction between plain text and ready-made HTML during incremental escaping changes |
| Public query and rendering | Apply shortcode filters, query rows, render lists and related content | `output/gigpress_shows.php`, `output/gigpress_related.php`, `output/gigpress_sidebar.php`; keep query and template changes scoped |
| Template override contract | Select custom theme templates before plugin defaults | `gigpress_template()` and `templates/*.php`; preserve lookup order (child theme, parent theme, `wp-content`, plugin fallback), names, include scope and variables |
| Syndication | Query and serialize show rows as RSS XML or iCalendar | `output/feed.php`, `output/ical.php`; keep wire formats, endpoint slugs, filter query variables, headers and field meaning stable |
| CSV boundary | Parse uploaded CSV, resolve/create related entities, skip duplicates, insert shows; export filtered rows in the legacy column order | Import in `admin/handlers.php`, export handlers in `gigpress.php`, UI in `admin/import-export.php`, bundled parser in `lib/parsecsv.lib.php` |

### Recommended Project Structure

```text
gigpress.php                 # composition root and compatibility metadata
admin/
  db.php                     # custom tables, defaults, upgrades
  handlers.php               # mutations, import, reorder operations
  new.php                    # add-show renderer/form
  shows.php                  # classic show list, filters, bulk actions
  artists.php venues.php tours.php settings.php import-export.php
output/
  gigpress_shows.php         # shortcode queries and list rendering
  gigpress_related.php       # post integration
  gigpress_sidebar.php       # widget and sidebar list
  feed.php ical.php          # RSS and calendar serializers
templates/                   # plugin fallback PHP fragments
lib/                         # bundled parser and compatibility helpers
```

### Structure Rationale

- **`gigpress.php`:** Keep it the composition root because every module currently relies on its constants, globals and shared helpers. Make each hook registration explicit and testable there; avoid expanding it with new CRUD logic.
- **`admin/`:** Preserve the existing renderer/handler split. Screens should keep presenting the legacy forms and list behavior; request validation and writes remain centralized in handlers where feasible.
- **`output/` and `templates/`:** Keep format-specific retrieval and serialization separate from template fragments. Public theme overrides are a compatibility surface, not incidental files.
- **`admin/db.php`:** Keep data/schema changes isolated. Runtime compatibility fixes should not trigger table recreation or data conversion.
- **No new framework layer:** A broad class-based rewrite would enlarge regression scope without improving compatibility at WordPress’s existing hook, database and template boundaries.

## Architectural Patterns

### Pattern 1: Preserve the procedural composition root; make hook registration explicit

**What:** `gigpress.php` loads the existing modules and connects their callbacks to WordPress lifecycle hooks. Keep that structure but ensure each registration uses the correct lifecycle event, a stable callback, explicit capability policy and current metadata. Declare `Requires at least: 7.0` and `Requires PHP: 8.3` in the plugin header and keep distribution/readme metadata aligned.

**When to use:** For every compatibility change that connects GigPress to core, including menus, feeds, settings, post filters and AJAX.

**Trade-offs:** This leaves global state and procedural functions in place, but minimizes change to plugin APIs and preserves hook names that themes or other plugins may call. Defer namespacing or object-oriented conversion until a separate migration can add compatibility shims.

**Example:**
```php
add_action( 'admin_menu', 'gigpress_admin_menu' );
add_action( 'init', 'add_gigpress_feeds' );
add_action( 'admin_post_gigpress_export', 'gigpress_export' );
```

The actual code should retain GigPress’s established callback names where those names are externally callable.

### Pattern 2: Separate menu registration from menu ordering; preserve access checks in callbacks

**What:** Continue registering the GigPress top-level menu and submenus on `admin_menu`. Use a stable, sanitized plugin slug rather than `__FILE__` for new or revised registrations, retain the existing page callbacks, and preserve the Add Show landing page through a matching top-level/submenu slug. Menu visibility capability and callback-level authorization must agree. Keep `manage_options` for settings unless the product’s established role policy says otherwise.

The current menu code in `gigpress.php` also enables `custom_menu_order`, registers a global `menu_order` callback, and appends `separator-gp`. The reported key `separator-gigpress` does not by itself prove this code causes the warning. First reproduce with the reported WordPress release and inspect the completed `$menu` and `$menu_order` arrays. Then remove the custom relocation/separator filter if visual placement is not essential; otherwise make the transformation deterministic, preserve all unrelated menu entries, and handle missing GigPress/comments entries and index zero correctly. Prefer normal registration and documented numeric positions when they provide the needed result.

**When to use:** First compatibility slice because the reported issue is in admin menu construction and this logic currently runs globally in the admin.

**Trade-offs:** A plain position may not reproduce the exact custom placement after Comments. A custom order filter retains that placement but depends on global core menu arrays, so it needs focused compatibility coverage. Do not adjust database or form code while isolating a menu-order defect.

### Pattern 3: Secure request boundaries and preserve the data contract

**What:** At admin form, query-string, AJAX, and CSV boundaries, verify the user capability and nonce, validate expected types/ranges, sanitize values appropriate to the field, use `$wpdb->prepare()` for SQL values, and escape at the final output context. Nonces mitigate request forgery; they do not replace capability checks. Preserve legacy action names and form field names unless an adapter keeps old submissions working.

For show-list forms and queries, change one boundary at a time: normalize pagination, sort, scope and entity IDs before building query conditions; keep `sanitize_sql_orderby()` for the allowed sort expression; then keep result ordering, bulk actions, soft-delete/restore and related-post behavior unchanged. Do not combine this with schema or query architecture changes.

**When to use:** In admin mutations and filtering work, and incrementally at public HTML/XML/CSV output boundaries.

**Trade-offs:** WordPress’s guidance to escape late is sound, but `gigpress_prepare()` currently mixes plain values, formatted values and prebuilt HTML. A blanket conversion from HTML fields to plain fields can break templates, feeds and extensions. Keep existing keys stable; introduce explicitly named escaped/plain fields or helper boundaries where needed, then migrate bundled templates and serializers with compatibility coverage.

### Pattern 4: Keep the override resolver as a stable plugin API

**What:** Preserve `gigpress_template($path)` and its current resolution order: active child theme `/gigpress-templates/`, parent theme `/gigpress-templates/`, `WP_CONTENT_DIR/gigpress-templates/`, then plugin `templates/`. Preserve all template basenames, the include call pattern, and variables made available in include scope. Validate internal template names against the known set or a constrained basename before path construction; do not let a request value become `$path`.

**When to use:** Public layout/responsiveness work and any template cleanup.

**Trade-offs:** WordPress `locate_template()` searches standard theme template locations and is not a drop-in replacement for this plugin-specific directory contract. It can inform child-versus-parent priority, but replacing the resolver or renaming fragments would silently stop existing overrides from loading. Add new fragments additively and document their variables.

### Pattern 5: Keep public serializers independent while sharing prepared rows

**What:** The shortcode/widget path and feed paths may share retrieval or normalization helpers, but HTML templates, RSS XML and iCalendar are different output contexts. Preserve `gigpress_prepare()`’s context argument and stable output fields while moving safe common date/query logic only where duplicated behavior is demonstrably equivalent. Escape separately for HTML text/attributes/URLs, XML, and iCalendar text rules.

**When to use:** Public rendering, responsive styles and feed compatibility changes.

**Trade-offs:** Some duplicated SQL remains, but a premature universal query abstraction risks changing scope filters, date boundaries, empty-feed behavior and feed ordering. Extract narrow helpers only after characterization of existing output.

## Data Flow

### Request Flow

```text
Admin GET/POST                         Public shortcode/feed request
     ↓                                           ↓
WordPress loads gigpress.php        WP routes shortcode or feed slug
     ↓                                           ↓
admin_menu → page callback          output/gigpress_shows.php or feed.php/ical.php
     ↓                                           ↓
form → handler + capability/nonce   scope/filter validation → joined $wpdb query
     ↓                                           ↓
validate/sanitize/prepare SQL        gigpress_prepare($row, $context)
     ↓                                   ↙              ↘
$wpdb mutation → notice/redirect     theme template   RSS/iCalendar serializer
     ↓                                           ↓
existing options and custom tables ← joined query results / stable payload
```

### State Management

```text
gigpress_settings option ──> bootstrap/settings/public behavior
four custom tables ─────────> admin CRUD and output queries
WordPress posts ────────────> optional show-related links
CSV file ──parse/validate───> entity resolution and show inserts
database rows ──select/map──> CSV columns in established order
```

### Key Data Flows

1. **Admin CRUD:** WordPress loads `gigpress.php`; its `admin_menu` callback registers page routes. A page renderer emits a classic form. The submit handler verifies the GigPress nonce and appropriate capability, normalizes input, then writes via `$wpdb` and/or `wp_insert_post()`. Preserve custom tables, IDs, soft-delete behavior, post association and action fields.
2. **Show list and bulk actions:** `admin/shows.php` accepts filters and pagination, constructs a database query, and presents rows. Form actions route to handlers in `admin/handlers.php`. Keep sorting, paging, trash/restore and bulk-action semantics intact while making request normalization explicit.
3. **Public show list:** shortcode callbacks call `gigpress_shows()`; it applies scope/date/entity filters, queries joined tables, normalizes each row with `gigpress_prepare()`, then includes fragments returned by `gigpress_template()`. Theme overrides consume variables in the renderer’s include scope.
4. **Feeds:** `init` registers the established `gigpress` and `gigpress-ical` feed names. `output/feed.php` and `output/ical.php` independently query upcoming rows, call `gigpress_prepare()` with feed context, and serialize. Keep URL filters, headers, row limits, date/time conversion and output field contracts stable.
5. **CSV import/export:** the admin page renders file upload and export filters. Import verifies the existing nonce, parses the file, resolves or creates artist/tour/venue records, detects duplicate shows, inserts accepted rows, and reports inserted/skipped rows. Export filters shows and serializes the legacy columns. Compatibility work should validate upload error state and file shape before entity creation, preserve column order and data meaning, and avoid partial orphan creation when row validation fails. The code currently uses the bundled `parseCSV` library and includes a legacy curly-brace string offset in export processing (`$show['show_time']{7}`); treat that export path as a PHP-runtime modernization target and preserve the single-show-time sentinel semantics when updating syntax/logic.

## Integration Sequence

Use this order so later UI work rests on a stable compatibility base:

1. **Characterize contracts and environment first.** Record current plugin header, PHP/WP minimums, menu routes/capabilities, shortcodes and widget APIs, feed names/query parameters, template names/variables/priority, custom-table schema/version, import headers and export column order. Keep representative database snapshots and current output examples for comparison. Do not edit schema in this pass.
2. **Bootstrap and hooks, then menu warning.** Add the selected `Requires at least: 7.0` and `Requires PHP: 8.3` metadata; verify module loading and current PHP syntax compatibility; inspect activation/upgrade behavior without running destructive uninstall. Isolate `admin_menu`, `custom_menu_order`, separator creation and callback permissions on WP 7.0 and the reported WP 7.1.2 line. Preserve menu slugs/callback behavior or provide redirects/adapters if a slug must change. This is the narrowest diagnostic area and should land before screens are changed.
3. **Request/form and query boundaries.** Update PHP 8.3-incompatible syntax in admin and shared paths. Then harden the add/edit forms and `admin/handlers.php` request parsing, followed by show-list filters, stable ordering, pagination and bulk actions. Normalize IDs/enums/dates before SQL; keep `$wpdb->prepare()` for values and whitelist sort expressions. Verify mutation capability checks independently of menu visibility and preserve nonce action strings during the first pass.
4. **Public query and presentation contract.** Update `gigpress_shows()`, sidebar/related integration and `gigpress_prepare()` with additive data shaping. Migrate bundled templates to context-correct escaping in small slices; retain old field names and their established HTML meaning for theme overrides. Then adjust CSS/responsive layout without changing template lookup priority or shortcode defaults.
5. **Feeds as a separate output slice.** Verify RSS and iCalendar query filters, output headers, date/time behavior, escaping/serialization and empty result handling independently from HTML. Keep legacy feed endpoint names and stable fields. Do not assume HTML escaping is correct for XML/iCalendar.
6. **CSV paths last, with transaction-like row behavior.** Modernize parser/upload handling, validate required headers and each row before creating related records, retain duplicate rules and feedback categories, and keep export filters/column order stable. Correct the PHP 8.3-targeted export offset syntax here and compare imports/exports against fixtures from the baseline. Plan rollback or row-level failure reporting for partial imports; do not silently rewrite historical values.

Each slice should be reviewable and reversible. The repository map reports no automated test suite; the roadmap should budget explicit validation against the supported matrix, existing database fixtures, templates, both feeds and CSV round trips rather than treating a successful plugin load as sufficient evidence.

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| Small site / tens of thousands of shows | Keep synchronous PHP, `$wpdb`, and current four-table layout; bound feed results and preserve existing query indexes/schema. Focus on query correctness and PHP warnings. |
| Larger catalog / high admin traffic | Profile actual joined queries and pagination first; optimize indexes or query shape only after a schema review. Consider chunked CSV processing and bounded memory after measuring realistic files. |
| Very high traffic across many sites | Add cache only with invalidation on every show/entity mutation and settings change; avoid premature async workers or service decomposition for this compatibility goal. |

### Scaling Priorities

1. **First bottleneck:** Repeated joined queries and unbounded admin/import work on a large site. Preserve result behavior while adding measured limits or batching, not speculative abstractions.
2. **Second bottleneck:** Public list/feed query repetition. Only share retrieval after tests show identical scope and date semantics; cache only after mutation invalidation is explicit.

## Anti-Patterns

### Anti-Pattern 1: Rebuilding the plugin during a compatibility update

**What people do:** Replace the global procedural modules with a framework, rewrite persistence, rename public functions and templates, or move data into custom post types in one release.

**Why it's wrong:** These changes conflate runtime compatibility with product/data migration and can break theme overrides, shortcodes, feeds, stored IDs and third-party code without making the reported defect easier to isolate.

**Do this instead:** Keep current boundaries; add narrow helpers and adapters only where needed, then plan any data-model migration separately with explicit backfill and rollback.

### Anti-Pattern 2: Fixing the menu warning by guessing at separator names

**What people do:** Rename a separator string based only on `Undefined array key "separator-gigpress"`, or disable all menu ordering without observing the generated arrays.

**Why it's wrong:** The source currently registers `separator-gp`, not the reported `separator-gigpress`; core versions may transform/consume menu order differently, and other filters can contribute entries. The warning clue does not establish which callback created the missing key.

**Do this instead:** Reproduce with the target WordPress/PHP pair, inspect the final menu and order arrays plus callbacks, then simplify the custom ordering or make it robust and test the same pair.

### Anti-Pattern 3: Treating database values as already-safe HTML

**What people do:** Rely on `wptexturize()`, database sanitization, or `gigpress_prepare()` prebuilt HTML as universal output protection.

**Why it's wrong:** Admin HTML, template HTML, RSS XML and iCalendar have different contexts; content can enter through CSV, old database records or theme overrides. Escaping at the wrong layer can create injection risk or double-escaping regressions.

**Do this instead:** Validate and sanitize at input, prepare SQL safely, escape at the final context, and document any legacy field that intentionally returns markup. Migrate one renderer/format at a time.

### Anti-Pattern 4: Replacing the custom template path with standard theme lookup

**What people do:** Swap `gigpress_template()` for `locate_template()` and rename fragments to WordPress conventional paths.

**Why it's wrong:** Existing users may have templates in `/gigpress-templates/` or `wp-content/gigpress-templates/`; names and include variables are part of the live customization contract. Core `locate_template()` searches conventional stylesheet/template directories, so it does not preserve the extra content-directory fallback on its own.

**Do this instead:** Keep the resolver and ordering, constrain the internal template key, add optional new fragment contracts, and give users a deprecation window before any removal.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| WordPress core | Hooks, plugin metadata, menu APIs, settings/options, `$wpdb`, shortcodes, widgets, feed routing, posts | Treat WP 7.0+ as the compatibility floor; verify the reported 7.1.2 warning on the exact release line. Keep declared capability and actual callback checks aligned. |
| Theme customization | Plugin-specific PHP template lookup in child theme, parent theme, `wp-content`, plugin fallback | Preserve the current directory/name/variable contract; standard WordPress template hierarchy is useful guidance but is not identical to this resolver. |
| CSV consumers | File upload and downloads parsed by a bundled CSV library and spreadsheet programs | Preserve header names, field order, date/time representation and import deduplication behavior. Validate content rather than relying only on extension/MIME. |
| Google Calendar/Maps links | URL generation from local rows | No API credentials or remote service client. Preserve generated links while encoding/escaping each context correctly. |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| `gigpress.php` ↔ `admin/*` | Global functions, `$gpo`, table constants, WordPress callbacks | Bootstrap loads all files; preserve global function names and do not read request data before authorization/normalization. |
| `admin` renderers ↔ `admin/handlers.php` | POST fields, `gpaction`, nonce action, redirects/notices | Form field and action names are compatibility surfaces; change only with backward-compatible parsing. |
| Admin/output modules ↔ `admin/db.php` | `$wpdb`, `GIGPRESS_*` table constants, option globals | Keep schema untouched for runtime fixes and preserve `$wpdb->prefix` behavior. |
| `output/*` ↔ `gigpress_prepare()` | Joined row object plus scope string; associative prepared array | Existing keys and scope-specific HTML/plain distinctions feed templates and serializers. |
| Output renderers ↔ `gigpress_template()` | Template key and PHP include scope | Preserve basename, lookup order and variables expected by third-party overrides. |
| Feed routes ↔ clients | `?feed=gigpress` and `?feed=gigpress-ical`, GET filter fields | URLs and serializer formats are public contracts; maintain filtering, limits, calendar dates and response headers. |

## Sources

- WordPress Plugin Handbook, [Administration Menus](https://developer.wordpress.org/plugins/administration-menus/) and [Sub-Menus](https://developer.wordpress.org/plugins/administration-menus/sub-menus/) — register page callbacks through `admin_menu`; callback code should check capabilities and nonce/validation for writes. **Confidence: MEDIUM** (official docs, cross-checked with the current code map).
- WordPress Developer Reference, [`add_menu_page()`](https://developer.wordpress.org/reference/functions/add_menu_page/) and [`add_submenu_page()`](https://developer.wordpress.org/reference/functions/add_submenu_page/) — menu capabilities, slug constraints, hook timing, registration return values and optional position semantics. **Confidence: MEDIUM**.
- WordPress Common APIs Handbook, [Security](https://developer.wordpress.org/apis/security/), [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/) — validate/sanitize input, prefer validation, escape late for the relevant output context, use WordPress APIs. **Confidence: MEDIUM**.
- WordPress Developer Reference, [`locate_template()`](https://developer.wordpress.org/reference/functions/locate_template/) — child theme before parent theme and template loading behavior; used here only to compare standard lookup with GigPress’s custom fallback contract. **Confidence: MEDIUM**.
- WordPress Plugin Handbook, [Header Requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/) — the main plugin header can declare `Requires at least` and `Requires PHP`. **Confidence: MEDIUM**.
- Repository architecture map, integration map, conventions and inspected source at `gigpress.php`, `admin/handlers.php`, `admin/import-export.php`, `output/gigpress_shows.php`, `output/feed.php`, and `output/ical.php`. **Confidence: HIGH** for the observed current structure; live behavior on WP 7.1.2 remains unverified.

---
*Architecture research for: GigPress compatibility modernization*
*Researched: 2026-10-03*

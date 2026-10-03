---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
<!-- refreshed: 2026-10-03 -->

# Architecture

**Analysis Date:** 2026-10-03

## System Overview

```text
┌─────────────────────────────────────────────────────────────┐
│ WordPress plugin bootstrap                                  │
│ `gigpress.php`                                               │
│ constants, includes, hooks, shortcodes, shared helpers       │
└───────────────┬───────────────────────┬─────────────────────┘
                │                       │
                ▼                       ▼
┌───────────────────────────┐  ┌──────────────────────────────┐
│ Admin UI and mutations     │  │ Public presentation           │
│ `admin/*.php`              │  │ `output/*.php`, `templates/`  │
│ screens, handlers, settings│  │ shortcodes, widget, feeds    │
└──────────────┬────────────┘  └──────────────┬───────────────┘
               │                              │
               └──────────────┬───────────────┘
                              ▼
                 ┌────────────────────────────┐
                 │ WordPress options and DB    │
                 │ `admin/db.php`, `$wpdb`      │
                 │ shows/artists/venues/tours   │
                 └────────────────────────────┘
```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| Bootstrap and integration | Defines table/version constants, loads modules, registers WordPress hooks, exposes shared helpers | `gigpress.php` |
| Persistence and upgrades | Declares four custom tables, defaults, installation, schema migrations, uninstall | `admin/db.php` |
| Admin screens | Renders add/edit/list/settings/import-export pages | `admin/new.php`, `admin/shows.php`, `admin/artists.php`, `admin/venues.php`, `admin/tours.php`, `admin/settings.php`, `admin/import-export.php` |
| Admin mutations | Validates requests and inserts, updates, soft-deletes, restores, imports, and reorders records | `admin/handlers.php` |
| Public show rendering | Queries shows, applies filters/scopes, prepares data, renders overridable templates and JSON-LD | `output/gigpress_shows.php`, `gigpress.php` |
| Related post integration | Adds show details to post content and maintains show/post links | `output/gigpress_related.php`, `gigpress.php` |
| Sidebar/widget rendering | Registers `Gigpress_widget` and renders filtered sidebar lists | `output/gigpress_sidebar.php` |
| Syndication | Emits RSS and iCalendar responses through WordPress feeds | `output/feed.php`, `output/ical.php` |
| Presentation templates | Small PHP fragments for lists, headings, menus, empty states, and sidebars | `templates/*.php` |

## Pattern Overview

**Overall:** Procedural WordPress plugin with a shared bootstrap and direct database access.

**Key Characteristics:**
- `gigpress.php` is the composition root and owns most cross-cutting helpers and WordPress hook registration.
- Modules use global `$wpdb`, `$gpo`, `$gp_countries`, and table constants rather than injected services.
- Admin and public modules query the custom tables directly, then pass prepared arrays into PHP partial templates.
- WordPress supplies routing, lifecycle hooks, options, posts, feeds, shortcodes, widgets, permissions, and nonce APIs.

## Layers

**Bootstrap/integration layer:**
- Purpose: Load the plugin and connect it to WordPress.
- Location: `gigpress.php`
- Contains: Constants, includes, hooks, shortcodes, widget registration, formatting and URL helpers.
- Depends on: WordPress APIs and `admin/db.php`.
- Used by: Every admin and output module.

**Persistence layer:**
- Purpose: Own custom schema, settings defaults, installation, and migrations.
- Location: `admin/db.php`
- Contains: `GIGPRESS_*` table definitions, `gigpress_settings`, `dbDelta()` setup, upgrade functions.
- Depends on: `$wpdb`, WordPress upgrade APIs, plugin constants.
- Used by: Bootstrap, admin screens, handlers, and output queries.

**Administration layer:**
- Purpose: Provide privileged CRUD and configuration workflows.
- Location: `admin/`
- Contains: Page renderers in entity files and request mutation functions in `handlers.php`.
- Depends on: `$wpdb`, settings, nonce checks, WordPress posts/options.
- Used by: WordPress admin menu callbacks registered in `gigpress.php`.

**Presentation/output layer:**
- Purpose: Produce front-end HTML, widget output, RSS, and iCalendar.
- Location: `output/`, `templates/`, and rendering helpers in `gigpress.php`.
- Contains: Query/filter logic, output formatting, feed serialization, and template includes.
- Depends on: Custom tables, settings, WordPress permalink/date/escaping APIs.
- Used by: Shortcodes, content filters, widgets, and feed endpoints.

## Data Flow

### Primary Admin Request Path

1. WordPress loads the plugin bootstrap (`gigpress.php`).
2. `admin_menu` invokes a page callback such as `gigpress_admin_shows()` (`admin/shows.php`).
3. Forms post to an admin action handled by a function in `admin/handlers.php`; nonce and field checks run before `$wpdb->insert()` or `$wpdb->update()`.
4. Records are stored in `wp_gigpress_shows`, `wp_gigpress_artists`, `wp_gigpress_venues`, or `wp_gigpress_tours`, with related WordPress posts created by `wp_insert_post()` when configured (`admin/handlers.php`).

### Primary Public Request Path

1. A shortcode such as `gigpress_shows` invokes `gigpress_shows()` (`output/gigpress_shows.php`).
2. The function builds scope, date, artist, tour, venue, and pagination conditions and queries joined custom tables.
3. Each row is normalized by `gigpress_prepare()` (`gigpress.php`).
4. Renderer functions include fragments selected by `gigpress_template()` (`gigpress.php`), allowing child theme, parent theme, `wp-content`, or plugin fallback templates.
5. The response may include JSON-LD generated by `gigpress_json_ld()` (`output/gigpress_shows.php`).

### Feed Path

1. `init` calls `add_gigpress_feeds()` (`gigpress.php`).
2. WordPress routes `?feed=gigpress` to `gigpress_feed()` (`output/feed.php`) or `?feed=gigpress-ical` to `gigpress_ical()` (`output/ical.php`).
3. The feed module queries upcoming joined shows, calls `gigpress_prepare()` with a feed-specific scope, and serializes RSS XML or iCalendar text.

**State Management:** Persistent configuration lives in the `gigpress_settings` option; entity state lives in four custom tables. Show deletion is generally represented by `show_status = 'deleted'`, and related WordPress post IDs are stored in `show_related`.

## Key Abstractions

**Prepared show data:**
- Purpose: Convert a database row into context-aware display, calendar, link, and schema fields.
- Examples: `gigpress_prepare()` in `gigpress.php`, consumers in `output/gigpress_shows.php`, `output/feed.php`, and `output/ical.php`.
- Pattern: Shared associative array with scope-specific fields.

**Template resolver:**
- Purpose: Permit theme customization without editing plugin templates.
- Examples: `gigpress_template()` in `gigpress.php`, fragments in `templates/`.
- Pattern: Ordered filesystem fallback from child theme to parent theme to `wp-content` to plugin defaults.

**Custom table constants:**
- Purpose: Keep table names compatible with the active WordPress prefix.
- Examples: `GIGPRESS_SHOWS`, `GIGPRESS_ARTISTS`, `GIGPRESS_VENUES`, `GIGPRESS_TOURS` in `gigpress.php`.
- Pattern: Every SQL query builds against these constants and `$wpdb`.

## Entry Points

**Plugin load:**
- Location: `gigpress.php`
- Triggers: WordPress plugin discovery/load.
- Responsibilities: Define globals/constants, load modules, register hooks.

**Admin menu:**
- Location: `gigpress_admin_menu()` in `gigpress.php`
- Triggers: `admin_menu`.
- Responsibilities: Register entity, settings, and import/export pages.

**Show shortcodes:**
- Location: `output/gigpress_shows.php`
- Triggers: `add_shortcode()` registrations in `gigpress.php`.
- Responsibilities: Render upcoming, archive, filtered, and menu views.

**Widget:**
- Location: `Gigpress_widget` and `gigpress_load_widgets()` in `output/gigpress_sidebar.php`.
- Triggers: `widgets_init`.
- Responsibilities: Configure and render sidebar show lists.

**Feeds:**
- Location: `gigpress_feed()` and `gigpress_ical()` in `output/feed.php` and `output/ical.php`.
- Triggers: WordPress feed URLs registered during `init`.
- Responsibilities: Serialize upcoming show data.

## Architectural Constraints

- **Threading:** WordPress request lifecycle; no explicit worker or asynchronous model.
- **Global state:** `$wpdb`, `$gpo`, `$gp_db`, `$default_settings`, and `$gp_countries` are shared across modules; initialization occurs in `gigpress.php` and `admin/db.php`.
- **Circular imports:** No explicit module import cycle is detected; all modules are loaded from the bootstrap with `require()`.
- **WordPress coupling:** Modules assume WordPress globals, hooks, options, admin APIs, feed routing, and table prefix semantics.
- **Template override contract:** Public markup depends on template names and variables established before each `include`; preserve those variables when adding or changing templates.

## Anti-Patterns

### Direct SQL in presentation modules

**What happens:** `output/gigpress_shows.php`, `output/gigpress_sidebar.php`, `output/feed.php`, and `output/ical.php` each construct their own joined queries.
**Why it's wrong:** Query rules and escaping can drift across output formats.
**Do this instead:** Keep new shared retrieval or filtering logic in a reusable helper near `gigpress.php` or a dedicated output data module, then pass normalized rows to renderers.

### Shared mutable globals

**What happens:** Modules read and mutate `$gpo` and other globals directly, for example in `admin/db.php` and `gigpress.php`.
**Why it's wrong:** Initialization order and mutation side effects become implicit dependencies.
**Do this instead:** Use the established globals for compatibility, but isolate new settings reads/writes behind focused helpers and update the option once per request.

## Error Handling

**Strategy:** Validate admin form fields, use WordPress nonce checks, report admin errors inline, and rely on `$wpdb` return values for persistence failures.

**Patterns:**
- `gigpress_error_checking()` returns field keyed errors before mutations (`admin/handlers.php`).
- Mutation handlers call `check_admin_referer()` and show `$wpdb` errors in admin contexts (`admin/handlers.php`).
- Public queries generally render empty templates or empty feed bodies when no rows exist (`output/gigpress_shows.php`, `output/feed.php`).

## Cross-Cutting Concerns

**Logging:** No application logger is detected; optional diagnostics are exposed through `admin/debug.php` when `GIGPRESS_DEBUG` is enabled in `gigpress.php`.
**Validation:** WordPress sanitizers, `wpdb->prepare()`, `checkdate()`, and custom `gigpress_db_in()` are used at admin boundaries (`gigpress.php`, `admin/handlers.php`).
**Authentication:** WordPress admin capabilities from `$gpo['user_level']`, `manage_options`, admin routing, and nonce checks protect management actions (`gigpress.php`, `admin/handlers.php`).

---

*Architecture analysis: 2026-10-03*

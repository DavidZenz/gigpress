---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
# Codebase Structure

**Analysis Date:** 2026-10-03

## Directory Layout

```text
gigpress/
├── gigpress.php             # WordPress plugin bootstrap and shared helpers
├── admin/                    # Admin pages, CRUD handlers, schema and upgrades
├── output/                   # Shortcodes, widget, related posts, RSS and iCal
├── templates/                # PHP presentation partials with theme override support
├── lib/                      # Supporting libraries and country data
├── scripts/                  # Front-end and admin JavaScript
├── css/                      # Front-end and admin stylesheets
├── images/                   # Plugin icons and UI assets
├── langs/                    # gettext catalogs and compiled translations
├── .planning/codebase/       # Generated architecture mapping documents
└── readme.txt                # WordPress plugin readme
```

## Directory Purposes

**`admin/`:**
- Purpose: WordPress dashboard UI, request handlers, settings, import/export, schema lifecycle.
- Contains: Page callbacks in `new.php`, `shows.php`, `artists.php`, `venues.php`, `tours.php`, `settings.php`, and `import-export.php`; mutations in `handlers.php`; persistence in `db.php`.
- Key files: `admin/handlers.php`, `admin/db.php`, `admin/shows.php`.

**`output/`:**
- Purpose: Public-facing show queries and alternate output channels.
- Contains: `gigpress_shows.php`, `gigpress_sidebar.php`, `gigpress_related.php`, `feed.php`, and `ical.php`.
- Key files: `output/gigpress_shows.php`, `output/gigpress_sidebar.php`.

**`templates/`:**
- Purpose: Markup fragments included by public renderers.
- Contains: show list, artist/tour headings, menu wrappers, sidebar wrappers, and empty/footer fragments.
- Key files: `templates/shows-list.php`, `templates/shows-list-start.php`, `templates/sidebar-list.php`.

**`lib/`:**
- Purpose: Data tables and compatibility/support code.
- Contains: `countries.php`, `parsecsv.lib.php`, and JSON compatibility helper `upgrade.php`.

**`scripts/`, `css/`, `images/`, `langs/`:**
- Purpose: Browser behavior, styling, image assets, and translations.
- Key files: `scripts/gigpress.js`, `scripts/gigpress-admin.js`, `css/gigpress.css`, `css/gigpress-admin.css`, `langs/gigpress-en_US.pot`.

## Key File Locations

**Entry Points:**
- `gigpress.php`: Plugin load, constants, includes, WordPress hooks, shortcodes, and shared helpers.
- `output/feed.php`: RSS feed callback.
- `output/ical.php`: iCalendar feed callback.

**Configuration:**
- `admin/db.php`: Default `gigpress_settings` values and schema version.
- `admin/settings.php`: Dashboard settings UI and option updates.
- `gigpress.php`: Plugin version, database version, feed URLs, and debug switch.

**Core Logic:**
- `admin/handlers.php`: Entity validation and mutations.
- `output/gigpress_shows.php`: Public show filtering and rendering.
- `gigpress.php`: Show normalization, template resolution, related post integration, and common helpers.

**Testing:**
- Not detected. No dedicated test directory or test configuration is present in the repository listing.

## Naming Conventions

**Files:**
- Lowercase PHP filenames grouped by responsibility, such as `admin/import-export.php` and `output/gigpress_shows.php`.
- CSS and JavaScript use lowercase `gigpress` prefixes: `css/gigpress-admin.css`, `scripts/gigpress-admin.js`.
- Public template fragments use hyphenated semantic names: `shows-list-empty.php`, `sidebar-tour-heading.php`.

**Directories:**
- Lowercase role-based directories: `admin`, `output`, `templates`, `lib`, `scripts`, `css`, `images`, and `langs`.

## Where to Add New Code

**New Feature:**
- Admin workflow: add the page renderer to the closest file under `admin/` and mutation logic to `admin/handlers.php`; register the page in `gigpress_admin_menu()` in `gigpress.php`.
- Public show feature: add query/filter and public API code under `output/`, using `gigpress_prepare()` and existing templates where possible.
- Feed format: add a serializer under `output/` and register the route in `add_gigpress_feeds()` in `gigpress.php`.
- Tests: no existing test location is established; introduce tests only with an explicit project decision about framework and placement.

**New Component/Module:**
- Implementation: place admin functionality in `admin/`, public output in `output/`, shared low-level helpers in `gigpress.php` or `lib/`, and markup in `templates/`.
- Load order: add the module `require()` in `gigpress.php` before the hook or callback that depends on it.

**Utilities:**
- Shared WordPress/data helpers: `gigpress.php`.
- Parsing or compatibility utilities: `lib/`.
- Reusable markup: `templates/` with variables prepared by the caller.

## Special Directories

**`.planning/codebase/`:**
- Purpose: GSD generated architecture, structure, quality, stack, integration, and concern maps.
- Generated: Yes.
- Committed: Repository workflow determines this; current mapping output is written here for downstream planning.

**`langs/`:**
- Purpose: gettext source catalogs (`.po`, `.pot`) and compiled catalogs (`.mo`).
- Generated: `.mo` files are compiled artifacts; `.po` and `.pot` are translation sources.
- Committed: Yes, files are present in the repository.

**`images/`:**
- Purpose: Static plugin icons and calendar/feed assets.
- Generated: No.
- Committed: Yes.

---

*Structure analysis: 2026-10-03*

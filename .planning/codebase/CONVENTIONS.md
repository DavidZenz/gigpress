---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
# Coding Conventions

**Analysis Date:** 2026-10-03

## Naming Patterns

**Files:**
- Use lowercase PHP filenames grouped by role: `admin/handlers.php`, `output/gigpress_shows.php`, and `templates/shows-list.php`.

**Functions:**
- Use the `gigpress_` prefix for plugin functions, with snake_case names such as `gigpress_prepare_show_fields()` in `admin/handlers.php` and `gigpress_shows()` in `output/gigpress_shows.php`.
- WordPress callbacks retain the same prefixed naming pattern; hook registration is centralized near the end of `gigpress.php`.

**Variables:**
- Use lowercase snake_case for local variables and arrays, for example `$show_data`, `$further_where`, and `$default_settings` in `gigpress.php` and `admin/db.php`.
- Use uppercase constants with the `GIGPRESS_` prefix for table names, versions, URLs, and date values in `gigpress.php`.
- Shared plugin state is exposed through globals such as `$wpdb`, `$gpo`, `$gp_db`, and `$gp_countries`.

**Types:**
- This is procedural PHP with associative arrays and WordPress database result objects; there are no project-defined namespaces or typed domain classes.
- The legacy widget in `output/gigpress_sidebar.php` is the main class-based component.

## Code Style

**Formatting:**
- No formatter configuration is present. Match the existing tab-indented, brace-on-same-line PHP style and preserve mixed inline HTML/PHP templates.
- Existing code uses both compact one-line conditionals and multiline braces; keep new code readable and consistent with its surrounding file.

**Linting:**
- No ESLint, PHP_CodeSniffer, or project lint configuration is detected.
- Use WordPress escaping and sanitization helpers at boundaries, including `sanitize_text_field()`, `sanitize_sql_orderby()`, `esc_url()`, and `wptexturize()` as shown in `admin/shows.php` and `gigpress.php`.

## Import Organization

**Order:**
1. WordPress globals and plugin constants.
2. Required plugin modules from `admin/`, `output/`, and `lib/`.
3. Hook registrations and callback definitions.

**Path Aliases:**
- No path aliases are used. Modules are loaded with relative `require()` calls from `gigpress.php`.

## Error Handling

**Patterns:**
- Collect form and database failures in `$errors` arrays and display translated messages in admin flows, as in `admin/handlers.php`.
- Validate admin mutations with `check_admin_referer('gigpress-action')` and use `current_user_can()` checks where applicable.
- Use `$wpdb->prepare()` for dynamic SQL values and WordPress return values such as `wp_insert_post()` to detect failures.
- Compatibility helpers in `lib/upgrade.php` report invalid inputs with `trigger_error()`.

## Logging

**Framework:** `gigpress_debug()` in `admin/debug.php` plus WordPress/PHP behavior; no structured logger is configured.

**Patterns:**
- Keep diagnostic behavior behind the `GIGPRESS_DEBUG` constant and the optional Debug admin page wired in `gigpress.php`.
- Do not add direct production output for diagnostics in public rendering functions.

## Comments

**When to Comment:**
- Comment WordPress integration, compatibility workarounds, SQL/schema migrations, and template override behavior. Examples include `gigpress_template()` in `gigpress.php` and upgrade notes in `admin/db.php`.
- Preserve comments that explain backwards-compatible shortcodes in `output/gigpress_shows.php`.

**JSDoc/TSDoc:**
- Not used. PHPDoc appears only in the bundled compatibility/parser library `lib/parsecsv.lib.php`.

## Function Design

**Size:** Keep new functions focused on one hook, admin operation, output path, or transformation. Existing large handlers such as `gigpress_prepare_show_fields()` and `gigpress_shows()` coordinate several steps and should be changed carefully.

**Parameters:** Prefer optional associative-array filters for public rendering APIs, matching `gigpress_shows($filter = null, $content = null)` and `gigpress_sidebar($filter = null)`.

**Return Values:** Rendering functions commonly return buffered HTML strings; admin callbacks mostly echo markup or redirect, while preparation helpers return associative arrays such as `$show` and `$showdata`.

## Module Design

**Exports:** Files define globally available prefixed functions and are loaded from the main plugin file; there are no explicit module export systems.

**Barrel Files:** `gigpress.php` acts as the composition root by defining constants, loading modules, and registering WordPress hooks.

---

*Convention analysis: 2026-10-03*

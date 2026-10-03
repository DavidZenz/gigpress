---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
# Technology Stack

**Analysis Date:** 2026-10-03

## Languages

**Primary:**
- PHP (legacy PHP 5 compatibility) - WordPress plugin implementation in `gigpress.php`, `admin/`, `output/`, and `lib/`

**Secondary:**
- JavaScript - front-end and admin interactions in `scripts/gigpress.js` and `scripts/gigpress-admin.js`
- CSS - public and admin styling in `css/gigpress.css` and `css/gigpress-admin.css`
- gettext PO/MO - translations in `langs/`

## Runtime

**Environment:**
- WordPress plugin runtime, with WordPress core APIs and globals supplied by the host site
- PHP version is not declared; `lib/upgrade.php` provides compatibility shims for older PHP releases

**Package Manager:**
- None detected
- Lockfile: missing

## Frameworks

**Core:**
- WordPress 3.0+ API (tested up to 4.3 per `readme.txt`) - plugin lifecycle, hooks, admin screens, shortcodes, widgets, localization, and database access

**Testing:**
- Not detected

**Build/Dev:**
- No build system detected; PHP, JavaScript, CSS, image, and translation assets are committed directly

## Key Dependencies

**Critical:**
- WordPress core - required APIs include `$wpdb`, Settings API, admin menu APIs, shortcode APIs, feed rewrites, post APIs, and enqueue APIs in `gigpress.php` and `admin/`
- MySQL-compatible WordPress database - custom GigPress tables created through `dbDelta()` in `admin/db.php`

**Infrastructure:**
- jQuery and jQuery UI Sortable supplied/enqueued by WordPress in `gigpress.php`
- Bundled `parsecsv` implementation in `lib/parsecsv.lib.php` for CSV import/export
- Bundled PHP compatibility layer in `lib/upgrade.php` for JSON and other newer PHP functions

## Configuration

**Environment:**
- WordPress options store; `gigpress_settings` and defaults are defined in `admin/db.php`
- Host WordPress configuration supplies database connection, site URL, timezone, locale, filesystem, and admin email

**Build:**
- Plugin metadata and version are in `gigpress.php`
- Database schema and migrations are in `admin/db.php` and `lib/upgrade.php`
- No package, compiler, or bundler configuration detected

## Platform Requirements

**Development:**
- A WordPress installation capable of loading classic PHP plugins, with database access and writable WordPress upload/plugin paths for CSV workflows

**Production:**
- WordPress 3.0 or newer is declared in `readme.txt`; actual supported PHP and WordPress versions are not otherwise enforced in code

---

*Stack analysis: 2026-10-03*

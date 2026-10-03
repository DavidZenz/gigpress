---
last_mapped_commit: 93f21160ad34b07f7ef2909c068ba51b4dd23dcc
last_mapped_at: 2026-10-03
---
# External Integrations

**Analysis Date:** 2026-10-03

## APIs & External Services

**WordPress platform:**
- WordPress core APIs - plugin lifecycle, admin UI, shortcodes, widgets, posts, settings, localization, rewrite/feed registration, and asset loading
  - SDK/Client: WordPress PHP functions and globals, including `$wpdb`
  - Auth: WordPress roles/capabilities; configured default is `edit_posts`, while settings require `manage_options` (`gigpress.php`, `admin/settings.php`)

**Calendar and mapping links:**
- Google Calendar event links - generated as outbound links for individual shows in `gigpress.php`
  - SDK/Client: URL construction; no API client or credentials
  - Auth: None
- Google Maps - venue address links generated from show venue data in `gigpress.php`
  - SDK/Client: URL construction to `maps.google.com`
  - Auth: None

## Data Storage

**Databases:**
- WordPress database, typically MySQL/MariaDB
  - Connection: WordPress `$wpdb` configuration
  - Client: `$wpdb` queries and `dbDelta()`; tables are `${wpdb->prefix}gigpress_shows`, `gigpress_artists`, `gigpress_venues`, and `gigpress_tours` (`admin/db.php`)

**File Storage:**
- WordPress uploads/filesystem for uploaded CSV imports via `wp_upload_bits()` in `admin/handlers.php`
- Plugin-local committed assets and translations under `images/`, `scripts/`, `css/`, `templates/`, and `langs/`

**Caching:**
- None detected

## Authentication & Identity

**Auth Provider:**
- WordPress roles and capabilities
  - Implementation: admin menus and settings use capability checks; AJAX and admin-post handlers rely on WordPress request context and nonces where implemented (`gigpress.php`, `admin/handlers.php`)

## Monitoring & Observability

**Error Tracking:**
- None detected

**Logs:**
- WordPress/database error display is used in selected admin handlers via `$wpdb->show_errors()`; `admin/debug.php` is available only when `GIGPRESS_DEBUG` is enabled in `gigpress.php`

## CI/CD & Deployment

**Hosting:**
- Deployed as a WordPress plugin directory; no hosting provider integration detected

**CI Pipeline:**
- None detected

## Environment Configuration

**Required env vars:**
- None declared; configuration is read from WordPress core and the `gigpress_settings` option

**Secrets location:**
- No plugin-managed secrets detected; WordPress database credentials and admin identity remain host configuration

## Webhooks & Callbacks

**Incoming:**
- WordPress hooks and request endpoints: `init` registers GigPress RSS/iCalendar feeds; `admin_post_gigpress_export`, `wp_ajax_gigpress_reorder_artists`, and feed query parameters are handled in `gigpress.php` and `admin/handlers.php`

**Outgoing:**
- RSS 2.0 feed emitted by `output/feed.php` at the `gigpress` feed query
- iCalendar feed emitted by `output/ical.php` at the `gigpress-ical` feed query, including `webcal://` convenience URL
- Schema.org/Event JSON-LD emitted in `output/gigpress_shows.php` and `output/gigpress_related.php`
- Per-show Google Calendar and Google Maps links are rendered from local show data; no authenticated outbound requests are made

---

*Integration audit: 2026-10-03*

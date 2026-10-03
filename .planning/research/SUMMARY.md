# Project Research Summary

**Project:** GigPress
**Domain:** Brownfield WordPress plugin compatibility and workflow improvements
**Researched:** 2026-10-03
**Confidence:** MEDIUM

## Executive Summary

GigPress is a procedural WordPress plugin that manages shows and their artists, venues, and tours, then publishes them through admin workflows, theme-overridable templates, shortcodes/widgets, RSS, iCalendar, and CSV. The research recommends keeping its current architecture and public contracts while repairing compatibility at request, hook, persistence, and serialization boundaries. The product baseline is WordPress 7.0+ and PHP 8.3+; PHP 8.2 is only the environment reported with the warning.

Roadmap work should first establish a bootable compatibility baseline, diagnose the WordPress admin-menu warning from actual menu/order arrays and active callbacks, and prove existing data and output contracts remain intact. Then deliver the five approved UX areas: show date/time entry, show-list filters and bulk actions, settings organization/help, responsive public displays, and import/export layout/feedback. The largest risks are legacy syntax that can prevent PHP 8.3 parsing, unintended schema or historical-data changes, and breaking theme overrides or feeds through broad rendering changes. Isolate work into reviewable slices and verify each output format and extension contract independently.

## Key Findings

### Recommended Stack

Keep WordPress and PHP as host-provided runtimes; add only development and CI tooling. Declare `Requires at least: 7.0` and `Requires PHP: 8.3`; validate WordPress 7.0 and 7.1 release lines against PHP 8.3, 8.4, and 8.5, refreshing current stable patches over time. Static checks (PHP lint, WordPress Plugin Check, WPCS/PHPCompatibilityWP) complement behavioral validation but cannot prove compatibility. Add PHPUnit with a disposable WordPress database incrementally, beginning with activation, menu, CRUD, feeds, CSV, and upgrade behavior. See [STACK.md](STACK.md).

**Core technologies:**
- WordPress 7.0+ — host APIs and the user-selected minimum; test the minimum and current stable release lines.
- PHP 8.3+ — runtime floor; scan every shipped PHP file for syntax/API incompatibilities, including bundled libraries.
- PHPUnit/WordPress test environment and Plugin Check — development-only behavioral and static checks for a plugin with no existing automated suite.

### Expected Features

The compatibility gate and preservation of existing records, options, workflows, feeds, CSV format, and templates are table stakes. After that gate, complete all five selected UX improvements. Accessibility, authorization, and context-specific output safety are shared requirements on touched screens and renderers.

**Must have (table stakes):**
- WordPress 7.0+ / PHP 8.3+ compatibility and warning-free core workflows, with the reported menu issue investigated.
- Preserve custom-table schema and existing show/entity IDs, relationships, settings, CRUD, soft-delete behavior, shortcode/widget, RSS/iCalendar, CSV, and template override contracts.
- Keep classic admin conventions, capability and nonce checks, validation, accessible labels and errors, and context-appropriate escaping.

**Should have (approved UX outcomes in this delivery):**
- Add/edit date and time workflow with clear labels, validation, preserved values on errors, and unchanged stored semantics.
- Show-list filtering and bulk actions with persistent visible filter state, explicit selected-record actions, and clear results.
- Better grouped settings and adjacent help while retaining all existing option keys and meanings.
- Responsive public show listings at 320 CSS px while preserving template names, variables, CSS hooks, and override lookup order.
- Clearer import/export layout and row-level outcome feedback while retaining GigPress CSV columns, filters, and data meaning.

**Defer (v2+):**
- Ticketing, booking, recurring events, maps, reminders, external integrations, or arbitrary CSV mapping.
- React/block-editor replacement or wholesale admin/framework rewrite.
- Schema/data-model changes unless a separately justified, versioned migration is necessary.

### Architecture Approach

Keep the procedural composition root, module boundaries, custom tables, global function names, WordPress hooks, and classic admin pages. Make narrow changes at lifecycle, request, database, template, and output boundaries. Keep persistence/upgrade work isolated; preserve `gigpress_prepare()` fields through additive adapters; keep HTML templates, RSS XML, iCalendar, and CSV serialization distinct. The custom resolver's child-theme → parent-theme → `wp-content/gigpress-templates` → bundled fallback order is an established plugin API and must remain intact. See [ARCHITECTURE.md](ARCHITECTURE.md).

**Major components:**
1. `gigpress.php` — bootstrap, hooks, public functions, shared preparation, compatibility metadata.
2. `admin/` and `admin/db.php` — classic renderers and handlers, request authorization/validation, custom tables and versioned upgrades.
3. `output/`, `templates/`, and `gigpress_template()` — public queries, template overrides, RSS/iCalendar serializers, shortcodes/widgets.
4. CSV boundary (`admin/handlers.php`, `admin/import-export.php`, `lib/parsecsv.lib.php`) — parse/validate/import outcomes and stable export format.

### Critical Pitfalls

1. **Guessing the menu-warning cause from the slug** — reproduce on the reported WordPress release, inspect final `$menu`, default slugs, returned ordering, and active callbacks. Repository code uses `separator-gp`; the reported `separator-gigpress` may come from a deployed version or another callback. Do not mutate `$menu` inside the ordering filter.
2. **Treating this as only a PHP 8.2-to-8.3 change** — curly-brace offsets were removed in PHP 8.0 and occur in the plugin and bundled/upgrade paths. Parse-check all shipped PHP files on supported PHP versions before UX work.
3. **Changing schema or historical data during cleanup/UI work** — preserve tables, IDs, statuses, settings, and post links. Any required migration must be versioned, repeat-safe, bounded, and checked against representative database copies.
4. **Using HTML escaping for every output** — serialize RSS as XML and iCalendar according to its text/line-folding rules; keep raw values until the appropriate boundary and validate hostile punctuation/newlines.
5. **Breaking theme overrides during responsive changes** — preserve lookup paths/order, names, include variables, prepared fields, and CSS hooks; verify bundled and override templates.

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Compatibility Baseline and Menu Diagnosis
**Rationale:** All later UX work depends on a plugin that loads and hooks correctly at the selected floor; the warning is localized to admin menu construction but its source is unconfirmed.
**Delivers:** Correct metadata, PHP 8.3 parse/runtime cleanup across all shipped code, reproducible menu diagnosis/fix, and a baseline validation plan across WordPress 7.0/7.1 and PHP 8.3+.
**Addresses:** Platform compatibility and existing workflow preservation prerequisites.
**Avoids:** Slug guessing, PHP 8.2 becoming an implied support target, and fixing only frequently loaded PHP files.
**Research flag:** Needs focused planning research/reproduction for the warning and active menu callback interaction. Official API/core behavior is documented, but exact cause requires the affected environment.

### Phase 2: Data and Existing Contract Characterization
**Rationale:** Before changing screens, establish the storage, options, function/hook, feeds, CSV, and template contracts that an upgrade must preserve.
**Delivers:** Representative old-schema fixtures/checks and before/after inventory of entity counts, IDs, statuses, relationships, option values, public endpoint behavior, CSV columns, and template variables. Keep schema unchanged by default.
**Addresses:** Existing data and workflow preservation across CRUD, upgrades, feeds, CSV, and overrides.
**Avoids:** Accidental data-model migration, schema changes mixed into UI tasks, and unrepeatable legacy upgrade behavior.
**Research flag:** Needs project-specific source/fixture investigation because historical database versions and upgrade branches are not fully characterized in the research.

### Phase 3: Admin Workflow Improvements
**Rationale:** The date/time form, show-list filters/bulk actions, and settings organization share classic admin semantics and can build on one accessible, authorized request/form baseline.
**Delivers:** The three approved admin UX areas with preserved field/action/option contracts, filter state, capability+nonce checks, keyboard operation, labels, and actionable text errors/notices.
**Addresses:** Add-show date/time workflow; show-list filtering and bulk actions; settings organization and help.
**Uses:** Existing `admin/` renderer/handler split, WordPress core admin/Settings APIs where useful without requiring broad migration.
**Avoids:** Changed date/time semantics, stale/destructive bulk selection, hidden legacy settings loss, and inaccessible or unauthorized mutations.
**Research flag:** Standard WordPress patterns; planning should inspect existing field names, defaults, query state, and option inventory. No broad external framework research is needed.

### Phase 4: Public Display and Feed Correctness
**Rationale:** Public HTML and machine-consumed feeds share data preparation but have different output contracts; isolate the serializers before or alongside responsive CSS.
**Delivers:** Responsive default show listings and separately verified RSS/iCalendar output, with stable query filters, fields, template variables, endpoint slugs, and override lookup behavior.
**Addresses:** Responsive public show displays and baseline preservation of publishing surfaces.
**Uses:** Existing `output/`, `templates/`, `gigpress_prepare()`, and custom template resolver.
**Avoids:** Replacing resolver paths, changing fields seen by theme overrides, applying HTML escaping to XML/iCalendar, or losing feed URL/date semantics.
**Research flag:** Needs targeted verification of feed serializers against XML and RFC 5545 edge cases, plus representative theme override fixtures. WordPress escaping guidance alone does not establish current plugin output correctness.

### Phase 5: Import/Export UX and Integrity
**Rationale:** CSV is a separate, data-mutating boundary with legacy parser syntax, row-level partial outcomes, and compatibility requirements; tackle it after storage contracts are characterized.
**Delivers:** Clear import/export task layout, validated rows, useful totals and per-row reasons, safe spreadsheet output, and unchanged compatible CSV headers/order/filters.
**Addresses:** Import/export layout and feedback.
**Uses:** Existing parser and handler boundary, updated for PHP 8.3 where needed.
**Avoids:** Orphan related records, silent partial imports, broken round trips, formula execution risks, and memory-heavy exports.
**Research flag:** Needs fixture-driven planning for duplicate/invalid rows, partial failure behavior, spreadsheet safety, and CSV round-trip expectations.

### Phase Ordering Rationale

- Compatibility comes first because every admin, public, feed, and import workflow depends on successful loading and stable hooks.
- Characterize schema and external contracts before redesigning forms or output; default to no schema migration.
- Group admin improvements around shared form accessibility/security, but retain existing procedural renderers and handlers.
- Treat HTML, RSS XML, iCalendar, and CSV as separate serialization targets with format-specific acceptance checks.
- Make responsive work additive around current template contracts, and keep CSV work isolated because it can create records as it parses rows.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 1:** Reproduce the `separator-gigpress` warning with exact plugin/core versions and inspect active `menu_order` callbacks; cause remains unresolved.
- **Phase 2:** Identify the historical schema versions and upgrade paths that need representative fixtures; verify preservation assumptions.
- **Phase 4:** Validate RSS XML/iCalendar serialization and theme override compatibility with hostile values and real override layouts.
- **Phase 5:** Define row-level import semantics and safe CSV export/round-trip behavior with representative fixtures.

Phases with standard patterns (skip broad research-phase):
- **Phase 3:** WordPress classic admin forms, labels, Settings API conventions, capability checks, nonces, and table semantics are well documented; inspect local contracts during planning.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | MEDIUM | Official PHP/WordPress docs and release tables are strong; the support floor is user-selected, while current stable patch versions change and must be refreshed at execution time. |
| Features | MEDIUM | Existing product scope and five UX areas are grounded in project decisions and code reading; acceptance outcomes are proposed, not validated by a live-site audit. |
| Architecture | MEDIUM | Repository structure and contracts are well mapped; runtime behavior on the target releases and historical database state remain unverified. |
| Pitfalls | MEDIUM | PHP syntax and core menu behavior are supported by primary sources; attribution of the exact reported warning remains open, and data risks need fixture verification. |

**Overall confidence:** MEDIUM

### Gaps to Address

- **Warning attribution:** Reported `separator-gigpress` differs from this checkout's `separator-gp`; get exact deployed plugin build and enumerate active callbacks through reproduction on WordPress 7.1.2. Keep PHP 8.2 as repro context only; target support remains PHP 8.3+.
- **Historical database coverage:** Research did not establish all schema versions/sites needing upgrade coverage. Planning should inventory `db_version` branches and construct sanitized representative copies before any migration-adjacent change.
- **Automated validation:** No test suite exists. Decide a staged path from lint/static checks and manual disposable-site smoke checks to database-backed PHPUnit coverage and CI matrix.
- **Live UX evidence:** The five areas were selected from source review, not live-site observation. Validate acceptance criteria against actual workflows during implementation without expanding product scope.
- **Current versions:** Refresh WordPress/PHP stable patch versions and active support status when plans are executed; do not treat research-date patches as permanent.
- **Output contracts:** Record representative RSS, iCalendar, CSV, and theme override fixtures before changing serialization or rendering.

## Sources

### Primary (HIGH confidence)
- [WordPress plugin header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/) and [readme documentation](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) — minimum and tested-version metadata.
- [WordPress release archive](https://wordpress.org/download/releases/), [PHP supported versions](https://www.php.net/supported-versions.php), and [WordPress PHP compatibility table](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/) — research-date support matrix.
- [PHP 8.0 incompatible changes](https://www.php.net/manual/en/migration80.incompatible.php) and [PHP 8.3 migration guide](https://www.php.net/manual/en/migration83.php) — removed syntax and runtime changes.
- [WordPress `custom_menu_order`](https://developer.wordpress.org/reference/hooks/custom_menu_order/), [`menu_order`](https://developer.wordpress.org/reference/hooks/menu_order/), and [WordPress 7.1.2 menu construction](https://github.com/WordPress/wordpress-develop/blob/7.1.2/src/wp-admin/includes/menu.php) — ordering contract; exact warning attribution still requires reproduction.
- [WordPress security APIs](https://developer.wordpress.org/apis/security/), [escaping guidance](https://developer.wordpress.org/apis/security/escaping/), [table creation/upgrades](https://developer.wordpress.org/plugins/creating-tables-with-plugins/), and [`dbDelta()`](https://developer.wordpress.org/reference/functions/dbDelta/) — input/output boundaries and safe schema upgrade patterns.
- [WordPress Plugin Check](https://github.com/WordPress/plugin-check), [automated testing handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/), [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/), and [Sub-Menus](https://developer.wordpress.org/plugins/administration-menus/sub-menus/) — validation and admin patterns.

### Repository evidence (HIGH for inspected structure; not external validation)
- `.planning/PROJECT.md`, `.planning/codebase/ARCHITECTURE.md`, `.planning/codebase/CONCERNS.md`, and inspected GigPress sources identified in the research reports — data/schema boundaries, custom template resolver, legacy syntax, feed and CSV behavior.

### Secondary (MEDIUM confidence)
- [WordPress accessibility site feedback guide](https://make.wordpress.org/accessibility/handbook/get-involved/site-feedback-guide/), [table style guidance](https://make.wordpress.org/docs/style-guide/formatting/tables/), [theme handbook](https://developer.wordpress.org/themes/getting-started/what-is-a-theme/), and [template hierarchy](https://developer.wordpress.org/themes/classic-themes/basics/template-hierarchy/) — UX/accessibility context; GigPress's custom resolver remains a repository-specific contract.

---
*Research completed: 2026-10-03*
*Ready for roadmap: yes*

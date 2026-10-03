# Feature Research

**Domain:** Brownfield WordPress show and event listing plugin compatibility release
**Researched:** 2026-10-03
**Confidence:** MEDIUM

## Feature Landscape

GigPress already covers its product's central jobs: maintain shows and their artist, venue, and tour relationships; publish upcoming, past, and filtered listings; and provide RSS, iCalendar, related-post, and CSV workflows. This delivery should protect those expectations while polishing the five user-approved workflows. Compatibility with WordPress 7.0+ and PHP 8.3+ is the release gate; UX improvements follow behind it.

### Table Stakes (Users Expect These)

Compatibility-critical items are P1 and precede UX work. “Approved UX outcomes” are the five requested improvements, remain within GigPress's existing domain, and have concrete acceptance targets below.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Supported platform compatibility | Admin warnings, fatal errors, and save regressions make a plugin unusable | HIGH | P1. Verify activation, menu registration, add/edit/list/settings/import-export, feeds, and public rendering on the selected WordPress 7.0+ and PHP 8.3+ minimums. State minimums in plugin metadata/readme. PHP 8.2 is diagnostic context, not a support target. |
| Preserve show, artist, venue, and tour data and current workflows | Existing sites depend on those records and their relationships | HIGH | P1. Keep custom table contents/schema and existing setting values usable; retain CRUD, soft delete/restore, copy, related posts, shortcode/widget, RSS, iCalendar, and CSV formats/filters. Avoid an unsolicited data-model migration. |
| Reliable add/edit show date, time, and duration entry | Correct date/time is the defining data of a show listing | MEDIUM | P1 baseline plus approved improvement. Continue to support existing localized date display, optional time, multi-day dates, defaults, and validation. Do not change the stored date/time representation or meaning as a UX shortcut. |
| Filterable, paginated show administration | Managers need to find past/upcoming records and narrow by artist, tour, and venue | MEDIUM | P1 baseline plus approved improvement. Preserve All/Upcoming/Past, saved scope/sort/page size, current filters, pagination, and trash operations. Filter changes must not silently reset unrelated scope. |
| Classic WordPress admin conventions | Plugin pages should be recognizable, capability-guarded, and resilient to core changes | MEDIUM | P1. Keep classic admin pages and WordPress navigation, `.wrap`, page headings, core table/form styling, capability checks, nonce checks on mutations, and success/error notices. Consider Settings API registration for settings sections/fields, but do not require a broad rewrite to realize organization/help. [Plugin admin menu guidance](https://developer.wordpress.org/plugins/administration-menus/sub-menus/), [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) |
| Accessible admin forms and tables | Labeled controls and semantic tables make data entry and show management usable with keyboard and assistive technology | MEDIUM | P1. Associate every input with a label; preserve meaningful heading order, table header cells with `scope`, row headings where appropriate, visible focus, and text-based validation feedback. Errors identify the field and how to correct it; do not rely on color alone. [WordPress accessibility feedback guide](https://make.wordpress.org/accessibility/handbook/get-involved/site-feedback-guide/), [WordPress table guidance](https://make.wordpress.org/docs/style-guide/formatting/tables/) |
| Readable public show listings at narrow widths | Visitors commonly view schedules in constrained viewports; clipped date/location/ticket details undermine the core publishing job | MEDIUM | P1 baseline plus approved improvement. Retain the established list/table content and CSS hooks, make existing output usable at 320 CSS px without page-level horizontal scrolling, and keep dates, venue/city/country, time, ticket and notes content available. Avoid needing the site owner to replace their theme. |
| CSV import/export continuity and actionable results | CSV is an established migration and maintenance path for existing databases | MEDIUM | P1. Preserve the GigPress header/schema, related-post option, artist/tour/date filters, and output intended for spreadsheet use. Report upload/parse failures and totals for inserted, skipped, and duplicate rows; identify rejected rows and reasons. Keep feedback understandable and escaped. [WordPress import guidance](https://developer.wordpress.org/advanced-administration/wordpress/import/), [input and output safety](https://developer.wordpress.org/plugins/wordpress-org/common-issues/) |
| Public template override compatibility | Themes already customize GigPress markup; changing the extension point can break existing sites without a plugin error | HIGH | P1. Preserve `gigpress_template()`'s child theme → parent theme → `wp-content` → bundled fallback order, template names, expected variables, and CSS opt-out/custom stylesheet behavior. Add responsive styling around the contract; do not rename templates or move output to a new renderer in this delivery. [Theme presentation responsibilities](https://developer.wordpress.org/themes/getting-started/what-is-a-theme/), [classic template precedence](https://developer.wordpress.org/themes/classic-themes/basics/template-hierarchy/) |
| Safe request and output handling | Admin mutations and imported text are untrusted input | HIGH | P1. Retain capability and nonce validation; validate dates, IDs, and allowed filter/sort values; escape at output for the HTML/attribute/URL context. CSV feedback should not echo raw file names or cells unsafely. [Nonces](https://developer.wordpress.org/apis/security/nonces/), [escaping data](https://developer.wordpress.org/apis/security/escaping/) |

### Approved UX Outcomes (Within Existing Scope)

These are the user's five explicit priorities, scoped to polishing existing screens and output. The measures are proposed release acceptance criteria, not claims about an audited live site.

| Area | Expected Outcome | Complexity | Measurable Outcome | Dependencies |
|------|------------------|------------|--------------------|--------------|
| Add-show date/time workflow | Make the existing date, optional time, and multi-day/expiration fields easier to understand and correct; keep defaults and submitted values after validation errors | MEDIUM | On add and edit, every date/time field has an associated label and clear required/optional status; invalid dates return a field-specific text error and preserve all entered values; keyboard-only completion and save work without JS | Compatibility-safe date validation and existing form data shape; accessible form baseline |
| Show-list filtering and bulk actions | Make current scope, artist, tour, venue, sort, and page-size filters clearer; keep selections/actions understandable | MEDIUM | Applying one filter preserves other selected filters and scope; current filter state is visible after submit and pagination; bulk trash requires an explicit selected-record action and reports its result; empty results explain active criteria and offer a clear reset path | Query/filter logic and pagination state; nonce/capability checks for actions; accessible table semantics |
| Settings organization and help | Group existing settings into clear sections and explain non-obvious controls in place | MEDIUM | Existing settings remain editable and saved with the same option keys/meanings; each section has a descriptive heading; date/time format fields link to current WordPress format guidance; help text is adjacent to its control; settings errors/success are announced using core conventions | Inventory of current options/defaults; Settings API or existing options.php flow without dropping hidden legacy settings |
| Responsive public show displays | Improve narrow-screen reading while keeping the current template override and CSS contracts | MEDIUM | Default public listings display all established show fields at 320 CSS px with no page-level horizontal overflow; date/location/ticket links remain operable; desktop layout and a representative child-theme override still render, with old class hooks and template variables intact | Preserve templates and CSS hooks; enqueue styles through WordPress; test bundled and override templates |
| Import/export layout and feedback | Separate the existing import and export tasks visually and make completion/failure outcomes scannable | LOW-MEDIUM | Import form communicates required file type and related-post option; each run reports total rows, inserted, duplicates, skipped/errors; skipped rows include a readable reason; export filters are labeled and result uses existing compatible CSV; empty/error/success states are distinguishable | Keep parser and file/CSV contract; upload validation and safe feedback; explicit test fixtures for successful, malformed, duplicate, and partial imports |

### Differentiators (Existing Product Value to Preserve)

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| One show manager for artists, venues, tours, and schedules | A focused data model makes maintaining a touring or venue schedule practical without splitting the workflow across unrelated post types | HIGH | Existing GigPress identity; preserve rather than expand. |
| Multiple publishing surfaces from shared show data | Shortcodes, widgets, related posts, RSS, and iCalendar let site owners publish the same schedule where their site and subscribers need it | HIGH | Existing capabilities. Include them in compatibility validation; don't add new channels in this release. |
| Theme-overridable public templates | Site owners can change markup while keeping show management in the plugin | HIGH | Retain the custom override resolver as an intentional extension contract, even though it is not WordPress's native template hierarchy. |
| CSV portability | Existing installations can move and edit show data with common spreadsheet workflows | MEDIUM | Preserve current compatible CSV import/export, improve presentation and feedback only. |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Replace the classic screens with a new block-editor/React application | Modern interface expectation | Adds a new app architecture and migration surface while the release goal is platform compatibility and focused workflow polish | Improve the existing classic WordPress admin pages using core UI patterns and accessible semantics |
| Replace custom GigPress templates or rename their files | Simplifies internal styling | Breaks existing child/parent theme and `wp-content/gigpress-templates` overrides | Keep paths, names, variables, and CSS hooks stable; adapt bundled templates and CSS compatibly |
| New event/ticketing product features (checkout, recurring series, venues map, RSVP, reminders, booking, external sync) | Common in larger event platforms | Not requested and would change the product, data model, integrations, or operational burden | Defer; deliver compatibility and the five approved existing-workflow improvements |
| Import preview/mapping wizard or arbitrary CSV field mapping | Can accommodate unrelated CSV schemas | Expands importer complexity beyond existing GigPress CSV portability and risks ambiguous row mapping | Keep the documented/current CSV schema and improve validation, counts, and row-level error feedback |
| Redesign every admin page or adopt a settings framework migration wholesale | Offers visual consistency | Broad rewrites create regression risk unrelated to the five priorities | Organize only the existing settings and show workflows; use core APIs where they reduce risk |

## Feature Dependencies

```text
Compatibility baseline and data preservation
    ├──requires──> WordPress/PHP 7.0+/8.3+ request and hook correctness
    ├──requires──> Existing option keys, DB schema, and public signatures retained
    └──requires──> Existing CSV and theme override contracts retained

Accessible admin baseline
    ├──enhances──> Add-show date/time workflow
    ├──enhances──> Show-list filtering and bulk actions
    └──enhances──> Settings organization and help

Filter and pagination state
    └──requires──> Show-list filtering and bulk actions

Template resolver and CSS hooks
    └──requires──> Responsive public show displays

CSV format and parser behavior
    └──requires──> Import/export layout and feedback
```

### Dependency Notes

- **Compatibility baseline before UX:** First establish that core hooks/menu registration and PHP execution work at the chosen minima. Then improve workflows so usability changes do not mask platform failures.
- **Preserve configuration and storage:** Settings reorganization depends on retaining the existing `gigpress_settings` keys and any hidden fields not represented in the form. No schema migration is needed to reorganize help or labels.
- **Accessible semantics support admin UX:** Form labels, field-specific errors, and table semantics are shared prerequisites; implement them alongside the relevant screens rather than introducing a separate redesign phase.
- **Responsive output depends on extension stability:** Validate default styling and representative theme override files before changing CSS assumptions. The plugin template resolver and WordPress's own theme template hierarchy are distinct mechanisms; keep GigPress's resolver contract.
- **Importer feedback depends on stable row outcomes:** Separate parsed, inserted, duplicate, and rejected results before presenting counts; keep the current parser/schema and avoid treating a partial import as an all-or-nothing success.

## MVP Definition

For this compatibility delivery, “v1” means the release candidate after the compatibility gate and the approved improvements are complete.

### Launch With (v1)

- [ ] WordPress 7.0+ and PHP 8.3+ minimum support declared and verified — compatibility leads because every existing workflow depends on it.
- [ ] Existing records, options, CRUD, public shortcodes/widgets, feeds, and CSV contract preserved — required for safe upgrades.
- [ ] Existing template override order, names, variables, and CSS opt-out/custom stylesheet behavior preserved — established customization contract.
- [ ] Add-show date/time workflow improvement — requested priority, with validation and entered values preserved on errors.
- [ ] Show list filters/bulk action improvement — requested priority, with filter state and action results clear.
- [ ] Settings grouped with contextual help — requested priority, with existing settings keys retained.
- [ ] Responsive default show display — requested priority, tested alongside a representative override.
- [ ] Import/export layout and feedback improvements — requested priority, with row counts/reasons and existing CSV compatibility.
- [ ] Accessible labels, text error feedback, keyboard operation, and semantic tables on touched admin screens — part of compatibility-quality workflow behavior.

### Add After Validation (v1.x)

- [ ] Address additional usability findings only if the compatibility rollout or user feedback identifies a concrete issue within these same five areas.
- [ ] Add optional broader browser/theme coverage when support evidence warrants it; first validate the core supported WordPress/PHP matrix and the override contract.

### Future Consideration (v2+)

- [ ] New event operations, ticketing, booking, reminders, integrations, or arbitrary external CSV mapping — defer because they expand product scope and data/integration requirements.
- [ ] A block-editor-native management application or wholesale admin redesign — defer until separately requested and justified; unnecessary for this compatibility delivery.

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| WordPress/PHP compatibility and data/workflow preservation | HIGH | HIGH | P1 |
| Theme override and public output compatibility | HIGH | MEDIUM | P1 |
| Secure and accessible classic admin baseline | HIGH | MEDIUM | P1 |
| Add-show date/time workflow | HIGH | MEDIUM | P1 after compatibility baseline |
| Show-list filters and bulk actions | HIGH | MEDIUM | P1 after compatibility baseline |
| Settings organization/help | MEDIUM | MEDIUM | P1 after compatibility baseline |
| Responsive public listings | HIGH | MEDIUM | P1 after compatibility baseline |
| Import/export layout and feedback | MEDIUM | LOW-MEDIUM | P1 after compatibility baseline |
| New product capabilities beyond the five selected areas | LOW for this release | HIGH | P3 / deferred |

**Priority key:**
- P1: Required for this delivery
- P2: Useful but can follow delivery if time or validation evidence requires it
- P3: Deferred future consideration

## Competitor Feature Analysis

This research is scoped to an existing product's approved update, not a market comparison. No competitor audit was requested or used; avoid turning competitor parity into new requirements. The relevant “Our Approach” is therefore to preserve the existing GigPress capabilities and focus this release on compatibility plus the five explicitly approved UX areas.

| Feature | Existing GigPress baseline | This delivery |
|---------|----------------------------|---------------|
| Show management | Add/edit/copy/list/trash shows and link artists, venues, tours | Preserve records and behavior; improve date/time entry and show-list task clarity |
| Admin settings | Single classic settings screen with date, display, feed, related-post, and advanced options | Group current controls and make help contextual without dropping existing options |
| Public schedule | Existing shortcode/widget templates and CSS; supports theme override resolution | Improve default narrow layouts while preserving override contract |
| CSV portability | Existing GigPress CSV import/export with filters, duplicate/error/success feedback | Preserve format and row decisions; improve layout, summary, and actionable feedback |
| Alternate outputs | Existing RSS and iCalendar feeds | Preserve URLs/behavior; no new output formats |

## Sources

- [WordPress Plugin Handbook: Sub-Menus](https://developer.wordpress.org/plugins/administration-menus/sub-menus/) — classic admin pages, `.wrap`, capability checks, form security.
- [WordPress Plugin Handbook: Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) — sections/fields, core-consistent presentation, capability checking, nonces, sanitization, error reporting.
- [WordPress Accessibility: Site Feedback Guide](https://make.wordpress.org/accessibility/handbook/get-involved/site-feedback-guide/) — labeled controls and actionable error identification.
- [WordPress Documentation Style Guide: Tables](https://make.wordpress.org/docs/style-guide/formatting/tables/) — distinguish headings and use `th`/`scope` for accessible tables.
- [WordPress Advanced Administration Handbook: Importing Content](https://developer.wordpress.org/advanced-administration/wordpress/import/) — established upload/import interaction pattern.
- [WordPress Plugin Review Team: Common Issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/) — sanitize, validate, and escape request and output data.
- [WordPress Common APIs: Nonces](https://developer.wordpress.org/apis/security/nonces/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/) — secure actions and late context-appropriate output escaping.
- [WordPress Theme Handbook: What Is a Theme?](https://developer.wordpress.org/themes/getting-started/what-is-a-theme/) and [Template Hierarchy](https://developer.wordpress.org/themes/classic-themes/basics/template-hierarchy/) — theme presentation responsibility and native child-theme precedence. GigPress's custom resolver is verified directly in this repository, not supplied by WordPress core.
- Project evidence: `.planning/PROJECT.md`, `.planning/codebase/ARCHITECTURE.md`, `.planning/codebase/STRUCTURE.md`, `admin/new.php`, `admin/shows.php`, `admin/settings.php`, `admin/import-export.php`, `admin/handlers.php`, `gigpress.php`, and `templates/` (reviewed 2026-10-03).

---
*Feature research for: GigPress compatibility update*
*Researched: 2026-10-03*

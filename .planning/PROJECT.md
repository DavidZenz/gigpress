# GigPress

## What This Is

GigPress is a WordPress plugin for managing artists, venues, tours, and shows, then publishing show information on a site. It includes administration workflows, public show displays, RSS and iCalendar feeds, and CSV import/export.

## Core Value

Site owners can manage show information and reliably publish it on their WordPress sites.

## Requirements

### Validated

- ✓ Manage shows, artists, venues, and tours through WordPress administration — established product capability
- ✓ Publish show listings and show details in WordPress content — established product capability
- ✓ Provide RSS and iCalendar feeds and CSV import/export — established product capability

### Active

- [ ] Support WordPress 7.0 and later, including the 7.1 release line, with a clearly stated minimum.
- [ ] Support PHP 8.3 and later, with a clearly stated minimum.
- [ ] Investigate and fix the reported GigPress admin-menu warning on WordPress 7.1.2; the report came from PHP 8.2, which is diagnostic context and not a supported target.
- [ ] Preserve existing GigPress data and established administration, display, feed, and import/export behavior while updating compatibility.
- [ ] Identify and deliver useful improvements to the add-show date/time workflow, show-list filtering and bulk actions, settings organization and help, responsive public show displays, and import/export layout and feedback.

### Out of Scope

- Treating PHP 8.2 as a supported target — the user selected PHP 8.3+ as the minimum.
- Replacing GigPress's show-management purpose or removing its established data and publishing workflows — compatibility work should preserve them.
- Rebuilding public templates in a way that breaks existing theme overrides — current template customization is an established extension point.

## Context

- This is a brownfield WordPress plugin implemented in procedural PHP. The codebase map is in `.planning/codebase/`.
- The existing plugin manages shows, artists, venues, and tours; supports public listings and feeds; and provides CSV import/export.
- The user reported this warning on WordPress 7.1.2 with PHP 8.2: `Warning: Undefined array key "separator-gigpress" in wp-admin/includes/menu.php on line 339`. The warning is actual; its cause has not yet been confirmed. PHP 8.2 is not part of the support target.
- UX opportunities were identified by reading the existing source. No live-site or visual audit has been performed.
- The repository currently has no automated test suite, so compatibility verification will need an explicit validation approach during planning.

## Constraints

- **Compatibility**: WordPress 7.0+ and PHP 8.3+ are the minimum support targets — explicitly selected by the user.
- **Compatibility policy**: Keep the minimums clear and track currently supported upstream releases — the user requested up-to-date compatibility.
- **Data integrity**: Existing show and related records must remain usable through the upgrade — the plugin already manages site content and user data.
- **Extension compatibility**: Preserve theme template overrides and existing public display customization where practical — these are established plugin behaviors.
- **Scope**: Compatibility is the first priority; the five identified UX areas are included as secondary improvements in this first delivery — selected by the user.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Set the minimum PHP version to 8.3. | User selected PHP 8.3+ as the support baseline. | — Pending |
| Set the minimum WordPress version to 7.0. | User selected WordPress 7.0+ and 7.1+. | — Pending |
| Treat the WordPress 7.1.2 / PHP 8.2 warning as an investigation clue, not a PHP 8.2 support commitment. | User clarified that PHP 8.2 is not a target. | — Pending |
| Include improvement work in all five identified UX areas, behind compatibility. | User selected priorities 1–5. | — Pending |

## Evolution

After each phase, move completed and confirmed requirements from Active to Validated, move rejected requirements to Out of Scope with a reason, record new requirements and decisions, and update this project description if the product changes. Review all sections at each milestone.

---
*Last updated: 2026-10-03 after project discovery and compatibility-scope approval.*

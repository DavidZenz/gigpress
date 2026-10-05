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
- ✓ Support WordPress 7.0 and the 7.1 release line with a stated minimum — Phase 1
- ✓ Support PHP 8.3 and later with a stated minimum; PHP 8.2 remains diagnostic-only — Phase 1
- ✓ Reproduce and trace the reported admin-menu warning with a controlled fixture, fix GigPress's equivalent menu-order conflict, and leave the unavailable live callback unclaimed — Phase 1
- ✓ Preserve records, IDs, relationships, settings, and linked content through recognized database upgrades, interruption/retry, and repeat loads — Phase 2, reconstructed fixtures
- ✓ Preserve post-upgrade show create/edit/copy/trash/restore, dependency-safe entity deletion, and ownership-safe tour undo — Phase 2
- ✓ Clear show date/time entry and recoverable validation, retained show-list filtering and selected-only confirmation, and grouped settings preserving stored values — Phase 3, automated evidence plus eight user-reported UAT passes
- ✓ Changed Phase 3 controls support associated labels, keyboard operation and readable feedback — Phase 3 human acceptance

### Active

- [ ] Preserve established public display, feed, theme-override, and import/export contracts while improving publishing and CSV workflows.
- [ ] Deliver responsive public show displays and clearer import/export layout and feedback — remaining Phases 4 and 5.

### Out of Scope

- Treating PHP 8.2 as a supported target — the user selected PHP 8.3+ as the minimum.
- Replacing GigPress's show-management purpose or removing its established data and publishing workflows — compatibility work should preserve them.
- Rebuilding public templates in a way that breaks existing theme overrides — current template customization is an established extension point.

## Context

- This is a brownfield WordPress plugin implemented in procedural PHP. The codebase map is in `.planning/codebase/`.
- The existing plugin manages shows, artists, venues, and tours; supports public listings and feeds; and provides CSV import/export.
- The user reported this warning on WordPress 7.1.2 with PHP 8.2: `Warning: Undefined array key "separator-gigpress" in wp-admin/includes/menu.php on line 339`. A controlled fixture now reproduces the exact core warning and identifies its fixture callback; the unavailable live site's callback remains unproven. PHP 8.2 is not part of the support target.
- UX opportunities were identified by reading the existing source. The preferred and conflict-order admin menus were visually checked in disposable WordPress 7.1.2/PHP 8.3 cells; no live-site UI audit was performed.
- A repository-owned OrbStack/Compose compatibility harness now covers supported PHP syntax, WordPress activation, menu ordering, data-preserving lifecycle transitions, and existing admin/public/feed/CSV workflows.
- Phase 2 verifies populated reconstructed database versions 1.0–1.6 and post-upgrade CRUD across the supported matrix. No live backup was tested; public/CSV workflow cells use fresh databases, with migrated-fixture publishing and CSV integration assigned to Phases 4 and 5.

- Phase 3 has 6384 passing administration assertions across six pinned supported cells, 54 authenticated HTTP checks and 39 PHP syntax checks. Eight user-reported passing UAT checkpoints close manual acceptance; the earlier browser record retains its actual observation limits.
- Three inherited CSV/conversion review defects remain required Phase 5 fixes in `.planning/CSV-REVIEW-FOLLOWUPS.md`; Phase 3 acceptance does not establish plugin-wide safety.

## Constraints

- **Compatibility**: WordPress 7.0+ and PHP 8.3+ are the minimum support targets — explicitly selected by the user.
- **Compatibility policy**: Keep the minimums clear and track currently supported upstream releases — the user requested up-to-date compatibility.
- **Data integrity**: Existing show and related records must remain usable through the upgrade — the plugin already manages site content and user data.
- **Extension compatibility**: Preserve theme template overrides and existing public display customization where practical — these are established plugin behaviors.
- **Scope**: Compatibility is the first priority; the five identified UX areas are included as secondary improvements in this first delivery — selected by the user.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Set the minimum PHP version to 8.3. | User selected PHP 8.3+ as the support baseline. | Implemented and verified in Phase 1; PHP 8.2 remains diagnostic-only. |
| Set the minimum WordPress version to 7.0. | User selected WordPress 7.0+ and 7.1+. | Implemented and verified in Phase 1 on WordPress 7.0.6 and 7.1.2. |
| Treat the WordPress 7.1.2 / PHP 8.2 warning as an investigation clue, not a PHP 8.2 support commitment. | User clarified that PHP 8.2 is not a target. | Exact warning reproduced in a controlled fixture; the live callback identity is unproven. |
| Include improvement work in all five identified UX areas, behind compatibility. | User selected priorities 1–5. | Sequenced after compatibility; data preservation is Phase 2, administration Phase 3, publishing Phase 4, and CSV Phase 5. |
| Verify and journal each upgrade step, then write and read back the final completion marker. | Interrupted upgrades must resume without duplicating records or changing saved settings intent. | Verified across recognized source versions in Phase 2. |
| Block unsafe metadata and gate all admin mutations on database readiness. | An unproven upgrade state must not accept writes or CSV upload side effects. | Verified bootstrap and handler guards in Phase 2. |
| Count active and trashed dependencies before entity deletion and track tour undo ownership per show. | Deletion and undo must preserve relationships and intervening reassignment. | Verified against upgraded fixtures in Phase 2. |
| Resolve upstream runtime targets and pin one WordPress patch per line for each matrix run. | Compatibility evidence must test consistent versions across PHP branches. | Six supported runtime cells pass in Phase 2; exact versions and image IDs are recorded. |

| Keep raw received correction values separate from normalized writes and reuse completed related-record IDs on retry. | Failed saves must remain correctable without duplicate related records. | Implemented and accepted in Phase 3. |
| Bind trash confirmation to the owner and exact selected IDs, then report verified per-ID outcomes. | Filtering must not expand selection; Cancel must change nothing. | Implemented and accepted in Phase 3. |
| Keep six settings groups immediately visible and preserve protected, unknown, nested and falsey stored values. | Clear settings navigation must retain existing site configuration and semantics. | Implemented and accepted in Phase 3. |

## Evolution

After each phase, move completed and confirmed requirements from Active to Validated, move rejected requirements to Out of Scope with a reason, record new requirements and decisions, and update this project description if the product changes. Review all sections at each milestone.

---
*Last updated: 2026-10-05 after Phase 3 administration verification and human acceptance.*

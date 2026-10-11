---
gsd_state_version: "1.0"
current_phase: 05
current_phase_name: CSV Import/Export
status: executing
stopped_at: Completed 05-02-PLAN.md
last_updated: "2026-10-11T11:32:55.679Z"
last_activity: 2026-10-11
last_activity_desc: Phase 05 execution started
state_head: 4a7e490f6284ecc68d8c10fb405fd200b6a944f9
progress:
  total_phases: 5
  completed_phases: 4
  total_plans: 23
  completed_plans: 20
  percent: 80
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-10-11)

**Core value:** Site owners can manage show information and reliably publish it on their WordPress sites.
**Current focus:** Phase 05 — CSV Import/Export

## Current Position

Phase: 05 (CSV Import/Export) — EXECUTING
Plan: 3 of 5
Status: Ready to execute next Phase 05 plan
Last activity: 2026-10-11 — Phase 05 execution started

Progress: [████████░░] 80%

Planned work: 19/23 plans complete (80% progress bar); 05-01 is complete and the next Phase 05 plan has not started.

## Performance Metrics

**Velocity:**
- Total plans completed: 18
- Average duration: -
- Total execution time: -

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01 | 5 | - | - |
| 02 | 4 | - | - |
| 03 | 4 | - | - |
| 04 | 5 | - | - |

**Recent Trend:**
- Last 5 plans: none
- Trend: No data

*Updated after each plan completion*
**Per-Plan Metrics:**

| Plan | Duration | Tasks | Files |
|------|----------|-------|-------|
| Phase 01 P01 | 36min | 3 tasks | 9 files |
| Phase 01 P02 | 8min | 2 tasks | 5 files |
| Phase 01 P03 | 14min | 2 tasks | 4 files |
| Phase 01 P04 | 48min | 2 tasks | 6 files |
| Phase 01 P05 | 90m | 2 tasks | 7 files |
| Phase 02-data-and-upgrade-preservation P01 | 12min | 2 tasks | 7 files |
| Phase 02-data-and-upgrade-preservation P02 | 33m | 3 tasks | 11 files |
| Phase 02-data-and-upgrade-preservation P03 | 8m | 3 tasks | 6 files |
| Phase 02 P04 | 25m | 2 tasks | 4 files |
| Phase 03 P01 | 21min | 3 tasks | 8 files |
| Phase 03 P02 | 12min | 2 tasks | 4 files |
| Phase 03 P03 | 16min | 3 tasks | 4 files |
| Phase 03 P04 | 535min | 3 tasks | 11 files |
| Phase 04 P01 | 20min | 2 tasks | 8 files |
| Phase 04 P02 | 14min | 2 tasks | 7 files |
| Phase 04 P03 | 23min | 3 tasks | 11 files |
| Phase 04-public-publishing P04 | 36min | 3 tasks | 10 files |
| Phase 04 P05 | 1273min | 3 tasks | 14 files |
| Phase 05 P01 | 23min | 2 tasks | 7 files |
| Phase 05 P02 | 23min | 2 tasks | 8 files |

## Accumulated Context

### Decisions

Recent decisions affecting current work:

- Minimum support is WordPress 7.0 and PHP 8.3; PHP 8.2 is diagnostic-only.
- Below PHP 8.3, an already-active GigPress remains active but inert with a scoped notice, and resumes normally when PHP 8.3+ returns.
- The reported menu warning is reproduced only by a controlled fixture; the unavailable live callback remains unproven.
- GigPress registers its separator during `admin_menu` and returns standard order unchanged on conflicts.
- OrbStack/Compose supplies the WordPress/PHP matrix; WordPress 7.0.6 uses the official release archive, and `Tested up to: 7.1` is backed by the PHP 8.3–8.5 workflow matrix.
- [Phase 02-data-and-upgrade-preservation]: Use a durable journal and final-marker read-back for upgrades.
- [Phase 02-data-and-upgrade-preservation]: Block unsafe metadata rather than guessing or reinstalling existing data.
- [Phase 02-data-and-upgrade-preservation]: Journal transformed settings after each successful legacy step so retries resume with source-specific semantics.
- [Phase 02-data-and-upgrade-preservation]: Reuse exact artist and venue identity matches after interruption; reject ambiguous generated mappings.
- [Phase 02-data-and-upgrade-preservation]: Keep current 1.6 as a populated no-op fixture with every documented default present.
- [Phase 02-data-and-upgrade-preservation]: Run coordinator readiness checks after nonce verification before each covered mutation.
- [Phase 02-data-and-upgrade-preservation]: Count all show statuses for entity deletion while retaining active-only list counts.
- [Phase 02-data-and-upgrade-preservation]: Use gigpress_tour_restore_map for exact pending undo ownership and retain the row marker only as a compatibility hint.
- [Phase 02]: Run the aggregate registry in one disposable container cell while each required case receives a reset request state and fresh plugin activation.
- [Phase 02]: Treat WordPress 7.0.6 and 7.1.2 with PHP 8.3, 8.4, and 8.5 as the supported evidence set; PHP 8.2 remains diagnostic-only.
- [Phase 02]: Store a compact machine-readable evidence record inside the human-readable matrix report and validate it without rerunning containers.
- [Phase 03]: Keep raw correction text separate from normalized show fields; replacement dates require an explicit checkbox.
- [Phase 03]: Carry completed related-creation IDs into recovered selections and retry bookkeeping so rejected retries do not duplicate entries.
- [Phase 03]: Keep the eight-case administration registry fail-closed until each later-plan module supplies nonempty real assertions.
- [Phase 03]: Protect stored metadata and unknown keys during settings form saves, while trusted programmatic partial updates merge into storage.
- [Phase 03]: Represent uncommon current choices explicitly and preserve exact falsey checkbox types on unchanged settings saves.
- [Phase 03]: Persist only scope and integer page size; entity filters, sort and page position remain request state.
- [Phase 03]: Bind trash to the current owner, exact deduplicated ordered IDs and stored return state, consuming the expiring intent before writes.
- [Phase 03]: Count only strict verified status transitions and scope Undo to changed IDs; recount and clamp return links after writes.

- [Phase 03]: Eight explicit user UAT passes close manual acceptance; source-equivalent automated evidence remains separate from human confirmations.
- [Phase 04]: Keep persisted show_id and existing relationships as the identity anchor across public destinations.
- [Phase 04]: Keep supplemental public-only values separate from canonical migration evidence.
- [Phase 04]: Expect 1.1 migrated RSS order [113, 111] because its historical schema predates show_status; keep linked post and selected iCalendar checks tied to show 111.
- [Phase 04]: Only the complete bundled start/body/end set receives automatic responsive styling; owner templates require explicit opt-in.
- [Phase 04]: Keep the wide table and existing show, grouping, status, ticket, calendar, subscription, and theme-width contracts.
- [Phase 04]: Keep final browser measurement and calendar-client acceptance in plan 04-05.
- [Phase 04]: Keep legacy fragment keys and owner-template variables while adding plain prepared values for machine output.
- [Phase 04]: Escape configured table labels and grouped artist headings at the output boundary after hostile fixture assertions exposed raw paths.
- [Phase 04-public-publishing]: Preserve the feed endpoints, filter membership, event order, item identities, and UID construction while serializing from plain values.
- [Phase 04-public-publishing]: Treat browser and calendar-client acceptance as a separate blocking gate for Plan 04-05; HTTP evidence does not set phase acceptance or Nyquist compliance.
- [Phase 04-public-publishing]: Normalize equivalent HTML entities in subscription URL assertions before comparing saved destinations.
- [Phase 04]: All 22 UAT rows pass; explicit acceptance of the static privacy assessment closes the sole human verification decision on 2026-10-11.
- [Phase 04]: Canonical verification passes against unchanged implementation source; browser/client evidence remains separate from automated checks.

Phase 05 planning decisions:

- D-17 preserves exact CSV values with explicit spreadsheet text-import guidance; direct-open formula risk is accepted, with no silent transformation or formula-based download block.
- Parallel Wave 3 contains export/UI (05-03) and tour conversion (05-04) after shared import/harness seams are complete.
- Static plan verification passed after one revision; actual CSV, migrated-data, runtime, browser and client evidence is still required during execution.
- [Phase 05]: Preserve exact CSV values, including formula-like, Unicode, and leading-plus values; provide spreadsheet text-import guidance per D-17.
- [Phase 05]: Keep existing status 0 unchanged and identify it for manual correction per D-13.
- [Phase 05]: Preserve exact decoded CSV values and provide spreadsheet text-import guidance per D-17.
- [Phase 05]: Use actual authenticated multipart transport for upload-origin evidence; keep persistence fault injection in trusted in-process tests.

### Pending Todos

None yet.

### Blockers/Concerns

- [Phase 01] The unavailable live site's callback identity for the reported warning remains unproven; the controlled fixture is not evidence of the live actor.
- [Phase 05] Verify CSV import/export edge cases and mutation protection with representative fixtures; close required CR-04 unsafe notices, CR-05 conversion write-loss and CR-07 status-format defects from CSV-REVIEW-FOLLOWUPS.md.
- [Phase 04 advisory] Public UI review remains 17/24: grouped subscription names/targets/focus and desktop cancelled-text contrast need improvement. These are documented quality findings, not PUB-01/PUB-02 completion blockers.

## Deferred Items

| Category | Item | Status | Deferred At | Milestone |
|----------|------|--------|-------------|-----------|
| Verification | Exercise public displays, theme overrides, RSS, and iCalendar on populated migrated Phase 02 fixtures. | Completed Phase 04, 2026-10-11 | Phase 02 | Current |
| Verification | Exercise CSV import/export contracts and round trips on populated migrated Phase 02 fixtures. | Pending Phase 05 | Phase 02 | Current |

## Session Continuity

Last session: 2026-10-11T11:32:55.642Z
Stopped at: Completed 05-02-PLAN.md
Resume file: None

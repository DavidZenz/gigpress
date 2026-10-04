---
gsd_state_version: "1.0"
current_phase: 02
current_phase_name: Data and Upgrade Preservation
status: verifying
stopped_at: Completed 02-04-PLAN.md
last_updated: "2026-10-04T14:37:29.455Z"
last_activity: 2026-10-04
last_activity_desc: Phase 02 execution started
state_head: 3ee96f8311ad5f758c450b77047ad0edc1f1680e
progress:
  total_phases: 5
  completed_phases: 1
  total_plans: 9
  completed_plans: 9
  percent: 20
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-10-04)

**Core value:** Site owners can manage show information and reliably publish it on their WordPress sites.
**Current focus:** Phase 02 — Data and Upgrade Preservation

## Current Position

Phase: 02 (Data and Upgrade Preservation) — EXECUTING
Plan: 4 of 4
Status: Phase complete — ready for verification
Last activity: 2026-10-04 — Phase 02 execution started

Progress: ░░░░░░░░░░ [██░░░░░░░░] 20%

## Performance Metrics

**Velocity:**
- Total plans completed: 5
- Average duration: -
- Total execution time: -

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01 | 5 | - | - |

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

### Pending Todos

None yet.

### Blockers/Concerns

- [Phase 01] The unavailable live site's callback identity for the reported warning remains unproven; the controlled fixture is not evidence of the live actor.
- [Phase 02] Characterize historical schema and upgrade behavior before migration-adjacent changes.
- [Phase 04] Verify template overrides and feed contracts with representative fixtures.
- [Phase 05] Verify CSV import/export edge cases and mutation protection with representative fixtures.

## Deferred Items

| Category | Item | Status | Deferred At | Milestone |
|----------|------|--------|-------------|-----------|
| *(none)* | | | | |

## Session Continuity

Last session: 2026-10-04T14:37:29.431Z
Stopped at: Completed 02-04-PLAN.md
Resume file: None

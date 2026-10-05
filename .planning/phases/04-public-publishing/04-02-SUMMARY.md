---
phase: 04-public-publishing
plan: 02
subsystem: ui
tags: [wordpress, public-listings, responsive-css, template-overrides, compatibility]

# Dependency graph
requires:
  - phase: 04-public-publishing
    provides: Migrated public fixture registry and labelled per-show calendar actions from plan 04-01.
provides:
  - Bundled-only responsive show presentation with real narrow-layout labels and all details visible.
  - Complete and mixed override-priority evidence for child, parent, wp-content, and bundled partials.
  - Explicit owner adoption guidance for the gigpress-layout-bundled marker.
affects: [04-03, 04-04, 04-05, public-templates, theme-adoption]

# Actuals (#2632) — chars/4 over the realized implementation diff.
actuals:
  tokens: 10332
  tasks: 2
  commits: 2
commits: 2
plan_head_before: 0298707b5859f7fd551cff3c236e75b92db28636
plan_head_after: f8bb04a8a1d83cfb6c6f9dfe7819a0d5ae111470

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Resolve all main structural partials before emitting a bundled-only responsive marker.
    - Scope legacy calendar-toggle CSS to non-bundled tables while keeping new actions as ordinary links.

key-files:
  created:
    - tests/compat/public-layout.php
    - tests/compat/fixtures/public-publishing/overrides.php
    - docs/public-template-adoption.md
  modified:
    - output/gigpress_shows.php
    - templates/shows-list-start.php
    - templates/shows-list.php
    - css/gigpress.css

key-decisions:
  - "Only the complete bundled start/body/end set receives automatic responsive opt-in; owner templates can explicitly adopt the marker."
  - "Keep the wide table and existing show, grouping, status, ticket, calendar, subscription, and theme-width contracts."
  - "Keep final 320 CSS-pixel browser measurement and calendar-client acceptance in plan 04-05."

patterns-established:
  - "Owner partials are independently resolved and tested at each child, parent, wp-content, and bundled priority level."
  - "Real markup labels accompany narrow-layout CSS rather than generated content."

requirements-completed: [PUB-01, PUB-02]

# Coverage metadata
coverage:
  - id: D1
    description: "The bundled main listing retains its wide table and renders labelled, date-first narrow show blocks with visible details and actions."
    requirement: PUB-02
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case layout-main"
        status: pass
    human_judgment: true
    rationale: "The compatibility cell verifies markup and source contracts; actual 320 CSS-pixel bounds and visual readability remain for plan 04-05 browser acceptance."
  - id: D2
    description: "Compact subscriptions, widgets, and related-show output preserve existing filters, endpoints, and theme inheritance."
    requirement: PUB-01
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case layout-compact"
        status: pass
    human_judgment: false
  - id: D3
    description: "Complete and mixed owner overrides retain exact priority and remain outside automatic bundled responsive styling, with an explicit adoption path."
    requirement: PUB-02
    verification:
      - kind: e2e
        ref: "rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case override-priority"
        status: pass
    human_judgment: false
  - id: D4
    description: "Theme owners have exact filenames, variables, hooks, marker, and mixed-override guidance for adopting bundled responsive styles."
    verification:
      - kind: other
        ref: "docs/public-template-adoption.md"
        status: pass
    human_judgment: true
    rationale: "A theme owner should review the guidance against their own custom partial structure before opting in."

# Metrics
duration: 14min
completed: 2026-10-05
status: complete
---

# Phase 04 Plan 02: Bundled Responsive Presentation Summary

**Bundled show listings now keep the wide table while offering labelled narrow blocks, compact public surfaces, and tested owner-controlled override adoption.**

## Performance

- **Duration:** 14 min
- **Started:** 2026-10-05T10:04:45Z
- **Completed:** 2026-10-05T10:18:41Z
- **Tasks:** 2
- **Files modified:** 7

## Accomplishments

- Added the `gigpress-layout-bundled` opt-in only when the resolved start, body, and end partials are all bundled. The bundled main table retains its wide presentation and becomes labelled, wrapping show blocks at narrow widths.
- Kept the date range, optional time semantics, status badges and details, saved ticket destination/label, and direct Google Calendar and iCalendar actions visible in the main listing.
- Kept subscriptions configurable and wrapping, improved compact widget/related wrapping, and scoped legacy calendar-toggle styles to non-bundled tables.
- Added fail-closed public layout assertions and an override fixture that checks child, parent, wp-content, and bundled priority; complete sets; mixed partial ownership; caller variables and hooks; explicit opt-in; and optional asset contracts.
- Documented exact owner filenames, variables, hooks, marker use, and stylesheet behavior in `docs/public-template-adoption.md`.

## Task Commits

Each task was committed atomically:

1. **Task 04-02-01: Render one complete bundled show as a readable narrow block and wide table** - `b6b4645` (`feat`)
2. **Task 04-02-02: Preserve complete and mixed override priority and document explicit adoption** - `f8bb04a` (`feat`)

**Plan metadata:** Task commits are measured above; this summary, STATE.md, and ROADMAP.md are included in the final documentation commit.

## Files Created/Modified

- `output/gigpress_shows.php` - Resolves start/body/end paths before deciding automatic bundled style ownership.
- `templates/shows-list-start.php` - Adds the bundled ownership class only when the full structural set is bundled.
- `templates/shows-list.php` - Adds real narrow labels and stable show ID attributes while retaining show details and hooks.
- `css/gigpress.css` - Adds bundled narrow presentation, compact-surface wrapping, status emphasis, and owner-only legacy toggle scoping.
- `tests/compat/public-layout.php` - Verifies main, compact, and override contracts with independent named assertions.
- `tests/compat/fixtures/public-publishing/overrides.php` - Supplies isolated template variants for the resolver and mixed-ownership ladder.
- `docs/public-template-adoption.md` - Explains exact opt-in and custom stylesheet behavior.

## Verification

- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case layout-main` — PASS; 11 named checks, no warnings or plugin errors.
- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case layout-compact` — PASS; 7 named checks, no warnings or plugin errors.
- `rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario public-publishing --case override-priority` — PASS; 11 named checks, no warnings or plugin errors.
- `rtk proxy git diff --check` — clean before both task commits.

## Decisions Made

- Only a complete bundled structural set receives automatic responsive styling; owners explicitly adopt the marker after reviewing their markup.
- Keep visual 320 CSS-pixel measurement, keyboard acceptance, and calendar-client verification for plan 04-05 after all source changes.

## Deviations from Plan

None - plan executed as written.

## Issues Encountered

- The host has no PHP CLI. The required WordPress/PHP compatibility cells ran the PHP 8.3.35 runtime in disposable containers and passed.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plan 04-02 is complete and ready for plan 04-03.
- The final real-browser viewport, keyboard, theme inheritance, and calendar-client acceptance remains assigned to plan 04-05.

---
*Phase: 04-public-publishing*
*Completed: 2026-10-05*

## Self-Check: PASSED

- SUMMARY.md exists at the requested phase path.
- Task commit `b6b4645` exists in Git history.
- Task commit `f8bb04a` exists in Git history.

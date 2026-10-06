---
phase: 04-public-publishing
plan: 05
subsystem: public-publishing
tags: [wordpress, php83, responsive-layout, rss, icalendar, browser-uat, docker]

# Dependency graph
requires:
  - phase: 04-public-publishing
    provides: Migrated public fixtures, layout and override contracts, destinations, and the nine-case feed matrix from plans 04-01 through 04-04.
provides:
  - Final-source retained public fixture lifecycle and browser acceptance procedure.
  - Source-bound six-cell public matrix plus approved final-source browser and calendar-client evidence.
  - Final unchanged-source fixture recheck, verified cleanup, and completed Phase 04 validation.
affects: [public-publishing, csv-import-export, wordpress-compatibility]

# Actuals (#2632): changed source/evidence diff chars divided by four.
actuals:
  tokens: 96499
  tasks: 3
  commits: 14
commits: 14
plan_head_before: 14c9cc561a920213cb747ef60c49830248c921bd
plan_head_after: 7e66b3e

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Keep automated matrix evidence separate from browser and calendar-client approval.
    - Bind final browser evidence and fixture checks to the same production-source fingerprint.
    - Remove owned loopback fixture resources only after final-source recheck and human approval.

key-files:
  created:
    - tests/compat/PUBLIC-BROWSER.md
    - .planning/phases/04-public-publishing/04-BROWSER.md
  modified:
    - tests/compat/run.sh
    - tests/compat/browser-bootstrap.php
    - tests/compat/compose.browser.yaml
    - tests/compat/public-layout.php
    - css/gigpress.css
    - .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md
    - .planning/phases/04-public-publishing/04-VALIDATION.md

key-decisions: []
requirements-completed: [PUB-01, PUB-02]

# Coverage metadata
coverage:
  - id: D1
    description: "All nine public contracts pass on WordPress 7.0.6 and 7.1.2 with PHP 8.3, 8.4, and 8.5; the source-bound evidence report validates and rejects its 22 named corruptions."
    requirement: PUB-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh matrix --scenario public-publishing --case all; 04-PUBLIC-MATRIX.md"
        status: pass
      - kind: integration
        ref: "tests/compat/run.sh public-evidence --action validate and --action self-test"
        status: pass
    human_judgment: false
  - id: D2
    description: "The final-source listing, compact surfaces, theme inheritance, and complete/mixed template overrides remain readable and owner-controlled."
    requirement: PUB-02
    verification:
      - kind: manual_procedural
        ref: ".planning/phases/04-public-publishing/04-BROWSER.md#listing-at-320-css-pixels"
        status: pass
      - kind: manual_procedural
        ref: ".planning/phases/04-public-publishing/04-BROWSER.md#template-override-ownership"
        status: pass
    human_judgment: true
    rationale: "Actual viewport bounds, readability, theme styling, and override ownership require visual browser judgment."
  - id: D3
    description: "Ticket, Google Calendar, iCalendar, RSS, and webcal links remain keyboard-usable with browser JavaScript disabled."
    requirement: PUB-02
    verification:
      - kind: manual_procedural
        ref: ".planning/phases/04-public-publishing/04-BROWSER.md#keyboard-with-javascript-blocked"
        status: pass
    human_judgment: true
    rationale: "Keyboard focus and link activation were observed in a real browser with scripts blocked."
  - id: D4
    description: "Apple Calendar import preserves the no-time, actual-midnight, and all-day multi-day event meanings."
    requirement: PUB-01
    verification:
      - kind: manual_procedural
        ref: ".planning/phases/04-public-publishing/04-BROWSER.md#calendar-client-import"
        status: pass
    human_judgment: true
    rationale: "Calendar-client semantics require a real import; the user reported checking all three event cases and passed them."
  - id: D5
    description: "The approved final-source fixture passes its public checks with unchanged snapshots and leaves no owned containers or volumes."
    requirement: PUB-01
    verification:
      - kind: e2e
        ref: "tests/compat/run.sh public-fixture --action check --case all"
        status: pass
      - kind: e2e
        ref: "tests/compat/run.sh public-fixture --action stop"
        status: pass
    human_judgment: false

# Metrics
duration: 1273min
completed: 2026-10-06
status: complete
---

# Phase 04 Plan 05: Final-Source Public Acceptance Summary

**The final-source GigPress publishing slice passed the six-cell WordPress/PHP matrix, user-approved browser and calendar checks, and owned-fixture cleanup.**

## Performance

- **Duration:** approximately 21h13m elapsed from the first plan-specific commit; this includes user-review and checkpoint wait across sessions.
- **Started:** 2026-10-05T12:23:06Z
- **Completed:** 2026-10-06T09:36:02Z
- **Tasks:** 3
- **Files modified:** 14 plan source and evidence files.

## Accomplishments

- Rebuilt and validated the complete nine-case public matrix across WordPress 7.0.6 and 7.1.2 with PHP 8.3, 8.4, and 8.5: six cells, 798 named assertions, zero warnings/fatals/plugin errors, and 22 passing corruption checks.
- Added the retained loopback fixture lifecycle and exact final-source browser procedure. Final-source visual review passed at 320 CSS pixels and desktop width, including compact/theme behavior and complete/mixed override ownership.
- Confirmed keyboard activation with JavaScript disabled and recorded the user’s Apple Calendar import pass for no-time, actual-midnight, and all-day multi-day cases.
- Rechecked the approved fingerprint: 13 final fixture assertions passed, all nine endpoints returned HTTP 200, and migrated snapshots were unchanged. Fixture services and volumes were removed.

## Task Commits

1. **Task 04-05-01: final-source fixture and matrix** — source/evidence corrections across `4021a92`, `53766d5`, `6c994df`, `130a02e`, `c8972f8`, `d8e03f8`, `fc8aef0`, `af3977c`, `6da9754`, `7989fa1`, `132ae7a`, `2ff45da`, and `5efc01d`.
2. **Task 04-05-02: final-source browser checkpoint** — initial observation record `7e66b3e`; final user approvals are recorded in this plan’s metadata commit.
3. **Task 04-05-03: final recheck, cleanup, and validation** — results are included in this plan’s metadata commit.

## Files Created/Modified

- `tests/compat/PUBLIC-BROWSER.md` — repeatable final-source browser and calendar-client procedure.
- `.planning/phases/04-public-publishing/04-BROWSER.md` — final-source identity, measured layout, human approvals, keyboard results, calendar import report, and cleanup evidence.
- `.planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md` — refreshed automated evidence and clarification that its embedded machine acceptance fields remain automation-only.
- `.planning/phases/04-public-publishing/04-VALIDATION.md` — completed task map, manual sign-off, and Nyquist status.
- `css/gigpress.css` and `tests/compat/public-layout.php` — wide-table minimum widths and their regression assertion.
- `tests/compat/run.sh`, `tests/compat/browser-bootstrap.php`, and `tests/compat/compose.browser.yaml` — owned final-source fixture lifecycle and matrix support.

## Decisions Made

No new project decisions; the plan followed the established Phase 04 public layout, template ownership, and feed contracts.

## Deviations from Plan

None. The wide-column correction was made within the plan’s final-source review loop, then included in the refreshed matrix and browser fingerprint.

## Issues Encountered

The sandbox initially denied access to OrbStack’s Docker socket for the final fixture check. The same command passed with the authorized elevated access; no source change was needed.

## User Setup Required

None.

## Next Phase Readiness

PUB-01 and PUB-02 are validated. Phase 04 is ready for `$gsd-verify-work 04`; Phase 05 CSV Import/Export is next in the roadmap.

---
*Phase: 04-public-publishing*
*Completed: 2026-10-06*

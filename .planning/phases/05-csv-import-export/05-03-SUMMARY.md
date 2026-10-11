---
phase: 05-csv-import-export
plan: 03
subsystem: csv
tags: [wordpress, csv, import, export, admin-ui, security]

# Dependency graph
requires:
  - phase: 05-csv-import-export
    provides: Shared 23-column CSV contract, guarded import pipeline, and structured import results from plans 05-01 and 05-02.
provides:
  - Shared export selection, count, and download predicates with guarded response emission.
  - Stacked Import/Export screen with retained filter choices, exact-value guidance, and actionable result details.
  - Compatibility coverage for export filters, empty selections, exact CSV literals, and prior import contracts.
affects: [05-04, csv-import-export, admin-ui]

# Actuals (#2632)
actuals:
  tokens: 8707
  tasks: 2
  commits: 4
plan_head_before: a54edda9c8c0af73f75789a32f7a5770ab69ee07
plan_head_after: b68f5641536889caceb075565f2106c0b74bff7e

# Tech tracking
tech-stack:
  added: []
  patterns: [shared-normalized-export-selection, accessible-native-disclosures, structured-import-result-rendering]

key-files:
  created: [admin/csv-export.php, tests/compat/csv-export.php, tests/compat/fixtures/csv/export-literals.php]
  modified: [gigpress.php, admin/import-export.php, css/gigpress-admin.css, tests/compat/csv-workflows.php]

key-decisions:
  - "Drive the preview count and emitted export rows from one normalized predicate contract, with -1 as the all-dates default and current-visit-only filters."
  - "Preserve formula-like and leading-plus CSV values exactly and provide narrowly scoped Excel text-import instructions per D-17."
  - "Map legacy skipped import rows to the duplicate category once and retain explicit row-level recovery guidance."

patterns-established:
  - "Normalize export state once and reuse the same selection predicate for count and row retrieval."
  - "Render import outcomes as escaped, category-grouped details with same-page recovery actions."

requirements-completed: [CSV-01, CSV-02, SEC-01]
coverage:
  - id: D1
    description: Export filters, displayed count, guarded download, empty-state retention, and reset behavior share one tested selection contract.
    requirement: CSV-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case export-filters"
        status: pass
    human_judgment: false
  - id: D2
    description: The stacked screen and result details expose the required controls, outcomes, and bounded text-import workflow while the CSV preserves exact values.
    requirement: CSV-02
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case export-literals"
        status: pass
    human_judgment: true
    rationale: "The compatibility case checks rendered markup and exact values, but does not assess the screen visually in a browser."
  - id: D3
    description: Existing migrated import behavior remains intact across supported CSV fixture versions after the export screen changes.
    requirement: SEC-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case migrated-contracts"
        status: pass
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case tracer-1.4"
        status: pass
    human_judgment: false

# Metrics
duration: 14min
completed: 2026-10-11
status: complete
---

# Phase 05 Plan 03: CSV Export and Import/Export Screen Summary

**One normalized export contract now drives filter counts and CSV rows, alongside a stacked admin screen that preserves exact values and explains the observed Excel text-import path.**

## Performance

- **Duration:** 14 min
- **Started:** 2026-10-11T11:34:15Z
- **Completed:** 2026-10-11T11:47:52Z
- **Tasks:** 2
- **Files modified:** 7

## Accomplishments

- Bound artist, tour, and date filters, preview counts, and CSV rows to the same nondeleted-row predicate; empty results return to the form with current choices intact and an explicit reset action.
- Finished the Import-first stacked screen, accessible disclosures, truthful result groups, and recovery actions.
- Verified all 23 CSV columns and exact UTF-8 values, including formula-like text, leading-plus values, status text, blank End date, and distinct optional-time sentinels.

## Task Commits

Each task was committed with RED and GREEN commits:

1. **Task 1: Bind export filtering, count, and download to one guarded selection contract** - `8136362` (RED), `bc80bff` (GREEN)
2. **Task 2: Finish the stacked Import/Export screen and exact-value CSV contract** - `1a1f615` (RED), `b68f564` (GREEN)

**Measured plan commits:** 4 (`plan_head_before` through `plan_head_after` above).

**Plan metadata:** `75ca4d2` (docs: complete plan)

## Files Created/Modified

- `admin/csv-export.php` - normalized filter state, shared selection predicate, CSV encoding, and guarded response boundary.
- `gigpress.php` - capability and nonce checks plus safe empty/error redirect behavior for the compatibility export entry point.
- `admin/import-export.php` - stacked screen, retained filters, scoped guidance, and structured result rendering.
- `css/gigpress-admin.css` - screen-scoped stacked layout, disclosures, and result details.
- `tests/compat/csv-export.php` - filter, empty-state, screen, and literal-value contracts.
- `tests/compat/fixtures/csv/export-literals.php` - exact-value fixture data.
- `tests/compat/csv-workflows.php` - registered export case routing.

## Decisions Made

- Use `-1` as the explicit default for all dates, artists, and tours; filter values remain limited to the current visit.
- Preserve exact formula-like and leading-plus values. Describe the observed Excel text-import workflow without promising universal spreadsheet safety.
- Count `skipped` legacy import results under duplicates exactly once.

## TDD Gate Compliance

| Task | RED commit and evidence | GREEN commit and verification | Status |
|---|---|---|---|
| 05-03-01 | `8136362`; `RED_EVIDENCE_OK` | `bc80bff`; `export-filters` passed | Pass |
| 05-03-02 | `1a1f615`; `RED_EVIDENCE_OK` | `b68f564`; `export-literals` passed | Pass |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Corrected CSV header serialization for the numeric header list**
- **Found during:** Task 2 (exact-value CSV contract)
- **Issue:** The encoder treated the ordered header list as a keyed record, emitting empty header cells.
- **Fix:** Serialize the canonical header list as values and serialize data rows by the matching ordered field names.
- **Files modified:** `admin/csv-export.php`, `tests/compat/csv-export.php`
- **Verification:** `export-literals` passed with exact 23-column header and decoded-value assertions.
- **Committed in:** `b68f564` (part of task commit)

**Total deviations:** 1 auto-fixed (Rule 1)
**Impact on plan:** The correction was required for the planned CSV contract; no scope was added.

## Issues Encountered

- The default sandbox could not access the local OrbStack Docker socket; the compatibility harness completed successfully with the approved host execution path.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plan 05-03 is complete; the parallel Wave 3 plan 05-04 can proceed.
- `export-filters`, `export-literals`, `tracer-1.4`, and `migrated-contracts` passed on WordPress 7.1.2 / PHP 8.3.35.

## Self-Check: PASSED

- All seven planned source/test files exist.
- All four task commits are present in Git history.
- No generated stubs or TODO/FIXME placeholders were found in the plan's files.

---
*Phase: 05-csv-import-export*
*Completed: 2026-10-11*

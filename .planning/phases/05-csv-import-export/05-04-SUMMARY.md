---
phase: 05-csv-import-export
plan: 04
subsystem: database
tags: [wordpress, csv, conversion, recovery, journal, compatibility]
requires:
  - phase: 05-02
    provides: shared CSV workflow compatibility harness and fixtures
provides:
  - Complete tour conversion preview with explicit Confirm and Cancel
  - Exact-name target selection with ambiguity protection
  - Durable per-tour conversion journal and ownership-safe retry
affects: [05-csv-import-export, verification, ship]
actuals:
  tokens: 11335
  tasks: 2
  commits: 4
plan_head_before: f5caee969abc12225d50c4bb12a41c9927b63f7e
plan_head_after: b2fe6030365b21a33daea561611b1667b81c8e9e
tech-stack:
  added: []
  patterns: [owner-bound one-use intents, per-tour durable journals, conditional per-show writes with readback]
key-files:
  created:
    - admin/tour-conversion.php
    - tests/compat/csv-conversion.php
    - tests/compat/fixtures/csv/conversion.php
  modified:
    - admin/handlers.php
    - admin/artists.php
    - tests/compat/csv-workflows.php
key-decisions:
  - "Require an owner-bound preview and explicit confirmation before any destructive conversion."
  - "Retry only relationships still owned by the conversion; preserve intervening reassignment conflicts and retain the source tour."
requirements-completed: [CSV-02, SEC-01]
coverage:
  - id: D1
    description: Complete conversion preview, explicit confirmation, and deterministic exact-name target selection.
    requirement: CSV-02
    verification:
      - kind: integration
        ref: "tests/compat/run.sh: csv-workflows/conversion-confirm"
        status: pass
    human_judgment: false
  - id: D2
    description: Durable per-tour recovery with readback and ownership checks across mutation faults.
    requirement: SEC-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh: csv-workflows/conversion-retry"
        status: pass
    human_judgment: false
duration: 16min
completed: 2026-10-11
status: complete
---

# Phase 05 Plan 04: Guarded Tour Conversion Summary

**Tour conversion now requires a complete preview and explicit confirmation, then journals each show relationship so owned work can recover safely after faults.**

## Performance

- **Duration:** 16 min
- **Started:** 2026-10-11T11:48:00Z (approximate)
- **Completed:** 2026-10-11T12:03:48Z
- **Tasks:** 2
- **Files modified:** 6 implementation and compatibility files

## Accomplishments

- Added complete preview and separate Confirm/Cancel actions. The preview reports active and trashed references; preview and Cancel leave tables unchanged.
- Bound conversion intents to the administrator, expiry, source snapshot, and preview fingerprint. Exact-name target matching reuses one match, creates only when none exists, and leaves ambiguous matches untouched.
- Added a durable per-tour journal with per-show original/intended/current relationships, readback evidence, and retry ownership checks. Independent tours proceed independently; conflicts preserve the reassigned show and retain the source tour.
- Verified no schema or database version change.

## Task Commits

Each task was committed atomically with RED/GREEN evidence:

1. **Task 1 RED:** `4fc995f` — conversion confirmation contract tests fail before implementation.
2. **Task 1 GREEN:** `ce277fe` — guarded preview and deterministic target selection.
3. **Task 2 RED:** `cf3c01e` — recovery and fault-injection contract tests fail before implementation.
4. **Task 2 GREEN:** `b2fe603` — journaled recovery and ownership-safe retries.

## Verification

- `csv-workflows/conversion-confirm`: passed on WordPress 7.1.2 / PHP 8.3.35, with 15 assertions and no warnings or plugin errors.
- `csv-workflows/conversion-retry`: passed on WordPress 7.1.2 / PHP 8.3.35, with all 27 assertions true and no warnings or plugin errors.
- Fault scenarios covered early/late insert, update, readback, delete, and journal persistence, plus deliberate reassignment between partial progress and retry.
- `upgrade-preservation --case all`: 10 of 11 cases passed. `show-lifecycle` failed; the same isolated failure reproduces on the pre-05-04 baseline commit. It is recorded in `deferred-items.md` as an existing compatibility issue.
- `git diff --check` passed. Stub scan found no TODO/FIXME/placeholder markers in the plan’s changed implementation or test files.

## Files Created/Modified

- `admin/tour-conversion.php` - preview/confirm/cancel state machine, durable journal, conditional writes, and recovery.
- `admin/handlers.php` - guarded conversion dispatch and removal of the legacy destructive conversion path.
- `admin/artists.php` - complete preview and explicit conversion controls with actionable results.
- `tests/compat/csv-conversion.php` - confirmation, retry, fault, and ownership assertions.
- `tests/compat/fixtures/csv/conversion.php` - deterministic conversion fixtures.
- `tests/compat/csv-workflows.php` - registers the conversion compatibility scenario.

## Decisions Made

- Reuse a target only when exactly one artist name is byte-for-byte equal; ambiguity never writes.
- Retry a show update only while its current relationship matches the journaled original. Preserve any intervening assignment and keep its source tour for resolution.
- Delete a source only after all originally confirmed relationships are verified and no source references remain.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Persist retry ownership recovery before reloading the journal**
- **Found during:** Task 2
- **Issue:** A late update fault left a stale journal entry that caused retry to report failure despite the relationship being applied and read back.
- **Fix:** Persist the cleared error and owned/readback evidence immediately when a retry verifies the intended relationship.
- **Files modified:** `admin/tour-conversion.php`
- **Verification:** `conversion-retry` passed, including the `update-after` injected fault.
- **Committed in:** `b2fe603`

**2. [Out-of-scope issue] Isolate the existing upgrade-preservation failure**
- **Found during:** Task 2 verification
- **Issue:** The aggregate compatibility matrix reports a `show-lifecycle` failure.
- **Fix:** Re-ran the case alone and on the pre-plan baseline; both fail identically. Left unrelated show-lifecycle implementation untouched and recorded the issue in `deferred-items.md`.
- **Files modified:** `.planning/phases/05-csv-import-export/deferred-items.md`
- **Verification:** `show-lifecycle` fails on current code and on archived `HEAD^`; the remaining ten aggregate cases pass.
- **Committed in:** Plan metadata commit.

**Total deviations:** 1 auto-fixed bug; 1 pre-existing out-of-scope issue documented.
**Impact on plan:** Conversion work is complete. The pre-existing compatibility failure is scoped and documented for follow-up.

## Issues Encountered

- The local host lacks a PHP binary; compatibility tests ran through the project’s WordPress container.
- Default Docker socket access was denied, so the requested compatibility scenarios ran through the approved escalated command path.

## User Setup Required

None.

## Next Phase Readiness

Plan 05-04 is complete. The pre-existing `show-lifecycle` compatibility failure remains deferred; do not treat the aggregate upgrade-preservation matrix as fully green until that issue is resolved.

## Self-Check: PASSED

- Summary file exists at the required path.
- All four task RED/GREEN commits are present in Git history.
- Measured task commit count is 4 from `plan_head_before` through `plan_head_after`.

---
*Phase: 05-csv-import-export*
*Completed: 2026-10-11*

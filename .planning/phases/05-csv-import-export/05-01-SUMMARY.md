---
phase: 05-csv-import-export
plan: "01"
subsystem: csv
tags: [wordpress, php, csv, import, export, compatibility]

requires:
  - phase: 02-data-and-upgrade-preservation
    provides: Reconstructed 1.0-1.6 fixtures, migration seeding, source snapshots, and preservation manifests
  - phase: 03-administration-workflows
    provides: WordPress administration conventions and protected mutation patterns
provides:
  - Shared CSV schema, validation, persistence, and per-row result contracts
  - Guarded WordPress CSV import callback delegating into the shared contract
  - Fail-closed tracer and migrated-contract test dispatch for recognized 1.0-1.6 sources
affects: [05-02, 05-03, 05-04]

actuals:
  tokens: 10029
  tasks: 2
  commits: 5
  plan_head_before: aabce39edde41ebb6d36201dbc3affbbd2a19eb0
  plan_head_after: 7a275973d1750e312c27f8c2674ff1be78190941

tech-stack:
  added: []
  patterns:
    - Shared procedural CSV contracts separate request guards, row preparation, persistence, and result accounting
    - Fixture-owned expected CSV rows remain independent of the importer and exporter under test

key-files:
  created:
    - admin/csv.php
    - tests/compat/csv-migrated.php
    - tests/compat/fixtures/csv/expected-migrated.php
  modified:
    - admin/handlers.php
    - tests/compat/run.sh
    - tests/compat/probe.php
    - tests/compat/csv-workflows.php

key-decisions:
  - Preserve exact CSV scalar values, including formula-like, Unicode, and leading-plus values, and provide spreadsheet text-import guidance per D-17.
  - Preserve existing status 0 unchanged; reject it for new import with an explicit manual-correction reason per D-13.

patterns-established:
  - A CSV record receives one terminal structured outcome, and retries are checked against full table snapshots.
  - Same-site preservation and fresh-destination allocation are separate assertions.

requirements-completed: [CSV-01, CSV-02, SEC-01]

coverage:
  - id: D1
    description: Shared CSV import validates and persists supported records behind capability, method, nonce, and database guards.
    requirement: SEC-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case tracer-1.4"
        status: pass
    human_judgment: false
  - id: D2
    description: CSV exports preserve the established ordered columns and values through migrated 1.0-1.6 source states and fresh-destination retries.
    requirement: CSV-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case migrated-contracts"
        status: pass
    human_judgment: false
  - id: D3
    description: CSV result preparation identifies unsupported legacy status 0 for manual correction without changing the stored record.
    requirement: CSV-02
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case migrated-contracts"
        status: pass
    human_judgment: false

duration: 23min
completed: 2026-10-11
status: complete
---

# Phase 05 Plan 01: CSV Import/Export Summary

**Protected CSV import contracts and independent migration round trips for every recognized 1.0-1.6 source version**

## Performance

- **Duration:** 23 min
- **Started:** 2026-10-11T10:43:02Z
- **Completed:** 2026-10-11T11:05:50Z
- **Tasks:** 2
- **Files modified:** 7

## Accomplishments

- Added shared CSV column, status, row-preparation, persistence, and outcome-accounting helpers; kept `gigpress_import()` as the guarded WordPress entry point.
- Added a populated 1.4 tracer proving exact headers, unchanged source data during export, protected request handling, fresh-destination creation, and an unchanged duplicate retry.
- Added independent expected projections for reconstructed 1.0-1.6 sources, checking same-site snapshots, fresh IDs, retries, related-post choices, legacy status `0`, and exact formula-like, Unicode, and leading-plus values.

## Task Commits

1. **Task 1 RED:** `6b39069` — failing migrated CSV tracer contract.
2. **Task 1 GREEN:** `a6d6cc2` — shared import and persistence implementation.
3. **Task 2 RED:** `dee1daf` — failing migrated CSV contract matrix.
4. **Task 2 GREEN:** `ad49b9b` — fixture-driven preservation contracts.
5. **Task 2 supplemental test:** `7a27597` — exact literal CSV round-trip assertions.

**Plan metadata:** Included with the summary and phase-state update in the final documentation commit.

## Files Created/Modified

- `admin/csv.php` - Shared CSV schema, validation, persistence, outcomes, request guards, and legacy status-zero guidance.
- `admin/handlers.php` - Delegates the established import callback to the shared request handler.
- `tests/compat/run.sh` and `tests/compat/probe.php` - Forward and execute the selected CSV scenario and case.
- `tests/compat/csv-workflows.php` - Case registry, tracer, and migrated-case dispatch.
- `tests/compat/csv-migrated.php` - Cross-version same-site and fresh-destination assertions.
- `tests/compat/fixtures/csv/expected-migrated.php` - Independent ordered row expectations for versions 1.0 through 1.6.

## Decisions Made

- Preserved values exactly after CSV syntax decoding. Formula-like values, Unicode, and leading-plus phone numbers are neither rewritten nor blocked; spreadsheet text-import guidance is assigned to the Phase 05 export UI work.
- Kept existing status `0` unchanged in storage and export. New imports reject it with a manual-correction reason instead of inferring a supported status.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing critical guidance] Identify legacy status `0` for manual correction**
- **Found during:** Task 2
- **Issue:** The shared status validator rejected unsupported values but did not explain that a legacy `0` requires human correction.
- **Fix:** Added a status-specific manual-correction reason while leaving the stored legacy status and export value unchanged; added a fixture-backed assertion.
- **Files modified:** `admin/csv.php`, `tests/compat/csv-migrated.php`
- **Verification:** The `migrated-contracts` cell confirms status `0` exports unchanged, is rejected as a new import, and leaves the source snapshot unchanged.
- **Committed in:** `ad49b9b`

**2. [Integration adjustment] Route the new migrated case through the existing dispatcher**
- **Found during:** Task 2
- **Issue:** The task file list omitted `tests/compat/csv-workflows.php`, but the required `migrated-contracts` selected case could not run without a dispatcher route.
- **Fix:** Added the case route and fail-closed module status to the existing dispatcher.
- **Files modified:** `tests/compat/csv-workflows.php`, `tests/compat/csv-migrated.php`
- **Verification:** The selected case reports PASS with nine nonempty assertions and no warnings, fatals, or plugin errors.
- **Committed in:** `dee1daf`, `ad49b9b`

**Total deviations:** 2 (one Rule 2 correction and one required dispatcher integration adjustment).
**Impact on plan:** Closed the legacy status guidance gap and made the planned selected case runnable; no package installs or schema changes were introduced.

## TDD Gate Compliance

- Task 1: RED `6b39069` → `RED_EVIDENCE_OK` → GREEN `a6d6cc2`; tracer passed after implementation and again at plan verification.
- Task 2: RED `dee1daf` → `RED_EVIDENCE_OK` → GREEN `ad49b9b`; migrated matrix passed all recognized versions.
- Supplemental literal-value coverage was added in `7a27597` and passed the same migrated-contracts verification.

## Issues Encountered

- OrbStack daemon access required the approved elevated read-only probe and elevated Docker compatibility runs. The probe succeeded, so automated container verification proceeded.
- The 1.0 and 1.1 reconstructed schemas have no status column, so both historical rows appear in their exports. The 1.4 and 1.5 source schemas do not retain the later postal-code field. The independent manifests assert those observed values explicitly.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plan 05-01 is complete; Plan 05-02 can build on the shared import contracts and fail-closed compatibility scenario.
- Broader import row-error, retry, upload-guard, export-filter, and browser-screen cases remain assigned to later Phase 05 plans.

---
*Phase: 05-csv-import-export*
*Completed: 2026-10-11*

## Self-Check: PASSED

- Summary file exists at the required phase path.
- All five task commits listed above resolve in the repository log.
- The ledger reports five commits from the recorded plan start; the recorded base and current task head match.

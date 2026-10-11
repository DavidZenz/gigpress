---
phase: 05-csv-import-export
plan: "02"
subsystem: csv
tags: [wordpress, php, csv, import, multipart, validation, retry]

requires:
  - phase: 05-csv-import-export
    provides: Shared CSV schema, persistence, and result contracts from Plan 05-01
provides:
  - Strict UTF-8 header and record validation with byte-preserving values
  - Authenticated multipart upload boundary and truthful file-level feedback
  - Verified persistence outcomes and safe retry after insert or read-back failures
affects: [05-03, 05-04]

actuals:
  tokens: 9153
  tasks: 2
  commits: 5
  plan_head_before: 064cd5134e7fc84db03e45e0b68bb85e5954d809
  plan_head_after: 4a7e490f6284ecc68d8c10fb405fd200b6a944f9

tech-stack:
  added: []
  patterns:
    - Validate complete CSV records before any persistence lookup or write
    - Use actual authenticated multipart transport for upload-origin evidence and trusted in-process helpers for fault injection
    - Verify inserted identities and values, then reuse completed entities during retry

key-files:
  created:
    - tests/compat/csv-import.php
    - tests/compat/fixtures/csv/import-records.php
    - tests/compat/fixtures/csv/empty.csv
    - tests/compat/fixtures/csv/header-only.csv
  modified:
    - admin/csv.php
    - tests/compat/browser-bootstrap.php
    - tests/compat/csv-workflows.php
    - tests/compat/run.sh

key-decisions:
  - "Preserve decoded UTF-8 values exactly, including formula-like and leading-plus values, and provide spreadsheet text-import guidance per D-17."
  - "Keep actual multipart transport separate from trusted in-process persistence fault tests; production has no upload-origin bypass."

patterns-established:
  - "A CSV import record is counted once as imported, duplicate, rejected, or failed, with row identity and actionable next steps."
  - "Negative upload and validation cases compare complete persistence snapshots before and after the request."

requirements-completed: [CSV-01, CSV-02, SEC-01]

coverage:
  - id: D1
    description: "Strict row validation preserves valid CSV literals and rejects malformed records without writes."
    requirement: CSV-01
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case import-rows"
        status: pass
    human_judgment: false
  - id: D2
    description: "The production import route accepts only authorized, bounded, actual multipart uploads and renders failures as inert text."
    requirement: SEC-01
    verification:
      - kind: e2e
        ref: "tests/compat/run.sh browser-fixture --action smoke --wp 7.1.2 --php 8.3 --case csv"
        status: pass
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case upload-guards"
        status: pass
    human_judgment: false
  - id: D3
    description: "Database write and read-back failures produce failed outcomes and retry without duplicate entities or overwritten shows."
    requirement: CSV-02
    verification:
      - kind: integration
        ref: "tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario csv-workflows --case import-retry"
        status: pass
    human_judgment: false

duration: 23min
completed: 2026-10-11
status: complete
---

# Phase 05 Plan 02: Validated CSV Import Summary

**Strict CSV record validation and actual multipart upload guards with verified, retry-safe persistence**

## Performance

- **Duration:** 23 min
- **Started:** 2026-10-11T11:08:59Z
- **Completed:** 2026-10-11T11:31:52Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments

- Added strict header, encoding, record-width, field, date, time, status, and related-post validation before persistence. Valid decoded values remain exact, including formula-like values and leading-plus text.
- Added authenticated multipart upload validation for capability, method, action, nonce, readiness, upload origin, file extension, readability, and the effective WordPress/PHP upload limit. Empty and header-only CSV files return actionable file-level feedback with zero row totals.
- Added distinct imported, duplicate, rejected, and failed outcomes; verified inserted records by read-back; and covered retries after artist, tour, venue, show, and read-back failures.
- Added actual multipart browser evidence with no production bypass and complete snapshots around rejected upload paths.

## Task Commits

1. **Task 1 RED:** `1e1c853` — failing import row contract.
2. **Task 1 GREEN:** `6695115` — record validation and persistence.
3. **Task 2 RED:** `43bb630` — failing upload and retry contracts.
4. **Task 2 GREEN:** `6e9ea36` — multipart upload guards and failure coverage.
5. **Compatibility fix:** `4a7e490` — retain the Plan 05-01 row-preparation helper.

Plan metadata is recorded in the final documentation commit.

## Files Created/Modified

- `admin/csv.php` - Strict parsing, validation, upload boundary, structured outcomes, verified persistence, and retry behavior.
- `tests/compat/csv-import.php` - Import row, persistence fault, retry, and upload guard assertions.
- `tests/compat/fixtures/csv/import-records.php` - Independent valid and invalid CSV bytes and row expectations.
- `tests/compat/fixtures/csv/empty.csv` and `header-only.csv` - Actual multipart file-level error fixtures.
- `tests/compat/browser-bootstrap.php` - Owned authenticated multipart helper and real browser transport assertions.
- `tests/compat/csv-workflows.php` and `tests/compat/run.sh` - Case dispatch and browser CSV selector integration.

## Verification

- `import-rows`: PASS, 11 assertions, 0 warnings/fatals/plugin errors.
- `import-retry`: PASS, 6 assertions, 0 warnings/fatals/plugin errors.
- `upload-guards`: PASS, 2 assertions, 0 warnings/fatals/plugin errors.
- Actual multipart browser CSV case: PASS, 10 case assertions (11 runner assertions), including successful upload persistence, hostile filename escaping, invalid nonce, subscriber denial, empty/header-only files, effective upload limit, and temporary upload cleanup.
- Existing `tracer-1.4`: PASS, 14 assertions, 0 warnings/fatals/plugin errors.
- Existing `migrated-contracts`: PASS across versions 1.0–1.6, 9 assertions, 0 warnings/fatals/plugin errors.
- All listed runs used WordPress 7.1.2 and PHP 8.3.35.

## Decisions Made

- Preserve exact valid CSV values after syntax decoding, including formula-like and leading-plus content; keep text-import guidance as the spreadsheet safety mechanism per D-17.
- Use real multipart HTTP requests to prove `is_uploaded_file()` acceptance. Direct database fault injection remains in the trusted in-process test path.

## Deviations from Plan

### Auto-fixed Issues

**1. [Integration adjustment] Register the plan’s selected import cases**
- **Found during:** Task 1
- **Issue:** The existing CSV dispatcher did not route the new import row, retry, and upload guard cases.
- **Fix:** Registered the cases and added the browser runner’s CSV selector.
- **Files modified:** `tests/compat/csv-workflows.php`, `tests/compat/run.sh`
- **Verification:** All three selected in-process cases and the actual multipart browser case pass.
- **Committed in:** `1e1c853`, `43bb630`

**2. [Rule 1 - Bug] Restore Plan 05-01 preparation API compatibility**
- **Found during:** Regression verification
- **Issue:** Replacing the earlier preparation function left the existing migrated CSV tracer calling a missing helper.
- **Fix:** Restored `gigpress_csv_prepare_record()` as a thin wrapper around the stricter validator.
- **Files modified:** `admin/csv.php`
- **Verification:** `tracer-1.4` and `migrated-contracts` both pass across their assertions and migrated fixtures.
- **Committed in:** `4a7e490`

**Total deviations:** 2 (one required test integration adjustment and one Rule 1 compatibility fix).
**Impact on plan:** The additions enable the planned verification and preserve the earlier plan’s public helper contract; no schema or package changes were introduced.

## TDD Gate Compliance

- Task 1: RED `1e1c853` → GREEN `6695115`; `import-rows` passes with 11 assertions.
- Task 2: RED `43bb630` → GREEN `6e9ea36`; upload, retry, and actual multipart checks pass.
- Regression fix: `4a7e490` restores the prior helper API; tracer and migration matrix pass.

## Issues Encountered

- The first post-implementation tracer run identified the removed helper contract; it was restored and both existing CSV regression cases passed afterward.
- Container-based verification required OrbStack access outside the default sandbox; approved runs completed successfully.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- The import and persistence seams are ready for the planned export/UI and tour conversion plans.
- Plans 05-03 and 05-04 were not started.

---
*Phase: 05-csv-import-export*
*Completed: 2026-10-11*

## Self-Check: PASSED

- Summary file exists at the required phase path.
- All five plan commits listed above resolve in the repository log.
- The plan ledger base and recorded task head match the five measured plan commits.

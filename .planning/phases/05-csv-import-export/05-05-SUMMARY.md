---
phase: 05-csv-import-export
plan: 05
subsystem: testing
tags: [wordpress, csv, compatibility-matrix, browser-uat, evidence]
requires:
  - phase: 05-03
    provides: Exact export behavior and stacked Import/Export screen
  - phase: 05-04
    provides: Confirmed conversion and recovery scenarios
provides:
  - Exact nine-case CSV evidence across six pinned WordPress/PHP runtime cells
  - Corruption-tested CSV and refreshed Phase 02/04 evidence reports
  - Owned CSV browser fixture and accepted human-acceptance record with explicit D-01 waiver
affects: [05-csv-import-export, verification]
actuals:
  tokens: 148551
  tasks: 3
  commits: 3
plan_head_before: beba8c0f78a3f741a1f8f48a57f3ca7c3df52e9e
plan_head_after: 73dad7163fe004fb0769d2f6dc9305591009f255
tech-stack:
  added: []
  patterns: [source-and-result-fingerprinted evidence, owned loopback fixture sessions]
key-files:
  created:
    - .planning/phases/05-csv-import-export/05-CSV-MATRIX.md
    - .planning/phases/05-csv-import-export/05-BROWSER.md
    - tests/compat/CSV-BROWSER.md
  modified:
    - tests/compat/run.sh
    - tests/compat/csv-workflows.php
    - tests/compat/browser-bootstrap.php
    - gigpress.php
    - .planning/phases/05-csv-import-export/05-VALIDATION.md
    - .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md
    - .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md
key-decisions:
  - "Treat all as an aggregate selector; record exactly nine independently fingerprinted CSV case results per runtime."
  - "Keep formula-like and leading-plus values exact; Excel-specific text-import acceptance stays pending until observed in a named client/version."
  - "Keep the D-01 no-JavaScript waiver explicit and untested; keep the optional spreadsheet-client workflow separate from exact raw-CSV acceptance."
patterns-established:
  - "Evidence validation recomputes source/result hashes and rejects corruption in copied records."
  - "Browser evidence distinguishes authenticated HTTP smoke from actual named-browser observations."
requirements-completed: [CSV-01, CSV-02, SEC-01]
coverage:
  - id: D1
    description: Exact CSV compatibility matrix, final fingerprints, and inherited evidence refresh
    requirement: CSV-01
    verification:
      - kind: integration
        ref: "csv-evidence validate: 54 rows, six runtime cells"
        status: pass
      - kind: integration
        ref: "public-evidence validate: six cells, 852 assertions"
        status: pass
      - kind: integration
        ref: "preservation-evidence validate: six cells, 11 cases each"
        status: pass
    human_judgment: false
  - id: D2
    description: Human browser and optional spreadsheet-client acceptance
    requirement: CSV-02
    verification:
      - kind: human
        ref: "05-BROWSER.md H-05-01 through H-05-05; 05-UAT.md tests 1-7"
        status: pass
    human_judgment: true
    rationale: "The user confirmed the browser workflows and explicitly waived D-01 for now. D-01 remains untested; no spreadsheet text-import/save/re-import behavior is claimed, and the optional client row stays pending."
duration: 64min
completed: 2026-10-11
status: complete
---

# Phase 05 Plan 05: Final CSV Evidence and Browser Fixture Summary

**The exact nine-case CSV matrix, corruption checks, refreshed Phase 02/04 reports, and owned browser fixture are ready for human acceptance.**

## Performance

- **Duration:** About 64 minutes
- **Started:** 2026-10-11T12:04:26Z (approximate)
- **Completed:** 2026-10-11T13:08:41Z
- **Tasks:** 3
- **Files modified:** 10 plan-owned files across task commits

## Accomplishments

- Built and validated 54 exact CSV records covering nine registered cases across WordPress 7.0.7/7.1.3 and PHP 8.3/8.4/8.5; the 12-class corruption self-test passed.
- Added an owned fixture lifecycle and verified the authenticated multipart smoke. The first smoke revealed a missing `admin/csv.php` include on the Import/Export screen; loading that file fixed the route, and the rerun passed all checks and cleaned up its containers.
- Rebuilt and validated the Phase 04 public matrix (852 assertions, six cells, zero warnings/fatals/plugin errors) and Phase 02 preservation matrix (11 cases across six cells). Phase 02's earlier `show-lifecycle` failure came from stale test expectations: the test expected the retired one-click conversion to mutate data and omitted the required POST action for import. The updated test verifies safe legacy rejection and current request guards; the 05-04 conversion cases had passed and remain disclosed as separate evidence.
- Recorded the owned human fixture URL and session path. D-02 through D-17 have passing browser observations; D-01 is waived and untested; no spreadsheet-client behavior is claimed.

### Human acceptance follow-up (2026-10-12)

- The user identified macOS and the Codex app integrated browser; its version is not exposed. The observed fixture is the OrbStack-hosted site at `http://127.0.0.1:32768`.
- D-02 is now user-confirmed after the column reference was corrected to show all 23 ordered rows with formats and examples. D-03, D-04, D-08 through D-16 also have observations in `05-BROWSER.md`; D-09 through D-11 and D-14 through D-16 pass.
- D-05 passed after a real multipart upload displayed all four outcome groups and their expanded row details together; its one-request failure trigger was removed immediately afterward. D-06 and D-07 passed after the user uploaded a one-row impossible-date CSV and confirmed the row guidance and recovery actions. D-17 passed when the user pasted the actual all-dates export showing the leading-zero postal code, leading-plus phone values, and formula-like note unchanged. The user confirmed the embedded browser cannot provide a JavaScript-off check. The acceptance disposition below records the later temporary waiver for D-01 without claiming that it was tested. No spreadsheet-client workflow is claimed.
- After the D-02 source correction, the focused `export-literals` case passed and the 54-row matrix was rebuilt, validated, and corruption-tested again.

### Acceptance disposition (2026-10-12)

- At the user's request, the Phase 05 conversational acceptance is recorded complete with a temporary waiver for D-01. The UAT marks the waiver-recording check passed because the user explicitly accepted it for now; the browser evidence continues to mark D-01 itself waived and untested.
- The waiver does not claim that JavaScript-disabled behavior passed. D-02 through D-17 retain their recorded passing evidence. No spreadsheet-client workflow is claimed.

### Review-gap closure follow-up (2026-10-12)

- CR-01: import validation now enforces schema-sized UTF-8 text, Notes byte capacity, and supported two-letter country codes before persistence. `import-rows` passes in all six CSV matrix runtime cells.
- CR-02: the preview and download use the configured GigPress capability; configured-manager success and unauthorized denial pass in `export-filters` across all six cells.
- CR-03/CR-04: verification read errors retain the source tour and nonterminal journal, while an atomic option-backed mutex serializes confirmations. Both fault-injection cases pass in `conversion-retry` across all six cells.
- WR-01: the legacy full-workflow probe now logs in, loads the real import form/nonce, posts a multipart upload, repeats it without mutation, and downloads through the admin-post endpoint. The final-source WordPress 7.1.2 / PHP 8.3.35 probe and runner self-test pass.
- The final Phase 05 matrix has 54 passing records (nine exact CSV cases across six runtimes), validates its source/result fingerprints, and rejects all 12 corruption classes. Refreshed Phase 02 and Phase 04 reports validate; Phase 04 also rejects all 22 corruption classes.
- Follow-up review fixes cover target-lookup errors, transactional deletion verification and rollback, refusal on nontransactional tour storage, and the `edit_posts` fallback for missing capability settings. The focused conversion suite passes 33 assertions, export/import capability parity passes 16 assertions, and final code review reports zero findings.
- Regression checks pass for the real multipart workflow and cleanup, runner self-test, menu contract, and PHP 8.3 → diagnostic PHP 8.2 → PHP 8.3 recovery on WordPress 7.0 and 7.1.

## Task Commits

1. **Task 05-05-01:** `26ff1de` — exact CSV evidence producer and validator.
2. **Task 05-05-02:** `0191910` — owned browser fixture, pending acceptance form, and route include fix.
3. **Task 05-05-03:** `73dad71` — refreshed matrix reports and validation mapping.

**Plan metadata:** `3c18078` — state, roadmap, and summary record.

## Files Created/Modified

- `tests/compat/run.sh`, `tests/compat/csv-workflows.php` — exact CSV evidence generation, validation, corruption checks, and fixture lifecycle.
- `tests/compat/browser-bootstrap.php`, `gigpress.php` — authenticated browser prerequisites and the shared CSV include for the admin route.
- `tests/compat/CSV-BROWSER.md`, `05-BROWSER.md` — fixture instructions and human evidence form.
- `05-CSV-MATRIX.md`, `05-VALIDATION.md`, Phase 02/04 matrix reports — final automated evidence and truthful pending-state mapping.

## Decisions Made

- Keep all nine CSV cases as distinct evidence records; `all` is only the aggregate runner selector.
- Preserve exact CSV values and accept direct-open formula risk. The UI's text-import guidance is Excel-specific, and its spreadsheet-client workflow remains pending until a named client/version is observed.
- Mark Phase 05 complete with Nyquist compliance true after all evidence and the user-approved D-01 waiver are recorded; keep the optional spreadsheet-client workflow separate and unobserved.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Load the shared CSV contract on the Import/Export route**
- **Found during:** Task 05-05-02
- **Issue:** The owned browser fixture raised an undefined-function error for `gigpress_csv_columns()`.
- **Fix:** Load `admin/csv.php` from `gigpress.php` before `admin/import-export.php`.
- **Files modified:** `gigpress.php`
- **Verification:** The authenticated multipart CSV fixture smoke passed after the fix.
- **Committed in:** `0191910`

**2. [Rule 1 - Test contract] Correct stale Phase 02 lifecycle expectations**
- **Found during:** Task 05-05-03 continuation
- **Issue:** The `show-lifecycle` case expected the retired one-click tour conversion and invoked import without the current POST action.
- **Fix:** The parent executor updated the compatibility test to assert legacy rejection without writes and to exercise the current import request/nonce rejection. This change remains separate from this executor's task commits.
- **Files modified:** `tests/compat/upgrade-preservation-crud.php`
- **Verification:** The targeted case passed; the final preservation matrix and validator passed all 11 cases in all six runtime cells.

**Total deviations:** 2 automatic fixes (1 route boot bug, 1 stale test contract).
**Impact on plan:** Both fixes were necessary for accurate runtime evidence; no production conversion behavior was changed.

## Issues Encountered

- Initial CSV browser smoke failed at the Import/Export route due to the missing shared CSV include. The route was fixed and the smoke passed.
- The pre-conversion `show-lifecycle` failure was reproduced during the initial Phase 02 refresh, then diagnosed as a stale compatibility-test contract. The current final-source matrix passes; the historical failure remains documented in the Phase 02 and 05-04 evidence.
- The unavailable JavaScript-disabled behavior is explicitly waived and untested; the optional spreadsheet-client workflow remains unobserved. The user-reviewed fixture was at `http://127.0.0.1:32768`; its session is listed in `05-BROWSER.md`.

## User Setup Required

No external service configuration is required. Human acceptance requires access to the live local fixture and an actual browser/version; spreadsheet-client acceptance is optional and must name the observed client/version.

## Next Phase Readiness

- Plan 05-05 evidence work and the follow-up gap closure are recorded. Phase 05 is complete with Nyquist compliance true and the user's temporary D-01 waiver; the JavaScript-disabled behavior remains explicitly untested.
- D-02 through D-17 retain their passing observations. The optional spreadsheet-client workflow remains unobserved and is not inferred from the HTTP smoke or compatibility matrix.

---
*Phase: 05-csv-import-export*
*Completed: 2026-10-11*

## Self-Check: PASSED

- Summary file exists at the required phase path.
- Task commits `26ff1de`, `0191910`, and `73dad71` are present in the repository history.
- Measured plan commit count is 3 from `plan_head_before` through `plan_head_after`.

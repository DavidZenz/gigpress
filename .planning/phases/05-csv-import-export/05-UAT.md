---
status: complete
phase: 05-csv-import-export
source: 05-01-SUMMARY.md, 05-02-SUMMARY.md, 05-03-SUMMARY.md, 05-04-SUMMARY.md, 05-05-SUMMARY.md
started: 2026-10-12T13:42:53Z
updated: 2026-10-12T13:42:53Z
---

## Current Test

[testing complete — accepted with temporary waiver]

## Tests

### 1. Compatibility and evidence matrix
expected: The final Phase 05 matrix covers nine CSV cases across six WordPress/PHP runtime cells; corruption checks reject all 12 invalid evidence classes; refreshed Phase 02 and Phase 04 reports validate.
result: pass
evidence: 54 CSV records passed; all 12 corruption classes were rejected; refreshed Phase 04 public evidence and Phase 02 preservation evidence validated. See 05-CSV-MATRIX.md and 05-VALIDATION.md.

### 2. Import/export screen and CSV reference
expected: The CSV reference opens with all 23 ordered rows and Format/Example columns; the related-post option is unchecked and explains destination-site IDs; action labels are “Import shows” and “Download CSV.”
result: pass
evidence: User confirmed the readable 23-row disclosure and pasted the page showing the related-post explanation and exact action labels. See 05-BROWSER.md decisions D-02 through D-04.

### 3. Import outcomes, recovery, duplicate handling, and legacy status
expected: A multipart upload reports imported, duplicate, rejected, and failed outcomes with row details and recovery links; invalid dates are rejected; duplicate rows do not overwrite existing notes; legacy status 0 is identified for manual attention.
result: pass
evidence: User confirmed all four outcome groups, rejected-row guidance and recovery actions, unchanged existing notes on duplicate reupload, and the visible status-0 guidance. See 05-BROWSER.md decisions D-05 through D-08 and D-13.

### 4. Export selection, live count, no-match, and filter lifetime
expected: The live summary reflects artist/tour/date choices; downloads contain matching rows; a no-match selection downloads nothing; current choices persist during the visit, Reset restores defaults, and a later visit starts from defaults.
result: pass
evidence: User confirmed the 26-row upcoming download without Apply, the no-match notice without a file, Reset behavior, and filter retention/default behavior. See 05-BROWSER.md decisions D-09 through D-11.

### 5. Spreadsheet guidance and exact CSV literals
expected: The visible note and expanded Excel text-import steps are clear, and raw CSV preserves leading-zero, leading-plus, and formula-like values exactly.
result: pass
evidence: User confirmed the guidance was clear and pasted the downloaded CSV containing `00100`, `+00012`, `+43-1-555-0100`, and `=2+2` unchanged. No spreadsheet client was used, so no client-specific import/save/re-import result is claimed. See 05-BROWSER.md decisions D-12 and D-17.

### 6. Tour conversion preview, cancellation, confirmation, and recovery
expected: Preview identifies the tour and reference counts; Cancel leaves it unchanged; confirmation creates/reuses the intended artist, removes the source tour only after reassignment, retains all show associations, and retry remains safe.
result: pass
evidence: User confirmed the Browser Tour preview and Cancel behavior, then ran the no-match conversion and confirmed success, one new artist, source-tour removal, and all 26 show references retained. Automated conversion evidence covers the other matching, ambiguity, and retry cases. See 05-BROWSER.md decisions D-14 through D-16.

### 7. JavaScript-disabled layout and fallback
expected: With JavaScript disabled, Import remains first, Export remains visible below it, and the no-JavaScript fallback is operable.
result: skipped
reason: The user confirmed JavaScript-off inspection is unavailable in the Codex integrated browser and explicitly waived this check for the current acceptance on 2026-10-12. The behavior remains untested; source inspection is recorded separately and is not treated as a browser pass. This is a temporary acceptance waiver, not a test result.

## Summary

total: 7
passed: 6
issues: 0
pending: 0
skipped: 1
blocked: 0

## Gaps

None reported. Test 7 is an explicitly waived observation, not a product issue or a passing test. The optional spreadsheet-client workflow was not part of this acceptance and remains unobserved.

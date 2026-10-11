---
phase: 05-csv-import-export
status: pending-human-observation
browser: pending
browser_version: pending
spreadsheet_client: pending
spreadsheet_client_version: pending
fixture_url: pending
fixture_session: pending
---

# Phase 05 CSV Browser Acceptance

This is an evidence form. No browser or spreadsheet-client observation has been recorded yet. Automated container/HTTP evidence does not fill these rows. D-17 retains exact CSV values, accepts direct-open formula risk, and permits only text-import guidance bounded to an actually observed named client workflow.

| Decision | Required observation | Browser/client identity | Result/evidence | Status |
|---|---|---|---|---|
| D-01 | With JavaScript disabled, Import is first and Export remains visible below it. | Pending | Pending | Pending |
| D-02 | Essential CSV requirements are visible; the column reference disclosure opens and lists formats/examples accessibly. | Pending | Pending | Pending |
| D-03 | Related-post checkbox is visible and unchecked; destination-site exact post ID explanation is clear. | Pending | Pending | Pending |
| D-04 | Action labels are exactly “Import shows” and “Download CSV.” | Pending | Pending | Pending |
| D-05 | Real multipart import shows imported, duplicate, rejected and failed counts with expandable outcome groups. | Pending | Pending | Pending |
| D-06 | Rejected/failed details identify CSV row, show identity, affected field/reason and correction or retry guidance. | Pending | Pending | Pending |
| D-07 | After import the result remains on Import/Export and offers View shows and Upload another CSV. | Pending | Pending | Pending |
| D-08 | Corrected reupload is safe; matching existing shows are duplicates and are not overwritten. | Pending | Pending | Pending |
| D-09 | All-dates, upcoming and past selections display the actual artist/tour/scope/count and download matching rows. | Pending | Pending | Pending |
| D-10 | Empty selection creates no download, retains current choices and offers Reset filters; choosing Reset restores all three defaults. | Pending | Pending | Pending |
| D-11 | Choices last for the current visit and a later visit uses existing defaults. | Pending | Pending | Pending |
| D-12 | Visible spreadsheet note and disclosure explain text import and preserving exact values for re-import. | Pending | Pending | Pending |
| D-13 | Existing status `0` remains unchanged and is identified for manual correction. | Pending | Pending | Pending |
| D-14 | Conversion preview lists affected tour names/counts; Cancel changes nothing; Confirm explains reassignment and source removal. | Pending | Pending | Pending |
| D-15 | Exact-one target is reused; no-match creates one target; ambiguity leaves the tour unchanged; retry does not duplicate. | Pending | Pending | Pending |
| D-16 | Independent tours continue; failures and readback are visible; retained relationships retry safely without overwriting reassignment. | Pending | Pending | Pending |
| D-17 | Exported formula-like and leading-plus values remain exact. Any spreadsheet observation names client/version and records text-import, save and re-import only; direct-open safety remains unclaimed. | Pending | Pending | Pending |
| Optional spreadsheet client | Record client/version, OS, downloaded-file hash, exact text-import settings, saved-file hash and re-imported values. | Pending | Pending | Pending |

## Required observation steps

1. Start the owned fixture using the commands in `tests/compat/CSV-BROWSER.md`. Record the returned loopback URL, private session path, actual browser/version and operating system.
2. In that browser, disable JavaScript and inspect the import-first stacked screen, disclosure keyboard access, unchecked related-post option and exact labels.
3. Upload the deterministic CSV using the form. Record the four outcome counts, row details, retry affordances and whether matching rows remain unchanged.
4. Download all-dates, upcoming and past exports. Record selected scope and count, downloaded-file hashes, the 23-column order, exact formula-like/leading-plus literals, status, time and date values.
5. Submit an empty selection. Confirm there is no download, current choices remain selected, Reset filters is available and choosing it restores All artists / All tours / All dates. Revisit to confirm defaults.
6. Preview then Cancel conversion and compare snapshots. Preview then Confirm the exact-one, no-match, ambiguous, independent-failure and retry cases; record affected row/tour/artist identifiers and final outcomes.
7. Only if a spreadsheet client is actually used, name its version and record text-import, save and re-import results. Do not mark any unobserved item accepted.

## Baseline note

Plan 05-04's conversion case matrix passed. Its summary separately records one `show-lifecycle` failure on the pre-conversion baseline. That failure remains disclosed and must not be represented as a Phase 05 conversion regression or as a passing preservation case.

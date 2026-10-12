---
phase: 05-csv-import-export
status: pending-human-observation
browser: pending
browser_version: pending
spreadsheet_client: pending
spreadsheet_client_version: pending
fixture_url: http://127.0.0.1:32819
fixture_session: /var/folders/1l/t8mktk5j58g75tthg0q79qhw0000gn/T//gigpress-browser-bkTGXa/session.json
---

# Phase 05 CSV Browser Acceptance

This is an evidence form. No browser or spreadsheet-client observation has been recorded yet. Automated container/HTTP evidence does not fill these rows. D-17 retains exact CSV values and accepts direct-open formula risk. The UI's text-import steps are explicitly Excel-specific; no Excel or other spreadsheet-client workflow has been observed, so client acceptance remains pending and no client-specific outcome is claimed.

| Decision | Required observation | Browser/client identity | Result/evidence | Status |
|---|---|---|---|---|
| D-01 | With JavaScript disabled, Import is first and Export remains visible below it. | Pending | Pending | Pending |
| D-02 | Essential CSV requirements are visible; the column reference disclosure opens and lists formats/examples accessibly. | Pending | Pending | Pending |
| D-03 | Related-post checkbox is visible and unchecked; destination-site exact post ID explanation is clear. | Pending | Pending | Pending |
| D-04 | Action labels are exactly “Import shows” and “Download CSV.” | Pending | Pending | Pending |
| D-05 | Real multipart import shows imported, duplicate, rejected and failed counts with expandable outcome groups. | Pending user's browser/version | User reported first upload: “Imported (1)”; Row 2: The Compatibility Band — 2026-10-03 — The Test Hall — “Show imported and verified.” Unchanged reupload: 0 imported · 1 duplicate · 0 rejected · 0 failed; Duplicates (1) lists Row 2 and says the matching show was left unchanged. | Pass; browser/version pending |
| D-06 | Rejected/failed details identify CSV row, show identity, affected field/reason and correction or retry guidance. | Pending | Pending | Pending |
| D-07 | After import the result remains on Import/Export and offers View shows and Upload another CSV. | Pending | Pending | Pending |
| D-08 | Corrected reupload is safe; matching existing shows are duplicates and are not overwritten. | Pending user's browser/version | User reuploaded the same show identity with changed Notes; GigPress reported 0 imported · 1 duplicate · 0 rejected · 0 failed. User opened the existing show for editing and confirmed Notes still read “Quoted, durable notes.” | Pass; browser/version pending |
| D-09 | All-dates, upcoming and past selections display the actual artist/tour/scope/count and download matching rows. | Pending user's browser/version | Three files match the requested scopes: All dates has 27 rows, Upcoming has 26 rows dated 2032-05-06, and Past has the one 2026-10-03 sample. On the refreshed reactive UI, user confirmed the live summary “Artist: All artists; Tour: All tours; Date scope: Upcoming dates; matching shows: 26” and confirmed Download CSV without Apply returned the expected 26 upcoming shows. | Pass; browser/version pending |
| D-10 | Empty selection creates no download, retains current choices and offers Reset filters; choosing Reset restores all three defaults. | Pending user's browser/version | User selected The Compatibility Band / All tours / Upcoming dates and confirmed the live summary showed 0 matching shows without Apply; they noted there is no show row for that artist. On Download CSV, no file downloaded and the no-matches notice appeared. User confirmed Reset filters restored defaults. | Pass; browser/version pending |
| D-11 | Choices last for the current visit and a later visit uses existing defaults. | Pending user's browser/version | User selected Past dates, downloaded CSV, and confirmed Past remained selected. After leaving Import/Export and reopening it from the menu, All artists / All tours / All dates were restored. | Pass; browser/version pending |
| D-12 | Visible spreadsheet note and disclosure explain text import and preserving exact values for re-import. | Pending user's browser/version | User confirmed the visible Export warning is clear and the expanded “Import spreadsheet values as text” guidance explains Excel text-import and preserving exact values for re-import. | Pass; browser/version pending |
| D-13 | Existing status `0` remains unchanged and is identified for manual correction. | User-provided browser page text; browser/version pending | User pasted the expanded disclosure sentence “Status 0 is exported unchanged and needs manual attention.” The automated `export-literals` check confirmed the exported legacy Status value remains exactly `0`. | Pass; browser/version pending |
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

The final Phase 02 preservation rerun now passes all 11 cases in all six runtime cells. The earlier `show-lifecycle` failure came from test expectations that still treated the retired one-click conversion as an active write path and sent CSV import without its required POST action. The test now covers the guarded legacy rejection and current import request contract; confirmed conversion remains covered by the Phase 05 conversion cases.
